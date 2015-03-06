<?php  
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! class_exists( 'BP_Msgat_Action' ) ):
	
class BP_Msgat_Action{
	private $option_name_prefix = 'message_attachments_';
	
	/**
	 * Empty constructor function to ensure a single instance
	 */
	public function __construct(){
		// ... leave empty, see Singleton below
	}
	
	public static function instance(){
		static $instance = null;

		if ( null === $instance ){
			$instance = new BP_Msgat_Action;
			$instance->setup();
		}

		return $instance;
	}
	
	public function option( $key ){
		$value = bp_message_attachment()->option( $key );
		return $value;
	}
	
	public function setup(){
		if( !is_admin() && !is_network_admin() ){
			add_action( 'wp_enqueue_scripts', array( $this, 'add_css_js' ) );
		}
		
		$display_hooks = apply_filters( 'bp_msgat_form_display_hooks', array( 'bp_after_messages_compose_content', 'bp_after_message_reply_box' ) );
		foreach( $display_hooks as $action_name ){
			add_action( $action_name, array( $this, 'show_attachment_form' ) );
		}
		
		add_action( 'wp_ajax_bp_msgat_upload',		array( $this, 'ajax_upload_file' ) );
		add_action( 'messages_message_after_save',	array( $this, 'add_attachments' ) );
		add_action( 'bp_after_message_content',		array( $this, 'show_attachments' ) );
	}
	
	public function add_css_js(){
		if( !bp_is_current_component( 'messages' ) )
			return;
		
		wp_enqueue_script( 'bp-msgat', BPMSGAT_PLUGIN_URL . 'assets/js/script.min.js', array( 'jquery', 'plupload-all' ), '2.1.0', true );
		//wp_enqueue_script( 'bp-msgat', BPMSGAT_PLUGIN_URL . 'assets/js/script.js', array( 'jquery', 'plupload-all' ), '2.1.0', true );
		
		if( $this->option( 'load-css' )=='yes' ){
			wp_enqueue_style( 'bp-msgat', BPMSGAT_PLUGIN_URL . 'assets/css/style.css', array(), '2.0' );
		}
		
		$data = apply_filters( 'bp_msgat_script_data', array(
			'uploader'	=> array(
				'max_file_size'	=> (int) $this->option( 'max-size' ) . 'mb',
				'multiselect'	=> false,//can enable it in future,
				'nonce'			=> wp_create_nonce( 'bp_msgat_upload' ),
				'flash_swf_url'       => includes_url( 'js/plupload/plupload.flash.swf' ),
				'silverlight_xap_url' => includes_url( 'js/plupload/plupload.silverlight.xap' ),
				'filters'             => array( 
					array( 
						'title'         => __( 'Allowed Files', 'bp-msgat' ), 
						'extensions'    => implode( ',', $this->option('file-types') ),
						'max_file_size' => (int) $this->option( 'max-size' ) . 'mb',
					)
				),
			),
			'selectors'	=> array(
				'form_message'	=> '#send_message_form',
				'form_reply'	=> '#send-reply',
			),
			'lang'	=> array(
				'upload_error'	=> array(
					'file_size'	=> sprintf( __( 'Uploaded file must not be more than %s mb', 'bp-msgat' ), $this->option( 'max-size' ) ),
					'file_type'	=> sprintf( __( 'Selected file not allowed to be uploaded. It must be one of the following: %s', 'bp-msgat' ), implode( ', ', $this->option( 'file-types' ) ) ),
					'generic'	=> __( 'Error! File could not be uploaded.', 'bp-msgat' ),
				),
				'remove'	=> __( 'Remove', 'bp-msgat' ),
				'uploading'	=> __( 'Uploading...', 'bp-msgat' ),
			),
			'current_action'	=> bp_current_action(),
		) );
		wp_localize_script( 'bp-msgat', 'BPMsgAt_Util', $data );
	}
	
	public function temporarly_filters_wp_upload_dir( $upload_data ) {
		$upload_dir = bp_message_attachment()->get_upload_dir();
		
		$args = array( 
			'path'    => $upload_dir['dir'],
			'url'     => $upload_dir['url'],
			'subdir'  => false,
			'basedir' => $upload_dir['dir'],
			'baseurl' => $upload_dir['url'],
		);
		
		$r = wp_parse_args( $args, $upload_data );

		return $r;
	}
	
