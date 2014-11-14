
jQuery(document).ready(function($) {
	
function myTrim(x) {
    return x.replace(/^\s+|\s+$/gm,'');
}	

jQuery("#add_user_id").validate({	
	
						errorClass:"errormassagevalide",
						rules: {
						name: {
							required: true,							
									},						
					},
					messages: {
						
					},	
				    submitHandler: function(form) {
						 setTimeout(function(){
								var data = {
									action: 'add_aur_role',
									 name: jQuery("#name").val(),
									 caps:jQuery("#caps").val(),
							
									};
						
								jQuery.ajax(ajax_object.ajax_url, {
									type: "POST",
									data: data,
									cache: false,
									success: function (get_response) {
									var  get_arr=get_response.split("|");	
								var  response=myTrim(get_arr[0]);
							
									jQuery( "#users tbody" ).append( "<tr id="+response+" >" +
									"<td class="+response+">" + response + "</td>" +
									"<td>" + '<div><a id="'+ response +'"  name="'+ response +'" class="edit_role" href="#">Edit</a> <a id="'+ response +'"  name="'+ response +'" class="delete_role" href="#">Delete</a></div>'+ "</td>" +
									"</tr>" );										
									jQuery('.drop_down_ud').html(myTrim(get_arr[1]));										
									jQuery( "#dialog-form" ).dialog( "close" );										
									},
									error: function (error) {
										if (typeof console === "object") {
											console.log(error);
										}
									},
									complete: function () {
									}
								});
			
							 }, 100);
					},
				});	


jQuery("#edit_user_id").validate({
						errorClass:"errormassagevalide",
						rules: {
						name: {
							required: true,
						},						
						
					},
					messages: {
						
					},					
					
				    submitHandler: function(form) {
						var new_val =jQuery("#name1").val();
						 setTimeout(function(){
								var data = {
									action: 'edit_aur_role',									
									 qval: jQuery("#name1").val(),
									 old_role:jQuery("#oldname").val(),				
									};						
								jQuery.ajax(ajax_object.ajax_url, {
									type: "POST",
									data: data,
									cache: false,
									success: function (response) {									
									 var my_val=jQuery("#oldname").val();									
									 jQuery('.'+my_val).html(new_val);	
									jQuery('#'+my_val).find('a').attr('name',new_val);									
									jQuery( "#dialog-form-edit" ).dialog( "close" );										
									},
									error: function (error) {
										if (typeof console === "object") {
											console.log(error);
										}
									},
									complete: function () {
									}
								});
			
							 }, 100);
					},
				});	

});