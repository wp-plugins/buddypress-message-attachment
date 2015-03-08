<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if( ! class_exists( 'BP_Msgat_Plugin' ) ):

class BP_Msgat_Plugin {
	/**
	 * Main includes
	 * @var array
	 */
	private $main_includes = array(
		'actions',
		'template',
	);

	/**
	 * Admin includes
	 * @var array
	 */
	private $admin_includes = array(
		'admin',
	);
	
	/**
	 * Default options for the plugin.
	 * After the user saves options the first time they are loaded from the DB.
	 *
	 * @var array
	 */
	private $default_options = array(
		'file-types'	=> array( 'png', 'jpg', 'jpeg', 'pdf', 'zip', 'rar', ),//allowed files types in attachment
		'max-size'		=> 5,//maximum attachment size in MB (per individual file),
		'load-css'		=> true,
	);
	
	/**
	 * This options array is setup during class instantiation, holds
	 * default and saved options for the plugin.
	 *
	 * @var array
	 */
	public $options = array();
	
	public $network_activated = false;
	
	private $all_file_types = false,
			$upload_dir = false;
			
	public static function instance(){
		// Store the instance locally to avoid private static replication
		static $instance = null;

		// Only run these methods if they haven't been run previously
		if ( null === $instance ){
			$instance = new BP_Msgat_Plugin;
			$instance->setup_globals();
			$instance->setup_actions();
			$instance->setup_textdomain();
		}

		// Always return the instance
		return $instance;
	}
	
	private function setup_globals(){
		$this->network_activated = $this->is_network_activated();
		
		// DEFAULT CONFIGURATION OPTIONS
		$default_options = $this->default_options;
		
		$saved_options = $this->network_activated ?  get_site_option( 'bp_msgat_plugin_options' ) : get_option( 'bp_msgat_plugin_options' );
		$saved_options = maybe_unserialize( $saved_options );

		$this->options = wp_parse_args( $saved_options, $default_options );
	}
	
	/**
	 * Check if the plugin is activated network wide(in multisite)
	 * 
	 * @return boolean
	 */
	private function is_network_activated(){
		$network_activated = false;
		if ( is_multisite() ) {
			if ( ! function_exists( 'is_plugin_active_for_network' ) )
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

			if( is_plugin_active_for_network( 'buddypress-message-attachment/loader.php' ) ){
				$network_activated = true;
			}
		}
		return $network_activated;
	}
	
	private function setup_actions(){
		// Admin
		if ( ( is_admin() || is_network_admin() ) && current_user_can( 'manage_options' ) ){
			$this->load_admin();
		}
		
		// Hook into BuddyPress init
		add_action( 'bp_init',		array( $this, 'bp_init' ) );
		
		add_action( 'bp_actions',	array( $this, 'file_downloader' ) );
	}
	
	public function setup_textdomain(){
		$domain = 'bp-msgat';
		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		//first try to load from wp-content/languages/plugins/ directory
		load_textdomain( $domain, WP_LANG_DIR.'/plugins/'.$domain.'-'.$locale.'.mo' );

		//if not found, then load from buddypress-message-attachment/languages directory
		load_plugin_textdomain( $domain, false, 'buddypress-message-attachment/languages' );
	}
	
	public function bp_init(){
		//Dont load if activity component is not enabled
		if( !bp_is_active( 'messages' ) ){
			//show notice to admin
			add_action( 'admin_notices',			array( $this, 'admin_notice_messages_dependency' ) );
			add_action( 'network_admin_notices',	array( $this, 'admin_notice_messages_dependency' ) );
			return;
		} else {
			$this->load_main();
		}
	}
	
	private function load_admin(){
		$this->do_includes( $this->admin_includes );
		$this->admin = BP_Msgat_Admin::instance();
	}
	
	private function load_main(){
		$this->do_includes( $this->main_includes );
		$this->actions = BP_Msgat_Action::instance();
	}
	
	public function do_includes( $includes = array() ){
		foreach( (array)$includes as $include ){
			require_once( BPMSGAT_PLUGIN_DIR . 'includes/' . $include . '.php' );
		}
	}
	
	public function option( $key ){
		$key    = strtolower( $key );
		$option = isset( $this->options[$key] )
		        ? $this->options[$key]
		        : null;

		//@todo: add a filter for option before returning.
		return $option;
	}
	
