<?php
/**
 * Plugin Name:   Simple Lead Gen Form
 * Plugin URI:    http://github.com/josephfusco/simple-lead-gen-form
 * Description:   Add a simple lead generation form to your website, collect & manage information from potential customers.
 * Version:       1.0.0
 * Author:        Joseph Fusco
 * Author URI:    http://josephfus.co
 * License:       GPLv2 or later
 * Text Domain:   simple-lead-gen-form
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initialize plugin.
 *
 * @since  1.0.0
 */
function slgf_init() {
	slgf_register_customer_cpt();
	slgf_register_customer_tax();
}
add_action( 'init', 'slgf_init' );

/**
 * Flush rewrite rules.
 *
 * @since  1.0.0
 */
function slgf_rewrite_flush() {
	slgf_register_customer_cpt();
	flush_rewrite_rules();
	set_transient( 'slgf-shortcode-notice', true, 5 );
}
register_activation_hook( __FILE__, 'slgf_rewrite_flush' );

/**
 * Shortcode notice on activation.
 *
 * @since  1.0.0
 */
function slgf_shortcode_admin_notice() {
	if ( get_transient( 'slgf-shortcode-notice' ) ) {
		?>
		<div class="updated notice is-dismissible">
			<p>Use the shortcode <code>[slgf_form]</code> to get started!</p>
		</div>
		<?php
		delete_transient( 'slgf-shortcode-notice' );
	}
}
add_action( 'admin_notices', 'slgf_shortcode_admin_notice' );

/**
 * Enqueue frontend scripts & styles.
 *
 * @since  1.0.0
 */
function slgf_enqueue_frontend_scripts() {
	wp_enqueue_style( 'simple-lead-gen-form', plugins_url( 'assets/styles/form.css' , __FILE__ ), array(), '1.0' );
	wp_enqueue_script( 'slgf-ajax', plugins_url( 'assets/js/form.js', __FILE__ ), array( 'jquery' ), '1.0', true );

	wp_localize_script( 'slgf-ajax', 'slgf_ajax_object',
		array(
			'ajax_url'   => admin_url( 'admin-ajax.php' ),
			'ajax_nonce' => wp_create_nonce( 'slgf_nonce_fusco' ),
		)
	);
}
add_action( 'wp_enqueue_scripts', 'slgf_enqueue_frontend_scripts' );

/**
 * Enqueue admin scripts & styles.
 *
 * @since  1.0.0
 */
function slgf_enqueue_admin_scripts() {
	global $post_type;

	if ( 'customer' === $post_type ) {
		wp_enqueue_style( 'simple-lead-gen-form-admin', plugins_url( 'assets/styles/admin.css' , __FILE__ ), array(), '1.0' );
	}
}
add_action( 'admin_enqueue_scripts', 'slgf_enqueue_admin_scripts' );

/**
 * Form shortcode.
 *
 * @since  1.0.0
 *
 * @param  $attr
 */
function slgf_form( $attr ) {
	$attr = shortcode_atts( array(
		'label_name'        => 'Name',
		'label_phone'       => 'Phone Number',
		'label_email'       => 'Email Address',
		'label_budget'      => 'Desired Budget (USD)',
		'label_message'     => 'Message',
		'maxlength_name'    => '',
		'maxlength_phone'   => '',
		'maxlength_email'   => '',
		'maxlength_budget'  => '',
		'maxlength_message' => '',
		'rows_message'      => '',
		'cols_message'      => '',
	), $attr );

	ob_start();

	?>
	<form id="slgf" class="slgf" action="" method="post" enctype="multipart/form-data">

		<div class="slgf-form-group">
			<label for="slgf_name"><?php echo esc_html( $attr['label_name'] ); ?></label>
			<input type="text" id="slgf_name" name="slgf_name" maxlength="<?php echo esc_html( $attr['maxlength_name'] ); ?>" required>
		</div>

		<div class="slgf-form-group">
			<label for="slgf_phone"><?php echo esc_html( $attr['label_phone'] ); ?></label>
			<input type="tel" id="slgf_phone" name="slgf_phone" maxlength="<?php echo esc_html( $attr['maxlength_phone'] ); ?>">
		</div>

		<div class="slgf-form-group">
			<label for="slgf_email"><?php echo esc_html( $attr['label_email'] ); ?></label>
			<input type="email" id="slgf_email" name="slgf_email" maxlength="<?php echo esc_html( $attr['maxlength_email'] ); ?>" required>
		</div>

		<div class="slgf-form-group">
			<label for="slgf_budget"><?php echo esc_html( $attr['label_budget'] ); ?></label>
			<input type="number" id="slgf_budget" name="slgf_budget" maxlength="<?php echo esc_html( $attr['maxlength_budget'] ); ?>">
		</div>

		<div class="slgf-form-group">
			<label for="slgf_message"><?php echo esc_html( $attr['label_message'] ); ?></label>
			<textarea id="slgf_message" name="slgf_message" maxlength="<?php echo esc_html( $attr['maxlength_message'] ); ?>" rows="<?php echo esc_html( $attr['rows_message'] ); ?>" cols="<?php echo esc_html( $attr['cols_message'] ); ?>" required></textarea>
		</div>

		<div class="slgf-form-group">
			<input type="hidden" id="slgf_time" name="slgf_time" value="<?php echo esc_html( slgf_get_current_time() ); ?>">
			<input type="submit">
		</div>

	</form>
	<?php

	return ob_get_clean();
}
add_shortcode( 'slgf_form', 'slgf_form' );

