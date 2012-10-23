<?php

if ( !defined( 'ABSPATH' ) ) exit;

if ( file_exists( dirname( __FILE__ ) . '/includes/languages/' . get_locale() . '.mo' ) )
	load_plugin_textdomain( 'bp-msgat', dirname( __FILE__ ) . '/includes/languages/' . get_locale() . '.mo' );


class BP_Msgat_Component extends BP_Component {

	
	function __construct() {
		global $bp;

		parent::start(
			'msgat',
			__( 'Msgat', 'bp-msgat' ),
			BP_MSGAT_PLUGIN_DIR
		);

		
		 $this->includes();

		
		$bp->active_components[$this->id] = '1';

		
		add_action( 'init', array( &$this, 'register_post_types' ) );
	}

	function includes() {

		// Files to include
		$includes = array(
			'includes/actions.php',
			'includes/filters.php',
			'includes/classes.php'
		);

		parent::includes( $includes );

		// As an example of how you might do it manually, let's include the functions used
		// on the WordPress Dashboard conditionally:
		if ( is_admin() || is_network_admin() ) {
			include( BP_MSGAT_PLUGIN_DIR . '/includes/admin.php' );
		}
	}

	
	function setup_globals() {
		global $bp;

		// Defining the slug in this way makes it possible for site admins to override it
		if ( !defined( 'BP_MSGAT_SLUG' ) )
			define( 'BP_MSGAT_SLUG', $this->id );

		// Global tables for the example component. Build your table names using
		// $bp->table_prefix (instead of hardcoding 'wp_') to ensure that your component
		// works with $wpdb, multisite, and custom table prefixes.
		$global_tables = array(
			'table_name'      => $bp->table_prefix . 'bp_msgat'
		);

		// Set up the $globals array to be passed along to parent::setup_globals()
		$globals = array(
			'slug'                  => BP_MSGAT_SLUG,
			'root_slug'             => isset( $bp->pages->{$this->id}->slug ) ? $bp->pages->{$this->id}->slug : BP_MSGAT_SLUG,
			'has_directory'         => false, // Set to false if not required
			//'notification_callback' => 'bp_example_format_notifications',
			'search_string'         => __( 'Search Attachements...', 'bp-msgat' ),
			'global_tables'         => $global_tables
		);

		// Let BP_Component::setup_globals() do its work.
		parent::setup_globals( $globals );

	}

	function register_post_types() {
		// Set up some labels for the post type
		$labels = array(
			'name'	   => __( 'Message Attachements', 'bp-msgat' ),
			'singular' => __( 'Message Attachement', 'bp-msgat' )
		);

		// Set up the argument array for register_post_type()
		$args = array(
			'label'	   => __( 'Message Attachement', 'bp-msgat' ),
			'labels'   => $labels,
			'public'   => false,
			'show_ui'  => true,
			'supports' => array( 'title','editor','author','custom-fields' )
		);

		
		register_post_type( 'messageattachements', $args );

		parent::register_post_types();
	}

	function register_taxonomies() {

	}

}

function bp_msgat_load_core_component() {
	global $bp;

	$bp->msgat = new BP_Msgat_Component;
}
add_action( 'bp_loaded', 'bp_msgat_load_core_component' );


?>