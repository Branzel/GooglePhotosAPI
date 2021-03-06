<?php
/**
 * GoogleAPI Class
 *
 * @package GoogleAPI
 * @author Rico Paridaens
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * GoogleAPI class
 * @package GoogleAPI
 * @author Rico Paridaens
 * @since 1.0.0
 */
abstract class Branzel_GoogleAPI {
	/**
	 * GoogleAPI version.
	 *
	 * Increases whenever a new plugin version is released.
	 *
	 * @since 1.0.0
	 * @const string
	 */
	const version = '1.0.0';
	
	/**
	 * Instance of the controller.
	 *
	 * @since 1.0.0
	 * @var GoogleAPI_*_Controller
	 */
	public static $controller;
	
	/**
	 * Instance of the Google API.
	 *
	 * @since 1.0.0
	 */
	public static $client;
	
	/**
	 * Instance of the SimpleCache.
	 *
	 * @since 1.0.0
	 */
	public static $cache;
	
	/**
	 * Instance of the Options Model.
	 *
	 * @since 1.3.0
	 * @var GoogleAPI_Options_Model
	 */
	// public static $model_options;
	
	/**
	 * GoogleAPI version.
	 *
	 * @since 1.0.0
	 * @const string
	 */
	const optionNames = array (
		'code' => 'branzel_googleapi_code',
		'access_token' => 'branzel_googleapi_access_token',
		'token_expires' => 'branzel_googleapi_token_expires',
		'doReset' => 'branzel_googleapi_reset',
	);
	
	/**
	 * Instance of the APIBridge.
	 *
	 * @since 1.0.0
	 * @var GoogleAPI_APIBridge
	 */
	// public static $api_bridge;
	
	/**
	 * Start-up GoogleAPI (run on WordPress "init") and load the controller for the current state.
	 *
	 * @since 1.0.0
	 */
	public static function run() {
		/* TEST */
		
		self::load_file("autoload.php", 'includes/libs/Google/vendor');
		// self::load_file("PhotosLibrary.php", 'includes/libs/Google/src/Google/Service');
		self::$client = new Google_Client();
		
		
		if ( get_option( self::optionNames['access_token'] ) != '' ) {
			Branzel_GoogleAPI::$client->setClientId('95707748700-cs9p8b8706c4kcmlknv2nfgb400ltjj7.apps.googleusercontent.com');
			Branzel_GoogleAPI::$client->setClientSecret('MlmFOkNfxnD4laMDrBPigJ3o');
			Branzel_GoogleAPI::$client->setAccessToken( get_option( self::optionNames['access_token'] ) );
		}
		
		self::$cache = self::load_class('Gilbitron\Util\SimpleCache', 'SimpleCache.php', 'includes');
		self::$cache->cache_path = BRANZEL_GOOGLEAPI_ABSPATH . 'cache/';
		self::$cache->cache_time = 3600;
		
		
		// self::initGoogleClient();
		
		// Load modals options, to be accessible from everywhere via `GoogleAPI::$model_options`.
		// self::$model_options = self::load_model( 'options' );
		
		if ( is_admin() ) {
			$controller = 'admin';
			self::$controller = self::load_controller( $controller );
		} else {
			$controller = 'frontend';
			self::$controller = self::load_class( "Branzel_GoogleAPI_Controller", "class-controller.php", 'classes' );
		}
		
		// Register main Block Script
		wp_register_script('branzel-googleapi-block-script', plugins_url('dist/blocks.build.js', BRANZEL_GOOGLEAPI__FILE__), array( 'wp-blocks', 'wp-i18n', 'wp-element' ), Branzel_GoogleAPI::version,	true);
		
		// Register Frontend Style
		wp_register_style('branzel-googleapi-frontend-style', plugins_url('dist/blocks.style.build.css', BRANZEL_GOOGLEAPI__FILE__), array( 'wp-blocks' ), Branzel_GoogleAPI::version);

		// Register Editor Style
		wp_register_style('branzel-googleapi-editor-style',	plugins_url('dist/blocks.editor.build.css', BRANZEL_GOOGLEAPI__FILE__), array( 'wp-edit-blocks' ), Branzel_GoogleAPI::version);
		
		// Register the block
		register_block_type( 'branzel/block-google-albums', array(
			'editor_script' => 'branzel-googleapi-block-script',
			'editor_style' => 'branzel-googleapi-editor-style',
			'style' => 'branzel-googleapi-frontend-style',
			'render_callback' => array( self::$controller, 'renderGooglePhotosBlock'),
		) );
		// self::$api_bridge = self::load_class( "GoogleAPI_APIBridge", "class-apibridge.php", 'classes' );
	}
	
