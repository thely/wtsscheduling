<?php
	require_once(realpath('../../../wp-load.php'));
	//{filter_by:"wts_subject.id", filter_id: this.value, whichpod: "wts_course"};
	header("Content-Type: text/json");
	$filter_by = $_GET['filter_by'];
	$filter_id = strtolower($_GET['filter_id']);
	$whichpod = $_GET['whichpod'];
	$extra_fields = explode(",", $_GET['extra_fields']);

	$params = array(
		"where" => "$filter_by = '$filter_id'"
	);

	$mypod = pods($whichpod, $params);
	$filtered_pods = array();
	$id_field = ""; $name_field = "";
	
	if ($whichpod == "wts_course") {
		$id_field = "post_title";
		$name_field = "post_title";
	}
	else if ($whichpod == "user") {
		$id_field = "id";
		$name_field = "display_name";
	}


	while ($mypod->fetch()) {
		$nextpod = array(
			"id" => $mypod->field($id_field), 
			"name" => $mypod->field($name_field)
		);
		if ($extra_fields != null) {
			foreach ($extra_fields as $efield) {
				$field_value = $mypod->field($efield);

				//field exists, and is set
				if ($field_value != null && $field_value != false){
					$nextpod[$efield] = $field_value;
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