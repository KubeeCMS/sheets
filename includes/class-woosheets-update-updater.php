<?php
// Direct access security
if ( !defined( 'WOOSHEETS_PLUGIN_SECURITY' ) ) {
	die();
}

final class WooSheets_Update_Updater {

	protected static $_instance = NULL;

	public function __construct() {
			$this->woosheets_setup();		
			// Deferred Download.
			add_action( 'upgrader_package_options', array( $this, 'woosheets_maybe_deferred_download' ), 9 );

			// Add pre download filter to help with 3rd party plugin integration.
			add_filter( 'upgrader_pre_download', array( $this, 'woosheets_upgrader_pre_download' ), 2, 4 );
	}

	public function woosheets_setup() {
		$instance = new WooSheets_Update_Manager ( WOOSHEETS_PLUGIN_SLUG, $this );
	}

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	public function init() {

	}

	/**
		 * Defers building the API download url until the last responsible moment to limit file requests.
		 *
		 * Filter the package options before running an update.
		 *
		 * @param array $options {
		 *     Options used by the upgrader.
		 *
		 * @type string $package Package for update.
		 * @type string $destination Update location.
		 * @type bool   $clear_destination Clear the destination resource.
		 * @type bool   $clear_working Clear the working resource.
		 * @type bool   $abort_if_destination_exists Abort if the Destination directory exists.
		 * @type bool   $is_multi Whether the upgrader is running multiple times.
		 * @type array  $hook_extra Extra hook arguments.
		 * }
		 * @since 1.0.0
		 */
		public function woosheets_maybe_deferred_download( $options ) {
			$package = $options['package'];
			if ( false !== strrpos( $package, 'deferred_download' ) && false !== strrpos( $package, 'item_id' ) ) {
				parse_str( parse_url( $package, PHP_URL_QUERY ), $vars );
				if ( $vars['item_id'] ) {
					$args               = $this->woosheets_set_bearer_args( $vars['item_id'] );
					$options['package'] = $this->woosheets_download( $vars['item_id'], $args );
				}
			}
			return $options;
		}

		/**
		 * We want to stop certain popular 3rd party scripts from blocking the update process by
		 * adjusting the plugin name slightly so the 3rd party plugin checks stop.
		 *
		 * Currently works for: Visual Composer.
		 *
		 * @param string $reply Package URL.
		 * @param string $package Package URL.
		 * @param object $updater Updater Object.
		 *
		 * @return string $reply    New Package URL.
		 * @since 2.0.0
		 */
		public function woosheets_upgrader_pre_download( $reply, $package, $updater ) {
			if ( strpos( $package, 'marketplace.envato.com/short-dl' ) !== false ) {
				if ( isset( $updater->skin->plugin_info ) && ! empty( $updater->skin->plugin_info['Name'] ) ) {
					$updater->skin->plugin_info['Name'] = $updater->skin->plugin_info['Name'] . '.';
				} else {
					$updater->skin->plugin_info = array(
						'Name' => 'Name',
					);
				}
			}
			return $reply;
		}
		/**
		 * Get the item download.
		 *
		 * @since 1.0.0
		 *
		 * @param  int   $id The item ID.
		 * @param  array $args The arguments passed to `wp_remote_get`.
		 * @return bool|array The HTTP response.
		 */
		public function woosheets_download( $id, $args = array() ) {
			if ( empty( $id ) ) {
				return false;
			}

			$url      = 'https://api.envato.com/v2/market/buyer/download?item_id=' . $id . '&shorten_url=true';
			$response = WooSheets_Update_Manager::woosheets_request( $url, $args );

			// @todo Find out which errors could be returned & handle them in the UI.
			if ( is_wp_error( $response ) || empty( $response ) || ! empty( $response['error'] ) ) {
				return false;
			}

			if ( ! empty( $response['wordpress_plugin'] ) ) {
				return $response['wordpress_plugin'];
			}
			return false;
		}
		/**
		 * Returns the bearer arguments for a request with a single use API Token.
		 *
		 * @param int $id The item ID.
		 *
		 * @return array
		 * @since 1.0.0
		 */
		public function woosheets_set_bearer_args( $id ) {
			$token = get_option( 'woosheets_envato_apikey' );
			
			if ( ! empty( $token ) ) {
				$args = array(
					'headers' => array(
						'Authorization' => 'Bearer ' . $token,
					),
				);
			}
			return $args;
		}
}


final class WooSheets_Update_Manager {

