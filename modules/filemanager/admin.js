$(function() {
	//alert('add new');
	//$('#button_add').click(function() { editPageInfo(-1); });
});


function editThisItem(file) {
	backgroundLock();
	$.get('./?ajaxproxy=filemanager&module=filemanager&action=edit_elem&file='+file)
		.success(function(result) {
			$('<div id="edit_dialog"></div>')
				.html(result)
				.dialog({
					title: 'Edit item: '+file,
					modal: true,
					width: 'auto',
					close: function() {
						$('#edit_dialog').remove();
						backgroundRelease();						
					}
				});
		});
};

deleteThisItem = function(file, category) {
	if (confirm('really delete "'+file+'" ?')) {
		location.href = './?module=filemanager&action=delete&category='+category+'&filename='+file;
	}
}