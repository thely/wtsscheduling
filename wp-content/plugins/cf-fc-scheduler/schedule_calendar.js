/*
 * To use, make a new schedule calendar object, passing in all required
 * parameters, add permissions with the set_permissions function, then
 * call init with the modal object to initialize the calendar itself.
 *
 * @param  element_id  {string}  the id of the element to use for the
 * calendar display.
 * @param  element_name  {string}  the name of the input element that
 * will be sent on form submission.
 * @param  gcal_api_key  {string}  the api key to use in the call to
 * Google Calendar.
 * @param  start_time  {moment}  the earliest allowed start time for
 * an event on a given day.
 * @param  end_time  {moment}  the latest allowed end time for an
 * event on a given day.
 * @param  calendar_info  {array}  associated Google Calendar information
 * as retrieved from the bound pods field.
 */
var ScheduleCalendar = function(element_id, element_name, gcal_api_key,
  start_time, end_time, calendar_info) {
  this.id = element_id;
  this.name = element_name;
  this.calendar = jQuery('#' + this.id);
  this.start_time = start_time;
  this.end_time = end_time;

  // Object whose state corresponds to the value of this field.
  this.changes = {};

  // Object holding the edited events.
  this.changes['events'] = {};

  // Counter used as placeholder for id field for newly-created events.
  this.tempId = 0;

  this.gcal_api_key = gcal_api_key;

  this.setup_calendar_info(calendar_info);
}

// Take in representation of Google Calendar information and create
// internal representations of the data.
ScheduleCalendar.prototype.setup_calendar_info = function(calendar_info) {
  // Calendar id by name of associated center.
  this.calendars_by_center = {};
  this.centers_by_calendar = {};
  calendar_info.forEach(function(info) {
    this.calendars_by_center[info['center.name']] = info['calendar_id'];
    this.centers_by_calendar[info['calendar_id']] = info['center.name'];
  }, this);

  // List of centers.
  this.centers = calendar_info.map(function(info) {
    return info['center.name'];
  });

  // Google Calendar ids to use in populating calendar.
  this.calendar_ids = calendar_info.map(function(info) {
    return info['calendar_id'];
  });

  // Put calendar ids into format to use as event sources.
  this.event_sources = this.calendar_ids.map(function(calendar_id) {
    return {
      "googleCalendarId": calendar_id,
      "googleCalendarApiKey": this.gcal_api_key
    }
  }, this);
}

// Sets the permissions of the calendar. `permissions` is an object that
// contains information retrieved from the form config fields.
ScheduleCalendar.prototype.set_permissions = function(permissions) {
  this.permissions = permissions;
  this.permissions_set = true;
};

// Permissions must have been set before getting view info.
ScheduleCalendar.prototype.get_view_info = function() {
  var monthly = this.permissions.monthly_view;
  var weekly = this.permissions.weekly_view;
  var daily = this.permissions.daily_view;

  var views = [];
  if (monthly) { views[0] = "month"; }
  if (weekly) { views[views.length] = "agendaWeek"; }
  if (daily) { views[views.length] = "agendaDay"; }

  return views.toString();
};

// Initialize the schedule calendar. Takes in the modal dialog object
// and initiates it as well.
ScheduleCalendar.prototype.init = function(modal) {
  this.modal = modal;
  this.modal.init(this);

  this.calendar.fullCalendar({
    eventSources: this.event_sources,
    //View generation
    header: {
      left: "prev,next today",
      center: "title",
      right: this.get_view_info()
    },
    minTime: this.start_time.format("HH:mm:ss"),
    maxTime: this.end_time.format("HH:mm:ss"),
    timezone: "local",
    eventClick: this.event_click_handler.bind(this),
    eventRender: this.event_render_handler.bind(this),
    // For adding new calendar events
    selectable: {
      month: this.permissions.editable_in_monthly_view,
      'default': this.permissions.editable
    },
    selectHelper: true,
    selectOverlap: false,
    select: this.select_handler.bind(this),
    //Dragging/dropping, for updating existing events
    eventStartEditable: this.permissions.editable,
    eventDurationEditable: this.permissions.editable,
    eventLimit: true,
    eventOverlap: false,
    eventDrop: this.event_drop_handler.bind(this),
    eventResize: this.event_resize_handler.bind(this)
  });
}

