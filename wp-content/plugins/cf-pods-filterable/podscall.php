<?php
	require_once(realpath('../../../wp-load.php'));
	//{filter_by:"wts_subject.id", filter_id: this.value, whichpod: "wts_course"};
	header("Content-Type: text/json");
	$filter_by = $_POST['filter_by'];
	//$filter_id = strtolower($_GET['filter_id']);
	$filter_id = $_POST['filter_id'];
	$whichpod = $_POST['whichpod'];
	$extra_fields = explode(",", $_POST['extra_fields']);
	$extra_where = str_replace("\\", "", Caldera_Forms::do_magic_tags($_POST['extra_where']));

	$querystring = "";
	if ($extra_where != null) {
		$querystring = "$filter_by = '$filter_id' AND $extra_where";
	}
	else {
		$querystring = "$filter_by = '$filter_id'";
	}

	$params = array(
		"where" => $querystring
	);

	$mypod = pods($whichpod, $params);
	$filtered_pods = array();
	$id_field = ""; $name_field = "";
	
	//echo "THE POD IS: $whichpod";
	if ($whichpod == "wts_course") {
		$id_field = "post_title";
		$name_field = "post_title";
	}
	else if ($whichpod == "user") {
		$id_field = "ID";
		$name_field = "display_name";
	}
	else if ($whichpod == "calendar") {
		$id_field = "calendar_id";
		$name_field = "tutor";
	}

	//cycle through every pod that matches the filter
	while ($mypod->fetch()) {
		$nextpod = array(
			"id" => $mypod->field($id_field), 
			"name" => $mypod->field($name_field)
		);
		//var_dump($nextpod);
		if ($extra_fields != null) {
			//loop by fields requested
			foreach ($extra_fields as $efield) {
				$field_value = $mypod->field($efield);

				//field exists, and is set
				if ($field_value != null && $field_value != false){
					//field is a relationship, a multi-select, or both
					if (is_array($field_value)) {
						$return_arr = array();
						foreach($field_value as $item) {
							//var_dump($field_value);
							//post-type or user
							if (array_key_exists('post_title', $item)) {
								array_push($return_arr, array("id" => $item['post_name'], "name" => $item['post_title']));
							}
							//taxonomy
							else if (array_key_exists('taxonomy', $item)) {
								array_push($return_arr, array("id" => $item['slug'], "name" => $item['name']));	
							}
						}
						$nextpod[$efield] = $return_arr;
					}

					else {
						$nextpod[$efield] = $field_value;
					}
				}
				//field exists on this pod, but is not set
				else if ($field_value == false) {
					$nextpod[$efield] = "";
				}
			}
		}
		array_push($filtered_pods, $nextpod);
	}
	print json_encode($filtered_pods);
?>