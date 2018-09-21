<?php
/**
 * Album Cache for GoogleAPI
 *
 * @package GoogleAPI
 * @subpackage Caches
 * @author Rico Paridaens
 * @since 1.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * Album Cache class
 * @package GoogleAPI
 * @subpackage Caches
 * @author Rico Paridaens
 * @since 1.0.0
 */
class GoogleAPI_MediaItem_Cache extends GoogleAPI_Cache  {
	/**
	 * Album id
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $id = "";
	
	/**
	 * String of the date on which the mediaItem was ceated
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $creationTime = "";
	
	/**
	 * Title of the album
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $title = '';
	
	/**
	 * Description of the mediaItem
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $description = '';
	
	/**
	 * Height of the mediaItem
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $height = 0;
	
	/**
	 * Width of the mediaItem
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $width = 0;
	
	/**
	 * Base Url of the mediaItem
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $baseUrl = '';
	
	/**
	 * MimeType of the mediaItem
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $mimeType = '';
	
	/**
	 * Initialize the Album Cache
	 *
	 * @since 1.0.0
	 */
	public function __construct($mediaItem) {
		if( $mediaItem instanceof Google_Service_PhotosLibrary_MediaItem ) {
			$this->id = $mediaItem->getId();
			$this->setBaseUrl( $mediaItem->getBaseUrl() );
			$this->setTitle( $mediaItem['modelData']['filename'] );
			$this->setDescription( $mediaItem->getDescription() );
			$this->setWidth( $mediaItem->getMediaMetadata()->getWidth() );
			$this->setHeight( $mediaItem->getMediaMetadata()->getHeight() );
			$this->setCreationTime( $mediaItem->getMediaMetadata()->getCreationTime() );
			$this->mimeType = $mediaItem->getMimeType();
		} elseif ( is_array($mediaItem) ) {
			$this->id = $mediaItem['id'];
			$this->setData($mediaItem);
			
			if ( ! ( isset($this->getData()['title']) && isset($this->getData()['baseUrl']) && isset($this->getData()['mimeType']) && isset($this->getData()['metaData']['creationTime']) && isset($this->getData()['metaData']['height']) && isset($this->getData()['metaData']['width']) ) ) {
				echo "Requesting new data";
				
				$this->refreshData();
			} else {
				if ( isset($this->getData()['title']) ) {
					$this->title = $this->getData()['title'];
				}
				if ( isset($this->getData()['description']) ) {
					$this->description = $this->getData()['description'];
				}
				if ( isset($this->getData()['baseUrl']) ) {
					$this->baseUrl = $this->getData()['baseUrl'];
				}
				if ( isset($this->getData()['mimeType']) ) {
					$this->mimeType = $this->getData()['mimeType'];
				}
				if ( isset($this->getData()['metaData']['creationTime']) ) {
					$this->creationTime = $this->getData()['metaData']['creationTime'];
				}
				if ( isset($this->getData()['metaData']['width']) ) {
					$this->width = $this->getData()['metaData']['width'];
				}
				if ( isset($this->getData()['metaData']['height']) ) {
					$this->height = $this->getData()['metaData']['height'];
				}
			}
		}
		
		$this->label = "mediaItem-" . $this->getId();
	}
	
	public function refreshData() {
		try {
			$response = Branzel_GoogleAPI_Controller::$photosLibraryClient->mediaItems->get($this->getId());
			print_r($response);
			$this->id = $response->getId();
			$this->setBaseUrl( $response->getBaseUrl() );
			$this->setTitle( $response['modelData']['filename'] );
			$this->setDescription( $response->getDescription() );
			$this->setWidth( $response->getMediaMetadata()->getWidth() );
			$this->setHeight( $response->getMediaMetadata()->getHeight() );
			$this->setCreationTime( $response->getMediaMetadata()->getCreationTime() );
			$this->mimeType = $response->getMimeType();
		} catch (\Google\ApiCore\ApiException $e) {
			print_r ( $e);
		}
	}
	
