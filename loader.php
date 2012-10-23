<?php
/*
Plugin Name: BuddyPress Message Attachment
Plugin URI: http://webdeveloperswall.com/buddypress/buddypress-message-attachment
Description: This Buddypress plugin enables users to send attachements in private messages
Version: 1.1
Revision Date: 08 21, 2012
Requires at least: WP 3.2.1, BuddyPress 1.5
Tested up to: WP 3.2.1, BuddyPress 1.6
License: GNU General Public License 2.0 (GPL) http://www.gnu.org/licenses/gpl.html
Author: E-Media Identity
Author URI: http://emediaidentity.com/
Network: true
*/
// Define a constant that can be checked to see if the component is installed or not.
define( 'BP_MSGAT_IS_INSTALLED', 1 );

// Define a constant that will hold the current version number of the component
// This can be useful if you need to run update scripts or do compatibility checks in the future
define( 'BP_MSGAT_VERSION', '1.1' );

// Define a constant that we can use to construct file paths throughout the component
define( 'BP_MSGAT_PLUGIN_DIR', dirname( __FILE__ ) );


define ( 'BP_MSGAT_DB_VERSION', '1' );

/* Only load the component if BuddyPress is loaded and initialized. */
function bp_msgat_init() {
	// Because our loader file uses BP_Component, it requires BP 1.5 or greater.
	if ( version_compare( BP_VERSION, '1.3', '>' ) )
		require( dirname( __FILE__ ) . '/includes/bp-msgat-loader.php' );
}
add_action( 'bp_include', 'bp_msgat_init' );

/* Put setup procedures to be run when the plugin is activated in the following function */
function bp_msgat_activate() {
	
}
register_activation_hook( __FILE__, 'bp_msgat_activate' );

/* On deacativation, clean up anything your component has added. */
function bp_msgat_deactivate() {
	 
}
register_deactivation_hook( __FILE__, 'bp_msgat_deactivate' );
?>
