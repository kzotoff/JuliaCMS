<?php
require('_init.php');

///////////////////////////////////////////////////////////////////////////////////////////////////
// determines file extension //////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
function get_extension_by_content($file_content) {
	if (substr($file_content,6,4)=='JFIF') {
		$ext = 'jpg';
	} elseif (substr($file_content,0,4) == chr(0xff).chr(0xd8).chr(0xff).chr(0xe1)) {
		$ext = 'jpg';
	} elseif (substr($file_content,0,5) == 'GIF89') {
		$ext = 'gif';
	} elseif (substr($file_content,1,3) == 'PNG') {
		$ext = 'png';
	} else {
		$ext = false;
	}
	return $ext;
}
///////////////////////////////////////////////////////////////////////////////////////////////////
// saves to gallery from a temp file //////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
function save_to_gallery_from_file($filename) {
	global $DB;
	global $tab_acl, $tab_files, $tab_users;
	global $re_name;
	global $msg_err_bad_filename;
	global $msg_err_bad_file_format;
	
	// determine file format
	$f=fopen($filename,'r');
	$fileformat=fread($f,10);
	fclose($f);
	
	if (!($ext = get_extension_by_content($fileformat))) {
		$_SESSION['gallery_'.DB_PREFIX]['message'] = $msg_err_bad_file_format;
		return;
	}
	
	$newname = str_replace('-','',strtolower(create_guid()));
	// move to gallery folder
	try {
		// create thumbnail
		list($width, $height)=getimagesize($filename);
		$ratio=max($width,$height,150)/150;
		$newwidth=floor($width/$ratio);
		$newheight=floor($height/$ratio);
		switch ($ext) {
			case 'jpg':
			case 'jpeg':
				$source=imagecreatefromjpeg($filename);
				$newname .= '.jpg';
				break;
			case 'png':
				$source=imagecreatefrompng($filename);
				$newname .= '.png';
				break;
			case 'gif':
				$source=imagecreatefromgif($filename);
				$newname .= '.gif';
				break;
		}
		$thumb=imagecreatetruecolor($newwidth,$newheight);
		imagecopyresized($thumb, $source, 0, 0, 0, 0, $newwidth, $newheight, $width, $height);

		// save thumbnail and image itself
		imagejpeg($thumb,'preview/' . $newname);
		copy($filename, RELATIVE_DIR.'gallery/' . $newname);
		// erase temp file
		unlink($filename);
		// delete if something bad else add to database
		$statement = $DB->prepare(
			'insert into '.$tab_files.' (id, name, type, parent_id, caption, keywords, description, public) '
			.' values (\''.create_guid().'\', :name, 0, \''.$_SESSION['gallery_'.DB_PREFIX]['folder_id'].'\', :caption, :keywords, :description, 1)');
		$statement->bindValue(':name',        $newname                                       );
		$statement->bindValue(':keywords',    get_as_regexp($_POST['caption'], $re_name)     );
		$statement->bindValue(':keywords',    get_as_regexp($_POST['keywords'], $re_name)    );
		$statement->bindValue(':description', get_as_regexp($_POST['description'], $re_name) );
		
		$statement->execute();
	} catch (Exception $e) {
		// delete files when failed ***************************************************************
		echo 'error : '.$e->getMessage();
		unlink(RELATIVE_DIR.'gallery/'.$newname);
		unlink('preview/'.$newname);
	}
}

///////////////////////////////////////////////////////////////////////////////////////////////////
// saves to file from post data ///////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
function save_from_post($index) {
	global $re_name;
	global $msg_err_bad_filename;
	
	if (get_as_regexp($_FILES['filename']['name'][$index], $re_name) == '') {
		$_SESSION['gallery_'.DB_PREFIX]['message'] = $msg_err_bad_filename;
		return;
	}
	if ($result = move_uploaded_file($_FILES['filename']['tmp_name'][$index], 'tempfile')) {
		save_to_gallery_from_file('tempfile');
	}
}

///////////////////////////////////////////////////////////////////////////////////////////////////
// saves to file from url supplied ////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
function save_from_url($url) {
	global $re_url;
	global $msg_err_bad_filename;
	global $msg_err_bad_file_format;
	
	if ($url == '') {
		return;
	}
	$curl = curl_init($url);
	curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

	if (!($result = curl_exec($curl))) {
		echo 'curl failed'; exit;
		return;
	}

	$f = fopen('tempfile','w');
	fwrite($f, $result);
	fclose($f);

	save_to_gallery_from_file('tempfile');
}

