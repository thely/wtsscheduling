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
 *<label for="{{_id}}_api_key">API Key</label>
 *	<div class="caldera-config-field">
 *		<input type="text" id="{{_id}}_api_key" class="block-input field-config magic-tag-enabled required" name="{{_name}}[api_key]" value="{{api_key}}">
 */
?>
<div class="caldera-config-group">
	<label for="{{_id}}_service_account_name">Service Account Name</label>
	<div class="caldera-config-field">
		<input type="text" id="{{_id}}_service_account" class="block-input field-config magic-tag-enabled required" name="{{_name}}[service_account]" value="{{service_account}}">
		<p>Required. Email for the service account</p>
	</div>
</div>
<div class="caldera-config-group">
	<label for="{{_id}}_key_file_location">Key file</label>
	<div class="caldera-config-field">
		<input type="text" id="{{_id}}_key_file_location" class="block-input field-config magic-tag-enabled required" name="{{_name}}[key_file_location]" value="{{key_file_location}}">
		<p>Required. Location of the private key file</p>
	</div>
</div>
