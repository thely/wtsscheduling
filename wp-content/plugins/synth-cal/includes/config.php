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
<style>
.hidden-file {
	display: none !important;
}
</style>
<div class="caldera-config-group">
	<label for="{{_id}}_key_file_location">JSON Private Key File</label>
	<div class="caldera-config-field">
		<span id="{{_id}}_key_filename_display">No file selected</span>
		<button class="btn btn-default" id="{{_id}}_key_file_button">Browse...</button>
		<input type="file" id="{{_id}}_key_file_location" class="block-input field-config hidden-file" name="{{_name}}[key_file_location]" value="{{key_file_location}}">
		<p>Required. Location of the private key file</p>
	</div>
</div>
<!-- Input to hold uploaded file name -->
<input type="hidden" id="{{_id}}_key_filename" class="block-input field-config required" name="{{_name}}[key_file_name]" value="{{key_file_name}}">
<!-- Input to hold uploaded file contents -->
<input type="hidden" id="{{_id}}_key_file_contents" class="block-input field-config required" name="{{_name}}[key_file_contents]" value="{{key_file_contents}}">
<div class="caldera-config-group">
	<label for="{{_id}}_service_account_name">Service Account Name</label>
	<div class="caldera-config-field">
		<input type="text" id="{{_id}}_service_account" class="block-input field-config magic-tag-enabled required" name="{{_name}}[service_account]" value="{{service_account}}">
		<p>Required. Email for the service account.</p>
	</div>
</div>
{{#script}}
// Check that File APIs are available.
if (!(window.File && window.FileReader && window.FileList && window.Blob)) {
  alert('Error, File Upload will not work! Try using a different browser.');
}

// Click listeners for hidden file field.
jQuery('#{{_id}}_key_file_button').click(function(e) {
	jQuery('#{{_id}}_key_file_location').click();
	e.preventDefault();
});

jQuery('#{{_id}}_key_filename_display').click(function(e) {
	jQuery('#{{_id}}_key_file_button').click();
	e.preventDefault();
});

/*
 * This function takes an input identifier corresponding to a file
 * input and a label corresponding to the element meant to display
 * the selected file and updates the latter with the value of the
 * former, or "No file selected." if no file has been selected.
 */
function updateSelectedFilePath(input, label) {
  var path = jQuery(input).val();
  if (path === "") {
    jQuery(label).text("No file selected.");
  } else {
    var fileName = path.replace(/.*(\/|\\)/, '');
    jQuery(label).text(fileName);
  }
}

/*
 * Handle key file selection, updating hidden content field and parsing
 * file contents to populate service account field.
 */
function handleKeyFileSelect(evt) {
	var files = evt.target.files;
	if (files.length > 0) {
		var file = files[0];
		var reader = new FileReader();
		reader.onload = (function(f, elt) {
			return function(e) {
				// Set field value.
				var contents = e.target.result;
				if (contents !== "") {
					// Set value of hidden key contents field.
					jQuery('#{{_id}}_key_file_contents').val(contents);

					// File name field.
					jQuery('#{{_id}}_key_filename').val(jQuery(elt).val());
					
					// File name display.
					updateSelectedFilePath(elt, '#{{_id}}_key_filename_display');
				}
			}
		})(file, this);
		reader.readAsDataURL(file);
	} else {
		updateSelectedFilePath(this, '#{{_id}}_key_filename_display');
		jQuery('#{{_id}}_key_file_contents').val("");
		jQuery('#{{_id}}_key_filename').val("");
	}
}
// Update span and hidden fields when file selected. Remember these are
// only triggered when the selected file changes.
jQuery('#{{_id}}_key_file_location').change(handleKeyFileSelect);

// Initially populate file name span with name of file, if previously
// selected.
updateSelectedFilePath('#{{_id}}_key_filename', '#{{_id}}_key_filename_display');

{{/script}}
