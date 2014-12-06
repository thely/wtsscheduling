<?php echo $wrapper_before; ?>
<?php echo $field_before; ?>
	<input type="hidden" name="<?php echo $field_name; ?>" value="1" data-field="<?php echo $field_base_id; ?>">
	<div id="<?php echo $field_id; ?>" style="position: relative;"></div>	
	<?php echo $field_required; ?>
	<?php echo $field_caption; ?>
<?php echo $field_after; ?>
<?php echo $wrapper_after; ?>
<?php 
	$new_email = Caldera_forms::do_magic_tags($field['config']['new_email']); 
	$api_key = Caldera_forms::do_magic_tags($field['config']['api_key']);
?>
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
    <fieldset>
      <label for="event-start">Start</label>
      <input name="event-start" class="event-start">
      <label for="event-end">End</label>
      <input name="event-end" class="event-end">
      
      <!-- Allow form submission with keyboard without duplicating the dialog button -->
      <input type="submit" tabindex="-1" style="position:absolute; top:-1000px">
    </fieldset>
  </form>
</div>

<script type="text/javascript">

jQuery(document).ready(function() {
	// JS Object to become JSON string output
	var calChanges = {};
	calChanges['events'] = {};
	// div id of the calendar's location
	var CAL_DIV = "#<?php echo $field_id; ?>";
	// div id of modal for this calendar.
	var MODAL_DIV = "#event-dialog-<?php echo $field_id; ?>";
	var tempId = 0;

	/*
	 * Updates the value corresponding to this calendar field, which will
	 * be the return value to the Caldera Form.
	 */
	var updateFieldValue = function() {
		console.debug(calChanges); // DEBUG
		jQuery("input[name=<?php echo $field_name;?>]").val(encodeURI(JSON.stringify(calChanges)));
	}

	/*
	 * Adds or reconciles newEvent with the existing changes made using the
	 * calendar and updates the value for this calendar field.
	 * @param	newEvent
	 */
	var updateCalChanges = function(newEvent) {
		// Event was selected, used when reserving timeslot(s).
		if (newEvent['mode'] == "select") {
			// Set mode for synth-cal processor.
			calChanges['mode'] = "select";
			calChanges['events'][newEvent['id']] = newEvent;
			calChanges['calendar_id'] = calChanges['events'][newEvent['id']]['source'];
		}
		console.log(JSON.stringify(calChanges));
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
	// Form elements for event dialog box.
	var startElt = jQuery(MODAL_DIV + ' .event-start');
	var endElt = jQuery(MODAL_DIV + ' .event-end');
	//var endElt = $(MODAL_DIV + ' .event-start');

	// Called by modal to update selected event.
	var editEvent = function() {
		var calEvent = eventDialog.data('current-event');
		eventDialog.data('current-event', null);
		var startTime = startElt.datetimepicker('getDate');
		var endTime = endElt.datetimepicker('getDate');
		//  Update event with new times.
		calEvent.start.hour(startTime.getHours());
		calEvent.start.minutes(startTime.getMinutes());
		calEvent.end.hour(endTime.getHours());
		calEvent.end.minutes(endTime.getMinutes());
		// Update field data.
		updateCalChanges(calEvent);
		// Update calendar display.
		jQuery(CAL_DIV).fullCalendar('updateEvent', calEvent);
		// Close event editor.
		eventDialog.dialog("close");
	}

	var removeEvent = function() {
		var calEvent = eventDialog.data('current-event');
		eventDialog.data('current-event', null);
		calEvent['mode'] = "remove";
		// Update field data.
		updateCalChanges(calEvent);
		// Update calendar display.
		jQuery(CAL_DIV).fullCalendar('removeEvents', calEvent["id"]);
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
			jQuery(':focus', this).blur();
		},
		close: function() {
			//formElt[0].reset();
			//allFields.removeClass("ui-state-error");
		}
	});

	formElt = eventDialog.find("form").on("submit", function(event) {
		event.preventDefault();
		editEvent();
	});


	startElt.timepicker({
		controlType: 'select',
		hourMin: 7,
		hourMax: 20
	});

	endElt.timepicker({
		controlType: 'select',
		hourMin: 7,
		hourMax: 20
	});

	/*
	 * Handle clicks on existing events.
	 * @param	calEvent	{Event Object}	the event clicked.
	 */
	var eventClickHandler = function(calEvent) {
		// Select event in non-editable context, e.g. student sign up
		if (!isEditable()) {
			var id = calEvent.id;
			id = id.replace("@google.com", "");
			var eventData = {
				mode: "select",
				id: id,
				source: calEvent.source.googleCalendarId
			};
			console.log("eventData looks like " + JSON.stringify(eventData));
			updateCalChanges(eventData);
		} else {
			// Provide additional editing options.
			// Set start and end times.
			var startDate = calEvent.start.toDate()
			var endDate = calEvent.end.toDate()
			startElt.datetimepicker('setDate', startDate);
			endElt.datetimepicker('setDate', endDate);
			eventDialog.data('current-event', calEvent);
			eventDialog.dialog("open");
		}
		return false;
	}

	//Helper function for eventRendering; makes sure all extProps keys exist.
	var renderingPreReqsHelper = function(calEvent) {
		if ("extendedProperties" in calEvent) { 
			if ("private" in calEvent.extendedProperties) {
				if ("is_reserved" in calEvent.extendedProperties['private']){
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
		if (renderingPreReqsHelper(calEvent)) {
			if (calEvent.extendedProperties['private']['is_reserved'] === "true"){ 
				return false;
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
	 * @param	hasEvents	{Boolean}	optional, whether or not the
	 *   selected date period has existing events. The select event is
	 *   fired by fullcalendar only on empty date cells, this is for 
	 *   other functions to call this method.
	 */
	var selectHandler = function(start, end, hasEvents) {
		// Creates a new event with the given start and end time.
		var createNewEvent = function(newStart, newEnd) {
			var eventData = {
				title: "new tutoring time",
				start: start,
				end: end,
				tempId: "temp" + tempId,
				id: "temp" + tempId
			};
			jQuery(CAL_DIV).fullCalendar('renderEvent', eventData, true);
			eventData["mode"] = "insert";
			tempId++;
			updateCalChanges(eventData);
		}
		// Event created clicking in weekly/daily view.
		if (start.hasTime()) {
			createNewEvent(start, end);
		} else { // Event created clicking in monthly view.
			// Currently should not be invoked.
			/*// There are already times set for this date.
			if (hasEvents) {
				// Get earliest block of 30 minutes available.
				var eventsToday = jQuery(CAL_DIV).fullCalendar('clientEvents', function(event) {
					event.start.isSame(end, 'day');
				});
				console.log(eventsToday);
			} else { // There are no other times set for this date yet.
				var startMoment = this.calendar.moment(end.format());
				startMoment.time('07:00:00');
				var endMoment = moment(startMoment).add(30, 'minutes');
				createNewEvent(startMoment, endMoment);
			}*/
		}
	}

	var getVariableCalendarIds = function() {
		var eventSources = [];
		var source = "#<?php echo $new_email; ?>_1";
		var data = jQuery(source).val();
		if (data == "" || data == null || data == false) {
			return eventSources;
		}
		var calendarIds = JSON.parse(decodeURI(jQuery(source).val()));
		console.log("This is a new thing " + calendarIds);
		for (id in calendarIds) {
			if (calendarIds[id]['calendar_id'] != "") {
				eventSources.push(calendarIds[id]['calendar_id']);
			}
		}
		return eventSources;
	}

	var setCalendarIds = function() {
		console.log("Hello big function friend");
		var apiKey = "<?php echo $api_key; ?>";
		var eventSources = [];
		eventSources = getVariableCalendarIds();
		jQuery(CAL_DIV).fullCalendar('removeEventSources');
		console.log("Sources looks like: " + JSON.stringify(eventSources));
		jQuery.each(eventSources, function(key, val){
			jQuery(CAL_DIV).fullCalendar('addEventSource', { 
				"googleCalendarId": val,
				"googleCalendarApiKey": apiKey
			});
		});
		jQuery(CAL_DIV).fullCalendar('refetchEvents');
	}

	var filter = "#<?php echo $new_email; ?>_1";
	jQuery(filter).change(setCalendarIds);

	// Create FullCalendar element.
	jQuery(CAL_DIV).fullCalendar({
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
		selectable: false,
		//Dragging/dropping, for updating existing events
		eventStartEditable: false,
		eventDurationEditable: false,
		eventLimit: true,
		eventOverlap: false,
	});
});
</script>
