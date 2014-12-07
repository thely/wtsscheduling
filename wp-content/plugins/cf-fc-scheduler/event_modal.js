/*
 * EventModal represents the event-editing pop-up window.
 * @param  element_id  {string}  the id of the element to associate
 * with the modal.
 * @param  start_time  {moment}  the earliest time that an event can
 * start.
 * @param  end_time  {moment}  the latest time that an event can end.
 * @param  calendar_info  {array}  an array of objects with calendar_id
 * and center associations.
 */
var EventModal = function(element_id, start_time, end_time, calendar_info) {
  this.id = element_id;
  this.dialog = jQuery('#' + this.id);
  this.start_picker = jQuery('.event-start', this.dialog);
  this.end_picker = jQuery('.event-end', this.dialog);
  this.start_time = start_time;
  this.end_time = end_time;
  this.setup_calendar_info(calendar_info);
}

// Take in representation of Google Calendar information and create
// internal representations of the data.
EventModal.prototype.setup_calendar_info = function(calendar_info) {
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
}

// Initialize the dialog, associating it with the passed calendar.
EventModal.prototype.init = function(calendar) {
  this.calendar = calendar;
  // Set up dialog.
  this.dialog.dialog({
    autoOpen: false,
    height: 300,
    width: 350,
    modal: true,
    buttons: {
      Save: this.save.bind(this),
      Cancel: this.cancel.bind(this),
      Delete: this.remove.bind(this)
    }
  });

  // Handle form submit from modal in case enter key is pressed instead
  // of button.
  this.dialog.find("form").on("submit", function(event) {
    event.preventDefault();
    this.save();
  });

  // Set up time pickers on modal dialog form.
  this.start_picker.timepicker({
    controlType: 'select',
    hourMin: parseInt(this.start_time.format("H")),
    hourMax: parseInt(this.end_time.format("H"))
  });

  this.end_picker.timepicker({
    controlType: 'select',
    hourMin: parseInt(this.start_time.format("H")),
    hourMax: parseInt(this.end_time.format("H"))
  });

  // Add centers as radio inputs to dialog.
  this.centers.forEach(function(center, i) {
    var radio_attrs = {
      type: 'radio',
      name: 'event-center',
      value: center,
      class: center
    }

    var radio_container = jQuery('.event-center-radio', this.dialog);
    jQuery('<input>').attr(radio_attrs).appendTo(radio_container).after(center);
  }, this);
}

// Handler for the "Save" button on the event-editing modal.
// This updates the selected event with the information in the modal
// form.
// TODO: Check for overlap caused by editing of event, or for
// the time of the event to go outside of the allowed times.
EventModal.prototype.save = function() {
  var event = this.dialog.data('current-event');
  var new_event = this.dialog.data('new-event');

  this.dialog.data('current-event', null);
  this.dialog.data('new-event', null);

  var changes = {
    start: moment(this.start_picker.datetimepicker('getDate')),
    end: moment(this.end_picker.datetimepicker('getDate')),
    center: this.dialog.find("input[type='radio']:checked").val()
  };

  

  // Update calendar display if new.
  if (new_event) {
    //  Update event with new times.
    event.start.hour(changes.start.hour());
    event.start.minutes(changes.start.minutes());
    event.end.hour(changes.end.hour());
    event.end.minutes(changes.end.minutes());
    event['calendar_id'] = this.calendars_by_center[changes.center];
    this.calendar.add(event);
  } else {
    this.calendar.update(event, changes);
  }

  // Close event editor.
  this.dialog.dialog("close");
}

EventModal.prototype.cancel = function() {
  this.dialog.dialog("close");
}

// Handler for the "Delete" button on the event-editing modal.
EventModal.prototype.remove = function() {
  var calEvent = this.dialog.data('current-event');
  var new_event = this.dialog.data('new-event');

  this.dialog.data('current-event', null);
  this.dialog.data('new-event', null);

  // Update field and event if this was not new event, otherwise
  // just close the modal.
  if (!new_event) {
    this.calendar.remove(calEvent);
  }

  // Close event editor.
  this.dialog.dialog("close");
}

/*
 * Set up modal dialog form to reflect information from passed event
 * and show the dialog.
 * @param  event  {object}  the calendar event to populate information using.
 * @param  new_event  {boolean}  (optional) whether this is a new event.
 */
EventModal.prototype.show = function(event, new_event) {
  // Set start and end time pickers.
  var startDate = event.start.toDate();
  var endDate = event.end.toDate();
  this.start_picker.datetimepicker('setDate', startDate);
  this.end_picker.datetimepicker('setDate', endDate);
  this.dialog.find('[name="event-center"]').val([event['center']]);

  this.dialog.data('current-event', event);
  if (new_event) {
    this.dialog.data('new-event', true);
    this.dialog.find('input[type=radio]').prop('disabled', false);
  } else {
    // Disable center-selection div.
    this.dialog.find('input[type=radio]').prop('disabled', true);
  }

  this.dialog.dialog("open");
}
