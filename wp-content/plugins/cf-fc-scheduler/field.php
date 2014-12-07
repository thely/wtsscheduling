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

<script type="text/javascript" src="<?php echo plugin_dir_url(__FILE__) . "schedule_calendar.js"; ?>"></script>
<script type="text/javascript" src="<?php echo plugin_dir_url(__FILE__) . "event_modal.js"; ?>"></script>
<script type="text/javascript">

jQuery(document).ready(function() {
	// id of the current field.
	var field_id = "<?php echo $field_id; ?>";

	var field_name = "<?php echo $field_name;?>";

	// id of the fullcalendar element.
	var CAL_ID = field_id;

	// id of event-editing modal for this calendar.
	var MODAL_ID = "event-dialog-" + field_id;

	// id of input that has calendar information.
	var calendar_info_id = "<?php echo $calendar_info_id; ?>_1";

	// Google Calendar information and calendar<->center correspondence.
	var calendar_info = JSON.parse(jQuery('#' + calendar_info_id).val());

	// API Key for call to Google Calendar.
	var google_calendar_api_key = "<?php echo $field['config']['api_key']; ?>";

	// Default earliest time a shift can be scheduled.
	var schedule_lower_bound = moment().hour(7).minute(0).second(0);

	// Default latest time a shift can be scheduled.
	var schedule_upper_bound = moment().hour(20).minute(0).second(0);

	// Permissions set in field configuration.
	var permissions = {
		editable: <?php echo getConfigAsBool('editable', $field); ?>,
		editable_in_monthly_view: <?php echo getConfigAsBool('monthlyselecting', $field); ?>,
		weekly_view: <?php echo getConfigAsBool('weekly', $field); ?>,
		monthly_view: <?php echo getConfigAsBool('monthly', $field); ?>,
		daily_view: <?php echo getConfigAsBool('daily', $field); ?>
	};

	var calendar = new ScheduleCalendar(CAL_ID,
		field_name,
		google_calendar_api_key,
		schedule_lower_bound,
		schedule_upper_bound,
		calendar_info);
	calendar.set_permissions(permissions);

	var modal = new EventModal(MODAL_ID,
		schedule_lower_bound,
		schedule_upper_bound,
		calendar_info);

	calendar.init(modal);
	// This is so the field always looks the same if there are no changes.
	calendar.update_field();
});
</script>
