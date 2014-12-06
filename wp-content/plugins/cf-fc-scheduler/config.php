<div class="caldera-config-group">
	<label for="{{_id}}_api_key">API Key</label>
	<div class="caldera-config-field">
		<input type="text" id="{{_id}}_api_key" class="block-input field-config magic-tag-enabled required" name="{{_name}}[api_key]" value="{{api_key}}">
	</div>
</div>
<div class="caldera-config-group">
	<label>Calendar Info</label>
	<div class="caldera-config-field">
		{{{_field slug="calendar_info" exclude="system"}}}
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
	<label for="{{_id}}_monthlyselecting">Enable monthly selecting</label>
	<div class="caldera-config-field">
		<input type="checkbox" class="field-config {{_id}}_monthlyselecting" name="{{_name}}[monthlyselecting]" value="1" {{#if monthlyselecting}}checked="checked"{{/if}}>
	</div>
</div>
