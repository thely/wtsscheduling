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
	    	right: fc_make_view()
	    }
	});
});

var fc_make_view = function() {
	var weekly = "<?php echo $field['config']['weekly']; ?>";
	var monthly = "<?php echo $field['config']['monthly']; ?>";
	var daily = "<?php echo $field['config']['daily']; ?>";

	var views = [];
	if (monthly) { views[0] = "month"; }
	if (weekly) { views[views.length] = "agendaWeek"; }
	if (daily) { views[views.length] = "agendaDay"; }

	return views.toString();
}

/*jQuery('.caldera-editor-body').on('keyup', '.cfdatepicker-set-format', function(){
	var format_field	= $(this),
		default_field	= format_field.closest('.caldera-config-field-setup').find('.is-cfdatepicker');

	default_field.data('date-format', format_field.val());

	default_field.cfdatepicker('remove');

});*/


</script>

<?php /*
<div style="position: relative;" <?php if(!empty($field['config']['showval'])){ ?>class="row"<?php } ?>>
	<?php
		if(!empty($field['config']['showval'])){ ?>
			<div class="col-xs-8" 
				style="margin: <?php if(!empty($field['config']['pollyfill'])){ 
					echo '2px'; }
				else { 
					echo '8px'; } ?> 0px;">
		<?php }
		else{ ?>
			<div style="margin: <?php if(!empty($field['config']['pollyfill'])){ 
				echo '6px'; }
			else{ echo '12px'; } ?> 0px;">
		<?php } ?>
		<input id="<?php echo $field_id; ?>" type="range" data-handle="<?php echo $field['config']['handle']; ?>" data-trackcolor="<?php echo $field['config']['trackcolor']; ?>" data-handleborder="<?php echo $field['config']['handleborder']; ?>" data-color="<?php echo $field['config']['color']; ?>" data-field="<?php echo $field_base_id; ?>" name="<?php echo $field_name; ?>" value="<?php echo $field_value; ?>" min="<?php echo $field['config']['min']; ?>" max="<?php echo $field['config']['max']; ?>" step="<?php echo $field['config']['step']; ?>" <?php echo $field_required; ?>>
	</div>
	<?php if(!empty($field['config']['showval'])){ ?><div class="col-xs-4"><?php if(!empty($field['config']['prefix'])){echo $field['config']['prefix']; } ?><span id="<?php echo $field_id; ?>_value"><?php echo $field_value; ?></span><?php if(!empty($field['config']['suffix'])){echo $field['config']['suffix']; } ?></div><?php } ?>
</div>
*/ ?>