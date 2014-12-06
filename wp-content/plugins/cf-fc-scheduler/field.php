<?php
// Getting id of field that holds calendar information.
$calendar_info_id = Caldera_forms::do_magic_tags($field['config']['calendar_info']);
?>

<?php echo $wrapper_before; ?>
<?php echo $field_before; ?>
	<input type="hidden" name="<?php echo $field_name; ?>" value="1" data-field="<?php echo $field_base_id; ?>">
	<div id="<?php echo $field_id; ?>" style="position: relative;"></div>	
	<?php echo $field_required; ?>
	<?php echo $field_caption; ?>
<?php echo $field_after; ?>
<?php echo $wrapper_after; ?>
<?php
if (!function_exists('getConfigAsBool')) {
	// Takes a key and returns "true" if the key is present in $config and
	// has a truthy value, "false" otherwise.
	function getConfigAsBool($key, $field)
	{
		return (array_key_exists($key, $field['config']) &&
			  	$field['config'][$key]) ? "true" : "false";
	}
}
?>

<div id="event-dialog-<?php echo $field_id; ?>" title="Edit Event">
  <p class="validateTips">All form fields are required.</p>
  <form role="form">
  <span class="ui-helper-hidden-accessible"><input type="text"/></span>
    <fieldset>
    	<div class="event-center-radio">
    		<label for="event-center">Center</label>
    	</div>
    	<div>
	      <label for="event-start">Start</label>
	      <input name="event-start" class="event-start">
    	</div>
    	<div>
	      <label for="event-end">End</label>
	      <input name="event-end" class="event-end">
    	</div>
      
      <!-- Allow form submission with keyboard without duplicating the dialog button -->
      <input type="submit" tabindex="-1" style="position:absolute; top:-1000px">
    </fieldset>
  </form>
</div>

<script type="text/javascript">

