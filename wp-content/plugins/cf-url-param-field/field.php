<?php
if (isset($field['config']['parameter'])) {
  $param_value = filter_input(INPUT_GET, $field['config']['parameter'], FILTER_SANITIZE_STRING);
  if (!$param_value) {
    $param_value = "";
  }
} else {
  $param_value = "";
}
?>
<?php echo $wrapper_before; ?>
  <?php echo $field_label; ?>
  <?php echo $field_before; ?>
    <input type="text" data-field="<?php echo $field_base_id; ?>" class="<?php echo $field_class; ?>" id="<?php echo $field_id; ?>" name="<?php echo $field_name; ?>" value="<?php echo htmlentities( $param_value ); ?>" <?php echo $field_required; ?>>
    <?php echo $field_caption; ?>
  <?php echo $field_after; ?>
<?php echo $wrapper_after; ?>
