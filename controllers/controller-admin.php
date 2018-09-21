<?php
/**
 * Admin Controller for GoogleAPI
 *
 * @package GoogleAPI
 * @subpackage Controllers
 * @author Rico Paridaens
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Admin Controller class
 * @package GoogleAPI
 * @subpackage Controllers
 * @author Rico Paridaens
 * @since 1.0.0
 */
class Branzel_GoogleAPI_Admin_Controller extends Branzel_GoogleAPI_Controller  {
	/**
	 * Initialize the Admin Controller, determine location the admin menu, set up actions.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct();
		
		//add_action( 'admin_menu', array( $this, 'add_admin_menu_entry' ) );
		add_action( 'admin_menu', array( $this, 'add_admin_actions' ) );
		add_action( 'admin_init', array( $this, 'register_plugin_settings' ) );
	}
	
	/**
	 * Add admin screens to the correct place in the admin menu.
	 *
	 * @since 1.0.0
	 */
	public function render_adminpage() {
		if( $this->deauthorizeGoogleAccount() ) {
			Branzel_GoogleAPI::$client->revokeToken();
            // TODO: finsish this delete_option unset()
            delete_option( Branzel_GoogleAPI::optionNames['doReset'] );
            delete_option( Branzel_GoogleAPI::optionNames['token_expires'] );
            delete_option( Branzel_GoogleAPI::optionNames['code'] );
            delete_option( Branzel_GoogleAPI::optionNames['access_token'] );
        } ?>

        <div class="wrap">
            <h2>Google Photos Settings</h2>

            <?php 
                // Step 1:  The user has not authenticated we give them a link to login    
                if( Branzel_GoogleAPI::isAuthenticated() !== true ) { ?>

                <form action="options.php" method="post">
            <?php 
                    // settings_fields( $option_group )
                    // Output nonce, action, and option_page fields for a settings page. Please note that this function must be called inside of the form tag for the options page.
                    // $option_group - A settings group name. This should match the group name used in register_setting(). 
                    settings_fields( 'cws_gpp_code' );

                    // do_settings_sections( $page );
                    // Prints out all settings sections added to a particular settings page.
                    // The slug name of the page whose settings sections you want to output. This should match the page name used in add_settings_section().
                    do_settings_sections( 'branzel_googleapi_authenticate' ); 
                    ?>
                    <input name="Submit" type="submit" value="Save Changes" />  

                </form> 
            <?php
                } else { ?>

                <form action="options.php" method="post">

            <?php   settings_fields( 'branzel_gpp_reset' );
                    do_settings_sections( 'branzel_gpp_reset' );  
            ?>
					<p class="submit">
						<input name="Submit" type="submit" class="button button-primary" value="Deauthorise" onclick="if(!this.form.reset.checked){alert('You must click the checkbox to confirm you want to deauthorize current Google account.');return false}" />
					</p>
                </form>                             
            <?php                      
                }
            ?>

                <?php // $this->cws_gpp_meta_box_feedback(); ?>
        </div>
        <?php
    }

	/**
	 * Set up handlers for user actions in the backend that exceed plain viewing.
	 *
	 * @since 1.0.0
	 */
	public function add_admin_actions() {
		$icon_url = 'dashicons-googleplus';
		$position = ( ++$GLOBALS['_wp_last_utility_menu'] );
		
		add_menu_page( __('Google API', 'googleapi'), 'Google API', 'manage_options', 'branzel_googleapi', array( $this, 'render_adminpage'), $icon_url, $position );
	}
	
