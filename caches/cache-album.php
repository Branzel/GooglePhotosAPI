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
class GoogleAPI_Album_Cache extends GoogleAPI_Cache  {
	/**
	 * Album id
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $id = "";
	
	/**
	 * String of the ate on which the first picture was published
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $firstPublished = "";
	
	/**
	 * String of the ate on which the last picture was published
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $lastPublished = "";
	
	/**
	 * Array of mediaItems in this album
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $mediaItems;
	
	/**
	 * Number of pictures in this album
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $totalMediaItems = 0;
	
	/**
	 * Title of the album
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $title = '';
	
	/**
	 * Base Url of cover of the album
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $coverPhotoBaseUrl = '';
	
	/**
	 * Initialize the Album Cache
	 *
	 * @since 1.0.0
	 */
	public function __construct($albumId) {
		parent::__construct("album-" . $albumId);
		$this->id = $albumId;
		
		if ( isset($this->getData()['totalMediaItems']) && isset($this->getData()['title']) && isset($this->getData()['coverPhotoBaseUrl']) ) {
			$this->setTitle($this->getData()['title']);
			$this->setTotalMediaItems($this->getData()['totalMediaItems']);
			$this->setCoverPhotoBaseUrl($this->getData()['coverPhotoBaseUrl']);
		} else {
			try {
				$response = Branzel_GoogleAPI_Controller::$photosLibraryClient->albums->get($this->getId());
			} catch (\Google\ApiCore\ApiException $e) {
				print_r ( $e);
			}
			
			$this->setTitle($response->getTitle());
			$this->setTotalMediaItems($response['modelData']['mediaItemsCount']);
			$this->setCoverPhotoBaseUrl($response->getCoverPhotoBaseUrl());
		}
		
		if ( isset($this->getData()['mediaItems']) ) {
			$this->setMediaItems($this->getData()['mediaItems']);
		} elseif ( isset($this->getExpiredData()['mediaItems']) && isset($this->getExpiredData()['totalMediaItems'])  && isset($this->getExpiredData()['title']) ) {
			if ( ($this->getExpiredData()['title'] == $this->getTitle()) && ($this->getExpiredData()['totalMediaItems'] == $this->getTotalMediaItems()) ) {
				$this->setMediaItems($this->getExpiredData()['mediaItems']);				
			}
		} else {
			$this->refreshMediaItems();
		}
		
		if ( isset($this->getData()['metaData']['firstPublished']) && isset($this->getData()['metaData']['lastPublished']) ) {
			$this->firstPublished = $this->getData()['metaData']['firstPublished'];
			$this->lastPublished = $this->getData()['metaData']['lastPublished'];
		} elseif ( isset($this->getExpiredData()['metaData']['firstPublished']) && isset($this->getExpiredData()['metaData']['lastPublished']) && isset($this->getExpiredData()['totalMediaItems'])  && isset($this->getExpiredData()['title']) ) {
			if ( ($this->getExpiredData()['title'] == $this->getTitle()) && ($this->getExpiredData()['totalMediaItems'] == $this->getTotalMediaItems()) ) {
				$this->firstPublished = $this->getExpiredData()['metaData']['firstPublished'];
				$this->lastPublished = $this->getExpiredData()['metaData']['lastPublished'];				
			}
		} else {
			$this->refreshPublishedDate();
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
				'firstPublished' 	=> $this->firstPublished,
				'lastPublished' 	=> $this->lastPublished,
			),
			'totalMediaItems'	=> $this->totalMediaItems,
			'title'				=> $this->title,
			'coverPhotoBaseUrl'	=> $this->coverPhotoBaseUrl,
			'mediaItems'		=> $this->getMediaItemsArray(),
		));
		
		parent::save();
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
	 * Get the firstPublished of the cache
	 *
	 * @since 1.0.0
	 * @return string firstPublished
	 */
	public function getFirstPublished() {
		return $this->firstPublished;
	}
	
	/**
	 * Get the firstPublished of the album
	 *
	 * @since 1.0.0
	 * @return string firstPublished
	 */
	public function getFormattedFirstPublished($format) {
		return date($format, strtotime($this->firstPublished));
	}
	
	/**
	 * Get the lastPublished of the cache
	 *
	 * @since 1.0.0
	 * @return string lastPublished
	 */
	public function getLastPublished() {
		return strtotime($this->lastPublished);
	}
	
	/**
	 * Get the lastPublished of the album
	 *
	 * @since 1.0.0
	 * @return string lastPublished
	 */
	public function getFormattedLastPublished($format) {
		return date($format, strtotime($this->lastPublished));
	}
	
	/**
	 * Get the mediaItems in this album
	 *
	 * @since 1.0.0
	 * @return array mediaItems
	 */
	public function getMediaItems() {
		$this->refreshMediaItems();
		return $this->mediaItems;
	}
	
	/**
	 * Get the mediaItems in this album
	 *
	 * @since 1.0.0
	 * @return array mediaItems
	 */
	public function getMediaItemsArray() {
		if( empty($mediaItems) ) {
			return array();
		}
		
		$mediaItemsArray = array();
		foreach ( $this->mediaItems as $mediaItem) {
			array_push($mediaItemsArray, $mediaItem->save());
		}
		return $mediaItemsArray;
	}
	
	/**
	 * Get the number of pictures in this album
	 *
	 * @since 1.0.0
	 * @return int lastPublished
	 */
	public function getTotalMediaItems() {
		return $this->totalMediaItems;
	}
	
	/**
	 * Get the title of the album
	 *
	 * @since 1.0.0
	 * @return string title
	 */
	public function getTitle() {
		return $this->title;
	}
	
	/**
	 * Get the base Url of the cover of the album
	 *
	 * @since 1.0.0
	 * @return string title
	 */
	public function getCoverPhotoBaseUrl() {
		return $this->coverPhotoBaseUrl;
	}
	
	/**
	 * Set the firstPublished of the cache
	 *
	 * @since 1.0.0
	 * @param string $firstPublished
	 */
	public function setFirstPublished($firstPublished) {
		$this->firstPublished = $firstPublished;
	}
	
	/**
	 * Set the lastPublished of the cache
	 *
	 * @since 1.0.0
	 * @param string $lastPublished
	 */
	public function setLastPublished($lastPublished) {
		$this->lastPublished = $lastPublished;
	}
	
	/**
	 * Set the number of pictures in this album
	 *
	 * @since 1.0.0
	 * @param int $totalMediaItems
	 */
	public function setTotalMediaItems($totalMediaItems) {
		$this->totalMediaItems = $totalMediaItems;
	}
	
	/**
	 * Set mediaItems in this album
	 *
	 * @since 1.0.0
	 * @param int $mediaItems
	 */
	public function setMediaItems($mediaItems) {
		$mediaItemsCache = array();
		foreach ($mediaItems as $mediaItem) {
			$mediaItemCache = Branzel_GoogleAPI::load_cache('mediaItem', $mediaItem);
			array_push($mediaItemsCache, $mediaItemCache);
		}
				
		$this->mediaItems = $mediaItemsCache;
	}
	
	/**
	 * Set the base Url of the cover of the album
	 *
	 * @since 1.0.0
	 * @param int $coverPhotoBaseUrl
	 */
	public function setCoverPhotoBaseUrl($coverPhotoBaseUrl) {
		$this->coverPhotoBaseUrl = $coverPhotoBaseUrl;
	}
	
	/**
	 * Set the title of the album
	 *
	 * @since 1.0.0
	 * @param int $title
	 */
	public function setTitle($title) {
		$this->title = $title;
	}
	
	public function refreshPublishedDate($override = false) {
		if( $this->getFirstPublished() != '' && $this->getLastPublished() != '' && !$override ) {
			return true;
		}
		
		if ( ! $this->getMediaItems() ) {
			return false;
		}
		
		$dates = array();
		
		foreach($this->getMediaItems() as $mediaItem) {
			array_push($dates, $mediaItem->getCreationTime() );
		}
		
		sort($dates);
		
		$this->setFirstPublished( $dates[0] );
		$this->setLastPublished( array_pop($dates) );
		$this->save();
	}
	
	public function refreshMediaItems($override = false) {
		if( !empty($this->mediaItems) && !$override ) {
			return true;
		}
		
		$mediaItems = array();
		$pageToken = '';
		
		try {
			do {
				$searchRequest = new Google_Service_PhotosLibrary_SearchMediaItemsRequest();
				$searchRequest->setalbumId($this->getId());
				$searchRequest->setPageSize( 50 );
				$searchRequest->setPageToken( $pageToken );
					
				$response = Branzel_GoogleAPI_Controller::$photosLibraryClient->mediaItems->search( $searchRequest );
				$mediaItems = array_merge($mediaItems, $response->getMediaItems());
							
				$pageToken = $response->getNextPageToken();
			} while ($pageToken != '');
		} catch (\Google\ApiCore\ApiException $e) {
			print_r ( $e);
		}
		
		$this->setMediaItems($mediaItems);
		$this->save();
	}
} // class GoogleAPI_Album_Cache