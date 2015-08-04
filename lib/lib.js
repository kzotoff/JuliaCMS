$(function() {

	tinymce.init({
		selector:'.apply_tinymce',
		plugins: [
			"advlist autolink lists link image charmap print preview anchor",
			"searchreplace visualblocks code fullscreen insertdatetime media table contextmenu paste"
				],
		toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image"
	});

});


///////////////////////////////////////////////////////////////////////////////////////////////////
// locks background and displays loading indicator
///////////////////////////////////////////////////////////////////////////////////////////////////
backgroundLock = function() {

	var screenHeight = $(window).height();
	var screenWidth = $(window).width();

	backgroundRelease(true);

	$('<div class="lock_back_back"></div>')
		.css({
			'width'      : screenWidth+'px',
			'height'     : screenHeight+'px',
			'top'        : '0px'
		})
		.on('click', function() {
			contextMenuHide(true);
		})
		.appendTo($('body'))
		;
	$('<div class="lock_back_image"></div>')
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
	if (full === true) {
		$('.lock_back_back').remove();
	}
	$('.lock_back_image').remove();
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