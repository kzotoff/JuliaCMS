if (typeof(functionsDB) == 'undefined') { functionsDB = {}; }

functionsDB.templateToMessageFormHandlers = function() {

	//$('[data-attach-handlers="templateToMessageFormHandlers"]').css('width', '80em');
	$('.recipient-list-checkboxes input').on('click', function() { 
		$('.recipient-list .datablock_table_content .target_checker').removeAttr('checked');
		$('.recipient-list-checkboxes input').each(function() {
			if ($(this).attr('checked') == 'checked') {
				var markThis = $(this).attr('data-filter');
				$('.recipient-list .datablock_table_content td[data-field-name="clients_labels"]').each(function() {
					if ($(this).html().search(markThis) != -1) {
						$(this).closest('tr').find('.target_checker').attr('checked', 'checked');
					}
				});
			}
		});
	});
	
	var rowAnimated = false;
	
	$('#recipient-search-box').on('keyup', function() { 
		var searchThis = $(this).val();
		if ($.trim(searchThis) == '') {
			return;
		}

		var found = false;
		$('.recipient-list .datablock_table_content td[data-field-name="clients_second_name"]').each(function() {
			if ($(this).html().toUpperCase().search(searchThis.toUpperCase()) != -1) {

				found = true;
				var entireRow = $(this).closest('tr');

				var scrollTo = 0;
				entireRow.prevAll().each(function() {
					scrollTo += $(this).height();
				});
				entireRow.closest('.datablock_table_wrapper_content').scrollTop(Math.max(0, scrollTo-50));

				if (!rowAnimated) {
					var currentColor = entireRow.css('background-color');
					rowAnimated = true;
					entireRow
						.animate({ 'background-color' : 'green'      }, 'fast'                                  )
						.animate({ 'background-color' : currentColor }, 'fast', function() { rowAnimated = false; });
				}
				return false;
			}
		});
		
		if (!found) {
			$('#recipient-search-box').animate({ 'background-color' : 'red' }, 'fast').animate({ 'background-color' : 'white' }, 'fast');
		}
	
	
	});
	
}