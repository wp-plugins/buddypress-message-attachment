<?php
// Exit if accessed directly
if ( !defined('ABSPATH') )
	exit;

if ( !class_exists('BP_Msgat_Admin') ):

	class BP_Msgat_Admin {

		/**
		 * Plugin options
		 *
		 * @var array
		 */
		public $options = array();
		private $network_activated = false,
			$plugin_slug = 'bp-msgat',
			$menu_hook = 'admin_menu',
			$settings_page = 'options-general.php',
			$capability = 'manage_options',
			$form_action = 'options.php',
			$plugin_settings_url;

		/**
		 * Empty constructor function to ensure a single instance
		 */
		public function __construct() {
			// ... leave empty, see Singleton below
		}

		public static function instance() {
			static $instance = null;

			if ( null === $instance ) {
				$instance = new BP_Msgat_Admin;
				$instance->setup();
			}

			return $instance;
		}

		public function option( $key ) {
			$value = bp_message_attachment()->option($key);
			return $value;
		}

		public function setup() {
			if ( (!is_admin() && !is_network_admin() ) || !current_user_can('manage_options') ) {
				return;
			}

			$this->plugin_settings_url = admin_url('options-general.php?page=' . $this->plugin_slug);

			$this->network_activated = $this->is_network_activated();

			//if the plugin is activated network wide in multisite, we need to override few variables
			if ( $this->network_activated ) {
				// Main settings page - menu hook
				$this->menu_hook = 'network_admin_menu';

				// Main settings page - parent page
				$this->settings_page = 'settings.php';

				// Main settings page - Capability
				$this->capability = 'manage_network_options';

				// Settins page - form's action attribute
				$this->form_action = 'edit.php?action=' . $this->plugin_slug;

				// Plugin settings page url
				$this->plugin_settings_url = network_admin_url('settings.php?page=' . $this->plugin_slug);
			}

			//if the plugin is activated network wide in multisite, we need to process settings form submit ourselves
			if ( $this->network_activated ) {
				add_action('network_admin_edit_' . $this->plugin_slug, array( $this, 'save_network_settings_page' ));
			}

			add_action('admin_init', array( $this, 'admin_init' ));
			add_action($this->menu_hook, array( $this, 'admin_menu' ));

			add_filter('plugin_action_links', array( $this, 'add_action_links' ), 10, 2);
			add_filter('network_admin_plugin_action_links', array( $this, 'add_action_links' ), 10, 2);
		}

		/**
		 * Check if the plugin is activated network wide(in multisite).
		 * 
		 * @return boolean
		 */
		private function is_network_activated() {
			$network_activated = false;
			if ( is_multisite() ) {
				if ( !function_exists('is_plugin_active_for_network') )
					require_once( ABSPATH . '/wp-admin/includes/plugin.php' );

				if ( is_plugin_active_for_network('buddypress-message-attachment/loader.php') ) {
					$network_activated = true;
				}
			}
			return $network_activated;
		}

		public function admin_menu() {
			add_submenu_page(
				$this->settings_page, __('BP Message Attachments', 'bp-msgat'), __('Message Attachments', 'bp-msgat'), $this->capability, $this->plugin_slug, array( $this, 'options_page' )
			);
		}

		public function options_page() {
			?>
			<div class="wrap">
				<h2><?php _e('BP Message Attachments', 'bp-msgat'); ?></h2>
				<form method="post" action="<?php echo $this->form_action; ?>">

					<?php
					if ( $this->network_activated && isset($_GET['updated']) ) {
						echo "<div class='updated'><p>" . __('Settings updated.', 'bp-msgat') . "</p></div>";
					}
					?>

					<?php settings_fields('bp_msgat_plugin_options'); ?>
					<?php do_settings_sections(__FILE__); ?>

					<p class="submit">
						<input name="bp_msgat_submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>" />
					</p>
				</form>
			</div>
			<?php
		}

		public function admin_init() {
			register_setting('bp_msgat_plugin_options', 'bp_msgat_plugin_options', array( $this, 'plugin_options_validate' ));

			add_settings_section('general_section', __('Attachment Settings', 'bp-msgat'), array( $this, 'section_general' ), __FILE__);
			add_settings_field('file-types', __('File Types', 'bp-msgat'), array( $this, 'setting_file_types' ), __FILE__, 'general_section');
			add_settings_field('max-size', __('Maximum Size', 'bp-msgat'), array( $this, 'setting_max_size' ), __FILE__, 'general_section');

			add_settings_section('misc_section', __('Miscellaneous', 'bp-msgat'), array( $this, 'section_misc' ), __FILE__);
			add_settings_field('load-css', __('Load CSS', 'bp-msgat'), array( $this, 'setting_load_css' ), __FILE__, 'misc_section');
		}

		public function section_general() {
			//nothing..
		}

		public function section_misc() {
			//nothing..
		}

		private function file_upload_max_size() {
			// Start with post_max_size.
			$max_size_calculated = $post_max_size = $this->return_bytes(ini_get('post_max_size'));

			// If upload_max_size is less, then reduce. Except if upload_max_size is
			// zero, which indicates no limit.
			$upload_max = $this->return_bytes(ini_get('upload_max_filesize'));
			if ( $upload_max > 0 && $upload_max < $post_max_size ) {
				$max_size_calculated = $upload_max;
			}

			return array(
				'post_max_size' => $post_max_size,
				'upload_max_filesize' => $upload_max,
				'max_size_calculated' => $max_size_calculated,
			);
		}

		private function return_bytes( $val ) {
			$val = trim($val);
			$last = strtolower($val[strlen($val) - 1]);
			switch ( $last ) {
				// The 'G' modifier is available since PHP 5.1.0
				case 'g':
					$val *= 1024;
				case 'm':
					$val *= 1024;
				case 'k':
					$val *= 1024;
			}

			return $val;
		}

		public function plugin_options_validate( $input ) {
			$input['max-size'] = ( float ) $input['max-size'] ? ( float ) $input['max-size'] : 2;

			/* check for maximum post size and upload size restriction */
			$info = $this->file_upload_max_size();
			if ( $info['max_size_calculated'] < ( $input['max-size'] * 1024 * 1024 ) ) {
				$input['max-size'] = $info['max_size_calculated'] / (1024 * 1024);
				$input['max-size'] = number_format($input['max-size'], 2);
			}

			if ( !isset($input['load-css']) || !$input['load-css'] ) {
				$input['load-css'] = 'no';
			}
			return $input; //no validations for now
		}

		public function setting_file_types() {
			$selected_extensions = $this->option('file-types');

			$all_file_types = bp_message_attachment()->all_file_types();

			foreach ( $all_file_types as $group_key => $group ) {
				echo "<p><strong>{$group['label']}</strong></p>";

				$extensions = array_unique($group['extensions']);
				//sort alphabatically
				asort($extensions);

				foreach ( $extensions as $extension ) {
					$checked = in_array($extension, $selected_extensions) ? ' checked' : '';
					echo '<label><input type="checkbox" name="bp_msgat_plugin_options[file-types][]" value="' . $extension . '" ' . $checked . '>' . $extension . '</label>&nbsp;&nbsp;&nbsp;&nbsp;';
				}
				echo "<br><br>";
			}
		}

		public function setting_max_size() {
			$max_size = $this->option('max-size');
			echo "<input name='bp_msgat_plugin_options[max-size]' type='text' min='1' value='" . esc_attr($max_size) . "' />MB";
			echo '<p class="description">' . __('Maximum size(in MB) allowed per file.', 'bp-msgat') . '</p>';

			echo "<p class='notice notice-info'>";

			$info = $this->file_upload_max_size();
			$max_size_possible = $info['max_size_calculated'] / (1024 * 1024);
			$max_size_possible = number_format($max_size_possible, 2);

			printf(__('Based on your php configuration, maximum file size can not exceed %s MB.', 'bp-msgat'), $max_size_possible);
			echo "</p>";
		}

		public function setting_load_css() {
			$load_css = $this->option('load-css');
			$checked = $load_css == 'yes' ? ' checked' : '';
			echo "<input name='bp_msgat_plugin_options[load-css]' type='checkbox' value='yes' {$checked} />" . __('Yes', 'bp-msgat');
			echo '<p class="description">' . __('Whether to load plugin\'s css file or not. If you have overriden plugins\' css rules in your theme, you can uncheck this.', 'bp-msgat') . '</p>';
		}

		public function save_network_settings_page() {
			if ( !check_admin_referer('bp_msgat_plugin_options-options') )
				return;

			if ( !current_user_can($this->capability) )
				die('Access denied!');

			if ( isset($_POST['bp_msgat_submit']) ) {
				$submitted = stripslashes_deep($_POST['bp_msgat_plugin_options']);
				$submitted = $this->plugin_options_validate($submitted);

				update_site_option('bp_msgat_plugin_options', $submitted);
			}

			// Where are we redirecting to?
			$base_url = trailingslashit(network_admin_url()) . 'settings.php';
			$redirect_url = add_query_arg(array( 'page' => $this->plugin_slug, 'updated' => 'true' ), $base_url);

			// Redirect
			wp_redirect($redirect_url);
			die();
		}

		public function add_action_links( $links, $file ) {
			// Return normal links if not this plugin
			if ( plugin_basename(basename(constant('BPMSGAT_PLUGIN_DIR')) . '/loader.php') != $file ) {
				return $links;
			}

			$mylinks = array(
				'<a href="' . esc_url($this->plugin_settings_url) . '">' . __("Settings", "bp-msgat") . '</a>',
			);
			return array_merge($links, $mylinks);
		}

	}

	

	// End class BP_Msgat_Admin

endif;