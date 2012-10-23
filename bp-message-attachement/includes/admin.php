<?php
function bp_msgat_add_admin_menu() {
	global $bp;

	if ( !is_super_admin() )
		return false;

	add_submenu_page( 
		'bp-general-settings',
		__( 'Message Attachement', 'bp-msgat' ),
		__( 'Message Attachement', 'bp-msgat' ),
		'manage_options', 
		'bp-msgat-settings', 
		'bp_msgat_admin' 
	);
}
// The bp_core_admin_hook() function returns the correct hook (admin_menu or network_admin_menu),
// depending on how WordPress and BuddyPress are configured
add_action( bp_core_admin_hook(), 'bp_msgat_add_admin_menu' );

/**
 * bp_msgat_admin()
 *
 * Checks for form submission, saves component settings and outputs admin screen HTML.
 */
function bp_msgat_admin() {
	global $bp;
	/*check and update the default value for audio, video codes*/
	$ma_audiocode = get_option( 'ma_audiocode' );
	if(!isset($ma_audiocode) || $ma_audiocode ==""){
		$ma_audiocode = '<audio controls="controls">[att-code]Your browser does not support the audio element.</audio>';
		update_option( 'ma_audiocode', $ma_audiocode );
	}
	$ma_videocode = get_option( 'ma_videocode' );
	if(!isset($ma_videocode) || $ma_videocode ==""){
		$ma_videocode = '<video width="320" height="240" controls="controls" preload="metadata">[att-code]Your browser does not support the video tag.</video>';
		update_option( 'ma_videocode', $ma_videocode );
	}
	
	/* If the form has been submitted and the admin referrer checks out, save the settings */
	if ( isset( $_POST['ma-submit'] ) && check_admin_referer('msgat-settings') ) {
		$ext_arr = $_POST['ma-allowedExtensions'];
		$ext_csv ="";
		if(isset($ext_arr) && !empty($ext_arr)){
		foreach($ext_arr as $ext){
			$ext_csv .= $ext .",";
		}
		}
		if(trim($_POST['ma-addNewExtention'])!=""){
			//remove the first and last character from string if they are comma
			$more_ext = trim(trim($_POST['ma-addNewExtention']),",");
			$more_ext = str_replace(".","", $more_ext);
			$ext_csv .= $more_ext;
		}
		//remove the last comma if present
		$ext_csv = trim(trim($ext_csv),",");
		
		update_option( 'ma_allowedExtension', $ext_csv );
		update_option( 'ma_maxFileSize', $_POST['ma-maxFileSize'] );
		if(isset($_POST['code_audio']) && trim($_POST['code_audio'])!=""){
			update_option( 'ma_audiocode', stripslashes(trim($_POST['code_audio'])) );
		}
		if(isset($_POST['code_video']) && trim($_POST['code_video'])!=""){
			update_option( 'ma_videocode', stripslashes(trim($_POST['code_video'])) );
		}
		
		$ma_show_attach = "";
		if(isset($_POST['show_attach_audio'])){$ma_show_attach = "audio";}
		if(isset($_POST['show_attach_video'])){$ma_show_attach .= ",video";}
		$ma_show_attach = trim($ma_show_attach,",");
		update_option('ma_show_attach',$ma_show_attach);
		
		$updated = true;
	}
	$ma_allowedExtensions = get_option( 'ma_allowedExtension' );
	$ma_maxFileSize = get_option( 'ma_maxFileSize' );
	
?>
	<div class="wrap">
		<h2><?php _e( 'Manage Option for attachement in private messages', 'bp-msgat' ) ?></h2>
		<br />

		<?php if ( isset($updated) ) : ?><?php echo "<div id='message' class='updated fade'><p>" . __( 'Settings Updated.', 'bp-msgat' ) . "</p></div>" ?><?php endif; ?>

		<form action="<?php echo site_url() . '/wp-admin/admin.php?page=bp-msgat-settings' ?>" name="msgat-settings-form" id="msgat-settings-form" method="post">

			<table class="form-table">
				<tr valign="top">
					<th scope="row"><label><?php _e( 'Allowed Extensions', 'bp-msgat' ) ?></label></th>
					<td>
						<ul class='msgat_ext_chkboxlist'>
						<?php
							
							$al_ext_arr = explode(",",$ma_allowedExtensions);
							$counter = 1;
							foreach($al_ext_arr as $ext){
								if(trim($ext!="")){?>
									<li>
									<input type="checkbox" id="ma-allowedExtensions<?php echo $counter;?>" name="ma-allowedExtensions[]" value="<?php echo $ext;?>" checked="checked"/>
									<label for="ma-allowedExtensions<?php echo $counter;?>"><?php echo $ext;?></label>
									</li>
							<?php
								}
								$counter++;
							}
						?>
						</ul>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="ma-addNewExtention"><?php _e( 'Add more file types (extensions)', 'bp-msgat' ) ?></label></th>
					<td>
						<input name="ma-addNewExtention" type="text" id="ma-addNewExtention"  size="50" /><br/>
						<small><em>
						<?php _e( 'comma separated values. example: png, jpg etc.. wihtout any dot (".")', 'bp-msgat' ) ?>
						</em></small>
					</td>
				</tr>
				<tr>
					<th scope="row"><label for="target_uri"><?php _e( 'Maximum File Size', 'bp-msgat' ) ?></label></th>
					<td>
						<input name="ma-maxFileSize" type="text" id="ma-maxFileSize" value="<?php echo esc_attr( $ma_maxFileSize ); ?>" size="5" />MB
					</td>
				</tr>
				<tr><td colspan="2"><hr/></td></tr>
				<tr>
					<td colspan="2">
						<strong><?php _e('Add code to wrap around the attachements.','bp-msgat');?></strong>
						<em><?php _e('"[att-code]" is the shortcode for file url','bp-msgat');?></em>
					</td>
				</tr>
				<tr>
					<td>
						<?php _e('Code for audio files','bp-msgat');?><br/>
						<textarea name="code_audio" id="code_audio" rows="8" cols="50"><?php echo stripslashes(get_option( 'ma_audiocode' ));?></textarea>
						<?php
						$show_attach_vid = false; $show_attach_aud = false;
						$ma_show_attach = get_option('ma_show_attach');
						if(isset($ma_show_attach) && $ma_show_attach !=""){
							$show_attach = explode(",",$ma_show_attach);
							if (in_array("audio", $show_attach)) {
								$show_attach_aud = true;
							}
							if (in_array("video", $show_attach)) {
								$show_attach_vid = true;
							}
						}
						?>
						<br/>
						<input type="checkbox" name="show_attach_audio" id="show_attach_audio" <?php if($show_attach_aud){echo 'checked="checked"';} ?>/>
						<label for="show_attach_audio"><?php _e('Show Audio tag','bp-msgat');?></label>
					</td>
					<td>
						<?php _e('Code for video files','bp-msgat');?><br/>
						<textarea name="code_video" id="code_video" rows="8" cols="50"><?php echo stripslashes(get_option( 'ma_videocode' ));?></textarea>
						<br/>
						<input type="checkbox" name="show_attach_video" id="show_attach_video" <?php if($show_attach_vid){echo 'checked="checked"';} ?>/>
						<label for="show_attach_video"><?php _e('Show Video tag','bp-msgat');?></label>
					</td>
				</tr>
			</table>
			<p class="submit">
				<input type="submit" name="ma-submit" value="<?php _e( 'Save Settings', 'bp-msgat' ) ?>"/>
			</p>

			<?php
			/* This is very important, don't leave it out. */
			wp_nonce_field( 'msgat-settings' );
			?>
		</form>
	</div>
<?php
}


function bp_msgat_install_tables() {
	global $wpdb;

	if ( !is_super_admin() )
		return;

	if ( !empty($wpdb->charset) )
		$charset_collate = "DEFAULT CHARACTER SET $wpdb->charset";

	
	$sql = array();
	$sql[] = "CREATE TABLE IF NOT EXISTS {$wpdb->base_prefix}bp_msgat (
		  		id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
		  		message_id bigint(20) NOT NULL,
		  		attachement_url varchar(200) NOT NULL,
			    KEY message_id (message_id),
			    KEY attachement_url (attachement_url)
		 	   ) {$charset_collate};";

	dbDelta($sql);

	update_site_option( 'bp-msgat-db-version', BP_EXAMPLE_DB_VERSION );
}
//add_action( 'admin_init', 'bp_msgat_install_tables' );
?>