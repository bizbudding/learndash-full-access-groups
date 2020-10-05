<?php

/**
 * Plugin Name:     LearnDash Full Access Groups
 * Plugin URI:      https://bizbudding.com
 * Description:     Allow specific Groups to access a course without start date restrictions.
 * Version:         1.2.1
 *
 * Author:          BizBudding, Mike Hemberger
 * Author URI:      https://bizbudding.com
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Main LD_Full_Access_Groups Class.
 *
 * @since 1.0.0
 */
final class LD_Full_Access_Groups {

	/**
	 * @var LD_Full_Access_Groups The one true LD_Full_Access_Groups
	 * @since 1.0.0
	 */
	private static $instance;

	/**
	 * Main LD_Full_Access_Groups Instance.
	 *
	 * Insures that only one instance of LD_Full_Access_Groups exists in memory at any one
	 * time. Also prevents needing to define globals all over the place.
	 *
	 * @since   1.0.0
	 * @static  var array $instance
	 * @uses    LD_Full_Access_Groups::setup_constants() Setup the constants needed.
	 * @uses    LD_Full_Access_Groups::run() Activate, deactivate, etc.
	 * @see     LD_FULL_ACCESS_GROUPS()
	 * @return  object | LD_Full_Access_Groups The one true LD_Full_Access_Groups
	 */
	public static function instance() {
		if ( ! isset( self::$instance ) ) {
			// Setup the setup
			self::$instance = new LD_Full_Access_Groups;
			// Methods
			self::$instance->setup_constants();
			self::$instance->run();
		}
		return self::$instance;
	}

	/**
	 * Throw error on object clone.
	 *
	 * The whole idea of the singleton design pattern is that there is a single
	 * object therefore, we don't want the object to be cloned.
	 *
	 * @since   1.0.0
	 * @access  protected
	 * @return  void
	 */
	public function __clone() {
		// Cloning instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'learndash-full-access-groups' ), '1.0' );
	}

