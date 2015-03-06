<div class="bp_msgat_ui_wrapper">
	<label>
		<?php _e( 'Add an attachment','bp-msgat' );?><br>
	</label>
	<small><em><?php _e( 'Allowed file types : ', 'bp-msgat' ); echo implode( ', ', bp_message_attachment()->option('file-types') ); ?></em></small>
	<p><button class="button button-secondary" id="btn_msgat_upload" name="btn_msgat_upload"><?php _e( 'Choose file', 'bp-msgat' );?></button></p>
</div>
