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

$all_pods          = pods_api()->load_pods( array( 'names' => true ) );
?>

<div class="caldera-config-group">
	<label><?php echo __('Pod', 'pods-caldera-forms' ); ?></label>
	<div class="caldera-config-field">		
		<select id="{{_id}}_pod" class="block-input required field-config ajax-trigger" data-autoload="true" data-id="{{_id}}" data-name="{{_name}}" data-before="set_config_{{_id}}" data-callback="rebuild_field_binding" data-action="pods_cf_load_fields" data-target="#pods-binding-{{_id}}" data-event="change" name="{{_name}}[pod]" value="{{pod}}" required>
			<option value=""><?php echo __( 'Select a Pod', 'pods-caldera-forms' ); ?></option>
			<?php foreach ( $all_pods as $name => $label ) { ?>
			<option value="<?php echo $name; ?>"{{#is pod value="<?php echo $name;?>"}} selected="selected"{{/is}}><?php echo $label; ?></option>
			<?php } ?>
		</select>
	</div>
</div>

<div class="caldera-config-group">
	<label>Filter this pod by (Pods SQL query)</label>
	<div class="caldera-config-field">
		<input type="text" id="{{_id}}_filter_by" class="block-input field-config magic-tag-enabled" name="{{_name}}[filter_by]" value="{{filter_by}}">
	</div>
</div>

<div class="caldera-config-group">
	<label>Field to filter from</label>
	<div class="caldera-config-field">
		{{{_field slug="filter_id"}}}
	</div>
</div>

<div class="caldera-config-group">
	<label>Additional desired fields from Pods</label>
	<div class="caldera-config-field">
		<input type="text" id="{{_id}}_extra_fields" class="block-input field-config magic-tag-enabled" name="{{_name}}[extra_fields]" value="{{extra_fields}}">
	</div>
</div>

<div class="caldera-config-group">
	<label>Enable multi-select</label>
	<div class="caldera-config-field">
		<label><input type="checkbox" id="{{_id}}_multi" class="field-config" name="{{_name}}[multi]" value="1" {{#if my_option}}checked="checked"{{/if}}></label>
	</div>
</div>

<div class="caldera-config-group">
	<label>Hide field output (no dropdown)</label>
	<div class="caldera-config-field">
		<label><input type="checkbox" id="{{_id}}_hide" class="field-config" name="{{_name}}[hide]" value="1" {{#if my_option}}checked="checked"{{/if}}></label>
	</div>
</div>