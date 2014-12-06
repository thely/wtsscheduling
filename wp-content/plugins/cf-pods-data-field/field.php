<?php
$pod_type = $field['config']['pod'];
$where_query = Caldera_forms::do_magic_tags($field['config']['where_query']);
$return_fields = explode(",", $field['config']['return_field']);

$params = array(
	"where" => $where_query
);

$pod = pods($pod_type, $params);
$filtered_data = array();

if (count($return_field) == 1) {
	while ($pod->fetch()) {
		array_push($filtered_data, $pod->field($return_fields[0]));
	}
} else {
	while ($pod->fetch()) {
		$this_data = array();
		foreach($return_fields as $return_field) {
			$this_data[$return_field] = $pod->field($return_field);
		}
		array_push($filtered_data, $this_data);
	}
}
$data = json_encode($filtered_data);

echo $wrapper_before;
echo $field_label;
echo $field_before;
?>
	<input type="text"
	id="<?php echo $field_id; ?>"
	data-field="<?php echo $field_base_id; ?>"
	class="<?php echo $field_class; ?> final_end_source"
	name="<?php echo $field_name; ?>"
	style=""
	value=<?php echo $data ?>>
<?php
echo $field_caption;
echo $field_after;
echo $wrapper_after;
?>