/**
 * Get current time from 3rd party API.
 *
 * @link   http://timezonedb.com/
 * @since  1.0.0
 *
 * @return Unix epoch time
 */
function slgf_get_current_time() {
	$api_key      = 'H5N5BOUU90WC';
	$api_timezone = 'America/New_York';
	$api_request  = 'http://api.timezonedb.com/v2/get-time-zone?key=' . $api_key . '&format=json&by=zone&zone=' . $api_timezone;
	$api_response = wp_remote_get( $api_request );
	$api_data     = json_decode( wp_remote_retrieve_body( $api_response ), true );

	return $api_data['timestamp'];
}

/**
 * Process form data.
 *
 * @since  1.0.0
 */
function slgf_process() {
	check_ajax_referer( 'slgf_nonce_fusco', 'security' );

	if ( isset( $_POST['name'] ) ) {
		$name = sanitize_text_field( wp_unslash( $_POST['name'] ) );
	}
	if ( isset( $_POST['phone'] ) ) {
		$phone = sanitize_text_field( wp_unslash( $_POST['phone'] ) );
	}
	if ( isset( $_POST['email'] ) ) {
		$email = sanitize_email( wp_unslash( $_POST['email'] ) );
	}
	if ( isset( $_POST['budget'] ) ) {
		$budget = sanitize_text_field( wp_unslash( $_POST['budget'] ) );
	}
	if ( isset( $_POST['message'] ) ) {
		$message = sanitize_text_field( wp_unslash( $_POST['message'] ) );
	}
	if ( isset( $_POST['time'] ) ) {
		$time = sanitize_text_field( wp_unslash( $_POST['time'] ) );
	}

	$id = wp_insert_post(
		array(
			'post_status' => 'publish',
			'post_type'   => 'customer',
			'post_title'  => $name,
			'meta_input'  => array(
				'phone'   => $phone,
				'email'   => $email,
				'budget'  => $budget,
				'message' => $message,
				'time'    => $time,
			),
		)
	);

	echo 'Your information has been submitted!';

	wp_die();
}
add_action( 'wp_ajax_slgf_process_form', 'slgf_process' );
add_action( 'wp_ajax_nopriv_slgf_process_form', 'slgf_process' );

/**
 * Register customer custom post type.
 *
 * @since  1.0.0
 */