	public $plugin_slug;
	public $slug;
	/**
	 * WordPress plugins.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @var array
	 */
	private static $wp_plugins = array();
	/**
	 * Premium plugins.
	 *
	 * @since 1.0.0
	 * @access private
	 *
	 * @var array
	 */
	private static $plugins = array();
		
	function __construct( $plugin_slug, $instance ) {

		$this->plugin_envato_id = WOOSHEETS_PLUGIN_ID;
		$this->plugin_slug = $plugin_slug;
		$this->slug = explode( '/', $plugin_slug );
		$this->slug = str_replace( '.php', '', $this->slug[1] );
		
		
		// Inject plugin updates into the response array.
		add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'woosheets_update_plugins' ), 5, 1 );
		add_filter( 'pre_set_transient_update_plugins', array( $this, 'woosheets_update_plugins' ), 5, 1 );
		
		// Inject plugin information into the API calls.
		add_filter( 'plugins_api', array( $this, 'woosheets_plugins_api' ), 10, 3 );

	}	

	public function woosheets_update_plugins( $transient ) {
		
		self::woosheets_set_plugins( true );
			
		// Process premium plugin updates.
		$installed = array_merge( self::$plugins['active'], self::$plugins['installed'] );
		$plugins   = self::woosheets_wp_plugins();
		foreach ( $installed as $plugin => $premium ) {
			if ( isset( $plugins[ $plugin ] ) && version_compare( $plugins[ $plugin ]['Version'], $premium['version'], '<' ) ) {
				$_plugin                        = array(
					'slug'        => dirname( $plugin ),
					'plugin'      => $plugin,
					'new_version' => $premium['version'],
					'url'         => $premium['url'],
					'package'     => $this->woosheets_deferred_download( $premium['id'] ),
				);
				$transient->response[ $plugin ] = (object) $_plugin;
			}
		}

		return $transient;
	}
	
	/**
	 * Inject API data for premium plugins.
	 *
	 * @since 1.0.0
	 *
	 * @param bool   $response Always false.
	 * @param string $action The API action being performed.
	 * @param object $args Plugin arguments.
	 * @return bool|object $response The plugin info or false.
	 */
	public function woosheets_plugins_api( $response, $action, $args ) {
		self::woosheets_set_plugins( true );
		
		// Process premium theme updates.
		if ( 'plugin_information' === $action && isset( $args->slug ) ) {
			$installed = array_merge( self::$plugins['active'], self::$plugins['installed'] );
			foreach ( $installed as $slug => $plugin ) {
				
				if ( dirname( $slug ) === $args->slug ) {
					$response                 = new stdClass();
					$response->slug           = $args->slug;
					$response->plugin         = $slug;
					$response->plugin_name    = $plugin['name'];
					$response->name           = $plugin['name'];
					$response->version        = $plugin['version'];
					$response->author         = $plugin['author'];
					$response->homepage       = $plugin['url'];
					$response->requires       = $plugin['requires'];
					$response->tested         = $plugin['tested'];
					$response->downloaded     = $plugin['number_of_sales'];
					$response->last_updated   = $plugin['updated_at'];
					$response->sections       = array( 'description' => $plugin['description']);
					$response->banners['low'] = $plugin['landscape_url'];
					$response->rating         = ! empty( $plugin['rating'] ) && ! empty( $plugin['rating']['rating'] ) && $plugin['rating']['rating'] > 0 ? $plugin['rating']['rating'] / 5 * 100 : 0;
					$response->num_ratings    = ! empty( $plugin['rating'] ) && ! empty( $plugin['rating']['count'] ) ? $plugin['rating']['count'] : 0;
					$response->download_link  = $this->woosheets_deferred_download( $plugin['id'] );
					break;
				}
			}
		}
		return $response;
	}
	
	public function woosheets_set_plugins( $forced = false, $use_cache = false, $args = array() ) {
				
		self::$plugins = get_site_transient( self::woosheets_sanitize_key( 'envato-market' ) . '_plugins' );
		
		if ( false === self::$plugins || true === $forced ) {
			$plugins = self::woosheets_plugins();
			
			self::woosheets_process_plugins( $plugins, $args );
		} elseif ( true === $use_cache ) {
			self::woosheets_process_plugins( self::$plugins['purchased'], $args );
		}
	}
	
	public function woosheets_wp_plugins( $flush = false ) {
		if ( empty( self::$wp_plugins ) || true === $flush ) {
			wp_cache_flush();
			self::$wp_plugins = get_plugins();
		}
		return self::$wp_plugins;
	}
	
	public function woosheets_plugins( $args = array() ) {
		$plugins = array();

		$url      = 'https://api.envato.com/v2/market/buyer/list-purchases?filter_by=wordpress-plugins';
		$response = self::woosheets_request( $url, $args );

		if ( is_wp_error( $response ) || empty( $response ) || empty( $response['results'] ) ) {
			return $plugins;
		}
		
		foreach ( $response['results'] as $plugin ) {
			if( $plugin['item']['id'] == WOOSHEETS_PLUGIN_ID )
				$plugins[] = self::woosheets_normalize_plugin( $plugin['item'] );
		}
		return $plugins;
	}
	
	/**
	 * Normalize a plugin.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $plugin An array of API request values.
	 * @return array A normalized array of values.
	 */
	public function woosheets_normalize_plugin( $plugin ) {
		$requires = null;
		$tested   = null;
		$versions = array();

		// Set the required and tested WordPress version numbers.
		foreach ( $plugin['attributes'] as $k => $v ) {
			if ( ! empty( $v['name'] ) && 'compatible-software' === $v['name'] && ! empty( $v['value'] ) && is_array( $v['value'] ) ) {
				foreach ( $v['value'] as $version ) {
					$versions[] = str_replace( 'WordPress ', '', trim( $version ) );
				}
				if ( ! empty( $versions ) ) {
					$requires = $versions[ count( $versions ) - 1 ];
					$tested   = $versions[0];
				}
				break;
			}
		}

		$plugin_normalized = array(
			'id'              => $plugin['id'],
			'name'            => ( ! empty( $plugin['wordpress_plugin_metadata']['plugin_name'] ) ? $plugin['wordpress_plugin_metadata']['plugin_name'] : '' ),
			'author'          => ( ! empty( $plugin['wordpress_plugin_metadata']['author'] ) ? $plugin['wordpress_plugin_metadata']['author'] : '' ),
			'version'         => ( ! empty( $plugin['wordpress_plugin_metadata']['version'] ) ? $plugin['wordpress_plugin_metadata']['version'] : '' ),
			'description'     => self::woosheets_remove_non_unicode( strip_tags( $plugin['wordpress_plugin_metadata']['description'] ) ),
			'url'             => ( ! empty( $plugin['url'] ) ? $plugin['url'] : '' ),
			'author_url'      => ( ! empty( $plugin['author_url'] ) ? $plugin['author_url'] : '' ),
			'thumbnail_url'   => ( ! empty( $plugin['thumbnail_url'] ) ? $plugin['thumbnail_url'] : '' ),
			'landscape_url'   => ( ! empty( $plugin['previews']['landscape_preview']['landscape_url'] ) ? $plugin['previews']['landscape_preview']['landscape_url'] : '' ),
			'requires'        => $requires,
			'tested'          => $tested,
			'number_of_sales' => ( ! empty( $plugin['number_of_sales'] ) ? $plugin['number_of_sales'] : '' ),
			'updated_at'      => ( ! empty( $plugin['updated_at'] ) ? $plugin['updated_at'] : '' ),
			'rating'          => ( ! empty( $plugin['rating'] ) ? $plugin['rating'] : '' ),
		);

		// No main thumbnail in API response, so we grab it from the preview array.
		if ( empty( $plugin_normalized['landscape_url'] ) && ! empty( $plugin['previews'] ) && is_array( $plugin['previews'] ) ) {
			foreach ( $plugin['previews'] as $possible_preview ) {
				if ( ! empty( $possible_preview['landscape_url'] ) ) {
					$plugin_normalized['landscape_url'] = $possible_preview['landscape_url'];
					break;
				}
			}
		}
		if ( empty( $plugin_normalized['thumbnail_url'] ) && ! empty( $plugin['previews'] ) && is_array( $plugin['previews'] ) ) {
			foreach ( $plugin['previews'] as $possible_preview ) {
				if ( ! empty( $possible_preview['icon_url'] ) ) {
					$plugin_normalized['thumbnail_url'] = $possible_preview['icon_url'];
					break;
				}
			}
		}

		return $plugin_normalized;
	}
	
	/**
	 * Query the Envato API.
	 *
	 * @uses wp_remote_get() To perform an HTTP request.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url API request URL, including the request method, parameters, & file type.
	 * @param  array  $args The arguments passed to `wp_remote_get`.
	 * @return array|WP_Error  The HTTP response.
	 */
	public function woosheets_request( $url, $args = array() ) {
		$api_key = get_option( 'woosheets_envato_apikey' );
		$defaults = array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $api_key,
				'User-Agent'    => 'WordPress - Envato Market 2.0.3',// . envato_market()->get_version(),
			),
			'timeout' => 14,
		);
		$args     = wp_parse_args( $args, $defaults );

		$token = trim( str_replace( 'Bearer', '', $args['headers']['Authorization'] ) );
		if ( empty( $token ) ) {
			return new WP_Error( 'api_token_error', __( 'An API token is required.', 'envato-market' ) );
		}

		$debugging_information = [
			'request_url' => $url,
		];

		// Make an API request.
		$response = wp_remote_get( esc_url_raw( $url ), $args );

		// Check the response code.
		$response_code    = wp_remote_retrieve_response_code( $response );
		$response_message = wp_remote_retrieve_response_message( $response );

		$debugging_information['response_code']   = $response_code;
		$debugging_information['response_cf_ray'] = wp_remote_retrieve_header( $response, 'cf-ray' );
		$debugging_information['response_server'] = wp_remote_retrieve_header( $response, 'server' );

		if ( ! empty( $response->errors ) && isset( $response->errors['http_request_failed'] ) ) {
			// API connectivity issue, inject notice into transient with more details.
			return new WP_Error( 'http_error', esc_html( current( $response->errors['http_request_failed'] ) ), $debugging_information );
		}

		if ( 200 !== $response_code && ! empty( $response_message ) ) {
			return new WP_Error( $response_code, $response_message, $debugging_information );
		} elseif ( 200 !== $response_code ) {
			return new WP_Error( $response_code, __( 'An unknown API error occurred.', 'envato-market' ), $debugging_information );
		} else {
			$return = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( null === $return ) {
				return new WP_Error( 'api_error', __( 'An unknown API error occurred.', 'envato-market' ), $debugging_information );
			}
			return $return;
		}
	}
	
	
	/**
	 * Process the plugins and save the transient.
	 *
	 * @since 1.0.0
	 *
	 * @param array $purchased The purchased plugins array.
	 * @param array $args Used to remove or add a plugin during activate and deactivate routines.
	 */
	private function woosheets_process_plugins( $purchased, $args = array() ) {
		
		if ( is_wp_error( $purchased ) ) {
			$purchased = array();
		}

		$active    = array();
		$installed = array();
		$install   = $purchased;

		if ( ! empty( $purchased ) ) {
			foreach ( self::woosheets_wp_plugins( true ) as $slug => $plugin ) {
				foreach ( $install as $key => $value ) {
					if ( $this->woosheets_normalize( $value['name'] ) === $this->woosheets_normalize( $plugin['Name'] ) && $this->woosheets_normalize( $value['author'] ) === $this->woosheets_normalize( $plugin['Author'] ) && file_exists( WP_PLUGIN_DIR . '/' . $slug ) ) {
						$installed[ $slug ] = $value;
						unset( $install[ $key ] );
					}
				}
			}
		}

		foreach ( $installed as $slug => $plugin ) {
			$condition = false;
			if ( ! empty( $args ) && $slug === $args['plugin'] ) {
				if ( true === $args['remove'] ) {
					continue;
				}
				$condition = true;
			}
			if ( $condition || is_plugin_active( $slug ) ) {
				$active[ $slug ] = $plugin;
				unset( $installed[ $slug ] );
			}
		}

		self::$plugins['purchased'] = array_unique( $purchased, SORT_REGULAR );
		self::$plugins['active']    = array_unique( $active, SORT_REGULAR );
		self::$plugins['installed'] = array_unique( $installed, SORT_REGULAR );
		self::$plugins['install']   = array_unique( array_values( $install ), SORT_REGULAR );
		set_site_transient( 'envato_market_plugins', self::$plugins, HOUR_IN_SECONDS );
	}

	public function woosheets_normalize( $string ) {
		return strtolower( html_entity_decode( wp_strip_all_tags( $string ) ) );
	}
		
	/**
	 * Deferred item download URL.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id The item ID.
	 * @return string.
	 */
	public function woosheets_deferred_download( $id ) {
		if ( empty( $id ) ) {
			return '';
		}

		$args = array(
			'deferred_download' => true,
			'item_id'           => $id,
		);
		
		return add_query_arg( $args, esc_url( $this->woosheets_get_page_url() ) );
	}
	
	/**
	 * Return the plugin page URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function woosheets_get_page_url() {
		return admin_url( 'admin.php?page=' . $this->slug );
	}
	
	private function woosheets_sanitize_key( $key ) {
		return preg_replace( '/[^A-Za-z0-9\_]/i', '', str_replace( array( '-', ':' ), '_', $key ) );
	}
	
	static private function woosheets_remove_non_unicode( $retval ) {
		return preg_replace( '/[\x00-\x1F\x80-\xFF]/', '', $retval );
	}
	
}

