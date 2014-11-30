<?php echo $wrapper_before; ?>
	<?php echo $field_label; ?>
	<?php echo $field_before; ?>
		<select <?php echo $field_placeholder; ?> 
			id="<?php echo $field_id; ?>_selecter" 
			data-field="<?php echo $field_base_id; ?>" 
			class="<?php echo $field_class; ?>" 
			name="<?php echo $field_name; ?>" <?php echo $field_required; ?> >
			<?php
				$params = array(
					//"limit" => 5
					"orderby" => 't.name'
				);
				$mypod = pods('wts_subject', $params);
				while ($mypod->fetch()) { 
					$pod_id = $mypod->field('id');
					$pod_name = $mypod->field('name'); ?>
					<option value="<?php echo $pod_id; ?>" <?php if ($field_value == $pod_id) { ?>selected="selected"<?php } ?> ><?php echo $pod_name; ?></option>
				<?php }
			?>
		</select>
		<?php /*<select id="<?php echo $field_id?>_result"></select>*/ ?>
		<?php echo $field_caption; ?>
	<?php echo $field_after; ?>
<?php echo $wrapper_after; ?>
<script type="text/javascript">
	jQuery(document).ready(function() {
		jQuery("<?php echo $field_id?>_selecter option:selected").change(function () {
			console.log(this.value);
		});
		//var newthing = jQuery("<?php echo $field_id?>_selecter option:selected").val();
		//console.log(newthing);
	});
</script>