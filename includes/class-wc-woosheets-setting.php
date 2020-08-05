<?php

if ( ! function_exists('composerRequired7e59b00b8fddc8385312f117ab4f39c') )
	require_once( dirname(__FILE__) . '/lib/vendor/autoload.php');
$woosheets_default_status = array('pending','processing','on-hold','completed','cancelled','refunded','failed');
$woosheets_default_status_slug = array( 'wc-pending', 'wc-processing', 'wc-on-hold', 'wc-completed', 'wc-cancelled', 'wc-refunded', 'wc-failed' );
$woosheets_global_headers = array();
class Wc_WooSheets_Setting {
	/**
     * Initialization 
     *
     */
    public static function init() {
			add_action( 'admin_menu', __CLASS__ . '::wpdocs_register_my_woosheets_menu_page' );
			add_action( 'woocommerce_update_options_google_sheet_settings', __CLASS__ . '::update_settings' );
			add_action( 'woocommerce_order_status_pending', __CLASS__ . '::wcgs_pending');
			add_action( 'woocommerce_order_status_failed', __CLASS__ . '::wcgs_failed');
			add_action( 'woocommerce_order_status_on-hold', __CLASS__ . '::wcgs_hold');
			add_action( 'woocommerce_order_status_processing',__CLASS__ . '::wcgs_processing');
			add_action( 'woocommerce_order_status_completed', __CLASS__ . '::wcgs_completed');
			add_action( 'woocommerce_order_status_refunded', __CLASS__ . '::wcgs_refunded');
			add_action( 'woocommerce_order_status_cancelled', __CLASS__ . '::wcgs_cancelled');
			add_action( 'wp_trash_post', __CLASS__ . '::wcgs_trash');
			add_action( 'transition_post_status',  __CLASS__ . '::wcgs_restore', 10, 3);
			add_action( 'woocommerce_order_status_changed', __CLASS__ . '::woo_order_status_change_custom', 10, 3);
			add_action( 'woocommerce_admin_field_set_headers',  __CLASS__ . '::woocommerce_admin_field_set_headers');
			add_action( 'woocommerce_admin_field_product_headers',  __CLASS__ . '::woocommerce_admin_field_product_headers');
			add_action( 'woocommerce_admin_field_product_as_sheet_header',  __CLASS__ . '::woocommerce_admin_field_product_as_sheet_header');
			add_action( 'woocommerce_admin_field_product_headers_append_after',  __CLASS__ . '::woocommerce_admin_field_product_headers_append_after');
			add_action( 'woocommerce_admin_field_manage_row_field',  __CLASS__ . '::woocommerce_admin_field_manage_row_field');			
			add_action( 'woocommerce_admin_field_sync_button',  __CLASS__ . '::woocommerce_admin_field_sync_button');
			add_action( 'woocommerce_admin_field_custom_headers_action',  __CLASS__ . '::woocommerce_admin_field_custom_headers_action');
			add_action( 'woocommerce_admin_field_repeat_checkbox',  __CLASS__ . '::woocommerce_admin_field_repeat_checkbox');
			add_action( 'woocommerce_admin_field_new_spreadsheetname',  __CLASS__ . '::woocommerce_admin_field_new_spreadsheetname');
			add_action( 'admin_enqueue_scripts',  __CLASS__ . '::load_custom_wp_admin_style',30 );	
			add_action( 'wp_ajax_sync_all_orders', __CLASS__ . '::sync_all_orders');
			add_action( 'wp_ajax_clear_all_sheet', __CLASS__ . '::clear_all_sheet');
			add_action( 'wp_ajax_check_existing_sheet', __CLASS__ . '::check_existing_sheet');
			add_action( 'wp_ajax_woosheets_export_order', __CLASS__ . '::woosheets_export_order');
			add_action( 'woocommerce_process_shop_order_meta',  __CLASS__ . '::wc_woocommerce_process_post_meta' , 60, 2);
			add_filter( 'plugin_row_meta', __CLASS__ . '::plugin_row_meta' , 10, 2 );
			// Add item data to the cart.
			add_filter( 'woocommerce_add_cart_item_data', __CLASS__ . '::woosheets_add_cart_item_data' , 10, 2 );
			// Add meta to order.
			add_action( 'woocommerce_checkout_create_order_line_item',  __CLASS__ . '::woosheets_order_line_item' , 10, 3 );
			// Load cart data per page load.
			add_filter( 'woocommerce_get_cart_item_from_session',  __CLASS__ . '::woosheets_get_cart_item_from_session' , 20, 2 );
			
			add_action( 'plugins_loaded', __CLASS__ . '::load_textdomain' , 10 );
   		}
		
		/**
		 * Register a custom menu page.
		 */
		public static function wpdocs_register_my_woosheets_menu_page() {
			$woosheets_woosheets_page = add_menu_page(
				__( 'WooSheets', 'woosheets' ),
				'WooSheets',
				'manage_options',
				'woosheets',
				'',
				'dashicons-admin-generic',
				85
			);

			add_submenu_page( 'WooSheets', 'Google Sheets API Settings', 'Google Sheets API Settings', 'manage_options', 'woosheets',  __CLASS__ . '::woosheets_plugin_page');
		}
		
	/**
	 * Loads the plugin language files.
	 * Load only the woosheets translation.
	 */
	public static function load_textdomain() {
		load_plugin_textdomain( 'woosheets', false, WOOSHEETS_DIRECTORY . '/i18n/languages/' );
	}
		
	public static function load_custom_wp_admin_style() {
		if( isset($_GET["page"]) && $_GET["page"] == "woosheets")
    	{
			
			
			wp_register_script( 'woosheets_wp_admin_ui_js', plugin_dir_url( dirname(__FILE__) ) . 'js/jquery-ui.js',array(), WOOSHEETS_VERSION, true);
			wp_enqueue_script('woosheets_wp_admin_ui_js');
			wp_register_script( 'woosheets_wp_admin_js', plugin_dir_url( dirname(__FILE__) ) . 'js/admin-script.js',array(), WOOSHEETS_VERSION, true);
			wp_localize_script( 'woosheets_wp_admin_js', 'admin_ajax_object',
				array( 
					'ajaxurl' => admin_url( 'admin-ajax.php' )
				)
			);
			wp_enqueue_script('woosheets_wp_admin_js');
			wp_register_style( 'woosheets_wp_admin_css', plugin_dir_url(dirname(__FILE__) ) . 'css/admin-style.css', false, WOOSHEETS_VERSION );
			wp_enqueue_style( 'woosheets_wp_admin_css' );
			wp_register_style( 'woosheets_wp_admin_ui_css', plugin_dir_url(dirname(__FILE__) ) . 'css/jquery-ui.css', false, WOOSHEETS_VERSION );
			wp_enqueue_style( 'woosheets_wp_admin_ui_css' );
		}
	}
	
	/**
    * Show row meta on the plugin screen.
    *
    * @param mixed $links Plugin Row Meta
    * @param mixed $file  Plugin Base file
    * @return  array
    */
    public static function plugin_row_meta( $woosheets_links, $woosheets_file ) {
		
        if ( $woosheets_file == 'woosheets/woosheets.php' ) {
          $woosheets_row_meta = array(
            'docs'    => '<a href="' . esc_url( 'http://www.woosheets.creativewerkdesigns.com/Documentation/index.html' ) . '" title="' . esc_attr( __( 'View Documentation', 'woosheets' ) ) . '" target="_blank">' . __( 'Documentation', 'woosheets' ) . '</a>',
            'videotutorials'    => '<a href="' . esc_url( 'http://woosheets.creativewerkdesigns.com/Documentation/video-tutorials.html' ) . '" title="' . esc_attr( __( 'View Video Tutorials', 'woosheets' ) ) . '" target="_blank">' . __( 'Video Tutorials', 'woosheets' ) . '</a>'
          );
          return array_merge( $woosheets_links, $woosheets_row_meta );
        }
        return (array) $woosheets_links;
    }

		public static function woosheets_plugin_page() {
			
			
			$woosheets_apisettings = $woosheets_generalsettings = $woosheets_emsettings = $woosheets_supportsettings = '';
			// General Settings Tab
			if(isset($_GET['tab']) && $_GET['tab'] == 'general-settings' ){
				
				if( isset($_POST['submit']) )
					self::update_settings();
			}else{
				// Google API Settings Tab
				if(isset($_POST['submit'])){
					if(isset($_POST['client_token'])){
						$woosheets_clienttoken = $_POST['client_token'];
					}else{
						$woosheets_clienttoken = '';	
					}
					$woosheets_google_settings = array($_POST['client_id'],$_POST['client_secret'],$woosheets_clienttoken );
					update_option('google_settings', $woosheets_google_settings);
				}
				if(isset($_POST['revoke'])){
					$woosheets_google_settings 	= get_option('google_settings');
					$woosheets_google_settings[2] = '';
					update_option('google_settings', $woosheets_google_settings);
					update_option( 'woosheets_google_accessToken', '' );
					}	
				}
		?>
        <!-- .wrap -->
    	<div class="vertical-tabs">
        	<div class="woosheet-logo-section">
            	<img src="<?php echo plugins_url(); ?>/woosheets/images/logo.png">
            		<sup>V<?php echo WOOSHEETS_VERSION; ?></sup>
            	<div class="duc-btn">
            		<a target="_blank" href="<?php echo esc_url("http://www.woosheets.creativewerkdesigns.com/Documentation/index.html"); ?>"><?php echo esc_html__( "Documentation", "woosheets" ); ?></a>
            	</div>
            	<div class="duc-btn1">
            		<a target="_blank" href="<?php echo esc_url("http://www.woosheets.creativewerkdesigns.com/Documentation/video-tutorials.html"); ?>"><?php echo esc_html_e( "Video Tutorials", "woosheets" ); ?></a>
                    </div>
            </div>
             <div class="tab">
              <button class="tablinks googleapi-settings" onclick="woosheetstab(event, 'googleapi-settings')">
              	<span class="tab-icon"></span>
              	<?php echo esc_html__( "Google API", "woosheets" ); ?> <br><?php echo esc_html__( "Settings", "woosheets" ); ?></button>
              <button class="tablinks general-settings" onclick="woosheetstab(event, 'general-settings')"> <span class="tab-icon"></span><?php echo esc_html__( "General", "woosheets" ); ?> <br><?php echo esc_html__( "Settings", "woosheets" ); ?></button>
              <button class="tablinks export" onclick="woosheetstab(event, 'export')"> <span class="tab-icon"></span> <?php echo esc_html__( "Export Orders", "woosheets" ); ?></button>
              <button class="tablinks em-settings" onclick="woosheetstab(event, 'em-settings')"><span class="tab-icon"></span> <?php echo esc_html__( "Envato Market", "woosheets" ); ?><br><?php echo esc_html__( "Settings", "woosheets" ); ?></button>
              <button class="tablinks support" onclick="woosheetstab(event, 'support')"> <span class="tab-icon"></span> <?php echo esc_html__( "Support", "woosheets" ); ?></button>
            </div>
            <div id="googleapi-settings" class="tabcontent">
              <h3><?php echo esc_html__( "Google API Settings", "woosheets" ); ?></h3>
              <p><?php echo esc_html__( "Create new google APIs with Client ID and Client Secret keys to get an access for the google drive and google sheets. Please follow the documentation, login to your Gmail Account and start with", "woosheets" ); ?> <a href="<?php echo esc_url('http://www.woosheets.creativewerkdesigns.com/Documentation/sheetsetting.html'); ?>" target="
		  "><?php echo esc_html__( "here", "woosheets" ); ?>.</a></p>
       			<form method="post" action="<?php echo esc_html( admin_url( 'admin.php?page=woosheets' ) ); ?>">
				<?php 
                    $woosheets_google_settings_value = get_option('google_settings');
                ?>
                    <div id="universal-message-container woocommerce">
                      <br>
                        <div class="options">
                            <table class="form-table">
                            <tr>
                                <th> <?php echo esc_html__( "Client Id", "woosheets" ); ?> </th>
                                <td class="forminp forminp-text">
                                    <input type="text" name="client_id" value="<?php echo $woosheets_google_settings_value[0]; ?>" size="80" class = "googlesettinginput" placeholder="Enter Client Id"/>
                                </td>
                            </tr>
                            <tr>
                                <th> <?php echo esc_html__( "Client Secret", "woosheets" ); ?> </th>
                                <td class="forminp forminp-text">
                                    <input type="text" name="client_secret" value="<?php echo $woosheets_google_settings_value[1]; ?>" size="80" class = "googlesettinginput" placeholder="Enter Client Secret"/>
                                 </td>
                            </tr>
                            <?php 
                                if(!empty($woosheets_google_settings_value[0]) && !empty($woosheets_google_settings_value[1])){
                                    
                                        $woosheets_token_value = $woosheets_google_settings_value[2];
                                    
                            ?>
                             <tr>
                                <th><?php echo esc_html__( "Client Token", "woosheets" ); ?></th>
                            <?php  
                            if( empty($woosheets_token_value) && !isset( $_GET['code'] ) ){
                                $woosheets_auth_url = Wc_WooSheets_Setting::getClient();				
                             ?>
                               <td id="authbtn">
                                    <a href="<?php echo esc_url($woosheets_auth_url); ?>" id="authlink" target="_blank" ><div class="woosheets-button woosheets-button-secondary"><?php echo esc_html__( "Click here to generate an Authentication Token", "woosheets" ); ?></div></a>
                               </td>
                               <?php }
                               		$woosheets_code = ''; 
                               		if( isset( $_GET['code'] ) && !empty( $_GET['code']) )
                               			$woosheets_code = $_GET['code'];

                               ?>
                               <td class="forminp forminp-text" id="authtext" style=" <?php if( !empty($woosheets_token_value) || $woosheets_code ){ ?> display: inline-block; <?php } ?> "><input type="text" name="client_token" value="<?php  echo $woosheets_token_value?$woosheets_token_value:$woosheets_code ?>" size="80" placeholder="Please enter authentication code" id="client_token" class="googlesettinginput"/></td>
                              
                              </tr>
                            <?php 
                           
                            }
                                 if(!empty($woosheets_token_value)){
                             ?>
                            <tr>
                                <td></td>
                                <td><input type="submit" name="revoke" id="revoke" value = "Revoke Token" class="woosheets-button woosheets-button-secondary"/></td>
                            </tr>
                            <?php
                            } ?>
                             
                            </table>

                        	<a target="_blank" href="<?php echo esc_url("http://www.woosheets.creativewerkdesigns.com/Documentation/video-tutorials.html"); ?>">
                            <img src="<?php echo plugins_url(); ?>/woosheets/images/google-api-settings-video-tutorial.png" class="video-screenshot">
                            </a>
                        </div>
                        <?php $site_url  = $_SERVER['SERVER_NAME']; ?>
                        <p class="submit"><input type="submit" name="submit" id="submit" class="woosheets-button woosheets-button-primary" value="Save"></p>
                        <table class="copy-url-table" cellpadding="0" cellspacing="0" width="100%" border="0px">
                        	<tr>
                        		<td><?php echo esc_html__( "Authorized Domain : ", "woosheets" ); ?></td>
                        		<td><span id="authorized_domain"><?php echo esc_html($site_url); ?></span><span class="copy-icon woosheets-button woosheets-button-primary" id="a_domain" onclick="woosheets_copy('authorized_domain','a_domain');"></span></td>
                        	</tr>
                        	<tr>
                        		<td><?php echo esc_html__( "Authorised redirect URIs : ", "woosheets" ); ?></td>
                        		<td><span id="authorized_uri"><?php echo esc_html( admin_url( 'admin.php?page=woosheets' ) ); ?></span><span class="copy-icon woosheets-button woosheets-button-primary" onclick="woosheets_copy('authorized_uri','a_uri');" id="a_uri"></span></td>
                        	</tr>
                        </table>
                    </div>
        		</form>
            </div>
            
            <div id="general-settings" class="tabcontent">
              <h3><?php echo esc_html__( "General Settings", "woosheets" ); ?></h3>
              <?php 				
			  	$woosheets_google_settings = get_option('google_settings');
				if(!empty($woosheets_google_settings[2])){ ?>
					<form method="post" action="<?php echo esc_html( admin_url( 'admin.php?page=woosheets&tab=general-settings' ) ); ?>" id="mainform">
	            	<?php 
	            		if(Wc_WooSheets_Dependencies::is_woocommerce_active()) {
					  		woocommerce_admin_fields(self::get_settings()); 
					  		?>
					  		<p class="submit"><input type="submit" name="submit" id="submit" class="woosheets-button woosheets-button-primary" value="Save"></p>
					  		<?php
					  	}else{
					  		_e('WooSheets plugin requires <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugin to be active!');
					  	}
					?>
                    
              		</form>
                <?php
				}else{
					echo  esc_html__( "Please genearate authentication code from", "woosheets" ); ?> <strong><?php echo  esc_html__( "Google API Setting", "woosheets" ); ?></strong>
                    <a href='<?php echo esc_url( 'admin.php?page=woosheets' ); ?>'> <?php echo  esc_html__( "Click", "woosheets" ); ?></a>
				<?php }	  ?>
              
            </div>
            <div id="export" class="tabcontent">
              <h3><?php echo esc_html__( "Export Orders", "woosheets" ); ?></h3>
              <p><?php echo  esc_html__( "You can export all orders or select custom order date range with below settings. It will create new spreadsheet in your Google Drive.", "woosheets" ); ?></p>
              <?php 				
			  	$woosheets_google_settings = get_option('google_settings');
				if( !empty($woosheets_google_settings[2]) && Wc_WooSheets_Dependencies::is_woocommerce_active() ){ 
					
					$woosheets_product_category_list = array();
					$args = array(
						'taxonomy'   => "product_cat",
						'orderby'    => 'name'
					);
					$product_categories = get_terms($args);
					
					foreach( $product_categories as $prd_cat){
						$woosheets_product_category_list[$prd_cat->term_id] = $prd_cat->name;		
					}
				?>
					<form method="post" action="" id="exportform">
                    <table class="form-table woosheets-section-1">
                    <tbody><tr valign="top">
                        <th scope="row" class="titledesc">
                            <label for="woocommerce_spreadsheet"><?php echo  esc_html__( "Enter Spreadsheet Name", "woosheets" ); ?> <span class="woocommerce-help-tip" ></span></label>
                        </th>
                        <td class="forminp forminp-select"><input name="expspreadsheetname" id="expspreadsheetname" type="text" placeholder="<?php echo  esc_attr__( "Enter Spreadsheet Name", "woosheets" ); ?>" required></td>
                        </tr>
                        <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label for="woocommerce_spreadsheet"><?php echo  esc_html__( "Order Date Range", "woosheets" ); ?> <span class="woocommerce-help-tip" data-tip="Please select Google Spreadsheet."></span></label>
                        </th>
                        <td class="forminp forminp-select"> <?php echo  esc_html__( "From :", "woosheets" ); ?>   <input name="expspreadsheetname" id="ordfromdate" type="date" required></td>
                        </tr>
                        <tr valign="top">
                        <th scope="row" class="titledesc"></th>
                        <td class="forminp forminp-select"><?php echo  esc_html__( "To :", "woosheets" ); ?> <input name="expspreadsheetname" id="ordtodate" type="date" required></td>
                        </tr>
                        <tr valign="top">
                        <th scope="row" class="titledesc">
                            <label for="exportall"><?php echo esc_html__( "Export All Orders", "woosheets" ); ?></label>
                        </th>
                        <td class="exportrow">              
                          <label for="exportall">
                            <input name="repeat_checkbox" id="exportall" type="checkbox" class="" value="1"><span class="checkbox-switch"></span> 							
                          </label>
                        </td>
                    	</tr>
                        <tr valign="top">
                            <th scope="row" class="titledesc">
                                <label for="category_select"><?php echo esc_html__( "Select Category", "woosheets" ); ?></label>
                            </th>
                            <td class="exportrow">              
                              <label for="category_select">
                                <input name="category_select" id="category_select" type="checkbox" class="" value="1"><span class="checkbox-switch"></span> 							
                              </label>
                            </td>
                        </tr>
                        <tr class="td-prdcat-woosheets">
                        <td colspan="2" class="td-woosheets-headers">
                        <div class='woosheets_headers'>
                            <label for="sheet_headers"></label>
                            <div id="woosheets-headers-notice">
                            <i><?php echo esc_html__( "Select orders of below product categories as you want export within the spreadsheet.","woosheets"); ?></i>
                            </div>
                            <ul id="product-sortable">
                            <?php
                                        foreach ( $woosheets_product_category_list as $woosheets_key => $woosheets_val ) {
                            ?>				
                              <li class="ui-state-default"><label for="<?php echo $woosheets_val; ?>"><span class="ui-icon ui-icon-caret-2-n-s"></span><?php echo $woosheets_val; ?><input type="checkbox" name="productcat_header[]" value="<?php echo $woosheets_key; ?>" id="<?php echo $woosheets_val; ?>" class="prdcatheaders_chk"><span class="checkbox-switch-new"></span></label></li>
                             <?php 
                                    }
                                ?>
                            </ul>
                            <button type="button" class="woosheets-button woosheets-button-secondary" id="prdcatselectall" <?php if(!empty($woosheets_selections) ) echo 'style="display:none"'; ?>><?php esc_html_e( 'Select all', 'woosheets' ); ?></button>                
                           <button type="button" class="woosheets-button woosheets-button-secondary" id="prdcatselectnone" <?php if(!empty($woosheets_selections) ) echo 'style="display:none"'; ?>><?php esc_html_e( 'Select none', 'woosheets' ); ?></button>
                        </div>
                        </td>
                    </tr>
                    </tbody>
                    </table>

                    <p class="submit"><input type="submit" name="submit" id="exportsubmit" class="woosheets-button woosheets-button-primary" value="Export"><span class='processbar'><img src="<?php dirname(__FILE__) ?>images/spinner.gif" id="expsyncloader"><span id="expsynctext"><?php echo esc_html__( "Please wait...", "woosheets" ); ?></span></span><a target='_blank' class="woosheets-button woosheets-button-primary" href="" id='spreadsheet_url'><?php echo esc_html__( "View Spreadsheet", "woosheets" ); ?></a><a target='_blank' class="woosheets-button woosheets-button-primary" href="" id='spreadsheet_xslxurl'><?php echo esc_html__( "Download Spreadsheet (.xlsx)", "woosheets" ); ?></a></p>
                    
                    <?php if( get_option('woocommerce_spreadsheet') ){ 
                    	$spreadsheetid = get_option('woocommerce_spreadsheet');
                    	$xlsxurl = "https://docs.google.com/spreadsheets/u/0/d/{$spreadsheetid}/export?exportFormat=xlsx";
                    ?>
                    <table class="form-table woosheets-section-1">
                    	<tr valign="top">
                            <th scope="row" class="titledesc downloadall">
                                <label><?php echo esc_html__( "Download All Orders", "woosheets" ); ?></label>
                            </th>
                            <td class="exportrow">              
                              <a id="view_spreadsheet" download="" target="_blank" href="<?php echo $xlsxurl; ?>" class="woosheets-button">Download Spreadsheet (.xlsx)</a>
                            </td>
                        </tr>
                    </table>
                <?php } ?>  
              		</form>
                <?php
				}else{
					if( !Wc_WooSheets_Dependencies::is_woocommerce_active() ){
						_e('WooSheets plugin requires <a href="http://wordpress.org/extend/plugins/woocommerce/">WooCommerce</a> plugin to be active!');
					}else{
						echo  esc_html__( "Please genearate authentication code from", "woosheets" ); ?>
					 	<strong><?php echo  esc_html__( "Google API Setting", "woosheets" ); ?></strong>
                    	<a href='<?php echo esc_url( 'admin.php?page=woosheets' ); ?>'> <?php echo  esc_html__( "Click", "woosheets" ); ?></a>
				<?php
					}
				 }	  ?>
              
            </div>
            
            <div id="em-settings" class="tabcontent">
              <h3><?php echo esc_html__( "Envato Market Settings", "woosheets" ); ?></h3>
              <form action="" method="post">
				<?php wp_nonce_field( 'woosheets-special-string', '_wpnonce' ); ?>
               	<?php $woosheets_api_key = get_option( 'woosheets_envato_apikey' ); ?>
                <div class="envato-market-blocks">
                  <p> <?php echo esc_html__( "This settings will give you smooth update experience for WooSheets customers. It will also notify users for new updates of the plugin.", "woosheets" ); ?></p>
                    <div class="woosheets-supportform">              
                      <div class="col-left">
                      	<div class="description">
                            <p><strong><?php echo esc_html__( "WooSheets - Manage WooCommerce Orders with Google Spreadsheet", "woosheets" ); ?></strong>.</p>
                            <p><?php echo esc_html__( "Please follow the steps below:", "woosheets" ); ?></p>
                            <ol>
                              <li><?php echo esc_html__( "Generate an Envato API Personal Token by", "woosheets" ); ?> <a href="<?php echo esc_url( 'https://build.envato.com/create-token/?default=t&amp;purchase:download=t&amp;purchase:list=t' ); ?>" target="_blank"><?php echo esc_html__( "clicking this link", "woosheets" ); ?></a></li>
                              <li><?php echo esc_html__( "Name the token eg “My WordPress site”.", "woosheets" ); ?></li>
                              <li><?php echo esc_html__( "Ensure the following permissions are enabled:", "woosheets" ); ?>
                              	<ul>
                                    <li><?php echo esc_html__( "View and search Envato sites", "woosheets" ); ?></li>
                                    <li><?php echo esc_html__( "Download your purchased items", "woosheets" ); ?></li>
                                    <li><?php echo esc_html__( "List purchases you've made", "woosheets" ); ?></li>
                                </ul>
                              </li>
                              <li><?php echo esc_html__( "Copy the token into the Envato API Personal Token box.", "woosheets" ); ?></li>
                              <li><?php echo esc_html__( 'Click the "Save" button.', "woosheets" ); ?></li>
                            </ol>
                      </div>
                        <div class="form-field">
                            <input type="text" name="ws_envato" id="ws_envato" value="<?php echo $woosheets_api_key; ?>" placeholder="Envato API Personal Token" required />
                        </div>
                      </div>
                      <div class="col-right">
                        
                      </div>
                  </div>
          </div>
                    <p class="submit"><img src="<?php dirname(__FILE__) ?>images/spinner.gif" id="licenceloader"><span id="licencetext"><?php echo esc_html__( "Activating...", "woosheets" ); ?></span><input type="submit" name="submit" id="licence_submit" class="woosheets-button woosheets-button-primary" value="Save"></p>
                    <span class="woosheets-license-result"></span>
			</form>
            </div> 
            <div id="support" class="tabcontent">
              <h3><?php echo esc_html__( "We're here to help.", "woosheets" ); ?></h3>
              <p><?php echo esc_html__( "If you have a problem that you can't solve with our knowledge base documentation, you can send a message to our support team. They can be reached 24/7.", "woosheets" ); ?></p>
              <div class="custom-main support-top-section">
                  <div class="left"> 
                  <h4><?php echo esc_html__( "Need Help?", "woosheets" ); ?></h4>
                    <div class="woosheets-supportform">  
                    <p><?php echo esc_html__( "Visit Our Support Center 24/7 to Submit A Ticket", "woosheets" ); ?></p>            
                      <a href="<?php echo esc_url( "http://envato.creativewerkdesigns.com/tickets/index.php/signup?plugin=woosheets  ", "woosheets" ); ?>" target="_blank"><div class="woosheets-button woosheets-button-primary" style="text-align:center;"><?php echo esc_html__( "Submit A Ticket", "woosheets" ); ?></div></a>
                  </div>
                  </div>
                  <div class="right">
                    <h4>  <?php echo esc_html__( "Support Includes", "woosheets" ); ?></h4>
                        <ul class="check-icon">
                           <li><?php echo esc_html__( "Responding to questions or problems regarding the plugin's features", "woosheets" ); ?></li>
                           <li><?php echo esc_html__( "Fixing bugs and reported issues", "woosheets" ); ?></li>
                           <li><?php echo esc_html__( "Providing updates for the most popular plugins to ensure compatibility with new software versions", "woosheets" ); ?> </li>
                           <li><?php echo esc_html__( "Support for 3rd party plug-ins", "woosheets" ); ?> </li>
                         </ul>
                         <h4>  <?php echo esc_html__( "Does NOT Include", "woosheets" ); ?></h4>
                         <ul class="close-icon">
                            <li><?php echo esc_html__( "Plugin Customization", "woosheets" ); ?></li>
                            <li><?php echo esc_html__( "Requests that require Custom Coding", "woosheets" ); ?></li>
                          </ul>    
                    	<hr>
                        <a href="<?php echo esc_html__( "http://www.woosheets.creativewerkdesigns.com/Documentation/faq.html", "woosheets" ); ?>" target="_blank" class="woosheets-support woosheets-support-secondary"><?php echo esc_html__( "FAQ", "woosheets" ); ?></a>
                        <a href="<?php echo esc_html__( "http://www.woosheets.creativewerkdesigns.com/Documentation/plugins-compatibility.html", "woosheets" ); ?>" target="_blank" class="woosheets-support woosheets-support-secondary"><?php echo esc_html__( "Plugins Compatibility List", "woosheets" ); ?></a>
                  </div>
              </div>
              <div class="textwidget support-btm-section">
              <p><?php echo esc_html__( "Support is included 6 months for customers regarding bugs and issues related with our products. You can extend this period up to 12 months.", "woosheets" ); ?></p>
						<p><strong><?php echo esc_html__( "What's not included in item support!", "woosheets" ); ?></strong></p>
						<ul>
							<li>- <?php echo esc_html__( "Item Customization", "woosheets" ); ?></li>
							<li>- <?php echo esc_html__( "Hosting, Server Environment, or Software Changes", "woosheets" ); ?></li>
							<li>- <?php echo esc_html__( "Help from authors of included third party assets", "woosheets" ); ?></li>
						</ul>
					<p><?php echo esc_html__( "Read more", "woosheets" ); ?> <a href="<?php echo esc_html__( "http://codecanyon.net/page/item_support_policy", "woosheets" ); ?>" class="AMB-ILink" target="_blank"><?php echo esc_html__( "here", "woosheets" ); ?></a></p>
				</div>
            </div> 
         </div>
    <?php	
    }

	
	public static function _get_all_meta_values() {
			global $wpdb;
			$woosheets_wc_cf_headers = array();
			$woosheets_querystr = "SELECT {$wpdb->prefix}posts.* FROM {$wpdb->prefix}posts INNER JOIN {$wpdb->prefix}postmeta ON ( {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id ) WHERE 1=1 AND ( {$wpdb->prefix}postmeta.meta_key = '_product_addons' )";
			$woosheets_postsmeta = $wpdb->get_results($woosheets_querystr,ARRAY_A);
			foreach($woosheets_postsmeta as $woosheets_cfield){
				$woosheets_value = get_post_meta($woosheets_cfield['ID'],'_product_addons',true);
				if(!empty($woosheets_value)){
					foreach($woosheets_value as $woosheets_val){
						if($woosheets_val['type'] == 'heading') 
							continue;
						$woosheets_wc_cf_headers[] = $woosheets_val['name'];	
					}
				}
					
			}
			
			return $woosheets_wc_cf_headers;
	}
	
	
	public static function _get_all_attributes() {
		global $wpdb;
		$woosheets_attribute_taxonomies = array();
		$woosheets_attribute_taxonomies = $wpdb->get_results( "SELECT * FROM " . $wpdb->prefix . "woocommerce_attribute_taxonomies WHERE attribute_name != '' ORDER BY attribute_name ASC;" );
		set_transient( 'wc_attribute_taxonomies', $woosheets_attribute_taxonomies );
		$woosheets_attribute_taxonomies = array_column( array_filter( $woosheets_attribute_taxonomies ),'attribute_name');		
		$woosheets_attribute_taxonomies = array_map(function($e) { return str_replace('-',' ',ucfirst(str_replace("pa_",'',$e)));},$woosheets_attribute_taxonomies);
		 
		$woosheets_product_attr = Wc_WooSheets_Setting::get_meta_values('_product_attributes');
		$woosheets_product_attr = array_map(function($e) {
						return array_keys($e);
					}, $woosheets_product_attr);
		$woosheets_product_attr = Wc_WooSheets_Setting::array_flatten($woosheets_product_attr);		
		$woosheets_product_attr = array_map(function($e) { return str_replace('-',' ',ucfirst(str_replace("pa_",'',$e)));},$woosheets_product_attr);
		$woosheets_product_attr = array_unique($woosheets_product_attr);
    	
		$woosheets_attribute_taxonomies = array_unique(array_merge($woosheets_attribute_taxonomies,$woosheets_product_attr));
		
		return $woosheets_attribute_taxonomies;
	}
    /**
     * Uses the WooCommerce options API to save settings via the @see woocommerce_update_options() function.
     *
     * @uses woocommerce_update_options()
     * @uses self::get_settings()
     */
    public static function update_settings() {
			
		$woosheets_sheetname = self::create_sheet($_POST);
		if(isset($_POST['header_fields'])){
			
			$woosheets_header 		 = array();
 		    $woosheets_header_custom = array();
			if( isset($_POST['prdassheetheaders']) && isset($_POST['woosheets_append_after']) && in_array($_POST['woosheets_append_after'], array_map( function($key) {	return str_replace(' ', '-', strtolower($key));	}, $_POST['header_fields'] ))){
				$flag = 0;
				foreach( $_POST['header_fields'] as $headers){
					$woosheets_header[] 	   = $headers;
					$woosheets_header_custom[] = $_POST['header_fields_custom'][$flag];
					if( str_replace(' ', '-', strtolower($headers)) == str_replace(' ', '-', strtolower($_POST['woosheets_append_after'] ) ) &&  isset($_POST['product_header_fields']) ){
						foreach( $_POST['product_header_fields'] as $prd_header ){
							$woosheets_header[]			= $prd_header;
						}
						foreach( $_POST['product_header_fields_custom'] as $prd_header ){
							$woosheets_header_custom[]  = $prd_header;
						}		
					}	
					$flag++;
				}		
			}else{
				if( isset($_POST['prdassheetheaders']) && isset($_POST['product_header_fields']) && is_array( $_POST['product_header_fields'] ) &&  isset($_POST['prdassheetheaders']) ){
					$woosheets_header 		 = array_merge( $_POST['header_fields'], $_POST['product_header_fields'] );
					$woosheets_header_custom = array_merge( $_POST['header_fields_custom'], $_POST['product_header_fields_custom'] );
				}else{
					$woosheets_header 		 = $_POST['header_fields'];		
					$woosheets_header_custom = $_POST['header_fields_custom'];
				}
			}
			if( isset($_POST['prdassheetheaders']) ){
				update_option('prdassheetheaders', $_POST['prdassheetheaders']);
				update_option('woosheets_append_after', $_POST['woosheets_append_after']);
				if( isset($_POST['product_header_fields']) ){
					$woosheets_product_headers = stripslashes_deep($_POST['product_header_fields']);
					update_option('product_sheet_headers_list', $woosheets_product_headers);
				}
				if( isset($_POST['product_header_fields_custom']) ){
					$product_header_fields_custom = stripslashes_deep($_POST['product_header_fields_custom']);
					update_option('product_sheet_headers_list_custom', $product_header_fields_custom);
				}
			}else{
				update_option('prdassheetheaders', '');
				update_option('woosheets_append_after', '' );
				$woosheets_product_headers = array();
				update_option('product_sheet_headers_list', $woosheets_product_headers);
				update_option('product_sheet_headers_list_custom', $woosheets_product_headers);
			}	
			
			$woosheets_my_headers = stripslashes_deep( $woosheets_header );
			update_option( 'sheet_headers_list', $woosheets_my_headers);
			$woosheets_mycustom_headers = stripslashes_deep( $woosheets_header_custom );			
			update_option( 'sheet_headers_list_custom', $woosheets_mycustom_headers );
			
			//Append after dropdown value array
			if(isset($_POST['header_fields'])){
				$keys 							 = array_map( function($key) {	return str_replace(' ', '-', strtolower($key));	}, $_POST['header_fields'] );
				$woosheets_append_after_array    = array_combine($keys, $_POST['header_fields_custom']);
				update_option('woosheets_append_after_array', $woosheets_append_after_array);
			}
	 
		}
		woocommerce_update_options( self::get_settings() );
		if($_POST['woocommerce_spreadsheet'] == 'new')
			update_option('woocommerce_spreadsheet',$woosheets_sheetname);
		if(isset($_POST['header_format'])){
			$woosheets_header_format = $_POST['header_format'];	
			update_option('header_format', $woosheets_header_format);
		}
		if(isset($_POST['repeat_checkbox'])){	
			update_option('repeat_checkbox', 'yes');
		}else{
			update_option('repeat_checkbox', 'no');		
		}
    }
    /**
     * Get all the settings for this plugin for @see woocommerce_admin_fields() function.
     *
     * @return array Array of settings for @see woocommerce_admin_fields() function.
     */
    public static function get_settings() {
		Wc_WooSheets_Setting::getClient();		
		$woosheets_status_array = wc_get_order_statuses();		
        $woosheets_settings = array(
            'section_first_title' => array(
                'name'     => __( '', 'woosheets' ),
                'type'     => 'title',
                'desc'     => 'Assign Google Spreadsheet will be automatically create sheet name and sheet headers as per the below settings and it will be create new rows whenever new orders has been placed.',
                'id'       => 'wc_google_sheet_settings_first_section_start'
            ),
             array(
				'name'    => __( 'Select Spreadsheet', 'woosheets' ),
				'desc'    => __( 'Please select Google Spreadsheet.', 'woosheets' ),
				'id'      => 'woocommerce_spreadsheet',
				'css'     => 'min-width:150px;',
				'std'     => 'left', // WooCommerce < 2.0
				'default' => 'left', // WooCommerce >= 2.0
				'type'    => 'select',
				'options' =>  Wc_WooSheets_Setting::list_googlespreedsheet(),
				'desc_tip' =>  true,
			  ),
			  array( 
			  	'type' => 'new_spreadsheetname' 
			  ),
			  'section_first_end' => array(
                 'type' => 'sectionend',
                 'id' => 'wc_google_sheet_settings_first_section_end'
            	),
			'section_second_title' => array(
                'name'     => __( 'Default Order Status', 'woosheets' ),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'wc_google_sheet_settings_second_section_start'
            ),
			 array(
				'title'         => __( 'Pending Orders', 'woosheets' ),
				'id'            => 'pending_orders',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'autoload'      => false,
				  ),
			 array(
				'title'         => __( 'Processing Orders', 'woosheets' ),
				'id'            => 'processing_orders',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'autoload'      => false,
				  ),
			 array(
				'title'         => __( 'On hold Orders', 'woosheets' ),
				'id'            => 'on_hold_orders',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'autoload'      => false,
				  ),
			 array(
				'title'         => __( 'Completed Orders', 'woosheets' ),
				'id'            => 'completed_orders',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'autoload'      => false,
				  ),
			array(
				'title'         => __( 'Cancelled Orders', 'woosheets' ),
				'id'            => 'cancelled_orders',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'autoload'      => false,
				  ),
			 array(
				'title'         => __( 'Refunded Orders', 'woosheets' ),
				'id'            => 'refunded_orders',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'autoload'      => false,
				  ),
			array(
				'title'         => __( 'Failed Orders', 'woosheets' ),
				'id'            => 'failed_orders',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'autoload'      => false,
				  ),
			array(
				'title'         => __( 'Trash Orders', 'woosheets' ),
				'id'            => 'trash',
				'default'       => 'yes',
				'type'          => 'checkbox',
				'autoload'      => false,
				  ),
			array(
				'title'         => __( 'All Orders', 'woosheets' ),
				'id'            => 'all_orders',
				'default'       => 'no',
				'type'          => 'checkbox',
				'autoload'      => false,
				  ),
			 'section_second_end' => array(
                 'type' => 'sectionend',
                 'id' => 'wc_google_sheet_settings_second_section_end'
            	),
			'section_third_title' => array(
                'name'     => __( '', 'woosheets' ),
                'type'     => 'title',
                'desc'     => '',
                'id'       => 'wc_google_sheet_settings_third_section_start'
            ),	
			array( 'type' => 'manage_row_field' ),
			array( 'type' => 'set_headers' ),
			array( 'type' => 'product_as_sheet_header' ),
			array( 'type' => 'product_headers' ),
			array( 'type' => 'product_headers_append_after' ),
			 'section_third_end' => array(
                 'type' => 'sectionend',
                 'id' => 'wc_google_sheet_settings_third_section_end'
            	),
			'section_fourth_title' => array(
                'name'     => __( '', 'woosheets' ),
                'type'     => 'title',
                'desc'     => '',
				'class' => 'section_fourth_end',
                'id'       => 'wc_google_sheet_settings_fourth_section_start'
            ),	
			array( 'type'  => 'repeat_checkbox' ),
			array( 'type' => 'custom_headers_action' ),
			array( 'type' => 'sync_button' ),
			array(
				'title'         => __( 'Freeze Header', 'woosheets' ),
				'id'            => 'freeze_header',
				'default'       => 'no',
				'type'          => 'checkbox',
				'autoload'      => false,
				  ),
             'section_fourth_end' => array(
                 'type' => 'sectionend',
                 'id' => 'wc_google_sheet_settings_fourth_section_end'
            	)
        );
		$woosheets_custom_status_array = array();
		global $woosheets_default_status;
		$woosheets_settingflag = 0;
		foreach($woosheets_status_array as $woosheets_key => $woosheets_val){
			$woosheets_status = substr($woosheets_key, strpos($woosheets_key, "-") + 1); 
			if(!in_array($woosheets_status,$woosheets_default_status)){
				$woosheets_settingflag++;
				if( $woosheets_settingflag == 1  ){
					$woosheets_custom_status_array['section_fifth_title'] = array(
						'name'     => __( 'Custom Order Status', 'woosheets' ),
						'type'     => 'title',
						'desc'     => '',
						'id'       => 'wc_google_sheet_settings_second_section_start'
					);
				}
				$woosheets_custom_status_array[$woosheets_status] = 	array(
				'title'         => __( $woosheets_val. ' Orders', 'woosheets' ),
				'id'            => $woosheets_status,
				'default'       => 'No',
				'type'          => 'checkbox',
				'autoload'      => false,
				  );
			}
		}	
		if( $woosheets_settingflag > 0  ){
			$woosheets_custom_status_array['section_fifth_end'] = array(
                 'type' => 'sectionend',
                 'id' => 'wc_google_sheet_settings_second_section_end'
            	);
		}	
		if(!empty($woosheets_custom_status_array)){	
			$woosheets_settings = array_slice($woosheets_settings, 0, 15, true) + $woosheets_custom_status_array + array_slice($woosheets_settings, 15, count($woosheets_settings) - 1, true) ;
		}
		
        return $woosheets_settings;
    }
	
