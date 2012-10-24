<?php
function IsNullOrEmptyString($question){
    return (!isset($question) || trim($question)==='');
}
function bp_message_attachement_compose() {
	global $bp;?>
	
	<label>
		<?php _e('Add an attachement <br/>','bp-msgat');?>
		<small><em> <?php _e('Allowed file types :','bp-msgat'); echo get_option( 'ma_allowedExtension' );?></em></small>
	</label>
	<input type='file' name='ma_file' id='ma_file'/>
    <?php
	wp_enqueue_script( 'bp-msgat-general', WP_PLUGIN_URL .'/buddypress-message-attachment/includes/js/general.js' );
    
    ?>
<?php
}

function bp_message_attachement_compose_reply() {
	global $bp;?>
	
	<label>
		<?php _e('Add an attachement <br/>','bp-msgat');?>
		<small><em> <?php _e('Allowed file types :','bp-msgat'); echo get_option( 'ma_allowedExtension' );?></em></small>
	</label>
	<input type='file' name='ma_file' id='ma_file'/>
   <?php
    wp_enqueue_script( 'bp-msgat-reply', WP_PLUGIN_URL .'/buddypress-message-attachment/includes/js/reply.js' );
    
    ?>
<?php
}

function bp_message_attachement_add_attachement($msg){
	global $bp; global $wpdb;
	$f_u = "ma_file";//name of the file upload control
	/*do anyting only if user has seleted a file*/
	if ($_FILES[$f_u]['name']!="" && $_FILES[$f_u]['error'] != UPLOAD_ERR_NO_FILE){
		$upload_directory = wp_upload_dir();
		//an array : $upload_directory[basedir] => C:\path\to\wordpress\wp-content\uploads 
		$upload_folder = $upload_directory['basedir']."/message-attachements";
		
		$max_allwd_size = get_option( 'ma_maxFileSize' );//max size in MB
		$max_allwd_size = $max_allwd_size * 1024 * 1000; //converted to bytes
		
		$allwd_ext = get_option( 'ma_allowedExtension' );
		
		$allowedExts = explode(",",$allwd_ext);
		$extension = end(explode(".", $_FILES[$f_u]["name"]));
		
		$ret_msg ="";
		
		if ( $_FILES[$f_u]["size"] < $max_allwd_size && in_array($extension, $allowedExts)){
		
			if ($_FILES[$f_u]["error"] > 0){
				msgat_delete_message($msg->date_sent);
				bp_core_add_message( __( 'There was an error sending that message: file could not be attached, please try again', 'bp-msgat' ), 'error' );
				$red_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );
				bp_core_redirect($red_url);
				return false;
			}
			else{
				//make the file name unique
				$org_name = md5($msg->date_sent) . $_FILES[$f_u]["name"];
				//$_FILES[$f_u]["name"] = $msg->thread_id . $_FILES[$f_u]["name"];
				
				//check if folder exists
				if(!file_exists($upload_folder))
				{
					mkdir($upload_folder,0777);
				}
				
				if (file_exists($upload_folder . "/" . $org_name)){
					return false;
				}
				else{
					move_uploaded_file($_FILES[$f_u]["tmp_name"],
					$upload_folder . "/" . $org_name);
					//file uploaded successfuly now save the custom post
					
					$pst_content = "<a href='" . $upload_directory['baseurl'] . "/message-attachements/".$org_name."' title='view/Download' class='msgat_file'>"
										.$_FILES[$f_u]["name"]
									."</a>";
					$attachement_link = $upload_directory['baseurl'] . "/message-attachements/".$org_name;
					$args = array(
						'title'				=> $_FILES[$f_u]["name"],
						'message_id' 		=> $msg->thread_id ."=".$msg->date_sent,
						'sender_id'			=> $msg->sender_id,
						'attachement_url' 	=> $pst_content,
						'excerpt'			=> $attachement_link
					);
					$msgat_obj = new BP_Msgat_Attachement($args);
					//save the attachement custom post
					$msgat_obj->save();
				}
			}
		}
		else{
			msgat_delete_message($msg->date_sent);
			bp_core_add_message( __( 'There was an error sending that message: attachement too big or the file type not allowed, please try again', 'bp-msgat' ), 'error' );
			
			global $wp;
			$red_url = add_query_arg( $wp->query_string, '', home_url( $wp->request ) );
			
			bp_core_redirect($red_url);
			return false;
		}
	}
}
function msgat_delete_message($date_sent){
	global $wpdb; global $bp;
	$wpdb->query( $wpdb->prepare( "DELETE FROM {$bp->messages->table_name_messages} WHERE date_sent = '".$date_sent."'") );
}
function bp_message_attachement_show_attachement($msg){
	global $thread_template, $bp, $wpdb;
	if(!IsNullOrEmptyString($thread_template->message->thread_id)){
		/*
		 ***I am an ajax call, i've bypassed you isReply clause !!!!*****
		To avoid displaying attachements for all messages in a thread:
		1) fetch all messages in the current thread ($thread_template->message->thread_id)
			default order by id 'ASC' is already there
		3) then, check if if of first message in the thread is equal to current message's id
		4) if yes this is the first message of the thread and you can display the attachements, the set $isReply as true
		5) if no then then this is not the first message of the thread (must be the ajax call which bypassed the isReply clause), 
			dont display the attachements and still set $isReply as true
		*/
		$params= array(
			'author' 		=> $thread_template->message->sender_id,
			'post_type'		=> 'messageattachements',
			'meta_key' 		=> 'bp_msgat_message_id',
			//'meta_value'	=> $thread_template->message->thread_id
			'meta_value'	=> $thread_template->message->thread_id ."=".$thread_template->message->date_sent
		);
		//$params = 'author='.$thread_template->message->sender_id.',post_type=messageattachements';
		// The Query
		$the_query = new WP_Query( $params );
		if ($the_query->have_posts() ) {?>
			<div class="msgat_message_attachement">
				<span><?php _e('Attachements','bp-msgat');?></span>
				<ul class="msgat_att_list">
					<?php
					// The Loop
					while ( $the_query->have_posts() ) : $the_query->the_post();
						echo '<li class="'.$thread_template->message->id.'">';
						the_content();
						echo '</li>';
					endwhile;
				?>
				</ul>
				<br style="clear:both;"/>
				<?php
				$key_1_value = get_post_meta(get_the_ID(), 'bp_msgat_attachement_url', true);
				// check if the custum field has a value
				if($key_1_value != '') {
					/*check if the attachement is audio or video and if yes echo the respective code that user has provided*/
					$extension = explode(".",$key_1_value);
					$filetype = ""; $replacement ="";
					$count = count($extension);
					if(isset($count) && $count>0){
						$extension = trim($extension[$count-1]);
						
						switch ($extension) {
							case 'mp3':
								$filetype= "audio";
								$replacement = "<source src='".$key_1_value."' type='audio/mp3'/>";
								break;
							case 'mp4':
								$filetype = "video";
								$replacement = "<source src='".$key_1_value."' type='video/mp4'/>";
								break;
							case 'ogg':
								$filetype = "video";
								$replacement = "<source src='".$key_1_value."' type='video/ogg'/>";
								break;
						}
					}
					$ma_show_attach = get_option('ma_show_attach');
					$show_file = false;
					if(isset($ma_show_attach) && $ma_show_attach !=""){
						$show_attach = explode(",",$ma_show_attach);
						if (in_array($filetype, $show_attach)) {
							$show_file = true;
						}
					}
					if($filetype!="" && $replacement !="" && $show_file == true){
						switch ($filetype) {
							case 'audio':
								$code= get_option( 'ma_audiocode' );
								$code_ac = str_replace("[att-code]", $replacement, $code);
								echo $code_ac;
								break;
							case 'video':
								$code= get_option( 'ma_videocode' );
								$code_ac = str_replace("[att-code]", $replacement, $code);
								echo $code_ac;
								break;
						}
					}
				} 
				
				?>
			</div>
			<?php
			wp_enqueue_style( 'bp-msgat-style', WP_PLUGIN_URL .'/buddypress-message-attachment/includes/css/general.css' );
			?>
		<?php
		}
		// Reset Post Data
		wp_reset_postdata();
		
	}
}

add_action( 'bp_after_messages_compose_content', 'bp_message_attachement_compose' );
add_action( 'bp_after_message_reply_box','bp_message_attachement_compose_reply');

add_action( 'messages_message_after_save', 'bp_message_attachement_add_attachement');
add_action( 'bp_after_message_content', 'bp_message_attachement_show_attachement');
?>