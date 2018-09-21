/**
 * BLOCK: google-albums
 *
 * A Google API Block to show a list of albums
 */

//  Import CSS.
import './style.scss';
import './editor.scss';

const { __ } = wp.i18n; // Import __() from wp.i18n
const { registerBlockType } = wp.blocks; // Import registerBlockType() from wp.blocks
const { Component, Fragment } = wp.element;
const { InspectorControls } = wp.editor;
const { PanelBody, TextControl, CheckboxControl, RangeControl, SelectControl } = wp.components;

registerBlockType( 'branzel/block-google-albums', {
	title: __( 'Google Photo\'s' ), // Block title.
	icon: 'googleplus',
	category: 'embed',
	keywords: [
		__( 'Google' ),
		__( 'photos' ),
	],
	supports: {
		multiple: false,
		html: false,
		customClassName: false,
		className: false,
	},
	attributes: {
		hiddenAlbums: {
			type: 'string',
			default:'Auto Backup,Profile Photos'
		},
		groupingEnabled: {
			type: 'number',
			default: 1
		},
		groupingSeperator: {
			type: 'string',
			default:'\\'
		},
		groupingDepth: {
			type: 'number',
			default: 2
		},
		numImgRow: {
			type: 'number',
			default: 3
		},
		numImgRowMobile: {
			type: 'number',
			default: 2
		},
		imgThumbnailSize: {
			type: 'number',
			default: 200
		},
		albumThumbnailSize: {
			type: 'number',
			default: 200
		},
		numImgPage: {
			type: 'number',
			default: 25
		},
		numAlbumsPage: {
			type: 'number',
			default: 9
		},
		showAlbumTitle: {
			type: 'number',
			default: 1
		},
		showImageTitle: {
			type: 'number',
			default: 1
		},
		showAlbumDetails: {
			type: 'number',
			default: 1
		},
		showImageDetails: {
			type: 'number',
			default: 1
		},
		imageDownloadEnabled: {
			type: 'number',
			default: 1
		},
	},

	edit: class extends Component {
		constructor(props) {
			super(...arguments);
			this.props = props;
		}

		render() {
			const { className, attributes: { 
				hiddenAlbums, 
				groupingEnabled, 
				groupingSeperator, 
				groupingDepth,
				numImgRow,
				numImgRowMobile,
				imgThumbnailSize,
				albumThumbnailSize,
				numImgPage,
				numAlbumsPage,
				showAlbumTitle,
				showImageTitle,
				showAlbumDetails,
				showImageDetails,
				imageDownloadEnabled
			} = {} } = this.props;

			return (
				<Fragment>
					<InspectorControls>
						<PanelBody title={ __( 'General Settings' ) } className="blocks-google-albums-settings">
							<TextControl
								label={ __( "Hidden albums" ) }
								value={hiddenAlbums}
								help={ __( "Albums to hide, separated by a comma (,)" ) }
								onChange={ (hiddenAlbums) => { this.props.setAttributes({ hiddenAlbums }); } }
							/>
							<RangeControl
								label={ __( 'Number of albums on a row' ) }
								value={ numImgRow }
								onChange={ ( numImgRow ) => { this.props.setAttributes( { numImgRow} );	} }
								min={ 1 }
								max={ 6 }
							/>
							<RangeControl
								label={ __( 'Number of albums on a row (mobile)' ) }
								value={ numImgRowMobile }
								onChange={ ( numImgRowMobile ) => { this.props.setAttributes( { numImgRowMobile} );	} }
								min={ 1 }
								max={ 6 }
							/>
						</PanelBody>
						<PanelBody title={ __( 'Album Grouping Settings' ) } initialOpen={ false }>
							<CheckboxControl
								heading="Enable Album Grouping"
								label="Enable"
								checked={ groupingEnabled }
								onChange={ ( groupingEnabled ) => { this.props.setAttributes( { groupingEnabled } ); } }
							/>
							{ groupingEnabled && <RangeControl
								label={ __( 'Grouping Depth' ) }
								value={ groupingDepth }
								onChange={ ( groupingDepth ) => { this.props.setAttributes( { groupingDepth} );	} }
								min={ 0 }
								max={ 4 }
							/> }
							{ groupingEnabled && <TextControl
								label={ __( "Grouping seperator" ) }
								value={groupingSeperator}
								help={ __( "Grouping seperator (default: \\)" ) }
								onChange={ (groupingSeperator) => { this.props.setAttributes({ groupingSeperator }); } }
							/> }
						</PanelBody>
						<PanelBody title={ __( 'Album Page Settings' ) } className="blocks-google-albums-settings" initialOpen={ false }>
							<TextControl
								label={ __( "Size of the image thumbnail (in px)" ) }
								value={albumThumbnailSize}
								help={ __( "(default: 200)" ) }
								onChange={ (albumThumbnailSize) => { this.props.setAttributes({ albumThumbnailSize }); } }
							/>
							<TextControl
								label={ __( "Number of albums on each page" ) }
								value={numAlbumsPage}
								help={ __( "(default: 9)" ) }
								onChange={ (numAlbumsPage) => { this.props.setAttributes({ numAlbumsPage }); } }
							/>
							<CheckboxControl
								heading="Show album title?"
								label="Show"
								checked={ showAlbumTitle }
								onChange={ ( showAlbumTitle ) => { this.props.setAttributes( { showAlbumTitle } ); } }
							/>
							<CheckboxControl
								heading="Show album details?"
								label="Show"
								checked={ showAlbumDetails }
								onChange={ ( showAlbumDetails ) => { this.props.setAttributes( { showAlbumDetails } ); } }
							/>
						</PanelBody>
						<PanelBody title={ __( 'Picture Page Settings' ) } className="blocks-google-albums-settings" initialOpen={ false }>
							<TextControl
								label={ __( "Size of the image thumbnail (in px)" ) }
								value={imgThumbnailSize}
								help={ __( "(default: 200)" ) }
								onChange={ (imgThumbnailSize) => { this.props.setAttributes({ imgThumbnailSize }); } }
							/>
							<TextControl
								label={ __( "Number of images on each page" ) }
								value={numImgPage}
								help={ __( "(default: 25)" ) }
								onChange={ (numImgPage) => { this.props.setAttributes({ numImgPage }); } }
							/>
							<CheckboxControl
								heading="Show picture title?"
								label="Show"
								checked={ showImageTitle }
								onChange={ ( showImageTitle ) => { this.props.setAttributes( { showImageTitle } ); } }
							/>
							<CheckboxControl
								heading="Show picture details?"
								label="Show"
								checked={ showImageDetails }
								onChange={ ( showImageDetails ) => { this.props.setAttributes( { showImageDetails } ); } }
							/>
							<CheckboxControl
								heading="Enable image download"
								label="Enable"
								checked={ imageDownloadEnabled }
								onChange={ ( imageDownloadEnabled ) => { this.props.setAttributes( { imageDownloadEnabled } ); } }
							/>
						</PanelBody>
					</InspectorControls>
					<div className={className}>
						<p>{ __( "The Google Photo's will appear here." ) }</p>
					</div>
				</Fragment>
			);
		}
	},

	save: function() {
		return null // Server side rendering
	},
} );