	/**
	* Generate token for the user and refresh the token if it's expired.
	*
	* @return array
	*/
	public static function getClient()
	{	
		 
		$woosheets_google_settings = get_option('google_settings');
		$woosheets_clientId = $woosheets_google_settings[0];
		$woosheets_clientSecert = $woosheets_google_settings[1];
		$woosheets_client = new Google_Client();
		$woosheets_client->setApplicationName('Manage WooCommerce Orders with Google Spreadsheet');
		$woosheets_client->setScopes(Google_Service_Sheets::SPREADSHEETS_READONLY);
		$woosheets_client->setScopes(Google_Service_Drive::DRIVE_METADATA_READONLY);
		$woosheets_client->addScope(Google_Service_Sheets::SPREADSHEETS);
		$woosheets_client->setClientId($woosheets_clientId);
		$woosheets_client->setClientSecret($woosheets_clientSecert);
		$woosheets_client->setRedirectUri( esc_html( admin_url( 'admin.php?page=woosheets' ) ) );
		$woosheets_client->setAccessType('offline');
		$woosheets_client->setApprovalPrompt('force');



		// Load previously authorized credentials from a file.
		$woosheets_auth_token = $woosheets_google_settings[2];
		if(empty( $woosheets_auth_token )){
			$woosheets_auth_url = $woosheets_client->createAuthUrl();
			return $woosheets_auth_url;
		}
		$woosheets_woosheets_accessToken = get_option( 'woosheets_google_accessToken' );
		if ( !empty( $woosheets_woosheets_accessToken ) ) {
			$woosheets_accessToken = json_decode($woosheets_woosheets_accessToken, true);
		} else { 
			if ( empty( $woosheets_auth_token ) ) {
				$woosheets_auth_url = $woosheets_client->createAuthUrl();
				return $woosheets_auth_url;
			}
			else{
				$woosheets_authCode = trim($woosheets_auth_token);
				// Exchange authorization code for an access token.
				$woosheets_accessToken = $woosheets_client->fetchAccessTokenWithAuthCode($woosheets_authCode);
				// Store the credentials to disk.
			   	update_option('woosheets_google_accessToken', json_encode($woosheets_accessToken) );
			}
		}
		
		 $woosheets_client->setAccessToken( $woosheets_accessToken );
		// Refresh the token if it's expired.
		if ($woosheets_client->isAccessTokenExpired()) {
			// save refresh token to some variable
			$woosheets_refreshTokenSaved = $woosheets_client->getRefreshToken();			
			$woosheets_client->fetchAccessTokenWithRefreshToken($woosheets_client->getRefreshToken());
			 // pass access token to some variable
			$woosheets_accessTokenUpdated = $woosheets_client->getAccessToken();
			// append refresh token
			$woosheets_accessTokenUpdated['refresh_token'] = $woosheets_refreshTokenSaved;
			//Set the new acces token
			$woosheets_accessToken = $woosheets_refreshTokenSaved;
			update_option('woosheets_google_accessToken', json_encode( $woosheets_accessTokenUpdated ) );
			$woosheets_accessToken = json_decode( json_encode( $woosheets_accessTokenUpdated ), true); 
			$woosheets_client->setAccessToken($woosheets_accessToken);
		}

		return $woosheets_client;
	}
	
	/**
	* Prepare Google Spreadsheet list
	* 
	* @access public
	* @return array $woosheets_sheetarray
	*/
   public static function list_googlespreedsheet()
	{
		
		$woosheets_client = Wc_WooSheets_Setting::getClient();
		$woosheets_service = new Google_Service_Drive($woosheets_client);
		/* Build choices array. */
		$woosheets_sheetarray = array(
				'' => __( 'Select Google Spreeadsheet List', 'woosheets' ),
		);
		 
		// Print the names and IDs for up to 10 files.
		$woosheets_optParams = array(
		  'fields' => 'nextPageToken, files(id, name, mimeType)',
		  'q' => "mimeType='application/vnd.google-apps.spreadsheet' and trashed = false"
		 
		);
		$woosheets_results = $woosheets_service->files->listFiles($woosheets_optParams);
		
		
		
		if (count($woosheets_results->getFiles()) == 0) {
			$woosheets_sheetarray['new'] = __( "Create New Spreadsheet", 'woosheets');
		} else {
			foreach ($woosheets_results->getFiles() as $woosheets_file) {
				$woosheets_sheetarray[$woosheets_file->getId()] = __( $woosheets_file->getName(), 'woosheets' );
			}				
			$woosheets_sheetarray['new'] = __( "Create New Spreadsheet", 'woosheets' );
		}
			return $woosheets_sheetarray;
	}	
	
 	public static function wcgs_pending($woosheets_order_id) {
		if(get_option('pending_orders') == 'yes'){
			 Wc_WooSheets_Setting::getClient();
			 $woosheets_sheetname = 'Pending Orders';
			 Wc_WooSheets_Setting::insert_data_into_sheet($woosheets_order_id,$woosheets_sheetname);
		}
		if( get_option('all_orders') == 'yes') {
			Wc_WooSheets_Setting::getClient();
			$woosheets_sheetname = 'All Orders'; 
			Wc_WooSheets_Setting::woosheets_all_orders($woosheets_order_id,$woosheets_sheetname);
		}
    }
	
    public static function wcgs_failed($woosheets_order_id) {
		if(get_option('failed_orders') == 'yes'){
			 Wc_WooSheets_Setting::getClient();
			 $woosheets_sheetname = 'Failed Orders';
			 Wc_WooSheets_Setting::insert_data_into_sheet($woosheets_order_id,$woosheets_sheetname);
		}
		if( get_option('all_orders') == 'yes') {
			Wc_WooSheets_Setting::getClient();
			$woosheets_sheetname = 'All Orders'; 
			Wc_WooSheets_Setting::woosheets_all_orders($woosheets_order_id,$woosheets_sheetname);
		}
    }
	
    public static function wcgs_hold($woosheets_order_id) {
		if(get_option('on_hold_orders') == 'yes'){
			 Wc_WooSheets_Setting::getClient();
			 $woosheets_sheetname = 'On Hold Orders';
			 Wc_WooSheets_Setting::insert_data_into_sheet($woosheets_order_id,$woosheets_sheetname);
		}
		if( get_option('all_orders') == 'yes') {
			Wc_WooSheets_Setting::getClient();
			$woosheets_sheetname = 'All Orders'; 
			Wc_WooSheets_Setting::woosheets_all_orders($woosheets_order_id,$woosheets_sheetname);
		}
    }
	
    public static function wcgs_processing($woosheets_order_id) {
		if(get_option('processing_orders') == 'yes'){
			 Wc_WooSheets_Setting::getClient();
			 $woosheets_sheetname = 'Processing Orders';
			 Wc_WooSheets_Setting::insert_data_into_sheet($woosheets_order_id,$woosheets_sheetname);
		}
		if( get_option('all_orders') == 'yes') {
			Wc_WooSheets_Setting::getClient();
			$woosheets_sheetname = 'All Orders'; 
			Wc_WooSheets_Setting::woosheets_all_orders($woosheets_order_id,$woosheets_sheetname);
		}
    }
	
    public static function wcgs_completed($woosheets_order_id) {
		if(get_option('completed_orders') == 'yes'){
			 Wc_WooSheets_Setting::getClient();
			 $woosheets_sheetname = 'Completed Orders';
			 Wc_WooSheets_Setting::insert_data_into_sheet($woosheets_order_id,$woosheets_sheetname);
		}
		if( get_option('all_orders') == 'yes') {
			Wc_WooSheets_Setting::getClient();
			$woosheets_sheetname = 'All Orders'; 
			Wc_WooSheets_Setting::woosheets_all_orders($woosheets_order_id,$woosheets_sheetname);
		}
    }
	
    public static function wcgs_refunded($woosheets_order_id) {
		if(get_option('refunded_orders') == 'yes'){
			 Wc_WooSheets_Setting::getClient();
			 $woosheets_sheetname = 'Refunded Orders';
			 Wc_WooSheets_Setting::insert_data_into_sheet($woosheets_order_id,$woosheets_sheetname);
		}
		if( get_option('all_orders') == 'yes') {
			Wc_WooSheets_Setting::getClient();
			$woosheets_sheetname = 'All Orders'; 
			Wc_WooSheets_Setting::woosheets_all_orders($woosheets_order_id,$woosheets_sheetname);
		}
    }
	
    public static function wcgs_cancelled($woosheets_order_id) {
		if(get_option('cancelled_orders') == 'yes'){
			 Wc_WooSheets_Setting::getClient();
			 $woosheets_sheetname = 'Cancelled Orders';
			 Wc_WooSheets_Setting::insert_data_into_sheet($woosheets_order_id,$woosheets_sheetname);
		}
		if( get_option('all_orders') == 'yes') {
			Wc_WooSheets_Setting::getClient();
			$woosheets_sheetname = 'All Orders'; 
			Wc_WooSheets_Setting::woosheets_all_orders($woosheets_order_id,$woosheets_sheetname);
		}
    }
	
	public static function wcgs_trash($woosheets_order_id) {
		
		global $post_type;
		if($post_type !== 'shop_order') {
			return;
		}
		
		$woosheets_order = wc_get_order( $woosheets_order_id );
		if( $woosheets_order ){
			$woosheets_old_status  = $woosheets_order->get_status(); 
			/*Remove order detail from old status*/
			Wc_WooSheets_Setting::woo_order_status_change_custom($woosheets_order_id,$woosheets_old_status,'trash');
		}
	   /*
		* Move order detail to trash sheet
		*/
		if(get_option('trash_orders') == 'yes'){
			 Wc_WooSheets_Setting::getClient();
			 $woosheets_sheetname = 'Trash Orders';
			 Wc_WooSheets_Setting::insert_data_into_sheet($woosheets_order_id,$woosheets_sheetname);
		}
		if( get_option('all_orders') == 'yes') {
			Wc_WooSheets_Setting::getClient();
			$woosheets_sheetname = 'All Orders'; 
			Wc_WooSheets_Setting::woosheets_all_orders($woosheets_order_id,$woosheets_sheetname);
		}
    }
	
	public static function wcgs_restore( $woosheets_new_status, $woosheets_old_status, $woosheets_post ) {
		global $post_type;
		if( ($post_type !== 'shop_order') || (isset($_REQUEST['action']) && $_REQUEST['action'] != 'untrash') ) {
			return;
		}			
		$woosheets_status_array = wc_get_order_statuses();
		$woosheets_sheetname = $woosheets_status_array[$woosheets_new_status].' Orders';
    	if ( $woosheets_old_status == 'trash' ) {
			Wc_WooSheets_Setting::woo_order_status_change_custom($woosheets_post->ID,'trash',$woosheets_sheetname);
			Wc_WooSheets_Setting::insert_data_into_sheet($woosheets_post->ID,$woosheets_sheetname);
    	}
	}
	
	public static function woo_order_status_change_custom($woosheets_order_id,$woosheets_old_status,$woosheets_new_status){
		
		/* 
		 *Custom Order Status sheet setting 
		 */
		$woosheets_custom_status_array = array();
		$woosheets_status_array = wc_get_order_statuses();
		global $woosheets_default_status;		
		foreach($woosheets_status_array as $woosheets_key => $woosheets_val){
			$woosheets_status = substr($woosheets_key, strpos($woosheets_key, "-") + 1);
			if(!in_array($woosheets_status,$woosheets_default_status)){
				$woosheets_custom_status_array[$woosheets_status] = $woosheets_val;
			}
		}
		/**/
		$woosheets_client = Wc_WooSheets_Setting::getClient();
		$woosheets_service = new Google_Service_Sheets($woosheets_client);
		
		$woosheets_spreadsheetId = get_option('woocommerce_spreadsheet');
		
		$woosheets_response = $woosheets_service->spreadsheets->get($woosheets_spreadsheetId);
			
		foreach($woosheets_response->getSheets() as $woosheets_key => $woosheets_value) {
			 $woosheets_existingsheetsnames[$woosheets_value['properties']['title']] = $woosheets_value['properties']['sheetId'];
		}
		
		if($woosheets_old_status == 'processing'){
			if(get_option('processing_orders') == 'yes'){
				$woosheets_sheetID = $woosheets_existingsheetsnames['Processing Orders'];
				Wc_WooSheets_Setting::move_order($woosheets_order_id, $woosheets_sheetID, 'Processing Orders');				
			}
		}
		if($woosheets_old_status == 'on-hold'){
			if(get_option('on_hold_orders') == 'yes'){
				$woosheets_sheetID = $woosheets_existingsheetsnames['On Hold Orders'];
				Wc_WooSheets_Setting::move_order($woosheets_order_id, $woosheets_sheetID, 'On Hold Orders');
				
			}
		}
		if($woosheets_old_status == 'pending'){
			if(get_option('pending_orders') == 'yes'){
				$woosheets_sheetID = $woosheets_existingsheetsnames['Pending Orders'];
				Wc_WooSheets_Setting::move_order($woosheets_order_id, $woosheets_sheetID, 'Pending Orders'); 
				
			}
		}
		if($woosheets_old_status == 'cancelled'){
			if(get_option('cancelled_orders') == 'yes'){
				$woosheets_sheetID = $woosheets_existingsheetsnames['Cancelled Orders'];
				Wc_WooSheets_Setting::move_order($woosheets_order_id, $woosheets_sheetID, 'Cancelled Orders');
				
			}
		}
		if($woosheets_old_status == 'refunded'){
			if(get_option('refunded_orders') == 'yes'){
				$woosheets_sheetID = $woosheets_existingsheetsnames['Refunded Orders'];
				Wc_WooSheets_Setting::move_order($woosheets_order_id, $woosheets_sheetID, 'Refunded Orders');
				
			}
		}
		if($woosheets_old_status == 'failed'){
			if(get_option('failed_orders') == 'yes'){
				$woosheets_sheetID = $woosheets_existingsheetsnames['Failed Orders'];
				Wc_WooSheets_Setting::move_order($woosheets_order_id, $woosheets_sheetID, 'Failed Orders');
				
			}
		}
		if($woosheets_old_status == 'completed'){
			if(get_option('completed_orders') == 'yes'){
				$woosheets_sheetID = $woosheets_existingsheetsnames['Completed Orders'];
				Wc_WooSheets_Setting::move_order($woosheets_order_id, $woosheets_sheetID, 'Completed Orders');				
			}
		}
		
		if($woosheets_old_status == 'trash'){
			if(get_option('trash') == 'yes'){
				$woosheets_sheetID = $woosheets_existingsheetsnames['Trash Orders'];
				Wc_WooSheets_Setting::move_order($woosheets_order_id, $woosheets_sheetID, 'Trash Orders');				
			}
		}
		/*Custom Order Status*/
		if(array_key_exists($woosheets_old_status,$woosheets_custom_status_array)){
			if(get_option($woosheets_old_status) == 'yes'){
				$woosheets_sheetID = $woosheets_existingsheetsnames[$woosheets_custom_status_array[$woosheets_old_status]. ' Orders'];
				Wc_WooSheets_Setting::move_order($woosheets_order_id, $woosheets_sheetID, $woosheets_custom_status_array[$woosheets_old_status]. ' Orders');				
			}
		}
		
		if(array_key_exists($woosheets_new_status,$woosheets_custom_status_array)){
			if(get_option($woosheets_new_status) == 'yes'){
				Wc_WooSheets_Setting::getClient();
				$woosheets_sheetname = $woosheets_custom_status_array[$woosheets_new_status]. ' Orders';
				Wc_WooSheets_Setting::insert_data_into_sheet($woosheets_order_id,$woosheets_sheetname);		
			}
		}
		/**/
			
	}
	
	public static function move_order($woosheets_order_id, $woosheets_sheetID, $woosheets_sheetname){
	
		$woosheets_client 			= Wc_WooSheets_Setting::getClient();
		$woosheets_service 			= new Google_Service_Sheets($woosheets_client);
		$woosheets_spreadsheetId 	= get_option('woocommerce_spreadsheet');
		$woosheets_total 			= $woosheets_service->spreadsheets_values->get($woosheets_spreadsheetId, $woosheets_sheetname);
		$woosheets_numRows 			= $woosheets_total->getValues() != null ? count($woosheets_total->getValues()) : 0;
		$woosheets_rangetofind 		= $woosheets_sheetname.'!A1:'.'A'.$woosheets_numRows;
		$woosheets_allentry 		= $woosheets_service->spreadsheets_values->get($woosheets_spreadsheetId, $woosheets_rangetofind);
		$woosheets_data 			= $woosheets_allentry->getValues();
		do_action('woosheets_move_order', $woosheets_order_id, $woosheets_sheetname);
		$woosheets_data = array_map(
			function($woosheets_element)
			{
				if(isset($woosheets_element['0'])){
					return $woosheets_element['0'];
				}else{
					return "";
				}
			}, 
			$woosheets_data
		);
		$woosheets_order 		= wc_get_order( $woosheets_order_id );
		$woosheets_item_count 	= $woosheets_order->get_items();
		$woosheets_num 			= array_search($woosheets_order_id, $woosheets_data);
		if($woosheets_num>0){
			$woosheets_startindex 	= $woosheets_num;
			$woosheets_header_type 	= get_option('header_format');
			if( $woosheets_header_type == 'productwise' ){
				$woosheets_endindex = count($woosheets_item_count);	
				$woosheets_endindex = $woosheets_num+$woosheets_endindex;	
			}else{
				$woosheets_endindex = $woosheets_num+1;	
			}
			
			
			$woosheets_requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
			'requests' => array(
				'deleteDimension' => array(
					'range' => array(
					  'dimension' 	=> 'ROWS',
					  'sheetId' 	=> $woosheets_sheetID,
					  'startIndex' 	=> $woosheets_startindex,
					  'endIndex' 	=> $woosheets_endindex
					)
				)
			)));
					