	public function all_file_types(){
		if( !$this->all_file_types ){
			$this->all_file_types = apply_filters( 'bp_msgat_file_types', array(
				'images'	=> array(
					'label'			=> __( 'Images', 'bp-msgat' ),
					'extensions'	=> array( 'bmp', 'png', 'jpg', 'jpeg', 'gif', ),
				),
				'docs'		=> array(
					'label'			=> __( 'Documents', 'bp-msgat' ),
					'extensions'	=> array( 'txt', 'odt', 'doc', 'docx', 'pdf', 'xls', 'xlsx', 'ods', 'ppt', 'pptx', ),
				),
				'archives'	=> array(
					'label'			=> __( 'Archives', 'bp-msgat' ),
					'extensions'	=> array( 'zip', 'rar', 'gz', '7z', ),
				),
				'audio'		=> array(
					'label'			=> __( 'Audio', 'bp-msgat' ),
					'extensions'	=> array( 'wav', 'wma', 'm4a', 'amr', 'mp2', 'mp3', ),
				),
				'video'		=> array(
					'label'			=> __( 'Video', 'bp-msgat' ),
					'extensions'	=> array( 'mp4', ),
				),
			) );
		}
		
		return $this->all_file_types;
	}
	
	public function get_file_type_group( $extension ){
		$file_group = 'general';
		
		$all_file_types = $this->all_file_types();
		foreach( $all_file_types as $group_name => $group ){
			if( in_array( $extension, $group['extensions'] ) ){
				$file_group = $group_name;
				break;
			}
		}
		
		return $file_group;
	}
	
	public function get_upload_dir() {
		if( !$this->upload_dir ){
			$upload_datas = wp_upload_dir();

			$dir = $upload_datas["basedir"] . '/message-attachments';
			$url = $upload_datas["baseurl"] . '/message-attachments';

			// You can filter if you know what you're doing!
			$this->upload_dir = apply_filters( 'bp_msgat_upload_dir', array( 'dir' => $dir, 'url' => $url ) );
		}
		return $this->upload_dir;
	}
	
	public function admin_notice_messages_dependency(){
		if ( current_user_can( 'manage_options' ) ){
			$network_activated = false;
			if ( is_multisite() ) {
				if ( ! function_exists( 'is_plugin_active_for_network' ) )
					require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

				if( is_plugin_active_for_network( 'buddypress/bp-loader.php' ) ){
					$network_activated = true;
				}
			}
			
			if( $network_activated  ){
				$bp_settings_link = "<a href='" .	network_admin_url( 'admin.php?page=bp-components' ) . "'>" . __( 'BuddyPress Settings', 'bp-msgat' ) . "</a>";
			} else {
				$bp_settings_link = "<a href='" .			admin_url( 'admin.php?page=bp-components' ) . "'>" . __( 'BuddyPress Settings', 'bp-msgat' ) . "</a>";
			}
			$notice = sprintf( __( "Hey! BuddyPress Message Attachment requires messages component enabled. Please enable it by going under %s.", 'bp-msgat' ), $bp_settings_link );

			echo "<div class='error'><p>{$notice}</p></div>";
		}
	}
	
	public function file_downloader(){
		if ( bp_displayed_user_id() && bp_is_current_component( 'messages' ) && 'attachment' == bp_current_action() ) {
			$attachment_id = bp_action_variable( 0 );
			$thread_id = bp_action_variable( 1 );
			
			
			$c_thread_template = new BP_Messages_Thread_Template( $thread_id, 'ASC', array(
				'update_meta_cache' => false
			) );
			
			if( !$c_thread_template )
				return;
			
			/* check if user is one of the participants in thread */
			$is_participant = false;
			foreach( $c_thread_template->thread->recipients as $recepient ){
				if( $recepient->user_id == bp_loggedin_user_id() ){
					$is_participant = true;
					break;
				}
			}
			if( !$is_participant )
				return;
			
			$attachment = get_post( $attachment_id );
			if( 'attachment' != $attachment->post_type )
				return;
			
			$file_file = basename( $attachment->guid );
			
			$msgat_upload_dir = $this->get_upload_dir();
			$file_path = $msgat_upload_dir['dir'] .'/'. $file_file;
			
			$file_mime_type = $attachment->post_mime_type;
			
			// we have a file! let's force download.
			if ( file_exists( $file_path ) ){
				status_header( 200 );
				header( 'Cache-Control: cache, must-revalidate' );
				header( 'Pragma: public' );
				header( 'Content-Description: File Transfer' );
				header( 'Content-Length: ' . filesize( $file_path ) );
				header( 'Content-Disposition: attachment; filename='.$file_file );
				header( 'Content-Type: ' .$file_mime_type );
				readfile( $file_path );
				die();
			}
		}
	}
	
}// End class BP_Msgat_Plugin

endif;