<?php
/**
 * Create Pod entry from submission
 *
 * @param array $config Settings for the processor
 * @param array $form Full form structure
 */
function pods_cf_capture_entry($config, $form){
	global $transdata;

	// build entry
	$entry = array();

	// add object fields
	if(!empty($config['object_fields'])){
		foreach($config['object_fields'] as $object_field=>$binding){
			if(!empty($binding)){
				$entry[$object_field] = Caldera_Forms::get_field_data($binding, $form);
			}
		}
	}

	// add pod fields
	if(!empty($config['fields'])){
		foreach($config['fields'] as $pod_field=>$binding){
			if(!empty($binding)){
				$entry[$pod_field] = Caldera_Forms::get_field_data($binding, $form);
			}
		}
	}

	if (!empty($config['editable'])) {
		if ($config['pod'] == 'user') {
			$user_id = get_current_user_id();
			$params = array('where' => "id = '$user_id'");
			$mypod = pods('user', $params);
			while ($mypod->fetch()) {
				$entry['calendar_id'] = $mypod->field('calendar_id');
			}
		}


		$new_id = pods( $config['pod'] )->save( $entry, null, get_current_user_id()); 
		return array( 'pod_id' => $new_id );
	}
	else {
		$new_id = pods( $config['pod'] )->add( $entry );
   		return array( 'pod_id' => $new_id );
	}
}

/**
 * PrePopulate options to bound fields
 * Specifically, this loads the values of Pod-based fields in the non-admin view of the form.
 */
function pods_cf_populate_options($field){
	global $form;
	$processors = Caldera_Forms::get_processor_by_type('pods', $form);
	if(empty($processors)){
		return $field;
	}

	foreach($processors as $processor){

		// is configured
		$fields = array();
		if(!empty($processor['config']['fields'])){
			$fields = array_merge($fields, $processor['config']['fields']);
		}
		if(!empty($processor['config']['object_fields'])){
			$fields = array_merge($fields, $processor['config']['object_fields']);
		}
		if( $bound_field = array_search( $field['ID'], $fields ) ){
			// now lets see if this is a pick field
			$pod = pods($processor['config']['pod'], null, false );
			$pod_field = $pod->fields( $bound_field );
			if(!empty($pod_field['options']['required'])){
				$field['required'] = 1;
			}
			if( $pod_field[ 'type' ] === 'pick' ){
				
				$options = PodsForm::options( $pod_field[ 'type' ], $pod_field );

				include_once PODS_DIR . 'classes/fields/pick.php';
				$fieldtype = new PodsField_Pick();
				$choices = $fieldtype->data( $bound_field, null, $options, $pod );
				$field['config']['option'] = array();
				foreach($choices as $choice_value=>$choice_label){
					$field['config']['option'][] = array(
						'value'	=>	$choice_value,
						'label'	=>  $choice_label
					);
				}
			}
		}
	}

	return $field;
}
/**
 * Load Pod Fields config
 */
function pods_cf_load_fields(){

		$_POST = stripslashes_deep( $_POST );
		if(!empty($_POST['fields'])){
			$defaults = json_decode( $_POST['fields'] , true);			
		}

		$selected_pod = $_POST['_value'];
		$pods_api     = pods_api();
		$pod_fields   = array();
		if ( ! empty( $selected_pod ) ) {			
			$pod_object = $pods_api->load_pod( array( 'name' => $selected_pod ) );
			if ( ! empty( $pod_object ) && !empty( $pod_object['fields'] ) ) {
				echo '<h4>' . __('Pod Fields', 'pods-caldera-forms') . '</h4>';
				foreach ( $pod_object['fields'] as $name => $field ) {
					$sel = "";
					if(!empty($defaults[$selected_pod][$name])){
						$sel = 'data-default="'.$defaults[$selected_pod][$name].'"';
					}
					$locktype = '';
					$caption = '';
					if($field['type'] === 'pick'){
						$locktype = 'data-type="'.$field['options'][ 'pick_format_' . $field['options']['pick_format_type'] ].'"';
						$caption = '<p>'.__('Options will be auto auto-populated', 'pods-caldera-forms').'</p>';
					}
				?>
				<div class="caldera-config-group">
					<label for="<?php echo $_POST['id']; ?>_fields_<?php echo $name; ?>"><?php echo $field['label']; ?></label>
					<div class="caldera-config-field">
						<select class="block-input caldera-field-bind <?php echo ( empty( $field['options']['required'] ) ? '' : 'required' ); ?>" <?php echo $sel; ?> <?php echo $locktype; ?> id="<?php echo $_POST['id']; ?>_fields_<?php echo $name; ?>" name="<?php echo $_POST['name']; ?>[fields][<?php echo $name; ?>]"></select>
						<?php echo $caption ?>
					</div>
				</div>
				<?php
				}
			}
		}


		$wp_object_fields = array();
		if ( ! empty( $pod_object ) && !empty( $pod_object['object_fields'] ) ) {
			echo '<h4>' . __('WP Object Fields', 'pods-caldera-forms') . '</h4>';
			foreach ( $pod_object['object_fields'] as $name => $field ) {
					$sel = "";
					if(!empty($defaults[$selected_pod][$name])){
						$sel = 'data-default="'.$defaults[$selected_pod][$name].'"';
					}

				?>
				<div class="caldera-config-group">
					<label for="<?php echo $_POST['id']; ?>_object_<?php echo $name; ?>"><?php echo $field['label']; ?></label>
					<div class="caldera-config-field">
						<select class="block-input caldera-field-bind" id="<?php echo $_POST['id']; ?>_object_<?php echo $name; ?>" <?php echo $sel; ?> name="<?php echo $_POST['name']; ?>[object_fields][<?php echo $name; ?>]"></select>
					</div>
				</div>
				<?php
			}
		}

	exit;
}