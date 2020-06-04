<?php
/**
 * Admin Page
 *
 * @package WP Config File Manager
 * @since 1.0.0
 */

if ( ! class_exists( 'WP_Config_File_Manager' ) ) :

	/**
	 * WP Config File Manager
	 *
	 * @since 1.0.0
	 */
	class WP_Config_File_Manager {

		/**
		 * Instance
		 *
		 * @since 1.0.0
		 *
		 * @access private
		 * @var object Class object.
		 */
		private static $instance;

		/**
		 * Initiator
		 *
		 * @since 1.0.0
		 *
		 * @return object initialized object of class.
		 */
		public static function get_instance() {
			if ( ! isset( self::$instance ) ) {
				self::$instance = new self;
			}
			return self::$instance;
		}

		/**
		 * Constructor
		 *
		 * @since 1.0.0
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'register_admin_menu' ) );
            add_action( 'init', array( $this, 'process_form' ) );
			add_action( 'plugin_action_links_' . WP_CONFIG_FILE_MANAGER_BASE, array( $this, 'action_links' ) );
		}

        /**
         * Create the portfolio from add new portfolio form.
         *
         * @since 1.0.2
         *
         * @return void
         */
        public function process_form() {
            $page = isset( $_GET['page'] ) ? $_GET['page'] : null; // phpcs:ignore WordPress.Security.NonceVerification.Recommended


            if ( 'wp-config-file-manager' !== $page ) {
                return;
            }

            if ( ! isset( $_POST['wp-config-file-manager-save-settings'] ) ) {
                return;
            }

            if ( ! wp_verify_nonce( $_POST['wp-config-file-manager-save-settings'], 'wp-config-file-manager-save-settings-nonce' ) ) {
                return;
            }

            $request_constants = array();
            $all_constants = $this->get_constants();
            $valid_constants = array_keys( $all_constants );

            /**
             * Get all validated constants.
             */
            foreach ($_POST as $key => $value) {
                if( false !== strpos($key, 'wp-config-') ) {
                    $constant = str_replace('wp-config-', '', $key );
                    $request_constants[ $constant ] = sanitize_text_field( $value );
                }
            }

            /**
             * Set values to the validated constants.
             */
            $validated_constants = array();
            foreach ($all_constants as $constant_key => $constant_data) {
                if( 'boolean' === $constant_data['type'] ) {
                    if( array_key_exists($constant_key, $request_constants ) ) {
                        $validated_constants[ $constant_key ] = 'true';
                    } else {
                        $validated_constants[ $constant_key ] = 'false';
                    }
                } else if( 'undefined' !== $request_constants[ $constant_key ] ) {
                    $validated_constants[ $constant_key ] = $request_constants[ $constant_key ]; // Disabled
                }
            }

            /**
             * Save constant values into wp-config.php file.
             */
            $config_transformer = new WPConfigTransformer( $this->get_config_file_path() );
            foreach ($validated_constants as $key => $value) {
                if( 'boolean' === $all_constants[$key]['type'] ) {
                    $config_transformer->update( 'constant', $key, $value, array( 'raw' => true, 'normalize' => true ) );
                } else {
                    $config_transformer->update( 'constant', $key, $value, array( 'normalize' => true ) );
                }
            }
            
            // Redirected to the settings page.
            wp_redirect( admin_url( 'tools.php?page=wp-config-file-manager' ) );
        }

        /**
         * Get the wp-config.php file path
         *
         * @since 1.0.0
         * @return mixed
         */
        function get_config_file_path() {
            $path = false;

            if ( getenv( 'WP_CONFIG_PATH' ) && file_exists( getenv( 'WP_CONFIG_PATH' ) ) ) {
                $path = getenv( 'WP_CONFIG_PATH' );
            } elseif ( file_exists( ABSPATH . 'wp-config.php' ) ) {
                $path = ABSPATH . 'wp-config.php';
            } elseif ( file_exists( dirname( ABSPATH ) . '/wp-config.php' ) && ! file_exists( dirname( ABSPATH ) . '/wp-settings.php' ) ) {
                $path = dirname( ABSPATH ) . '/wp-config.php';
            }

            if ( $path ) {
                $path = realpath( $path );
            }

            return $path;
        }

		/**
		 * Show action links on the plugin screen.
         *
         * @since 1.0.0
		 * @param   mixed $links Plugin Action links.
		 * @return  array
		 */
		function action_links( $links ) {
			$action_links = array(
				'settings' => '<a href="' . admin_url( 'tools.php?page=wp-config-file-manager' ) . '" aria-label="' . esc_attr__( 'Settings', 'wp-config-file-manager' ) . '">' . esc_html__( 'Settings', 'wp-config-file-manager' ) . '</a>',
			);

			return array_merge( $action_links, $links );
		}

		/**
		 * Register menu
		 *
		 * @since 1.0.0
		 * @return void
		 */
		function register_admin_menu() {
			add_submenu_page( 'tools.php', __( 'WP Config Manager', 'wp-config-file-manager' ), __( 'WP Config Manager', 'wp-config-file-manager' ), 'manage_options', 'wp-config-file-manager', array( $this, 'options_page' ) );
		}

        /**
         * Get Constants
         *
         * @since 1.0.0
         * @return array
         */
        function get_constants() {
            $wp_debug_log_value = __( 'Disabled', 'wp-config-file-manager' );

            // Check WP_DEBUG_LOG.
            if ( is_string( WP_DEBUG_LOG ) ) {
                $wp_debug_log_value = WP_DEBUG_LOG;
            } elseif ( WP_DEBUG_LOG ) {
                $wp_debug_log_value = __( 'Enabled', 'wp-config-file-manager' );
            }

            // Check CONCATENATE_SCRIPTS.
            if ( defined( 'CONCATENATE_SCRIPTS' ) ) {
                $concatenate_scripts       = CONCATENATE_SCRIPTS ? __( 'Enabled', 'wp-config-file-manager' ) : __( 'Disabled', 'wp-config-file-manager' );
                $concatenate_scripts_debug = CONCATENATE_SCRIPTS ? 'true' : 'false';
            } else {
                $concatenate_scripts       = __( 'Undefined', 'wp-config-file-manager' );
                $concatenate_scripts_debug = 'undefined';
            }

            // Check COMPRESS_SCRIPTS.
            if ( defined( 'COMPRESS_SCRIPTS' ) ) {
                $compress_scripts       = COMPRESS_SCRIPTS ? __( 'Enabled', 'wp-config-file-manager' ) : __( 'Disabled', 'wp-config-file-manager' );
                $compress_scripts_debug = COMPRESS_SCRIPTS ? 'true' : 'false';
            } else {
                $compress_scripts       = __( 'Undefined', 'wp-config-file-manager' );
                $compress_scripts_debug = 'undefined';
            }

            // Check COMPRESS_CSS.
            if ( defined( 'COMPRESS_CSS' ) ) {
                $compress_css       = COMPRESS_CSS ? __( 'Enabled', 'wp-config-file-manager' ) : __( 'Disabled', 'wp-config-file-manager' );
                $compress_css_debug = COMPRESS_CSS ? 'true' : 'false';
            } else {
                $compress_css       = __( 'Undefined', 'wp-config-file-manager' );
                $compress_css_debug = 'undefined';
            }

            // Check WP_LOCAL_DEV.
            if ( defined( 'WP_LOCAL_DEV' ) ) {
                $wp_local_dev       = WP_LOCAL_DEV ? __( 'Enabled', 'wp-config-file-manager' ) : __( 'Disabled', 'wp-config-file-manager' );
                $wp_local_dev_debug = WP_LOCAL_DEV ? 'true' : 'false';
            } else {
                $wp_local_dev       = __( 'Undefined', 'wp-config-file-manager' );
                $wp_local_dev_debug = 'undefined';
            }

            return array(
                'WP_DEBUG'            => array(
                    'label' => 'WP_DEBUG',
                    'value' => WP_DEBUG ? __( 'Enabled', 'wp-config-file-manager' ) : __( 'Disabled', 'wp-config-file-manager' ),
                    'debug' => WP_DEBUG,
                    'type' => 'boolean',
                ),
                'WP_DEBUG_DISPLAY'    => array(
                    'label' => 'WP_DEBUG_DISPLAY',
                    'value' => WP_DEBUG_DISPLAY ? __( 'Enabled', 'wp-config-file-manager' ) : __( 'Disabled', 'wp-config-file-manager' ),
                    'debug' => WP_DEBUG_DISPLAY,
                    'type' => 'boolean',
                ),
                'WP_DEBUG_LOG'        => array(
                    'label' => 'WP_DEBUG_LOG',
                    'value' => $wp_debug_log_value,
                    'debug' => WP_DEBUG_LOG,
                    'type' => 'boolean',
                ),
                'SCRIPT_DEBUG'        => array(
                    'label' => 'SCRIPT_DEBUG',
                    'value' => SCRIPT_DEBUG ? __( 'Enabled', 'wp-config-file-manager' ) : __( 'Disabled', 'wp-config-file-manager' ),
                    'debug' => SCRIPT_DEBUG,
                    'type' => 'boolean',
                ),
                'WP_CACHE'            => array(
                    'label' => 'WP_CACHE',
                    'value' => WP_CACHE ? __( 'Enabled', 'wp-config-file-manager' ) : __( 'Disabled', 'wp-config-file-manager' ),
                    'debug' => WP_CACHE,
                    'type' => 'boolean',
                ),
                'CONCATENATE_SCRIPTS' => array(
                    'label' => 'CONCATENATE_SCRIPTS',
                    'value' => $concatenate_scripts,
                    'debug' => $concatenate_scripts_debug,
                    'type' => 'boolean',
                ),
                'COMPRESS_SCRIPTS'    => array(
                    'label' => 'COMPRESS_SCRIPTS',
                    'value' => $compress_scripts,
                    'debug' => $compress_scripts_debug,
                    'type' => 'boolean',
                ),
                'COMPRESS_CSS'        => array(
                    'label' => 'COMPRESS_CSS',
                    'value' => $compress_css,
                    'debug' => $compress_css_debug,
                    'type' => 'boolean',
                ),
                'WP_LOCAL_DEV'        => array(
                    'label' => 'WP_LOCAL_DEV',
                    'value' => $wp_local_dev,
                    'debug' => $wp_local_dev_debug,
                    'type' => 'boolean',
                ),
                
                'WP_MAX_MEMORY_LIMIT' => array(
                    'label' => 'WP_MAX_MEMORY_LIMIT',
                    'value' => WP_MAX_MEMORY_LIMIT,
                    'type' => 'string',
                ),
            );
            }

		/**
		 * Option Page
		 *
		 * @since 1.0.0
		 * @return void
		 */
		function options_page() {

			$data = $this->get_constants();
			?>
			<div class="wrap wp-config-file-manager">
				<h1><?php esc_html_e( 'WP Config Manager', 'wp-config-file-manager' ); ?></h1>
				<p class="description"><?php esc_html_e( 'Simply change the values of constents for debugging.', 'wp-config-file-manager' ); ?></p>
				<hr>

				  <div class="wrap">
					<div id="poststuff">
						<div id="post-body" class="columns-2">
							<div id="post-body-content">

								<div id="importer-content">

                                    <form class="wp-config-file-manager-new-template-form" name="wp-config-file-manager-new-template-form" method="POST">

                                        <table class="widefat striped">
                                            <?php foreach ($data as $constant_key => $constant_data) { ?>
                                                <tr>
                                                    <th scope="row"><?php echo esc_html( $constant_key ); ?></th>
                                                    <td>
                                                        <fieldset>
                                                            <legend class="screen-reader-text">
                                                                <span><?php echo esc_html( $constant_key ); ?></span>
                                                            </legend>
                                                            <label for="users_can_register">
                                                                <?php if( 'boolean' === $constant_data['type'] ) {
                                                                    $checked = ( 'Enabled' === $constant_data['value'] ) ? 'checked' : '';
                                                                    ?>
                                                                    <input type="checkbox" value="<?php echo esc_attr( $constant_data['value'] ); ?>" <?php echo $checked; ?> name="wp-config-<?php echo esc_attr( $constant_key ); ?>" />
                                                                <?php } else { ?>
                                                                    <input type="text" value="<?php echo esc_html( $constant_data['value'] ); ?>" class="regular-text code" name="wp-config-<?php echo esc_attr( $constant_key ); ?>" />
                                                                <?php } ?>
                                                            </label>
                                                        </fieldset>
                                                    </td>
                                                </tr>
                                            <?php } ?>

                                        </table>

                                        <p class="submit">
                                            <input type="submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Save Settings', 'wp-config-file-manager' ); ?>">
                                        </p>

                                        <?php wp_nonce_field( 'wp-config-file-manager-save-settings-nonce', 'wp-config-file-manager-save-settings' ); ?>

                                    </form>

								</div>

							</div>
						</div>
					</div>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * Initialize class object with 'get_instance()' method
	 */
	WP_Config_File_Manager::get_instance();

endif;
