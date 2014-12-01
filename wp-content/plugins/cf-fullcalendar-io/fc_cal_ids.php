<?php

	require_once(realpath('../../../wp-load.php'));	
	header("Content-Type: text/json");

	$cal_id = "";
	$filter_slug = "";
	if (array_key_exists("filter_slug", $_GET)) {
		$filter_slug = $_GET['filter_slug'];
		$params = array("where" => "your_courses.post_title = '$filter_slug'");
		$mypod = pods('user', $params);
		$all_ids = array();
		while ($mypod->fetch()) {
			$cal_id = $mypod->field('calendar_id');
			if ($cal_id != null) { 
				array_push($all_ids, $cal_id); 
			}
		}
		echo json_encode($all_ids);

	}

	else {
		$cid = get_current_user_id();
		$params = array(
			"where" => "id = $cid"
		);
		$mypod = pods('user', $params); 

		while ($mypod->fetch()){
			$cal_id = $mypod->field('calendar_id');
		}
		echo $cal_id;
	}
?>