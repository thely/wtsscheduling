<?php
$all_pods = pods_api()->load_pods( array( 'names' => true ) );
?>

<div class="caldera-config-group">
	<label>Pod</label>
	<div class="caldera-config-field">		
		<select id="{{_id}}_pod" class="block-input required field-config ajax-trigger" data-autoload="true" data-id="{{_id}}" data-name="{{_name}}" data-before="set_config_{{_id}}" data-callback="rebuild_field_binding" data-action="pods_cf_load_fields" data-target="#pods-binding-{{_id}}" data-event="change" name="{{_name}}[pod]" value="{{pod}}" required>
			<option value="">Select a Pod</option>
			<?php foreach ( $all_pods as $name => $label ) { ?>
			<option value="<?php echo $name; ?>"{{#is pod value="<?php echo $name;?>"}} selected="selected"{{/is}}><?php echo $label; ?></option>
			<?php } ?>
		</select>
	</div>
</div>

<div class="caldera-config-group">
	<label title="This is used to select the desired pods.">Where query</label>
	<div class="caldera-config-field">
		<input type="text" id="{{_id}}_where_query" class="block-input field-config magic-tag-enabled required" name="{{_name}}[where_query]" value="{{where_query}}">
	</div>
</div>

<div class="caldera-config-group">
	<label>Pod field(s) to return (comma-separated, no space)</label>
	<div class="caldera-config-field">
		<input type="text" id="{{_id}}_return_field" class="block-input field-config magic-tag-enabled required" name="{{_name}}[return_field]" value="{{return_field}}">
	</div>
</div>
