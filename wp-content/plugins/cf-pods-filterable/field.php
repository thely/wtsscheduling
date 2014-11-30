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
?>

<?php echo $wrapper_before; ?>
	<?php echo $field_label; ?>
	<?php echo $field_before; ?>
		<select <?php echo $field_placeholder; ?> 
			id="<?php echo $field_id; ?>" 
			data-field="<?php echo $field_base_id; ?>" 
			class="<?php echo $field_class; ?>" 
			name="<?php echo $field_name; ?>" <?php echo $field_required; ?> >
		</select>
		<?php echo $field_caption; ?>
	<?php echo $field_after; ?>
<?php echo $wrapper_after; ?>


<script type="text/javascript">
	jQuery(document).ready(function() {
		//console.log("<?php echo $whichpod; ?>");
		var mydir = "<?php echo plugin_dir_url(__FILE__); ?>";
		var new_id = "div.<?php echo $filter_id; ?> select";
		var nextfilter = "<?php echo $filter_by; ?>";
		var nextpod = "<?php echo $whichpod; ?>";
		jQuery(new_id).change(function () {
			var params = {filter_by: nextfilter, filter_id: this.value, whichpod: nextpod};
			jQuery.get(
				mydir + "podscall.php", 
				params, 
				function(data) {
					jQuery.each(data, function(key, val) {
						console.log("Id is " + val['id']);
						jQuery("#<?php echo $field_id; ?>").append(
							"<option value=\"" + val['id'] + "\">" + val['name'] + "</option>"
						);
					});
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