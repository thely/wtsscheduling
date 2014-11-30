<?php
/**
 * The processor config template.
 *
 * This is used to capture configuration settings to be used for the form processor
 * Form Configs are not traditional POST config saves. the whole form config object is built in javascript and the object posted to be saved.
 * This means that all config fields need to have the have the '.field-config' and the name="{{_name}}[config_name]" attribute class in order to be included in the config
 *
 *
 * Additional Tags
 * {{_id}} : unique field ID
 * {{{_field config_name type="field_type" required="true"}}} : Creates a direct bound field select NOTE the tripple {{{
 * {{#script}} ... {{/script}} : inline javascript wrapper for dynamically adding javascript to the template.
 * {{#is config_name value="check_value"}} do this {{/is}} : condition check of value ( for checks, selects etc. )
 *
 * CSS class names
 * .magic-tag-enabled 		: class to set an input / textarea to be magic tag enabled (tags auto complete)
 * .block-input				: full width input
 * .field-config			: required to set the field as a config setting
 * .caldera-config-group	: field wrapper class for styling
 * .caldera-config-field	: field inner wrapper for styling
 * .required				: class name to specify the field is required
 *
 *
 *
 * @since      1.0.0
 * @package    Reservation_Processor
 */

?>
<div class="caldera-config-group">
	<label for="{{_id}}_event_ids"><?php _e('Event IDs', 'schedule_processor'); ?></label>
	<div class="caldera-config-field">
		<input type="text" id="{{_id}}_event_ids" class="block-input field-config magic-tag-enabled required" name="{{_name}}[event_ids]" value="{{event_ids}}">
		<p>Required. Tag corresponding to calendar field output.</p>
	</div>
</div>
<div class="caldera-config-group">
  <label for="{{_id}}_calendar_id">Calendar ID</label>
  <div class="caldera-config-field">
    <input type="text" id="{{_id}}_calendar_id" class="block-input field-config magic-tag-enabled required" name="{{_name}}[calendar_id]" value="{{calendar_id}}">
    <p>Required. This must be set to the Calendar ID.</p>
  </div>
</div>
