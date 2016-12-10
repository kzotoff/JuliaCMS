$(function() {
	$('[data-module="filemanager"]').find('[data-action="filemanager-edit-item"]').on('click', function() {
		moduleFilemanager.editThisItem($(this).closest('td').attr('data-path'));
	});

	$('[data-module="filemanager"]').find('[data-action="filemanager-delete-item"]').on('click', function() {
		moduleFilemanager.deleteThisItem($(this).closest('td').attr('data-path'), $(this).closest('td').attr('data-category'));
	});
	
});

moduleFilemanager = {};

moduleFilemanager.editThisItem = function(file) {
	backgroundLock();
	$.get('./?ajaxproxy=filemanager&module=filemanager&action=edit_elem&file='+file)
		.success(function(result) {
			$('<div id="edit_dialog"></div>')
				.html(result)
				.dialog({
					title: 'Редактирование файла: '+file,
					modal: true,
					width: 'auto',
					close: function() {
						$('#edit_dialog').remove();
						backgroundRelease();						
					}
				});
			$('input[data-action="filemanager-edit-cancel"]').on('click', function() {
				$(this).closest('#edit_dialog').dialog('close');
			});
			
			// http://stackoverflow.com/questions/6140632/how-to-handle-tab-in-textarea
			$("#module_admin_edit_content").keydown(function(e) {
				if ($('.tab-behavior').find('input').attr('checked') != 'checked') {
					return;
				}

				if (e.keyCode === 9) { // tab was pressed
					// get caret position/selection
					var start = this.selectionStart;
					var end = this.selectionEnd;

					var value = $(this).val();

					// set textarea value to: text before caret + tab + text after caret
					$(this).val(
						value.substring(0, start)
						+ "\t"
						+ value.substring(end)
					);

					// put caret at right position again (add one for the tab)
					this.selectionStart = this.selectionEnd = start + 1;

					// prevent the focus lose
					e.preventDefault();
				}
			});			
		});
};


moduleFilemanager.deleteThisItem = function(file, category) {
	if (confirm('really delete "'+file+'" ?')) {
		location.href = './?module=filemanager&action=delete&category='+category+'&filename='+file;
	}
}