	/**
	 * Get the Access Token stored in db.
	 *
	 * @since    1.0.0
	 */    
    public static function getAccessToken() {
        $token = get_option( self::optionNames['access_token'] );
		
        return $token;
    }
	
	public static function isAuthenticated() {
        // get options from db
        $code = get_option( self::optionNames['code'] );
        $token = self::getAccessToken();
		
		if ( WP_DEBUG ) {
			// echo "Access Token:";
			// print_r($token);
		}
        
        if ( isset( $token['access_token'] ) ) {
			if (self::$client->isAccessTokenExpired()) {
				$newToken = self::$client->fetchAccessTokenWithRefreshToken();
				self::$client->setAccessToken($newToken);
				
				update_option(self::optionNames['access_token'], $newToken);
			}
			
			return true;
        } else {
            // get oauth2 code
            //$this->getOAuthToken();
        }

        return false;
    }
	
	/**
	 * Load a file with require_once(), after running it through a filter.
	 *
	 * @since 1.0.0
	 *
	 * @param string $file   Name of the PHP file with the class.
	 * @param string $folder Name of the folder with $class's $file.
	 */
	public static function load_file( $file, $folder ) {
		$full_path = BRANZEL_GOOGLEAPI_ABSPATH . $folder . '/' . $file;
		/**
		 * Filter the full path of a file that shall be loaded.
		 *
		 * @since 1.0.0
		 *
		 * @param string $full_path Full path of the file that shall be loaded.
		 * @param string $file      File name of the file that shall be loaded.
		 * @param string $folder    Folder name of the file that shall be loaded.
		 */
		$full_path = apply_filters( 'googleapi_load_file_full_path', $full_path, $file, $folder );
		if ( $full_path ) {
			require_once $full_path;
		}
	}

	/**
	 * Create a new instance of the $class, which is stored in $file in the $folder subfolder
	 * of the plugin's directory.
	 *
	 * @since 1.0.0
	 *
	 * @param string $class  Name of the class.
	 * @param string $file   Name of the PHP file with the class.
	 * @param string $folder Name of the folder with $class's $file.
	 * @param mixed  $params Optional. Parameters that are passed to the constructor of $class.
	 * @return object Initialized instance of the class.
	 */
	public static function load_class( $class, $file, $folder, $params = null ) {
		/**
		 * Filter name of the class that shall be loaded.
		 *
		 * @since 1.0.0
		 *
		 * @param string $class Name of the class that shall be loaded.
		 */
		$class = apply_filters( 'googleapi_load_class_name', $class );
		if ( ! class_exists( $class, false ) ) {
			self::load_file( $file, $folder );
		}
		$the_class = new $class( $params );
		return $the_class;
	}
	
	/**
	 * Create a new instance of the $view, which is stored in the "views" subfolder, and set it up with $data.
	 *
	 * @since 1.0.0
	 *
	 * @param string $view Name of the view to load.
	 * @param array  $data Optional. Parameters/PHP variables that shall be available to the view.
	 * @return object Instance of the initialized view, already set up, just needs to be rendered.
	 */
	public static function load_view( $view, array $data = array() ) {
		// View Base Class.
		self::load_file( 'class-view.php', 'classes' );
		// Make first letter uppercase for a better looking naming pattern.
		$ucview = ucfirst( $view );
		$the_view = self::load_class( "GoogleAPI_{$ucview}_View", "view-{$view}.php", 'views' );
		$the_view->setup( $view, $data );
		return $the_view;
	}
	
