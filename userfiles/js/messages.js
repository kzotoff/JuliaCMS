$(function() {
	
	$('#report_messages_delete_all').on('click', function() {
		backgroundLock();
		var URL = './?ajaxproxy=db&action=call_api&method=messages_delete_all';
		$.get(URL)
			.done(function() { alert('Сообщения удалены'); location.reload(); })
			.fail(function(status, textStatus) {
				alert(
					status.responseText+' '+ // text part of result
					status.status+' '+       // HTTP code
					status.statusText        // canonical code text (i.e., "Not found" for 404)
				);
			})
			.always(function() {
				backgroundRelease(true);
			});
	});

	$('#report_messages_send_all').on('click', function() {
		backgroundLock();
		var URL = './?module=db&action=call_api&method=send_outbox_messages';
		$.get(URL)
			.done(function(result) {
				var dialogHTML = '<div id="report_message_send_results"><div id="report_message_send_results_inner"></div><input type="button" value="Закрыть" id="report_message_send_results_close" class="btn btn-primary" /></div>';
				$(dialogHTML).appendTo($('body'));
				$('#report_message_send_results_inner').html(result);
				$('#report_message_send_results').dialog({
					title  : 'Отчет об отправке сообщений',
					width  : 'auto',
					height : 'auto',
					open   : $('#report_message_send_results_close').on('click', function() { $('#report_message_send_results').dialog('close'); }),
					close  : function() { $('#report_message_send_results').remove(); }
				});
			})
			.fail(function(status, textStatus) {
				alert(
					status.responseText+' '+ // text part of result
					status.status+' '+       // HTTP code
					status.statusText        // canonical code text (i.e., "Not found" for 404)
				);
			})
			.always(function() {
				backgroundRelease(true);
			});
		
	});
	
	
	
	
});