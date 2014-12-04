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

		$user_id = get_current_user_id();
		foreach ($entry as $key => $value) {
			if ($key == "user_pass") {
				if ($value != "" && $value != null) {
					$new_id = pods( $config['pod'] )->save( $key, $value, $user_id); 	
				}
				else if ($value == "" || $value == null) {
					echo "not saving password??";
					continue;
				}
			}
			else {
				$new_id = pods( $config['pod'] )->save( $key, $value, $user_id); 
			}
		}

		pods_cf_set_names($user_id, $config);
		return array( 'pod_id' => $new_id );
	}
	else {
		$new_id = pods( $config['pod'] )->add( $entry );
		pods_cf_set_names($new_id, $config);
   		return array( 'pod_id' => $new_id );
	}
}

function pods_cf_set_names($user_id, $config) {
	$params = array(
		"ID" => $user_id,
		"first_name" => Caldera_Forms::do_magic_tags($config['first_name']),
		"last_name" => Caldera_Forms::do_magic_tags($config['last_name'])
	);
	//var_dump($params);
	wp_update_user($params);
}
/**
 * PrePopulate options to bound fields
 * Specifically, this loads the values of Pod-based fields in the non-admin view of the form.
 */

function pods_cf_preload_all($form) {
	//echo "<pre>";
	//var_dump($form['fields']);
	//echo "</pre>";

	$processors = Caldera_Forms::get_processor_by_type('pods', $form);
	if(empty($processors)){
		return $form;
	}
	foreach ($processors as $processor) {
		if (empty($processor['config']['editable'])){
			return $form;
		}
		$pods_loadable_fields = array(
			"departments_covered", 
			"user_email"
		);
		$user_loadable_fields = array(
			"first_name",
			"last_name"
		);
		$loaded_user = array();
		$params = array("where" => "id = " . get_current_user_id());
		$loaded_user = pods('user', $params);
		$loaded_user->fetch();

		foreach ($form['fields'] as $key => $field) {
			if (in_array($field['slug'], $pods_loadable_fields)) {
				$pods_field = $loaded_user->field($field['slug']);	
				if ($field['type'] == "text" || $field['type'] == "email") {
					$textfield = $loaded_user->field($field['slug']);
					$form['fields'][$key]['config']['default'] = $textfield;
				}
			//NOTE: cannot be used, as the core will overwrite these values
			//when attempting to auto-populate. Use preload_options instead (below)
				/*else if ($field['type'] == "checkbox") {
					$field = Caldera_Forms::auto_populate_options_field($field, $form) ;
					$chboxes = $loaded_user->field($field['slug']);
					$ch_keys = array();

					foreach ($chboxes as $index => $value) {
						array_push($ch_keys, $value['post_title']);
					}
					//echo_debug($ch_keys);
					foreach ($field['config']['option'] as $index => $option) {
						//echo_debug($option);
						
						if (in_array($option['value'], $ch_keys)) {
							$field['config']['option'][$index]['checked'] = "checked";
							$form['fields'][$key] = $field;
						}
					}
					echo_debug($form['fields'][$key]);
				}*/
			}
			else if (in_array($field['slug'], $user_loadable_fields)) {
				$meta_field = get_user_meta(get_current_user_id(), $field['slug'], true);
				$form['fields'][$key]['config']['default'] = $meta_field;
				//echo_debug($meta_field);
			}
		}
	}
	return $form;
}

function echo_debug($obj) {
	echo "<pre>";
	echo var_dump($obj);
	echo "</pre>";
}

function pods_cf_preload_options($field) {
	global $form;
	
	$processors = Caldera_Forms::get_processor_by_type('pods', $form);
	if(empty($processors)){
		return $field;
	}

	foreach($processors as $processor){
		$loaded_user = array();
		if (empty($processor['config']['editable'])){
			return $field;
		}
		else {
			$params = array("where" => "id = " . get_current_user_id());
			$loaded_user = pods('user', $params);
			$loaded_user->fetch();
		
			if ($field['type'] == "checkbox") {
				$chboxes = $loaded_user->field($field['slug']);
				$ch_keys = array();

				foreach ($chboxes as $index => $value) {
					array_push($ch_keys, $value['ID']);
				}
				foreach ($field['config']['option'] as $index => $option) {
					//var_dump($option);
					if (in_array($option['value'], $ch_keys)) {
						$field['config']['option'][$index]['checked'] = "checked";
					}
				}
			}
			else if ($field['type'] == "dropdown") {
				$where = $loaded_user->field($field['slug']);
				foreach($field['config']['option'] as $index => $option) {
					if ($where == $option['value']) {
						$field['config']['option'][$index]['selected'] = "selected";
					}
				}
			}
		return $field;
		}
	}
}

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
						<select class="block-input caldera-field-bind" 
							id="<?php echo $_POST['id']; ?>_object_<?php echo $name; ?>" <?php echo $sel; ?> 
							name="<?php echo $_POST['name']; ?>[object_fields][<?php echo $name; ?>]">
						</select>
					</div>
				</div>
				<?php
			}
		}
	exit;
}