			$woosheets_response = $woosheets_service->spreadsheets->batchUpdate($woosheets_spreadsheetId, $woosheets_requestBody);
		}	
	}
	
	
	
	public static function is_yith_wapo_active(){
		if ( defined( 'YITH_WAPO' ) ) {
			return true;
		}
		return false;
	}
	public static function is_wooproduct_addon_active(){
		if ( ! class_exists( 'WC_Product_Addons' ) ) {
				return false;
			}
		return true;
	}
	public static function is_woo_subscription_active(){
		 if ( ! class_exists( 'WC_Subscriptions_Product' ) ) {//Woo_Advanced_QTY
				return false;
			}
		return true;
	}	
	public static function is_checkout_fields_pro_active(){
		if( ! class_exists('WCFE_Checkout_Fields_Utils') ){
				return false;
			}
		return true;
	}
	public static function is_checkout_fields_active(){
		if ( ! class_exists( 'WC_Checkout_Field_Editor' ) ) {
				return false;
			}
		return true;
	}
	public static function is_tm_extra_product_options_active(){
		if( ! class_exists( 'TM_Extra_Product_Options' ) && ! class_exists( 'THEMECOMPLETE_Extra_Product_Options' ) ){
				return false;
			}
		return true;
	}
	public static function is_cpo_product_option_active(){
		if( ! class_exists('Uni_Cpo') ){
				return false;
			}
		return true;
	}
	public static function is_wcpa_product_field_active(){
		if( ! class_exists('WCPA_Form') ){
				return false;
			}
		return true;		
	}
	public static function is_wc_bookings_active(){
		if( ! class_exists('WC_Bookings') ){
				return false;
			}
		return true;		
	}
	public static function is_woocommerce_checkout_manager_active(){
		if( ! class_exists('WOOCCM') ){
				return false;
			}
		return true;		
	}
	
	public static function is_stripe_active(){
		if( ! class_exists('WC_Stripe') ){
				return false;
			}
		return true;		
	}
	public static function is_pewc_active(){
		if( ! class_exists('PEWC_Product_Extra_Post_Type') ){
				return false;
			}
		return true;		
	}
	public static function is_wc_order_delivery_active(){
		if( ! class_exists('WC_Order_Delivery') ){
				return false;
			}
		return true;		
	}
	public static function is_woocommerce_delivery_active(){
		if( ! class_exists('WooCommerce_Delivery') ){
				return false;
			}
		return true;	
	}

	public static function is_booking_appointment_active(){
		if( ! class_exists('PH_Bookings_API_Manager') ){
				return false;
			}
		return true;	
	}

		
	
	
	public static function get_yith_fields(){
		    global $wpdb;
			$woosheets_rows 				= $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}yith_wapo_types WHERE del='0' ORDER BY priority ASC" );
			$woosheets_custom_field_header 	= array();
			
			foreach ( $woosheets_rows as $woosheets_key => $woosheets_value ){
				if(!empty($woosheets_value->options)){
					$woosheets_array_options 			= maybe_unserialize( $woosheets_value->options ); 
					$woosheets_custom_field_header[] 	= $woosheets_value->label; 
				}
			}	
			return $woosheets_custom_field_header;
	}
	
	
	public static function insert_data_into_sheet($woosheets_order_id, $woosheets_sheetname, $woosheets_flag = 0){
		
		$woosheets_client 			= Wc_WooSheets_Setting::getClient();
		$woosheets_service 			= new Google_Service_Sheets($woosheets_client);
		$woosheets_spreadsheetId 	= get_option('woocommerce_spreadsheet');
		
		$woosheets_order 		= wc_get_order( $woosheets_order_id );	
		$woosheets_headers_name = get_option('sheet_headers_list');
		$woosheets_header_type 	= get_option('header_format');
		if(!empty($woosheets_spreadsheetId)){
		    $woosheets_prdarray = Wc_WooSheets_Setting::make_value_array('insert',$woosheets_order_id);
			
			do_action( 'woosheets_insert_new_order', $woosheets_order_id, $woosheets_prdarray, $woosheets_sheetname );
			
			if($woosheets_flag == '1')
			   return $woosheets_prdarray;
			 
			if($woosheets_flag == 0){	
				$woosheets_values 	= $woosheets_prdarray;
				$woosheets_sheet 	= "'".$woosheets_sheetname."'!A:A";
				$woosheets_allentry = $woosheets_service->spreadsheets_values->get($woosheets_spreadsheetId, $woosheets_sheet);
				$woosheets_data 	= $woosheets_allentry->getValues();
								
				$woosheets_data = array_map(
					function($woosheets_element)
					{
						if(isset($woosheets_element['0'])){
							return $woosheets_element['0'];
						}else{
							return "";
						}
					}, 
					$woosheets_data
				);
				
				$woosheets_response = $woosheets_service->spreadsheets->get($woosheets_spreadsheetId);
		
				foreach($woosheets_response->getSheets() as $woosheets_key => $woosheets_value) {
					 $woosheets_existingsheetsnames[$woosheets_value['properties']['title']] = $woosheets_value['properties']['sheetId'];
				}
			
				$woosheets_sheetID = $woosheets_existingsheetsnames[$woosheets_sheetname];
				$woosheets_append = 0;
				foreach($woosheets_data as $woosheets_key=>$woosheets_value){
					if(!empty($woosheets_value)){
						if($woosheets_order_id < $woosheets_value){
							$woosheets_append = 1;
							if( $woosheets_header_type == 'productwise' ){
								$woosheets_prdcount 	= count( $woosheets_order->get_items() );
								$woosheets_startIndex 	= $woosheets_key;
								$woosheets_endIndex 	= $woosheets_key + $woosheets_prdcount;
							}else{
								$woosheets_startIndex 	= $woosheets_key;
								$woosheets_endIndex 	= $woosheets_key + 1;
							}
							
							$woosheets_requests = array(
								'insertDimension' => array(
									'range' => array(
										'sheetId' 		=> $woosheets_sheetID,
										'dimension' 	=> "ROWS",
										'startIndex' 	=> $woosheets_startIndex,
										'endIndex' 		=> $woosheets_endIndex
									)
								)
							);
							$woosheets_batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
								'requests' => $woosheets_requests
							));
		
							$woosheets_response 		= $woosheets_service->spreadsheets->batchUpdate($woosheets_spreadsheetId, $woosheets_batchUpdateRequest);
							$woosheets_startIndex 		= $woosheets_startIndex + 1;
							$woosheets_rangetoupdate 	= $woosheets_sheetname.'!A'.$woosheets_startIndex;
							
							$woosheets_requestBody 	= new Google_Service_Sheets_ValueRange(array( 'values' => $woosheets_values ));
							$woosheets_params 		= array( 'valueInputOption' => 'USER_ENTERED' ); 
							$woosheets_response 	= $woosheets_service->spreadsheets_values->update( $woosheets_spreadsheetId, $woosheets_rangetoupdate, $woosheets_requestBody, $woosheets_params );
							break;
						}
					}
				}
				if( $woosheets_append == 0 ){	
					$woosheets_isupdated = 0;
					$woosheets_requestBody = new Google_Service_Sheets_ValueRange( array( 'values' => $woosheets_values ) );
					$woosheets_params = array( 'valueInputOption' => 'USER_ENTERED' ); 			
					if( count($woosheets_data) > 1 ){
						$woosheets_rangetoupdate = $woosheets_sheetname.'!A'.( count($woosheets_data) + 1 );
						$woosheets_response = $woosheets_service->spreadsheets_values->update( $woosheets_spreadsheetId, $woosheets_rangetoupdate, $woosheets_requestBody, $woosheets_params );	
						$woosheets_isupdated = 1;
					}				
					if( $woosheets_isupdated == 0 )
						$woosheets_response = $woosheets_service->spreadsheets_values->append($woosheets_spreadsheetId, $woosheets_sheetname, $woosheets_requestBody, $woosheets_params);	
				}
			}
		}
	}
	
	public static function get_meta_values( $woosheets_meta_key,  $woosheets_post_type = 'product' ) {
		$woosheets_posts = get_posts(
			array(
				'post_type' => $woosheets_post_type,
				'meta_key' => $woosheets_meta_key,
				'posts_per_page' => -1,
			)
		);
	
		$woosheets_meta_values = array();
		foreach( $woosheets_posts as $woosheets_post ) {
			$woosheets_meta_values[] = get_post_meta( $woosheets_post->ID, $woosheets_meta_key, true );
		}
	
		return $woosheets_meta_values;
	
	}
	
	public static function array_flatten($woosheets_array) { 
		  if (!is_array($woosheets_array)) { 
			return FALSE; 
		  } 
		  $woosheets_result = array(); 
		  foreach ($woosheets_array as $woosheets_key => $woosheets_value) { 
			if (is_array($woosheets_value)) { 
			  $woosheets_result = array_merge($woosheets_result, Wc_WooSheets_Setting::array_flatten($woosheets_value)); 
			} 
			else { 
			  $woosheets_result[$woosheets_key] = $woosheets_value; 
			} 
		  } 
		  return $woosheets_result; 
		} 
	/**
	 * Output sheet header settings.
	 */
	public static function woocommerce_admin_field_set_headers() {
	
		$woosheets_header_type = get_option('header_format');
		$woosheets_headers = $woosheets_shipping_fields = $woosheets_billing_fields = $woosheets_additional_fields = array();
		
		$woosheets_prdwise = array( 'Order Number','Order Status','Product ID','Product Image','Product Name','Product Meta','SKU','Product Quantity','Product Base Price','Product Total','Prices Include Tax','Order Currency','Tax Total','Order Total','Order Discount Total','Order Discount Tax','Payment Method','Transaction ID', 'Billing First name','Billing Last Name','Billing Address 1','Billing Address 2','Billing City','Billing State','Billing Postcode','Billing Country','Billing Address','Billing Company Name' );
		
		$woosheets_ordwise = array( 'Order Number','Order Status','Product name(QTY)(SKU)','Prices Include Tax','Order Currency','Tax Total','Order Total','Order Discount Total','Order Discount Tax','Payment Method','Transaction ID', 'Billing First name','Billing Last Name','Billing Address 1','Billing Address 2','Billing City','Billing State','Billing Postcode','Billing Country', 'Billing Address','Billing Company Name' );
		
		if( $woosheets_header_type == 'productwise' ){
			$woosheets_headers = $woosheets_prdwise;
		}else{
			$woosheets_headers = $woosheets_ordwise;
		}
		
		
		if( Wc_WooSheets_Setting::is_checkout_fields_pro_active() ){
			$woosheets_checkout_field = get_option( 'thwcfe_sections', array() );
			
			if(!empty($woosheets_checkout_field)){
				
				if (array_key_exists("billing",$woosheets_checkout_field))
					$woosheets_billingcheckout_field 	= Wc_WooSheets_Setting::get_checkout_field_pro( $woosheets_checkout_field , 'billing');
				if (array_key_exists("shipping",$woosheets_checkout_field))
					$woosheets_shipping_checkout_field 	= Wc_WooSheets_Setting::get_checkout_field_pro( $woosheets_checkout_field , 'shipping');
				if (array_key_exists("additional",$woosheets_checkout_field))
					$woosheets_additional_checkout_field 	= Wc_WooSheets_Setting::get_checkout_field_pro( $woosheets_checkout_field , 'additional');
			}
		}
		
		if( Wc_WooSheets_Setting::is_checkout_fields_active() ){
			$woosheets_billing_fields    	= get_option( 'wc_fields_billing', array() );
			$woosheets_billing_fields 	= Wc_WooSheets_Setting::get_checkout_field( $woosheets_billing_fields );
			
			$woosheets_shipping_fields   	= get_option( 'wc_fields_shipping', array() );
			$woosheets_shipping_fields 	= Wc_WooSheets_Setting::get_checkout_field( $woosheets_shipping_fields );
			
			$woosheets_additional_fields 	= get_option( 'wc_fields_additional', array() );
			$woosheets_additional_fields 	= Wc_WooSheets_Setting::get_checkout_field( $woosheets_additional_fields );
				
		}
		
		//WooCommerce Checkout Manager By QuadLayers
		$woosheets_wcm_billing_fields = $woosheets_wcm_shipping_fields = $woosheets_wcm_additional_fields = array();
		if( Wc_WooSheets_Setting::is_woocommerce_checkout_manager_active() ){
				$woosheets_withoutkey = 0;
				$woosheets_wcm_billing_fields 	= Wc_WooSheets_Setting::woocommerce_checkout_manager_field( 'billing', $woosheets_withoutkey );
				$woosheets_wcm_shipping_fields 	= Wc_WooSheets_Setting::woocommerce_checkout_manager_field( 'shipping', $woosheets_withoutkey );
				$woosheets_wcm_additional_fields 	= Wc_WooSheets_Setting::woocommerce_checkout_manager_field( 'additional', $woosheets_withoutkey );
		}
		
		if( !empty( $woosheets_billing_fields ) ){
			$woosheets_headers = array_merge( $woosheets_headers, $woosheets_billing_fields );
			$woosheets_prdwise = array_merge( $woosheets_prdwise, $woosheets_billing_fields );
			$woosheets_ordwise = array_merge( $woosheets_ordwise, $woosheets_billing_fields );
		}
		
		/*Checkout Fields Pro*/
		if( !empty( $woosheets_billingcheckout_field ) ){
			$woosheets_headers = array_merge( $woosheets_headers, $woosheets_billingcheckout_field );
			$woosheets_prdwise = array_merge( $woosheets_prdwise, $woosheets_billingcheckout_field );
			$woosheets_ordwise = array_merge( $woosheets_ordwise, $woosheets_billingcheckout_field );
		}
		
		//WooCommerce Checkout Manager By QuadLayers
		if( !empty( $woosheets_wcm_billing_fields ) ){
			$woosheets_headers = array_merge( $woosheets_headers, $woosheets_wcm_billing_fields );
			$woosheets_prdwise = array_merge( $woosheets_prdwise, $woosheets_wcm_billing_fields );
			$woosheets_ordwise = array_merge( $woosheets_ordwise, $woosheets_wcm_billing_fields );
		}
		
		$woosheets_shipping_headers = array( 'Shipping First Name','Shipping Last Name','Shipping Address 1','Shipping Address 2','Shipping City','Shipping State','Shipping Postcode','Shipping Country','Shipping Address','Shipping Method Title', 'Shipping Total','Shipping Company Name' );
		$woosheets_headers = array_merge( $woosheets_headers, $woosheets_shipping_headers );
		$woosheets_prdwise = array_merge( $woosheets_prdwise, $woosheets_shipping_headers );
		$woosheets_ordwise = array_merge( $woosheets_ordwise, $woosheets_shipping_headers );
		
		if( !empty( $woosheets_shipping_fields ) ){
			$woosheets_headers = array_merge( $woosheets_headers, $woosheets_shipping_fields );
			$woosheets_prdwise = array_merge( $woosheets_prdwise, $woosheets_shipping_fields );
			$woosheets_ordwise = array_merge( $woosheets_ordwise, $woosheets_shipping_fields );
		}
		/*Checkout Fields Pro*/
		if( Wc_WooSheets_Setting::is_checkout_fields_pro_active() ){
			if( !empty( $woosheets_shipping_checkout_field ) ){
				$woosheets_headers = array_merge( $woosheets_headers, $woosheets_shipping_checkout_field );
				$woosheets_prdwise = array_merge( $woosheets_prdwise, $woosheets_shipping_checkout_field );
				$woosheets_ordwise = array_merge( $woosheets_ordwise, $woosheets_shipping_checkout_field );
			}
		}
		if( !empty( $woosheets_additional_fields ) ){
			$woosheets_headers = array_merge( $woosheets_headers, $woosheets_additional_fields );
			$woosheets_prdwise = array_merge( $woosheets_prdwise, $woosheets_additional_fields );
			$woosheets_ordwise = array_merge( $woosheets_ordwise, $woosheets_additional_fields );
		}
		/*Checkout Fields Pro*/
		if( Wc_WooSheets_Setting::is_checkout_fields_pro_active() ){
			if( !empty( $woosheets_additional_checkout_field ) ){
				$woosheets_headers = array_merge( $woosheets_headers, $woosheets_additional_checkout_field );
				$woosheets_prdwise = array_merge( $woosheets_prdwise, $woosheets_additional_checkout_field );
				$woosheets_ordwise = array_merge( $woosheets_ordwise, $woosheets_additional_checkout_field );
			}			
		}
		//WooCommerce Checkout Manager By QuadLayers
		if( !empty( $woosheets_wcm_shipping_fields ) ){
			$woosheets_headers = array_merge( $woosheets_headers, $woosheets_wcm_shipping_fields );
			$woosheets_prdwise = array_merge( $woosheets_prdwise, $woosheets_wcm_shipping_fields );
			$woosheets_ordwise = array_merge( $woosheets_ordwise, $woosheets_wcm_shipping_fields );
		}
		$woosheets_other_headers = array( 'Coupons Codes','Email','Phone','Customer ID','Customer Note','Order URL','Created Date','Status Updated Date', 'Order Completion Date', 'Order Paid Date', 'Order Notes' );	
			$woosheets_headers = array_merge( $woosheets_headers, $woosheets_other_headers );
			$woosheets_prdwise = array_merge( $woosheets_prdwise, $woosheets_other_headers );
			$woosheets_ordwise = array_merge( $woosheets_ordwise, $woosheets_other_headers );	
			
		if ( class_exists( 'order_delivery_date' ) ) {
			$woosheets_delivery_date = get_option( 'orddd_delivery_date_field_label' );
			if(!empty($woosheets_delivery_date)){
				$woosheets_headers = array_merge($woosheets_headers, array($woosheets_delivery_date));
				$woosheets_prdwise = array_merge($woosheets_prdwise, array($woosheets_delivery_date));
				$woosheets_ordwise = array_merge($woosheets_ordwise, array($woosheets_delivery_date));
			}
		}
		if ( class_exists( 'WC_pdf_functions' ) ) {
			$woosheets_invoice_number = 'Invoice Number';
			if(!empty($woosheets_invoice_number)){
				$woosheets_headers = array_merge($woosheets_headers, array($woosheets_invoice_number));
				$woosheets_prdwise = array_merge($woosheets_prdwise, array($woosheets_invoice_number));
				$woosheets_ordwise = array_merge($woosheets_ordwise, array($woosheets_invoice_number));
			}
		}
		
		if ( class_exists( 'WC_Xero' ) ) {
			$woosheets_xeroinvoice_number = 'Xero Invoice Id';
			if(!empty($woosheets_invoice_number)){
				$woosheets_headers = array_merge($woosheets_headers, array($woosheets_xeroinvoice_number));
				$woosheets_prdwise = array_merge($woosheets_prdwise, array($woosheets_xeroinvoice_number));
				$woosheets_ordwise = array_merge($woosheets_ordwise, array($woosheets_xeroinvoice_number));
			}
		}
		
		
		//WooCommerce Checkout Manager By QuadLayers
		if( !empty( $woosheets_wcm_additional_fields ) ){
			$woosheets_headers = array_merge( $woosheets_headers, $woosheets_wcm_additional_fields );
			$woosheets_prdwise = array_merge( $woosheets_prdwise, $woosheets_wcm_additional_fields );
			$woosheets_ordwise = array_merge( $woosheets_ordwise, $woosheets_wcm_additional_fields );
		}
		// Add attributes to headers list
		$woosheets_attribute_taxonomies = Wc_WooSheets_Setting::_get_all_attributes();
		
		if(!empty($woosheets_attribute_taxonomies)){
			$woosheets_headers = array_merge($woosheets_headers, $woosheets_attribute_taxonomies);
			$woosheets_prdwise = array_merge($woosheets_prdwise, $woosheets_attribute_taxonomies);
			$woosheets_ordwise = array_merge($woosheets_ordwise, $woosheets_attribute_taxonomies);
		 }
		/**/
			
		$woosheets_selections = stripslashes_deep(get_option('sheet_headers_list'));
		if( !$woosheets_selections )
			$woosheets_selections = array();
		
		$woosheets_selections_custom = stripslashes_deep(get_option('sheet_headers_list_custom'));

		if( !$woosheets_selections_custom )
			$woosheets_selections_custom = array();
		/*WooCommerce Product Add on*/
		if( Wc_WooSheets_Setting::is_wooproduct_addon_active() ){
			$woosheets_metavalues = Wc_WooSheets_Setting::_get_all_meta_values();
			if(!empty($woosheets_metavalues)){
				$woosheets_headers = array_merge($woosheets_headers, $woosheets_metavalues);
				$woosheets_prdwise = array_merge($woosheets_prdwise, $woosheets_metavalues);
				$woosheets_ordwise = array_merge($woosheets_ordwise, $woosheets_metavalues);
			}
		}
		/**/
		if(Wc_WooSheets_Setting::is_yith_wapo_active()){
			global $wpdb;
			/*YITH*/
			$woosheets_rows = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}yith_wapo_types WHERE del='0' ORDER BY priority ASC" );
			foreach ( $woosheets_rows as $woosheets_key => $woosheets_value ) :
				if(!empty($woosheets_value->options)){ 
					$woosheets_headers = array_merge($woosheets_headers, array($woosheets_value->label));
					$woosheets_prdwise = array_merge($woosheets_prdwise, array($woosheets_value->label));
					$woosheets_ordwise = array_merge($woosheets_ordwise, array($woosheets_value->label)); 
				}
			endforeach;
		}
		
		/*WooCommerce Subscriptions*/
		if( Wc_WooSheets_Setting::is_woo_subscription_active() ){
			$woosheets_woosubscriptions = array( 'Subscription Price', 'Subscription Sign Up Fee', 'Subscription Period', 'Subscription Period Interval', 'Subscription Length', 'Subscription Trial Period', 'Subscription Trial Length', 'Subscription Limit', 'Subscription One Time Shipping', 'Subscription Payment Sync Date','Subscription Next Payment Date','Subscription End Date' );
			$woosheets_headers = array_merge($woosheets_headers, $woosheets_woosubscriptions);
			$woosheets_prdwise = array_merge($woosheets_prdwise, $woosheets_woosubscriptions);
			$woosheets_ordwise = array_merge($woosheets_ordwise, $woosheets_woosubscriptions); 
		}
		//
		
		/*Woocommerce Exra product option*/
		if( Wc_WooSheets_Setting::is_tm_extra_product_options_active() ){
			$woosheets_filterd 	=array();
			$woosheets_filterd 	= Wc_WooSheets_Setting::get_product_option_field();
			if( !empty( $woosheets_filterd ) ){
				$woosheets_headers = array_merge( $woosheets_headers, $woosheets_filterd );
				$woosheets_prdwise = array_merge( $woosheets_prdwise, $woosheets_filterd );
				$woosheets_ordwise = array_merge( $woosheets_ordwise, $woosheets_filterd );
			}
    	}
		//
		if( Wc_WooSheets_Setting::is_cpo_product_option_active() ){
			$woosheets_cpo_product_option	= Wc_WooSheets_Setting::get_cpo_product_option_field();
			if( !empty( $woosheets_cpo_product_option ) ){
				$woosheets_headers = array_merge( $woosheets_headers, $woosheets_cpo_product_option );
				$woosheets_prdwise = array_merge( $woosheets_prdwise, $woosheets_cpo_product_option );
				$woosheets_ordwise = array_merge( $woosheets_ordwise, $woosheets_cpo_product_option );
			}
		}
		if( Wc_WooSheets_Setting::is_wcpa_product_field_active() ){
			$woosheets_wcpa_product_field	= Wc_WooSheets_Setting::wcpa_product_field();
			if( !empty( $woosheets_wcpa_product_field ) ){
				$woosheets_headers = array_merge( $woosheets_headers, $woosheets_wcpa_product_field );
				$woosheets_prdwise = array_merge( $woosheets_prdwise, $woosheets_wcpa_product_field );
				$woosheets_ordwise = array_merge( $woosheets_ordwise, $woosheets_wcpa_product_field );
			}
		}
		
		if( Wc_WooSheets_Setting::is_wc_bookings_active() ){
			$woosheets_wc_bookings_field	= Wc_WooSheets_Setting::woocommerce_booking_field();
			if( !empty( $woosheets_wc_bookings_field ) ){
				$woosheets_headers = array_merge( $woosheets_headers, $woosheets_wc_bookings_field );
				$woosheets_prdwise = array_merge( $woosheets_prdwise, $woosheets_wc_bookings_field );
				$woosheets_ordwise = array_merge( $woosheets_ordwise, $woosheets_wc_bookings_field );
			}
		}
		
		if( Wc_WooSheets_Setting::is_stripe_active() ){
			$woosheets_stripe_field	= Wc_WooSheets_Setting::woosheets_stripe_field();
			if( !empty( $woosheets_stripe_field ) ){
				$woosheets_headers = array_merge( $woosheets_headers, $woosheets_stripe_field );
				$woosheets_prdwise = array_merge( $woosheets_prdwise, $woosheets_stripe_field );
				$woosheets_ordwise = array_merge( $woosheets_ordwise, $woosheets_stripe_field );
			}
		}
		if( Wc_WooSheets_Setting::is_pewc_active() ){
			$product_field_extra_ultimate	= Wc_WooSheets_Setting::product_field_extra_ultimate();
			if( !empty( $woosheets_stripe_field ) ){
				$woosheets_headers = array_merge( $woosheets_headers, $product_field_extra_ultimate );
				$woosheets_prdwise = array_merge( $woosheets_prdwise, $product_field_extra_ultimate );
				$woosheets_ordwise = array_merge( $woosheets_ordwise, $product_field_extra_ultimate );
			}
		}

		//Order Hours Delivery plugin
		if ( class_exists('Zhours\Setup') ) {
			$woosheets_order_hours = array('Scheduler Shipping Type','Scheduler Shipping Date','Scheduler Shipping Time');
				$woosheets_headers = array_merge($woosheets_headers, $woosheets_order_hours);
				$woosheets_prdwise = array_merge($woosheets_prdwise, $woosheets_order_hours);
				$woosheets_ordwise = array_merge($woosheets_ordwise, $woosheets_order_hours);
		}

		if( Wc_WooSheets_Setting::is_wc_order_delivery_active() ){
			$wc_order_delivery_headers	= Wc_WooSheets_Setting::wc_order_delivery_headers();
			if( !empty( $woosheets_stripe_field ) ){
				$woosheets_headers = array_merge( $woosheets_headers, $wc_order_delivery_headers );
				$woosheets_prdwise = array_merge( $woosheets_prdwise, $wc_order_delivery_headers );
				$woosheets_ordwise = array_merge( $woosheets_ordwise, $wc_order_delivery_headers );
			}
		}

		if( Wc_WooSheets_Setting::is_woocommerce_delivery_active() ){
			$woocommerce_delivery_headers	= Wc_WooSheets_Setting::woocommerce_delivery_headers();
			if( !empty( $woocommerce_delivery_headers ) ){
				$woosheets_headers = array_merge( $woosheets_headers, $woocommerce_delivery_headers );
				$woosheets_prdwise = array_merge( $woosheets_prdwise, $woocommerce_delivery_headers );
				$woosheets_ordwise = array_merge( $woosheets_ordwise, $woocommerce_delivery_headers );
			}
		}

		if( Wc_WooSheets_Setting::is_booking_appointment_active() ){
			$booking_appointment_headers	= Wc_WooSheets_Setting::booking_appointment_headers();
			if( !empty( $booking_appointment_headers ) ){
				$woosheets_headers = array_merge( $woosheets_headers, $booking_appointment_headers );
				$woosheets_prdwise = array_merge( $woosheets_prdwise, $booking_appointment_headers );
				$woosheets_ordwise = array_merge( $woosheets_ordwise, $booking_appointment_headers );
			}
		}

		
		$woosheets_extra_headers = apply_filters( 'woosheets_custom_headers', array() );
		if( !empty( $woosheets_extra_headers ) && is_array( $woosheets_extra_headers ) ){
			$woosheets_headers = array_merge( $woosheets_headers, $woosheets_extra_headers );
			$woosheets_prdwise = array_merge( $woosheets_prdwise, $woosheets_extra_headers );
			$woosheets_ordwise = array_merge( $woosheets_ordwise, $woosheets_extra_headers );		
		}
		$woosheets_headers = stripslashes_deep($woosheets_headers);
		
		global $woosheets_global_headers;
		
		$woosheets_global_headers = $woosheets_headers;
		
		$woosheets_product_selections = stripslashes_deep(get_option('product_sheet_headers_list'));
		if( !$woosheets_product_selections )
			$woosheets_product_selections = array();

		?>
        <tr>
        	<td colspan="2" class="td-woosheets-headers">
        	<div class='woosheets_headers'>
                <label for="sheet_headers"><?php echo esc_html__( "Sheet Headers","woosheets"); ?></label>
                <div id="woosheets-headers-notice">
                <i><strong><?php echo esc_html__( "Note", "woosheets" ); ?>: </strong><?php echo esc_html__( 'All the disabled sheet headers will be deleted from the current spreadsheet automatically. You can enable again, clear spreadsheet and click with below "Click to Sync" button to make up to date data in spreadsheet.',"woosheets"); ?></i>
                </div>
                <ul id="sortable">
                <?php				
					if ( ! empty( $woosheets_selections ) ) {
						
							foreach ( $woosheets_selections as $woosheets_key => $woosheets_val ) {
								
								if( in_array($woosheets_val,$woosheets_product_selections) )
									continue;
								
								$woosheets_is_check = 'checked';
							$woosheets_labelid = strtolower(str_replace(' ','_',$woosheets_val));
				?>				
				  <li class="ui-state-default"><label for="<?php echo $woosheets_labelid; ?>"><span class="ui-icon ui-icon-caret-2-n-s"></span><span class="wootextfield"><?php echo isset($woosheets_selections_custom[$woosheets_key])?$woosheets_selections_custom[$woosheets_key]:$woosheets_val; ?></span><span class="ui-icon ui-icon-pencil"></span><input type="checkbox" name="header_fields_custom[]" value="<?php echo isset($woosheets_selections_custom[$woosheets_key])?$woosheets_selections_custom[$woosheets_key]:$woosheets_val; ?>" class="headers_chk1" <?php echo $woosheets_is_check; ?> hidden="true"><input type="checkbox" name="header_fields[]" value="<?php echo $woosheets_val; ?>" id="<?php echo $woosheets_labelid; ?>" class="headers_chk" <?php echo $woosheets_is_check; ?>><span class="checkbox-switch-new"></span></label></li>
				 <?php 
						}
					}
				
					if ( ! empty( $woosheets_headers ) ) {
							foreach ( $woosheets_headers as $woosheets_key => $woosheets_val ) {
								$woosheets_is_check = '';
								if( in_array( $woosheets_val, $woosheets_selections)){
									continue;
								}
							$woosheets_labelid = strtolower(str_replace(' ','_',$woosheets_val));
				?>				
                  <li class="ui-state-default"><label for="<?php echo $woosheets_labelid; ?>"><span class="ui-icon ui-icon-caret-2-n-s"></span><span class="wootextfield"><?php echo $woosheets_val; ?></span><span class="ui-icon ui-icon-pencil"></span><input type="checkbox" name="header_fields_custom[]" value="<?php echo $woosheets_val; ?>" class="headers_chk1" <?php echo $woosheets_is_check; ?> hidden="true"><input type="checkbox" name="header_fields[]" value="<?php echo $woosheets_val; ?>" id="<?php echo $woosheets_labelid; ?>" class="headers_chk" <?php echo $woosheets_is_check; ?>><span class="checkbox-switch-new"></span></label></li>
                  
                  
                 <?php 
						}
                    }
					
                    ?>
                </ul>
            	<input type="hidden" id="prdwise" value="<?php echo implode(',',$woosheets_prdwise); ?>">
                <input type="hidden" id="ordwise" value="<?php echo implode(',',$woosheets_ordwise); ?>">
                <button type="button" class="woosheets-button woosheets-button-secondary" id="selectall"><?php esc_html_e( 'Select all', 'woosheets' ); ?></button>                
               <button type="button" class="woosheets-button woosheets-button-secondary" id="selectnone"><?php esc_html_e( 'Select none', 'woosheets' ); ?></button>
            </div>
            </td>
        </tr>
	<?php		
	}
	
	public static function woosheets_stripe_field(){
		return array( 'Stripe Fee', 'Stripe Net', 'Stripe Charge Captured', 'Net Revenue From Stripe', 'Stripe Transaction Id' );		
	}
	
	public static function woocommerce_admin_field_product_headers_append_after(){
		global $woosheets_global_headers;
		$woosheets_append_after_array = get_option('woosheets_append_after_array');
		$woosheets_append_after       = get_option('woosheets_append_after');
		
		$woosheets_global_headers = $woosheets_append_after_array?$woosheets_append_after_array:$woosheets_global_headers;
		
		$selected ='';
		?>
		<tr valign="top" class="td-prd-woosheets-headers">
            <th scope="row" class="titledesc">
                <label for="woosheets_append_after"><?php echo esc_html__('Append After','woosheets'); ?> <span class="woocommerce-help-tip" data-tip="Product Headers Append After."></span></label>
            </th>
            <td class="forminp forminp-select">
                <select name="woosheets_append_after" id="woosheets_append_after" style="min-width:150px;" class="">
                	<?php foreach($woosheets_global_headers as $key => $headers) { 
						if( str_replace(' ', '-', strtolower($woosheets_append_after)) == $key || $headers == $woosheets_append_after )
							$selected = 'selected="selected"';
							$headerkey = '';
					?>
                    <option value="<?php echo !is_numeric($key)?$key:$headers; ?>" <?php echo $selected; ?>><?php echo $headers; ?></option>    
                    <?php 
					$selected = '';
					} ?>
                </select> 							
            </td>
    	</tr>
   	<?php
	}
	
	public static function woocommerce_admin_field_product_headers(){
		$woosheets_selections 		 = stripslashes_deep(get_option('product_sheet_headers_list'));
		$woosheets_selections_custom = stripslashes_deep(get_option('product_sheet_headers_list_custom'));
		if( !$woosheets_selections )
			$woosheets_selections = array();
		if( !$woosheets_selections_custom )
			$woosheets_selections_custom = array();
		
		 $args = array(
			'post_type'      => 'product',
			'posts_per_page' => -1,
			
		);
		$woosheets_product_list = array();
		$loop = new WP_Query( $args );
	
		while ( $loop->have_posts() ) : $loop->the_post();
			global $product;
			$woosheets_product_list[] = get_the_title();
		endwhile;
	
		wp_reset_query();
		?>
        <tr class="td-prd-woosheets-headers">
        	<td colspan="2" class="td-woosheets-headers">
        	<div class='woosheets_headers'>
                <label for="sheet_headers"></label>
				<div id="woosheets-headers-notice">
                <i><?php echo esc_html__( "Below all the product names will automatically create columns in spreadsheet with value as product quantity and Append after dropdown will add inbetween all the product names as per your dropdown selection in spreadsheet.","woosheets"); ?></i>
                </div>
                <ul id="product-sortable">
                <?php
				if ( ! empty( $woosheets_selections ) ) {
							foreach ( $woosheets_selections as $woosheets_key => $woosheets_val ) {
								$woosheets_is_check = 'checked';
							$woosheets_labelid = strtolower(str_replace(' ','_',$woosheets_val));
				?>				
				  <li class="ui-state-default"><label for="<?php echo $woosheets_labelid; ?>"><span class="ui-icon ui-icon-caret-2-n-s"></span><span class="wootextfield"><?php echo isset($woosheets_selections_custom[$woosheets_key])?$woosheets_selections_custom[$woosheets_key]:$woosheets_val; ?></span><span class="ui-icon ui-icon-pencil"></span><input type="checkbox" name="product_header_fields_custom[]" value="<?php echo isset($woosheets_selections_custom[$woosheets_key])?$woosheets_selections_custom[$woosheets_key]:$woosheets_val; ?>" class="prdheaders_chk1" <?php echo $woosheets_is_check; ?> hidden="true"><input type="checkbox" name="product_header_fields[]" value="<?php echo $woosheets_val; ?>" id="<?php echo $woosheets_labelid; ?>" class="prdheaders_chk" <?php echo $woosheets_is_check; ?>><span class="checkbox-switch-new"></span></label></li>
				 <?php 
						}
					}
					  
					
				if ( ! empty( $woosheets_product_list ) ) {
							foreach ( $woosheets_product_list as $woosheets_key => $woosheets_val ) {
								$woosheets_is_check = '';
								if( in_array( $woosheets_val, $woosheets_selections)){
									continue;
								}
							$woosheets_labelid = strtolower(str_replace(' ','_',$woosheets_val));
				?>				
                  <li class="ui-state-default"><label for="<?php echo $woosheets_labelid; ?>"><span class="ui-icon ui-icon-caret-2-n-s"></span><span class="wootextfield"><?php echo $woosheets_val; ?></span><span class="ui-icon ui-icon-pencil"></span><input type="checkbox" name="product_header_fields_custom[]" value="<?php echo $woosheets_val; ?>" class="prdheaders_chk1" <?php echo $woosheets_is_check; ?> hidden="true"><input type="checkbox" name="product_header_fields[]" value="<?php echo $woosheets_val; ?>" id="<?php echo $woosheets_labelid; ?>" class="prdheaders_chk" <?php echo $woosheets_is_check; ?>><span class="checkbox-switch-new"></span></label></li>
                 <?php 
						}
                    }
                    ?>
                </ul>
                <button type="button" class="woosheets-button woosheets-button-secondary" id="prdselectall" ><?php esc_html_e( 'Select all', 'woosheets' ); ?></button>                
               <button type="button" class="woosheets-button woosheets-button-secondary" id="prdselectnone" ><?php esc_html_e( 'Select none', 'woosheets' ); ?></button>
            </div>
            </td>
        </tr>
	<?php		
	}
	 
	// Function to check string starting 
	// with given substring 
	public static function woosheets_startsWith ($woosheets_string, $woosheets_startString) 
	{ 
		$woosheets_len = strlen($woosheets_startString); 
		return (substr($woosheets_string, 0, $woosheets_len) === $woosheets_startString); 
	} 
	
	public static function woocommerce_booking_field(){
		$woosheets_booking_field = array('Booking Start Date','Booking End Date','Booking Resource','Booking Cost','Booking Status','Booking Persons','Booking Start Time','Booking End Time');
		return $woosheets_booking_field;
	}
		
	//WooCommerce Checkout Manager By QuadLayers
	public static function woocommerce_checkout_manager_field( $woosheets_field = '', $woosheets_withkey = 0){
			if( $woosheets_field == 'billing' ){
				$woosheets_wooccm_field = get_option('wooccm_billing',true);
			}
			if( $woosheets_field == 'shipping' ){
				$woosheets_wooccm_field = get_option('wooccm_shipping',true);
			}
			if( $woosheets_field == 'additional' ){
				$woosheets_wooccm_field = get_option('wooccm_additional',true);
			}
			
			$woosheets_field_list 			= array();
			$woosheets_field_list_withkey 	= array();
			$woosheets_field_list_withtype	= array();
			
			if(!empty($woosheets_wooccm_field) && is_array($woosheets_wooccm_field) ){
				foreach($woosheets_wooccm_field as $woosheets_fields){
					if($woosheets_fields['type'] == 'heading' )
						continue;
					$woosheets_is_wcmfield = self::woosheets_startsWith( $woosheets_fields['name'],'wooccm' );		
					if( $woosheets_is_wcmfield ){
							$woosheets_field_key = '_'.$woosheets_field.'_'.$woosheets_fields['name'];
							if( !empty($woosheets_fields['label']) ){
								$woosheets_field_list[] 							= $woosheets_fields['label'];	
								$woosheets_field_list_withkey[$woosheets_field_key] 		= $woosheets_fields['label'];	
								$woosheets_field_list_withtype[$woosheets_fields['label']] 	= $woosheets_fields['type'];
							}else{
								$woosheets_field_list[] 							= ucfirst($woosheets_fields['type']);
								$woosheets_field_list_withkey[$woosheets_field_key] 		= ucfirst($woosheets_fields['type']);	
								$woosheets_field_list_withtype[$woosheets_fields['label']] 	= $woosheets_fields['type'];
							}
					}
				}	
			}
			if( $woosheets_withkey == 2 ){
				return $woosheets_field_list_withtype;
			}
			elseif( $woosheets_withkey == 1 ){
				return $woosheets_field_list_withkey;
			}else{
				return $woosheets_field_list;
			}
			
	}
	public static function wcpa_product_field( $woosheets_withkey = 0 ){
		global $wpdb;
		$woosheets_querystr = "SELECT {$wpdb->prefix}postmeta.* FROM {$wpdb->prefix}postmeta INNER JOIN {$wpdb->prefix}posts ON ( {$wpdb->prefix}posts.ID = {$wpdb->prefix}postmeta.post_id ) WHERE 1=1 AND ( {$wpdb->prefix}postmeta.meta_key = '_wcpa_fb-editor-data' ) AND {$wpdb->prefix}posts.post_status='publish'";
		$woosheets_postsmeta = $wpdb->get_results($woosheets_querystr,ARRAY_A);
		$woosheets_wcpa_headers = array();
		$woosheets_wcpa_headers_withkey = array();
		foreach( $woosheets_postsmeta as $woosheets_meta){
			$woosheets_json_encoded = json_decode($woosheets_meta['meta_value']);
			if ($woosheets_json_encoded && is_array($woosheets_json_encoded)) {
				foreach( $woosheets_json_encoded as $woosheets_field_label ){
					if( in_array( $woosheets_field_label->type, array( 'paragraph','header' ) ) )
						continue;
					if( isset( $woosheets_field_label->label ) && !empty( $woosheets_field_label->label ) ){
						$woosheets_wcpa_headers[] 							= $woosheets_field_label->label;	
						$woosheets_wcpa_headers_withkey[$woosheets_field_label->name] 	= $woosheets_field_label->label;
					}elseif( isset( $woosheets_field_label->name ) && !empty($woosheets_field_label->name) ){
						$woosheets_wcpa_headers[] 							= $woosheets_field_label->name;
						$woosheets_wcpa_headers_withkey[$woosheets_field_label->name] 	= $woosheets_field_label->name;
					}
				}
			} 
		}
		if( $woosheets_withkey == 1 ){
			$woosheets_wcpa_headers = $woosheets_wcpa_headers_withkey;		
		}
		return $woosheets_wcpa_headers;
	}
	
	public static function get_checkout_field( $woosheets_checkout_fields = array() ){
			if(!empty($woosheets_checkout_fields)){
				
				$woosheets_checkout_fields = array_map(
					function($element)
					{
						if($element['custom'] == 1)
							return $element['label']?$element['label']:$element['name'];
					}, 
					$woosheets_checkout_fields
				);
			}
			$woosheets_checkout_fields =  array_values(array_filter($woosheets_checkout_fields, function($woosheets_value) { return !is_null($woosheets_value) && $woosheets_value !== ''; }));
			return $woosheets_checkout_fields;
	}
	
	public static function get_checkout_field_pro( $woosheets_checkout_fields = array(), $woosheets_type = '' ){
			$woosheets_headers = array();
			if(!empty($woosheets_checkout_fields[$woosheets_type])){
				foreach($woosheets_checkout_fields[$woosheets_type]->fields as $woosheets_field){
							if( is_numeric($woosheets_field->property_set['custom']) &&  $woosheets_field->property_set['custom'] != 1 && !in_array($woosheets_field->property_set['type'] ,array( 'label','heading' ) )){
								if( !empty($woosheets_field->property_set['label']) ){
									$woosheets_headers[] = $woosheets_field->property_set['label'];
								}else{
									$woosheets_headers[] = $woosheets_field->property_set['name'];
								}
							}
								
					}
				}
			return $woosheets_headers;
	}
	
	public static function get_cpo_product_option_field(){
		$woosheets_cpo_headers = array();
		$woosheets_query = new WP_Query( array( 'post_type' => 'uni_cpo_option', 'posts_per_page' => - 1 , 'orderby' 	=> 'created_date', 'order' 		=> 'ASC','post_status' => 'publish') );
		if ( ! empty( $woosheets_query->posts ) ) {
			$woosheets_slugs_list = wp_list_pluck( $woosheets_query->posts, 'post_name', 'ID' );
			if( !empty($woosheets_slugs_list) ){
				foreach( $woosheets_slugs_list as $woosheets_k => $woosheets_v ){
					$woosheets_cpolabel = get_post_meta( $woosheets_k, '_cpo_general', true );
					if( isset($woosheets_cpolabel['advanced']['cpo_label']) && !empty($woosheets_cpolabel['advanced']['cpo_label']) ){
						$woosheets_cpo_headers['_'.$woosheets_v] = 	$woosheets_cpolabel['advanced']['cpo_label'];
					}else{
						$woosheets_cpo_headers['_'.$woosheets_v] = 	$woosheets_v;	
					}	
				}
			}
		}
		return $woosheets_cpo_headers;			
	}
	
	public static function get_product_option_field(){
			global $wpdb;
			$woosheets_meta_key = 'tm_meta';
 			$woosheets_tm_metas = $wpdb->get_results("SELECT pm.meta_value FROM $wpdb->postmeta as pm,$wpdb->posts as p WHERE pm.meta_key ='$woosheets_meta_key' AND pm.post_id=p.ID AND p.post_status = 'publish'");
			$woosheets_tm_metas = maybe_unserialize( $woosheets_tm_metas );
			
			$woosheets_filterd =array();
			foreach($woosheets_tm_metas as $woosheets_metas){
				$woosheets_field = maybe_unserialize($woosheets_metas->meta_value);
				$woosheets_is_value = false;
				$woosheets_is_key = false;
					foreach($woosheets_field['tmfbuilder'] as $woosheets_key => $woosheets_val ){
								
						if ( ($woosheets_key != 'sections_uniqid') && ($woosheets_key != 'header_uniqid') && ($woosheets_key != 'divider_uniqid') &&  ($woosheets_key != 'variations_uniqid') ) {
							if ( strpos($woosheets_key, '_uniqid') > 0 ){
									$woosheets_pairkey = $woosheets_val;
									$woosheets_is_key = true;
							}
						}
						if ( ($woosheets_key != 'sections_internal_name') && ($woosheets_key != 'header_internal_name') && ($woosheets_key != 'divider_internal_name') && ($woosheets_key != 'variations_internal_name') ) {
							if(preg_match('/_header_title$/', $woosheets_key)){	
									for( $i = 0; $i<count($woosheets_val); $i++ ){
										if(empty($woosheets_val[$i])){
											if(isset($woosheets_tempname[$i]))
												$woosheets_val[$i] = $woosheets_tempname[$i];
											else 
												$woosheets_val[$i] = '';
										}
									}
									$woosheets_pairvalue = $woosheets_val;
									$woosheets_is_value = true;
							}
							if(preg_match('/_internal_name$/', $woosheets_key)){	
								$woosheets_tempname = $woosheets_val;
							}
						}
						if( $woosheets_is_value &&  $woosheets_is_key){
							for( $i = 0; $i<count($woosheets_pairkey); $i++ )
								$woosheets_filterd[$woosheets_pairkey[$i]] = $woosheets_pairvalue[$i];
							$woosheets_is_value = false;
							$woosheets_is_key = false;
						}
				   }
			  }
			  return $woosheets_filterd;
	}

	public static function product_field_extra_ultimate(){
		$posts = get_posts(
	        array(
	            'post_type' => 'product',
	            'meta_key' => 'group_order',
	            'posts_per_page' => -1,
	        )
	    );
	    $meta_values = array();
	    foreach( $posts as $post ) {
	        $meta_values[] = get_post_meta( $post->ID, 'group_order', true );
	    }
	    $field_key_label = array();
	    foreach( $meta_values as $m ) {
	    	$mm = explode(',', $m);
	    	if( is_array($mm)){
	    		foreach ($mm as $key => $value) {
	    			
	    			$field_key = get_post_meta( $value, 'field_ids', true );
	    			foreach ($field_key as $k => $v) {
	    				$field_label = get_post_meta( $v, 'all_params', true );
	    				$field_key_label[] = $field_label['field_label']?$field_label['field_label']:'';	
	    			}
	    		}
	    	}
	    }
		return array_unique($field_key_label);
	}

	public static function wc_order_delivery_headers(){
		return array( 'Delivery Date', 'Schedule Time' );
	}

	public static function woocommerce_delivery_headers(){
		return array( 'WooCommerce Delivery Date', 'WooCommerce Delivery Time', 'WooCommerce Delivery Location' );	
	}

	public static function booking_appointment_headers(){
		return array( 'Booked From','Booked To' );	
	}
	
	public static function get_checkout_field_pro_key( $woosheets_checkout_fields = array() ){
			$woosheets_headers = array();
			if(!empty($woosheets_checkout_fields)){
				foreach($woosheets_checkout_fields as $woosheets_fields){
						foreach( $woosheets_fields->fields as $woosheets_field ){
							if( is_numeric($woosheets_field->property_set['custom']) &&  $woosheets_field->property_set['custom'] != 1 ){
								if( !empty( $woosheets_field->property_set['label'] ) ){
									$woosheets_headers[$woosheets_field->property_set['name']] = $woosheets_field->property_set['label'];	
								}else{
									$woosheets_headers[$woosheets_field->property_set['name']] = $woosheets_field->property_set['name'];	
								}
							}
								
						}
					}
				}
			return $woosheets_headers;
	}
	/**
	 * Syncronization Button
	 */
	public static function woocommerce_admin_field_sync_button() {
		$woosheets_selections = get_option('sheet_headers_list');
		if(!empty($woosheets_selections)){
		?>
		<tr valign="top" id="synctr">
            <th scope="row" class="titledesc">
                <label><?php echo esc_html__( "Sync Orders", "woosheets" ); ?></label>
            </th>

			<td class="forminp">              
              <img src="<?php dirname(__FILE__) ?>images/spinner.gif" id="syncloader"><span id="synctext"><?php echo esc_html__( "Synchronizing...", "woosheets" ); ?></span><a class="woosheets-button" href="javascript:void(0)" id="sync">
					<?php
							esc_html_e( 'Click to Sync', 'woosheets' ); 
					?>
                </a><br><br><i><strong><?php echo esc_html__( "Note", "woosheets" ); ?>:</strong> <?php echo esc_html__( "Click to Sync button will be append all the existing orders to the selected sheets as above.", "woosheets" ); ?></i> 
            </td>
        </tr>
	<?php
		}
	}
	
	public static function woocommerce_admin_field_product_as_sheet_header() { 
		$prdassheetheaders = get_option('prdassheetheaders');	
		if( !$prdassheetheaders )
			$prdassheetheaders = '';
	?>
		<tr valign="top" id="custom-disable-id">
            <th scope="row" class="titledesc">
                <label for="prdassheetheaders"><?php echo esc_html__( "Product Name as sheets headers", "woosheets" ); ?></label>
            </th>
			<td class="forminp custom-onoff">              
              <label for="prdassheetheaders">
			  	<input name="prdassheetheaders" id="prdassheetheaders" type="checkbox" class="" value="yes" <?php if( $prdassheetheaders == 'yes' ){ echo 'checked';} ?>><span class="checkbox-switch"></span> 							
              </label>
            </td>
        </tr>
	<?php
	}
	
	public static function woocommerce_admin_field_custom_headers_action() { 
	?>
		<tr valign="top">
            <th scope="row" class="titledesc">
                <label for="custom_header_action"><?php echo esc_html__( "Custom Static Headers", "woosheets" ); ?></label>
            </th>
			<td class="forminp">              
              <label for="custom_header_action">
			  	<input name="custom_header_action" id="custom_header_action" type="checkbox" class="" value="1"><span class="checkbox-switch"></span> 							
              </label> <br><br>
              <div class="custom-input-div">
	              <input type="text" name="custom_headers_val" id="custom_headers_val" placeholder="Enter Header Name"><br><br>
                  <button class="woosheets-button" type="button" id='add_ctm_val'><?php echo esc_html__( "Add", "woosheets" ); ?></button>
              </div>
                        
               
            </td>
        </tr>
	<?php
	}
	
	public static function woocommerce_admin_field_repeat_checkbox() { 
		$woosheets_is_checked = '';
		$woosheets_is_repeat = get_option('repeat_checkbox');
		if( $woosheets_is_repeat == 'yes')
			$woosheets_is_checked = 'checked';
	?>
		<tr valign="top" class="repeat_checkbox">
            <th scope="row" class="titledesc">
                <label for="id_repeat_checkbox"><?php echo esc_html__( "Allow to copy same columns", "woosheets" ); ?></label>
            </th>
			<td class="forminp">              
              <label for="id_repeat_checkbox">
			  	<input name="repeat_checkbox" id="id_repeat_checkbox" type="checkbox" class="" value="1" <?php echo $woosheets_is_checked; ?>><span class="checkbox-switch"></span> 							
              </label>
              <br><br><i><strong><?php echo esc_html__( "Note", "woosheets" ); ?>:</strong> <?php echo esc_html__( "It will allow to copy same columns into the rows i.e. Billing First Name, Billing Last Name etc.", "woosheets" ); ?> <br><?php echo esc_html__( "For More Details", "woosheets" ); ?> <a href="<?php echo esc_url("http://www.woosheets.creativewerkdesigns.com/Documentation/pluginsetting.html#allowtocopy", "woosheets" ); ?>" target="_blank"> <?php echo esc_html__( "click here.", "woosheets" ); ?></a></i>
            </td>
        </tr>
	<?php
	}
	
	/**
	 * Output new spreadsheet name input field.
	 */
	public static function woocommerce_admin_field_new_spreadsheetname() {
	?>
		
		<tr valign="top" id="newsheet" class="newsheetinput">
            <th scope="row" class="titledesc">
                <label for="sheet_headers"><?php echo esc_html__( "Enter New Spreadsheet name", "woosheets" ); ?></label>
            </th>
            <td class="forminp">
                 <input name="spreadsheetname" id="spreadsheetname" type="text">
            </td>
        </tr>
<?php		
	}
	/**
	 * Output radio button field.
	 */
	public static function woocommerce_admin_field_manage_row_field() {
		$woosheets_header_format = get_option('header_format');
		if(!empty($woosheets_header_format)){
			if( $woosheets_header_format == 'orderwise' ){
				$woosheets_orderwise = "checked='checked' disabled='disabled'";
				$woosheets_productwise = "disabled='disabled'";
			}else{
				$woosheets_productwise = "checked='checked' disabled='disabled'";
				$woosheets_orderwise = "disabled='disabled'";
			}
			$woosheets_disableclass = 'disabled';
		}else{
			$woosheets_orderwise = "";
			$woosheets_productwise = "";
			$woosheets_disableclass = '';
		}
	?>
		<tr valign="top" id="header_format">
            <th scope="row" class="titledesc">
                <label for="sheet_headers"><?php echo esc_html__( "Manage Row Data", "woosheets" ); ?></label>
            </th>
            <td class="forminp radio-box-td ">
                 <input name="header_format" id="orderwise" class="manage-row <?php echo $woosheets_disableclass;  ?>" value="orderwise" type="radio" <?php echo $woosheets_orderwise; ?>><label for="orderwise"><?php echo esc_html__( "Order Wise", "woosheets" ); ?></label> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp; 
                 <input name="header_format" id="productwise" class="manage-row <?php echo $woosheets_disableclass;  ?>" value="productwise" type="radio" <?php echo $woosheets_productwise; ?>><label for="productwise"><?php echo esc_html__( "Product Wise", "woosheets" ); ?></label>
            </td>
        </tr>
<?php		
	}
	
	public static function create_sheet($woosheets_data){ 

		$woosheets_client = Wc_WooSheets_Setting::getClient();
		$woosheets_service = new Google_Service_Sheets($woosheets_client);
		
		if($woosheets_data['woocommerce_spreadsheet'] == 'new'){
			
			$woosheets_newsheetname = trim($woosheets_data['spreadsheetname']); 
			/*
			 *Create new spreadsheet
			 */
			$woosheets_requestBody = new Google_Service_Sheets_Spreadsheet(array(
				 "properties" => array(
					 "title"=> $woosheets_newsheetname
				  ),
		     ));
			$woosheets_response = $woosheets_service->spreadsheets->create($woosheets_requestBody);
			$woosheets_spreadsheetname = $woosheets_response['spreadsheetId'];
		}else{
			$woosheets_spreadsheetname = $woosheets_data['woocommerce_spreadsheet'];
		}
		
		if(!empty($woosheets_data['header_fields'])){
			
			if( isset($woosheets_data['prdassheetheaders']) && isset($woosheets_data['woosheets_append_after']) &&  in_array($woosheets_data['woosheets_append_after'], array_map( function($key) {	return str_replace(' ', '-', strtolower($key));	}, $woosheets_data['header_fields'] ) ) ){
				$flag = 0;
				$woosheets_header = array();
				$woosheets_header_custom = array();
				foreach( $woosheets_data['header_fields'] as $headers){
					$woosheets_header[] = $headers;
					$woosheets_header_custom[] = $woosheets_data['header_fields_custom'][$flag];
					if( str_replace(' ', '-', strtolower($headers)) == $woosheets_data['woosheets_append_after'] && !empty($woosheets_data['product_header_fields']) ){
						foreach( $woosheets_data['product_header_fields'] as $prd_header ){
							$woosheets_header[]	= $prd_header;
						}		
						foreach( $woosheets_data['product_header_fields_custom'] as $prd_header ){
							$woosheets_header_custom[]  = $prd_header;
						}
					}	
					$flag++;
				}		
			}else{
				if( isset($woosheets_data['prdassheetheaders']) && !empty($woosheets_data['product_header_fields']) && isset($woosheets_data['woosheets_append_after']) && !empty($woosheets_data['woosheets_append_after']) ){
					$woosheets_header 			= array_merge( $woosheets_data['header_fields'], $woosheets_data['product_header_fields'] );	
					$woosheets_header_custom 	= array_merge( $woosheets_data['header_fields_custom'], $woosheets_data['product_header_fields_custom'] );	
				}else{
					$woosheets_header 			= $woosheets_data['header_fields'];
					$woosheets_header_custom 	= $woosheets_data['header_fields_custom'];
				}
			}
			$woosheets_headers 		 = stripslashes_deep($woosheets_header);
			$woosheets_header_custom = stripslashes_deep($woosheets_header_custom);
		}else{
			$woosheets_headers 			= stripslashes_deep(get_option('sheet_headers_list'));
			$woosheets_header_custom 	= stripslashes_deep(get_option('sheet_headers_list_custom'));
		}
		
		if(count($woosheets_headers)>0){
			array_unshift($woosheets_headers, "Order Id");
			$woosheets_value = array($woosheets_headers);
		}
		if(count($woosheets_header_custom)>0){
			array_unshift($woosheets_header_custom, "Order Id");
			$woosheets_value_custom = array($woosheets_header_custom);
		}
		$woosheet_remove_sheet = array();
		
		if( isset( $woosheets_data['pending_orders'] ) ) { 
			$woosheets_pendingorder  = $woosheets_data['pending_orders'];
		}else{ 
			$woosheets_pendingorder  = 0; 
			$woosheet_remove_sheet[] = 'Pending Orders';
		}
		
		if( isset( $woosheets_data['processing_orders'] ) ) {
			$woosheets_processingorder = $woosheets_data['processing_orders'];
		}else{ 
		    $woosheets_processingorder = 0; 
			$woosheet_remove_sheet[] = 'Processing Orders';
		}

		if( isset( $woosheets_data['on_hold_orders'] ) ) { 
			$woosheets_onholdorder = $woosheets_data['on_hold_orders'];
		}else{ 
			$woosheets_onholdorder = 0; 
			$woosheet_remove_sheet[] = 'On Hold Orders';
		}

		if( isset( $woosheets_data['completed_orders'] ) ) { 
			$woosheets_completedorders = $woosheets_data['completed_orders'];
		}else{ 
			$woosheets_completedorders = 0; 
			$woosheet_remove_sheet[] = 'Completed Orders';
		}

		if( isset( $woosheets_data['cancelled_orders'] ) ) { 
			$woosheets_cancelledorders = $woosheets_data['cancelled_orders'];
		}else{ 
			$woosheets_cancelledorders = 0; 
			$woosheet_remove_sheet[] = 'Cancelled Orders';
		}

		if( isset( $woosheets_data['refunded_orders'] ) ) { 
			$woosheets_refundedorders = $woosheets_data['refunded_orders'];
		}else{ 
			$woosheets_refundedorders = 0; 
			$woosheet_remove_sheet[] = 'Refunded Orders';
		}

		if( isset( $woosheets_data['failed_orders'] ) ) { 
			$woosheets_failedorders = $woosheets_data['failed_orders'];
		}else{ 
			$woosheets_failedorders = 0; 
			$woosheet_remove_sheet[] = 'Failed Orders';
		}

		if( isset( $woosheets_data['trash'] ) ) { 
			$woosheets_trashorders = $woosheets_data['trash'];
		}else{ 
			$woosheets_trashorders = 0; 
			$woosheet_remove_sheet[] = 'Trash Orders';
		}

		if( isset( $woosheets_data['all_orders'] ) ) { 
			$woosheets_allorders = $woosheets_data['all_orders'];
		}else{ 
			$woosheets_allorders = 0; 
			$woosheet_remove_sheet[] = 'All Orders';
		}
		
		$woosheets_order_array = array( $woosheets_pendingorder, $woosheets_processingorder, $woosheets_onholdorder, $woosheets_completedorders, $woosheets_cancelledorders ,$woosheets_refundedorders, $woosheets_failedorders, $woosheets_trashorders, $woosheets_allorders );
		
		
		$woosheets_sheetnames = array('Pending Orders','Processing Orders','On Hold Orders','Completed Orders','Cancelled Orders','Refunded Orders','Failed Orders','Trash Orders', 'All Orders');
		/* 
		 *Custom Order Status sheet setting 
		 */
		$woosheets_custom_status_array = array();
		$woosheets_status_array = wc_get_order_statuses();
		global $woosheets_default_status;
				
		foreach($woosheets_status_array as $woosheets_key => $woosheets_val){
			$woosheets_status = substr($woosheets_key, strpos($woosheets_key, "-") + 1);
			if(isset($woosheets_data[$woosheets_status])){ 
				$woosheets_order_array[]  = 1;
				$woosheets_sheetnames[] = $woosheets_val. ' Orders';
			}else{
				if( !in_array( $woosheets_status, $woosheets_default_status ) )
					$woosheet_remove_sheet[] = 	$woosheets_val. ' Orders';
			}
		}
		$woosheets_client = Wc_WooSheets_Setting::getClient();
		$woosheets_service = new Google_Service_Sheets($woosheets_client);
		$woosheets_response = $woosheets_service->spreadsheets->get( $woosheets_spreadsheetname );
		foreach($woosheets_response->getSheets() as $woosheets_s) {
			 $woosheets_existingsheets[$woosheets_s['properties']['sheetId']] = $woosheets_s['properties']['title'];
		}	
		/**/
		if($woosheets_data['woocommerce_spreadsheet'] != 'new'){
			for($i = 0; $i<count($woosheets_sheetnames); $i++){
				if(in_array($woosheets_sheetnames[$i], $woosheets_existingsheets)){
					$woosheets_order_array[$i] = 0;
				}else{ 
					if($woosheets_order_array[$i] == '1' && !in_array($woosheets_sheetnames[$i], $woosheets_existingsheets))
						$woosheets_order_array[$i] = 1;
				}	
			}
		} 
		
		for($i = 0; $i<count($woosheets_order_array); $i++)
		{ 

			if( $i == 0 ){ $woosheets_sheetname = 'Pending Orders'; }
			if( $i == 1 ){ $woosheets_sheetname = 'Processing Orders'; } 
			if( $i == 2 ){ $woosheets_sheetname = 'On Hold Orders'; }
			if( $i == 3 ){ $woosheets_sheetname = 'Completed Orders'; }
			if( $i == 4 ){ $woosheets_sheetname = 'Cancelled Orders'; } 
			if( $i == 5 ){ $woosheets_sheetname = 'Refunded Orders'; }
			if( $i == 6 ){ $woosheets_sheetname = 'Failed Orders'; } 
			if( $i == 7 ){ $woosheets_sheetname = 'Trash Orders'; }
			if( $i == 8 ){ $woosheets_sheetname = 'All Orders'; }
			/*Create Custom order sheet*/
			if($i > 8){
				$woosheets_sheetname = $woosheets_sheetnames[$i];
			}
			if( $woosheets_order_array[$i] == '1' ){ 			
				$woosheets_body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
					'requests' => array(
						'addSheet' => array(
					 		'properties' => array(
								'title' => $woosheets_sheetname
							)
						)
					)
				));
			 	$woosheets_response = $woosheets_service->spreadsheets->batchUpdate($woosheets_spreadsheetname,$woosheets_body);
			 	$woosheets_range = trim($woosheets_sheetname).'!A1';
				$woosheets_requestBody = new Google_Service_Sheets_ValueRange(array(
					'values' => $woosheets_value_custom
				));
				
				$woosheets_params = array( 'valueInputOption' => 'USER_ENTERED' ); 
		
				$woosheets_response = $woosheets_service->spreadsheets_values->append($woosheets_spreadsheetname, $woosheets_range, $woosheets_requestBody, $woosheets_params);
			}
		} 
		
		if($woosheets_data['woocommerce_spreadsheet'] == 'new'){
			$woosheets_requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
			'requests' => array(
					'deleteSheet' => array(
						'sheetId' => 0
						)
					)));
			$woosheets_response = $woosheets_service->spreadsheets->batchUpdate($woosheets_spreadsheetname, $woosheets_requestBody);
		}
		
		if($woosheets_data['woocommerce_spreadsheet'] != 'new'){
			
			$requestarray 		= array();
			$deleterequestarray = array();
			
			$woosheets_old_header_order = get_option('sheet_headers_list');
			
			array_unshift($woosheets_old_header_order, "Order Id");
			
			if( $woosheets_old_header_order != $woosheets_headers ) {
				//Delete deactivate column from sheet
				$woosheets_column = array_diff( $woosheets_old_header_order,$woosheets_headers );
				if( !empty( $woosheets_column ) ){
					$woosheets_column = array_reverse( $woosheets_column,true );
					foreach( $woosheets_column as $columnindex => $columnval ){
						unset( $woosheets_old_header_order[$columnindex] );
						$woosheets_old_header_order = array_values( $woosheets_old_header_order );
						
						for( $i = 0; $i<count( $woosheets_sheetnames ); $i++ ){
							
							if( in_array( $woosheets_sheetnames[$i], $woosheets_existingsheets ) ) {
								
								$woosheets_sheetID = array_search( $woosheets_sheetnames[$i], $woosheets_existingsheets );
								
								if( $woosheets_sheetID ){
									$deleterequestarray[] =  array(
									  "deleteDimension" => array(
										"range" => array(
										  "sheetId" => $woosheets_sheetID,
										  "dimension" => "COLUMNS",
										  "startIndex" => $columnindex,
										  "endIndex" => $columnindex + 1
										 )
									   )
									);	
								}
								
							}
						}
					}	
				}
				try{ 
				if( !empty( $deleterequestarray ) ){
					$woosheets_requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array('requests' => array($deleterequestarray)));	
					$woosheets_response = $woosheets_service->spreadsheets->batchUpdate($woosheets_spreadsheetname, $woosheets_requestBody);		
				}
				}catch( Exception $e ){}
			}
			if( $woosheets_old_header_order != $woosheets_headers ) {
				foreach( $woosheets_headers as $key => $hname){
					
					if( $hname == 'Order Id' )
						continue;
					
					$woosheets_startindex = array_search( $hname, $woosheets_old_header_order );
					
					if( $woosheets_startindex !== false && (isset( $woosheets_old_header_order[$key] ) && $woosheets_old_header_order[$key] != $hname )){

						unset($woosheets_old_header_order[$woosheets_startindex]);
						$woosheets_old_header_order =   array_merge(array_slice($woosheets_old_header_order, 0, $key),array(0=>$hname),array_slice($woosheets_old_header_order, $key, count($woosheets_old_header_order) - $key));

						$woosheets_endindex   = $woosheets_startindex + 1;
						$woosheets_destindex  = $key;
						
						for( $i = 0; $i<count($woosheets_sheetnames); $i++ ){
							if( in_array( $woosheets_sheetnames[$i], $woosheets_existingsheets ) ) {
								
								$woosheets_sheetID = array_search( $woosheets_sheetnames[$i], $woosheets_existingsheets );
								
								if( $woosheets_sheetID ){
										$requestarray[] = array(
											'moveDimension' => array(
												'source' => array(
												  'dimension' 	=> 'COLUMNS',
												  'sheetId' 	=> $woosheets_sheetID,
												  'startIndex' 	=> $woosheets_startindex,
												  'endIndex' 	=> $woosheets_endindex
												),
												'destinationIndex' => $woosheets_destindex
												)
											);
								}
							}
						}										
					}else if( $woosheets_startindex === false ){
						
						$woosheets_old_header_order =   array_merge( array_slice( $woosheets_old_header_order, 0, $key ),array( 0 => $hname ), array_slice( $woosheets_old_header_order, $key, count( $woosheets_old_header_order ) - $key ) );
						
						for( $i = 0; $i < count( $woosheets_sheetnames ); $i++ ) {
							if( in_array( $woosheets_sheetnames[$i], $woosheets_existingsheets ) ) {
								
								$woosheets_sheetID = array_search( $woosheets_sheetnames[$i], $woosheets_existingsheets );
								if( $woosheets_sheetID ){
										$requestarray[] =  array(
											  "insertDimension" => array(
												"range" => array(
												  "sheetId" => $woosheets_sheetID,
												  "dimension" => "COLUMNS",
												  "startIndex" => $key,
												  "endIndex" => $key + 1
												),
												"inheritFromBefore"=> true
											)
										);		
								}
							}
						}
					}
				}
				
							
				if( !empty( $requestarray ) ){
					$woosheets_requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array('requests' => array($requestarray)));	
					$woosheets_response = $woosheets_service->spreadsheets->batchUpdate($woosheets_spreadsheetname, $woosheets_requestBody);		
				}
			}
		}
		
		for($i = 0; $i<count($woosheets_sheetnames); $i++){
			if( in_array( $woosheets_sheetnames[$i], $woosheets_existingsheets ) &&  $woosheets_order_array[$i] == '0' ){
				$woosheets_range = trim($woosheets_sheetnames[$i]).'!A1';
				$woosheets_requestBody = new Google_Service_Sheets_ValueRange(array(
					'values' => $woosheets_value_custom
				));
				$woosheets_params = array( 'valueInputOption' => 'USER_ENTERED' ); 
				$woosheets_response = $woosheets_service->spreadsheets_values->update( $woosheets_spreadsheetname, $woosheets_range, $woosheets_requestBody, $woosheets_params );
			}
		}
		
		
		//delete sheet from spreadsheet on deactivate order status
		if( !empty($woosheet_remove_sheet) ){
			self::woosheets_delete_sheet( $woosheets_spreadsheetname, $woosheet_remove_sheet, $woosheets_existingsheets );
		}
		
		if(isset($woosheets_data['freeze_header'])){
			$woosheets_freeze = 1;
		}else{
			$woosheets_freeze = 0;
		}
		
		self::freeze_header($woosheets_spreadsheetname,$woosheets_freeze);
		
		return $woosheets_spreadsheetname;
	
	}
	public static function woosheets_delete_sheet( $woosheets_spreadsheetname, $woosheet_remove_sheet = array() , $woosheets_existingsheets = array() ){
		
		$woosheets_client = Wc_WooSheets_Setting::getClient();
		$woosheets_service = new Google_Service_Sheets($woosheets_client);
		
		foreach( $woosheet_remove_sheet as $woosheets_sheetname ){
			$woosheet_sid = array_search( $woosheets_sheetname, $woosheets_existingsheets );
			$woosheets_requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
			'requests' => array(
					'deleteSheet' => array(
						'sheetId' => $woosheet_sid
						)
					)));
			try{
				$woosheets_response = $woosheets_service->spreadsheets->batchUpdate($woosheets_spreadsheetname, $woosheets_requestBody);	
			}catch( Exception $e ){}
		}
			
	}
	
	public static function freeze_header($woosheets_spreadsheetname,$woosheets_freeze){
	    $woosheets_client = Wc_WooSheets_Setting::getClient();
		$woosheets_service = new Google_Service_Sheets($woosheets_client);
		$woosheets_response = $woosheets_service->spreadsheets->get($woosheets_spreadsheetname);
			
			foreach($woosheets_response->getSheets() as $woosheets_key => $woosheets_value) {
				 $woosheets_existingsheetsnames[$woosheets_value['properties']['title']] = $woosheets_value['properties']['sheetId'];
				 // TODO: Assign values to desired properties of `requestBody`:
				$woosheets_requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
					'requests' => array(
							 "updateSheetProperties" => array(
								 "properties"=> array(
									  "sheetId"=> $woosheets_value['properties']['sheetId'],
									  "gridProperties"=> array(
										 "frozenRowCount" => $woosheets_freeze
									)
								),
								"fields"=> "gridProperties.frozenRowCount"
							)
						)
					)
				);
				try{
					$woosheets_response = $woosheets_service->spreadsheets->batchUpdate($woosheets_spreadsheetname, $woosheets_requestBody);
				}catch(Exception $e){}
			}
			return;	
	}
		
	public static function sync_all_orders(){
			
		$woosheets_client = Wc_WooSheets_Setting::getClient();
		$woosheets_service = new Google_Service_Sheets($woosheets_client);
		$woosheets_spreadsheetId = get_option('woocommerce_spreadsheet');
		
		global $woosheets_default_status_slug;
		$woosheets_order_status_array = $woosheets_default_status_slug;
		/* Custom Order Status*/
		$woosheets_status_array = wc_get_order_statuses();
		$woosheets_status_array['wc-trash'] = 'Trash';

		foreach($woosheets_status_array as $woosheets_key => $woosheets_val){
			if(!in_array($woosheets_key,$woosheets_order_status_array)){ 
				$woosheets_order_status_array[] = $woosheets_key;
				$woosheets_custom_order_status[$woosheets_key] = $woosheets_val;
			}
		}
		$woosheets_existingsheetsnames = array();
		$woosheets_response = $woosheets_service->spreadsheets->get( $woosheets_spreadsheetId );
			foreach( $woosheets_response->getSheets() as $woosheets_key => $woosheets_value ) {
			 	$woosheets_existingsheetsnames[$woosheets_value['properties']['title']] = $woosheets_value['properties']['sheetId'];
		}
		/**/
		for( $i = 0; $i<count($woosheets_order_status_array); $i++ ){ 
			$woosheets_sheetname = '';
			if( ($i == '0') && (get_option('pending_orders') == 'yes') ){
				$woosheets_sheetname = 'Pending Orders';							
			}elseif( ($i == '1') && (get_option('processing_orders') == 'yes') ){
				$woosheets_sheetname = 'Processing Orders';	
			}elseif( ($i == '2') && (get_option('on_hold_orders') == 'yes') ){
				$woosheets_sheetname = 'On Hold Orders';
			}elseif( ($i == '3') && (get_option('completed_orders') == 'yes') ){
				$woosheets_sheetname = 'Completed Orders';
			}elseif( ($i == '4') && (get_option('cancelled_orders') == 'yes') ){
				$woosheets_sheetname = 'Cancelled Orders';
			}elseif( ($i == '5') && (get_option('refunded_orders') == 'yes') ){
				$woosheets_sheetname = 'Refunded Orders';
			}elseif( ($i == '6') && (get_option('failed_orders') == 'yes') ){
				$woosheets_sheetname = 'Failed Orders'; 
			}
			
			if( $i > 6 ){
				$woosheets_status = substr($woosheets_order_status_array[$i], strpos($woosheets_order_status_array[$i], "-") + 1); 
					if(get_option($woosheets_status) == 'yes'){
						$woosheets_sheetname = $woosheets_custom_order_status[$woosheets_order_status_array[$i]] . ' Orders';	
					}
			}
			if($woosheets_order_status_array[$i] == 'wc-trash'){ $woosheets_order_status_array[$i] = 'trash'; }
						
			if(!empty($woosheets_sheetname)){
				$woosheets_activesheets[$woosheets_order_status_array[$i]] = $woosheets_sheetname;
				$woosheets_getactivesheets[] = "'".$woosheets_sheetname."'!A:A";
			}
		}
		
		if( get_option('all_orders') == 'yes') {
			$woosheets_getactivesheets[] = "'All Orders'!A:A";
			$woosheets_activesheets['all_order'] = 'All Orders';
		}

		/*Get First Column Value from all sheets*/
		try{
			$woosheets_response = $woosheets_service->spreadsheets_values->batchGet( $woosheets_spreadsheetId, array( 'ranges' => $woosheets_getactivesheets ) );
		}
		catch(Exception $e) {
		  echo 'Message: ' .$e->getMessage();
		}
		$woosheets_existingorders = array();
		foreach ( $woosheets_response->getValueRanges() as $woosheets_order ) {
			$woosheets_rangetitle = explode("'!A",$woosheets_order->range);
			$woosheets_sheettitle = str_replace("'",'',$woosheets_rangetitle[0]);
			$woosheets_data = array_map(
				function($woosheets_element)
				{
					if(isset($woosheets_element['0'])){
						return $woosheets_element['0'];
					}else{
						return "";
					}
				}, 
				$woosheets_order->values
			);
			$woosheets_existingorders[$woosheets_sheettitle] = $woosheets_data;
		}
		
		/*----------------------------------------*/
			$woosheets_dataarray = array();
			$woosheets_isexecute = 0;
			foreach($woosheets_activesheets as $woosheets_sheet_slug => $woosheets_sheetname){

					if( $woosheets_sheetname == 'All Orders' ){
						$woosheets_query_args = array(
							'post_type'      => 'shop_order',
							'posts_per_page' => 999999999999,
							'order' => 'ASC',
							'post_status'	=> 'any'
						);
					}else{
					 	$woosheets_query_args = array(
							'post_type'      => 'shop_order',
							'post_status'    => $woosheets_sheet_slug,
							'posts_per_page' => 999999999999,
							'order' => 'ASC'
						);
					}							
					$woosheets_all_orders = get_posts( $woosheets_query_args );
					
					if( empty($woosheets_all_orders) )
						continue;
					$woosheets_values_array = array();
					
					foreach ( $woosheets_all_orders as $woosheets_order ) {
						
						if( in_array( $woosheets_order->ID, $woosheets_existingorders[$woosheets_sheetname])){
							continue;	
						}
						set_time_limit(999);
						$woosheets_order = wc_get_order( $woosheets_order->ID );
						$woosheets_order_data = $woosheets_order->get_data(); 
						$woosheets_status = $woosheets_order_data['status'];
						$woosheets_value = Wc_WooSheets_Setting::make_value_array('insert',$woosheets_order->get_id());
						$woosheets_values_array = array_merge($woosheets_values_array, $woosheets_value); 						
					}
					if(!empty( $woosheets_values_array ) ){
							try{
							$woosheets_requestBody = new Google_Service_Sheets_ValueRange(array( 'values' => $woosheets_values_array ));
							$woosheets_params = array( 'valueInputOption' => 'USER_ENTERED' ); 
							$woosheets_response = $woosheets_service->spreadsheets_values->append($woosheets_spreadsheetId, $woosheets_sheetname, $woosheets_requestBody, $woosheets_params);
							}catch(Exception $e) {
							  echo 'Message: ' .$e->getMessage();
							}	
					}
				}
			echo 'successful';
			die;
		}	
		public static function check_existing_sheet()
		{
			$woosheets_client = Wc_WooSheets_Setting::getClient();
			$woosheets_service = new Google_Service_Sheets($woosheets_client);
			$woosheets_sheetnames = array('Pending Orders','Processing Orders','On Hold Orders','Completed Orders','Cancelled Orders','Refunded Orders','Failed Orders','Trash Orders');
			if($_POST['id'] != 'new'){
				$woosheets_exist = 0;
				$woosheets_response = $woosheets_service->spreadsheets->get($_POST['id']);
				foreach($woosheets_response->getSheets() as $woosheets_s) {
					 $woosheets_existingsheets = $woosheets_s['properties']['title'];	
					 if(in_array($woosheets_existingsheets,$woosheets_sheetnames)) {
						 $woosheets_exist = 1;
						 break;
					}
				}
				if( $woosheets_exist ){
					echo 'successful';
					die(); 	
				}
			} 
			die();
		}
		
		public static function wc_woocommerce_process_post_meta( $woosheets_post_id, $woosheets_post ){
			Wc_WooSheets_Setting::getClient();
			Wc_WooSheets_Setting::wc_woocommerce_update_post_meta( $woosheets_post_id, $woosheets_post ); 
		}
		
		public static function wc_woocommerce_update_post_meta( $woosheets_post_id, $woosheets_post ) {
			if($woosheets_post->post_type != 'shop_order')
				return;
			$woosheets_client = Wc_WooSheets_Setting::getClient();
			$woosheets_service = new Google_Service_Sheets($woosheets_client);
			$woosheets_order = wc_get_order( $woosheets_post->ID );	
			$woosheets_spreadsheetId = get_option('woocommerce_spreadsheet');
			$woosheets_headers_name = get_option('sheet_headers_list');
			$woosheets_header_type = get_option('header_format');
			if(!empty($woosheets_spreadsheetId) && ($_REQUEST['order_status'] == $_REQUEST['post_status'])){
							
				
				$woosheets_values = Wc_WooSheets_Setting::make_value_array('update',$woosheets_order->get_id() );
				
				do_action( 'woosheets_update_order', $woosheets_order->get_id(), $woosheets_values, $_REQUEST['order_status']  );
				
				$woosheets_response = $woosheets_service->spreadsheets->get($woosheets_spreadsheetId);
		
				foreach($woosheets_response->getSheets() as $woosheets_key => $woosheets_value) {
					 $woosheets_existingsheetsnames[$woosheets_value['properties']['title']] = $woosheets_value['properties']['sheetId'];
				}
				$woosheets_sheetname = '';
				if($_REQUEST['order_status'] == 'wc-pending'){
					if(get_option('pending_orders') == 'yes'){
						$woosheets_sheetID = $woosheets_existingsheetsnames['Pending Orders'];
						$woosheets_sheetname = 'Pending Orders';
					}
				}
				if($_REQUEST['order_status'] == 'wc-processing'){
					if(get_option('processing_orders') == 'yes'){
						$woosheets_sheetID = $woosheets_existingsheetsnames['Processing Orders'];
						$woosheets_sheetname = 'Processing Orders';
					}	
				}
				if($_REQUEST['order_status'] == 'wc-on-hold'){
					if(get_option('on_hold_orders') == 'yes'){
						$woosheets_sheetID = $woosheets_existingsheetsnames['On Hold Orders'];
						$woosheets_sheetname = 'On Hold Orders';	
					}
				}
				if($_REQUEST['order_status'] == 'wc-failed'){
					if(get_option('failed_orders') == 'yes'){
						$woosheets_sheetID = $woosheets_existingsheetsnames['Failed Orders'];
						$woosheets_sheetname = 'Failed Orders';
					}
				}
				if($_REQUEST['order_status'] == 'wc-completed'){
					if(get_option('completed_orders') == 'yes'){
						$woosheets_sheetID = $woosheets_existingsheetsnames['Completed Orders'];
						$woosheets_sheetname = 'Completed Orders';
					}
				}	
				if($_REQUEST['order_status'] == 'wc-cancelled'){
					if(get_option('cancelled_orders') == 'yes'){
						$woosheets_sheetID = $woosheets_existingsheetsnames['Cancelled Orders'];
						$woosheets_sheetname = 'Cancelled Orders';
					}			
				}
				if($_REQUEST['order_status'] == 'wc-refunded'){
					if(get_option('refunded_orders') == 'yes'){
						$woosheets_sheetID = $woosheets_existingsheetsnames['Refunded Orders'];
						$woosheets_sheetname = 'Refunded Orders';
					}	
				}
				
				/* Custom Order Status*/
				if(empty($woosheets_sheetname)){
					$woosheets_custom_status_array = array();
					$woosheets_status_array = wc_get_order_statuses();
					global $woosheets_default_status_slug;
					foreach($woosheets_status_array as $woosheets_key => $woosheets_val){
						
						if(!in_array($woosheets_key,$woosheets_default_status_slug)){ 
						
							if($woosheets_key == $_REQUEST['order_status']){
								$woosheets_status = substr($woosheets_key, strpos($woosheets_key, "-") + 1); 
								if(get_option($woosheets_status) == 'yes'){
									$woosheets_sheetID = $woosheets_existingsheetsnames[$woosheets_val];
									$woosheets_sheetname = $woosheets_val. ' Orders';	
								}
							}
						}
					}
				}
				
				if( !empty($woosheets_sheetname) ){
				/**/
					$woosheets_total = $woosheets_service->spreadsheets_values->get($woosheets_spreadsheetId, $woosheets_sheetname);
					$woosheets_numRows = $woosheets_total->getValues() != null ? count($woosheets_total->getValues()) : 0;
					$woosheets_rangetofind = $woosheets_sheetname.'!A1:'.'A'.$woosheets_numRows;
					$woosheets_allentry = $woosheets_service->spreadsheets_values->get($woosheets_spreadsheetId, $woosheets_rangetofind);
					$woosheets_data = $woosheets_allentry->getValues();
					$woosheets_data = array_map(
						function($element)
						{
							if(isset($element['0'])){
								return $element['0'];
							}else{
								return "";
							}
						}, 
						$woosheets_data
					);
					
					
					$woosheets_num = array_search($woosheets_order->get_id(), $woosheets_data);
				
					if( $woosheets_num > 0 ){
					$woosheets_num = $woosheets_num + 1;
					//Add or Remove Row at spreadsheet
					$woosheets_ordrow = 0;
					$woosheets_notempty = 0;
					end($woosheets_data);         
					$woosheets_lastElement = key($woosheets_data); 
					reset($woosheets_data);
					
					for( $i = $woosheets_num ; $i < count($woosheets_data); $i++ ){
						if(  $woosheets_data[$i] == $woosheets_order->get_id() ){
							$woosheets_ordrow++; 
							if( $woosheets_lastElement == $i ){
								$woosheets_ordrow++;		
							}
						}else{
							if( $woosheets_lastElement == $i ){
								$woosheets_notempty = 1;	
								if( $woosheets_ordrow > 0)
									$woosheets_ordrow++;
							}else{
								$woosheets_ordrow++;	
							}
							break; 
						}
					}
					
					$woosheets_samerow = 0;
					if( $woosheets_ordrow == 0 ){
						$woosheets_samerow = 1;
					}
					
					if( $woosheets_samerow == 1 && $woosheets_header_type == "productwise" && $woosheets_notempty == 0 ){
						$woosheets_alphabet = range('A', 'Z');
						$woosheets_alphaindex = '';
						$woosheets_isID = array_search('Product ID', $woosheets_headers_name);
						if( $woosheets_isID ){
							$woosheets_alphaindex = $woosheets_alphabet[$woosheets_isID+1];	
						}else{
							$woosheets_isName = array_search('Product Name', $woosheets_headers_name);
							if( $woosheets_isName ){
								$woosheets_alphaindex = $woosheets_alphabet[$woosheets_isName+1];	
							}		
						}
						if( $woosheets_alphaindex != '' ){
							$woosheets_rangetofind = $woosheets_sheetname.'!'.$woosheets_alphaindex.$woosheets_num.':'.$woosheets_alphaindex;
							$woosheets_allentry = $woosheets_service->spreadsheets_values->get($woosheets_spreadsheetId, $woosheets_rangetofind);
							$woosheets_data = $woosheets_allentry->getValues();
							
							$woosheets_data = array_map(
								function( $woosheets_element )
								{
									if( isset( $woosheets_element['0'] ) ){
										return $woosheets_element['0'];
									}else{
										return "";
									}
								}, 
								$woosheets_data
							);
							if( ( count( $woosheets_values ) < count( $woosheets_data ) )){
								$woosheets_ordrow = count($woosheets_data);	
								$woosheets_samerow = 0;
							}
						}
						
					}
					if( $woosheets_notempty == 1 && $woosheets_ordrow == 0 ){
						 $woosheets_samerow = 0;
						 $woosheets_ordrow = 1;
					}
						
					if( ( count( $woosheets_values ) > $woosheets_ordrow ) && $woosheets_samerow == 0 ){//Insert blank row into spreadsheet
							$woosheets_endIndex = count( $woosheets_values ) - (int)$woosheets_ordrow;
							$woosheets_endIndex = (int)$woosheets_endIndex + (int)$woosheets_num;
							$woosheets_requests = array(
								'insertDimension' => array(
									'range' => array(
										'sheetId' => $woosheets_sheetID,
										'dimension' => "ROWS",
										'startIndex' => $woosheets_num,
										'endIndex' => $woosheets_endIndex
									)
								)
							);
						$woosheets_batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
							'requests' => $woosheets_requests
						));
						$woosheets_response = $woosheets_service->spreadsheets->batchUpdate( $woosheets_spreadsheetId, $woosheets_batchUpdateRequest );	
					
					}elseif( count( $woosheets_values ) < $woosheets_ordrow && $woosheets_samerow == 0){//Remove extra row from spreadhseet
						$woosheets_endIndex =  (int)$woosheets_ordrow - count( $woosheets_values );	
						$woosheets_endIndex = (int)$woosheets_endIndex + (int)$woosheets_num;				
						$woosheets_requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
						'requests' => array(
								'deleteDimension' => array(
									'range' => array(
									  'dimension' => 'ROWS',
									  'sheetId' => $woosheets_sheetID,
									  'startIndex' => $woosheets_num,
									  'endIndex' => $woosheets_endIndex
									)
								)
							)));	
						$woosheets_response = $woosheets_service->spreadsheets->batchUpdate( $woosheets_spreadsheetId, $woosheets_requestBody );					
					}
						//End of add- remove row at spreadsheet
						
						$woosheets_rangetoupdate = $woosheets_sheetname.'!A'.$woosheets_num;
						$woosheets_requestBody = new Google_Service_Sheets_ValueRange(array(
							'values' => $woosheets_values
						));
						$woosheets_params = array( 'valueInputOption' => 'USER_ENTERED' ); //USER_ENTERED
							$woosheets_response = $woosheets_service->spreadsheets_values->update( $woosheets_spreadsheetId, $woosheets_rangetoupdate, $woosheets_requestBody, $woosheets_params );	
						
					}
				}
				//All Order
				if(get_option('all_orders') == 'yes'){
					$woosheets_sheetname = 'All Orders';
					Wc_WooSheets_Setting::woosheets_all_orders( $woosheets_order->get_id(), $woosheets_sheetname );
				}
			}
		}
		public static function getItemmeta( $woosheets_item = '' ){
			$woosheets_meta_html = '';
			if(!empty($woosheets_item)){
				$woosheets_variationame = '';
				if($woosheets_item->get_variation_id()){
					$woosheets_variation = wc_get_product($woosheets_item->get_variation_id());
					$woosheets_variationame = strip_tags($woosheets_variation->get_formatted_name());
				} 
				$woosheets_meta_html  .= 'Variation Name: '.$woosheets_variationame.'('.$woosheets_item->get_variation_id().')'.","; // the Variation id
				if($woosheets_item->get_tax_class()) $woosheets_meta_html  .= 'Tax Class:'.$woosheets_item->get_tax_class().",";
				$woosheets_meta_html  .= 'Line subtotal:'.$woosheets_item->get_subtotal().","; // Line subtotal (non discounted)
				if($woosheets_item->get_subtotal_tax()) $woosheets_meta_html 	.= 'Line subtotal tax:'.$woosheets_item->get_subtotal_tax().","; // Line subtotal tax (non discounted)
				$woosheets_meta_html  .= 'Line total:'.$woosheets_item->get_total().","; // Line total (discounted)
				if($woosheets_item->get_total_tax()) $woosheets_meta_html  .= 'Line total tax:'.$woosheets_item->get_total_tax(); // Line total tax (discounted)
			}
    		$woosheets_meta_html = rtrim( $woosheets_meta_html , ',');
			return $woosheets_meta_html;
		}
		
		public static function woosheets_add_cart_item_data( $woosheets_cart_item_data, $woosheets_product_id ) {
			if(!Wc_WooSheets_Setting::is_wooproduct_addon_active()){
				return $woosheets_cart_item_data;		
			}
			if ( isset( $_POST ) && ! empty( $woosheets_product_id ) ) {
				$woosheets_post_data = $_POST;
			} else {
				return;
			}
			$woosheets_metavalues = Wc_WooSheets_Setting::_get_all_meta_values();
			
			$woosheets_product_addons = WC_Product_Addons_Helper::get_product_addons( $woosheets_product_id );
			if ( empty( $woosheets_cart_item_data['woosheets_headers'] ) ) {
					$woosheets_cart_item_data['woosheets_headers'] = array();
				}
			if ( is_array( $woosheets_product_addons ) && ! empty( $woosheets_product_addons ) ) {
				foreach ( $woosheets_product_addons as $woosheets_addon ) {
					
					// If type is heading, skip.
					if ( 'heading' === $woosheets_addon['type'] ) {
						continue;
					}
					
					$woosheets_value = isset( $woosheets_post_data[ 'addon-' . $woosheets_addon['field_name'] ] ) ? $woosheets_post_data[ 'addon-' . $woosheets_addon['field_name'] ] : '';
					$woosheets_key = strtolower(str_replace(' ','_',$woosheets_addon['name']));
					if ( is_array( $woosheets_value ) ) {
						$woosheets_value = array_map( 'stripslashes', $woosheets_value );
					} else {
						$woosheets_value = stripslashes( $woosheets_value );
					}
					
					$woosheets_data[$woosheets_addon['field_name']] = $woosheets_addon['name'];
					$woosheets_cart_item_data['woosheets_headers'] = array_merge( $woosheets_cart_item_data['woosheets_headers'], apply_filters( 'woocommerce_product_addon_cart_item_data', $woosheets_data, $woosheets_addon, $woosheets_product_id, $woosheets_post_data ) );	
				}	
			}
			return $woosheets_cart_item_data;
		}
		
		/**
		 * Include add-ons line item meta.
		 *
		 * @param  WC_Order_Item_Product $item          Order item data.
		 * @param  string                $cart_item_key Cart item key.
		 * @param  array                 $values        Order item values.
		 */
		public static function woosheets_order_line_item( $woosheets_item, $woosheets_cart_item_key, $woosheets_values ) {
			if( Wc_WooSheets_Setting::is_wooproduct_addon_active() ){
				if ( ! empty( $woosheets_values['addons'] ) ) {
					
					foreach ( $woosheets_values['addons'] as $woosheets_addon ) {
						$woosheets_key           = $woosheets_addon['name'];
						$woosheets_price_type    = $woosheets_addon['price_type'];
						$woosheets_product       = $woosheets_item->get_product();
						$woosheets_product_price = $woosheets_product->get_price();
						$woosheets_price         = html_entity_decode( strip_tags( wc_price( WC_Product_Addons_Helper::get_product_addon_price_for_display( $woosheets_addon['price'], $woosheets_values['data'], true ) ) ) );
						/*
						 * For percentage based price type we want
						 * to show the calculated price instead of
						 * the price of the add-on itself and in this
						 * case its not a price but a percentage.
						 * Also if the product price is zero, then there
						 * is nothing to calculate for percentage so
						 * don't show any price.
						 */
						if ( $woosheets_addon['price'] && 'percentage_based' === $woosheets_price_type && 0 != $woosheets_product_price ) {
							$woosheets_price = html_entity_decode( strip_tags( wc_price( WC_Product_Addons_Helper::get_product_addon_price_for_display( ( $woosheets_product_price * ( $woosheets_addon['price'] / 100 ) ) ) ) ) );
						}
						if ( 'custom_price' === $woosheets_addon['field_type'] ) {
							$woosheets_addon['value'] = $woosheets_addon['price'];
						}
						
						
						$woosheets_key = strtolower(str_replace(' ','_',$woosheets_addon['name']));
							if ( is_array( $woosheets_value ) ) {
								$woosheets_value = array_map( 'stripslashes', $woosheets_value );
							} else {
								$woosheets_value = stripslashes( $woosheets_value );
							}
						$woosheets_item->add_meta_data( $woosheets_addon['field_name'], $woosheets_addon['value'] );
					}
				}
				if(!empty($woosheets_values['woosheets_headers'])){
					$woosheets_item->add_meta_data( 'woosheets_headers', $woosheets_values['woosheets_headers'] );		
				}
			}
	}
	/**
	 * Get cart item from session.
	 *
	 * @param array $cart_item Cart item data.
	 * @param array $values    Cart item values.
	 * @return array
	 */
	public static function woosheets_get_cart_item_from_session( $woosheets_cart_item, $woosheets_values ) {
		
		if ( ! empty( $woosheets_values['woosheets_headers'] ) )
			$woosheets_cart_item['woosheets_headers'] = $woosheets_values['woosheets_headers'];
		return $woosheets_cart_item;
		 
	}
	
	public static function make_value_array( $woosheets_operation = 'insert', $woosheets_order_id = 0 ){
			
			$woosheets_order 			= wc_get_order( $woosheets_order_id );	
			$woosheets_order_data 		= $woosheets_order->get_data();

			$woosheets_filterd 			= array();
			$woosheets_custfieldkeys 	= array();
			if( Wc_WooSheets_Setting::is_tm_extra_product_options_active() ){			
				$woosheets_filterd 	= Wc_WooSheets_Setting::get_product_option_field();
			}
			/*WooCommerce Product Add on*/
			if(Wc_WooSheets_Setting::is_wooproduct_addon_active()){
				$woosheets_cf_prdoduct_addon = array();
				$woosheets_metavalues = Wc_WooSheets_Setting::_get_all_meta_values();
				if(!empty($woosheets_metavalues)){
					$woosheets_cf_prdoduct_addon = array_merge($woosheets_cf_prdoduct_addon, $woosheets_metavalues);
				}
			}
			/**/
			
			if ( class_exists( 'order_delivery_date' ) ) {
				$woosheets_delivery_date 	= '';
				$woosheets_delivery_label 	= get_option( 'orddd_delivery_date_field_label' );
				if(!empty($woosheets_delivery_label))
					$woosheets_delivery_date = get_post_meta( $woosheets_order_data['id'], $woosheets_delivery_label , true );		
			}
			
			$woosheets_items = $woosheets_order->get_items();	
			
			//custom field headers for YITH
			if(Wc_WooSheets_Setting::is_yith_wapo_active()){
				$woosheets_custom_field_header = Wc_WooSheets_Setting::get_yith_fields();
			}
			
			//
			$woosheets_woosubscriptions = array();
			/* WooCommerce Subscription*/
			if( Wc_WooSheets_Setting::is_woo_subscription_active() ){
				$woosheets_woosubscriptions = array( 'Subscription Price', 'Subscription Sign Up Fee', 'Subscription Period', 'Subscription Period Interval', 'Subscription Length', 'Subscription Trial Period', 'Subscription Trial Length', 'Subscription Limit', 'Subscription One Time Shipping', 'Subscription Payment Sync Date','Subscription Next Payment Date','Subscription End Date' );
			}
			
			/*Checkout Fields Header*/
			$woosheets_shipping_fields = $woosheets_billing_fields  = $woosheets_additional_fields = array();
			if( Wc_WooSheets_Setting::is_checkout_fields_active() ){	
				$woosheets_billing_fields    	= get_option( 'wc_fields_billing', array() );
				$woosheets_billing_fields 		= Wc_WooSheets_Setting::get_checkout_field( $woosheets_billing_fields );
				
				$woosheets_shipping_fields   	= get_option( 'wc_fields_shipping', array() );
				$woosheets_shipping_fields 		= Wc_WooSheets_Setting::get_checkout_field( $woosheets_shipping_fields );
				
				
				$woosheets_additional_fields 	= get_option( 'wc_fields_additional', array() );
				$woosheets_additional_fields 	= Wc_WooSheets_Setting::get_checkout_field( $woosheets_additional_fields );
				
				$woosheets_checkoutfields   	= wc_get_custom_checkout_fields( $woosheets_order );
			}
			
			//WooCommerce Checkout Manager By QuadLayers
			$woosheets_wcm_billing_fields = $woosheets_wcm_shipping_fields = $woosheets_wcm_additional_fields = $woosheets_wcm_headers_list = $woosheets_wcm_headers_list_withtype = array();
			if( Wc_WooSheets_Setting::is_woocommerce_checkout_manager_active() ){
					$woosheets_withkey = 1;
					$woosheets_wcm_billing_fields 		= Wc_WooSheets_Setting::woocommerce_checkout_manager_field( 'billing', $woosheets_withkey );
					$woosheets_wcm_shipping_fields 		= Wc_WooSheets_Setting::woocommerce_checkout_manager_field( 'shipping', $woosheets_withkey );
					$woosheets_wcm_additional_fields 	= Wc_WooSheets_Setting::woocommerce_checkout_manager_field( 'additional', $woosheets_withkey );
					$woosheets_wcm_headers_list 		= array_merge($woosheets_wcm_billing_fields, $woosheets_wcm_shipping_fields, $woosheets_wcm_additional_fields);
					
					$woosheets_withtype 					= 2;
					$woosheets_wcm_billing_fields 			= Wc_WooSheets_Setting::woocommerce_checkout_manager_field( 'billing', $woosheets_withtype );
					$woosheets_wcm_shipping_fields 			= Wc_WooSheets_Setting::woocommerce_checkout_manager_field( 'shipping', $woosheets_withkey );
					$woosheets_wcm_additional_fields 		= Wc_WooSheets_Setting::woocommerce_checkout_manager_field( 'additional', $woosheets_withkey );
					$woosheets_wcm_headers_list_withtype 	= array_merge($woosheets_wcm_billing_fields, $woosheets_wcm_shipping_fields, $woosheets_wcm_additional_fields);
					
			}
			/*Checkout Fields Pro*/
			$woosheets_checkout_fields_pro = array();
			if( Wc_WooSheets_Setting::is_checkout_fields_pro_active() ){
				$woosheets_checkout_field = get_option( 'thwcfe_sections', array() );
				$woosheets_checkout_fields_pro = Wc_WooSheets_Setting::get_checkout_field_pro_key($woosheets_checkout_field);
			}
			
			// Product Attribute
			$woosheets_attribute_taxonomies = Wc_WooSheets_Setting::_get_all_attributes();
			
			
			//CPO Product Extra Option 
			$woosheets_cpo_product_option = array();
			if( Wc_WooSheets_Setting::is_cpo_product_option_active() ){
				$woosheets_cpo_product_option	= Wc_WooSheets_Setting::get_cpo_product_option_field();
			}
			
			/*
			 * WooCommerce Custom Product Addons (Free)
			 */
			$woosheets_wcpa_product_field = array();
			if(Wc_WooSheets_Setting::is_wcpa_product_field_active()){
				$woosheets_withkey = 1;
				$woosheets_wcpa_product_field = Wc_WooSheets_Setting::wcpa_product_field( $woosheets_withkey );
			}
			$woosheets_wc_bookings_field = array();
			if( Wc_WooSheets_Setting::is_wc_bookings_active() ){
				$woosheets_wc_bookings_field	= Wc_WooSheets_Setting::woocommerce_booking_field();
			}
			/*
			 *Stripe Headers
			 */
			 $woosheets_stripe_field = array();
			if(Wc_WooSheets_Setting::is_stripe_active()){
				$woosheets_stripe_field = Wc_WooSheets_Setting::woosheets_stripe_field();
			}

			/*
			*WooCommerce Product Add-Ons Ultimate
			*/
			$product_field_extra_ultimate = array();
			if( Wc_WooSheets_Setting::is_pewc_active() ){
				$product_field_extra_ultimate	= Wc_WooSheets_Setting::product_field_extra_ultimate();
			}

		   /*
			*WooCommerce order delivery by Themesquad
			*/
			$wc_order_delivery_headers = array();
			if( Wc_WooSheets_Setting::is_wc_order_delivery_active() ){
				$wc_order_delivery_headers	= Wc_WooSheets_Setting::wc_order_delivery_headers();
			}

			/*
			*WooCommerce Delivery by welaunch
			*/
			$woocommerce_delivery_headers = array();
			if( Wc_WooSheets_Setting::is_woocommerce_delivery_active() ){
				$woocommerce_delivery_headers = Wc_WooSheets_Setting::woocommerce_delivery_headers();
			}

			/*
			*WooCommerce Delivery by welaunch
			*/
			$woocommerce_booking_appointment_headers = array();
			if( Wc_WooSheets_Setting::is_booking_appointment_active() ){
				$woocommerce_booking_appointment_headers = Wc_WooSheets_Setting::booking_appointment_headers();
			}

			$woosheets_order_hours = array();
			//Order Hours Delivery plugin
			if ( class_exists('Zhours\Setup') ) {
				$woosheets_order_hours = array('Scheduler Shipping Type','Scheduler Shipping Date','Scheduler Shipping Time');;
			}
			
			/*
			 * get custom headers
			 */
			$woosheets_extra_headers	= array();
			$woosheets_extra_headers 	= apply_filters( 'woosheets_custom_headers', array() );	
			
			$woosheets_temp_headers 	= array();
			$woosheets_value 			= array();
			$woosheets_prdarray 		= array();
			$woosheets_value[0] 		= $woosheets_order_id;
			$woosheets_headers_name 	= stripslashes_deep(get_option('sheet_headers_list'));
			$woosheets_product_headers 	= stripslashes_deep(get_option('product_sheet_headers_list'));
			$woosheets_is_repeat 		= get_option('repeat_checkbox');
			$woosheets_prdflag = $woosheets_customflag = $woosheets_subscriglag = $woosheets_attrflag = $woosheets_epo = $woosheets_epocount = $woosheets_bookflag = 0;
			$woosheets_header_type 		= get_option('header_format');
			
			if( $woosheets_header_type == "productwise" ){
				$woosheets_rcount = 0;
				foreach($woosheets_items as $woosheets_item){
					if( $woosheets_rcount > 0 ){
						$woosheets_prdarray[ $woosheets_rcount - 1][0] = $woosheets_order_id;
					}
					$woosheets_rcount++;
				}
			}
			for($i = 0 ; $i<count($woosheets_headers_name); $i++){
				
				$woosheets_arr = explode(' ',trim($woosheets_headers_name[$i]));
				
				if($woosheets_arr[0] == 'Billing'){
					$woosheets_strs = trim(strtolower(substr($woosheets_headers_name[$i], 8)));
					if( $woosheets_operation == 'insert' ) {					
						$woosheets_name = str_replace(' ', '_', $woosheets_strs);
						if( $woosheets_headers_name[$i] == 'Billing Postcode' ){
							$woosheets_insert_val = $woosheets_order_data['billing'][$woosheets_name]?"'".$woosheets_order_data['billing'][$woosheets_name]:'';
						}elseif( $woosheets_headers_name[$i] == 'Billing Address'){
							$woosheets_states 		= WC()->countries->get_states( $woosheets_order->get_billing_country() );
							$woosheets_states_name  = !empty( $woosheets_states[ $woosheets_order->get_billing_state() ] ) ? $woosheets_states[ $woosheets_order->get_billing_state() ] : $woosheets_order_data['billing']['state'];
							$woosheets_insert_val  = $woosheets_order_data['billing']['address_1'].'
'.$woosheets_order_data['billing']['address_2'].'
'.$woosheets_order_data['billing']['city'].'
'.$woosheets_order_data['billing']['postcode'].'
'.$woosheets_states_name.'
'.WC()->countries->countries[ $woosheets_order->get_billing_country() ];
						}elseif( $woosheets_headers_name[$i] == 'Billing Company Name' ){
							$woosheets_insert_val = $woosheets_order_data['billing']['company']?$woosheets_order_data['billing']['company']:'';
						}elseif( $woosheets_headers_name[$i] == 'Billing Country' ){
							$woosheets_insert_val = WC()->countries->countries[ $woosheets_order->get_billing_country() ];
						}elseif( $woosheets_headers_name[$i] == 'Billing State' ){
							$woosheets_states 		= WC()->countries->get_states( $woosheets_order->get_billing_country() );
							$woosheets_insert_val  = !empty( $woosheets_states[ $woosheets_order->get_billing_state() ] ) ? $woosheets_states[ $woosheets_order->get_billing_state() ] : '';
						}else{
							$woosheets_insert_val = $woosheets_order_data['billing'][$woosheets_name]?$woosheets_order_data['billing'][$woosheets_name]:'';
						}
							
						if( $woosheets_header_type == "productwise" ){
							self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
						}else{
							$woosheets_value[$i + 1] = $woosheets_insert_val;
						}
					}else{
						$woosheets_name = '_billing_'.str_replace(' ', '_', $woosheets_strs);
						if( $woosheets_headers_name[$i] == 'Billing Postcode' ){
						    $woosheets_insert_val = $_REQUEST[$woosheets_name]?"'".$_REQUEST[$woosheets_name]:'';	
						}elseif( $woosheets_headers_name[$i] == 'Billing Address'){
							$woosheets_states 		= WC()->countries->get_states( $woosheets_order->get_billing_country() );
							$woosheets_states_name  = !empty( $woosheets_states[ $woosheets_order->get_billing_state() ] ) ? $woosheets_states[ $woosheets_order->get_billing_state() ] : $_REQUEST['_billing_state'];
							$woosheets_insert_val  = $woosheets_order_data['billing']['address_1'].'
'.$_REQUEST['_billing_address_2'].'
'.$_REQUEST['_billing_city'].'
'.$_REQUEST['_billing_postcode'].'
'.$woosheets_states_name.'
'.WC()->countries->countries[ $woosheets_order->get_billing_country() ];
						}elseif( $woosheets_headers_name[$i] == 'Billing Company Name' ){
							$woosheets_insert_val = $_REQUEST['_billing_company']?$_REQUEST['_billing_company']:'';
						}elseif( $woosheets_headers_name[$i] == 'Billing Country' ){
							$woosheets_insert_val = WC()->countries->countries[ $woosheets_order->get_billing_country() ];
						}elseif( $woosheets_headers_name[$i] == 'Billing State' ){
							$woosheets_states 		= WC()->countries->get_states( $woosheets_order->get_billing_country() );
							$woosheets_insert_val  = !empty( $woosheets_states[ $_REQUEST['_billing_state' ] ] ) ? $woosheets_states[ $_REQUEST['_billing_state' ]  ] : '';
						}else{
							$woosheets_insert_val = $_REQUEST[$woosheets_name]?$_REQUEST[$woosheets_name]:'';
						}
						$woosheets_insert_val = trim($woosheets_insert_val);
						if( $woosheets_header_type == "productwise" ){
							self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
						}else{
							$woosheets_value[$i + 1] = $woosheets_insert_val;
						}	
					}
					continue;
				}
				if($woosheets_arr[0] == 'Shipping'){
						$woosheets_shipping_method_title = '';
						$woosheets_strs = trim(strtolower(substr($woosheets_headers_name[$i], 9)));
						$woosheets_name = str_replace(' ', '_', $woosheets_strs);
						if($woosheets_headers_name[$i] == 'Shipping Method Title'){
							foreach( $woosheets_order->get_items( 'shipping' ) as $woosheets_item_id => $woosheets_shipping_item_obj ){
							   $woosheets_shipping_method_title       = $woosheets_shipping_item_obj->get_method_title();
							}	
							$woosheets_insert_val =  $woosheets_shipping_method_title ? $woosheets_shipping_method_title :'';
					  	}elseif($woosheets_headers_name[$i] == 'Shipping Total'){
							$woosheets_shipping_method_total = '';
							foreach( $woosheets_order->get_items( 'shipping' ) as $woosheets_item_id => $woosheets_shipping_item_obj ){
								$woosheets_shipping_method_total = $woosheets_shipping_item_obj->get_total();
							}
							$woosheets_insert_val =  $woosheets_shipping_method_total ? $woosheets_shipping_method_total :'';
						}elseif( $woosheets_headers_name[$i] == 'Shipping Postcode' ){
							$woosheets_insert_val = $woosheets_order_data['shipping'][$woosheets_name]?"'".$woosheets_order_data['shipping'][$woosheets_name]:'';			
						}elseif( $woosheets_headers_name[$i] == 'Shipping Address'){
							$woosheets_states 		= WC()->countries->get_states( $woosheets_order->get_shipping_country() );
							$woosheets_states_name  = !empty( $woosheets_states[ $woosheets_order->get_shipping_state() ] ) ? $woosheets_states[ $woosheets_order->get_shipping_state() ] : $woosheets_order_data['shipping']['state'];
							$woosheets_insert_val  = $woosheets_order_data['billing']['address_1'].'
'.$woosheets_order_data['shipping']['address_2'].'
'.$woosheets_order_data['shipping']['city'].'
'.$woosheets_order_data['shipping']['postcode'].'
'.$woosheets_states_name.'
'.isset(WC()->countries->countries[ $woosheets_order->get_shipping_country() ])?WC()->countries->countries[ $woosheets_order->get_shipping_country() ]:'';
						}elseif( $woosheets_headers_name[$i] == 'Shipping Company Name' ){
							$woosheets_insert_val = $woosheets_order_data['shipping']['company']?$woosheets_order_data['shipping']['company']:'';
						}elseif( $woosheets_headers_name[$i] == 'Shipping Country' ){
							$woosheets_insert_val = isset(WC()->countries->countries[ $woosheets_order->get_shipping_country() ])?WC()->countries->countries[ $woosheets_order->get_shipping_country() ]:'';
						}elseif( $woosheets_headers_name[$i] == 'Shipping State' ){
							$woosheets_states 		= WC()->countries->get_states( $woosheets_order->get_shipping_country() );
							$woosheets_insert_val  = !empty( $woosheets_states[ $woosheets_order->get_shipping_state() ] ) ? $woosheets_states[ $woosheets_order->get_shipping_state() ] : $woosheets_order_data['shipping']['state'];
						}else{
							$woosheets_insert_val = $woosheets_order_data['shipping'][$woosheets_name]?$woosheets_order_data['shipping'][$woosheets_name]:'';	
						}
						$woosheets_insert_val = trim($woosheets_insert_val);
						if( $woosheets_header_type == "productwise" ){
							self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
						}else{
							$woosheets_value[$i + 1] = $woosheets_insert_val;
						}	
						continue;
				}
				if($woosheets_headers_name[$i] == 'Order Number'){
					$woosheets_acount = 0;
					foreach($woosheets_items as $woosheets_item){
						if( $woosheets_acount > 0 ){
							$woosheets_prdarray[ $woosheets_acount - 1][$i+1] = $woosheets_order->get_order_number()?$woosheets_order->get_order_number():'';
						}else{
							$woosheets_value[$i + 1] = $woosheets_order->get_order_number()?$woosheets_order->get_order_number():'';		
						}
						$woosheets_acount++;
					}
					continue;
				}
				if($woosheets_headers_name[$i] == 'Order Currency'){
					$woosheets_insert_val = $woosheets_order_data['currency']?$woosheets_order_data['currency']:'';
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}
				if($woosheets_headers_name[$i] == 'Order Status'){
					$woosheets_insert_val = ucfirst($woosheets_order->get_status());
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}			
				
				
				if($woosheets_headers_name[$i] == 'Transaction ID'){
					$woosheets_insert_val = $woosheets_order_data['transaction_id']?$woosheets_order_data['transaction_id']:'';
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}
				
				if($woosheets_headers_name[$i] == 'Tax Total'){
					$woosheets_taxes_total = $woosheets_order->get_total_tax();
					$woosheets_insert_val = $woosheets_taxes_total?$woosheets_taxes_total:'';
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}
				
				if($woosheets_headers_name[$i] == 'Coupons Codes'){
					$woosheets_version = '3.7.0';
					$woosheets_coupon_code = '';
					global $woocommerce;
					if ( version_compare( $woocommerce->version, $woosheets_version, ">=" ) ) {
						$woosheets_get_coupon_codes =  implode(',',$woosheets_order->get_coupon_codes());//return array
						$woosheets_insert_val = $woosheets_get_coupon_codes?$woosheets_get_coupon_codes:'';
					}else{
						$woosheets_get_used_coupons =  implode(',',$woosheets_order->get_used_coupons());//return array
						$woosheets_insert_val = $woosheets_get_used_coupons?$woosheets_get_used_coupons:'';	
					}
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}
				
				if($woosheets_headers_name[$i] == 'Order URL'){
					$woosheets_get_view_order_url = $woosheets_order->get_view_order_url();		
					$woosheets_insert_val = $woosheets_get_view_order_url?$woosheets_get_view_order_url:'';			
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}
				
				if($woosheets_headers_name[$i] == 'Prices Include Tax'){ 
					$woosheets_insert_val = $woosheets_order_data['prices_include_tax']?'Yes':'No';
					if( $woosheets_header_type == "productwise" ){					
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}

				if($woosheets_headers_name[$i] == 'Customer Note'){
					$woosheets_insert_val = $woosheets_order_data['customer_note']?$woosheets_order_data['customer_note']:'';
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}
				
				if($woosheets_headers_name[$i] == 'Customer ID'){
					$woosheets_insert_val = $woosheets_order_data['customer_id']?$woosheets_order_data['customer_id']:'';
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}
				
				if($woosheets_headers_name[$i] == 'Order Total'){
					$woosheets_insert_val = $woosheets_order_data['total']?$woosheets_order_data['total']:'';
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}
				
				if($woosheets_headers_name[$i] == 'Order Discount Total'){
					$woosheets_insert_val = $woosheets_order_data['discount_total']?$woosheets_order_data['discount_total']:'';
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}
				
				if($woosheets_headers_name[$i] == 'Order Discount Tax'){
					$woosheets_insert_val = $woosheets_order_data['discount_tax']?$woosheets_order_data['discount_tax']:'';
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}
				
				if($woosheets_headers_name[$i] == 'Payment Method'){
					$woosheets_insert_val = $woosheets_order_data['payment_method_title']?$woosheets_order_data['payment_method_title']:'';
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}
				
				if($woosheets_headers_name[$i] == 'Email'){
					if( $woosheets_operation == 'insert')
						$woosheets_insert_val = $woosheets_order_data['billing']['email']?$woosheets_order_data['billing']['email']:'';
					else
						$woosheets_insert_val = $_REQUEST['_billing_email']?$_REQUEST['_billing_email']:'';
					
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}
				
				if($woosheets_headers_name[$i] == 'Phone'){
					if( $woosheets_operation == 'insert')
						$woosheets_insert_val = $woosheets_order_data['billing']['phone']?"'".$woosheets_order_data['billing']['phone']:'';
					else
						$woosheets_insert_val = $_REQUEST['_billing_phone']?"'".$_REQUEST['_billing_phone']:'';
				
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}
				
				if($woosheets_headers_name[$i] == 'Created Date'){
					$woosheets_insert_val = $woosheets_order_data['date_created']->format('Y-m-d H:i:s');
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}
				
				if($woosheets_headers_name[$i] == 'Status Updated Date'){									
					if( isset($woosheets_order_data['date_modified']) ){								
						$woosheets_insert_val =  $woosheets_order_data['date_modified']->format('Y-m-d H:i:s');	
					}else{
						$woosheets_insert_val = '';	
					}
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}
				
				if($woosheets_headers_name[$i] == 'Order Completion Date'){	
					if( isset($woosheets_order_data['date_completed']) ){								
						$woosheets_insert_val =  $woosheets_order_data['date_completed']->format('Y-m-d H:i:s');	
					}else{
						$woosheets_insert_val = '';	
					}
					
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}
				
				if($woosheets_headers_name[$i] == 'Order Paid Date'){	
					if( isset($woosheets_order_data['date_paid']) ){								
						$woosheets_insert_val =  $woosheets_order_data['date_paid']->format('Y-m-d H:i:s');	
					}else{
						$woosheets_insert_val = '';	
					}
					
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;
				}
				
				if ( class_exists( 'order_delivery_date' ) ) {
					if($woosheets_headers_name[$i] == $woosheets_delivery_label){
						$woosheets_insert_val = $woosheets_delivery_date;
						if( $woosheets_header_type == "productwise" ){
							self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
						}else{
							$woosheets_value[$i + 1] = $woosheets_insert_val;
						}
						continue;
					}
				}	
				
				if ( class_exists( 'WC_pdf_functions' ) ) {
					
					if( $woosheets_headers_name[$i] == 'Invoice Number' ){
						$woosheets_insert_val = $woosheets_order->get_meta('_invoice_number_display');
						if( $woosheets_header_type == "productwise" ){
							self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
						}else{
							$woosheets_value[$i + 1] = $woosheets_insert_val;
						}
						continue;
					}
				}
				
				if ( class_exists( 'WC_Xero' ) ) {
					
					if( $woosheets_headers_name[$i] == 'Xero Invoice Id' ){
						$woosheets_insert_val = $woosheets_order->get_meta('_xero_invoice_id');
						if( $woosheets_header_type == "productwise" ){
							self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
						}else{
							$woosheets_value[$i + 1] = $woosheets_insert_val;
						}
						continue;
					}
				}
				
				/*Stripe Fields*/
				if(in_array( $woosheets_headers_name[$i],$woosheets_stripe_field)){
					$woosheets_stripe_data = '';
					if( $woosheets_headers_name[$i] == 'Stripe Fee' )
						$woosheets_stripe_data   = WC_Stripe_Helper::get_stripe_fee( $woosheets_order );
						
					if( $woosheets_headers_name[$i] == 'Stripe Net' )
						$woosheets_stripe_data   = WC_Stripe_Helper::get_stripe_net( $woosheets_order );
						
					if( $woosheets_headers_name[$i] == 'Stripe Charge Captured' )
						$woosheets_stripe_data           = WC_Stripe_Helper::is_wc_lt( '3.0' ) ? get_post_meta( $woosheets_order_id, '_stripe_charge_captured', true ) : $woosheets_order->get_meta( '_stripe_charge_captured', true );
					
					if( $woosheets_headers_name[$i] == 'Net Revenue From Stripe' )
						$woosheets_stripe_data = WC_Stripe_Helper::is_wc_lt( '3.0' ) ? get_post_meta( $woosheets_order_id, 'Net Revenue From Stripe', true ) : $woosheets_order->get_meta( 'Net Revenue From Stripe', true );
					
					if( $woosheets_headers_name[$i] == 'Stripe Transaction Id' )
						$woosheets_stripe_data	= WC_Stripe_Helper::is_wc_lt( '3.0' ) ? get_post_meta( $woosheets_order_id, '_transaction_id', true ) : $woosheets_order->get_transaction_id();
					
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_stripe_data);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;					
				}

				if( $woosheets_headers_name[$i] == 'Order Notes' ){
						$woosheets_insert_val = self::woosheets_get_all_order_notes($woosheets_order_id);
						$woosheets_insert_val = implode(',',$woosheets_insert_val);
						if( $woosheets_header_type == "productwise" ){
							self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
						}else{
							$woosheets_value[$i + 1] = $woosheets_insert_val;
						}
						continue;
				}
				
				/*Order Hours Delivery plugin*/
				if(in_array( $woosheets_headers_name[$i],$woosheets_order_hours)){
					$woosheets_insert_val = '';
					if( $woosheets_headers_name[$i] == 'Scheduler Shipping Type' )
						$woosheets_insert_val   = get_post_meta( $woosheets_order_id, '_zh_shipping_type', true );
					
					if( $woosheets_headers_name[$i] == 'Scheduler Shipping Date' )
						$woosheets_insert_val   = get_post_meta( $woosheets_order_id, '_zh_shipping_date', true );

					if( $woosheets_headers_name[$i] == 'Scheduler Shipping Time' )
						$woosheets_insert_val   = get_post_meta( $woosheets_order_id, '_zh_shipping_time', true );

					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;		
				}

				//WooCommerce order delivery by Themesquad
				if(in_array( $woosheets_headers_name[$i],$wc_order_delivery_headers)){
					$woosheets_insert_val = '';
					if( $woosheets_headers_name[$i] == 'Delivery Date' ){
						$woosheets_insert_val   = $woosheets_order->get_meta('_delivery_date');
					}
					
					if( $woosheets_headers_name[$i] == 'Schedule Time' ){
						$delivery_time   = $woosheets_order->get_meta('_delivery_time_frame');
						$time_to = $time_from = '';
						if ( isset( $delivery_time['time_from'] ) ){
							$time_from = $delivery_time['time_from'];
						}
						if ( isset( $delivery_time['time_to'] ) ){
							$time_to = $delivery_time['time_to'];
						}
						$woosheets_insert_val = $time_from.' - '.$time_to;
					}

					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;		
				}
			
				//WooCommerce Delivery by welunch
				if(in_array( $woosheets_headers_name[$i],$woocommerce_delivery_headers)){
					$woosheets_insert_val = '';

					if( $woosheets_headers_name[$i] == 'WooCommerce Delivery Date' ){
						$woosheets_insert_val   = $woosheets_order->get_meta('delivery_date_formatted');
					}
					if( $woosheets_headers_name[$i] == 'WooCommerce Delivery Time' ){
						$woosheets_insert_val   = $woosheets_order->get_meta('delivery_time');
					}
					if( $woosheets_headers_name[$i] == 'WooCommerce Delivery Location' ){
						$woosheets_insert_val   = $woosheets_order->get_meta('delivery_location');
					}
					
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;		
				}

				//WooCommerce Bookings And Appointments by PluginHive
				if(Wc_WooSheets_Setting::is_booking_appointment_active()){
				    if( in_array($woosheets_headers_name[$i],$woocommerce_booking_appointment_headers) ){
				    if( $woosheets_header_type == "productwise" ){
				                if( $woosheets_bookflag == 0 ){
				                    $woosheets_bcount = 0;

				                    foreach($woosheets_items as $woosheets_item){
				                        $woosheets_metadata = $woosheets_item->get_formatted_meta_data();
				                            $woosheets_bookingmetaval = '';
				                            foreach($woosheets_metadata as $woosheets_meta){
				                                if( $woosheets_meta->key == $woosheets_headers_name[$i] ){
				                                    $woosheets_bookingmetaval .= $woosheets_meta->value.","; 
				                                }
				                            }
				                            $woosheets_bookingmetaval = rtrim($woosheets_bookingmetaval,',');
				                            if( $woosheets_bcount > 0 ){
				                                if(is_array($woosheets_bookingmetaval)){
				                                    $woosheets_prdarray[ $woosheets_bcount - 1][$i+1] = implode(',',$woosheets_bookingmetaval);
				                                }else{
				                                    $woosheets_prdarray[ $woosheets_bcount - 1][$i+1] = $woosheets_bookingmetaval;
				                                }
				                            }else{
				                                if(is_array($woosheets_bookingmetaval)){
				                                    $woosheets_value[$i+1] = implode(',',$woosheets_bookingmetaval);
				                                }else{
				                                    $woosheets_value[$i+1] = $woosheets_bookingmetaval;
				                                }       
				                            }
				                        $woosheets_bcount++;            
				                    }
				                    $woosheets_bookflag = 0;    
				                }
				        }else{
				            $woosheets_bookingmetaval = '';
				            foreach($woosheets_items as $woosheets_item){
				                $woosheets_metadata = $woosheets_item->get_formatted_meta_data();
				                foreach($woosheets_metadata as $woosheets_meta){
				                    if( $woosheets_meta->key == $woosheets_headers_name[$i])
				                        $woosheets_bookingmetaval .= $woosheets_meta->value.","; 
				                }                                        
				                $woosheets_bookingmetaval = rtrim(trim($woosheets_bookingmetaval),",");
				                $woosheets_bookingmetaval .= '|';
				            }
				            
				                $woosheets_value[] = rtrim(trim($woosheets_bookingmetaval),"|");
				        }
				        continue;
				    }
				}

				 /*Checkout Fields*/
				 if(in_array( $woosheets_headers_name[$i],$woosheets_billing_fields)){
					foreach ( $woosheets_checkoutfields as $woosheets_name => $woosheets_options ) {
						if( $woosheets_options['label'] == $woosheets_headers_name[$i] || $woosheets_options['name'] == $woosheets_headers_name[$i] ){
							$woosheets_insert_val = wc_get_checkout_field_value( $woosheets_order, $woosheets_name, $woosheets_options );
							if( $woosheets_header_type == "productwise" ){
								self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
							}else{
								$woosheets_value[$i + 1] = $woosheets_insert_val;
							}
						}				
					}
					continue;
				}
				if(in_array( $woosheets_headers_name[$i],$woosheets_shipping_fields)){
					foreach ( $woosheets_checkoutfields as $woosheets_name => $woosheets_options ) {
						if( $woosheets_options['label'] == $woosheets_headers_name[$i] || $woosheets_options['name'] == $woosheets_headers_name[$i] ){
							$woosheets_insert_val = wc_get_checkout_field_value( $woosheets_order, $woosheets_name, $woosheets_options );
							if( $woosheets_header_type == "productwise" ){
								self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
							}else{
								$woosheets_value[$i + 1] = $woosheets_insert_val;
							}
						}				
					}
					continue;
				}
				if(in_array( $woosheets_headers_name[$i],$woosheets_additional_fields)){
					foreach ( $woosheets_checkoutfields as $woosheets_name => $woosheets_options ) {
						if( $woosheets_options['label'] == $woosheets_headers_name[$i] || $woosheets_options['name'] == $woosheets_headers_name[$i] ){
							$woosheets_insert_val = wc_get_checkout_field_value( $woosheets_order, $woosheets_name, $woosheets_options );
							if( $woosheets_header_type == "productwise" ){
								self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
							}else{
								$woosheets_value[$i + 1] = $woosheets_insert_val;
							}
						}				
					}
					continue;
				}
			//
			if(in_array( $woosheets_headers_name[$i],$woosheets_checkout_fields_pro )){
				$woosheets_fieldkey = array_search( $woosheets_headers_name[$i],$woosheets_checkout_fields_pro );
				$woosheets_fieldkeydata  = get_post_meta( $woosheets_order_id, $woosheets_fieldkey, true );
				if(is_array($woosheets_fieldkeydata)){
					$woosheets_insert_val = implode(',',$woosheets_fieldkeydata);
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}	
				}else{
					if ( strpos($woosheets_fieldkeydata, 'name') !== false && strpos($woosheets_fieldkeydata, 'url') !== false && strpos($woosheets_fieldkeydata, 'http') !== false) {
						$woosheets_string = explode(',',$woosheets_fieldkeydata);		
						foreach($woosheets_string as $woosheets_s){
							if ( strpos($woosheets_s, 'url') !== false && strpos($woosheets_s, 'http') !== false ){
								$woosheets_url = explode('":"',$woosheets_s);
								$woosheets_remove[] = "'";
								$woosheets_remove[] = '"';
								$woosheets_remove[] = ",";
								$woosheets_fieldkeydata = str_replace($woosheets_remove,'',$woosheets_url[1]);	
							}
						}
					}
  					$woosheets_insert_val = $woosheets_fieldkeydata;
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}	
				}
				continue;
			}

			//WooCommerce Product Add-Ons Ultimate
			if(in_array( $woosheets_headers_name[$i],$product_field_extra_ultimate)){
				if( $woosheets_header_type == "productwise" ){
					if( $woosheets_attrflag == 0 ){
						$woosheets_acount = 0;
						foreach($woosheets_items as $woosheets_item){
							$woosheets_attr_headers = array();
							$woosheets_metadata = $woosheets_item->get_meta_data();
								$woosheets_attrmetaval = '';
								foreach($woosheets_metadata as $woosheets_meta){
									if( strtolower($woosheets_meta->key) == strtolower('_'.$woosheets_headers_name[$i]) ){
										if(!empty($woosheets_meta->value)){
											$woosheets_attrmetaval .= $woosheets_meta->value.",";
									    }
									}
								}
								$woosheets_attrmetaval = rtrim($woosheets_attrmetaval,',');
								if( $woosheets_acount > 0 ){
									if(is_array($woosheets_attrmetaval)){
										$woosheets_prdarray[ $woosheets_acount - 1][$i+1] = implode(',',$woosheets_attrmetaval);
									}else{
										$woosheets_prdarray[ $woosheets_acount - 1][$i+1] = $woosheets_attrmetaval;
									}
								}else{
									if(is_array($woosheets_attrmetaval)){
										$woosheets_value[$i + 1] = implode(',',$woosheets_attrmetaval);
									}else{
										$woosheets_value[$i + 1] = $woosheets_attrmetaval;
									}		
								}
							unset($woosheets_attr_headers);
							$woosheets_acount++;			
						}
						continue;
						$woosheets_attrflag = 0;	
					}						
				}else{
					$woosheets_attrval = '';
					foreach($woosheets_items as $woosheets_item){
						$woosheets_metadata = $woosheets_item->get_meta_data();
						foreach($woosheets_metadata as $woosheets_meta){
							if( strtolower($woosheets_meta->key) == strtolower('_'.$woosheets_headers_name[$i]) ){
								if(!empty($woosheets_meta->value)){
									$woosheets_attrval .= $woosheets_meta->value.",";
							    }
							}
						}							 
						$woosheets_attrval = rtrim(trim($woosheets_attrval),",");
						$woosheets_attrval .= '|';
					}
					$woosheets_value[] = rtrim(trim($woosheets_attrval),"|");		
				}
				continue;		
			}
			
			if(in_array( $woosheets_headers_name[$i],$woosheets_wcm_headers_list )){
				$woosheets_fieldkey = array_search( $woosheets_headers_name[$i],$woosheets_wcm_headers_list );
				$woosheets_fieldkeydata  = get_post_meta( $woosheets_order_id, $woosheets_fieldkey, true );
				
				$woosheets_fieldtype = '';
				if( isset($woosheets_wcm_headers_list_withtype[$woosheets_headers_name[$i]]))
					$woosheets_fieldtype = $woosheets_wcm_headers_list_withtype[$woosheets_headers_name[$i]];
				 
				if(is_array($woosheets_fieldkeydata)){
					$woosheets_insert_val = implode(',',$woosheets_fieldkeydata);
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}	
				}else{
					if( $woosheets_fieldtype == 'file' )
						$woosheets_insert_val = wp_get_attachment_url( $woosheets_fieldkeydata )?wp_get_attachment_url( $woosheets_fieldkeydata ):'';	
					else
						$woosheets_insert_val = $woosheets_fieldkeydata;	
						
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
				}
				continue;
			}
			
			if(in_array( $woosheets_headers_name[$i],$woosheets_attribute_taxonomies)){
				if( $woosheets_header_type == "productwise" ){
					if( $woosheets_attrflag == 0 ){
						$woosheets_acount = 0;
						foreach($woosheets_items as $woosheets_item){
							$woosheets_attr_headers = array();
							$woosheets_metadata = $woosheets_item->get_meta_data();
								$woosheets_attrmetaval = '';
								foreach($woosheets_metadata as $woosheets_meta){
									$woosheets_temp = str_replace('-',' ',trim($woosheets_meta->key));
									if( strtolower(str_replace("pa_",'',$woosheets_temp)) == strtolower($woosheets_headers_name[$i])){
										if(!empty($woosheets_meta->value)){
											$woosheets_term = get_term_by('slug', $woosheets_meta->value, $woosheets_meta->key);

											if( isset($woosheets_term->name) ){
												$woosheets_attrmetaval .= $woosheets_term->name.",";
											}else{
												$woosheets_attrmetaval .= $woosheets_meta->value.","; 	
											}
									    }
									}
								}
								$woosheets_attrmetaval = rtrim($woosheets_attrmetaval,',');
								if( $woosheets_acount > 0 ){
									if(is_array($woosheets_attrmetaval)){
										$woosheets_prdarray[ $woosheets_acount - 1][$i+1] = implode(',',$woosheets_attrmetaval);
									}else{
										$woosheets_prdarray[ $woosheets_acount - 1][$i+1] = $woosheets_attrmetaval;
									}
								}else{
									if(is_array($woosheets_attrmetaval)){
										$woosheets_value[$i + 1] = implode(',',$woosheets_attrmetaval);
									}else{
										$woosheets_value[$i + 1] = $woosheets_attrmetaval;
									}		
								}
							unset($woosheets_attr_headers);
							$woosheets_acount++;			
						}
						continue;
						$woosheets_attrflag = 0;	
					}						
				}else{
					$woosheets_attrval = '';
					foreach($woosheets_items as $woosheets_item){
						$woosheets_metadata = $woosheets_item->get_meta_data();
						foreach($woosheets_metadata as $woosheets_meta){
							$woosheets_temp = str_replace('-',' ',trim($woosheets_meta->key));
							if( strtolower(str_replace("pa_",'',$woosheets_temp)) == strtolower($woosheets_headers_name[$i])){
								if(!empty($woosheets_meta->value)){
									$woosheets_term = get_term_by('slug', $woosheets_meta->value, $woosheets_meta->key);
									if( isset($woosheets_term->name) ){
										$woosheets_attrval .= $woosheets_term->name.",";
									}else{
										$woosheets_attrval .= $woosheets_meta->value.","; 	
									}
							    }
							}
						}									 
						$woosheets_attrval = rtrim(trim($woosheets_attrval),",");
						$woosheets_attrval .= '|';
					}
					$woosheets_value[] = rtrim(trim($woosheets_attrval),"|");		
				}
				continue;		
			}
			//Custom field Value for YITH
			if(Wc_WooSheets_Setting::is_yith_wapo_active()){
				if( in_array($woosheets_headers_name[$i],$woosheets_custom_field_header) ){
					if( $woosheets_header_type == "productwise" ){
							if( $woosheets_yithflag == 0 ){
								$woosheets_ycount = 0;
								foreach($woosheets_items as $woosheets_item){
									$woosheets_yith_headers = array();
									$woosheets_metadata = $woosheets_item->get_formatted_meta_data();
										$woosheets_yithmetaval = '';
										foreach($woosheets_metadata as $woosheets_meta){
											if( strtolower($woosheets_meta->key) == strtolower($woosheets_headers_name[$i])){
												$woosheets_yithmetaval .= $woosheets_meta->value.","; 
											}
										}
										$woosheets_yithmetaval = rtrim($woosheets_yithmetaval,',');
										if( $woosheets_ycount > 0 ){
											if(is_array($woosheets_yithmetaval)){
												$woosheets_prdarray[ $woosheets_ycount - 1][$i+1] = implode(',',$woosheets_yithmetaval);
											}else{
												$woosheets_prdarray[ $woosheets_ycount - 1][$i+1] = $woosheets_yithmetaval;
											}
										}else{
											if(is_array($woosheets_yithmetaval)){
												$woosheets_value[$i+1] = implode(',',$woosheets_yithmetaval);
											}else{
												$woosheets_value[$i+1] = $woosheets_yithmetaval;
											}		
										}
									unset($woosheets_yith_headers);
									$woosheets_ycount++;			
								}
								$woosheets_yithflag = 0;	
							}
					}else{
						$woosheets_yithmetaval = '';
						foreach($woosheets_items as $woosheets_item){
							$woosheets_metadata = $woosheets_item->get_formatted_meta_data();
							foreach($woosheets_metadata as $woosheets_meta){
								if( strtolower($woosheets_meta->key) == strtolower($woosheets_headers_name[$i]))
									$woosheets_yithmetaval .= $woosheets_meta->value.","; 
							}										 
							$woosheets_yithmetaval = rtrim(trim($woosheets_yithmetaval),",");
							$woosheets_yithmetaval .= '|';
						}
						
							$woosheets_value[] = rtrim(trim($woosheets_yithmetaval),"|");
					}
					continue;
				}
			}
			/*WooCommerce Product Add on*/
			if(Wc_WooSheets_Setting::is_wooproduct_addon_active()){
				if( in_array($woosheets_headers_name[$i],$woosheets_cf_prdoduct_addon) ){
					if( $woosheets_header_type == "productwise" ){
							if( $woosheets_customflag == 0 ){
								$woosheets_ccount = 0;
								
								foreach($woosheets_items as $woosheets_item){
									
									$woosheets_custom_temp = array();
									$woosheets_temp_headers = array();
									$woosheets_metaval = '';
									$woosheets_wooheaders = $woosheets_item->get_meta( 'woosheets_headers' , true );
									if( !empty($woosheets_wooheaders) ){
									
										$woosheets_metaval = '';
										$woosheets_iskeyfound = 0;
										$woosheets_key = array_search( $woosheets_headers_name[$i], $woosheets_wooheaders );
										if($woosheets_key){
											if(!in_array($woosheets_key,$woosheets_temp_headers)){
												$woosheets_temp_headers[] = $woosheets_key;
											}else{
												foreach( $woosheets_wooheaders as $woosheets_hkey => $woosheets_hvalue ){
													if($woosheets_headers_name[$i] == $woosheets_hvalue){
														if(!in_array($woosheets_hkey,$woosheets_temp_headers)){
															$woosheets_temp_headers[] = $woosheets_hkey;
															$woosheets_key = $woosheets_hkey;	
															break;
														}	
													}
												}	
											}									
											$woosheets_metafieldkey = $woosheets_item->get_meta( $woosheets_key, false );
											$woosheets_metaval = array_map(function($e) {
												return is_object($e) ? $e->value : $e['value'];
											}, $woosheets_metafieldkey);	
											$woosheets_iskeyfound = 1;	
										}
										
									}else{
										$woosheets_metaval = array();
										$item_meta_data = $woosheets_item->get_meta_data();
										foreach( $item_meta_data as $woosheets_metavalue ){
											$explodewith = " (";
											$woosheets_afterexplode = explode($explodewith,$woosheets_metavalue->key);
											if( isset($woosheets_afterexplode[0]) && $woosheets_afterexplode[0] == $woosheets_headers_name[$i] ){
												$woosheets_metaval[] = 	$woosheets_metavalue->value;		
											}
										}
											
									}
										if( $woosheets_ccount > 0 ){
											if(is_array($woosheets_metaval)){
												$woosheets_prdarray[ $woosheets_ccount - 1][$i+1] = implode(',',$woosheets_metaval);
											}else{
												$woosheets_prdarray[ $woosheets_ccount - 1][$i+1] = $woosheets_metaval;
											}
										}else{
											if(is_array($woosheets_metaval)){
												$woosheets_value[$i + 1] = implode(',',$woosheets_metaval);
											}else{
												$woosheets_value[$i + 1] = $woosheets_metaval;
											}		
										}
									unset($woosheets_temp_headers);
									$woosheets_ccount++;
							}	
							$woosheets_customflag = 0;	
							continue;
						}
					}else{
						$woosheets_cvalue = '';
						foreach($woosheets_items as $woosheets_item){
							$woosheets_temp_headers = array();
							$woosheets_wooheaders = $woosheets_item->get_meta( 'woosheets_headers' , true );		
							$woosheets_key = array_search( $woosheets_headers_name[$i], $woosheets_wooheaders );
							$woosheets_metaval = '';
							if($woosheets_key){
								if(!in_array($woosheets_key,$woosheets_temp_headers)){
									$woosheets_temp_headers[] = $woosheets_key;
								}else{
									foreach( $woosheets_wooheaders as $woosheets_hkey => $woosheets_hvalue ){
										if($woosheets_headers_name[$i] == $woosheets_hvalue){
											if(!in_array($woosheets_hkey,$woosheets_temp_headers)){
												$woosheets_temp_headers[] = $woosheets_hkey;
												$woosheets_key = $woosheets_hkey;	
												break;
											}	
										}
									}	
								}									
								$woosheets_metafieldkey = $woosheets_item->get_meta( $woosheets_key, false );
								$woosheets_metaval = array_map(function($e) {
									return is_object($e) ? $e->value : $e['value'];
								}, $woosheets_metafieldkey);		
							}
							
							if(is_array($woosheets_metaval)){
								$woosheets_cvalue .= implode(',',$woosheets_metaval).'|';
							}else{
								$woosheets_cvalue .= $woosheets_metaval.'|';
							}
						}
						unset($woosheets_temp_headers);//update
						$woosheets_value[] = rtrim(trim($woosheets_cvalue),"|");
					}
					continue;
				}
			}
				/**/
				
			/* WooCommerce Subscriptions */			
			if( Wc_WooSheets_Setting::is_woo_subscription_active() ){
					
				if( in_array($woosheets_headers_name[$i],$woosheets_woosubscriptions) ){
					if ( wcs_order_contains_subscription( $woosheets_order_id, 'any' ) ) {
						if( $woosheets_header_type == "productwise" ){
							if( $woosheets_subscriglag == 0 ){
									$woosheets_ccount = 0;
									foreach($woosheets_items as $woosheets_item){
										$woosheets_custom_temp = array();
										$woosheets_temp_headers = array();
										$woosheets_product_id = $woosheets_item['product_id']?$woosheets_item['product_id']:'';
										$woosheets_renewal_time = $woosheets_interval = $woosheets_period = $woosheets_length = $woosheets_trial_period = $woosheets_trial_length = $woosheets_sign_up_fee = $woosheets_one_time_shipping = $woosheets_payment_sync_date = '' ;
										if( class_exists( 'WC_Subscriptions_Product' ) && WC_Subscriptions_Product::is_subscription( $woosheets_product_id ) ) {
											$woosheets_is_exist = true;
										}else{
											$woosheets_is_exist = false;	
										}
										if($woosheets_is_exist){
												$woosheets_subvalue = '';
												$woosheets_key = in_array( $woosheets_headers_name[$i], $woosheets_woosubscriptions );
												if($woosheets_key){
													
													if( $woosheets_headers_name[$i] == 'Subscription Price' ){
														if($woosheets_item['variation_id'] > 0){
															$woosheets_variable_product = wc_get_product($woosheets_item['variation_id']);	
															$woosheets_price = $woosheets_variable_product->get_price();	
														}else{
															$woosheets_price = WC_Subscriptions_Product::get_price( $woosheets_product_id ) ;	
														}
														$woosheets_subvalue     = $woosheets_price * $woosheets_item['quantity'];
													}
													if( $woosheets_headers_name[$i] == 'Subscription Limit' )
														$woosheets_subvalue     = get_post_meta( $woosheets_product_id, '_subscription_limit', true );
													if( $woosheets_headers_name[$i] == 'Subscription Period Interval' )
														$woosheets_subvalue     = WC_Subscriptions_Product::get_interval( $woosheets_product_id );
													if( $woosheets_headers_name[$i] == 'Subscription Period' )
														$woosheets_subvalue     = WC_Subscriptions_Product::get_period( $woosheets_product_id );
													if( $woosheets_headers_name[$i] == 'Subscription Length' )
														$woosheets_subvalue     = WC_Subscriptions_Product::get_length( $woosheets_product_id );
													if( $woosheets_headers_name[$i] == 'Subscription Trial Period' )
														$woosheets_subvalue      = WC_Subscriptions_Product::get_trial_period( $woosheets_product_id );
													if( $woosheets_headers_name[$i] == 'Subscription Trial Length' )
														$woosheets_subvalue 	   = WC_Subscriptions_Product::get_trial_length( $woosheets_product_id );
													if( $woosheets_headers_name[$i] == 'Subscription Sign Up Fee' )
														$woosheets_subvalue      = WC_Subscriptions_Product::get_sign_up_fee( $woosheets_product_id );
													if( $woosheets_headers_name[$i] == 'Subscription One Time Shipping' ){
														$woosheets_one_time_shipping = WC_Subscriptions_Product::needs_one_time_shipping( $woosheets_product_id );
														if( $woosheets_one_time_shipping )
															$woosheets_subvalue = 'Yes';
														else
															$woosheets_subvalue = 'No';
													}
													if( $woosheets_headers_name[$i] == 'Subscription Payment Sync Date' )	{
														$woosheets_subvalue = WC_Subscriptions_Synchroniser::get_products_payment_day( $woosheets_product_id );	
														if(is_array($woosheets_subvalue)){
															$woosheets_monthNum  = $woosheets_subvalue['month'];
															$woosheets_dateObj   = DateTime::createFromFormat('!m', $woosheets_monthNum);
															$woosheets_monthName = $woosheets_dateObj->format('F'); // March
															$woosheets_subvalue = $woosheets_subvalue['day'].', '.$woosheets_monthName;
														}else{
															$woosheets_subvalue = 'N/A';	
														}
													}
													if( $woosheets_headers_name[$i] == 'Subscription Next Payment Date' )	{
														$woosheets_subvalue = WC_Subscriptions_Order::get_next_payment_date ( $woosheets_order, $woosheets_product_id );
												    }
												    if( $woosheets_headers_name[$i] == 'Subscription End Date' )	{
														$subscription_end_date = '';
												    	$subscriptions_ids = wcs_get_subscriptions_for_order( $woosheets_order_id );
														    foreach( $subscriptions_ids as $subscription_id => $subscription_obj ){
														        if($subscription_obj->order->id == $woosheets_order_id){
														        	$subscription_end_date = $subscription_obj->schedule_end;
														        	break;
														        } // Stop the loop
														    }
														$woosheets_subvalue = $subscription_end_date;
												    }
												}
											if( $woosheets_ccount > 0 ){
												if(is_array($woosheets_subvalue)){
													$woosheets_prdarray[ $woosheets_ccount - 1 ][$i+1] = implode(',',$woosheets_subvalue);
												}else{
													$woosheets_prdarray[ $woosheets_ccount - 1 ][$i+1] = ucfirst($woosheets_subvalue);
												}
											}else{
												if(is_array($woosheets_subvalue)){
													$woosheets_value[$i + 1] = implode(',',$woosheets_subvalue);
												}else{
													$woosheets_value[$i + 1] = ucfirst($woosheets_subvalue);
												}		
											}
									}
									$woosheets_ccount++;
									}
									$woosheets_subscriglag = 0;
							}
						}else{
							$woosheets_cvalue = '';
							foreach($woosheets_items as $woosheets_item){
								$woosheets_product_id = $woosheets_item['product_id']?$woosheets_item['product_id']:'';		
								$woosheets_key = in_array( $woosheets_headers_name[$i], $woosheets_woosubscriptions );
								$woosheets_subvalue = '';
								if( class_exists( 'WC_Subscriptions_Product' ) && WC_Subscriptions_Product::is_subscription( $woosheets_product_id ) ) {
									$woosheets_is_exist = true;
								}else{
									$woosheets_is_exist = false;	
								}
								if( $woosheets_key && $woosheets_is_exist ){		
										if( $woosheets_headers_name[$i] == 'Subscription Price' ){
												if($woosheets_item['variation_id'] > 0){
													$woosheets_variable_product = wc_get_product($woosheets_item['variation_id']);	
													$woosheets_price = $woosheets_variable_product->get_price();	
												}else{
													$woosheets_price = WC_Subscriptions_Product::get_price( $woosheets_product_id ) ;	
												}
												$woosheets_subvalue     = $woosheets_price * $woosheets_item['quantity'];
										}
											
										if( $woosheets_headers_name[$i] == 'Subscription Limit' )
											$woosheets_subvalue     = get_post_meta( $woosheets_product_id, '_subscription_limit', true );
										if( $woosheets_headers_name[$i] == 'Subscription Period Interval' )
											$woosheets_subvalue     = WC_Subscriptions_Product::get_interval( $woosheets_product_id );
										if( $woosheets_headers_name[$i] == 'Subscription Period' )
											$woosheets_subvalue     = WC_Subscriptions_Product::get_period( $woosheets_product_id );
										if( $woosheets_headers_name[$i] == 'Subscription Length' )
											$woosheets_subvalue     = WC_Subscriptions_Product::get_length( $woosheets_product_id );
										if( $woosheets_headers_name[$i] == 'Subscription Trial Period' )
											$woosheets_subvalue      = WC_Subscriptions_Product::get_trial_period( $woosheets_product_id );
										if( $woosheets_headers_name[$i] == 'Subscription Trial Length' )
											$woosheets_subvalue 	   = WC_Subscriptions_Product::get_trial_length( $woosheets_product_id );
										if( $woosheets_headers_name[$i] == 'Subscription Sign Up Fee' )
											$woosheets_subvalue      = WC_Subscriptions_Product::get_sign_up_fee( $woosheets_product_id );
										if( $woosheets_headers_name[$i] == 'Subscription One Time Shipping' ){
											$woosheets_one_time_shipping = WC_Subscriptions_Product::needs_one_time_shipping( $woosheets_product_id );
											if( $woosheets_one_time_shipping )
												$woosheets_subvalue = 'Yes';
											else
												$woosheets_subvalue = 'No';
										}
										if( $woosheets_headers_name[$i] == 'Subscription Payment Sync Date' )	{
											$woosheets_subvalue = WC_Subscriptions_Synchroniser::get_products_payment_day( $woosheets_product_id );	
											if(is_array($woosheets_subvalue)){
												$woosheets_monthNum  = $woosheets_subvalue['month'];
												$woosheets_dateObj   = DateTime::createFromFormat('!m', $woosheets_monthNum);
												$woosheets_monthName = $woosheets_dateObj->format('F'); // March
												$woosheets_subvalue = $woosheets_subvalue['day'].', '.$woosheets_monthName;
											}
										}
										if( $woosheets_headers_name[$i] == 'Subscription Next Payment Date' )	{
											$woosheets_subvalue = WC_Subscriptions_Order::get_next_payment_date ( $woosheets_order, $woosheets_product_id );
									    }
									    if( $woosheets_headers_name[$i] == 'Subscription End Date' )	{
											$subscription_end_date = '';
									    	$subscriptions_ids = wcs_get_subscriptions_for_order( $woosheets_order_id );
											    foreach( $subscriptions_ids as $subscription_id => $subscription_obj ){
											        if($subscription_obj->order->id == $woosheets_order_id){
											        	$subscription_end_date = $subscription_obj->schedule_end;
											        	break;
											        } // Stop the loop
											    }
											$woosheets_subvalue = $subscription_end_date;
									    }
								}
								
								if(is_array($woosheets_subvalue)){
									$woosheets_cvalue .= implode(',',$woosheets_subvalue).'|';
								}else{
									$woosheets_cvalue .= ucfirst($woosheets_subvalue).'|';
								}
							}
							$woosheets_value[$i + 1] = ltrim(rtrim(trim($woosheets_cvalue),"|"),"|");
						}
						continue;
					}
				}
			}
				
			/*Extra Product option Field*/
			if(Wc_WooSheets_Setting::is_tm_extra_product_options_active()){
					if( in_array($woosheets_headers_name[$i],$woosheets_filterd) ){
						
						if( $woosheets_header_type == "productwise" ){
								if( $woosheets_epo == 0 ){
									$woosheets_epocount = 0;
									$woosheets_line_items = $woosheets_order->get_items( apply_filters( 'woocommerce_admin_order_item_types', 'line_item' ) );
									foreach ( $woosheets_line_items as $woosheets_item_id => $woosheets_item ) {
										$woosheets_yith_headers = array();
										$woosheets_item_meta = function_exists( 'wc_get_order_item_meta' ) ? wc_get_order_item_meta( $woosheets_item_id, '', FALSE ) : $woosheets_order->get_item_meta( $woosheets_item_id );	
										if( isset($woosheets_item_meta['_tmcartepo_data']) )
											$woosheets_producttmdata = unserialize($woosheets_item_meta['_tmcartepo_data'][0]);
										else
											$woosheets_producttmdata = '';
											
											$woosheets_yithmetaval = '';
											$woosheets_fkey = '';
											foreach ( $woosheets_filterd as $woosheets_flkey => $woosheets_flvalue ) {
												if( $woosheets_flvalue == $woosheets_headers_name[$i] && !in_array( $woosheets_flkey, $woosheets_custfieldkeys ) ){
													$woosheets_fkey = $woosheets_flkey;
													$woosheets_custfieldkeys[] = $woosheets_flkey;
													break;
												}
											}
											
												foreach($woosheets_producttmdata as $woosheets_pvalue){
													if( $woosheets_pvalue['section'] == $woosheets_fkey ){
														$woosheets_yithmetaval .= $woosheets_pvalue['value'].","; 
													}
												}
											
											$woosheets_yithmetaval = rtrim($woosheets_yithmetaval,',');
											if( $woosheets_epocount > 0 ){
												if(is_array($woosheets_yithmetaval)){
													$woosheets_prdarray[ $woosheets_epocount - 1][$i+1] = implode(',',$woosheets_yithmetaval);
												}else{
													$woosheets_prdarray[ $woosheets_epocount - 1][$i+1] = $woosheets_yithmetaval;
												}
											}else{
												if(is_array($woosheets_yithmetaval)){
													$woosheets_value[$i + 1] = implode(',',$woosheets_yithmetaval);
												}else{
													$woosheets_value[$i + 1] = $woosheets_yithmetaval;
												}		
											}
										unset($woosheets_yith_headers);
										$woosheets_epocount++;			
									}
									$woosheets_epo = 0;
									continue;	
								}
								
						}else{
							$woosheets_yithmetaval = '';
							$woosheets_fkey = '';
							foreach ( $woosheets_filterd as $woosheets_flkey => $woosheets_flvalue ) {
								if( $woosheets_flvalue == $woosheets_headers_name[$i] && !in_array( $woosheets_flkey, $woosheets_custfieldkeys ) ){
									$woosheets_fkey = $woosheets_flkey;
									$woosheets_custfieldkeys[] = $woosheets_flkey;
									break;
								}
							}
							
							$woosheets_line_items = $woosheets_order->get_items( apply_filters( 'woocommerce_admin_order_item_types', 'line_item' ) );
							foreach ( $woosheets_line_items as $woosheets_item_id => $woosheets_item ) {
								$woosheets_item_meta = function_exists( 'wc_get_order_item_meta' ) ? wc_get_order_item_meta( $woosheets_item_id, '', FALSE ) : $woosheets_order->get_item_meta( $woosheets_item_id );	
								if( isset($woosheets_item_meta['_tmcartepo_data']) ){
									$woosheets_producttmdata = unserialize($woosheets_item_meta['_tmcartepo_data'][0]);
									foreach($woosheets_producttmdata as $woosheets_pvalue){
										if( $woosheets_pvalue['section'] == $woosheets_fkey )
											$woosheets_yithmetaval .= $woosheets_pvalue['value'].","; 
									}										 
									$woosheets_yithmetaval = rtrim(trim($woosheets_yithmetaval),",");
									$woosheets_yithmetaval .= '|';
								}
							}
							
							$woosheets_value[] = rtrim(trim($woosheets_yithmetaval),"|");
							
						}
						continue;
					}
				}
				
				
				/*CPO Extra Product Option*/
				if(Wc_WooSheets_Setting::is_cpo_product_option_active()){
						if( in_array(stripslashes($woosheets_headers_name[$i]),$woosheets_cpo_product_option) ){
							if( $woosheets_header_type == "productwise" ){
									if( $woosheets_cpoflag == 0 ){
										$woosheets_cpocount = 0;
										foreach($woosheets_items as $woosheets_item){
											$woosheets_cpo_headers = array();
											$woosheets_metadata = $woosheets_item->get_formatted_meta_data();
												
												$woosheets_cpometaval = '';
												$woosheets_cpokey = array_search(stripslashes($woosheets_headers_name[$i]),$woosheets_cpo_product_option);
												
												if($woosheets_cpokey){
													foreach($woosheets_metadata as $woosheets_meta){
														if( strtolower($woosheets_meta->key) == strtolower($woosheets_cpokey)){
															$woosheets_cpometaval .= trim(strip_tags($woosheets_meta->display_value)).","; 
														}
													}
												}
												$woosheets_cpometaval = rtrim($woosheets_cpometaval,',');
												if( $woosheets_cpocount > 0 ){
													if(is_array($woosheets_cpometaval)){
														$woosheets_prdarray[$woosheets_cpocount - 1][$i+1] = implode(',',$woosheets_cpometaval);
													}else{
														$woosheets_prdarray[$woosheets_cpocount - 1][$i+1] = $woosheets_cpometaval;
													}
												}else{
													if(is_array($woosheets_cpometaval)){
														$woosheets_value[$i+1] = implode(',',$woosheets_cpometaval);
													}else{
														$woosheets_value[$i+1] = $woosheets_cpometaval;
													}		
												}
											unset($woosheets_cpo_headers);
											$woosheets_cpocount++;			
										}
										$woosheets_cpoflag = 0;	
									}
							}else{
								$woosheets_cpometaval = '';
								$woosheets_cpokey = array_search(stripslashes($woosheets_headers_name[$i]),$woosheets_cpo_product_option);
								foreach($woosheets_items as $woosheets_item){
									$woosheets_metadata = $woosheets_item->get_formatted_meta_data();
									foreach($woosheets_metadata as $woosheets_meta){
										if( strtolower($woosheets_meta->key) == strtolower($woosheets_cpokey))
											$woosheets_cpometaval .= trim(strip_tags($woosheets_meta->display_value)).","; 
									}										 
									$woosheets_cpometaval = rtrim(trim($woosheets_cpometaval),",");
									$woosheets_cpometaval .= '|';
								}
								
									$woosheets_value[] = ltrim(rtrim(trim($woosheets_cpometaval),"|"),"|");
							}
							continue;
						}
					}
					
				/*WooCommerce Booking*/	
				if( Wc_WooSheets_Setting::is_wc_bookings_active() ){
					if( in_array($woosheets_headers_name[$i],$woosheets_wc_bookings_field) ){
						
						if( $woosheets_header_type == "productwise" ){
							$woosheets_b_count = 0;
								$woosheets_aBookingQuery = new WP_Query( 
									array( 
										'post_parent'       => (int)$woosheets_order_id,
										'post_type'         => 'wc_booking',
										'posts_per_page'    => -1 ,
										'post_status' 		=> 'Any'
										)
									);
								$woosheets_bkids = array();
								if ( $woosheets_aBookingQuery->have_posts() ) : 
									while ( $woosheets_aBookingQuery->have_posts() ) : $woosheets_aBookingQuery->the_post(); 
										$woosheets_bkids[] = get_the_ID();
									endwhile; 
								endif;
								if( !empty($woosheets_bkids) ){
									$woosheets_bkids = array_reverse($woosheets_bkids);
									$woosheets_bkids = array_values($woosheets_bkids);
								}
									
									$woosheets_booking_start_date = $woosheets_booking_end_date = $woosheets_booking_resource = $woosheets_booking_cost = $woosheets_booking_status = $woosheets_booking_persons = $woosheets_booking_start_time = $woosheets_booking_end_time = '';
									$bkcount = 0;
								foreach( $woosheets_items as $woosheets_id => $woosheets_item ) {
									if ($woosheets_item['variation_id']) { 
										$woosheets_product = wc_get_product($woosheets_item['variation_id']);
									} elseif( $woosheets_item['product_id'] ) {
										$woosheets_product = wc_get_product($woosheets_item['product_id']);
									}
									if( !empty($woosheets_product) && ( $woosheets_product->is_type('accommodation-booking') || $woosheets_product->is_type('booking') ) && !empty($woosheets_bkids) ) {
									
									$woosheets_bkid = $woosheets_bkids[$bkcount];
									$bkcount++;
										$woosheets_booking = get_wc_booking($woosheets_bkid);
										$woosheets_booking_start_date = date( get_option('date_format') , $woosheets_booking->start);
										$woosheets_booking_end_date   = date( get_option('date_format') , $woosheets_booking->end);
										$woosheets_booking_start_time = date("H:i",$woosheets_booking->start).", ";
									    $woosheets_booking_end_time   = date("H:i",$woosheets_booking->end).", ";
										$woosheets_booking_cost   	= $woosheets_booking->cost;
										$woosheets_booking_status     = $woosheets_booking->status;
										if( isset($woosheets_booking->persons) && !empty($woosheets_booking->persons) ){
											foreach( $woosheets_booking->persons as $woosheets_bkey => $woosheets_bval){
												if($woosheets_bkey)
													$woosheets_booking_persons = get_the_title( $woosheets_bkey ).' : '.$woosheets_bval.', ';
												else
													$woosheets_booking_persons = $woosheets_bval.', ';	
											}
										}else{
											$woosheets_booking_persons = 0;	
										}
										if( !empty($woosheets_booking->resource_id) )
											$woosheets_booking_resource   =  get_the_title( $woosheets_booking->resource_id ); 
									if( $woosheets_b_count > 0 ){
										if( $woosheets_headers_name[$i] == 'Booking Start Date')
											$woosheets_prdarray[ $woosheets_b_count - 1][$i+1] = $woosheets_booking_start_date;
										if( $woosheets_headers_name[$i] == 'Booking End Date')
											$woosheets_prdarray[ $woosheets_b_count - 1][$i+1] = $woosheets_booking_end_date;
										if( $woosheets_headers_name[$i] == 'Booking Resource')
											$woosheets_prdarray[ $woosheets_b_count - 1][$i+1]  =  $woosheets_booking_resource;
										if( $woosheets_headers_name[$i] == 'Booking Cost')
											$woosheets_prdarray[ $woosheets_b_count - 1][$i+1] = $woosheets_booking_cost;
										if( $woosheets_headers_name[$i] == 'Booking Status')
											$woosheets_prdarray[ $woosheets_b_count - 1][$i+1] = ucfirst($woosheets_booking_status);
										if( $woosheets_headers_name[$i] == 'Booking Persons')
											$woosheets_prdarray[ $woosheets_b_count - 1][$i+1] = rtrim(trim($woosheets_booking_persons),",");
										if( $woosheets_headers_name[$i] == 'Booking Start Time')
											$woosheets_prdarray[ $woosheets_b_count - 1][$i+1] = rtrim(trim($woosheets_booking_start_time),",");
										if( $woosheets_headers_name[$i] == 'Booking End Time')
											$woosheets_prdarray[ $woosheets_b_count - 1][$i+1] = rtrim(trim($woosheets_booking_end_time),",");
											
									}else{	
										if( $woosheets_headers_name[$i] == 'Booking Start Date')
											$woosheets_value[$i + 1] = $woosheets_booking_start_date;
										if( $woosheets_headers_name[$i] == 'Booking End Date')
											$woosheets_value[$i + 1] = $woosheets_booking_end_date;
										if( $woosheets_headers_name[$i] == 'Booking Resource')
											$woosheets_value[$i + 1] = $woosheets_booking_resource;
										if( $woosheets_headers_name[$i] == 'Booking Cost')
											$woosheets_value[$i + 1] = $woosheets_booking_cost;
										if( $woosheets_headers_name[$i] == 'Booking Status')
											$woosheets_value[$i + 1] = ucfirst($woosheets_booking_status);
										if( $woosheets_headers_name[$i] == 'Booking Persons')
											$woosheets_value[$i + 1] = rtrim(trim($woosheets_booking_persons),",");
										if( $woosheets_headers_name[$i] == 'Booking Start Time')
											$woosheets_value[$i + 1] = rtrim(trim($woosheets_booking_start_time),",");
										if( $woosheets_headers_name[$i] == 'Booking End Time')
											$woosheets_value[$i + 1] = rtrim(trim($woosheets_booking_end_time),",");
									}
									$woosheets_b_count++;
								}
							}
							continue;
						}else{
							 
							$woosheets_aBookingQuery = new WP_Query( 
								array( 
									'post_parent'       => (int)$woosheets_order_id,
									'post_type'         => 'wc_booking',
									'posts_per_page'    => -1,
									'post_status' 		=> 'Any' 
									)
								);
							$woosheets_bkids = array();
							if ( $woosheets_aBookingQuery->have_posts() ) : 
								while ( $woosheets_aBookingQuery->have_posts() ) : $woosheets_aBookingQuery->the_post(); 
									$woosheets_bkids[] = get_the_ID();
								endwhile; 
							endif;
							if( !empty($woosheets_bkids) ){
								asort($woosheets_bkids);
							}
							$woosheets_booking_start_date = $woosheets_booking_end_date = $woosheets_booking_resource = $woosheets_booking_cost = $woosheets_booking_status = $woosheets_booking_persons = $woosheets_booking_personss = $woosheets_booking_start_time = $woosheets_booking_end_time = '';
							foreach($woosheets_bkids as $woosheets_bkid){
									$woosheets_booking = get_wc_booking($woosheets_bkid);
									$woosheets_booking_start_date .= date( get_option('date_format'), $woosheets_booking->start).", ";
									$woosheets_booking_end_date   .= date( get_option('date_format'), $woosheets_booking->end).", ";
									$woosheets_booking_start_time .= date("H:i",$woosheets_booking->start).", ";
									$woosheets_booking_end_time   .= date("H:i",$woosheets_booking->end).", ";
									$woosheets_booking_resource   .= get_the_title( $woosheets_booking->resource_id ).", "; 
									$woosheets_booking_cost   	.= $woosheets_booking->cost.", "; 
									$woosheets_booking_status     .= ucfirst($woosheets_booking->status).", "; 
									if( isset($woosheets_booking->persons) ){
										$woosheets_booking_persons = '';
										foreach( $woosheets_booking->persons as $woosheets_bkey => $woosheets_bval){
											if($woosheets_bkey)
												$woosheets_booking_persons .= get_the_title( $woosheets_bkey ).' : '.$woosheets_bval.', ';
											else
												$woosheets_booking_persons .= $woosheets_bval.', ';	
										}
										$woosheets_booking_personss .= $woosheets_booking_persons; 	
									}
							}
							if( $woosheets_headers_name[$i] == 'Booking Start Date')
								$woosheets_value[] = rtrim(trim($woosheets_booking_start_date),",");
							if( $woosheets_headers_name[$i] == 'Booking End Date')
								$woosheets_value[] = rtrim(trim($woosheets_booking_end_date),",");
							if( $woosheets_headers_name[$i] == 'Booking Resource')
								$woosheets_value[] = rtrim(trim($woosheets_booking_resource),",");
							if( $woosheets_headers_name[$i] == 'Booking Cost')
								$woosheets_value[] = rtrim(trim($woosheets_booking_cost),",");
							if( $woosheets_headers_name[$i] == 'Booking Status')
								$woosheets_value[] = rtrim(trim($woosheets_booking_status),",");
							if( $woosheets_headers_name[$i] == 'Booking Persons')
								$woosheets_value[] = rtrim(trim($woosheets_booking_personss),",");
							if( $woosheets_headers_name[$i] == 'Booking Start Time')
								$woosheets_value[] = rtrim(trim($woosheets_booking_start_time),",");
							if( $woosheets_headers_name[$i] == 'Booking End Time')
								$woosheets_value[] = rtrim(trim($woosheets_booking_end_time),",");
							continue;
						}
					}
				}			
				
				/*WooCommerce Custom Product Addons (Free)*/
				if(Wc_WooSheets_Setting::is_wcpa_product_field_active()){
						if( in_array( $woosheets_headers_name[$i], $woosheets_wcpa_product_field) ){
							if( $woosheets_header_type == "productwise" ){
									if( $woosheets_wcpaflag == 0 ){
										$woosheets_wcpacount = 0;
										foreach($woosheets_items as $woosheets_item){
											$woosheets_metadata = $woosheets_item->get_meta('_WCPA_order_meta_data');
												
												$woosheets_wcpametaval = '';
												$woosheets_wcpakey = array_search( $woosheets_headers_name[$i], $woosheets_wcpa_product_field);
												if($woosheets_wcpakey){
													foreach($woosheets_metadata as $woosheets_meta){
														if( strtolower($woosheets_meta['name']) == strtolower($woosheets_wcpakey)){
															if( is_array($woosheets_meta['value']) ){
																$woosheets_wcpametaval .= implode(',',$woosheets_meta['value']);
															}else{
																$woosheets_wcpametaval .= trim( $woosheets_meta['value'] ).",";	
															}
														}
													}
												}
												$woosheets_wcpametaval = rtrim($woosheets_wcpametaval,',');
												if( $woosheets_wcpacount > 0 ){
													if(is_array($woosheets_wcpametaval)){
														$woosheets_prdarray[$woosheets_wcpacount - 1][$i+1] = implode(',',$woosheets_wcpametaval);
													}else{
														$woosheets_prdarray[$woosheets_wcpacount - 1][$i+1] = $woosheets_wcpametaval;
													}
												}else{
													if(is_array($woosheets_wcpametaval)){
														$woosheets_value[$i+1] = implode(',',$woosheets_wcpametaval);
													}else{
														$woosheets_value[$i+1] = $woosheets_wcpametaval;
													}		
												}	
											$woosheets_wcpacount++;			
										}
										$woosheets_wcpaflag = 0;	
									}
							}else{
								$woosheets_wcpametaval = '';
								$woosheets_wcpakey = array_search( $woosheets_headers_name[$i], $woosheets_wcpa_product_field );
								foreach($woosheets_items as $woosheets_item){
									$woosheets_metadata = $woosheets_item->get_meta('_WCPA_order_meta_data');
									foreach($woosheets_metadata as $woosheets_meta){
										if( strtolower($woosheets_meta['name']) == strtolower($woosheets_wcpakey)){
											if( is_array($woosheets_meta['value']) ){
												foreach($woosheets_meta['value'] as $woosheets_m){
													$woosheets_wcpametaval .= $woosheets_m['value'] .",";		
												}
											}else{
												$woosheets_wcpametaval .= trim( $woosheets_meta['value'] ).",";	
											}	
										}	 
									}										 
									$woosheets_wcpametaval = rtrim(trim($woosheets_wcpametaval),",");
									$woosheets_wcpametaval .= '|';
								}
									$woosheets_value[] = ltrim(rtrim(trim($woosheets_wcpametaval),"|"),"|");
							}
							continue;
						}
					}
				
				if( $woosheets_header_type == 'productwise' ){
					$woosheets_productwisearray = array('Product Name','Product Meta','Product Image','Product ID','Product Quantity','Product Base Price','SKU','Product Total');
					if( in_array($woosheets_headers_name[$i],$woosheets_productwisearray) ){						
						if( $woosheets_prdflag == 0 ){
								$woosheets_prdcount = 0;
								foreach( $woosheets_items as $woosheets_id => $woosheets_item ) {
									$woosheets_product_id = '';
									$woosheets_quantity = '';
									$woosheets_total = '';
									$woosheets_product_name = '';
									
									if( 'Product ID' == $woosheets_headers_name[$i] ){
										$woosheets_product_id = $woosheets_item['product_id']?$woosheets_item['product_id']:'';	
										if($woosheets_prdcount > 0){
											$woosheets_prdarray[$woosheets_prdcount - 1][$i + 1] = 	$woosheets_product_id;
										}else{
											$woosheets_value[$i + 1] = 	$woosheets_product_id;
										}
									}
																
									if( 'Product Name' == $woosheets_headers_name[$i] ){
										$woosheets_product_name = $woosheets_item['name']?$woosheets_item['name']:'';	
										if($woosheets_prdcount > 0){
											$woosheets_prdarray[$woosheets_prdcount - 1][$i + 1] = 	$woosheets_product_name;
										}else{
											$woosheets_value[$i + 1] = 	$woosheets_product_name;
										}
									}
									
									if( 'Product Meta' == $woosheets_headers_name[$i] ){
										$woosheets_product_id = $woosheets_item['product_id']?$woosheets_item['product_id']:'';
										$woosheets_product = wc_get_product( $woosheets_product_id );
										$woosheets_meta_html = '';
										if( !empty($woosheets_product_id) ){
											if( $woosheets_product->is_type( 'variable' ) ){
												$woosheets_meta_html = Wc_WooSheets_Setting::getItemmeta($woosheets_item);
											} 
										}
										if($woosheets_prdcount > 0){
											$woosheets_prdarray[$woosheets_prdcount - 1][$i + 1] = 	$woosheets_meta_html;
										}else{
											$woosheets_value[$i + 1] = 	$woosheets_meta_html;
										}
									}				
									
									if( 'SKU' == $woosheets_headers_name[$i] ){
										  // Check if product has variation.
										  $woosheets_product_variation_id = $woosheets_item['variation_id'];
										  if ($woosheets_product_variation_id) { 
											$woosheets_product = wc_get_product($woosheets_item['variation_id']);
										  } else {
											$woosheets_product = wc_get_product($woosheets_item['product_id']);
										  }
										  $woosheets_sku = '';
										  if( $woosheets_product ){
										  	$woosheets_sku = $woosheets_product->get_sku();
										  }
										  if($woosheets_prdcount > 0){
												$woosheets_prdarray[$woosheets_prdcount - 1][$i + 1] = $woosheets_sku;
										  }else{
												$woosheets_value[$i + 1] = $woosheets_sku;
										  }
									}
										
									if( 'Product Quantity' == $woosheets_headers_name[$i] ){
										$woosheets_quantity = $woosheets_item['quantity']?$woosheets_item['quantity']:'';	
										if($woosheets_prdcount > 0){
											$woosheets_prdarray[$woosheets_prdcount - 1][$i + 1] = 	$woosheets_quantity;
										}else{
											$woosheets_value[$i + 1] = 	$woosheets_quantity;
										}
									}
									if( 'Product Base Price' == $woosheets_headers_name[$i] ){
										$woosheets_total = '';
										if ($woosheets_item['variation_id']) { 
											$woosheets_product = wc_get_product($woosheets_item['variation_id']);
											$woosheets_total = $woosheets_order->get_item_meta($woosheets_id, '_line_total', true);
										} elseif( $woosheets_item['product_id'] ) {
											$woosheets_product = wc_get_product($woosheets_item['product_id']);
											$woosheets_total = $woosheets_order->get_item_meta($woosheets_id, '_line_total', true);
										}
										
										if($woosheets_prdcount > 0){ 
											$woosheets_prdarray[$woosheets_prdcount - 1][$i + 1] = 	$woosheets_total;
										}else{
											$woosheets_value[$i + 1] = 	$woosheets_total;
										}
									}
									if( 'Product Total' == $woosheets_headers_name[$i] ){
										$woosheets_total = $woosheets_order->get_total()?$woosheets_order->get_total():'';
										$woosheets_fee_total 	= 0;
										$$woosheets_item_count 	= count( $woosheets_order->get_items() );
										foreach( $woosheets_order->get_fees() as $item_id => $item_fee ){
											$woosheets_fee_total = abs($item_fee->get_total());
										}
										if( $woosheets_fee_total > 0 ){
											$feediduction = $woosheets_fee_total / $woosheets_item_count;
											$$woosheets_amount = number_format((float)$feediduction, 2, '.', '');
											$woosheets_total = $woosheets_total - $woosheets_amount;
										}
										
										if($woosheets_prdcount > 0){
											$woosheets_prdarray[$woosheets_prdcount - 1][$i + 1] = 	$woosheets_total;
										}else{
											$woosheets_value[$i + 1] = 	$woosheets_total;
										}
									}	
									
									if( 'Product Image' == $woosheets_headers_name[$i] ){
											$woosheets_image_src = '';
											if( $woosheets_item['variation_id'] > 0){
													$woosheets_variable_product = wc_get_product($woosheets_item['variation_id']);	
													$woosheets_image_id = $woosheets_variable_product->get_image_id();
													$woosheets_image_array = wp_get_attachment_image_src( $woosheets_image_id, 'thumbnail' );
													$woosheets_image_src = $woosheets_image_array[0];
											}else{
												$woosheets_product_id = $woosheets_item['product_id']?$woosheets_item['product_id']:'';
												if($woosheets_product_id){
													$woosheets_variable_product = wc_get_product($woosheets_product_id);	
													$woosheets_image_id = $woosheets_variable_product->get_image_id();
													$woosheets_image_array = wp_get_attachment_image_src( $woosheets_image_id, 'thumbnail' );
													$woosheets_image_src = $woosheets_image_array[0];	
												}
											}
											
											if($woosheets_prdcount > 0){
												$woosheets_prdarray[$woosheets_prdcount - 1][$i + 1] = 	'=IMAGE("'.$woosheets_image_src.'")';
											}else{
												$woosheets_value[$i + 1] = 	'=IMAGE("'.$woosheets_image_src.'")';
											}
										
									}														
									$woosheets_prdcount++;
								}
								$woosheets_prdflag = 0; 
							}
						continue;
					}
				}else{
					if($woosheets_headers_name[$i] == 'Product name(QTY)(SKU)'){
						$woosheets_prod_qty = '';
						$woosheets_items = $woosheets_order->get_items();
						foreach($woosheets_items as $woosheets_item) {
							$woosheets_product_variation_id = $woosheets_item['variation_id'];
							  if ($woosheets_product_variation_id) { 
								$woosheets_product = wc_get_product($woosheets_item['variation_id']);
							  } else {
								$woosheets_product = wc_get_product($woosheets_item['product_id']);
							  }
							  $woosheets_sku = '';
							  if( $woosheets_product ){
									$woosheets_sku = '('.$woosheets_product->get_sku().')';
							  }
							$woosheets_product_name = $woosheets_item['name'].'('.$woosheets_item['quantity'].')'?$woosheets_item['name'].'('.$woosheets_item['quantity'].')'.$woosheets_sku:'';
							$woosheets_prod_qty .= ','.$woosheets_product_name;
						}
						$woosheets_value[] = ltrim($woosheets_prod_qty,',');
						continue;
					} 
				}
				
				if( is_array($woosheets_product_headers) && in_array( $woosheets_headers_name[$i], $woosheets_product_headers) ){
					$woosheets_insert_val = '';
					$woosheets_acount = 0;
					if( $woosheets_header_type == "productwise" ){
						foreach($woosheets_items as $woosheets_item){
							$woosheets_insert_val = '';
							if( preg_replace('/-/', '–', $woosheets_item->get_name(), 1) == $woosheets_headers_name[$i] ){
								$woosheets_insert_val = $woosheets_item->get_quantity();
							}
							if( $woosheets_acount > 0 ){
								$woosheets_prdarray[ $woosheets_acount - 1][$i+1] = $woosheets_insert_val;
							}else{
								$woosheets_value[$i + 1] = $woosheets_insert_val;		
							}
							$woosheets_acount++;
						}
					}else{
						foreach($woosheets_items as $woosheets_item){
							if( preg_replace('/-/', '–', $woosheets_item->get_name(), 1) == $woosheets_headers_name[$i] ){
								$woosheets_insert_val += $woosheets_item->get_quantity();
							}
						}
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}
					continue;	
				}
				
				if( is_array( $woosheets_extra_headers ) &&  in_array( $woosheets_headers_name[$i], $woosheets_extra_headers) ){
					$woosheets_extra_headers_val 	= apply_filters( 'woosheets_custom_values', $woosheets_order_id, $woosheets_headers_name[$i]);
					if( is_object($woosheets_extra_headers_val) ){
						$woosheets_extra_headers_val = '';	
					}
					if( is_array($woosheets_extra_headers_val) ){
						$woosheets_extra_headers_val = implode(',',$woosheets_extra_headers_val);	
					}
					$woosheets_insert_val		= $woosheets_extra_headers_val;
					if( $woosheets_header_type == "productwise" ){
						self::add_repeat_value( $woosheets_items, $woosheets_prdarray, $woosheets_value, $i, $woosheets_insert_val,1);
					}else{
						$woosheets_value[$i + 1] = $woosheets_insert_val;
					}	
					continue;	
				}
				$woosheets_value[$i + 1] = '';
			}
			
			if( $woosheets_header_type != "productwise"){
				$woosheets_value =  Wc_WooSheets_Setting::cleanArray($woosheets_value);
				$woosheets_values = array( $woosheets_value );
			}else{
				foreach($woosheets_prdarray as $woosheets_arrykey => $woosheets_valarray){
					$woosheets_prdarray[$woosheets_arrykey] = Wc_WooSheets_Setting::cleanArray($woosheets_valarray);	
				}
				$woosheets_value =  Wc_WooSheets_Setting::cleanArray($woosheets_value);
				array_unshift($woosheets_prdarray,$woosheets_value);
				$woosheets_values = $woosheets_prdarray;
			} 
			$woosheets_values = apply_filters( 'woosheets_values', $woosheets_values );
			return $woosheets_values; 
     }

	public static function cleanArray($woosheets_array)
	{
		end($woosheets_array);
		$woosheets_max = key($woosheets_array); //Get the final key as max!
		for($i = 0; $i < $woosheets_max; $i++)
		{
			if( !isset( $woosheets_array[$i] ) || is_null( $woosheets_array[$i] ) )
			{
				$woosheets_array[$i] = '';
			}else{
				$woosheets_array[$i] = trim($woosheets_array[$i]);
			}
		}
		ksort($woosheets_array);
		return $woosheets_array;
	}

	public static function add_repeat_value( $woosheets_items, &$woosheets_prdarray, &$woosheets_value, $i, $woosheets_insert_val = '', $is_individual = 0 ){
		$woosheets_is_repeat = get_option('repeat_checkbox');
		$woosheets_acount = 0;
		if( $is_individual ){
			$woosheets_insert_val = explode( ',', $woosheets_insert_val );
		}
		foreach($woosheets_items as $woosheets_item){
			if( $woosheets_acount > 0 && $woosheets_is_repeat == 'yes' ){
				if( $is_individual ){
					$woosheets_prdarray[ $woosheets_acount - 1][$i+1] = isset($woosheets_insert_val[$woosheets_acount])?$woosheets_insert_val[$woosheets_acount]:'';
				}else{
					$woosheets_prdarray[ $woosheets_acount - 1][$i+1] = $woosheets_insert_val;
				}
			}else{
				if( $is_individual ){
					$woosheets_value[$i + 1] = isset($woosheets_insert_val[$woosheets_acount])?$woosheets_insert_val[$woosheets_acount]:'';
				}else{
					$woosheets_value[$i + 1] = $woosheets_insert_val;	
				}
						
			}
			$woosheets_acount++;
		}
		return;	
	}

	public static function woosheets_export_order(){
								
		$woosheets_fromdate 		= $_POST['from_date'];
		$woosheets_todate 			= $_POST['to_date'];
		$woosheets_exportall 		= $_POST['exportall'];
		$woosheets_spreadsheetname 	= $_POST['spreadsheetname'];
		$iscategory_enable			= $_POST['category_select'];
		$woosheets_category_ids		= $_POST['category_ids'];
		$woosheets_client 			= Wc_WooSheets_Setting::getClient();
		$woosheets_service 			= new Google_Service_Sheets($woosheets_client);
		$woosheets_spreadsheetId 	= self::woosheets_create_spreadsheet( $woosheets_spreadsheetname );
		if( empty($woosheets_spreadsheetId) )
			return false;
		
		global $woosheets_default_status_slug;
		$woosheets_order_status_array = $woosheets_default_status_slug;
		/* Custom Order Status*/
		$woosheets_status_array = wc_get_order_statuses();
		$woosheets_status_array['wc-trash'] = 'Trash';
		foreach($woosheets_status_array as $woosheets_key => $woosheets_val){
			if(!in_array($woosheets_key,$woosheets_order_status_array)){ 
				$woosheets_order_status_array[] = $woosheets_key;
				$woosheets_custom_order_status[$woosheets_key] = $woosheets_val;
			}
		}
		$woosheets_existingsheetsnames = array();
		$woosheets_response = $woosheets_service->spreadsheets->get( $woosheets_spreadsheetId );
			foreach( $woosheets_response->getSheets() as $woosheets_key => $woosheets_value ) {
			 	$woosheets_existingsheetsnames[$woosheets_value['properties']['title']] = $woosheets_value['properties']['sheetId'];
		}
		/**/
		$woosheets_headers 			= stripslashes_deep(get_option('sheet_headers'));
		$woosheets_headers_custom 	= stripslashes_deep(get_option('sheet_headers_list_custom', array() ));
		if( !empty( $woosheets_headers_custom ) )
			$woosheets_headers = $woosheets_headers_custom;
		array_unshift($woosheets_headers, "Order Id");
		$woosheets_value = array($woosheets_headers);
		for( $i = 0; $i<count($woosheets_order_status_array); $i++ ){ 
			$woosheets_sheetname = '';
			if( ($i == '0') && (get_option('pending_orders') == 'yes') ){
				$woosheets_sheetname = 'Pending Orders';							
			}elseif( ($i == '1') && (get_option('processing_orders') == 'yes') ){
				$woosheets_sheetname = 'Processing Orders';	
			}elseif( ($i == '2') && (get_option('on_hold_orders') == 'yes') ){
				$woosheets_sheetname = 'On Hold Orders';
			}elseif( ($i == '3') && (get_option('completed_orders') == 'yes') ){
				$woosheets_sheetname = 'Completed Orders';
			}elseif( ($i == '4') && (get_option('cancelled_orders') == 'yes') ){
				$woosheets_sheetname = 'Cancelled Orders';
			}elseif( ($i == '5') && (get_option('refunded_orders') == 'yes') ){
				$woosheets_sheetname = 'Refunded Orders';
			}elseif( ($i == '6') && (get_option('failed_orders') == 'yes') ){
				$woosheets_sheetname = 'Failed Orders'; 
			}
			
			if( $i > 6 ){
				$woosheets_status = substr($woosheets_order_status_array[$i], strpos($woosheets_order_status_array[$i], "-") + 1); 
					if(get_option($woosheets_status) == 'yes'){
						$woosheets_sheetname = $woosheets_custom_order_status[$woosheets_order_status_array[$i]] . ' Orders';	
					}
			}
			if($woosheets_order_status_array[$i] == 'wc-trash'){ $woosheets_order_status_array[$i] = 'trash'; }
			if(!empty($woosheets_sheetname)){
				$woosheets_activesheets[$woosheets_order_status_array[$i]] = $woosheets_sheetname;
				/*Create new sheet into spreadsheet*/
				$woosheets_body = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
					'requests' => array(
						'addSheet' => array(
					 		'properties' => array(
								'title' => $woosheets_sheetname
							)
						)
					)
				));
			 	$woosheets_response = $woosheets_service->spreadsheets->batchUpdate($woosheets_spreadsheetId,$woosheets_body);
			 	$woosheets_range = trim($woosheets_sheetname).'!A1';
				$woosheets_requestBody = new Google_Service_Sheets_ValueRange(array(
					'values' => $woosheets_value
				));
				
				$woosheets_params = array( 'valueInputOption' => 'USER_ENTERED' ); 
		
				$woosheets_response = $woosheets_service->spreadsheets_values->append($woosheets_spreadsheetId, $woosheets_range, $woosheets_requestBody, $woosheets_params);
				/**/
			}
		}
		/*Delete Default sheet*/
		$woosheets_requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
		'requests' => array(
				'deleteSheet' => array(
					'sheetId' => 0
					)
				)));
		$woosheets_response = $woosheets_service->spreadsheets->batchUpdate($woosheets_spreadsheetId, $woosheets_requestBody);

			$woosheets_dataarray = array();
			$woosheets_isexecute = 0;
			foreach($woosheets_activesheets as $woosheets_sheet_slug => $woosheets_sheetname){
					 $woosheets_query_args = array(
						'post_type'      => 'shop_order',
						'post_status'    => $woosheets_sheet_slug,
						'posts_per_page' => 999999999999,
						'order' => 'ASC'
						);							
					$woosheets_all_orders = get_posts( $woosheets_query_args );
					if( empty($woosheets_all_orders) )
						continue;
					$woosheets_values_array = array();
					
					foreach ( $woosheets_all_orders as $woosheets_order ) {
						
						set_time_limit(999);
						$woosheets_order = wc_get_order( $woosheets_order->ID );
						if( $iscategory_enable == 'yes' ){
							$woosheets_flag = 'exclude';
							$woosheets_items = $woosheets_order->get_items();
							foreach ( $woosheets_items as $item ) {
								$terms = get_the_terms ( $item->get_product_id(), 'product_cat' );
								foreach ( $terms as $term ) {
									if( in_array($term->term_id, $woosheets_category_ids) ){
										$woosheets_flag = 'include';
										break;	
									}
								}
							}
							if( $woosheets_flag == 'exclude' )
								continue;
						}
						
						if( $woosheets_exportall == 'no' ){
							$woosheets_orderdate = new DateTime( $woosheets_order->get_date_created()->format ('Y-m-d'));
							$woosheets_datefrom  = new DateTime( $woosheets_fromdate );
							$woosheets_dateto    = new DateTime( $woosheets_todate );
							
							if( $woosheets_orderdate < $woosheets_datefrom || $woosheets_orderdate > $woosheets_dateto )
								continue;
						}
						$woosheets_order_data = $woosheets_order->get_data(); 
						$woosheets_status = $woosheets_order_data['status'];
						$woosheets_value = Wc_WooSheets_Setting::make_value_array('insert',$woosheets_order->get_id());
						$woosheets_values_array = array_merge($woosheets_values_array, $woosheets_value); 					
					}
					if(!empty( $woosheets_values_array ) ){
							try{
							$woosheets_newarray = array();
							foreach ($woosheets_values_array as $key => $rowvalue) {
								$woosheets_newarray[] = array_map('trim', $rowvalue);
								
							}
							$woosheets_requestBody = new Google_Service_Sheets_ValueRange(array( 'values' => $woosheets_newarray ));
							$woosheets_params = array( 'valueInputOption' => 'USER_ENTERED' ); 
							$woosheets_response = $woosheets_service->spreadsheets_values->append($woosheets_spreadsheetId, $woosheets_sheetname, $woosheets_requestBody, $woosheets_params);
							}catch(Exception $e) {
							  echo 'Message: ' .$e->getMessage();
							}	
					}
				}
				$woosheets_resultdata = array();
				$woosheets_resultdata['result'] = 'successful';
				$woosheets_resultdata['spreadsheetid'] = $woosheets_spreadsheetId;
				echo json_encode($woosheets_resultdata);
			die;
		}
		
		public static function woosheets_create_spreadsheet( $woosheets_spreadsheetname = '' ){
			if( !empty( $woosheets_spreadsheetname ) ){
				$woosheets_client = Wc_WooSheets_Setting::getClient();
				$woosheets_service = new Google_Service_Sheets($woosheets_client);
				
				$woosheets_newsheetname = trim($woosheets_spreadsheetname); 
				/*
				 *Create new spreadsheet
				 */
				$woosheets_requestBody = new Google_Service_Sheets_Spreadsheet(array(
					 "properties" => array(
						 "title"=> $woosheets_newsheetname
					  ),
				 ));
				$woosheets_response = $woosheets_service->spreadsheets->create($woosheets_requestBody);
				$woosheets_spreadsheetId = $woosheets_response['spreadsheetId'];	
				return 	$woosheets_spreadsheetId;
			}	
			return;	
		}
		
		/**
		 * 
		 * @param int $order_id
		 * @return array
		 */
		public static function woosheets_get_all_order_notes( $order_id ){
			$order_notes	=	array();
			$args = array (
					'post_id' 	=> $order_id,
					'orderby' 	=> 'comment_ID',
					'order' 	=> 'DESC',
					'approve' 	=> 'approve',
					'type' 		=> 'order_note'
			);
			remove_filter ( 'comments_clauses', array (
					'WC_Comments',
					'exclude_order_comments'
			), 10, 1 );
			
			$notes = get_comments ( $args );
			if ($notes) {
				foreach ( $notes as $note ) {
					$order_notes[]	=  wp_kses_post ( $note->comment_content );
				}
			} 
			
			return $order_notes;
		}

		public static function woosheets_all_orders( $woosheets_order_id, $woosheets_sheetname ){
			
			$woosheets_client = Wc_WooSheets_Setting::getClient();
			$woosheets_service = new Google_Service_Sheets($woosheets_client);
			/**/
			$woosheets_order = wc_get_order( $woosheets_order_id );	
			$woosheets_spreadsheetId = get_option('woocommerce_spreadsheet');
			
			$woosheets_sheet 	= "'".$woosheets_sheetname."'!A:A";
			$woosheets_allentry = $woosheets_service->spreadsheets_values->get($woosheets_spreadsheetId, $woosheets_sheet);
			$woosheets_data 	= $woosheets_allentry->getValues();
							
			$woosheets_data = array_map(
				function($woosheets_element)
				{
					if(isset($woosheets_element['0'])){
						return $woosheets_element['0'];
					}else{
						return "";
					}
				}, 
				$woosheets_data
			);
			$woosheets_num = array_search( $woosheets_order_id, $woosheets_data );
			if( $woosheets_num > 0){
				$woosheets_values = Wc_WooSheets_Setting::make_value_array('insert',$woosheets_order_id );
				$woosheets_response = $woosheets_service->spreadsheets->get($woosheets_spreadsheetId);
			
				foreach($woosheets_response->getSheets() as $woosheets_key => $woosheets_value) {
					 $woosheets_existingsheetsnames[$woosheets_value['properties']['title']] = $woosheets_value['properties']['sheetId'];
				}
				$woosheets_sheetID = $woosheets_existingsheetsnames['All Orders'];
				$woosheets_num = $woosheets_num + 1;
				//Add or Remove Row at spreadsheet
				$woosheets_ordrow = 0;
				$woosheets_notempty = 0;
				end($woosheets_data);         
				$woosheets_lastElement = key($woosheets_data); 
				reset($woosheets_data);
				
				for( $i = $woosheets_num ; $i < count($woosheets_data); $i++ ){
					if(  $woosheets_data[$i] == $woosheets_order->get_id() ){
						$woosheets_ordrow++; 
						if( $woosheets_lastElement == $i ){
							$woosheets_ordrow++;		
						}
					}else{
						if( $woosheets_lastElement == $i ){
							$woosheets_notempty = 1;	
							if( $woosheets_ordrow > 0)
								$woosheets_ordrow++;
						}else{
							$woosheets_ordrow++;	
						}
						break; 
					}
				}
				
				$woosheets_samerow = 0;
				if( $woosheets_ordrow == 0 ){
					$woosheets_samerow = 1;
				}
				
				if( $woosheets_samerow == 1 && $woosheets_header_type == "productwise" && $woosheets_notempty == 0 ){
					$woosheets_alphabet = range('A', 'Z');
					$woosheets_alphaindex = '';
					$woosheets_isID = array_search('Product ID', $woosheets_headers_name);
					if( $woosheets_isID ){
						$woosheets_alphaindex = $woosheets_alphabet[$woosheets_isID+1];	
					}else{
						$woosheets_isName = array_search('Product Name', $woosheets_headers_name);
						if( $woosheets_isName ){
							$woosheets_alphaindex = $woosheets_alphabet[$woosheets_isName+1];	
						}		
					}
					if( $woosheets_alphaindex != '' ){
						$woosheets_rangetofind = $woosheets_sheetname.'!'.$woosheets_alphaindex.$woosheets_num.':'.$woosheets_alphaindex;
						$woosheets_allentry = $woosheets_service->spreadsheets_values->get($woosheets_spreadsheetId, $woosheets_rangetofind);
						$woosheets_data = $woosheets_allentry->getValues();
						
						$woosheets_data = array_map(
							function( $woosheets_element )
							{
								if( isset( $woosheets_element['0'] ) ){
									return $woosheets_element['0'];
								}else{
									return "";
								}
							}, 
							$woosheets_data
						);
						if( ( count( $woosheets_values ) < count( $woosheets_data ) )){
							$woosheets_ordrow = count($woosheets_data);	
							$woosheets_samerow = 0;
						}
					}
					
				}
				if( $woosheets_notempty == 1 && $woosheets_ordrow == 0 ){
					 $woosheets_samerow = 0;
					 $woosheets_ordrow = 1;
				}
					
				if( ( count( $woosheets_values ) > $woosheets_ordrow ) && $woosheets_samerow == 0 ){//Insert blank row into spreadsheet
						$woosheets_endIndex = count( $woosheets_values ) - (int)$woosheets_ordrow;
						$woosheets_endIndex = (int)$woosheets_endIndex + (int)$woosheets_num;
						$woosheets_requests = array(
							'insertDimension' => array(
								'range' => array(
									'sheetId' => $woosheets_sheetID,
									'dimension' => "ROWS",
									'startIndex' => $woosheets_num,
									'endIndex' => $woosheets_endIndex
								)
							)
						);
					$woosheets_batchUpdateRequest = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
						'requests' => $woosheets_requests
					));
					$woosheets_response = $woosheets_service->spreadsheets->batchUpdate( $woosheets_spreadsheetId, $woosheets_batchUpdateRequest );	
				
				}elseif( count( $woosheets_values ) < $woosheets_ordrow && $woosheets_samerow == 0){//Remove extra row from spreadhseet
					$woosheets_endIndex =  (int)$woosheets_ordrow - count( $woosheets_values );	
					$woosheets_endIndex = (int)$woosheets_endIndex + (int)$woosheets_num;				
					$woosheets_requestBody = new Google_Service_Sheets_BatchUpdateSpreadsheetRequest(array(
					'requests' => array(

							'deleteDimension' => array(
								'range' => array(
								  'dimension' => 'ROWS',
								  'sheetId' => $woosheets_sheetID,
								  'startIndex' => $woosheets_num,
								  'endIndex' => $woosheets_endIndex
								)
							)
						)));	
					$woosheets_response = $woosheets_service->spreadsheets->batchUpdate( $woosheets_spreadsheetId, $woosheets_requestBody );					
				}
					//End of add- remove row at spreadsheet
					
					$woosheets_rangetoupdate = $woosheets_sheetname.'!A'.$woosheets_num;
					$woosheets_requestBody = new Google_Service_Sheets_ValueRange(array(
						'values' => $woosheets_values
					));
					$woosheets_params = array( 'valueInputOption' => 'USER_ENTERED' ); //USER_ENTERED
						$woosheets_response = $woosheets_service->spreadsheets_values->update( $woosheets_spreadsheetId, $woosheets_rangetoupdate, $woosheets_requestBody, $woosheets_params );	
			}else{
				Wc_WooSheets_Setting::insert_data_into_sheet($woosheets_order_id, $woosheets_sheetname,0 );
			}
			return;
		}

	public static function get_column_index($number)
	{
		if ($number <= 0) return null;
	
		$temp; $letter = '';
		while ($number > 0) {
			$temp = ($number - 1) % 26;
			$letter = chr($temp + 65) . $letter;
			$number = ($number - $temp - 1) / 26;
		}
		return $letter;
	}
	
	
	public static function clear_all_sheet(){
		
		$woosheets_client = Wc_WooSheets_Setting::getClient();
		$woosheets_service = new Google_Service_Sheets($woosheets_client);
		$woosheets_spreadsheetId = get_option('woocommerce_spreadsheet');
		
		$requestBody 				= new Google_Service_Sheets_ClearValuesRequest();
		$total_headers 				= count(get_option('sheet_headers_list'))+1;
		$last_column 				= Wc_WooSheets_Setting::get_column_index($total_headers);
		
		global $woosheets_default_status_slug;
		$woosheets_order_status_array = $woosheets_default_status_slug;
		/* Custom Order Status*/
		$woosheets_status_array = wc_get_order_statuses();
		$woosheets_status_array['wc-trash'] = 'Trash';

		foreach($woosheets_status_array as $woosheets_key => $woosheets_val){
			if(!in_array($woosheets_key,$woosheets_order_status_array)){ 
				$woosheets_order_status_array[] = $woosheets_key;
				$woosheets_custom_order_status[$woosheets_key] = $woosheets_val;
			}
		}
		$woosheets_existingsheetsnames = array();
		$woosheets_response = $woosheets_service->spreadsheets->get( $woosheets_spreadsheetId );
			foreach( $woosheets_response->getSheets() as $woosheets_key => $woosheets_value ) {
			 	$woosheets_existingsheetsnames[$woosheets_value['properties']['title']] = $woosheets_value['properties']['sheetId'];
		}
		/**/
		for( $i = 0; $i<count($woosheets_order_status_array); $i++ ){ 
			$woosheets_sheetname = '';
			if( ($i == '0') && (get_option('pending_orders') == 'yes') ){
				$woosheets_sheetname = 'Pending Orders';							
			}elseif( ($i == '1') && (get_option('processing_orders') == 'yes') ){
				$woosheets_sheetname = 'Processing Orders';	
			}elseif( ($i == '2') && (get_option('on_hold_orders') == 'yes') ){
				$woosheets_sheetname = 'On Hold Orders';
			}elseif( ($i == '3') && (get_option('completed_orders') == 'yes') ){
				$woosheets_sheetname = 'Completed Orders';
			}elseif( ($i == '4') && (get_option('cancelled_orders') == 'yes') ){
				$woosheets_sheetname = 'Cancelled Orders';
			}elseif( ($i == '5') && (get_option('refunded_orders') == 'yes') ){
				$woosheets_sheetname = 'Refunded Orders';
			}elseif( ($i == '6') && (get_option('failed_orders') == 'yes') ){
				$woosheets_sheetname = 'Failed Orders'; 
			}
			
			if( $i > 6 ){
				$woosheets_status = substr($woosheets_order_status_array[$i], strpos($woosheets_order_status_array[$i], "-") + 1); 
					if(get_option($woosheets_status) == 'yes'){
						$woosheets_sheetname = $woosheets_custom_order_status[$woosheets_order_status_array[$i]] . ' Orders';	
					}
			}
			if($woosheets_order_status_array[$i] == 'wc-trash'){ $woosheets_order_status_array[$i] = 'trash'; }
						
			if(!empty($woosheets_sheetname)){
				try{
				$range = $woosheets_sheetname.'!A2:'.$last_column.'10000';
				$response = $woosheets_service->spreadsheets_values->clear($woosheets_spreadsheetId, $range, $requestBody);
				}catch(Exception $e){}
			}
		}
		
		if( get_option('all_orders') == 'yes') {
			$woosheets_sheetname = 'All Orders';
			try{
			$range = $woosheets_sheetname.'!A2:'.$last_column.'10000';
			$response = $woosheets_service->spreadsheets_values->clear($woosheets_spreadsheetId, $range, $requestBody);
			}catch(Exception $e){}
		}
		echo 'successful';
		die();
	}
}
Wc_WooSheets_Setting::init();