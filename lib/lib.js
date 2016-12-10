$(function() {

	// install TinyMCE
	tinymce.init({
		selector : '.apply_tinymce',
		visual : true,
		menu : {
			edit   : {title: 'Edit',   items: 'undo redo | cut copy paste pastetext | selectall | code'},
			insert : {title: 'Insert', items: 'chooseimage filelink | link media'},
			view   : {title: 'View',   items: 'visualblocks'},
			format : {title: 'Format', items: 'bold italic underline strikethrough superscript subscript | removeformat'},
			table  : {title: 'Table',  items: 'inserttable tableprops deletetable | cell row column'}
		},
		plugins : [
			"advlist autolink lists link image charmap print preview anchor",
			"searchreplace visualblocks code fullscreen insertdatetime media table contextmenu paste",
			"minigallery filelink"
				],
		toolbar : "insertfile undo redo | fontselect fontsizeselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image | minigallery filelink",
		extended_valid_elements: "template[*] input[type=\"button\"]"
	});

	// display popups
	$('<div class="popup-container"></div>').appendTo($('body'));
	$('.popup-message').each(function() {
		popupMessageShow($(this));
	});
	
});

///////////////////////////////////////////////////////////////////////////////////////////////////
// displays hidden popup message //////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
popupMessageShow = function(container) {
	if (container.jquery) {	
		$(container).css('display', 'block');
		$(container).detach().appendTo('.popup-container');
		$(container).on('click', function() { popupMessageRemove(container); });
		setTimeout(function() { popupMessageRemove(container); }, 7000);	
	}
	if (container.popupClass) {
		var morePopup = $('<div class="popup-message '+container.popupClass+'"></div>').html(container.message).appendTo('.popup-container');
		morePopup.on('click', function() { popupMessageRemove(morePopup); });
		setTimeout(function() { popupMessageRemove(morePopup); }, 7000);	
	}
}

///////////////////////////////////////////////////////////////////////////////////////////////////
// hides and removes visible container ////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
popupMessageRemove = function(container) {
	$(container)
		.animate({'opacity' : '0'}, 'slow')
		.animate({'height' : '0px'}, 'slow', function() { $(container).remove(); })
}

///////////////////////////////////////////////////////////////////////////////////////////////////
// locks background and displays loading indicator
///////////////////////////////////////////////////////////////////////////////////////////////////
backgroundLock = function() {

	var screenHeight = $(window).height();
	var screenWidth = $(window).width();

	backgroundRelease();

	$('<div id="loading_back"></div>')
		.css({
			'width'      : screenWidth+'px',
			'height'     : screenHeight+'px',
			'top'        : '0px'
		})
		.on('click', function() {

		})
		.appendTo($('body'))
		;
	$('<div id="loading_bar"></div>')
		.css({
			'width'      : screenWidth+'px',
			'height'     : screenHeight+'px',
			'top'        : '0px'
		})
		.appendTo($('body'))
		;
}


///////////////////////////////////////////////////////////////////////////////////////////////////
// releases locked background. if "full" argument is not true, just removes loading indicator,
///////////////////////////////////////////////////////////////////////////////////////////////////
backgroundRelease = function(full) {
	if (full !== false) {
		$('#loading_back').remove();
	}
	$('#loading_bar').remove();
}


///////////////////////////////////////////////////////////////////////////////////////////////////
// debug tool /////////////////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
viewObjectContents = function(someObject) {
	var result = '';
	for (prop in someObject) {
		result += prop + ' = ' + someObject[prop] + '\r\n';
	}
	alert(result);
}


///////////////////////////////////////////////////////////////////////////////////////////////////
// creates uniquie random suffix //////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
getRandomSuffix = function() {
	return Math.random().toString().replace('.', '');
}