/*
 * Handle clicks on existing events.
 * TODO: ensure reserved events can't be edited.
 * @param calEvent  {Event Object}  the event clicked.
 */
ScheduleCalendar.prototype.event_click_handler = function(event) {
  event["mode"] = "update";
  this.modal.show(event);
  return false;
}

ScheduleCalendar.prototype.event_drop_handler = function(event, delta, revertFunc) {
  var eventData = {
    mode: "update",
    id: event.id,
    start: event.start.format(),
    end: event.end.format(),
    calendar_id: event['calendar_id']
  };
  if ("tempId" in event) {
    eventData['tempId'] = event.tempId;
  }
  this.update_changes(eventData);
}

ScheduleCalendar.prototype.event_resize_handler = function(event, delta, revertFunc) {
  this.event_drop_handler(event, delta, revertFunc);
}

/*
 * Handle selection of empty time slot/day. If the time slot clicked
 * was in weekly or daily view, start and end will correspond to the
 * start and end time of the slot clicked, otherwise they will be
 * ambiguous moments.
 *
 * @param start {Moment}  the start time of the time slot clicked.
 * @param end {Moment}  the end time of the time slot clicked.
 */
ScheduleCalendar.prototype.select_handler = function(start, end) {
  // Creates a new event with the given start and end time and opens
  // the event-editing modal.
  var createNewEvent = function(newStart, newEnd) {
    var eventData = {
      title: "new tutoring time",
      start: start,
      end: end,
      tempId: "temp" + this.tempId,
      id: "temp" + this.tempId,
      mode: "insert",
      center: this.centers[0]
    };

    this.modal.show(eventData, true);
  }.bind(this);
  // Ensure selected time is unambiguous.
  if (start.hasTime()) {
    createNewEvent(start, end);
  }
}

/*
 * Acts on events if their "is_reserved" extendedProperty is set to "true." 
 * For students, this hides the event entirely; for tutors, it makes the
 * event uneditable and changes the color scheme slightly.
 *
 * Note: this is where course-filtering and tutor-filtering will happen
 * when the time comes!
 */
ScheduleCalendar.prototype.event_render_handler = function(event, element) {
  // Set necessary event properties.
  if (!event.hasOwnProperty("calendar_id") && event.hasOwnProperty('source')) {
    event["calendar_id"] = event.source.googleCalendarId;
    event["center"] = this.centers_by_calendar[event["calendar_id"]];
  }

  if (this.check_event_properties(event)) {
    if (event.extendedProperties['private']['is_reserved'] === "true"){ 
      //immobilize reserved times for the tutors
      console.log(element); // DEBUG
      //jQuery(element).css("border", "1px solid gray");
      jQuery(element).css("background-color", "#777");
      event.eventColor = "blue";
      event.startEditable = false;
      event.durationEditable = false;
    }
  }
}

// Helper function for event render handler; makes sure all extProps keys exist.
ScheduleCalendar.prototype.check_event_properties = function(event) {
  if ("extendedProperties" in event) { 
    if ("private" in event.extendedProperties) {
      if ("is_reserved" in event.extendedProperties['private']) {
        return true;
      } else { return false; }
    } else { return false; }
  } else { return false; }
}

ScheduleCalendar.prototype.set_view = function(view_info) {
  this.view_info = view_info;
}

// Remove the selected event from the calendar, updating the state of
// the field if necessary.
ScheduleCalendar.prototype.remove = function(event) {
  event["mode"] = "remove";
  // Update field data.
  this.update_changes(event);

  // Update calendar display.
  this.calendar.fullCalendar('removeEvents', event["id"]);
}