	/**
	 * Save the cache to the local storage
	 *
	 * @since 1.0.0
	 */
	public function save() {
		$this->setData(array(
			'metaData'			=> array(
				'creationTime' 	=> $this->creationTime,
				'width' 		=> $this->width,
				'height' 		=> $this->height,
			),
			'title'				=> $this->title,
			'description'		=> $this->description,
			'baseUrl'			=> $this->baseUrl,
			'mimeType'			=> $this->mimeType,
			'id'				=> $this->id,
		));
		
		return $this->getData();
		// Branzel_GoogleAPI::$cache->set_cache($this->getLabel(), json_encode($this->getData()));
	}
	
	/**
	 * Get the id of the album
	 *
	 * @since 1.0.0
	 * @return string firstPublished
	 */
	public function getId() {
		return $this->id;
	}
	
	/**
	 * Get the creationTime of the mediaItem
	 *
	 * @since 1.0.0
	 * @return string creationTime
	 */
	public function getCreationTime() {
		return $this->creationTime;
	}
	
	/**
	 * Get the creationTime of the mediaItem
	 *
	 * @since 1.0.0
	 * @return string creationTime
	 */
	public function getFormattedCreationTime($format) {
		return date($format, strtotime($this->creationTime));
	}
	
	/**
	 * Get the title of the mediaItem
	 *
	 * @since 1.0.0
	 * @return string title
	 */
	public function getTitle() {
		return $this->title;
	}
	
	/**
	 * Get the description of the mediaItem
	 *
	 * @since 1.0.0
	 * @return string title
	 */
	public function getDescription() {
		return $this->description;
	}
	
	/**
	 * Get the base Url of the cover of the mediaItem
	 *
	 * @since 1.0.0
	 * @return string title
	 */
	public function getBaseUrl() {
		return $this->baseUrl;
	}
	
	/**
	 * Get the full size url of either the video or the picture
	 *
	 * @since 1.0.0
	 * @return string downloadUrl
	 */
	public function getDownloadUrl() {
		if ( $this->isVideo() ) {
			return $this->getBaseUrl() . '=dv';
		} else {
			return $this->getBaseUrl();
		}
	}
	
	/**
	 * Get the width of the mediaItem
	 *
	 * @since 1.0.0
	 * @return string width
	 */
	public function getWidth() {
		return $this->width;
	}
	
	/**
	 * Get the base height of the mediaItem
	 *
	 * @since 1.0.0
	 * @return string height
	 */
	public function getHeight() {
		return $this->height;
	}
	
	/**
	 * Is this mediaItem a video?
	 *
	 * @since 1.0.0
	 * @return boolean isVideo
	 */
	public function isVideo() {
		if ( $this->mimeType == 'video/mp4' ) {
			return true;
		} else {
			return false;
		}
	}
	
	/**
	 * Set the creationTime of the mediaItem
	 *
	 * @since 1.0.0
	 * @param string $creationTime
	 */
	public function setCreationTime($creationTime) {
		$this->creationTime = $creationTime;
	}
	
	/**
	 * Set the base Url of the mediaItem
	 *
	 * @since 1.0.0
	 * @param string $baseUrl
	 */
	public function setBaseUrl($baseUrl) {
		$this->baseUrl = $baseUrl;
	}
	
	/**
	 * Set the title of the album
	 *
	 * @since 1.0.0
	 * @param string $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}
	
	/**
	 * Set the description of the album
	 *
	 * @since 1.0.0
	 * @param string $description
	 */
	public function setDescription($description) {
		$this->description = $description;
	}
	
	/**
	 * Set the width of the album
	 *
	 * @since 1.0.0
	 * @param int $width
	 */
	public function setWidth($width) {
		$this->width = $width;
	}
	
	/**
	 * Set the height of the album
	 *
	 * @since 1.0.0
	 * @param int $height
	 */
	public function setHeight($height) {
		$this->height = $height;
	}
} // class GoogleAPI_Album_Cache