	/**
	 * Create a new instance of the $model, which is stored in the "models" subfolder.
	 *
	 * @since 1.0.0
	 *
	 * @param string $model Name of the model.
	 * @return object Instance of the initialized model.
	 */
	public static function load_model( $model ) {
		// Model Base Class.
		self::load_file( 'class-model.php', 'classes' );
		// Make first letter uppercase for a better looking naming pattern.
		$ucmodel = ucfirst( $model );
		$the_model = self::load_class( "GoogleAPI_{$ucmodel}_Model", "model-{$model}.php", 'models' );
		return $the_model;
	}
	
	/**
	 * Create a new instance of the $controller, which is stored in the "controllers" subfolder.
	 *
	 * @since 1.0.0
	 *
	 * @param string $controller Name of the controller.
	 * @return object Instance of the initialized controller.
	 */
	public static function load_controller( $controller ) {
		// Controller Base Class.
		self::load_file( 'class-controller.php', 'classes' );
		
		// Make first letter uppercase for a better looking naming pattern.
		$uccontroller = ucfirst( $controller );
		$the_controller = self::load_class( "Branzel_GoogleAPI_{$uccontroller}_Controller", "controller-{$controller}.php", 'controllers' );
		return $the_controller;
	}
	
	/**
	 * Create a new instance of the $cache, which is stored in the "caches" subfolder.
	 *
	 * @since 1.0.0
	 *
	 * @param string $cache Name of the cache.
	 * @return object Instance of the initialized cache.
	 */
	public static function load_cache($cache, $label = null) {
		// Controller Base Class.
		self::load_file( 'class-cache.php', 'classes' );
		
		// Make first letter uppercase for a better looking naming pattern.
		$uccache = ucfirst( $cache );
		$the_cache = self::load_class( "GoogleAPI_{$uccache}_Cache", "cache-{$cache}.php", 'caches', $label );
		return $the_cache;
	}

	/**
	 * Generate the complete nonce string, from the nonce base, the action and an item, e.g. tablepress_delete_table_3.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $action Action for which the nonce is needed.
	 * @param string|bool $item   Optional. Item for which the action will be performed, like "table".
	 * @return string The resulting nonce string.
	 */
	public static function nonce( $action, $item = false ) {
		$nonce = "googleapi_{$action}";
		if ( $item ) {
			$nonce .= "_{$item}";
		}
		return $nonce;
	}
	
	
	/**
	 * Check whether a nonce string is valid.
	 *
	 * @since 1.0.0
	 *
	 * @param string      $action    Action for which the nonce should be checked.
	 * @param string|bool $item      Optional. Item for which the action should be performed, like "table".
	 * @param string      $query_arg Optional. Name of the nonce query string argument in $_POST.
	 * @param bool $ajax Whether the nonce comes from an AJAX request.
	 */
	public static function check_nonce( $action, $item = false, $query_arg = '_wpnonce', $ajax = false ) {
		$nonce_action = self::nonce( $action, $item );
		if ( $ajax ) {
			check_ajax_referer( $nonce_action, $query_arg );
		} else {
			check_admin_referer( $nonce_action, $query_arg );
		}
	}
	
