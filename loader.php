<?php
/*
Plugin Name: BuddyPress Message Attachment
Plugin URI: http://webdeveloperswall.com/buddypress/buddypress-message-attachment
Description: Extend BuddyPress' private message feature by enabling attachments. This plugin enables users to send attachments in private messages.
Version: 2.1.1
Author: ckchaudhary
Author URI: http://webdeveloperswall.com/author/ckchaudhary
Text Domain: bp-msgat
Domain Path: /languages
*/

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/* ++++++++++++++++++++++++++++++
 * CONSTANTS
 +++++++++++++++++++++++++++++ */
// Directory
if ( ! defined( 'BPMSGAT_PLUGIN_DIR' ) ) {
	define( 'BPMSGAT_PLUGIN_DIR', trailingslashit( plugin_dir_path( __FILE__ ) ) );
}

// Url
if ( ! defined( 'BPMSGAT_PLUGIN_URL' ) ) {
	$plugin_url = trailingslashit( plugin_dir_url( __FILE__ ) );

	// If we're using https, update the protocol.
	if ( is_ssl() )
		$plugin_url = str_replace( 'http://', 'https://', $plugin_url );

	define( 'BPMSGAT_PLUGIN_URL', $plugin_url );
}

/* ______________________________ */

function bp_msgat_init() {
	global $bp_msgat;
	require( BPMSGAT_PLUGIN_DIR . 'includes/main-class.php' );
    $bp_msgat = BP_Msgat_Plugin::instance();
}
add_action( 'plugins_loaded', 'bp_msgat_init' );

/**
 * Returns plugins instance.
 * Must be called after plugins_loaded.
 * 
 * @since 2.0
 * @global Main plugin object $bp_msgat
 * @return Main plugin object
 */
function bp_message_attachment(){
	global $bp_msgat;
	return $bp_msgat;
}