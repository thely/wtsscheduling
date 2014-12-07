<?php echo $wrapper_before; ?>
<?php echo $field_before; ?>
	<input type="hidden" name="<?php echo $field_name; ?>" value="1" data-field="<?php echo $field_base_id; ?>">
	<div id="<?php echo $field_id; ?>" style="position: relative;"></div>	
	<?php echo $field_required; ?>
	<?php echo $field_caption; ?>
<?php echo $field_after; ?>
<?php echo $wrapper_after; ?>

<?php 
	$calendar_ids = Caldera_forms::do_magic_tags($field['config']['calendar_ids']); 
	$api_key = Caldera_forms::do_magic_tags($field['config']['api_key']);
	$session_length = Caldera_forms::do_magic_tags($field['config']['session_length'])
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
  <p class="validateTips">Select a timeslot.</p>
 
  <form role="form" id="event-dialog-<?php echo $field_id; ?>-form">
    <fieldset>
      <div id="event-dialog-<?php echo $field_id; ?>-timeselect"></div>
      
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
	var sessionLength = 0;
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
		else if (newEvent['mode'] == "select_split") {
			calChanges = newEvent;
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
	//var startElt = jQuery(MODAL_DIV + ' .event-start');
	//var endElt = jQuery(MODAL_DIV + ' .event-end');
	//-var endElt = $(MODAL_DIV + ' .event-start');

	var lengths = "#<?php echo $session_length; ?>_1";
	sessionLength = jQuery(lengths).val();
	jQuery(lengths).change(function() {
		sessionLength = jQuery(this).val();
		console.log(sessionLength);
	});


	var divideSelectedTime = function() {
		var calEvent = eventDialog.data('current-event');
		var selectors = "#event-dialog-<?php echo $field_id; ?>-timeselect";
		jQuery(selectors).empty();
		var startTime = moment(calEvent.start);
		var endTime = moment(calEvent.end);
		var splitEventData = {};
		//splitEventData['original'] = calEvent.id;
		var formatting = 'YYYY-MM-DD[T]HH:mm:ss';
		var index = 0;
		while (moment.duration(endTime.diff(startTime)).asMinutes() >= sessionLength) {
			//Make a new end time, as a clone of the start time
			var startClone = startTime.clone();
			startClone.add(sessionLength, 'minutes');
			//Add time information to .data()
			splitEventData[index] = {};
			splitEventData[index]['start'] = startTime.format(formatting);
			splitEventData[index]['end'] = startClone.format(formatting);
			var range = startTime.format("h:mm a") + " - " + startClone.format("h:mm a");
			jQuery(selectors).append("<div style='display:block;'><input type='radio' name='" 
					+ calEvent.id + "' value='" + index + "' />" + range + "</div>");
			startTime.add(sessionLength, 'minutes');
			index++;
		}
		eventDialog.data('splitEventData', splitEventData);
		console.log(splitEventData);
	}

	// Called by modal to create the return
	var editEvent = function() {
		var calEvent = eventDialog.data('current-event');
		var splitData = eventDialog.data('splitEventData');
		eventDialog.data('current-event', null);
		var index = jQuery("input[name="+calEvent.id+"]:checked", "#event-dialog-<?php echo $field_id; ?>-form").val();
		var newEvent = {};
		newEvent['mode'] = "select_split";
		newEvent['events'] = splitData;
		newEvent['original'] = calEvent.id;
		newEvent['selected'] = index;
		newEvent['events'] = mergeUnselectedEvents(newEvent['events'], index, Object.keys(newEvent['events']).length-1);

		updateCalChanges(newEvent);
		// Update calendar display.
		//jQuery(CAL_DIV).fullCalendar('updateEvent', calEvent);
		// Close event editor.
		eventDialog.dialog("close");
		return false;
	}

	//Recursively merge unselected events for inserting larger chunks of time into GCal
	var mergeUnselectedEvents = function(events, selected, key) {
		if (key == 0) {
			return events;
		}
		else if (key-1 in events) {
			if (events[key]['start'] == events[key-1]['end'] && 
					key != selected && key-1 != selected) {
				events[key]['start'] = events[key-1]['start'];
				delete events[key-1];
				if (key-2 in events) {
					//console.log(events);
					return mergeUnselectedEvents(events, selected, key-2);
				}
				else {
					//console.log(events);
					return events;
				}
			}
			else {
				//console.log(events);
				return mergeUnselectedEvents(events, selected, key-1);
			}
		}
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
			}
		},
		open: function() {
			divideSelectedTime();
			jQuery(':focus', this).blur();
			return false;
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
	/*startElt.timepicker({
		controlType: 'select',
		hourMin: 7,
		hourMax: 20
	});
	endElt.timepicker({
		controlType: 'select',
		hourMin: 7,
		hourMax: 20
	});*/

	/*
	 * Handle clicks on existing events.
	 * @param	calEvent	{Event Object}	the event clicked.
	 */
	var eventClickHandler = function(calEvent) {
		//Only open the dialog if the time period is greater than the session length
		var dur = moment.duration(moment(calEvent.end).diff(moment(calEvent.start)));
		if (sessionLength != 0 && dur.asMinutes() > sessionLength) {
			eventDialog.data('current-event', calEvent);
			eventDialog.dialog("open");
		}
		//Selecting a time period that matches the session length
		else {
			var id = calEvent.id;
			id = id.replace("@google.com", "");
			var eventData = {
				mode: "select",
				id: id,
				source: calEvent.source.googleCalendarId
			};
			console.log("eventData looks like " + JSON.stringify(eventData));
			updateCalChanges(eventData);
		}
		// Provide additional editing options.
		// Set start and end times.
		/*var startDate = calEvent.start.toDate()
		var endDate = calEvent.end.toDate()
		startElt.datetimepicker('setDate', startDate);
		endElt.datetimepicker('setDate', endDate);
		eventDialog.data('current-event', calEvent);
		eventDialog.dialog("open");*/
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
		var source = "#<?php echo $calendar_ids; ?>_1";
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


	var filter = "#<?php echo $calendar_ids; ?>_1";
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