	/**
	 * Register Settings, Settings Section and Settings Fileds.
     * 
     * @link    https://codex.wordpress.org/Function_Reference/register_setting
     * @link    https://codex.wordpress.org/Function_Reference/add_settings_section
     * @link    https://codex.wordpress.org/Function_Reference/add_settings_field
	 *
	 * @since    2.0.0
	 */    
    public function register_plugin_settings() {
        // register_setting( $option_group, $option_name, $sanitize_callback ).
        // $option_group - A settings group name. Must exist prior to the register_setting call. This must match the group name in settings_fields().
        // $option_name - The name of an option to sanitize and save.
        // $sanitize_callback - A callback function that sanitizes the option's value.
        // register_setting( 'cws_gpp_options', 'cws_gpp_options', array( $this, 'validate_main_options' ) );

        register_setting( 'branzel_gpp_reset', Branzel_GoogleAPI::optionNames['doReset'], array( $this, 'validate_reset_options' ) );

        // add_settings_section( $id, $title, $callback, $page )
        // $id - String for use in the 'id' attribute of tags
        // $title - Title of the section
        // $callback - Function that fills the section with the desired content. The function should echo its output.
        // $page - The menu page on which to display this section. Should match $menu_slug in add_options_page();
        add_settings_section( 'cws_gpp_add_code', 'Authenticate with Google', array( $this, 'section_text' ), 'branzel_googleapi_authenticate' );
        // add_settings_section( 'cws_gpp_add_options', 'Default Settings', array( $this, 'section_main_text' ), 'cws_gpp_defaults' );

        add_settings_section( 'branzel_gpp_add_reset', 'Deauthorise Plugin from your Google Account', array( $this, 'section_reset_text' ), 'branzel_gpp_reset' );

        // add_settings_field( $id, $title, $callback, $page, $section, $args );
        // $id - String for use in the 'id' attribute of tags
        // $title - Title of the field
        // $callback - Function that fills the field with the desired inputs as part of the larger form. Passed a single argument, 
        // the $args array. Name and id of the input should match the $id given to this function. The function should echo its output.
        // $page - The menu page on which to display this field. Should match $menu_slug in add_options_page();
        // $section - (optional) The section of the settings page in which to show the box. A section added with add_settings_section() [optional]
        // $args - (optional) Additional arguments that are passed to the $callback function
        // add_settings_field( 'cws_myplugin_oauth2_code', 'Enter Google Access Code here', array( $this, 'setting_input' ), 'branzel_googleapi_authenticate', 'cws_gpp_add_code' );
        
        // Add reset option
        add_settings_field( Branzel_GoogleAPI::optionNames['doReset'], 'Click here to confirm you want to deauthorise plugin from your google account', array( $this, 'options_reset' ), 'branzel_gpp_reset', 'branzel_gpp_add_reset' );   
    }
	/**
	 * Authenticate the client.
	 *
	 * @since 1.0.0
	 */
    private function authentication_process() {
		
		
		// Branzel_GoogleAPI::$client->setAuthConfig('client_secrets.json');
		Branzel_GoogleAPI::$client->setApplicationName("Branzel's Google Photo's API WP Plugin");
        Branzel_GoogleAPI::$client->setClientId('95707748700-cs9p8b8706c4kcmlknv2nfgb400ltjj7.apps.googleusercontent.com');
        Branzel_GoogleAPI::$client->setClientSecret('MlmFOkNfxnD4laMDrBPigJ3o');
		Branzel_GoogleAPI::$client->setAccessType("offline");        // offline access
		Branzel_GoogleAPI::$client->setIncludeGrantedScopes(true);   // incremental auth
		Branzel_GoogleAPI::$client->addScope(Google_Service_PhotosLibrary::DRIVE_PHOTOS_READONLY);
		Branzel_GoogleAPI::$client->addScope(Google_Service_PhotosLibrary::PHOTOSLIBRARY_READONLY);
		Branzel_GoogleAPI::$client->setRedirectUri( admin_url( 'admin.php?page=branzel_googleapi', 'https' ) );

        if ( ! isset($_GET['code']) ) 
        {
            $loginUrl = Branzel_GoogleAPI::$client->createAuthurl();
			
			return $loginUrl;
        }

		return null;
    }
	
	/**
	 * Get the authourisation link.
	 *
	 * @since 1.0.0
	 */   
    public function createAuthLink( $authUrl ) {

        if ( isset( $authUrl ) ) {

            $output = "<br><br><a class='login' href='$authUrl' target='_blank'>Connect My Google Account</a>"; 
        } else {
            $output = "There was a problem generating the Google Autherisation link";
        }

        return $output;
    }
	
