<?php
// Direct access security
if ( !defined( 'WOOSHEETS_PLUGIN_SECURITY' ) ) {
	die();
}

final class WooSheets_Update_Licenser {

	protected static $_instance = NULL;

	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	function __construct() {
		add_action( 'wp_ajax_woosheets_activate_license', array( $this, 'activate' ) );
		add_action( 'wp_ajax_woosheets_deactivate_license', array( $this, 'deactivate' ) );
	}

	public function init() {

	}

	private function get_ajax_var( $param, $default = NULL ) {
		return isset( $_POST[ $param ] ) ? $_POST[ $param ] : $default;
	}


	public function check_license() {

		$woosheetsapikey = get_option( 'woosheets_envato_apikey' );
		return $woosheetsapikey;
	}

	public function activate() {
		$this->request( 'activation' );
	}

	public function deactivate() {
		$this->request( 'deactivation' );
	}

	public function request( $action = '' ) {
		
		if ( ! isset( $_POST['wpnonce'] ) 	|| ! wp_verify_nonce( $_POST['wpnonce'], 'woosheets-special-string' ) 
		) {
			print 'Sorry, your nonce did not verify.';
		   	exit;
		}
		else{		
			$id 		= WOOSHEETS_PLUGIN_ID;
			$url      	= 'https://api.envato.com/v2/market/catalog/item?id=' . $id;
			$defaults 	= array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $_POST['api_key'],
					'User-Agent'    => 'WordPress - Envato Market 2.0.3',
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
			$response 		= wp_remote_get( esc_url_raw( $url ), $args );
			$result 		= json_decode( $response['body'] );
			$message_wrap 	= '<div class="%s"><p>%s</p></div>';
			
			if ( is_wp_error( $response ) || isset( $result->error ) ) {
				
				$message_wrap = '<div class="%s"><p>%s</p></div>';
				$message = 'Please enter valid Envato API Token';
				$status = 'error';
				
				echo json_encode( array( 'result' => '-2', 'message' => sprintf( $message_wrap, $status, $result->error ) ) );
				die();
			}
			else
			{
				update_option( 'woosheets_envato_apikey', $_POST['api_key'] );	
				echo json_encode( array( 'result' => '4', 'message' => sprintf( $message_wrap, 'updated', __( 'Your OAuth Personal Token has been verified.', 'woocommerce-settings-googlesheet' ) ) ) );
				die();	
			}
		}
	}

}

