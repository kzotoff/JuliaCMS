$(function() {
	$('#button_add').click(function() { editPageInfo(-1); });
});

function editPageInfo(id) {
	backgroundLock();
	$.get('./?ajaxproxy=content&module=content&action=edit_elem&id='+id)
		.success(function(result) {
			$('<div id="edit_dialog"></div>')
				.html(result)
				.dialog({
					title: (id < 0 ? 'New page' : 'Page properties'),
					modal: true,
					width: '70rem',
					close: function() {
						$('#edit_dialog').remove();
						backgroundRelease(true);
					}
				});
		});
};

deletePage = function(id) {
	if (confirm('really delete?')) {
		location.href = './?module=content&action=delete&id='+id;
	}
}