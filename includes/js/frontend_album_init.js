jQuery(document).ready(function($) {
	let $document = $(this);
	
	var nextPage = jQuery('.next-page-link').attr('href');
	var totalSlides = $('#mygallery .image').length;
	var lastSlide = 0;
				
	var grid = jQuery('.grid').masonry({ 
		itemSelector: '.grid-item', 
		columnWidth: '.grid-sizer',
		percentPosition: true,
	}); 
	
	// layout Masonry after each image loads
	grid.imagesLoaded().progress( function() {
	  grid.masonry('layout');
	});
	
	// var msnry = grid.data('masonry'); 
	// var $container = jQuery('.grid').infiniteScroll({ 
		// path: function() { 
			// return nextPage; 
		// }, 
		// outlayer: msnry, 
		// append: '.grid-item', 
		// history: false, 
		// status: '.page-load-status', 
		// hideNav: '.pagination', 
	// });
	
	// $container.on( 'append.infiniteScroll', function( event, response, path, items ) {
		// console.log(items);
		// if ( $document.data('lightGallery') ) {
			// $document.data('lightGallery').destroy(true);
		// }
		// totalSlides = $('#mygallery .image').length;
		
		// jQuery("#mygallery .image").on('click',function(event) {
			// event.preventDefault();
			// var index = jQuery("#mygallery .image").index( $(this) );
			// openLightGallery($document, index);
		// });
	// });
				
	// $container.on( 'load.infiniteScroll', function( event, response, path ) { 
		// nextPage = ( jQuery(response).find('.next-page-link').length ? jQuery(response).find('.next-page-link').attr('href') : false);
	// });
	

    // $document.on('onCloseAfter.lg', function(event) {
		// $document.data('lightGallery').destroy(true);
    // });
	
	// $document.on('onBeforeSlide.lg', function(event, prevIndex, index, fromTouch, fromThumb){
		// if ( index > (totalSlides-5) ) {
			// lastSlide = index;
			// $container.infiniteScroll('loadNextPage');
		// }
	// });
	
	// jQuery("#mygallery .image").on('click',function(event) {
		// event.preventDefault();
		// var index = jQuery("#mygallery .image").index( $(this) );
		// openLightGallery($document, index);
	// });
	jQuery("#mygallery").lightGallery({
		// index: indexToOpen,
		selector: '.image',
		autoplayFirstVideo:false,
		thumbnail:false
        // dynamic: true,
        // dynamicEl: slides,
    });
});

function openLightGallery($document, indexToOpen) {
	var slides = [];
	jQuery( "#mygallery .image" ).each(function( index ) {
		slides.push({
			"src": jQuery(this).attr('href'),
			"thumb": jQuery(this).find('img').attr('src'),
			"subHtml": jQuery(this).find('img').attr('title'),
		});
	});
	
	$document.lightGallery({
		index: indexToOpen,
		selector: '.image',
		autoplayFirstVideo:false,
		thumbnail:false
        // dynamic: true,
        // dynamicEl: slides,
    });
}