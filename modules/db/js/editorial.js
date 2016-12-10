//
// editor box functions
//

///////////////////////////////////////////////////////////////////////////////////////////////////
// typical handler attach function ////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
functionsDB.editorialFormHandlers = function(popupBox) {
	var editorial = popupBox.children('.edit-dialog-wrapper');
	$(editorial).find('.edit-dialog-categories').find('input[data-action="show-category"]').click(function() {
		if ($(this).attr('data-show-all') == 'yes') { // TAG_TODO записать в мануал про назначение этого атрибута
			functionsDB.editorialFilterCategories(editorial, '*');
		} else {
			functionsDB.editorialFilterCategories(editorial, $(this).attr('data-category'));
		}
	});
}

///////////////////////////////////////////////////////////////////////////////////////////////////
// shows-hides edit boxes by category. "*" means "all" ////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
functionsDB.editorialFilterCategories = function(editorial, category) {

	// iterate all edit controls // TAG_TODO в мануале написать про необходимость класса form-group
	$(editorial).find('.form-group').each(function() {
		// show item if category found or displaying all
		if ((category == '*') || ($(this).attr('data-categories').indexOf('/'+category+'/') != -1)) {
			if ($(this).css('height') == '0px') {
				$(this).animate({
					'height'        : '30px',
					'margin-bottom' : '15px',
				}, 'fast', function() { $(this).css('overflow', 'visible') });
			}
		} else {
			if ($(this).css('height') != '0px') {
				$(this).css('overflow', 'hidden');
				$(this).animate({
					'height'        : '0px',
					'margin-bottom' : '0px'
				}, 'fast');
			}
		}
	});

}