	/**
	 * Generate the action URL, to be used as a link within the plugin (e.g. in the submenu navigation or List of Tables).
	 *
	 * @since 1.0.0
	 *
	 * @param array  $params    Optional. Parameters to form the query string of the URL.
	 * @param bool   $add_nonce Optional. Whether the URL shall be nonced by WordPress.
	 * @param string $target    Optional. Target File, e.g. "admin-post.php" for POST requests.
	 * @return string The URL for the given parameters (already run through esc_url() with $add_nonce === true!).
	 */
	public static function url( array $params = array(), $add_nonce = false, $target = '' ) {

		// Default action is "list", if no action given.
		if ( ! isset( $params['action'] ) ) {
			$params['action'] = 'list';
		}
		$nonce_action = $params['action'];

		if ( $target ) {
			$params['action'] = "googleapi_{$params['action']}";
		} else {
			$params['page'] = 'googleapi';
			// Top-level parent page needs special treatment for better action strings.
			if ( self::$controller->is_top_level_page ) {
				$target = 'admin.php';
				if ( ! in_array( $params['action'], array( 'list', 'edit' ), true ) ) {
					$params['page'] = "googleapi_{$params['action']}";
				}
				if ( ! in_array( $params['action'], array( 'edit' ), true ) ) {
					$params['action'] = false;
				}
			} else {
				$target = self::$controller->parent_page;
			}
		}

		// $default_params also determines the order of the values in the query string.
		$default_params = array(
			'page'   => false,
			'action' => false,
			'item'   => false,
		);
		$params = array_merge( $default_params, $params );

		$url = add_query_arg( $params, admin_url( $target ) );
		if ( $add_nonce ) {
			$url = wp_nonce_url( $url, self::nonce( $nonce_action, $params['item'] ) ); // wp_nonce_url() does esc_html()
		}
		return $url;
	}
	
	/**
	 * Create a redirect URL from the $target_parameters and redirect the user.
	 *
	 * @since 1.0.0
	 *
	 * @param array $params    Optional. Parameters from which the target URL is constructed.
	 * @param bool  $add_nonce Optional. Whether the URL shall be nonced by WordPress.
	 */
	public static function redirect( array $params = array(), $add_nonce = false ) {
		$redirect = self::url( $params );
		if ( $add_nonce ) {
			if ( ! isset( $params['item'] ) ) {
				$params['item'] = false;
			}
			// Don't use wp_nonce_url(), as that uses esc_html().
			$redirect = add_query_arg( '_wpnonce', wp_create_nonce( self::nonce( $params['action'], $params['item'] ) ), $redirect );
		}
		wp_redirect( $redirect );
		exit;
	}
	
	/**
     * Get current url.
     *
     * @since    1.0.0
     */
    public static function getUrl() {
        return home_url( $wp->request );
    }
	
	/**
	 * Google client class Instantiate
	 * @since      3.0.10
	 * @return object
	 */
	private static function initGoogleClient() {
		if ( !self::$client ) {
			
			$plugin_path = BRANZEL_GOOGLEAPI_ABSPATH . 'includes/api-libs';
			set_include_path( $plugin_path . PATH_SEPARATOR . get_include_path());
		
			require_once BRANZEL_GOOGLEAPI_ABSPATH . 'includes/api-libs/Google/Client.php';
			self::$client = new Google_Client();
		}
	}
	
	public static function getFormedURL ( $arg) {
		$url = get_permalink() . "?" . http_build_query($arg);
		
		return $url;
	}
	
