<?php
/*
Plugin Name:Add User Role
Plugin URI: http://assertionit.com/wordpress/AddUserRoll.php
Description: This plugin will show Roll
Author: Nikhil Vaghela
Version:0.0.1
Author URI: http://assertionit.com/
*/
require_once(sprintf('%s/ajax/ajax.php', dirname(__FILE__)));
            $aur_ajax = new aur_ajax_call_class();

/*---------------------------------------------------------------Admin Menu in User--------------------------------------------------------- */
function wp_aur_menu() {
	add_users_page('My Plugin Users', 'My Role', 'read', 'my-unique-identifier', 'aur_plugin_function');
}
add_action('admin_menu', 'wp_aur_menu');

/*---------------------------------------------------------------Load script--------------------------------------------------------- */

function aur_admin_loadscript()
{
	wp_enqueue_style('userrole_css', plugins_url().'/add-user-role/css/style.css');
	wp_enqueue_style('jquery-ui', plugins_url().'/add-user-role/css/jquery-ui.css');	

	wp_enqueue_script('jquery-ui-dialog');
	wp_enqueue_script('jquery.validatebook', plugins_url().'/add-user-role/js/jquery.validate.js');	 

}
add_action('admin_enqueue_scripts','aur_admin_loadscript');

add_action( 'admin_footer', 'aur_action_javascript' ); // Write our JS below here
function aur_action_javascript() { ?>
<script>
jQuery(function() {
			
		 var dialog, form,		 
			name = jQuery( "#name" ),
			caps = jQuery( "#caps" ),
			allFields = jQuery( [] ).add( name ),
			tips = jQuery( ".validateTips" );			
dialog = jQuery( "#dialog-form" ).dialog({
		autoOpen: false,
		height: 230,
		width: 350,
		modal: true,
		buttons: {			
	 'Add User': function() {	
                submit = true;
                form.submit();
            },
		Cancel: function() {
		dialog.dialog( "close" );
		}
		},
		close: function() {
		form[ 0 ].reset();
		allFields.removeClass( "ui-state-error" );
		}
});

	form = dialog.find( "form" ).on( "submit", function( event ) {
		event.preventDefault();
	
	});

	jQuery( "#create-user" ).button().on( "click", function() {
		dialog.dialog( "open" );		
	});

});

jQuery(document).ready(function(e) {
	jQuery(document).on('click','.delete_role',function() {		
	var del=jQuery(this).attr('id');
 	jQuery( "#dialog-confirm" ).dialog({
	resizable: false,
	height:140,
	modal: true,
	buttons: {
		"Delete all items": function() {
			var data = {
									action: 'del_aur_role',
									 del_role:del						
									};
				jQuery.ajax(ajax_object.ajax_url, {
									type: "POST",
									data: data,
									cache: false,
									success: function (response) {
									jQuery('.drop_down_ud').html(response);									
									jQuery("#"+del).remove();																								
									jQuery( "#dialog-confirm" ).dialog( "close" );										
									},
									error: function (error) {
										if (typeof console === "object") {
											console.log(error);
										}
									},
									complete: function () {
									}
								});
			},
			Cancel: function() {
			jQuery( this ).dialog( "close" );
			}
		}
	});
  });
    
});

jQuery(function() {
	
		 var dialog, form,		 
			name = jQuery( "#name1" ),
			allFields = jQuery( [] ).add( name ),
			tips = jQuery( ".validateTips" );

var old_role = "";
dialog = jQuery( "#dialog-form-edit" ).dialog({
		autoOpen: false,
		height: 190,
		width: 350,
		modal: true,
		buttons: {			
	 'Edit user Role': function() {	
                submit = true;
                form.submit();
            },
		Cancel: function() {
		dialog.dialog( "close" );
		}
		},
		close: function() {
		form[ 0 ].reset();
		allFields.removeClass( "ui-state-error" );
		}
});

	form = dialog.find( "form" ).on( "submit", function( event ) {
		event.preventDefault();		
		
	});
	
	jQuery(document).on('click','.edit_role',function() {	
		dialog.dialog( "open" );
		old_role=jQuery(this).attr('id');	
		old_role_name=jQuery(this).attr('name');
		jQuery("#name1").val(old_role_name);
		jQuery("#oldname").val(old_role);
		jQuery("#oldnew").val(old_role_name);
	});


});
</script>

     <?php
}
function aur_plugin_function(){	
	echo '<div class="addrole">Role : </div>';	




		aur_list_disp_table();
}

function aur_list_disp_table(){	
?>
<div id="dialog-form" title="Create new user">
    <form id="add_user_id">
        <fieldset>
        <label for="name">Role Name </label>
        <input type="text" name="name" id="name" class="text ui-widget-content ui-corner-all">        
        <label for="name"> Inherit Caps: </label>        
        <select name="caps" id="caps" class="text ui-widget-content ui-corner-all drop_down_ud" style="width:95%;"  >
      <?php foreach (get_editable_roles() as $role_name => $role_info) {
    if( $role_name!= 'administrator') { ;?> 
      <option value="<?php echo $role_name; ?>"><?php echo  $role_info['name']; ?></option>
        <?php } } ?>
        </select>        
        <input type="submit"  tabindex="-1" style="position:absolute; top:-1000px">
        </fieldset>
    </form>    
</div>

<div id="users-contain" class="ui-widget">
<h1>Existing Users Role:</h1>
<table id="users" class="ui-widget ui-widget-content">
	<thead>
		<tr class="ui-widget-header ">
			<th width="70%">Role</th><th>Action</th>
		</tr>
	</thead>    
	<tbody>    
     <?php foreach (get_editable_roles() as $role_name => $role_info) {
      if( $role_name!= 'administrator') { ;?>
		<tr id="<?php echo $role_name; ?>">
			<td class="<?php echo $role_name; ?>"><?php echo  $role_info['name']; ?></td>
            <td>
            	<div>
                <a href="#"  class="edit_role" id="<?php echo $role_name; ?>" name="<?php echo  $role_info['name']; ?>">Edit</a>
             	<a href="#" class="delete_role" id="<?php echo $role_name; ?>">Delete</a>
               </div>
            </td>
		</tr>
          <?php } } ;?>
	</tbody>
</table>
</div>

<button id="create-user">Create new user</button>
<div id="dialog-form-edit" title="Create new user">
   <form id="edit_user_id">
        <fieldset>
        <label for="name">Role Name </label>
        <input type="hidden" name="oldname" id="oldname" />
        <input type="hidden" name="oldnew" id="oldnew" />
        <input type="text" name="name" id="name1"  class="text ui-widget-content ui-corner-all">        
        <input type="submit" tabindex="-1" style="position:absolute; top:-1000px">
        </fieldset>
    </form>
</div>
<div id="dialog-confirm" style="display:none" title="Empty the recycle bin?">
<p> Are you sure you want delete?</p>
</div>
 <?php } ;?>