<?php
/***
* Description: Plugin Design
* Author: Syed Amir Hussain
***/
?>
<div class="am_main_container">
	<div class="width_37 float-left">
		<div class="wrap">
			<h2>DB Backup</h2>
		</div>
		<p class="am_csv_alerts">
		</p>
		<form name="am_csv" id="am_csv" method="post" class="am_csv" action="handle_req.php" onsubmit="return false;">
			<div class="am_option_container">
				<div class="am_option_container float-left width_57">
					<select name="am_csv_tbl[]" id="am_csv_tbl" size="10" multiple="multiple">
						<?php
							$tables = $this->am_get_tables( $exclude_prefix = true );
							foreach($tables as $tableName) {
								$tableName = str_replace( $wpdb->prefix, '', $tableName ); $val = trim(strtolower($tableName )); $tableName = trim( $tableName );
						?>
								<option value="<?php echo $val ?>">
									<?php echo $tableName ?>
								</option>
						<?php
						}
						?>
					</select>
				</div>
				<div class="am_option_container float-right width_40">
					<div class="border am_mb_13">
						<input type="checkbox" name="csv_comp_bkp" id="csv_comp_bkp" value="comp_bkp">&nbsp;<label for="csv_comp_bkp">Complete Backup</label>
					</div>
					<fieldset class="am_option am_mb_13 width_93">
						<legend>
						<input type="radio" name="am_option" id="am_option_csv" value="make_csv">&nbsp;<label for="am_option_csv">Make CSV for Excel</label>
						</legend>
						<p>
							<input type="checkbox" name="csv_inc_col" id="csv_inc_col" value="include_column">&nbsp;<label for="csv_inc_col">Include Column</label>
						</p>
					</fieldset>
					<fieldset class="am_option width_93">
						<legend>
						<input type="radio" name="am_option" id="am_option_ex" value="export">&nbsp;<label for="am_option_ex">Export</label>
						</legend>
						<p>
							<input type="checkbox" name="ex_struct" id="ex_struct" value="only_structure">&nbsp;<label for="ex_struct">Structure</label>
						</p>
						<p>
							<input type="checkbox" name="ex_data" id="ex_data" value="only_data">&nbsp;<label for="ex_data">Data</label>
						</p>
					</fieldset>
				</div>
			</div>
			<div class="am_option_container">
				<fieldset class="am_saveAs width_66 float-left">
					<legend>
					<input type="checkbox" value="save_as" name="am_saveAs_option" clas="am_saveAs_option" id="am_saveAs_option">&nbsp;<label for="am_saveAs_option">Save As</label>
					</legend>
					<p>
						<div class="width_87px float-left">
							<label for="am_saveAs_fileName">File Name :</label>
						</div>
						<input type="text" name="am_saveAs_fileName" class="am_saveAs_fileName" id="am_saveAs_fileName" disabled/>
					</p>
					<p>
						<label for="am_saveAs_zip">Compression :</label>
						<select id="am_saveAs_zip" name="am_saveAs_zip" disabled>
							<option value="none">None</option>
							<option value="zipped">zipped</option>
						</select>
					</p>
				</fieldset>
				<p class="w-7 mt_87 float-left" align="right">
					<input type="submit" name="make_csv" class="button" id="make_csv" value="Backup">
				</p>
			</div>
		</form>
		<p class="am_seperator"></p>
		<div class="am_donate_form">
			<?php  echo $donate ?>
		</div>
	</div>
	<div class="am_csv_output float-right"></div>
</div>