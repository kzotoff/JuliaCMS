$(function() {
	$('#button_add').click(function() { editNewsInfo(-1); });
});

function editNewsInfo(id) {
	backgroundLock();
	$.get('./?ajaxproxy=news&module=news&action=edit_elem&id='+id)
		.success(function(result) {
			$('<div id="edit_dialog"></div>')
				.html(result)
				.dialog({
					title: (id < 0 ? 'New news' : 'News properties'),
					modal: true,
					width: 'auto',
					close: function() {
						$('#edit_dialog').remove();
						backgroundRelease();
					}
				});
		});
};

deleteNews = function(id) {
	if (confirm('really delete?')) {
		location.href = './?module=news&action=delete&id='+id;
	}
}