jQuery(document).ready(function() {
	// Object whose state corresponds to the value of this field.
	var calChanges = {};

	// Object holding the edited events.
	calChanges['events'] = {};

	// id of the current field.
	var field_id = "<?php echo $field_id; ?>";

	// id of the fullcalendar element.
	var CAL_DIV = "#" + field_id;

	// id of event-editing modal for this calendar.
	var MODAL_DIV = "#event-dialog-" + field_id;

	// id of input that has calendar information.
	var calendar_info_id = "<?php echo $calendar_info_id; ?>_1";

	// Google Calendar information and calendar<->center correspondence.
	var calendar_info = JSON.parse(jQuery('#' + calendar_info_id).val());

	// Calendar id by name of associated center.
	var calendars_by_center = {};
	var centers_by_calendar = {};
	calendar_info.forEach(function(info) {
		calendars_by_center[info['center.name']] = info['calendar_id'];
		centers_by_calendar[info['calendar_id']] = info['center.name'];
	});

	// List of centers.
	var centers = calendar_info.map(function(info) {
		return info['center.name'];
	});

	// Google Calendar ids to use in populating calendar.
	var calendar_ids = calendar_info.map(function(info) {
		return info['calendar_id'];
	});

	// API Key for call to Google Calendar.
	var google_calendar_api_key = "<?php echo $field['config']['api_key']; ?>";

	// Whether or not the events are editable, per the field configuration.
	var isEditable = <?php echo getConfigAsBool('editable', $field); ?>;
	var isEditableInMonthlyView = <?php echo getConfigAsBool('monthlyselecting', $field); ?>;
	// Counter used as placeholder for id field for newly-created events.
	var tempId = 0;

	// Form elements for event dialog box.
	var modal_start_picker = jQuery(MODAL_DIV + ' .event-start');
	var modal_end_picker = jQuery(MODAL_DIV + ' .event-end');

	/*
	 * Serializes `calChanges` to JSON and sets the value of the calendar
	 * to that JSON representation, which will be the return of the field
	 * when the form is submitted.
	 */
	var updateFieldValue = function() {
		//console.debug(calChanges); // DEBUG
		jQuery("input[name=<?php echo $field_name;?>]").val(encodeURI(JSON.stringify(calChanges)));
	}

	/*
	 * Adds or reconciles newEvent with the existing changes made using the
	 * calendar and updates the value for this calendar field.
	 * @param	 newEvent  {object}  the event information 
	 */
	var updateCalChanges = function(newEvent) {
		// Set mode for scheduling processor.
		if (newEvent['mode'] == "update") {
			// Making changes to an event not yet saved on GCal.
			if ("tempId" in newEvent) {
				newEvent['mode'] = "insert";
				calChanges['events'][newEvent['tempId']] = newEvent;
			} else { // Making changes to an existing event.
				calChanges['events'][newEvent['id']] = newEvent;
			}
		} else if (newEvent['mode'] == "insert") { // Creating a new event.
			calChanges['events'][newEvent["tempId"]] = newEvent;
		} else if (newEvent['mode'] == "remove") { // Remove event.
			// Event not yet saved on GCal, just remove it from the
			// list of changes.
			if ("tempId" in newEvent) {
				delete calChanges['events'][newEvent["tempId"]];
			} else { // Removing an existing event.
				calChanges['events'][newEvent["id"]] = newEvent;
			}
		}
		//console.log(JSON.stringify(calChanges)); // DEBUG
		updateFieldValue();
	}

	var fcMakeView = function() {
		var weekly = <?php echo getConfigAsBool('weekly', $field); ?>;
		var monthly = <?php echo getConfigAsBool('monthly', $field); ?>;
		var daily = <?php echo getConfigAsBool('daily', $field); ?>;

		var views = [];
		if (monthly) { views[0] = "month"; }
		if (weekly) { views[views.length] = "agendaWeek"; }
		if (daily) { views[views.length] = "agendaDay"; }

		return views.toString();
	}

	/* Setting up dialog for event editing. */
	// Handler for the "Save" button on the event-editing modal.
	// This updates the selected event with the information in the modal
	// form.
	// TODO: Check for overlap caused by editing of event, or for
	// the time of the event to go outside of the allowed times.
	var editEvent = function() {
		var calEvent = eventDialog.data('current-event');
		var new_event = eventDialog.data('new-event');

		eventDialog.data('current-event', null);
		eventDialog.data('new-event', null);

		var startTime = modal_start_picker.datetimepicker('getDate');
		var endTime = modal_end_picker.datetimepicker('getDate');
		var center_selected = eventDialog.find("input[type='radio']:checked").val();
		//  Update event with new times.
		calEvent.start.hour(startTime.getHours());
		calEvent.start.minutes(startTime.getMinutes());
		calEvent.end.hour(endTime.getHours());
		calEvent.end.minutes(endTime.getMinutes());
		calEvent['calendar_id'] = calendars_by_center[center_selected];
		// Update field data.
		updateCalChanges(calEvent);

		// Update calendar display if new.
		if (new_event) {
			jQuery(CAL_DIV).fullCalendar('renderEvent', calEvent, true);
			tempId++;
		} else {
			jQuery(CAL_DIV).fullCalendar('updateEvent', calEvent);
		}
		// Close event editor.
		eventDialog.dialog("close");
	}

	// Handler for the "Delete" button on the event-editing modal.
	var removeEvent = function() {
		var calEvent = eventDialog.data('current-event');
		var new_event = eventDialog.data('new-event');

		eventDialog.data('current-event', null);
		eventDialog.data('new-event', null);

		// Update field and event if this was not new event, otherwise
		// just close the modal.
		if (!new_event) {
			calEvent['mode'] = "remove";
			// Update field data.
			updateCalChanges(calEvent);
			// Update calendar display.
			jQuery(CAL_DIV).fullCalendar('removeEvents', calEvent["id"]);
		}

		// Close event editor.
		eventDialog.dialog("close");
	}

	// Create jquery-ui dialog for event property editing.
	var eventDialog = jQuery(MODAL_DIV).dialog({
		autoOpen: false,
		height: 300,
		width: 350,
		modal: true,
		buttons: {
			Save: editEvent,
			Cancel: function() {
				eventDialog.dialog("close");
			},
			Delete: removeEvent
		},
		open: function() {
			//jQuery(':focus', this).blur();
		},
		close: function() {
			//formElt[0].reset();
			//allFields.removeClass("ui-state-error");
		}
	});

	/*
	 * Set up modal dialog form to reflect information from passed event.
	 * @param  event  {object}  the calendar event to populate information using.
	 * @param  new_event  {boolean}  (optional) whether this is a new event.
	 */
	var setModalForEvent = function(event, new_event) {
		// Set start and end time pickers.
		var startDate = event.start.toDate();
		var endDate = event.end.toDate();
		modal_start_picker.datetimepicker('setDate', startDate);
		modal_end_picker.datetimepicker('setDate', endDate);
		eventDialog.find('[name="event-center"]').val([event['center']]);

		eventDialog.data('current-event', event);
		if (new_event) {
			eventDialog.data('new-event', true);
			eventDialog.find('input[type=radio]').prop('disabled', false);
		} else {
			// Disable center-selection div.
			eventDialog.find('input[type=radio]').prop('disabled', true);
		}
	}

	// Handle form submit from modal in case enter key is pressed instead
	// of button.
	formElt = eventDialog.find("form").on("submit", function(event) {
		event.preventDefault();
		editEvent();
	});

	// Set up time pickers on modal dialog form.
	modal_start_picker.timepicker({
		controlType: 'select',
		hourMin: 7,
		hourMax: 20
	});

	modal_end_picker.timepicker({
		controlType: 'select',
		hourMin: 7,
		hourMax: 20
	});

	// Add centers as radio inputs to dialog.
	centers.forEach(function(center, i) {
		var radio_attrs = {
			type: 'radio',
			name: 'event-center',
			value: center,
			class: center
		}

		var radio_container = jQuery(MODAL_DIV + ' .event-center-radio');
		jQuery('<input>').attr(radio_attrs).appendTo(radio_container).after(center);
	});

	/*
	 * Handle clicks on existing events.
	 * TODO: ensure reserved events can't be edited.
	 * @param	calEvent	{Event Object}	the event clicked.
	 */
	var eventClickHandler = function(calEvent) {
		// Set modal form with data from calendar event.
		setModalForEvent(calEvent);
		// Show the modal.
		eventDialog.dialog("open");
		return false;
	}

	//Helper function for eventRendering; makes sure all extProps keys exist.
	var renderingPreReqsHelper = function(calEvent) {
		if ("extendedProperties" in calEvent) { 
			if ("private" in calEvent.extendedProperties) {
				if ("is_reserved" in calEvent.extendedProperties['private']) {
					return true;
				} else { return false; }
			} else { return false; }
		} else { return false; }
	}

	/*
	 * Acts on events if their "is_reserved" extendedProperty is set to "true." 
	 * For students, this hides the event entirely; for tutors, it makes the
	 * event uneditable and changes the color scheme slightly.
	 *
	 * Note: this is where course-filtering and tutor-filtering will happen
	 * when the time comes!
	 */
	var eventRenderHandler = function(calEvent, element) {
		// Set necessary event properties.
		if (!calEvent.hasOwnProperty("calendar_id") && calEvent.hasOwnProperty('source')) {
			calEvent["calendar_id"] = calEvent.source.googleCalendarId;
			calEvent["center"] = centers_by_calendar[calEvent["calendar_id"]];
		}

		if (renderingPreReqsHelper(calEvent)) {
			if (calEvent.extendedProperties['private']['is_reserved'] === "true"){ 
				//immobilize reserved times for the tutors
				console.log(element); // DEBUG
				//jQuery(element).css("border", "1px solid gray");
				jQuery(element).css("background-color", "#777");
				calEvent.eventColor = "blue";
				calEvent.startEditable = false;
				calEvent.durationEditable = false;
			}
		}
	}
	
	/*
	 * Handle selection of empty time slot/day. If the time slot clicked
	 * was in weekly or daily view, start and end will correspond to the
	 * start and end time of the slot clicked, otherwise they will be
	 * ambiguous moments.
	 *
	 * @param	start	{Moment}	the start time of the time slot clicked.
	 * @param	end	{Moment}	the end time of the time slot clicked.
	 */
	var selectHandler = function(start, end) {
		// Creates a new event with the given start and end time and opens
		// the event-editing modal.
		var createNewEvent = function(newStart, newEnd) {
			var eventData = {
				title: "new tutoring time",
				start: start,
				end: end,
				tempId: "temp" + tempId,
				id: "temp" + tempId,
				mode: "insert",
				center: centers[0]
			};

			setModalForEvent(eventData, true);
			eventDialog.dialog("open");
		}
		// Ensure selected time is unambiguous.
		if (start.hasTime()) {
			createNewEvent(start, end);
		}
	}

	// Put calendar ids into format to use as event sources.
	var event_sources = calendar_ids.map(function(calendar_id) {
		return {
			"googleCalendarId": calendar_id,
			"googleCalendarApiKey": google_calendar_api_key
		}
	}, this);

	// Create FullCalendar element, sources are created
	jQuery(CAL_DIV).fullCalendar({
		eventSources: event_sources,
		//View generation
		header: {
			left: "prev,next today",
			center: "title",
			right: fcMakeView()
		},
		minTime: "07:00:00",
		maxTime: "20:00:00",
		timezone: "local",
		eventClick: eventClickHandler,
		eventRender: eventRenderHandler,
		// For adding new calendar events
		selectable: {
			month: isEditableInMonthlyView,
			'default': isEditable
		},
		selectHelper: true,
		selectOverlap: false,
		select: selectHandler,
		//Dragging/dropping, for updating existing events
		eventStartEditable: isEditable,
		eventDurationEditable: isEditable,
		eventLimit: true,
		eventOverlap: false,
		eventDrop: function(event, delta, revertFunc) {
			event["mode"] = "update";
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
			updateCalChanges(eventData);
		},
		eventResize: function(event, delta, revertFunc) {
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
			updateCalChanges(eventData);
		}
	});
});
</script>