	/*
     *
     * Pagination Helper
     *
     * $num_pages, int
     * $current_page, int
     * $album_id
     *
     * return string
     */     
    public static function get_pagination($total_num_albums, $num_image_results, $page) {
		// Is pagination necessary?
		if ( $num_image_results < 1 ) {
			return;
		}
		
		$num_pages = ceil( $total_num_albums / $num_image_results );

		// If ony need one page then do not display pagination
		if ( $num_pages <= 1 ){
			return;
		}
		
		// TODO: Do we need this check?
		if( !isset( $page ) || $page < 1 ) { 
			$page = 1;
		}
		
		// Define necessary variables
		$previous = $page - 1;
        $next     = $page + 1;
		
		// Begin pagination HTML5
		$output = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center">';
		
		// First page link
		$output .= '<li class="page-item'  . ( $page <= 1 ? ' disabled' : '' ) . '"><a class="page-link" href="' . Branzel_GoogleAPI::getFormedURL( array (
			'album_page' => 1,
			'album' => ( isset ( $_GET['album'] ) ? $_GET['album'] : '' ),
			'folder' => ( isset ( $_GET['folder'] ) ? $_GET['folder'] : '' ),
			'lastfolder' => ( isset ( $_GET['lastfolder'] ) ? $_GET['lastfolder'] : '' ),
		)) . '"><i class="fa fa-step-backward" aria-hidden="true"></i></a></li>';
		
		
		$output .= '<li class="page-item' . ( $page <= 1 ? ' disabled' : '' ) . '"><a class="page-link" href="' . Branzel_GoogleAPI::getFormedURL( array (
			'album_page' => $previous,
			'album' => ( isset ( $_GET['album'] ) ? $_GET['album'] : '' ),
			'folder' => ( isset ( $_GET['folder'] ) ? $_GET['folder'] : '' ),
			'lastfolder' => ( isset ( $_GET['lastfolder'] ) ? $_GET['lastfolder'] : '' ),
		)) . '" id="prev_page"><span aria-hidden="true">&laquo;</span><span class="sr-only">Previous page</span></a></li>';
		
		$start = 1;
		if ($page > 3) {
			$start = $page - 2;
			if ( $start > $num_pages - 4 ) {
				$start = $num_pages - 4;
			}
		}
		
        for( $i=$start; $i < $start + 5; $i++ ) {
			$output .= '<li class="page-item' . ( $i == $page ? ' active' : '' ) . '">';
        
            $output .= '<a class="page-link" href="' . Branzel_GoogleAPI::getFormedURL( array (
			'album_page' => $i,
			'album' => ( isset ( $_GET['album'] ) ? $_GET['album'] : '' ),
			'folder' => ( isset ( $_GET['folder'] ) ? $_GET['folder'] : '' ),
			'lastfolder' => ( isset ( $_GET['lastfolder'] ) ? $_GET['lastfolder'] : '' ),
		)) . '" id="page_'. $i . '">' . $i . '</a>';
			
			$output .= ( $i == $page ? '<span class="sr-only">(current)</span>' : '' ) . '</li>';
        }
        
		$output .= "<li class=\"page-item" . ( $next >= $num_pages ? ' disabled' : '' ) .  "\"><a class=\"page-link\" href=\"" . Branzel_GoogleAPI::getFormedURL( array (
			'album_page' => $next,
			'album' => ( isset ( $_GET['album'] ) ? $_GET['album'] : '' ),
			'folder' => ( isset ( $_GET['folder'] ) ? $_GET['folder'] : '' ),
			'lastfolder' => ( isset ( $_GET['lastfolder'] ) ? $_GET['lastfolder'] : '' ),
		)) . "\" id='next_page'><span aria-hidden=\"true\">&raquo;</span><span class=\"sr-only\">Next page</span></a></li>";
		
		$output .= "<li class=\"page-item" . ( $page >= $num_pages ? ' disabled' : '' ) .  "\"><a class=\"page-link\" href=\"" . Branzel_GoogleAPI::getFormedURL( array (
			'album_page' => $num_pages,
			'album' => ( isset ( $_GET['album'] ) ? $_GET['album'] : '' ),
			'folder' => ( isset ( $_GET['folder'] ) ? $_GET['folder'] : '' ),
			'lastfolder' => ( isset ( $_GET['lastfolder'] ) ? $_GET['lastfolder'] : '' ),
		)) . "\" id='next_page'><span aria-hidden=\"true\"><i class=\"fa fa-step-forward\" aria-hidden=\"true\"></i></span></a></li>";
        
		$output .= "</ul></nav>\n";
        return $output;
    }
} // class GoogleAPI