<?php
/*
Plugin Name: Nexus Export
Plugin URI: http://thenexus.tv
Description: Generates JSON for the Nexus data.
Version: 0.1
Author: 
Author Email: me@home.com
License:

  
*/

define( 'NE_PATH', dirname( __FILE__ ) );

class Nexus_Export {


	function __construct() {
		add_action( 'init', array( $this, 'init_nexus_core' ) );
	}
  
	function init_nexus_core() {
	
		add_action( 'admin_menu', array( $this, 'admin_page' ) );

	}

	function admin_page() {
		add_menu_page('Nexus Export', 'Nexus Export', 'manage_options', 'nexus-export', array( $this, 'render_admin_page' ) );
	}

	function render_admin_page() {
		include_once( NE_PATH . '/admin_page.php' );
	}
 
}

new Nexus_Export();

?>