displayError = function(text, messageType, buttons) {
	alert(text);
}

$(function() {
	
	// global storage
	if (typeof(storage) === 'undefined') {
		storage = {};
	}

	// TAG_TODO про эту хрень тоже в мануал написать
	// global userland functions storage
	
	// declare init mouse coordinates
	mouseX = 100;
	mouseY = 100;
	
	// user interface content generator
	storage.apiProxy = '.';
	
	// catch any click coordinates
	$(document).on('mousedown', function(event) {
		mouseX = event.pageX;
		mouseY = event.pageY;
	});	

	if ($('.datablock_table_wrapper_content').length > 0) {
		// syncronous scrolling of header and content tables
		$('.datablock_table_wrapper_content').on('scroll', function() {
			$('.datablock_table_wrapper_header').scrollLeft($('.datablock_table_wrapper_content').scrollLeft());
		});
		
		var tableContentHeight = $(window).height() - $('.datablock_table_wrapper_content').offset().top - 1;
		$('.datablock_table_wrapper_content').css('height', tableContentHeight + 'px');
		
		// row events
		$('.datablock_table_content').find('td').on('click', function() {
			
			storage.contextMenuSource = this; // store click event source (API caller needs this)
			
			var URL = compileURL(
				storage.apiProxy,
				{
					ajaxproxy   : 'db',
					action      : 'contextmenu',
					report_id   : storage.tableData.reportId,
					row_id      : $(this).closest('tr').attr('data-row-id'),
					field_name  : $(this).attr('data-field-name')
				},
				{ cache_killer : true }
			);

			contextMenuShow(URL);
		});
		
		storage.tableData = JSON.parse($('.datablock_wrapper').children('.json_data').text());
	}
});


// init plugin container //////////////////////////////////////////////////////////////////////////
if (typeof(functionsDB) == 'undefined') { functionsDB = {}; }

functionsDB.submitFormToIframe = function(sourceButton, messages, callbacks) {

	// change some look
	sourceButton.attr('disabled', 'disabled');
	sourceButton.addClass('button-with-loader');

	var sourceForm = sourceButton.closest('form');

	// as we need to send files, it's unable to use form serialization, the only way is to use submitting
	// iframe will be used at target to avoid entire page reloading
	var iframeID = 'save_iframe_'+getRandomSuffix();
	
	$('<iframe id="'+iframeID+'" name="'+iframeID+'"></iframe>').css({'display' : 'none'}).appendTo($('body'));
	sourceForm.attr('target', iframeID);
	sourceForm.submit();

	// now we should wait for server response and take some actions
	var readyTicks = 0;
	var checkAnswerInterval = setInterval(function() {
	
		// if timed out, just inform user and reload the page
		if (readyTicks++ > 60) {
			clearInterval(checkAnswerInterval);
			alert(messages.timeout);
			callbacks.timeout();
			return;
		}
		
		// check for OK status
		var postResult = $('#'+iframeID).contents()[0].body.innerHTML;
		if (postResult > '') {
			clearInterval(checkAnswerInterval);
			if (postResult == 'OK') {
				callbacks.success();
				// TAG_TODO сделать перезагрузку только контейнера
			} else {
				alert(messages.error+'\r\nОтвет сервера:\r\n'+postResult);
				callbacks.error();
			}
		}
	}, 500);

}

///////////////////////////////////////////////////////////////////////////////////////////////////
// displays a context menu, AJAX-received
///////////////////////////////////////////////////////////////////////////////////////////////////
contextMenuShow = function(URL) {
	$('.context_menu').remove();
	
	var menuMargin = 3; // margin between menu and screen borders
	backgroundLock();
	$
		.get(URL)
		.done(function(result) {
			$('<div class="context_menu"></div>')
				.on('click', function() {
					contextMenuHide(true);
				})
				.appendTo($('body'))
				.html(result)
				.offset({
					top  : Math.min(mouseY, $(window).height() + $(window).scrollTop()  - menuMargin - $('.context_menu').height()),
					left : Math.min(mouseX, $(window).width()  + $(window).scrollLeft() - menuMargin - $('.context_menu').width() )
				})
			;
			backgroundRelease(false);
		})
		.fail(function() {
			displayError('error displaying menu. please reload the page');
			backgroundRelease(true);
		})
		;
}


///////////////////////////////////////////////////////////////////////////////////////////////////
// hides the menu and background if required
///////////////////////////////////////////////////////////////////////////////////////////////////
contextMenuHide = function(full) {
	$('.context_menu').remove();
	if (full === true) {
		backgroundRelease(true);
	}
}