function slgf_register_customer_cpt() {
	$args = array(
		'publicly_queryable'   => false,
		'exclude_from_search'  => true,
		'show_in_nav_menus'    => false,
		'show_ui'              => true,
		'show_in_menu'         => true,
		'show_in_admin_bar'    => true,
		'menu_position'        => null,
		'menu_icon'            => 'dashicons-id-alt',
		'can_export'           => true,
		'delete_with_user'     => false,
		'hierarchical'         => false,
		'supports'             => array( 'title', 'custom-fields' ),
		'labels'               => array(
			'name'               => __( 'Customers', 'simple-lead-gen-form' ),
			'singular_name'      => __( 'Customer', 'simple-lead-gen-form' ),
			'menu_name'          => __( 'Customers', 'simple-lead-gen-form' ),
			'name_admin_bar'     => __( 'Customers', 'simple-lead-gen-form' ),
			'add_new'            => __( 'Add New', 'simple-lead-gen-form' ),
			'add_new_item'       => __( 'Add New Customer', 'simple-lead-gen-form' ),
			'edit_item'          => __( 'Edit Customer', 'simple-lead-gen-form' ),
			'new_item'           => __( 'New Customer', 'simple-lead-gen-form' ),
			'view_item'          => __( 'View Customer', 'simple-lead-gen-form' ),
			'search_items'       => __( 'Search Customers', 'simple-lead-gen-form' ),
			'not_found'          => __( 'No customers found', 'simple-lead-gen-form' ),
			'not_found_in_trash' => __( 'No customers found in trash', 'simple-lead-gen-form' ),
			'all_items'          => __( 'All Customers', 'simple-lead-gen-form' ),
			'archive_title'      => __( 'Customers', 'simple-lead-gen-form' ),
		),
		'capabilities'         => array(
			'edit_post'          => 'activate_plugins',
			'read_post'          => 'activate_plugins',
			'delete_post'        => 'activate_plugins',
			'edit_posts'         => 'activate_plugins',
			'edit_others_posts'  => 'activate_plugins',
			'delete_posts'       => 'activate_plugins',
			'publish_posts'      => 'activate_plugins',
			'read_private_posts' => 'activate_plugins',
			'create_posts'       => 'do_not_allow',
		),
	);

	register_post_type( 'customer', $args );
}

/**
 * Customer update messages.
 *
 * See /wp-admin/edit-form-advanced.php
 *
 * @param array $messages Existing post update messages.
 *
 * @return array Amended post update messages with new CPT update messages.
 */
