<?php //>// default news template. will be used if no TEMPLATE attribute// found in <TEMPLATE TYPE="NEWS">, so do not remove this template or specify// every time: <template type="news" stream="side" template="template_name" />define('MODULE_NEWS_TEMPLATE_DEFAULT', <<<'HTML'<div class="news_single">	<div class="news_stamp">%stamp%</div>	<div class="news_caption"><a class="news_main_link" href="%link%">%caption%</a></div>	<div class="news_text">%text%</div></div>	HTML);// ������� ��� �������� �� ����� ���������define('MODULE_NEWS_ALL_PAGES_INFO_TABLE', '<div class="admin_buttons">	<div class="btn btn-default" id="button_add">add more</div></div><div class="admin_content"><table class="tablesorter admin_table">	<thead>		<tr>			<th>stamp</th>			<th>caption</th>			<th>link</th>			<th>page</th>			<th>streams</th>			<th>action</th>		</tr>	</thead>	<tbody>%s	</tbody></table></div>');define('MODULE_NEWS_MANAGE_SINGLE_ROW', '<tr id="news_row_%1$s" class="item_info_row">	<td class="news_stamp">%7$s</td>	<td class="news_caption">%2$s</td>	<td class="news_link">%3$s</td>	<td class="news_page">%4$s</td>	<td class="news_streams">%5$s</td>	<td class="actionboard">		<a class="control_link" onclick="editNewsInfo(%1$s);"><img src="images/module_content/pencil.gif" alt="" /></a>		<a class="control_link" href="./%4$s?module=content&amp;edit"><img src="images/module_content/right_green.gif" alt="" /></a>		<a class="control_link" onclick="deleteNews(%1$s);"><img src="images/module_content/redcross.gif" alt="" /></a>	</td></tr>');define('MODULE_NEWS_MANAGE_ITEM_EDIT', '<form action="./" method="post" class="form-horizontal admin_edit_form" role="form">	<input type="hidden" name="id"     value="%1$s" />	<input type="hidden" name="module" value="news" />	<input type="hidden" name="action" value="edit_item" />		<div class="form-group">		<label class="control-label col-sm-4" for="edit_title">caption</label>		<div class="col-sm-8"><input class="form-control" id="edit_title" type="text" name="caption"  value="%2$s"/></div>	</div>	<div class="form-group">		<label class="control-label col-sm-4" for="edit_summary">summary</label>		<div class="col-sm-8"><textarea class="form-control" rows="5" id="edit_summary" type="text" name="summary">%3$s</textarea></div>	</div>	<div class="form-group">		<label class="control-label col-sm-4" for="edit_free_link">link<span class="mini">(optional)</span></label>		<div class="col-sm-8"><input class="form-control" id="edit_free_link" type="text" name="link" value="%4$s" /></div>	</div>		<div class="form-group">		<label class="control-label col-sm-4" for="edit_link">content page</label>		<div class="col-sm-8">%5$s</div>	</div>		<div class="form-group">		<label class="control-label col-sm-4" for="edit_streams">streams</label>		<div class="col-sm-8"><input class="form-control" id="edit_streams" type="text" name="streams" value="%6$s" /></div>	</div>		<div class="form-group dialog_buttons">		<input type="submit" value="Save" class="btn btn-primary" />	</div></form>');?>