	/**
	 * Disable unserializing of the class.
	 *
	 * @since   1.0.0
	 * @access  protected
	 * @return  void
	 */
	public function __wakeup() {
		// Unserializing instances of the class is forbidden.
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'learndash-full-access-groups' ), '1.0' );
	}

	/**
	 * Setup plugin constants.
	 *
	 * @access  private
	 * @since   1.0.0
	 * @return  void
	 */
	private function setup_constants() {

		// Plugin version.
		if ( ! defined( 'LD_FULL_ACCESS_GROUPS_VERSION' ) ) {
			define( 'LD_FULL_ACCESS_GROUPS_VERSION', '1.2.1' );
		}

		// Plugin Folder Path.
		if ( ! defined( 'LD_FULL_ACCESS_GROUPS_PLUGIN_DIR' ) ) {
			define( 'LD_FULL_ACCESS_GROUPS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
		}

		// Plugin Includes Path
		if ( ! defined( 'LD_FULL_ACCESS_GROUPS_INCLUDES_DIR' ) ) {
			define( 'LD_FULL_ACCESS_GROUPS_INCLUDES_DIR', LD_FULL_ACCESS_GROUPS_PLUGIN_DIR . 'includes/' );
		}

		// Plugin Folder URL.
		if ( ! defined( 'LD_FULL_ACCESS_GROUPS_PLUGIN_URL' ) ) {
			define( 'LD_FULL_ACCESS_GROUPS_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
		}

		// Plugin Root File.
		if ( ! defined( 'LD_FULL_ACCESS_GROUPS_PLUGIN_FILE' ) ) {
			define( 'LD_FULL_ACCESS_GROUPS_PLUGIN_FILE', __FILE__ );
		}

		// Plugin Base Name
		if ( ! defined( 'LD_FULL_ACCESS_GROUPS_BASENAME' ) ) {
			define( 'LD_FULL_ACCESS_GROUPS_BASENAME', dirname( plugin_basename( __FILE__ ) ) );
		}

	}

	/**
	 * Hook all the things.
	 *
	 * @return  void
	 */
	public function run() {
		add_action( 'admin_init',             array( $this, 'updater' ) );
		add_action( 'admin_enqueue_scripts',  array( $this, 'register_scripts' ) );
		add_action( 'add_meta_boxes',         array( $this, 'register_metabox' ) );
		add_action( 'save_post_sfwd-courses', array( $this, 'save_values' ) );
		add_action( 'get_header',             array( $this, 'course_bypass' ) );
	}

	/**
	 * Setup the plugin updater.
	 *
	 * @uses    https://github.com/YahnisElsts/plugin-update-checker/
	 *
	 * @return  void
	 */
	public function updater() {
		if ( ! class_exists( 'Puc_v4_Factory' ) ) {
			require_once LD_FULL_ACCESS_GROUPS_INCLUDES_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php'; // 4.4
		}
		$updater = Puc_v4_Factory::buildUpdateChecker( 'https://github.com/bizbudding/learndash-full-access-groups/', __FILE__, 'learndash-full-access-groups' );
	}

	/**
	 * Register scripts/styles for enqueueing later.
	 *
	 * @return  void
	 */
	public function register_scripts() {
		wp_register_style( 'select2',                LD_FULL_ACCESS_GROUPS_PLUGIN_URL . 'assets/css/select2.min.css', array(), '4.0.5' );
		wp_register_script( 'select2',               LD_FULL_ACCESS_GROUPS_PLUGIN_URL . 'assets/js/select2.min.js', array( 'jquery' ), '4.0.5', true );
		wp_register_script( 'ld_full_access_groups', LD_FULL_ACCESS_GROUPS_PLUGIN_URL . 'assets/js/ld-full-access-groups.js', array( 'select2' ), LD_FULL_ACCESS_GROUPS_VERSION, true );
	}

	/**
	 * Register the metabox.
	 *
	 * @return  void
	 */
	function register_metabox() {

		add_meta_box(
			'ld_full_access_groups_edit',
			esc_html__( 'LearnDash Full Access Groups', 'learndash-full-access-groups' ),
			array( $this, 'render_metabox' ),
			'sfwd-courses',
			'side',
			'default'
		);

	}

	/**
	 * Render the metabox HTML.
	 *
	 * @param   $post  The current post object.
	 *
	 * @return  void
	 */
	function render_metabox( $post ) {

		wp_enqueue_style( 'select2' );
		wp_enqueue_script( 'select2' );
		wp_enqueue_script( 'ld_full_access_groups' );

		// return;

		$groups = get_posts( array(
			'post_type'        => 'groups',
			'posts_per_page'   => 500,
			'post_status'      => 'publish',
			'orderby'          => 'title',
			'order'            => 'ASC',
			'suppress_filters' => true
		) );

		if ( $groups ) {

			// Set nonce.
			wp_nonce_field( 'nonce_ld_full_access_groups_action', 'nonce_ld_full_access_groups_field' );

			// Existing values.
			$selected = (array) get_post_meta( $post->ID, 'ld_full_access_groups', true );

			// Select field.
			echo '<p><select id="ld_full_access_groups" class="ld-full-access-groups widefat" name="ld_full_access_groups[]" multiple="multiple">';
				foreach ( $groups as $group ) {
					printf( '<option value="%s" %s>%s</option>', $group->ID, selected( in_array( $group->ID, $selected ) ), get_the_title( $group->ID ) );
				}
			echo '</select></p>';
			printf( '<em>%s</em>', esc_html__( 'Selected groups will have access to this course without start date restrictions. Course access/enrollment is managed by the group itself. Course prerequisites will not be affected.', 'learndash-full-access-groups' ) );

		}

	}

	/**
	 * Save the selected groups to post meta.
	 *
	 * @param  $post_id  The current post ID.
	 *
	 * @return  void
	 */
	function save_values( $post_id ) {

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

		// Get the groups array.
		$groups = isset( $_POST['ld_full_access_groups'] ) ? (array) $_POST['ld_full_access_groups'] : '';

		// Sanitize.
		$groups = ! empty( $groups ) ? array_map( 'absint', $groups ) : '';

		// Update the post meta.
		update_post_meta( $post_id, 'ld_full_access_groups', $groups );

	}

	/**
	 * Remove the start date restrictions
	 * from courses when in a group selected via the metabox on the course edit page.
	 *
	 * @return  void
	 */
	function course_bypass() {

		// Bail if not a single learndash post.
		if ( ! is_singular( array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-quiz', 'sfwd-topic', 'sfwd-certificates' ) ) ) {
			return;
		}

		// Bail if LD removes this function. Better to remove course access then to blow things up.
		if ( ! function_exists( 'learndash_get_course_id' ) ) {
			return;
		}

		// Determine type of ID is being passed in.  Should be the ID of anything that belongs to a course (Lesson, Topic, Quiz, etc).
		$course_id = learndash_get_course_id( get_the_ID() );

		// Bail if no course ID.
		if ( ! $course_id ) {
			return;
		}
		// Saved groups.
		$groups = get_post_meta( $course_id, 'ld_full_access_groups', true );

		// Bail if not groups to override access.
		if ( ! $groups ) {
			return;
		}

		// Start with no access.
		$access = false;

		// If user is in any group that grants bypass access.
		foreach ( (array) $groups as $group_id ) {
			if ( learndash_is_user_in_group( get_current_user_id(), $group_id ) ) {
				$access = true;
				break;
			}
		}

		// Bail if no access. Let LD do its' thing.
		if ( ! $access ) {
			return;
		}

		// Remove the LD content filter that hides content and shows start date message.
		remove_filter( 'learndash_content', 'lesson_visible_after', 1, 2 );

		// Remove the lesson not available template, mostly for the course list.
		add_filter( 'learndash_template', function( $filepath, $name, $args, $echo, $return_file_path ) {
			if ( 'learndash_course_lesson_not_available' === $name ) {
				return false;
			}
			return $filepath;
		}, 10, 5 );

	}

}

/**
 * The main function for that returns LD_Full_Access_Groups.
 *
 * The main function responsible for returning the one true LD_Full_Access_Groups
 * Instance to functions everywhere.
 *
 * @return object|LD_Full_Access_Groups The one true LD_Full_Access_Groups Instance.
 */
function ld_full_access_groups() {
	return LD_Full_Access_Groups::instance();
}

// Get LD_Full_Access_Groups Running.
ld_full_access_groups();
