<?php

class BP_Msgat_Attachement {
	var $id;
	var $title;
	var $message_id;
	var $sender_id;
	var $attachement_url;
	var $excerpt;
	var $query;

	function __construct( $args = array() ) {
		// Set some defaults
		$defaults = array(
			'id'				=> 0,
			'title'				=> '',
			'message_id' 		=> 0,
			'sender_id'			=> 0,
			'attachement_url' 	=> "",
			'excerpt'			=> ""
		);

		// Parse the defaults with the arguments passed
		$r = wp_parse_args( $args, $defaults );
		extract( $r );

		if ( $id ) {
			$this->id = $id;
			
			$this->populate( $this->id );
		} else {
			foreach( $r as $key => $value ) {
				$this->{$key} = $value;
			}
		}
	}

	function populate() {
		global $wpdb, $bp, $creds;

		if ( $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM ".$wpdb->base_prefix."posts WHERE id = %d", $this->id ) ) ) {
			//$this->message_id = $row->message_id;
			$this->sender_id = $row->sender_id;
			$this->attachement_url  = $row->attachement_url;
		}
	}

	

	function save() {
		global $wpdb, $bp;

		

		if ( $this->id ) {
			// Set up the arguments for wp_insert_post()
			$wp_update_post_args = array(
				'ID'			=> $this->id,
				'post_author'	=> $this->sender_id,
				'post_title'	=> $this->title,
				'post_content'	=> $this->attachement_url
			);

			// Save the post
			$result = wp_update_post( $wp_update_post_args );

			// We'll store the message's ID as postmeta
			if ( $result ) {
				update_post_meta( $result, 'bp_msgat_message_id', $this->message_id );
				update_post_meta( $result, 'bp_msgat_attachement_url', $this->excerpt );
			}
		} else {
			// Set up the arguments for wp_insert_post()
			$wp_insert_post_args = array(
				'post_status'	=> 'publish',
				'post_type'		=> 'messageattachements',
				'post_author'	=> $this->sender_id,
				'post_title'	=> $this->title,
				'post_content'	=> $this->attachement_url,
				'post_excerpt'	=> $this->excerpt
			);

			// Save the post
			$result = wp_insert_post( $wp_insert_post_args );

			// We'll store the reciever's ID as postmeta
			if ( $result ) {
				update_post_meta( $result, 'bp_msgat_message_id', $this->message_id );
				update_post_meta( $result, 'bp_msgat_attachement_url', $this->excerpt );
			}
		}

		return $result;
	}

	function get( $args = array() ) {
		// Only run the query once
		if ( empty( $this->query ) ) {
			$defaults = array(
				'sender_id'	=> 0,
				'message_id'	=> 0,
				'per_page'	=> 10,
				'paged'		=> 1
			);

			$r = wp_parse_args( $args, $defaults );
			extract( $r );

			$query_args = array(
				'post_status'	 => 'publish',
				'post_type'	 => 'Message Attachements',
				'posts_per_page' => $per_page,
				'paged'		 => $paged,
				'meta_query'	 => array()
			);

			if ( $sender_id ) {
				$query_args['author'] = (array)$sender_id;
			}

			// We can filter by postmeta by adding a meta_query argument. Note that
			if ( $message_id ) {
				$query_args['meta_query'][] = array(
					'key'	  => 'bp_msgat_message_id',
					'value'	  => (array)$message_id,
					'compare' => 'IN' // Allows $recipient_id to be an array
				);
			}

			// Run the query, and store as an object property, so we can access from
			// other methods
			$this->query = new WP_Query( $query_args );

			// Let's also set up some pagination
			$this->pag_links = paginate_links( array(
				'base' => add_query_arg( 'items_page', '%#%' ),
				'format' => '',
				'total' => ceil( (int) $this->query->found_posts / (int) $this->query->query_vars['posts_per_page'] ),
				'current' => (int) $paged,
				'prev_text' => '&larr;',
				'next_text' => '&rarr;',
				'mid_size' => 1
			) );
		}
	}


	function have_posts() {
		return $this->query->have_posts();
	}


	function the_post() {
		return $this->query->the_post();
	}


	function delete() {
		return wp_trash_post( $this->id );
	}



	function delete_all() {

	}

	function delete_by_user_id() {

	}
}

?>