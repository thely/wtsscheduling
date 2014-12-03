<?php
/**
 * Builds the form processor config panel
 *
 */
$pods_api          = pods_api();
$all_pods          = $pods_api->load_pods( array( 'names' => true ) );

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
	<label for="{{_id}}_editable">Edit existing pods</label>
	<div class="caldera-config-field">
		<input type="checkbox" class="field-config" name="{{_name}}[editable]" value="1" {{#if editable}}checked="checked"{{/if}}>
	</div>
</div>
<div id="pods-binding-{{_id}}">
</div>

{{#script}}
	{{#if object_fields}}
	var config_{{_id}} = { {{pod}} : {
	{{#each object_fields}}
		'{{@key}}' : "{{this}}",
	{{/each}}
	{{#each fields}}
		'{{@key}}' : "{{this}}",
	{{/each}}	
		'_all_' : true
	} };
	{{/if}}
	function set_config_{{_id}}(el, ev){
		if(typeof config_{{_id}} !== 'undefined'){
			jQuery(el).data('fields', JSON.stringify( config_{{_id}} ) );
		}
		return true;
	}
{{/script}}

<div class="caldera-config-group">
	<label for="{{_id}}_first_name">First name</label>
	<div class="caldera-config-field">
		<input type=text class="block-input caldera-field-bind magic-tag-enabled field-config" 
			id="{{_id}}_first_name" name="{{_name}}[first_name]" value="{{first_name}}">
	</div>
</div>

<div class="caldera-config-group">
	<label for="{{_id}}_last_name">Last name</label>
	<div class="caldera-config-field">
		<input type=text class="block-input caldera-field-bind magic-tag-enabled field-config" 
			id="{{_id}}_last_name" name="{{_name}}[last_name]" value="{{last_name}}">
	</div>
</div>