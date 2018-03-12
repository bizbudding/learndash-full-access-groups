<?php

// Register styles and scripts for later.
add_action( 'admin_enqueue_scripts', function() {
	wp_register_style( 'select2',                LD_FULL_ACCESS_GROUPS_PLUGIN_URL . 'assets/css/select2.min.css', array(), '4.0.5' );
	wp_register_script( 'select2',               LD_FULL_ACCESS_GROUPS_PLUGIN_URL . 'assets/js/select2.min.js', array( 'jquery' ), '4.0.5', true );
	wp_register_script( 'ld_full_access_groups', LD_FULL_ACCESS_GROUPS_PLUGIN_URL . 'assets/js/ld-full-access-groups.js', array( 'select2' ), LD_FULL_ACCESS_GROUPS_VERSION, true );
});

add_action( 'add_meta_boxes', 'ld_full_access_groups_register_metabox' );
function ld_full_access_groups_register_metabox() {

	add_meta_box(
		'ld_full_access_groups_edit',
		esc_html__( 'LearnDash Full Access Groups', 'text-domain' ),
		'ld_full_access_groups_render_metabox',
		'sfwd-courses',
		'side',
		'core'
	);

}


function ld_full_access_groups_render_metabox( $post ) {

	wp_enqueue_style( 'select2' );
	wp_enqueue_script( 'select2' );
	wp_enqueue_script( 'ld_full_access_groups' );

	$args = array(
		'post_type'              => 'groups',
		'posts_per_page'         => 500,
		'post_status'            => 'publish',
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	);

	$groups = new WP_Query( $args );

	if ( $groups->have_posts() ) {
		// Set nonce.
		wp_nonce_field( 'nonce_ld_full_access_groups_action', 'nonce_ld_full_access_groups_field' );
		// Existing values.
		$selected = (array) get_post_meta( $post->ID, 'ld_full_access_groups', true );
		// Select field.
		echo '<p><select id="ld_full_access_groups" class="ld-full-access-groups widefat" name="ld_full_access_groups[]" multiple="multiple">';
			while ( $groups->have_posts() ) : $groups->the_post();
				printf( '<option value="%s" %s>%s</option>', get_the_ID(), selected( in_array( get_the_ID(), $selected ) ), get_the_title() );
			endwhile;
		echo '</select></p>';
		printf( '<em>%s</em>', esc_html__( 'Selected groups will have access to this course without start date restrictions. Course access/enrollment is managed by the group itself. Course prerequisites will not be affected.', 'learndash-full-access-groups' ) );
	}

	wp_reset_postdata();

}

add_action( 'save_post_sfwd-courses', 'ld_full_access_groups_save_metabox' );
function ld_full_access_groups_save_metabox( $post_id ) {

	if ( ! isset( $_POST[ 'nonce_ld_full_access_groups_field' ] ) || ! wp_verify_nonce( $_POST[ 'nonce_ld_full_access_groups_field' ], 'nonce_ld_full_access_groups_action' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	if ( is_multisite() && ms_is_switched() ) {
		return;
	}

	if ( ! isset( $_POST['ld_full_access_groups'] ) ) {
		return;
	}

	// Get the groups array.
	$groups = (array) $_POST['ld_full_access_groups'];

	// Sanitize.
	$groups = array_map( 'absint', $groups );

	// Update the post meta.
	update_post_meta( $post_id, 'ld_full_access_groups', $groups );

}
