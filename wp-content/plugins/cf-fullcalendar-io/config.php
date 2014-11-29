<?php

/**
 * Preview Templates use handlebars.js for structure
 * see http://handlebarsjs.com/ for more on this.
 * 
 * this is passed the default array in the field register under config
 * It also has {{_name}} and {{_id}} these are used to define the config value and is required
 * The class field-config is also required on tehj actual field capture to aid in config objects
 * 
 * Add the class magic-tag-enabled to a text or textarea to create a magic tag enable field
 * 
 */


?>

<div class="caldera-config-group">
	<label for="{{_id}}_api_key">API Key</label>
	<div class="caldera-config-field">
		<input type="text" id="{{_id}}_api_key" class="block-input field-config magic-tag-enabled required" name="{{_name}}[api_key]" value="{{api_key}}">
	</div>
</div>
<div class="caldera-config-group">
	<label for="{{_id}}_cal_id">Calendar ID</label>
	<div class="caldera-config-field">
		<input type="text" id="{{_id}}_cal_id" class="block-input field-config magic-tag-enabled required" name="{{_name}}[cal_id]" value="{{cal_id}}">
	</div>
</div>

<div class="caldera-config-group">
	<label for="{{_id}}_monthly">Enable monthly view</label>
	<div class="caldera-config-field">
		<input type="checkbox" class="field-config" name="{{_name}}[monthly]" value="1" {{#if monthly}}checked="checked"{{/if}}>
	</div>
</div>

<div class="caldera-config-group">
	<label for="{{_id}}_weekly">Enable weekly view</label>
	<div class="caldera-config-field">
		<input type="checkbox" class="field-config" name="{{_name}}[weekly]" value="1" {{#if weekly}}checked="checked"{{/if}}>
	</div>
</div>

<div class="caldera-config-group">
	<label for="{{_id}}_daily">Enable single day view</label>
	<div class="caldera-config-field">
		<input type="checkbox" class="field-config {{_id}}_daily" name="{{_name}}[daily]" value="1" {{#if daily}}checked="checked"{{/if}}>
	</div>
</div>

<div class="caldera-config-group">
	<label for="{{_id}}_editable">Enable adding/dragging events</label>
	<div class="caldera-config-field">
		<input type="checkbox" class="field-config {{_id}}_editable" name="{{_name}}[editable]" value="1" {{#if editable}}checked="checked"{{/if}}>
	</div>
</div>

<div class="caldera-config-group">
	<label for="{{_id}}_monthlyselecting">Enable adding/dragging events</label>
	<div class="caldera-config-field">
		<input type="checkbox" class="field-config {{_id}}_monthlyselecting" name="{{_name}}[monthlyselecting]" value="1" {{#if monthlyselecting}}checked="checked"{{/if}}>
	</div>
</div>

<?php /*<div class="caldera-config-group">
	<label>Input Option</label>
	<div class="caldera-config-field">
		<input type="text" id="{{_id}}_another_option" class="block-input field-config magic-tag-enabled" name="{{_name}}[another_option]" value="{{another_option}}">
	</div>
</div> */?>
