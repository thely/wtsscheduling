<?php echo $wrapper_before; ?>
<?php echo $field_before; ?>
	<input type="hidden" name="<?php echo $field_name; ?>" value="1" data-field="<?php echo $field_base_id; ?>">
	<div id="<?php echo $field_id; ?>" style="position: relative;"></div>	
	<?php echo $field_required; ?>
	<?php echo $field_caption; ?>
<?php echo $field_after; ?>
<?php echo $wrapper_after; ?>

<script type="text/javascript">

var calChanges = {}; //JS Object to become JSON string output
calChanges['events'] = {};
var CAL_DIV = "#<?php echo $field_id; ?>";  //div ID of the calendar's location

var tempId = 0;

var updateCalChanges = function(newEvent) {
//newData is always a single event's addition/change
	if (newEvent['mode'] == "select") {
		calChanges = newEvent;
	}
	else if (newEvent['mode'] == "update") {
		if ("tempId" in newEvent) { //moving around a newly created event
			newEvent['mode'] = "insert";
			calChanges[newEvent['tempId']] = newEvent;
		}
		else { //moving around an existing event
			calChanges[newEvent['id']] = newEvent;
		}
	}
	else if (newEvent['mode'] == "insert") { //creating a new event
		calChanges[newEvent["tempId"]] = newEvent;
	}
	console.log(encodeURI(JSON.stringify(calChanges, null, '\t')));
	jQuery("input[name=<?php echo $field_name;?>]").val(encodeURI(JSON.stringify(calChanges)));
}

var isEditable = function() {
	var isEdit = "<?php echo $field['config']['editable']?>";
	return isEdit ? true : false;
}
var fcMakeView = function() {
	var weekly = "<?php echo $field['config']['weekly']; ?>";
	var monthly = "<?php echo $field['config']['monthly']; ?>";
	var daily = "<?php echo $field['config']['daily']; ?>";

	var views = [];
	if (monthly) { views[0] = "month"; }
	if (weekly) { views[views.length] = "agendaWeek"; }
	if (daily) { views[views.length] = "agendaDay"; }

	return views.toString();
}

jQuery(document).ready(function() {
	jQuery(CAL_DIV).fullCalendar({
		//Event sources
	    googleCalendarApiKey: "<?php echo $field['config']['api_key']; ?>",
	    events: {
	        googleCalendarId: "<?php echo Caldera_forms::do_magic_tags($field['config']['cal_id']); ?>",
	        className: 'test_events',
	    },
	    //View generation
	    header: {
	    	left: "prev,next today",
	    	center: "title",
	    	right: fcMakeView()
	    },
	    minTime: "07:00:00",
	    maxTime: "20:00:00",
	    //Selecting, for student sign-up
	    eventClick: function(calEvent) {
	    	if (!isEditable()) {
		        var id = calEvent.id;
		        id = id.replace("@google.com", "");
		       	var eventData = {
		    		mode: "select",
		    		id: id
		    	};
		    	updateCalChanges(eventData);
	    	}
	    	return false;
	    },
	    //Adding new calendar events
	    selectable: isEditable(),
	    selectHelper: true,
	    selectOverlap: false,
	    select: function(start, end) {
	    	var eventData = {
	    		title: "new tutoring time",
	    		start: start,
	    		end: end,
	    		tempId: "temp" + tempId
	    	};
	    	jQuery(CAL_DIV).fullCalendar('renderEvent', eventData, true);
	    	eventData["mode"] = "insert";
	    	eventData["id"] = "";
	    	tempId++;
	    	updateCalChanges(eventData);
	    },

	    //Dragging/dropping, for updating existing events
	    eventStartEditable: isEditable(),
	    eventDurationEditable: isEditable(),
	    eventLimit: true,
	    eventOverlap: false,
	    eventDrop: function(event, delta, revertFunc) {
	    	var eventData = {
	    		mode: "update",
	    		id: event.id,
	    		start: event.start.format(),
	    		end: event.end.format()
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
	    		end: event.end.format()
	    	};
	    	if ("tempId" in event) {
	    		eventData['tempId'] = event.tempId;
	    	}
	    	updateCalChanges(eventData);
	    }
	});
});

</script>
