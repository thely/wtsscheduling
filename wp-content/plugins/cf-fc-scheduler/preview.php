<?php

/**
 * Preview Templates use handlebars.js for structure
 * see http://handlebarsjs.com/ for more on this.
 * Here is a sample of the config data set to this template
 * {
 *    "id": "generated_id",
 *    "label": "user defined lable",
 *    "slug": "user defined slug",
 *    "required": "1", // this wont be here if not set as required
 *    "caption": "user defined description",
 *    "config": { // field specific configs set from the config template
 *        "my_option": "",
 *        "another_option": "",
 *    }
 *}
 * 
 */
?>
<div class="preview-caldera-config-group">
	{{#unless hide_label}}<label class="control-label">{{label}}{{#if required}} <span style="color:#ff0000;">*</span>{{/if}}</label>{{/unless}}
	<div class="preview-caldera-config-field">
		<?php /*<div id="calendar{{id}}"></div> */?>
		<input {{#if hide_label}}placeholder="{{label}}"{{else}}placeholder="{{config/placeholder}}"{{/if}} type="text" class="preview-field-config" value="{{config/api_key}}">
		<span class="help-block">{{caption}}</span>
	</div>
</div>

<?php /*
{{#script}}
jQuery(document).ready(function() {
	jQuery("#calendar{{id}}").fullCalendar({
		header: {
	    	left: "prev,next today",
	    	center: "title",
	    	right: "agendaWeek,month"
	    }
	});
});
{{/script}} */
?>