	public function show_attachment_form(){
		if ( ! _device_can_upload() ) {
			//echo '<p>' . __( 'The web browser on your device cannot be used to upload files. You may be able to use the <a href="http://wordpress.org/extend/mobile/">native app for your device</a> instead.', 'bp-msgat' ) . '</p>';
			return;//we can't do anything
		}
		
		echo "<input type='hidden' name='bp_msgat_attachment_ids' value=''>";
		bp_msgat_buffer_template_part( 'form' );
	}
	
	function ajax_upload_file(){
		// Check the nonce
		check_ajax_referer( 'bp_msgat_upload' );

		if ( !is_user_logged_in() ) {
			echo '-1';
			return false;
		}

		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ){
			require_once(ABSPATH . "wp-admin" . '/includes/image.php');
			require_once(ABSPATH . "wp-admin" . '/includes/file.php');
			require_once(ABSPATH . "wp-admin" . '/includes/media.php');
		}

		if ( ! function_exists('media_handle_upload' ) ){
			require_once(ABSPATH . 'wp-admin/includes/admin.php');
		}

		/*
		 * Need to call it once, so that result is cached and wp_upload_dir function is not called again.
		 * Otherwise, when we hook into upload_dir filter, the function internally calls bp_message_attachment()->get_upload_dir()
		 * which internally calls wp_upload_dir, causing a loop.
		 */
		bp_message_attachment()->get_upload_dir();

		//safe to add filter now
		add_filter( 'upload_dir', array( $this, 'temporarly_filters_wp_upload_dir' ) );
		$aid = media_handle_upload( 'file', 0 );
		remove_filter( 'upload_dir', array( $this, 'temporarly_filters_wp_upload_dir' ) );

		$file_info = wp_prepare_attachment_for_js( $aid );
		
		$result = array(
			'status'        => ( $aid !== null ),
			'attachment_id'	=> (int)$aid,
			'name'			=> $file_info['title'],
		);

		die( json_encode( $result ) );
	}
	
	public function add_attachments( $msg ){
		//dont do anything if it was a notice to all users instead of a private message.
		//this method is never called if a notice is being sent, but just to make sure...
		if( isset( $_POST['send-notice'] ) ){
			return;
		}
		
		$attachment_ids_csv = isset( $_POST['bp_msgat_attachment_ids'] ) ? trim( $_POST['bp_msgat_attachment_ids'], ',' ) : '';
		if( $attachment_ids_csv && !empty( $attachment_ids_csv ) ){
			$attachment_ids_temp = explode( ',', $attachment_ids_csv );
			$attachment_ids = array();
			foreach( $attachment_ids_temp as $a_id ){
				$a_id = (int)trim($a_id);
				if( $a_id )
					$attachment_ids[] = $a_id;
			}
			
			if( !empty( $attachment_ids ) )
				update_option( $this->option_name_prefix . $msg->id, $attachment_ids );
		}
	}
	
	public function show_attachments(){
		global $thread_template;
		$message_id = $thread_template->message->id;
		
		if( defined('DOING_AJAX') && DOING_AJAX && $thread_template->message_count > 1 ){
			// message reply is submitted with ajax
			// thread template is not traversed all the way, so we get id of first message in thread.
			$last_index = $thread_template->message_count - 1;
			$message = $thread_template->thread->messages[$last_index];
			$message_id = $message->id;
		}
		
		$attachment_ids = get_option( $this->option_name_prefix . $message_id );
		
		if( empty( $attachment_ids ) )
			return;
		
		/*
		 * Need to call it once, so that result is cached and wp_upload_dir function is not called again.
		 * Otherwise, when we hook into upload_dir filter, the function internally calls bp_message_attachment()->get_upload_dir()
		 * which internally calls wp_upload_dir, causing a loop.
		 */
		bp_message_attachment()->get_upload_dir();

		//safe to add filter now
		add_filter( 'upload_dir', array( $this, 'temporarly_filters_wp_upload_dir' ) );
		
		echo "<div class='attachments-wrapper'>";
		
		do_action( 'bp_msgat_before_attachments_list' );
		
		foreach( $attachment_ids as $a_id ){
			$file_info = wp_prepare_attachment_for_js( $a_id );
			$file_type_group = bp_message_attachment()->get_file_type_group( $file_info['subtype'] );
			
			$file_info['file_type_group'] = $file_type_group;
			msgat_the_attachment( $file_info );
			
			bp_msgat_buffer_template_part( 'file', $file_type_group );
		}
		
		do_action( 'bp_msgat_after_attachments_list' );
		
		echo "</div><!-- .attachments-wrapper -->";
		
		remove_filter( 'upload_dir', array( $this, 'temporarly_filters_wp_upload_dir' ) );
	}
}// End class BP_Msgat_Action
endif;