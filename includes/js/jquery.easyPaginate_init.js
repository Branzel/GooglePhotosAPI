jQuery( document ).ready(function() {
	jQuery('#fotoboekPagination').easyPaginate({
		paginateElement: '> div.listItem',
		elementsPerPage: numAlbumResults,
		effect: 'fade',
		firstButtonText: '<i class="fa fa-step-backward" aria-hidden="true"></i>',
		lastButtonText: '<span aria-hidden="true"><i class="fa fa-step-forward" aria-hidden="true"></i></span>',
		prevButtonText: '<span aria-hidden="true">«</span><span class="sr-only">Previous page</span>',
		nextButtonText: '<span aria-hidden="true">»</span><span class="sr-only">Next page</span>',
	});
});