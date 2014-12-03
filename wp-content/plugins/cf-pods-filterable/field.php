<?php
/*
 * Pods SQL Cheat Sheet!
 *
 * to filter course by subjects: wts_subject.slug = 'biol'
 * to filter users by subjects: departments_covered.slug = 'biol'
 * to filter users by course: your_courses.slug = 'biol-112'
 *
 */


	$whichpod = $field['config']['pod'];
	$filter_by = $field['config']['filter_by'];
	$filter_id = $field['config']['filter_id'];
	$extra_fields = $field['config']['extra_fields'];
	$hide_output = $field['config']['hide'];
	$multi = $field['config']['multi'];
?>

<?php if (!$hide_output) { ?>
<?php echo $wrapper_before; ?>
	<?php echo $field_label; ?>
	<?php echo $field_before; ?>
		<?php if ($multi) {

		} else { ?>
		<select 
			<?php echo $field_placeholder; ?> 
			id="<?php echo $field_id; ?>" 
			data-field="<?php echo $field_base_id; ?>" 
			class="<?php echo $field_class; ?>" 
			name="<?php echo $field_name; ?>" <?php echo $field_required; ?> >
		</select>
		<?php } ?>
		<?php echo $field_caption; ?>
	<?php echo $field_after; ?>
<?php echo $wrapper_after; ?>

<?php } else { ?>
<input type="text" 
	id="<?php echo $field_id; ?>" 
	data-field="<?php echo $field_base_id; ?>" 
	class="<?php echo $field_class; ?> final_end_source" 
	name="<?php echo $field_name; ?>" 
	style="visibility: hidden;" >
<?php } ?>

<script type="text/javascript">
	jQuery(document).ready(function() {
		//console.log("<?php echo $whichpod; ?>");
		var mydir = "<?php echo plugin_dir_url(__FILE__); ?>";
		var fieldToWatch = "#<?php echo $filter_id; ?>_1";
		var nextfilter = "<?php echo $filter_by; ?>";
		var nextpod = "<?php echo $whichpod; ?>";
		var extraFields = "<?php echo $extra_fields; ?>";
		var isHidden = "<?php echo $hide_output; ?>";
		var fieldId = "#<?php echo $field_id; ?>";

		jQuery(fieldToWatch).change(function () {
			var params = {filter_by: nextfilter, filter_id: this.value, whichpod: nextpod, extra_fields: extraFields};
			jQuery.get(
				mydir + "podscall.php", 
				params, 
				function(data) {
					if (isHidden) {
						jQuery(fieldId).val(encodeURI(JSON.stringify(data))).change();
					}
					else {
						jQuery.each(data, function(key, val) {
							console.log("Id is " + val['id']);
							jQuery(fieldId).append(
								"<option value=\"" + val['id'] + "\">" + val['name'] + "</option>"
							);
						});
					}
				},
			"json"
			).error(function(jqXHR, textStatus, errorThrown) {
				console.log(textStatus);
			});
		});
		//var newthing = jQuery("<?php echo $field_id?>_selecter option:selected").val();
		//console.log(newthing);
	});
</script>