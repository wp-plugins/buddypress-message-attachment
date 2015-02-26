<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

function bp_msgat_load_template( $template, $variation=false ){
	$file = $template;
	
	if( $variation ){
		$file .= '-' . $variation;
	}
	$file .= '.php';
	
	$file_found = false;
	//first try to load template-variation.php
	if( file_exists(STYLESHEETPATH.'/buddypress/members/single/messages/attachments/'.$file ) ){
        include (STYLESHEETPATH.'/buddypress/members/single/messages/attachments/'.$file);
		$file_found = true;
	} else if(file_exists(TEMPLATEPATH.'/buddypress/members/single/messages/attachments/'.$file)){
        include (TEMPLATEPATH.'/buddypress/members/single/messages/attachments/'.$file);
		$file_found = true;
	} else if(file_exists(BPMSGAT_PLUGIN_DIR.'templates/'.$file)){
        include (BPMSGAT_PLUGIN_DIR.'templates/'.$file);
		$file_found = true;
	}
	
	if( !$file_found && $variation != '' ){
		//then try to load template.php
		$file = $template . '.php';
		if( file_exists(STYLESHEETPATH.'/buddypress/members/single/messages/attachments/'.$file ) ){
			include (STYLESHEETPATH.'/buddypress/members/single/messages/attachments/'.$file);
		} else if(file_exists(TEMPLATEPATH.'/buddypress/members/single/messages/attachments/'.$file)){
			include (TEMPLATEPATH.'/buddypress/members/single/messages/attachments/'.$file);
		} else if(file_exists(BPMSGAT_PLUGIN_DIR.'templates/'.$file)){
			include (BPMSGAT_PLUGIN_DIR.'templates/'.$file);
		}
	}
}

function bp_msgat_buffer_template_part( $template, $variation='', $echo=true ){
	ob_start();
	
	bp_msgat_load_template( $template, $variation );
	// Get the output buffer contents
	$output = ob_get_clean();

	// Echo or return the output buffer contents
	if ( true === $echo ) {
		echo $output;
	} else {
		return $output;
	}
}

function msgat_the_attachment( $file_info ){
	global $msgat_current_file;
	$msgat_current_file = $file_info;
}

function msgat_file_cssclass(){
	echo msgat_get_file_cssclass();
}
	function msgat_get_file_cssclass(){
		global $msgat_current_file;
		$classes = array( 'attachment' );
		
		$classes[] = $msgat_current_file['file_type_group'];
		$classes[] = $msgat_current_file['subtype'];
		$classes[] = 'file-' . $msgat_current_file['id'];
		
		return apply_filters( 'msgat_get_file_cssclass', implode( ' ', $classes ) );
	}
	
function msgat_file_thumbnail_url(){
	echo msgat_get_file_thumbnail_url();
}
	function msgat_get_file_thumbnail_url(){
		global $msgat_current_file;
		
		$url = $msgat_current_file['icon'];
		if( $msgat_current_file['type']=='image' ){
			if( isset( $msgat_current_file['sizes'] ) && isset( $msgat_current_file['sizes']['thumbnail'] ) ){
				$url = $msgat_current_file['sizes']['thumbnail']['url'];
			}
		}
		
		return apply_filters( 'msgat_get_file_thumbnail_url', $url );
	}
	
function msgat_file_download_url(){
	echo msgat_get_file_download_url();
}
	function msgat_get_file_download_url(){
		global $msgat_current_file;
		
		$url = trailingslashit( bp_loggedin_user_domain() . bp_get_messages_slug() . '/attachment/' . $msgat_current_file['id'] . '/' . bp_get_the_thread_id() );
		
		return apply_filters( 'msgat_get_file_download_url', $url );
	}
	
function msgat_file_url(){
	echo msgat_get_file_url();
}
	function msgat_get_file_url(){
		global $msgat_current_file;
		$url = $msgat_current_file['url'];
		return apply_filters( 'msgat_get_file_url', $url );
	}
	
function msgat_file_name(){
	echo msgat_get_file_name();
}
	function msgat_get_file_name(){
		global $msgat_current_file;
		$name = $msgat_current_file['title'];
		return apply_filters( 'msgat_get_file_name', $name );
	}