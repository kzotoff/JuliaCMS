<?php
require('_init.php');

function terminate($code, $text) {
	header('HTTP/1.1 '.$code.' '.$text);
	echo '<doctype html><html><header><title>'.$code.'</title></header><body><h1>'.$code.' '.$text.'</h1></body></html>';
	exit;
}

if (($id = @$_GET['id']) == '') {
	terminate('418','I\'m a teapot');
}

if (!user_allowed_to('view item', $DB->querySingle('select `parent_id` from `'.$tab_files.'` where `id`=\''.$id.'\''))) {
	terminate('403','Forbidden');
}

$filename = $DB->querySingle('select `name` from `'.$tab_files.'` where `id`=\''.$id.'\'');
if ($filename == '') {
	terminate('404','Not found');
}

$source = (isset($_GET['preview'])?'preview':RELATIVE_DIR.'gallery');
// determine file format
$f = fopen($source.'/'.$filename,'r');
$fileformat = fread($f,10);
fclose($f);

if (substr($fileformat,6,4) == 'JFIF') {
	$type='jpeg';
} elseif (substr($fileformat,0,4) == chr(0xff).chr(0xd8).chr(0xff).chr(0xe1)) {
	$type='jpeg';
} elseif (substr($fileformat,0,5) == 'GIF89') {
	$type='gif';
} elseif (substr($fileformat,1,3) == 'PNG') {
	$type='png';
} else {
	terminate('503','Internal server error');
	exit;
}

header('HTTP/1.1','200 OK');
header('Content-Type: image/'.$type);
readfile($source.'/'.$filename);

?>