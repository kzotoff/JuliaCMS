<?php// общая обертка всей админкиdefine('MODULE_FILEMANAGER_HTML_ENTIRE_WRAPPER', '<div class="admin_buttons">	<div class="admin_make_inline">		<form action="." method="get" class="form-inline" role="form">		<label for="category_selector">category:</label>		<input type="hidden" name="module" value="filemanager" />		<input type="hidden" name="action" value="manage" />		<select class="form-control" id="category_selector" name="category">			<option value="">select...</option>			<template type="filemanager_dir_list" />		</select>		<input type="submit" class="btn btn-default" value="manage" />		</form>	</div>	<div class="admin_make_inline">		<form action="." method="post" enctype="multipart/form-data" class="form-inline" role="form">		<label for="filelist">upload more:</label>		<input type="hidden" name="module" value="filemanager" />		<input type="hidden" name="action" value="upload" />		<input type="hidden" name="category" value="%category%" />		<input type="file" class="form-control" id="filelist" name="files[]" multiple="multiple" />		<input type="submit" class="btn btn-default" value="upload" />		</form>	</div></div><div class="admin_content"><table class="tablesorter admin_table"><thead>	<tr>		<th>filename</th>		<th>action</th>	</tr></thead><tbody>	<template type="filemanager_table_rows" /></tbody></table></div>');// одна строка таблицыdefine('MODULE_FILEMANAGER_HTML_SINGLE_FILE_ROW', <<<'HTML'<tr class="item_info_row"><td>%filename%</td><td class="actionboard">	<a class="control_link" onclick="editThisItem('%path%');"><img src="images/module_content/pencil.gif" alt="" /></a>	<a class="control_link" onclick="deleteThisItem('%path%', '%category%');"><img src="images/module_content/redcross.gif" alt="" /></a></td></tr>HTML);// для формы редактирования содержимого файлаdefine('MODULE_FILEMANAGER_HTML_EDIT_CONTENT_FORM', <<<'HTML'<form action="." method="post"><input type="hidden" name="module" value="filemanager" /><input type="hidden" name="action" value="update_file" /><input type="hidden" name="category" value="%category%" /><input type="hidden" name="filename" value="%filename%" /><table class="item_edit_table"><tr>	<td>filename</td>	<td><input type="text" name="new_filename" value="%filename%" /></td></tr><tr>	<td colspan="2">		<textarea id="filecontent" name="filecontent" style="height: 12em; font-family: courier;">%content%</textarea>	</td></tr><tr>	<td colspan="2">		<input type="submit" value="save" class="button" />	</td></tr></table></form>HTML);?>