	/**
	 * Draw the Section Header for the admin area.
	 *
	 * @since 1.0.0
	 */
    function section_text() {
		if (! isset($_GET['code']) ) {
		
        echo 'You need to click here to authorize access and paste the Access Code provided by Google in the field below.';
		
		// get the google authorisation url
        //$authUrl = $this->client->createAuthUrl();
        $authUrl = $this->authentication_process();

//var_dump($authUrl);

        // display the google authorisation url
        echo $this->createAuthLink( $authUrl );
        
        $code = get_option( 'branzel_gpp_code' );
        $oauth2_code = $code['oauth2_code'];
        
        $token = Branzel_GoogleAPI::getAccessToken();
        $token = $token['access_token'];
  
//var_dump($code['oauth2_code'] );
		}

        if ( isset($_GET['code']) ) {

		Branzel_GoogleAPI::$client->setApplicationName("Branzel's Google Photo's API WP Plugin");
        Branzel_GoogleAPI::$client->setClientId('95707748700-cs9p8b8706c4kcmlknv2nfgb400ltjj7.apps.googleusercontent.com');
        Branzel_GoogleAPI::$client->setClientSecret('MlmFOkNfxnD4laMDrBPigJ3o');
		Branzel_GoogleAPI::$client->setAccessType("offline");        // offline access
		Branzel_GoogleAPI::$client->setIncludeGrantedScopes(true);   // incremental auth
		Branzel_GoogleAPI::$client->addScope(Google_Service_PhotosLibrary::DRIVE_PHOTOS_READONLY);
		Branzel_GoogleAPI::$client->addScope(Google_Service_PhotosLibrary::PHOTOSLIBRARY_READONLY);
		Branzel_GoogleAPI::$client->setRedirectUri( admin_url( 'admin.php?page=branzel_googleapi', 'https' ) );


            // $this->client->authenticate( $code['oauth2_code'] );  
            Branzel_GoogleAPI::$client->authenticate( $_GET['code'] );
            //$AccessToken = $this->client->getAccessToken();
            $AccessToken = Branzel_GoogleAPI::$client->getAccessToken();
			// print_r( $AccessToken);
            // $AccessToken = json_decode( $AccessToken, TRUE );
            
            // delete code
           	$code = get_option( Branzel_GoogleAPI::optionNames['code'] );
            
            if ( $code ) {
                unset($_GET['code']);
                update_option( Branzel_GoogleAPI::optionNames['code'], $code );
            }
                        
            // store access token
            if( update_option( Branzel_GoogleAPI::optionNames['access_token'], $AccessToken ) ) {
                //if( $this->debug ) error_log( 'Update option: cws_gpp_access_token' );
               
                // store token expires
                $now = date("U");
                $token_expires = $now + $AccessToken['expires_in'];
                add_option( Branzel_GoogleAPI::optionNames['token_expires'], $token_expires );      
                
                $url = admin_url( "options-general.php?page=".$_GET["page"] );
                // error_log($url);
                
                wp_redirect( "$url" );
                exit;               
            }
        }        
    }
	
    /**
     * Validate user input.
     *
     * @since    2.0.0
     */         
    function validate_reset_options( $input ) {

		print_r($input);
        // Correct validation of checkboxes
        $valid['reset'] = ( isset( $input['reset'] ) && true == $input['reset'] ? true : false );

        return $valid;
    } 
	
    //
    function section_reset_text() {
        
    } 
	
    /**
     * Display and fill the form fields for storing defaults.
     *
     * Show Album Details
     *
     * @since    2.0.0
     */    
    function options_reset() {

        // set some defaults...
        $checked = '';

         // get option 'show_album_details' value from the database
        $options = get_option( Branzel_GoogleAPI::optionNames['doReset'] );       
        
        if($options[Branzel_GoogleAPI::optionNames['doReset']]) { $checked = ' checked="checked" '; }
        echo "<input ".$checked." id='reset' name='branzel_gpp_reset[reset]' type='checkbox' required />";
    } 
	
	/** GOOD WORKFLOW OF STEPS https://www.domsammut.com/code/php-server-side-youtube-v3-oauth-api-video-upload-guide **/   
    /**
     * Get the Reset option stored in db.
     *
     * @since    2.0.0
     */  
    public function deauthorizeGoogleAccount() {
        // get options from db

        if( get_option( Branzel_GoogleAPI::optionNames['doReset'] ) ){
            return true;
        } 

        return false;
    }
}