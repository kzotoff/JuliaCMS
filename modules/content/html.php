<?php

// обертка для таблички админки (со списком страниц)
define('MODULE_CONTENT_HTML_ALL_PAGES_INFO_TABLE', '
<div class="admin_buttons">

	<div class="btn btn-default" id="button_add">add more</div>

</div>

<div class="admin_content">
<table class="tablesorter admin_table">
	<thead>
		<tr>
			<th>alias</th>
			<th>title</th>
			<th>filename</th>
			<th>stylesheet</th>
			<th>script</th>
			<th>generator</th>
			<th>xsl</th>
			<th>file status</th>
			<th>action</th>
		</tr>
	</thead>
	<tbody>
%s
	</tbody>
</table>
</div>
');

// html для строки про одну страничку
define('MODULE_CONTENT_HTML_ROW_SINGPLE_PAGE_INFO', '
<tr id="page_row_%1$s" class="item_info_row">
	<td class="page_alias">%2$s</td>
	<td class="page_title">%3$s</td>
	<td class="page_filename">%4$s</td>
	<td class="page_css">%5$s</td>
	<td class="page_js">%6$s</td>
	<td class="page_generator">%7$s</td>
	<td class="page_xsl">%8$s</td>
	<td><file-state /></td>
	<td class="actionboard">
		<a class="control_link" onclick="editPageInfo(%1$s);"><img src="images/module_content/pencil.gif" alt="" /></a>
		<a class="control_link" href="./%2$s?module=content&amp;edit"><img src="images/module_content/right_green.gif" alt="" /></a>
		<a class="control_link" onclick="deletePage(%1$s);"><img src="images/module_content/redcross.gif" alt="" /></a>
	</td>
</tr>
');

// табличка для редактирования свойств страницы
define('MODULE_CONTENT_SINGLE_PAGE_EDIT_INFO', '
<form action="./" method="post" class="form-horizontal admin_edit_form" role="form">
	<input type="hidden" name="id"     value="%1$s" />
	<input type="hidden" name="module" value="content" />
	<input type="hidden" name="action" value="update" />
		
	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_title">title</label>
		<div class="col-sm-8"><input class="form-control" id="edit_title" type="text" name="title"  value="%2$s"/></div>
	</div>

	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_alias">alias</label>
		<div class="col-sm-8"><input class="form-control" id="edit_alias" type="text" name="alias" value="%3$s" /></div>
	</div>

	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_filename">custom filename</label>
		<div class="col-sm-8"><input class="form-control" id="edit_filename" type="text" name="filename" value="%4$s" /></div>
	</div>

	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_meta">meta</span></label>
		<div class="col-sm-8"><textarea class="form-control" rows="3" id="edit_meta" name="meta">%9$s</textarea></div>
	</div>

	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_css">stylesheet</span></label>
		<div class="col-sm-8"><input class="form-control" id="edit_css" type="text" name="css" value="%5$s"/></div>
	</div>

	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_js">script</span></label>
		<div class="col-sm-8"><input class="form-control" id="edit_js" type="text" name="js" value="%6$s"/></div>
	</div>

	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_generator">php generator function</label>
		<div class="col-sm-8"><input class="form-control" id="edit_generator" type="text" name="generator" value="%7$s"/></div>
	</div>

	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_xsl">XSL transformer</label>
		<div class="col-sm-8"><input class="form-control" id="edit_xsl" type="text" name="xsl" value="%8$s"/></div>
	</div>
	
	<div class="form-group dialog_buttons">
		<input type="submit" value="Save" class="btn btn-primary" />
	</div>

</form>
');

// обертка для контента страницы при редактировании
define('MODULE_CONTENT_TEXTAREA_WRAPPER', '
<form action="./%1$s" method="post" id="editpagecontentform">
<input type="hidden" name="action" value="savepage" />
<input type="hidden" name="module" value="content" />
<textarea style="min-height:500px;" name="pagecontent" id="editor" class="apply_tinymce">%2$s</textarea>
</form>
');

// обертка для php-кода
define('MODULE_CONTENT_TEXTAREA_WRAPPER_PHP', '
<form action="./%1$s" method="post" id="editpagecontentform">
<input type="hidden" name="action" value="savepage" />
<input type="hidden" name="module" value="content" />
<h4 class="php_code_informer">This page is script-generated, edit on your own risk</h4>
<textarea style="min-height:500px; width: 100%%" name="pagecontent" id="editor">%2$s</textarea>
</form>
');

// заготовка новой страницы в случае создания php-генератора
define('MODULE_CONTENT_NEW_PHP_PAGE', '<?php

// this is auto-generated function. just fill it with your code!
function %s() {
	$html = \'New page content\';
	
	return $html;
}
?>');

// html для вида "для печати"
define('MODULE_CONTENT_PRINT_FORM', '
<!doctype html>
<html>
<head>
	<meta charset="UTF-8">
	<title></title>
</head>
<body>
%content%
</body>
</html>
');


?>