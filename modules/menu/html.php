<?php

/*** стандартный режим ***************************************************************************/
// шаблон линка на элементе меню, в стандартном виде, на внешнюю ссылку
define('MODULE_MENU_LINK_TEMPLATE', '%s');

// шаблон линка на элементе меню, в стандартном виде, на свою страничку
define('MODULE_MENU_PAGE_TEMPLATE', './%s');

// единичный элемент навигатора для меню-списка
define('MODULE_MENU_NAVIGATOR_ITEM', '<p><a href="%1$s">%2$s</a></p>');

// элемент навигатора для меню-списка, текущий раздел
define('MODULE_MENU_NAVIGATOR_ITEM_SELECTED', '<h1>%2$s</h1>');

// обертка для всего навигатора целиком
define('MODULE_MENU_NAVIGATOR_WRAPPER', <<<'HTML'
<div class="navigator">%s</div>';
HTML
);

// обертка для вывода верхнего уровня
define('MODULE_MENU_TOP_LEVEL_WRAPPER', <<<'HTML'
<div class="catalog_top_level">
	%s
</div>
HTML
);

// обертка для остальных уровней
define('MODULE_MENU_OTHER_LEVEL_WRAPPER', <<<'HTML'
<div class="catalog_other_level">
	%s
</div>
HTML
);

define('MODULE_MENU_CATEGORY_TEXT_WRAPPER', <<<'HTML'
<div class="catalog_category_text">
	%s
</div>
HTML
);


/*** админка *************************************************************************************/

// menu item selector, entire <SELECT> template
define('MODULE_MENU_ADMIN_ELEM_LIST_WRAPPER', <<<'HTML'
<select name="parent" class="form-control" id="edit_parent">
	<option value="0">--root--</option>
	%s
</select>
HTML
);

// menu item selector, single <OPTION> element
define('MODULE_MENU_ADMIN_ELEM_LIST_OPTION', <<<'HTML'
<option value="%s" %s>%s</option>
HTML
);

// page selector, entire <SELECT> wrapper
define('MODULE_MENU_ADMIN_LINK_LIST_WRAPPER', <<<'HTML'
<select name="%s" class="form-control" id="edit_page">
	<option value="">-- no page --</option>
	%s
</select>
HTML
);

// page selector, single <OPTION>
define('MODULE_MENU_ADMIN_LINK_LIST_OPTION', <<<'HTML'
<option value="%s" class="%s" %s>%s</option>
HTML
);

// menu item edit form (used by AJAX-proxy)
define('MODULE_MENU_ELEM_EDIT_TABLE', <<<'HTML'
<form action="./" method="post" class="form-horizontal admin_edit_form" role="form">

	<input id="menu_id" type="hidden" name="menu_id" value="%menu_id%" />
	<input id="edit_id" type="hidden" name="id" value="%elem_id%" />
	<input type="hidden" name="action" value="update" />
	<input type="hidden" name="module" value="menu" />

	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_caption">Caption</label>
		<div class="col-sm-8"><input class="form-control" id="edit_caption" type="text" name="caption" value="%caption%" /></div>
	</div>

	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_alias">Alias</label>
		<div class="col-sm-8"><input class="form-control" id="edit_alias" type="text" name="alias" value="%alias%" /></div>
	</div>

	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_title">Title</label>
		<div class="col-sm-8"><input class="form-control" id="edit_title" type="text" name="title" value="%title%" /></div>
	</div>

	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_meta">Meta</label>
		<div class="col-sm-8"><textarea class="form-control" rows="3" id="edit_meta" name="meta">%meta%</textarea></div>
	</div>

	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_parent">Parent element</label>
		<div class="col-sm-8">%elem_list%</div>
	</div>

	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_page">Page</label>
		<div class="col-sm-8">%pages_list%</div>
	</div>

	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_link">Link</label>
		<div class="col-sm-8"><input class="form-control" id="edit_link" type="text" name="link" value="%link%" /></div>
	</div>

	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_text">Text</label>
		<div class="col-sm-8"><textarea class="form-control" id="edit_text" name="text">%text%</textarea></div>
	</div>

	<div class="form-group">
		<label class="control-label col-sm-4" for="">Picture</label>
		<div class="col-sm-8"><input class="form-control" id="edit_picture" type="text" name="picture" value="%picture%" /></div>
	</div>
		
	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_style_content">Content CSS</label>
		<div class="col-sm-8"><input class="form-control" id="edit_style_content" type="text" name="style_content" value="%style_content%" /></div>
	</div>

	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_style_item">Item class/style</label>
		<div class="col-sm-8"><input class="form-control" id="edit_style_item" type="text" name="style_item" value="%style_item%" /></div>
	</div>

	<div class="form-group">
		<label class="control-label col-sm-4" for="edit_hidden">Hidden</label>
		<div class="col-sm-8"><input id="edit_hidden" type="checkbox" name="hidden" %hidden% /></div>
	</div>

	<div class="form-group dialog_buttons">
		<input type="submit" value="update" class="btn btn-primary" />
	</div>

</form>
HTML
);

// кнопочки на каждой строке пункта меню в админке
define('MODULE_MENU_ADMIN_AT_ROW_CONTROLS', <<<'HTML'
<span class="actionboard">
<a class="control_link menu_sb_btn_moveup"  ><img src="images/module_menu/green.gif"    alt="up"   /></a>
<a class="control_link menu_sb_btn_movedown"><img src="images/module_menu/red.gif"      alt="down" /></a>
<a class="control_link menu_sb_btn_edit"    ><img src="images/module_menu/pencil.gif"   alt="edit" /></a>
<a class="control_link menu_sb_btn_delete"  ><img src="images/module_menu/redcross.gif" alt="del"  /></a>
</span>
HTML
);

// вся админка - обертка общая, кнопки, обертка для списка
define('MODULE_MENU_ADMIN_ENTIRE_CONTENT', <<<'HTML'

<div class="admin_buttons">

<input type="hidden" value="%menu_id%" id="current_menu_id" />

<div class="admin_make_inline">
	<div class="btn btn-default" id="button_add">add item</div>
</div>

<div class="admin_make_inline">
	<form action="." method="get" class="form-inline" role="form">
		<input type="hidden" name="module" value="menu" />
		<input type="hidden" name="action" value="manage" />
		<label for="menu_selector">menu: </label>
		<select name="menu_id" id="menu_selector" class="form-control">
		%menu_list%
		</select>
		<input type="submit" value="select" class="btn btn-default" />
	</form>
</div>

<div class="admin_make_inline">
	<form action="." method="post" class="form-inline" role="form">
		<input type="hidden" name="module" value="menu" />
		<input type="hidden" name="action" value="add_menu" />
		<input class="form-control" type="text" name="add_more" />
		<input type="submit" value="add menu" class="btn btn-default" />
	</form>
</div>

</div>

<div class="admin_content admin_menu_tree">

%menu_tree%

</div>
HTML
);

?>