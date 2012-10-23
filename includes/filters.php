<?php
 
 add_filter( 'bp_msgat_data_before_save', 'wp_filter_kses', 1 );

 add_filter( 'bp_msgat_data_message_id_before_save', 'wp_filter_kses', 1 );
 add_filter( 'bp_msgat_data_attachement_url_before_save', 'wp_filter_kses', 1 );

?>