// Add the passed event to the calendar and update changes.
ScheduleCalendar.prototype.add = function(event) {
  this.update_changes(event);
  this.calendar.fullCalendar('renderEvent', event, true);
  this.tempId++;
}

/*
 * Takes an event and changes, checks that the changes are valid in the
 * context of the other calendar events and time constraints on the
 * calendar, and applies the changes if they are accepted. `changes` is
 * an event-like object possessing `start`, `end`, and (optionally)
 * `center` properties.
 * @returns  boolean  true if the event was updated successfully, false
 * otherwise.
 */
ScheduleCalendar.prototype.update = function(event, changes) {
  // Set id of changes so checks ignore event to be updated.
  changes.id = event.id;

  // Change changes dates to have same day as event.
  changes.start = moment(changes.start)
    .date(event.start.date())
    .month(event.start.month())
    .year(event.start.year());
  changes.end = moment(changes.end)
    .date(event.end.date())
    .month(event.end.month())
    .year(event.end.year());

  // Check overlap.
  var overlap = this.check_event_overlap(changes);

  if (!overlap) {
    //  Update event with new times.
    event.start.hour(changes.start.hours());
    event.start.minutes(changes.start.minutes());
    event.end.hour(changes.end.hours());
    event.end.minutes(changes.end.minutes());
    event['calendar_id'] = this.calendars_by_center[changes.center];

    this.update_changes(event);
    this.calendar.fullCalendar('updateEvent', event);
    return true;
  } else {
    alert("Overlapping events not allowed!");
    return false;
  }
}

/*
 * Ensure provided event-like object does not overlap any other events,
 * returning true if so, false otherwise. See `update` for a description
 * of the event-like object.
 * From: http://stackoverflow.com/a/8796793/1698058
 */
ScheduleCalendar.prototype.check_event_overlap = function(event) {
  var events = this.calendar.fullCalendar('clientEvents');
  var start = event.start.toDate();
  var end = event.end.toDate();
  for (i in events) {
    if (events[i].id != event.id) {
      var event_start = events[i].start.toDate();
      var event_end = events[i].end.toDate();
      if (!(event_start >= end ||
          event_end <= start)) {
        return true;
      }
    }
  }
  return false;
};

/*
 * Ensure provided event-like object falls within the allowed time for
 * events.
 */
ScheduleCalendar.prototype.check_event_time = function(first_argument) {
  // body...
};

/*
 * Adds or reconciles event with the existing changes made using the
 * calendar and updates the value for this calendar field.
 * @param  newEvent  {object}  the event information 
 */
ScheduleCalendar.prototype.update_changes = function(event) {
  // Remove unnecessary 'source' attribute that causes JSON
  // rendering errors due to circular structure.
  if ("tempId" in event) {
    delete event.source;
  }
  // Set mode for scheduling processor.
  if (event['mode'] == "update") {
    // Making changes to an event not yet saved on GCal.
    if ("tempId" in event) {
      event['mode'] = "insert";
      this.changes['events'][event['tempId']] = event;
    } else { // Making changes to an existing event.
      this.changes['events'][event['id']] = event;
    }
  } else if (event['mode'] == "insert") { // Creating a new event.
    this.changes['events'][event["tempId"]] = event;
  } else if (event['mode'] == "remove") { // Remove event.
    // Event not yet saved on GCal, just remove it from the
    // list of changes.
    if ("tempId" in event) {
      delete this.changes['events'][event["tempId"]];
    } else { // Removing an existing event.
      this.changes['events'][event["id"]] = event;
    }
  }

  this.update_field();
}

/*
 * Serializes `changes` to JSON and sets the value of the calendar
 * to that JSON representation, which will be the return of the field
 * when the form is submitted.
 */
ScheduleCalendar.prototype.update_field = function() {
  jQuery("input[name="+this.name+"]").val(encodeURI(JSON.stringify(this.changes)));
}
