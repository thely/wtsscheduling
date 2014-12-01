<?php
	require_once(realpath('../../../wp-load.php'));
	//{filter_by:"wts_subject.id", filter_id: this.value, whichpod: "wts_course"};
	header("Content-Type: text/json");
	$filter_by = $_GET['filter_by'];
	$filter_id = strtolower($_GET['filter_id']);
	$whichpod = $_GET['whichpod'];

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
		if ($whichpod == "user") {
			$nextpod['calendar_id'] = $mypod->field("calendar_id");
		}
		array_push($filtered_pods, $nextpod);
	}
	print json_encode($filtered_pods);
?>