function slgf_customer_updated_messages( $messages ) {
	$post             = get_post();
	$post_type        = get_post_type( $post );
	$post_type_object = get_post_type_object( $post_type );

	$messages['customer'] = array(
		0  => '',
		1  => __( 'Customer updated.', 'simple-lead-gen-form' ),
		2  => __( 'Custom field updated.', 'simple-lead-gen-form' ),
		3  => __( 'Custom field deleted.', 'simple-lead-gen-form' ),
		4  => __( 'Customer updated.', 'simple-lead-gen-form' ),
		5  => isset( $_GET['revision'] ) ? sprintf( __( 'Customer restored to revision from %s', 'simple-lead-gen-form' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false,
		6  => __( 'Customer published.', 'simple-lead-gen-form' ),
		7  => __( 'Customer saved.', 'simple-lead-gen-form' ),
		8  => __( 'Customer submitted.', 'simple-lead-gen-form' ),
		9  => sprintf(
			__( 'Customer scheduled for: <strong>%1$s</strong>.', 'simple-lead-gen-form' ),
			date_i18n( __( 'M j, Y @ G:i', 'simple-lead-gen-form' ), strtotime( $post->post_date ) )
		),
		10 => __( 'Customer draft updated.', 'simple-lead-gen-form' ),
	);

	if ( $post_type_object->publicly_queryable ) {
		$permalink = get_permalink( $post->ID );

		$view_link = sprintf( ' <a href="%s">%s</a>', esc_url( $permalink ), __( 'View customer', 'simple-lead-gen-form' ) );
		$messages[ $post_type ][1] .= $view_link;
		$messages[ $post_type ][6] .= $view_link;
		$messages[ $post_type ][9] .= $view_link;

		$preview_permalink = add_query_arg( 'preview', 'true', $permalink );
		$preview_link = sprintf( ' <a target="_blank" href="%s">%s</a>', esc_url( $preview_permalink ), __( 'Preview customer', 'simple-lead-gen-form' ) );
		$messages[ $post_type ][8]  .= $preview_link;
		$messages[ $post_type ][10] .= $preview_link;
	}

	return $messages;
}
add_filter( 'post_updated_messages', 'slgf_customer_updated_messages' );

/**
 * Change Enter Title Here text for customer CPT.
 *
 * @since  1.0.0
 *
 * @param  $title
 * @return $title
 */
function slgf_change_enter_title_text( $title ) {
	$screen = get_current_screen();

	if ( 'customer' === $screen->post_type ) {
		$title = 'Enter customer name';
	}

	return $title;
}
add_filter( 'enter_title_here', 'slgf_change_enter_title_text' );

/**
 * Change bulk update messages for customer CPT.
 *
 * @since  1.0.0
 *
 * @param  $bulk_messages
 * @param  $bulk_counts
 * @return $bulk_messages
 */
function slgf_bulk_post_updated_messages_filter( $bulk_messages, $bulk_counts ) {

	$bulk_messages['customer'] = array(
		'updated'   => _n( '%s customer updated.', '%s customers updated.', $bulk_counts['updated'] ),
		'locked'    => _n( '%s customer not updated, somebody is editing it.', '%s customers not updated, somebody is editing them.', $bulk_counts['locked'] ),
		'deleted'   => _n( '%s customer permanently deleted.', '%s customers permanently deleted.', $bulk_counts['deleted'] ),
		'trashed'   => _n( '%s customer moved to the Trash.', '%s customers moved to the Trash.', $bulk_counts['trashed'] ),
		'untrashed' => _n( '%s customer restored from the Trash.', '%s customers restored from the Trash.', $bulk_counts['untrashed'] ),
	);

	return $bulk_messages;
}
add_filter( 'bulk_post_updated_messages', 'slgf_bulk_post_updated_messages_filter', 10, 2 );

/**
 * Register customer taxonomies.
 *
 * @since  1.0.0
 */
function slgf_register_customer_tax() {
	register_taxonomy( 'customer_category', 'customer',
		array(
			'hierarchical' => true,
			'show_ui'      => current_user_can( 'activate_plugins' ),
			'labels'       => array(
				'name'          => __( 'Categories', 'simple-lead-gen-form' ),
				'singular_name' => __( 'Category', 'simple-lead-gen-form' ),
			),
		)
	);

	register_taxonomy( 'customer_tag', 'customer',
		array(
			'hierarchical' => false,
			'show_ui'      => current_user_can( 'activate_plugins' ),
			'labels'       => array(
				'name'          => __( 'Tags', 'simple-lead-gen-form' ),
				'singular_name' => __( 'Tag', 'simple-lead-gen-form' ),
			),
		)
	);
}

/**
 * Filters the columns displayed in the Posts list table for customer CPT.
 *
 * @since  1.0.0
 *
 * @param  $columns
 * @return array
 */
function slgf_customer_cpt_columns( $columns ) {
	unset( $columns['title'] );
	unset( $columns['date'] );

	$new_columns = array(
		'name'    => __( 'Name', 'simple-lead-gen-form' ),
		'time'    => __( 'Time', 'simple-lead-gen-form' ),
		'email'   => __( 'Email', 'simple-lead-gen-form' ),
		'phone'   => __( 'Phone', 'simple-lead-gen-form' ),
		'budget'  => __( 'Budget', 'simple-lead-gen-form' ),
		'message' => __( 'Message', 'simple-lead-gen-form' ),
	);
	return array_merge( $columns, $new_columns );
}
add_filter( 'manage_customer_posts_columns' , 'slgf_customer_cpt_columns' );

/**
 * Output customer meta to column rows.
 *
 * @since  1.0.0
 *
 * @param  $column
 * @param  $post_id
 */
function slgf_custom_columns( $column, $post_id ) {
	global $post;

	switch ( $column ) {
		case 'name':
			echo '<strong><a href="' . esc_html( get_edit_post_link() ) . '">' . esc_html( get_the_title() ) . '</a></strong>';
			break;

		case 'time':
			$time           = get_post_meta( $post->ID, 'time', true );
			$time_formatted = date_i18n( 'H:i - M j, Y', $time );
			echo esc_html( $time_formatted );
			break;

		case 'email':
			echo '<a href="mailto:' . esc_html( get_post_meta( $post->ID, 'email', true ) ) . '">' . esc_html( get_post_meta( $post->ID, 'email', true ) ) . '</a>';
			break;

		case 'phone':
			echo esc_html( get_post_meta( $post->ID, 'phone', true ) );
			break;

		case 'budget':
			echo esc_html( get_post_meta( $post->ID, 'budget', true ) );
			break;

		case 'message':
			echo esc_html( get_post_meta( $post->ID, 'message', true ) );
			break;
	}
}
add_action( 'manage_customer_posts_custom_column' , 'slgf_custom_columns', 10, 2 );
