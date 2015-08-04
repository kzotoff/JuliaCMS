entitiesToChars = function(str) {
	return str
		.replace('&gt;','>')
		.replace('&lt;','<')
		.replace('&quot;','"')
		.replace('&amp;','&');

}

$(function() {
	$('.buttondiv')
		.mousedown(function() { $(this).removeClass('bn').addClass('bp'); })
		.mouseup(function() { $(this).removeClass('bp').addClass('bn'); })
		.disableTextSelect()
		;

//	$('#all_images .img_link').prettyPhoto({ social_tools:'' });
	$("a[data-gallery^='prettyPhoto']").prettyPhoto({ social_tools:'', deeplinking:false, hook:'data-gallery', overlay_gallery:false });

	$('#b_add_photo').click(function() { $('#addphotoform').dialog({ modal: true, width: 500, resizable: false, title:'Загрузка файла' }); });
	$('#b_add_photo').tooltip({ bodyHandler: function() { return $('<div></div>').text('Добавить картинку') }, delay:0 });

	$('#b_add_album').click(function() { $('#addalbumform').dialog({ modal: true, width: 500, resizable: false, title:'Новый альбом' }); });
	$('#b_add_album').tooltip({ bodyHandler: function() { return $('<div></div>').text('Добавить альбом') }, delay:0 });

	$('#b_del_item').click(function() { deletePhotos(function() { location.href='main.php'; }); });
	$('#b_del_item').tooltip({ bodyHandler: function() { return $('<div></div>').text('Удалить помеченные') }, delay:0 });

	$('#b_move_item').click(function() { $('#moveform').dialog({ modal: true, width: 500, resizable: false, title:'Перемещение' }); });
	$('#b_move_item').tooltip({ bodyHandler: function() { return $('<div></div>').text('Переместить помеченные') }, delay:0 });
	
	if ($('#errorform').length>0) {
		$('#ef_ok').click(function() { $('#errorform').remove(); });
		$('#errorform').dialog({ modal: true, width: 300, resizable: false, title:'Ошибка' });
	}

	$('#movebutton').click(function() { $(this)[0].disabled = true; moveSelected(function() { location.href = './main.php'; }); });
	$('.onetimebutton').click(function() { $(this)[0].disabled = true; $(this)[0].form.submit(); });
	
	$('.album_item').each(function() {
		if ($(this).children('.caption_div').html().replace(/<\/?[^>]+>/gim, '')>'')
			$(this).tooltip({
				bodyHandler: function() {
					caption = entitiesToChars($(this).children('.caption_div').html().replace(/<\/?[^>]+>/gim, ''));
					keywords = entitiesToChars($(this).children('.keywords_div').html().replace(/<\/?[^>]+>/gim, ''));
					description = entitiesToChars($(this).children('.description_div').html().replace(/<\/?[^>]+>/gim, ''))
					return $('<div></div>').html(
						(caption>''?'<b>Название:</b> '+caption+'<br />':'')
						+(keywords>''?'<b>Ключевые слова:</b> '+keywords+'<br />':'')
						+(description>''?'<b>Описание:</b> '+description:'')
					)
				},
				delay:0
			});
	});
});

deletePhotos = function(callback) {
	var list=[];
	t=$('.cb_div .cb:checked');
	massTotal=t.length;
	if (massTotal==0) {
		callback.call();
		return;
	}

	for (i=0; i<t.length; i++) { list[i]=t[i].id.substr(3,t[i].id.length-3); }

	confirmString='Помечено файлов (альбомов): '+massTotal+'\n'+'Удалить?';
	if (!confirm(confirmString)) {
		return false;
	}
	
	addElement({ tagName:'div', id:'mass_totalback', target:document.body });
	gaugeBack = addElement({ tagName:'div', className:'gaugeback', target:document.body });
	addElement({ tagName:'div', id:'mass_gauge', target:gaugeBack });
	addElement({ tagName:'div', className:'gaugeback gaugetext', target:document.body }).innerHTML = 'deleting....';
	
	$('#mass_gauge').css('width','1px');
	$('.gaugeback').css('left',(Math.floor($(window).width()/2)-200)+'px');
	$('#mass_totalback').css({
		'width':($(window).width()+'px'),
		'height':($(window).height()+'px')
	});
	deletePhotosRecursive(list, callback);
}

deletePhotosRecursive = function(list, callback) {
	if (list.length>0) {
		$.post('manage.php', { action:'delete', id:list[list.length-1], no_redirect:1 } )
			.complete(function(event) {
				list.length--;
				$('#mass_gauge').css('width', Math.floor((400/massTotal)*(massTotal-list.length))+'px');
				if (list.length>0) {
					deletePhotosRecursive(list, callback);
				} else {
					if (typeof callback == 'function') {
						callback.call();
					}
				}
			});
	}
}

moveSelected = function(callback) {

	var target = $('#movetarget').val();
	var list=[];
	t=$('.cb_div .cb:checked');
	massTotal=t.length;
	if (massTotal==0) {
		callback.call();
		return;
	}

	for (i=0; i<t.length; i++) { list[i]=t[i].id.substr(3,t[i].id.length-3); }

	confirmString='Помечено файлов (альбомов): '+massTotal+'\n'+'Переместить?';
	if (!confirm(confirmString)) {
		return false;
	}
	
	addElement({ tagName:'div', id:'mass_totalback', target:document.body });
	gaugeBack = addElement({ tagName:'div', className:'gaugeback', target:document.body });
	addElement({ tagName:'div', id:'mass_gauge', target:gaugeBack });
	addElement({ tagName:'div', className:'gaugeback gaugetext', target:document.body }).innerHTML='moving....';
	
	$('#mass_gauge').css('width','1px');
	$('.gaugeback').css('left',(Math.floor($(window).width()/2)-200)+'px');
	$('#mass_totalback').css({
		'width':($(window).width()+'px'),
		'height':($(window).height()+'px')
	});
	
	movePhotosRecursive(list, target, callback);
}

movePhotosRecursive = function(list, target, callback) {
	if (list.length>0) {
		$.post('manage.php', { action:'move', id:list[list.length-1], no_redirect:1, target:target } )
			.complete(function(event) {
				list.length--;
				$('#mass_gauge').css('width', Math.floor((400/massTotal)*(massTotal-list.length))+'px');
				if (list.length>0) {
					movePhotosRecursive(list, target, callback);
				} else {
					if (typeof callback == 'function') {
						callback.call();
					}
				}
			});
	}
}