///////////////////////////////////////////////////////////////////////////////////////////////////
// create request string from URL and parameters
///////////////////////////////////////////////////////////////////////////////////////////////////
compileURL = function(URL, parameters, options) {

	options = options || {};
	var request = '';
	
	// combine all parameters into string
	for (paramName in parameters) {
		request += (request > '' ? '&' : '?') + paramName + '=' + parameters[paramName];
	}

	// caching-prevention feature
	if (options.addCacheKiller === true) {
		request += (request > '' ? '&' : '?') + 'killcache' + String(Math.random()).substr(2);
	}

	return URL + request;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
// general API caller
// "override" may contain ID's for sutuations when that ID's cannot be determined
///////////////////////////////////////////////////////////////////////////////////////////////////
callAPI = function(methodName, override) {

	if (typeof(override) === 'undefined') {
		override = {};
	}

	var source = $(storage.contextMenuSource);
	var reportId  = typeof(override.reportId)  !== 'undefined' ? override.reportId  : source.closest('.datablock_wrapper').attr('data-report-id');
	var rowId     = typeof(override.rowId   )  !== 'undefined' ? override.rowId     : source.closest('tr').attr('data-row-id');
	var fieldName = typeof(override.fieldName) !== 'undefined' ? override.fieldName : source.attr('data-field-name');

	var URL = compileURL(
		storage.apiProxy,
		{
			ajaxproxy  : 'db',
			action     : 'call_api',
			method     : methodName,
			report_id  : reportId,
			row_id     : rowId,
			field_name : fieldName
		}
	);

	$.get(URL)
		.done(function(result, status, jqXHR) {

			var headers = {
				status  : jqXHR.getResponseHeader('X-JuliaCMS-Result-Status'),
				type    : jqXHR.getResponseHeader('X-JuliaCMS-Result-Type'),
				command : jqXHR.getResponseHeader('X-JuliaCMS-Result-Command')
			}

			if (headers.status != 'OK') {
				displayError(result); // TAG_TODO обрабатывать type (?)
				return false;
			}

			switch (headers.type) {
				
				// if HTML has arrived, show it as dialog and attach handlers
				case 'html':
					var newPopupBox = $('<div class="api-html-result"></div>');
					newPopupBox
						.html(result)
						.dialog({
							modal : true,
							close : function() { $('.api-html-result').remove(); },
							width : 'auto'
						});

					// iterate all buttons, add handlers to specially marked
					newPopupBox.find('[data-button-action]').each(function() {

						var buttonAction = $(this).attr('data-button-action');
						switch (buttonAction) {
							case 'form-cancel':
								$(this).on('click', function() {
									$(newPopupBox).dialog('close');
								});
								break;
							case 'form-submit':
								$(this).on('click', function() {
									functionsDB.submitFormToIframe(
										$(this),
										{ 
											timeout : 'Превышено время ожидания ответа сервера. Возможно, запись не была сохранена.',
											error   : 'Произошла ошибка при сохранении записи.'
										},
										{
											success : function() { location.reload(); },
											timeout : function() { location.reload(); },
											error   : function() { location.reload(); }
										}
									);
								});
								break;
						}
						
					});					
					
					// call handler attach function, if specified (must be loaded already!)
					if (newPopupBox.find('[data-attach-handlers]').length) {
						var handlerAttachFunction = newPopupBox.find('[data-attach-handlers]').attr('data-attach-handlers');
						if (handlerAttachFunction) {
							if (typeof(functionsDB[handlerAttachFunction]) == 'function') {
								functionsDB[handlerAttachFunction](newPopupBox);
							}
						}
					}

					// attach TinyMCE
					// prevent jQuery UI dialog from blocking focusin
					$(document).on('focusin', function(e) {
						if ($(e.target).closest(".mce-window, .moxman-window").length) {
							e.stopImmediatePropagation();
						}
					});
					$('.apply_tinymce').tinymce({
								plugins: [
											"advlist autolink lists link image charmap print preview anchor",
											"searchreplace visualblocks code fullscreen",
											"insertdatetime media table contextmenu paste",
											"minigallery"
										],
								toolbar: "insertfile undo redo | styleselect | bold italic | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | link image minigallery"
					});
				
						
					break;
					
				// nothing special, just for compatibility
				case 'plain':
					alert('[simple text response] ' + result);
					break;
					
				// can't apply it, but it's OK too
				case 'json':
					alert('JSON!');
					break;

				// can't apply it, but it's OK too
				case 'xml':
					alert('XML!');
					break;
					
				// that's something special for me!
				case 'command':
					switch (headers.command) {
						case 'reload':
							location.reload();
							break;
					}
					break;
			}
		})
		.fail(function(status, textStatus) {
			alert(
				status.responseText+' '+ // text part of result
				status.status+' '+       // HTTP code
				status.statusText        // canonical code text (i.e., "Not found" for 404)
			);
			backgroundRelease(true);
			return false;
		})
	;
	
	
}