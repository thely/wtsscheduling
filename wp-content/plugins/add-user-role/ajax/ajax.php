<?php 
if(!class_exists('aur_ajax_call_class')) 
{
    class aur_ajax_call_class
    {
		public function __construct()
        {
            add_action('init', array(&$this, 'init'));
		}
		public function init()
        {
			
			
			add_action( 'admin_enqueue_scripts', 'enqueue_ajax_booklyapp' );
            function enqueue_ajax_booklyapp($hook) {
		
                wp_enqueue_script('ajax-script-aur', plugins_url( '/ajax.js?v='.rand(), __FILE__ ), array('jquery'));	
                wp_localize_script('ajax-script-aur', 'ajax_object',
                        array(
                            'ajax_url' => admin_url('admin-ajax.php')
                            )
                        );
            }
			// add category data
			add_action('wp_ajax_add_aur_role', 'add_aur_role_callback');
       		function add_aur_role_callback() {
				global $wpdb;
	     		$val=trim($_REQUEST['name']);
				$cap=trim($_REQUEST['caps']);
				$capbiliti = get_role($cap);
				$role_capiti=$capbiliti->capabilities;	
				$val1=str_replace(" ","_", $val);
				
				add_role($val1, $val, $role_capiti);
				echo $val.' | ';
			?>	
				 <select name="caps" id="caps" class="text ui-widget-content ui-corner-all drop_down_ud" style="width:95%;"  >
      <?php foreach (get_editable_roles() as $role_name => $role_info) {
    if( $role_name!= 'administrator') { ;?> 
      <option value="<?php echo $role_name; ?>"><?php echo  $role_info['name']; ?></option>
        <?php } } ?>
        </select>
        <?php
			die;
			}
			
			add_action('wp_ajax_del_aur_role', 'del_aur_role_callback');
       		function del_aur_role_callback() {				
	     		global $wpdb,$user;
	           $del_role=trim($_REQUEST['del_role']);
 	           remove_role($del_role);	
			   ?>	
	  <select name="caps" id="caps" class="text ui-widget-content ui-corner-all drop_down_ud" style="width:95%;"  >
      <?php foreach (get_editable_roles() as $role_name => $role_info) {
    if( $role_name!= 'administrator') { ;?> 
      <option value="<?php echo $role_name; ?>"><?php echo  $role_info['name']; ?></option>
        <?php } } ?>
        </select>
        <?php		
			 			
			die;
			}
			
			add_action('wp_ajax_edit_aur_role', 'edit_aur_role_callback');
       		function edit_aur_role_callback() {			
	         global $wpdb;
			 $new_role_name=$_REQUEST['qval'];
			 $old_role_name=$_REQUEST['old_role'];
			 $new_role_name;
			 $old_role_name;
		 
			 $wp_roles =get_option('wp_user_roles'); 
			 $wp_roles[$old_role_name]['name']=$new_role_name;  
			 update_option('wp_user_roles',$wp_roles);
			 		
			die;
			}
					
		}
		
	}
}
?>