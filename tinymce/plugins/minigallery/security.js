$(function() {
	$('#adduser').click(function() { addUser(); });
	$('#deluser').click(function() { delUser(); });
	$('#publicfolder').change(function() { setPublic(); });
});

showFlower = function() {
	$('body').append($('<div></div>').attr('id','wait_back'));
	$('#wait_back').css({'width':$(window).width()+'px', 'height':$(window).height()+'px', 'background':'gray', 'opacity':'0.5', 'filter':'alpha(opacity=40)' });
	$('body').append($('<div></div>').attr('id','wait_flower'));
	$('#wait_flower').css({'width':$(window).width()+'px', 'height':$(window).height()+'px' });
}

addUser = function() {
	showFlower();
	$.post('security.php', {action:'addaccess', id:$('#userlist').val() })
		.complete(function() { location.href='security.php'; });
}

delUser = function() {
	showFlower();
	$.post('security.php', {action:'cancelaccess', id:$('#accesslist').val() })
		.complete(function() { location.href='security.php'; });
}

setPublic = function() {
	showFlower();
	$.post('security.php', {action:'setpublic', value:$('#publicfolder').val() })
		.complete(function() { location.href='security.php'; });
}