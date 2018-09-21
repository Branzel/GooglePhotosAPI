<?php
/**
 * GoogleAPI Base Controller with members and methods for all controllers
 *
 * @package GoogleAPI
 * @subpackage Controllers
 * @author Rico Paridaens
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Base Controller class
 * @package GoogleAPI
 * @subpackage Controllers
 * @author Rico Paridaens
 * @since 1.0.0
 */
class Branzel_GoogleAPI_Controller {
	/**
	 * Instance of the Google Photo's Service API
	 *
	 * @since 1.0.0
	 */
	public static $photosLibraryClient;
	
	/**
	 * Column width on mobile devices
	 *
	 * @since 1.0.0
	 */
	private $col_xs = 12;
	
	/**
	 * Column width on laptops devices
	 *
	 * @since 1.0.0
	 */
	private $col_sm = 4;
	
	/**
	 * Initialize the frontend controller
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		self::$photosLibraryClient = new Google_Service_PhotosLibrary(Branzel_GoogleAPI::$client);
		
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}
	
	/**
	 * Register the stylesheets && scripts for the public-facing side of the site.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		// wp_enqueue_script('infinite-scroll-script', plugins_url('includes/js/infinite-scroll.pkgd.min.js', BRANZEL_GOOGLEAPI__FILE__), array( 'jquery' ), Branzel_GoogleAPI::version, false );
		wp_enqueue_script('lightgallery-script', plugins_url('includes/js/lightgallery.min.js', BRANZEL_GOOGLEAPI__FILE__), array( 'jquery' ), Branzel_GoogleAPI::version, false );
		wp_enqueue_script('lightgallery-all-script', plugins_url('includes/js/lightgallery-all.min.js', BRANZEL_GOOGLEAPI__FILE__), array( 'jquery', 'lightgallery-script' ), Branzel_GoogleAPI::version, false );
		wp_enqueue_script('mousewheel-script', 'https://cdnjs.cloudflare.com/ajax/libs/jquery-mousewheel/3.1.13/jquery.mousewheel.min.js', array( 'jquery' ), '3.1.13', false );
		
		wp_enqueue_script('masonry-script', plugins_url('includes/js/masonry.pkgd.min.js', BRANZEL_GOOGLEAPI__FILE__), array( 'jquery' ), Branzel_GoogleAPI::version, false );
		wp_enqueue_script('imagesloaded-script', plugins_url('includes/js/imagesloaded.pkgd.min.js', BRANZEL_GOOGLEAPI__FILE__), array( 'jquery' ), Branzel_GoogleAPI::version, false );
	}
	
	public static function getUrl($arg) {
		$url = get_permalink() . "?" . http_build_query($arg);
		
		return $url;
	}
	
	private function getFolderId($list, $depth, $separator) {
		$list = array_slice($list, 0, $depth);
		return implode($separator, $list);
	}
	
	public function renderAlbumImages($attributes, $albumId, $pageToken) {
		if ( ! Branzel_GoogleAPI::isAuthenticated() ) {
			return "Not authenticated.";
		}
		
		// Create return output string
		$output = "";
		
		// Fetch Image Settings
		$show_title = 1;
		if ( isset ( $attributes['showImageTitle'] ) ) {
			$show_title = $attributes['showImageTitle'];
		}
		
		$show_details = 1;
		if ( isset ( $attributes['showImageDetails'] ) ) {
			$show_details = $attributes['showImageDetails'];
		}
		
		$num_results = 25;
		if ( isset ( $attributes['numImgPage'] ) ) {
			$num_results = $attributes['numImgPage'];
		}
					
		$thumb_size = 200;
		if ( isset ( $attributes['imgThumbnailSize'] ) ) {
			$thumb_size = $attributes['imgThumbnailSize'];
		}
		
		$download_enabled = 1;
		if ( isset ( $attributes['imageDownloadEnabled'] ) ) {
			$download_enabled = $attributes['imageDownloadEnabled'];
		}
		
		$grouping_enabled = 1;
		if ( isset ( $attributes['groupingEnabled'] ) ) {
			$grouping_enabled = $attributes['groupingEnabled'];
		}
		
		if ( $grouping_enabled == 1 ) {
			$grouping_seperator = '\\';
			if ( isset ( $attributes['groupingSeperator'] ) ) {
				$grouping_seperator = $attributes['groupingSeperator'];
			}
			
			$grouping_depth = 2;
			if ( isset ( $attributes['groupingDepth'] ) ) {
				$grouping_depth = $attributes['groupingDepth'];
			}
		}
		
		// Define theme for images
		if ( ! ($show_title || $show_details) ) {
			$theme = 'masonry';
		} else {
			$theme = 'grid';
		}
			
		// Enqueue scripts
		switch($theme) {
			case 'masonry':
				wp_enqueue_script('lightgallery-init-script', plugins_url('includes/js/frontend_album_init.js', BRANZEL_GOOGLEAPI__FILE__), array( 'jquery', 'lightgallery-script', 'lightgallery-all-script', 'masonry-script', 'imagesloaded-script' ), Branzel_GoogleAPI::version, false );
				break;
			default:
				wp_enqueue_script('easyPaginate-script', plugins_url('includes/js/jquery.easyPaginate.js', BRANZEL_GOOGLEAPI__FILE__), array( 'jquery' ), Branzel_GoogleAPI::version, false );
				wp_enqueue_script('easyPaginate-init-script', plugins_url('includes/js/jquery.easyPaginate_init.js', BRANZEL_GOOGLEAPI__FILE__), array( 'jquery', 'easyPaginate-script' ), Branzel_GoogleAPI::version, false );
				wp_add_inline_script('easyPaginate-init-script', "var numAlbumResults=$num_results;", 'before');
				break;
		}
		
		// Request Album information
		// Get cache
		$albumCache = $this->getAlbum($albumId);
		
		$title = $albumCache->getTitle();
		
		// Generate Title & breadcrumbs 
		if ( $grouping_enabled ) {
			$titleExpl = explode($grouping_seperator, $title, $grouping_depth + 1);
			
			if (count($titleExpl) > 1) {
				$title = $titleExpl[count($titleExpl) - 1];
			}
			
			$breadcrumbs = '<a href="' . Branzel_GoogleAPI_Controller::getUrl(array()) . '" target="_self">';
			$breadcrumbs .= esc_attr(the_title('','',false));
			$breadcrumbs .= '</a> » ';
			$i = 0;
			foreach ($titleExpl as $subItem) {
				$breadcrumbs .= '<a href="' . Branzel_GoogleAPI_Controller::getUrl(array(
					'folder' => ( ($i != count($titleExpl)-1) ? $this->getFolderId($titleExpl, $i+1, $grouping_seperator) : ''),
					'album' => ( ($i == count($titleExpl)-1) ? $albumId : '')
				)) . '" target="_self">';
				$breadcrumbs .= esc_attr($subItem);
				$breadcrumbs .= '</a>';
				
				if ($i != count($titleExpl)-1) {
					$breadcrumbs .= " » ";
				}
				
				$i++;
			}
		}
		
		// Render Title & breadcrumbs
		$output .= '<header class="entry-header"><h1>';
		$output .= esc_attr($title);
		$output .= '</h1></header>';
		$output .= $breadcrumbs;
		
		$mediaItemsCache = $albumCache->getMediaItems();
		
		try {
			// Render album images
			switch($theme) {
				case 'masonry':
					$output .= '<div id="mygallery" class="row grid"><div class=" col-xs-' . $this->col_xs . ' col-sm-' . $this->col_sm . ' grid-sizer"></div>';
					break;
				default:
					$output .= '<div id="mygallery" class="container-fluid fotoalbum"><div id="fotoboekPagination" class="fotoboekPagination row">';
					break;
			}
			
			if ( isset($_GET['debug']) && $_GET['debug'] ) {
				echo "Ammount of mediaItems:" . count($mediaItemsCache);
			}
			
			foreach ($mediaItemsCache as $mediaItem) {
				$output .= $this->getMediaItemHTML($mediaItem, $theme, $show_title, $show_details, $download_enabled, $thumb_size);
			}
			
			switch($theme) {
				case 'masonry':
					$output .= '</div>';
					// $output .= '<div class="page-load-status"><div class="page-load-status-message infinite-scroll-request"><i class="fas fa-spinner fa-pulse fa-2x"></i></div><p class="page-load-status-message infinite-scroll-last">End of content</p><p class="page-load-status-message infinite-scroll-error">No more images to load</p></div>';
					break;
				default:
					$output .= '</div></div>';
					break;
			}
		} catch (\Google\ApiCore\ApiException $e) {
			print_r ( $e);
		}
			
		return $output;
	}
	
	public function renderAlbumList($attributes, $folderId, $pageToken) {
		// Create return output string
		$output = "";
		
		// Fetch Album Settings
		$grouping_enabled = 1;
		if ( isset ( $attributes['groupingEnabled'] ) ) {
			$grouping_enabled = $attributes['groupingEnabled'];
		}
		
		if ( $grouping_enabled == 1 ) {
			$grouping_seperator = '\\';
			if ( isset ( $attributes['groupingSeperator'] ) ) {
				$grouping_seperator = $attributes['groupingSeperator'];
			}
			
			$grouping_depth = 2;
			if ( isset ( $attributes['groupingDepth'] ) ) {
				$grouping_depth = $attributes['groupingDepth'];
			}
		}
		
		$show_title = 1;
		if ( isset ( $attributes['showAlbumTitle'] ) ) {
			$show_title = $attributes['showAlbumTitle'];
		}
		
		$show_details = 1;
		if ( isset ( $attributes['showAlbumDetails'] ) ) {
			$show_details = $attributes['showAlbumDetails'];
		}
		
		$num_results = 9;
		if ( isset ( $attributes['numAlbumsPage'] ) ) {
			$num_results = $attributes['numAlbumsPage'];
		}
		
		$thumb_size = 200;
		if ( isset ( $attributes['albumThumbnailSize'] ) ) {
			$thumb_size = $attributes['albumThumbnailSize'];
		}
		
		// Map albums names to hide to array and trim white space
		$hide_albums = "Auto Backup,Profile Photos";
		if ( isset ( $attributes['hiddenAlbums'] ) ) {
			$hide_albums = $attributes['hiddenAlbums'];
		}
		
		if( $hide_albums !== NULL ) {
			$hide_albums = array_map( 'trim', explode( ',', $hide_albums ) );
		}
		
		// Enqueue scripts
		wp_enqueue_script('easyPaginate-script', plugins_url('includes/js/jquery.easyPaginate.js', BRANZEL_GOOGLEAPI__FILE__), array( 'jquery' ), Branzel_GoogleAPI::version, false );
		wp_enqueue_script('easyPaginate-init-script', plugins_url('includes/js/jquery.easyPaginate_init.js', BRANZEL_GOOGLEAPI__FILE__), array( 'jquery', 'easyPaginate-script' ), Branzel_GoogleAPI::version, false );
		wp_add_inline_script('easyPaginate-init-script', "var numAlbumResults=$num_results;", 'before');
		
		if ( $grouping_enabled == 1 ) {
			// FIX DOUBLE \\ BUG
			$folderId = str_replace("\\\\", "\\", $folderId);
			
			// Generate title and breadcrumbs
			$title = $folderId;
			$titleExpl = explode($grouping_seperator, $title, $grouping_depth + 1);
			
			if (count($titleExpl) > 1) {
				$title = $titleExpl[count($titleExpl) - 1];
			}
			
			$breadcrumbs = '<a href="' . Branzel_GoogleAPI_Controller::getUrl(array()) . '" target="_self">';
			$breadcrumbs .= esc_attr(the_title('','',false));
			$breadcrumbs .= '</a> » ';
			$i = 0;
			foreach ($titleExpl as $subItem) {
				$breadcrumbs .= '<a href="' . Branzel_GoogleAPI_Controller::getUrl(array(
					'folder' => $this->getFolderId($titleExpl, $i+1, $grouping_seperator)
				)) . '" target="_self">';
				$breadcrumbs .= esc_attr($subItem);
				$breadcrumbs .= '</a>';
				
				if ($i != count($titleExpl)-1) {
					$breadcrumbs .= " » ";
				}
				
				$i++;
			}
			
			// Render Title & breadcrumbs
			$output .= '<header class="entry-header"><h1>';
			$output .= esc_attr($title);
			$output .= '</h1></header>';
			$output .= $breadcrumbs;
		}
		
		$output .=  '<div id="fotoboekContainer" class="container-fluid fotoalbum"><div id="fotoboekPagination" class="row">';
		
		$albumListCache = Branzel_GoogleAPI::load_cache('albumList');
		
		// Pagination is handled automatically
		if ( $grouping_enabled == 1 ) {
			$groupedPictures = $albumListCache->getGroupedAlbumList($grouping_depth, $grouping_seperator);
			
			foreach($groupedPictures['subdirNames'] as $subdirName) {
				$subdir = $groupedPictures['subDirs'][$subdirName];
				
				if ( $folderId != '' && $groupedPictures['subDirs'][$folderId]['hasSubDirs'] ) {
					if ( $subdir['parent'] == $folderId ) {
						if( !in_array( $subdir['path'], $hide_albums ) ) {
							$output .= $this->getAlbumHTML( $groupedPictures['subDirs'], $subdir['path'], $show_title, $show_details, $thumb_size );
						}
					}
				} else {
					if ( $subdir['depth'] == 0 ) {
						if( !in_array( $subdir['path'], $hide_albums ) ) {
							$output .= $this->getAlbumHTML( $groupedPictures['subDirs'], $subdir['path'], $show_title, $show_details, $thumb_size );
						}
					}
				}
			}
			
		} else {
			$response = $photosLibraryClient->albums->listAlbums(array ( 'pageSize' => $num_results ));
			
			echo "To do";
		}
		
		$albumListCache->save();
		
		$output .= '</div></div>';
		
		return $output;
	}
	
	public static function renderGooglePhotosBlock( $attributes, $content ) {
		if ( is_admin() ) {
			return "isAdmin";
		}
		
		$pageToken = '';
		if ( isset( $_GET['pageToken'] ) ) {
			$pageToken = $_GET[ 'pageToken' ];   
		}
		
		$album = '';
		if ( isset( $_GET['album'] ) ) {
			$album = $_GET[ 'album' ];   
		}
		
		$folder = '';
		if ( isset( $_GET['folder'] ) ) {
			$folder = $_GET[ 'folder' ]; 
		}

		// Fetch General Settings
		$col_ammount = 3;
		if ( isset ( $attributes['numImgRow'] ) ) {
			$col_ammount = $attributes['numImgRow'];
		}
		
		$col_ammount_mobile = 2;
		if ( isset ( $attributes['numImgRowMobile'] ) ) {
			$col_ammount_mobile = $attributes['numImgRowMobile'];
		}
		
		$this->col_sm = round ( 12.0 / $col_ammount );
		$this->col_xs = round ( 12.0 / $col_ammount_mobile );
		
		// Render album or folder
		if ( $album != '') {
			return $this->renderAlbumImages($attributes, $album, $pageToken);
			
		} else {
			return $this->renderAlbumList($attributes, $folder, $pageToken);
		}
	}
	
	public function getAlbumHTML( $groupedPictures, $albumId, $show_title, $show_details = false, $thumb_size = 0 ) {
		// Start rendering
		$output = '<div class="listItem col-xs-' . $this->col_xs . ' col-sm-' . $this->col_sm . '"><div class="card box-shadow">';
		
		if( $groupedPictures[$albumId]['hasSubDirs'] ) {
			// This is a folder, do folder rendering
			
			$output .=  "<a href='" . esc_url(Branzel_GoogleAPI::getFormedURL( array ( 'folder' => $groupedPictures[$albumId]['path'] ) )) . "'>";
			
			$numAlbums = 0;
			$thumbnails = array();
			
			// Go trough the list to find coverPictures
			// Bug: doesn't work for second generation items
			foreach ( $groupedPictures as $subdir ) {
				if ( $subdir['parent'] == $albumId ) {
					if( isset($subdir['id']) ) {
						$album = $this->getAlbum($subdir['id']);
						array_push ( $thumbnails, $album->getCoverPhotoBaseUrl() );
						$numAlbums++;
					}
				}
			}
			
			$thumbnum = 0;
			$thumbcol = 6;
			
			if( count($thumbnails) >= 9) {
				$thumbcol = 4;
			}
			
			if( count($thumbnails) >= 3) {
				$output .= '<div class="row no-gutters">';
				foreach ($thumbnails as $image) {
					$output .= '<div class="col-' . $thumbcol . ' col-xs-' . $thumbcol . ' col-sm-' . $thumbcol . '"><img style="border-radius:0 !important" class="img-fluid" src="' . esc_url($image . ( $thumb_size > 0 ? "=w$thumb_size-h$thumb_size-c" : '' )) . '" /></div>';
					$thumbnum++;
					if (($thumbnum >= 9 && $thumbcol == 4) || ($thumbnum >= 4 && $thumbcol == 6) ) {
						break;
					}
				}
				$output .= '</div>';
			} else {
				$output .= '<img class="card-img-top" style="border-radius:0 !important" class="img-fluid" src="' . esc_url($thumbnails[0] . ( $thumb_size > 0 ? "=w$thumb_size-h$thumb_size-c" : '' )) . '" />';
				$thumbnum++;
			}
			
			$output .= '</a>';

			// Render title if enabled in block settings
			if( $show_title ) {
				$output .= '<div class="card-body">';
				$output .=  "<a href='" . Branzel_GoogleAPI::getFormedURL( array ( 'folder' => $groupedPictures[$albumId]['path'] ) ) . "'>" . $groupedPictures[$albumId]['name'] . "</a>";
				$output .= '</div>';
			}

			// Render details if enabled in block settings
			if( $show_details ) {
				$output .= '<div class="card-footer"><div class="float-left"><small>' . sprintf( _n( '%s album', '%s albums', $numAlbums, 'googleapi' ), $numAlbums ) . '</small></div></div>';
			}			
			
		} else {
			// This is an album, do album rendering
			
			// Get cache
			$albumCache = $this->getAlbum($groupedPictures[$albumId]['id']);
			$albumCache->refreshMediaItems();
			$albumCache->refreshPublishedDate();
			
			$albumCache->getCoverPhotoBaseUrl();
			$albumCache->getId();
			
			$title = $groupedPictures[$albumId]['name'];
	
			$output .=  "<a href='" . esc_url(Branzel_GoogleAPI::getFormedURL( array ( 'album' => $albumCache->getId() ) )) . "'>";
		
			$output .= '<img class="card-img-top" alt="' . esc_attr($title) .'" src="' . esc_url($albumCache->getCoverPhotoBaseUrl() . ( $thumb_size > 0 ? "=w$thumb_size-h$thumb_size-c" : '' ) ) . '" title="' . esc_attr($title) . '" />';
			
			$output .= '</a>';
			
			// Render title if enabled in block settings
			if( $show_title ) {
				$output .= '<div class="card-body">';
				
				$output .=  "<a href='" . esc_url(Branzel_GoogleAPI::getFormedURL( array( 'album' => $albumCache->getId() ) ) ) . "'>" . esc_html($title) . "</a>";
				
				$output .= '</div>';
			}
			
			// Render details if enabled in block settings
			if( $show_details ) {
				$num_photos = $albumCache->getTotalMediaItems(); 
				
				$wpDateFormat = get_option('date_format');
				$startDate = $albumCache->getFormattedFirstPublished($wpDateFormat);
				$endDate = $albumCache->getFormattedLastPublished($wpDateFormat);
				
				if ($startDate == $endDate) {
					$published = $startDate;
				} else {
					$published = $startDate . '-' . $endDate;
				}
				
				$output .= '<div class="card-footer"><div class="float-left"><small>' . sprintf( _n( '%s image', '%s images', $num_photos, 'googleapi' ), $num_photos ) . '</small></div><div class="float-right"><small>' . esc_html($published) . '</small></div></div>';
			}
	
			// Save the cache
			$albumCache->save();
		}
		
		$output .= "</div>";
		// Close bootstrap column 
		$output .= '</div>';
		
		return $output;
	}
	
	// Todo: handle video objects
	public function getMediaItemHTML($mediaItem, $theme, $show_title, $show_details, $download_enabled, $thumb_size) {
		$title = $mediaItem->getTitle();
		
		$desc = $title;
		if( $show_details || $show_title ) {
			$desc = '';

			if ( $show_title ) {
				$desc = $title;
			}

			if ( $show_title && $show_details ) {
				if( strlen( $mediaItem->getDescription() ) > 0 ) {
					$desc .= " - ";
				}
			}

			if ( $show_details ) {
				$desc .= $mediaItem->getDescription();
			}

		}
		
		$stylefix = "style='border-radius:calc(.25rem - 1px) calc(.25rem - 1px) 0 0 !important'";
		switch($theme) {
			case 'masonry':
				$output = '<div class="grid-item col-xs-' . $this->col_xs . ' col-sm-' . $this->col_sm . '">';
				if ( $mediaItem->isVideo() ) {
					$output .= '<a class="image" style="cursor:pointer;" title="' . $desc . '" data-sub-html="' . $title . '" data-poster="'. $mediaItem->getBaseUrl() . '" data-html="#video' . $mediaItem->getId() . '" data-download-url="' . $mediaItem->getDownloadUrl() . '">';
				} else {
					$output .= '<a class="image" href="' . $mediaItem->getDownloadUrl() . '" title="' . $desc . '" data-title="' . $title . '">';
				}
				if ( $mediaItem->getHeight() > $mediaItem->getWidth() ) {
					$output .= '<img class="img-height" src="' . $mediaItem->getBaseUrl() . ( $thumb_size > 0 ? "=w$thumb_size" : '' ) . '" alt="' . $title . '" />';
				} else {
					$output .= '<img class="img-width" src="' . $mediaItem->getBaseUrl() . ( $thumb_size > 0 ? "=h$thumb_size" : '' ) . '" alt="' . $title . '" />';
				}
				$output .= "</a>";
				
				if ( $mediaItem->isVideo() ) {
					$output .= '<div style="display:none;" id="video' . $mediaItem->getId() . '"><video class="lg-video-object lg-html5" controls preload="none"><source src="' . $mediaItem->getBaseUrl() . '=dv" type="video/mp4">Your browser does not support HTML5 video.</video></div>';
				}
				$output .= "</div>";
				break;
			default:
				$output = '<div class="listItem col-xs-' . $this->col_xs . ' col-sm-' . $this->col_sm . '"><div class="card box-shadow">';
				if ( $mediaItem->isVideo() ) {
					$output .= '<a class="image" style="cursor:pointer;" title="' . $desc . '" data-sub-html="' . $title . '" data-poster="'. $mediaItem->getBaseUrl() . '" data-html="#video' . $mediaItem->getId() . '" data-download-url="' . $mediaItem->getDownloadUrl() . '">';
				} else {
					$output .= '<a class="image" href="' . $mediaItem->getDownloadUrl() . '" title="' . $desc . '" data-title="' . $title . '">';
				}
				$output .= '<img ' . $stylefix . ' class="card-img-top" src="' . $mediaItem->getBaseUrl() . ( $thumb_size > 0 ? "=w$thumb_size-h$thumb_size-c" : '' ) . '" alt="' . $title . '" />';
				$output .= "</a>";
				
				if ( $show_title || ( $show_details && $mediaItem->getDescription() != '') ) {
					$output .= '<div class="card-body">';
				}
            
				// Get the title ready...
				if( $show_title ) {
					$output .= "<h6><span>$title</span></h6>";
				}

				// Get the details ready...
				if( $show_details && $mediaItem->getDescription() != '' ) {
					$output .= "<p>" . $mediaItem->getDescription() . "</p>";
					$output .= "<p class=\"caption\" style=\"display:none;\">$desc</p>\n";
				} else {
					$output .= "<p class=\"caption\" style=\"display:none;\">$desc</p>\n";
				}

				if ( $show_title || ( $show_details && $mediaItem->getDescription() != '')  ) {
					$output .= "</div>";
				}
            

				// Display link to original image file
				if( $download_enabled ){
					$output .= '<div class="card-footer"><div class="float-right"><a target=\"_blank\" download=\"' . $title . '" href="' . $mediaItem->getBaseUrl() . '"><i class="fa fa-download"></i></a></div></div>';
				}
				
				if ( $mediaItem->isVideo() ) {
					$output .= '<div style="display:none;" id="video' . $mediaItem->getId() . '"><video class="lg-video-object lg-html5" controls preload="none"><source src="' . $mediaItem->getDownloadUrl() . '" type="video/mp4">Your browser does not support HTML5 video.</video></div>';
				}

				$output .= "</div></div>";
				
				break;
		}
		return $output;
	}
	
	public function getAlbum($albumId) {
		$album = Branzel_GoogleAPI::load_cache('album', $albumId);
		$album->save();
		
		return $album;
	}
} // class GoogleAPI_Controller
