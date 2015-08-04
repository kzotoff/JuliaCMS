displayError = function(text, messageType, buttons) {
	alert(text);
}

$(function() {
	
	// global storage
	if (typeof(storage) === 'undefined') {
		storage = {};
	}

	// TAG_TODO про эту хрень тоже в мануал написать
	
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


///////////////////////////////////////////////////////////////////////////////////////////////////
// submits a form to hidden iframe, 
//
//
//
//
//
///////////////////////////////////////////////////////////////////////////////////////////////////
functionsDB.submitFormToIframe = function(formToSubmit, sourceButton, messages, callbacks) {

	// change some look
	sourceButton.attr('disabled', 'disabled');
	sourceButton.addClass('button-with-loader');

	// wa cannot use form serialization as file sending is not serializable, the only way is to use submitting
	// hidden iframe will be used at target to ensure that response will be correctly handled
	var iframeID = 'save_iframe_'+getRandomSuffix();
	$('<iframe id="'+iframeID+'" name="'+iframeID+'"></iframe>').css({'display' : 'none'}).appendTo($('body'));
	
	// now get the form
	// formToSubmit parameter can be either string or jQuery object, check if first
	if (typeof(formToSubmit) == 'string') {
		// if string, check whether form already exists
		if ($('form[data-form-name="'+formToSubmit+'"]').length > 0) {
			formToSubmit = $('form[data-form-name="'+formToSubmit+'"]');
		} else if ($('*[data-form-container="'+formToSubmit+'"]').length > 0) {
			formContainer = $('*[data-form-container="'+formToSubmit+'"]');
			formContainer.wrap('<form action="." method="post" data-form-name="'+formToSubmit+'"></form>');
			formToSubmit = formContainer.closest('form');
		}
	} else { 
		// form can point to some object, convert it to jQuery and wrap with form if required
		var testObject = $(formToSubmit);
		if (formToSubmit.get(0).tagName == 'form') {
			formToSubmit = testObject;
		} else {
			testObject.wrap('<form method="post" action="." data-form-name="'+testObject.attr('data-form-name')+'"></form>');
			formToSubmit = testObject.closest('form');
		}
	}

	formToSubmit.attr('method', 'post');
	formToSubmit.attr('target', iframeID);
	formToSubmit.attr('enctype', 'multipart/form-data');
	formToSubmit.submit();

	// now we should wait for server response and take some actions
	var readyTicks = 0;
	checkAnswerInterval = setInterval(function() {
	
		// check for OK status
		var postResult = $('#'+iframeID).contents()[0].body.innerHTML;

		if (postResult > '') {
			clearInterval(checkAnswerInterval);
			if (postResult == 'OK') {
			
				////////////////////////////////////////////////////////////////////////////////////////////////////			
				// TAG_TODO TAG_CRAZY
				// the following is absolute shit and should be refactored ASAP ////////////////////////////////////
				////////////////////////////////////////////////////////////////////////////////////////////////////			
				if ((formToSubmit.attr('data-form-name') == 'commentbox-adder-form') && ( $('[data-form-container="edit-dialog-form"]').length || $('[data-form-name="edit-dialog-form"]').length)) {
					callAPI('comments_dialog', {
						rowId     : $('[data-form-name="edit-dialog-form"]').find('input[name="row_id"]').val(),
						container : $('div[data-meaning="comments-dynamic-box"]')
					}, {
						after: function() {
							functionsDB.modifyCommentBoxInTemplates($('div[data-meaning="comments-dynamic-box"]').find('.commentbox-outer-wrapper'));
						}
					});
				} else {	
			
				callbacks.success();
				
				}


				// TAG_TODO сделать перезагрузку только контейнера
			} else {
				alert(messages.error+'\r\nОтвет сервера:\r\n'+postResult);
				callbacks.error();
			}
		}

		// if timed out, just inform user and reload the page
		if (readyTicks++ > 60) {
			clearInterval(checkAnswerInterval);
			if (messages.timeout > '') {
				alert(messages.timeout);
			}
			callbacks.timeout();
			return;
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
//
// hides the menu and background if required
//
///////////////////////////////////////////////////////////////////////////////////////////////////
contextMenuHide = function(full) {
	$('.context_menu').remove();
	if (full === true) {
		backgroundRelease(true);
	}
}

///////////////////////////////////////////////////////////////////////////////////////////////////
//
// create request string from URL and parameters
//
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
//
// API calls "command" result handler
//
///////////////////////////////////////////////////////////////////////////////////////////////////
functionsDB.handleCommand = function(headers) {

	switch (headers.command) {
		case 'reload':
			location.reload();
			break;
	}

	return true;
}

///////////////////////////////////////////////////////////////////////////////////////////////////
//
// general API caller
//
// override.reportId  : replace click source's report ID with this value
// override.rowId     : we can also force other source row
// override.fieldName : and even field
// override.container : box to place content. Will be created automatically if not specified
//
// callbacks.before   : NOT REALIZED
// callbacks.after    : callable, will be called after API call performed and processed (dialog boxes and so on)
//
///////////////////////////////////////////////////////////////////////////////////////////////////
callAPI = function(methodName, override, callbacks) {

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
				// or insert into container specified
				case 'html':
				
					// container to auto-attach handlers to (form, div or anything else)
					var resultContainer;
					if (typeof(override.container) != 'undefined') {
					
						$(override.container).html(result)
						resultContainer = override.container;
					
					} else {
						resultContainer = $('<div class="api-html-result"></div>');
						resultContainer
							.html(result)
							.dialog({
								modal : true,
								close : function() { $('.api-html-result').remove(); },
								width : 'auto'
							});
					}
					
					// iterate all buttons in the container, add handlers to specially marked controls
					resultContainer.find('[data-button-action]').each(function() {

						var buttonAction = $(this).attr('data-button-action');
						switch (buttonAction) {
							case 'form-cancel':
								$(this).on('click', function() {
									$(resultContainer).dialog('close');
								});
								break;
							case 'form-submit':
								$(this).on('click', function() {
									functionsDB.submitFormToIframe(
										$(this).attr('data-form-submit'),
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
					if (resultContainer.find('[data-attach-handlers]').length) {
						var handlerAttachFunctionString = resultContainer.find('[data-attach-handlers]').attr('data-attach-handlers');
						if (handlerAttachFunctionString) {
							var handlerAttachFunctionArray = handlerAttachFunctionString.split(/[\s,]+/);
							for (var z = 0; z < handlerAttachFunctionArray.length; z++) {
								if (typeof(functionsDB[handlerAttachFunctionArray[z]]) == 'function') {
									functionsDB[handlerAttachFunctionArray[z]](resultContainer);
								}
							}
						}
					}
					if ((typeof(callbacks) != 'undefined') && (typeof(callbacks.after) == 'function')) {
						callbacks.after();
					}
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
					functionsDB.handleCommand(headers);
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