///////////////////////////////////////////////////////////////////////////////////////////////////
// recursively deletes file/folder and all it's decendants ////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
function delete_item($id) {
	global $DB, $tab_acl, $tab_files, $tab_users;
	$query = $DB->query("select id from $tab_files where parent_id='$id'");
	while ($data = $query->fetch(PDO::FETCH_ASSOC)) {
		delete_item($data['id']);
	}
	$name = $DB->querySingle("select name from $tab_files where id='$id'");
	if (file_exists('gallery/'.$name)) {
		unlink('gallery/'.$name);
	}
	if (file_exists('preview/'.$name)) {
		unlink('preview/'.$name);
	}
	echo "delete from $tab_files where id='$id'".PHP_EOL;
	echo "delete from $tab_acl where object_id='$id'".PHP_EOL;
	
	$DB->exec("delete from $tab_files where id='$id'");
	$DB->exec("delete from $tab_acl where object_id='$id'");
}

///////////////////////////////////////////////////////////////////////////////////////////////////
// moves file/folder to another parent ////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
function move_item($id, $target_id) {
	global $DB, $tab_files;
	$DB->exec("update $tab_files set parent_id = '$target_id' where id = '$id'");
}

///////////////////////////////////////////////////////////////////////////////////////////////////
// yeah, parse POST data //////////////////////////////////////////////////////////////////////////
///////////////////////////////////////////////////////////////////////////////////////////////////
switch (@$_POST['action']) {
	case 'addfile':
		if (!user_allowed_to('add photo')) {
			break;
		}
		if ((get_as_regexp(@$_POST['url'],$re_url) == '') && (@$_POST['url'] > '')) {
			$_SESSION['gallery_'.DB_PREFIX]['message']=$msg_err_bad_url;
			break;
		}
		save_from_url($_POST['url']);

		for ($i=0; $i<count($_FILES['filename']['name']); $i++) {
//			if ((@$_FILES['filename']['size'][$i]<10)&&(@$_FILES['filename']['size'][$i] > 0)) {
			if (@$_FILES['filename']['size'][$i] > 0) {
				save_from_post($i);
			}
		}
		break;

	case 'addalbum':
		if (!user_allowed_to('add album')) {
			break;
		}
		$caption = get_as_regexp(@$_POST['albumname'], $re_name);
		if ($caption == '') {
			break;
		}
		$alias=get_as_regexp(@$_POST['albumalias'], $re_alias);
		if ($alias == '') {
			$alias = $DB->querySingle('select ifnull(max(cast(name as unsigned)), 0) + 1 from '.$tab_files.' ');
		}
		if ($DB->querySingle('select count(*) from '.$tab_files.' where name=\''.$alias.'\'') != '0') {
			$_SESSION['gallery_'.DB_PREFIX]['message'] = $msg_err_alias_exists;
			break;
		}
		
		$new_id = create_guid();
		$public = @$_POST['albumhidden'] ? 2 : 1;
		$DB->exec('insert into `'.$tab_files.'` (`id`, `name`, `type`, `parent_id`, `caption`, `keywords`, `public`) values (\''.$new_id.'\', \''.$alias.'\', 1, \''.$_SESSION['gallery_'.DB_PREFIX]['folder_id'].'\', \''.$caption.'\', \'\', ' . $public . ')');
		$DB->exec('insert into `'.$tab_acl.'` (`id`, `object_id`, `user_id`, `permission`) values (\''.create_guid().'\', \''.$new_id.'\', \''.$_SESSION['gallery_'.DB_PREFIX]['user_id'].'\', 1)');
		break;

	case 'delete':
		if (!user_allowed_to('delete item')) {
			echo '403';
			break;
		}
		$id = @$_POST['id'];
		if (get_as_regexp($id, $re_guid) == '') {
			echo '500';
			break;
		}
		delete_item($id);
		break;
	case 'move':
		if (!user_allowed_to('move item')) {
			echo '403';
			break;
		}
		$id = @$_POST['id'];
		if (get_as_regexp($id, $re_guid) == '') {
			echo '500';
			break;
		}
		$target_id = @$_POST['target'];
		if (get_as_regexp($target_id, $re_guid) == '') {
			echo '500';
			break;
		}
		move_item($id, $target_id);
		break;
	default:
		break;
}

if (!isset($_POST['no_redirect'])) {
	header('Location: main.php?id='.$_SESSION['gallery_'.DB_PREFIX]['folder_id']);
}
?>