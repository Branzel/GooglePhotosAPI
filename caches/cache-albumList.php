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
class GoogleAPI_AlbumList_Cache extends GoogleAPI_Cache  {
	/**
	 * Array of albums in this collection
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $albumList = array();
	
	/**
	 * Ordered list of the album photos
	 *
	 * @since 1.0.0
	 * @var int
	 */
	private $groupedAlbumList = array();
	
	/**
	 * Initialize the AlbumList Cache
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		parent::__construct("albumList");
		
		if ( isset($this->getData()['albumList'] ) ) {
			$this->albumList = $this->getData()['albumList'];
		} else {
			try {
				// Get all albums for easy pagination
				// Can this overload?? Fallback?
				$albums = array();
				$nextalbum = '';
				do {
					$response = Branzel_GoogleAPI_Controller::$photosLibraryClient->albums->listAlbums(array ( 
						'pageSize' => 50,
						'pageToken' => $nextalbum
					));
					
					$albums = array_merge($albums, $response->albums);
					$nextalbum = $response->getNextPageToken();
				} while ($nextalbum != '');
				
				foreach($albums as $album) {
					array_push($this->albumList, array(
						'id'	=> $album->getId(),
						'title'	=> $album->getTitle(),
					));
				}
			} catch (\Google\ApiCore\ApiException $e) {
				print_r ( $e);
			}
		}
		
		if ( isset($this->getData()['groupedAlbumList'] ) ) {
			$this->groupedAlbumList = $this->getData()['groupedAlbumList'];
		}
	}
	
	/**
	 * Save the cache to the local storage
	 *
	 * @since 1.0.0
	 */
	public function save() {
		$this->setData(array(
			'albumList'			=> $this->albumList,
			'groupedAlbumList'	=> $this->groupedAlbumList,
		));
		
		parent::save();
	}
	
	/**
	 * Get the albumList
	 *
	 * @since 1.0.0
	 * @return string albumList
	 */
	public function getAlbumList() {
		return $this->albumList;
	}
	
	/**
	 * Set the albumList of the cache
	 *
	 * @since 1.0.0
	 * @param string $albumList
	 */
	public function setAlbumList($albumList) {
		$this->albumList = $albumList;
	}
	
	/**
	 * Set the groupedAlbumList of the cache
	 *
	 * @since 1.0.0
	 * @param string $groupedAlbumList
	 */
	public function setGroupedAlbumList($groupedAlbumList) {
		$this->groupedAlbumList = $groupedAlbumList;
	}
	
	/**
	 * Get the groupedAlbumList
	 *
	 * @since 1.0.0
	 * @return string groupedAlbumList
	 */
	public function getGroupedAlbumList($grouping_depth, $grouping_separator) {
		foreach ($this->groupedAlbumList as $groupedAlbumList) {
			if ( $groupedAlbumList['grouping_depth'] == $grouping_depth && $groupedAlbumList['grouping_separator'] == $grouping_separator) {
				return $groupedAlbumList;
			}
		}
		
		if ( isset($_GET['debug']) && $_GET['debug'] ) {
			echo "No grouped list found, generating one";
		}
		return $this->generateGroupedAlbumList($grouping_depth, $grouping_separator);
	}
	
	private function generateGroupedAlbumList($grouping_depth, $grouping_separator) {
		if ( $grouping_depth < 1 ) {
			return $this->getAlbumList();
		}
		
		$subdirNames = array();
		$groupedPictures = array ();
		
		foreach($this->getAlbumList() as $album) {
			// get the data we need
			$title = explode($grouping_separator, $album['title'], $grouping_depth + 1);
			
			for ( $i = 0; $i < count($title) -1; $i++) {
				$path = '';
				$parent = '';
				for ( $j = 0; $j <= $i; $j++) {
					if ( $j < $i ) {
						$path .= $title[$j] . '\\';
						
						if ( $j+1 < $i ) {
							$parent .= $title[$j] . '\\';
						} else {
							$parent .= $title[$j];
						}
					} else {
						$path .= $title[$j];
					}
				}
				
				if ( !in_array( $path, $subdirNames ) ) {
					array_push( $subdirNames , $path );
					$groupedPictures[$path] = array(
						'path' 			=> $path,
						'name' 			=> $title[$i],
						'depth'			=> $i,
						'parent'		=> $parent,
						'hasSubDirs'	=> true
					);
				}
			}
			
			$path = (string)$album['title'];
			
			if ( !in_array( $path, $subdirNames ) ) {
				array_push( $subdirNames , $path );
				$groupedPictures[$path] = array(
					'id' 			=> $album['id'],
					'path' 			=> $path,
					'name' 			=> $title[count($title) - 1],
					'depth'			=> count($title) - 1,
					'parent'		=> substr ($path, 0, strlen($path) - strlen($title[count($title)-1])-1 ),
					'hasSubDirs'	=> false
				);
			}
		}
		
		sort($subdirNames);
		
		array_push($this->groupedAlbumList, array(
			'grouping_depth'		=> $grouping_depth,
			'grouping_separator'	=> $grouping_separator,
			'subdirNames' 			=> $subdirNames,
			'subDirs'				=> $groupedPictures
		));
		return array(
			'subdirNames' 	=> $subdirNames,
			'subDirs'		=> $groupedPictures);
	}
} // class GoogleAPI_Album_Cache