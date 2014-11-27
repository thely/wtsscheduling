<?php echo $wrapper_before; ?>
<?php echo $field_before; ?>
	<input type="hidden" name="<?php echo $field_name; ?>" value="1" data-field="<?php echo $field_base_id; ?>">
	<div id="<?php echo $field_id; ?>" style="position: relative;"></div>	
	<?php echo $field_required; ?>
	<?php echo $field_caption; ?>
<?php echo $field_after; ?>
<?php echo $wrapper_after; ?>

<script type="text/javascript">
jQuery(document).ready(function() {
	jQuery("#<?php echo $field_id; ?>").fullCalendar({
	    googleCalendarApiKey: "<?php echo $field['config']['api_key']; ?>",
	    events: {
	        googleCalendarId: "<?php echo Caldera_forms::do_magic_tags($field['config']['cal_id']); ?>",
	        className: 'test_events'
	    },
	    eventClick: function(calEvent) {
	        var id = calEvent.id;
	        id = id.replace("@google.com", "");
	        jQuery("input[name=<?php echo $field_name;?>]").val(id);
	        //jQuery("#fld_4059292_1").val(id);
	        return false;
	    },
	    header: {
	    	left: "prev,next today",
	    	center: "title",
	    	right: fcMakeView()
	    },
	    minTime: "07:00:00",
	    maxTime: "20:00:00",
	    eventStartEditable: isEditable(),
	    eventDurationEditable: isEditable(),
	    eventLimit: true
	});
});

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


</script>
