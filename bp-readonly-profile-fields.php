<?php
/**
 * Plugin Name: BP Readonly Profile Fields
 * Description: This allows profile fields to be readonly, it can be only edited by the admin
 * Author: BuddyDev.com
 * Version: 1.0.1
 * Plugin URI: http://buddydev.com/support/forums/topic/dont-want-to-allow-members-to-edit-even-once-in-non-editable-profile-fields/
 */

class BP_Readonly_Profile_Visibility {
	
	private static $instance = null;
	
	private function __construct() {
		
		add_filter( 'bp_xprofile_get_visibility_levels', array( $this, 'add_visibility_level' ) );
		add_filter( 'bp_xprofile_get_hidden_field_types_for_user', array( $this, 'hidden_levels' ) );
		
		add_filter( 'bp_before_has_profile_parse_args', array( $this, 'exclude_fields_on_register' ) );
	}
	
	
	public static function get_instance() {
		
		if( is_null( self::$instance ) ) {
			self::$instance = new self();
		}
		
		return self::$instance;
	}
	

	//this code will have side effects if the registration page is different than Bp or you are using Ajax registration plugin
	public function add_visibility_level( $levels ) {
		//sorry this is a hack, BuddyPress does not look for the field level VISIBILITY drop down from another list
		//and we are not going to show this level to normal user to check
		if( bp_is_register_page() ||  ( bp_is_user_profile_edit() && !  is_super_admin() ) ) {
				return $levels;
		}
		
		$levels['admin-editable'] = array(
			'id'    => 'admin-editable',
			'label' => __( 'Non Editable by User(visible to all)' )
		);
	
		return $levels;
	}
	//hide these fields on the edit profile page
	public function hidden_levels( $hidden_levels ) {
		
		//bp does not care for visibility on registration page, so we will have to use other strategy for that
		
		
		if(  bp_is_user_profile_edit() && ! is_super_admin()  ) {
			//hide hidden fields
			$hidden_levels[] = 'admin-editable';
		}
		
		return $hidden_levels;
	}
	
	//hide on edit/
	public function exclude_fields_on_register( $args ) {
		
		if( !  bp_is_register_page() && ! bp_is_user_profile_edit() ) {
			return $args;
		}
		
		if( is_super_admin() ) {
			return $args;
		}
		$exclude_fields = isset( $args['exclude_fields'] ) ? $args['exclude_fields']: array() ;
		
		if( is_string( $exclude_fields ) ) {
			$exclude_fields = explode( ',', $exclude_fields );//Bp does not mandate array, so could be a list by other plugin, play well with them
		}
		
		$hidden_fields = $this->get_hidden_field();
		$all_exclude = array_merge( $exclude_fields, $hidden_fields );
		
		$args['exclude_fields'] = join(',', $all_exclude );
		
		return $args;
	}
	//utility
	
	public function get_hidden_field() {
		
		global $wpdb;

		$table = buddypress()->profile->table_name_meta;
		//there is a cacth
		//figure it
		//or ask in our forums :D
		$query = $wpdb->prepare( "SELECT object_id FROM {$table} WHERE object_type = %s AND meta_key= %s and meta_value = %s", 'field', 'default_visibility', 'admin-editable'); 

		return $wpdb->get_col( $query );
	}
}

BP_Readonly_Profile_Visibility::get_instance();