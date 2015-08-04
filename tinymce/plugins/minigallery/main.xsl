<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/">
	<xsl:text disable-output-escaping="yes">&lt;!DOCTYPE html></xsl:text>
	<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>
		<xsl:text>gallery!</xsl:text>
		<xsl:if test="/root/options/@ghostmode = 'yes'">
			<xsl:text>&#160;(ghost mode)&#160;</xsl:text>
		</xsl:if>
	</title>
	<link rel="stylesheet" type="text/css" href="gallery.css" />
	<link rel="stylesheet" type="text/css" href="jquery-ui.css" />
    <link rel="stylesheet" type="text/css" href="jquery.tooltip.css" media="screen" />
    <link rel="stylesheet" type="text/css" href="prettyPhoto.css" media="screen" />
	<script src="mozq.js" type="text/javascript"></script>
	<script src="jquery.js" type="text/javascript"></script>
	<script src="jquery-ui.js" type="text/javascript"></script>
	<script src="jquery.prettyPhoto.js" type="text/javascript"></script>
	<script src="jquery.tooltip.min.js" type="text/javascript"></script>
	<script src="jquery.disable.text.select.js" type="text/javascript"></script>
	<script src="js.js" type="text/javascript"></script>
	</head>
	<body>
	
	<div id="mainbar">
<!-- path to the folder *********************************************************************** -->
		<xsl:if test="/root/options/@show_path = 'yes'">
			<div id="folder_path">
			<xsl:if test="/root/options/@logged = 'yes'">
				<span id="user_login"><xsl:value-of select="/root/userinfo/login" /></span>
				<span id="folder_path_divider">@</span>
			</xsl:if>
			<xsl:apply-templates select="/root/folderpath" />	
			</div>
		</xsl:if>

<!-- buttons as needed ************************************************************************ -->
<!--
		<xsl:if test="/root/options/@logged = 'yes'">
			<div class="buttondiv bn right" id="b_logout"><img src="./images/off.png" alt="" /></div>
		</xsl:if>
		<xsl:if test="/root/options/@logged = 'no'">
			<div class="buttondiv bn right" id="b_login"><img src="./images/key.png" alt="" /></div>
		</xsl:if>
-->
		<xsl:if test="/root/options/@admin = 'yes'">
<!--			<div class="buttondiv bn right" id="b_acl"><img src="./images/security.png" alt="" /></div> -->
			<div class="buttondiv bn right" id="b_del_item"><img src="./images/del.png" alt="" /></div>
			<div class="buttondiv bn right" id="b_move_item"><img src="./images/move.gif" alt="" /></div>
		</xsl:if>
		<xsl:if test="/root/options/@adder = 'yes'">
			<div class="buttondiv bn right" id="b_add_album"><img src="./images/folder_add.png" alt="" /></div>
			<div class="buttondiv bn right" id="b_add_photo"><img src="./images/add.png" alt="" /></div>
		</xsl:if>

<!--		<div class="buttondiv bn right" id="b_help"><img src="./images/info.png" alt="" /></div> -->
	
		<div class="antifloat"></div>
	</div>

<!-- popups *********************************************************************************** -->
	<div id="loginform" class="popupform">
		<form action="auth.php" method="post">
		<table class="commonform">
		<tr><td class="cf_l w200">Логин</td><td class="cf_r w300"><input class="textinput" type="text" name="login" /></td></tr>
		<tr><td class="cf_l">Пароль</td><td class="cf_r"><input class="textinput" type="password" name="password" /></td></tr>
		<tr><td class="cf_c" colspan="2"><input type="submit" value="Войти" /></td></tr>
		</table>
		</form>
	</div>

	<div id="addphotoform" class="popupform">
		<form action="manage.php" enctype="multipart/form-data" method="post">
		<input type="hidden" name="action" value="addfile" />
		<table class="commonform">
		<tr><td class="cf_l w200">Файл</td><td class="cf_r w300"><input id="filenameinput" class="filenameinput" type="file" multiple="multiple" accept="image/*" name="filename[]" /></td></tr>
		<tr><td class="cf_l w200">URL</td><td class="cf_r w300"><input id="urlinput" class="textinput" type="text" name="url" /></td></tr>
		<tr><td class="cf_comment" colspan="2">Можно одновременно URL и файл. Или несколько файлов.</td></tr>
		<tr><td class="cf_l">Название</td><td class="cf_r"><input class="textinput" type="text" name="caption" /></td></tr>
		<tr><td class="cf_l">Ключевые слова</td><td class="cf_r"><input class="textinput" type="text" name="keywords" /></td></tr>
		<tr><td class="cf_comment" colspan="2">Допустимые форматы: jpg, gif, png. Не более 2МБ.</td></tr>
		<tr><td class="cf_l cf_top">Описание</td><td class="cf_r"><textarea class="textinput" rows="5" name="description" /></td></tr>
		<tr><td class="cf_c" colspan="2"><input id="a111" type="submit" value="Загрузить" class="onetimebutton" /></td></tr>
		</table>
		</form>
	</div>

	<div id="addalbumform" class="popupform">
		<form action="manage.php" method="post">
		<input type="hidden" name="action" value="addalbum" />
		<table class="commonform">
		<tr><td class="cf_l w200">Название</td><td class="cf_r w300"><input class="textinput" type="text" name="albumname" /></td></tr>
		<tr><td class="cf_l w200">Алиас</td><td class="cf_r w300"><input class="textinput" type="text" name="albumalias" /></td></tr>
		<tr><td class="cf_l w200">Скрыть</td><td class="cf_r w300"><input class="textinput" type="checkbox" name="albumhidden" /></td></tr>
		<tr><td class="cf_c" colspan="2"><input id="a222" type="submit" value="Создать" class="onetimebutton" /></td></tr>
		</table>
		</form>
	</div>

	<div id="moveform" class="popupform">
		<table class="commonform">
		<tr>
			<td class="cf_l w200">Куда переместим?</td>
			<td class="cf_r w300">
				<select class="textinput" id="movetarget" name="movetarget">
					<option value="00000000-0000-0000-0000-000000000000">* в корень *</option>
					<xsl:for-each select="/root/folderlist/folder">
						<option value="{@id}">
						<xsl:value-of select="." />
						</option>
					</xsl:for-each>
				</select>
			</td>
		</tr>
		<tr><td class="cf_c" colspan="2"><input id="movebutton" type="button" value="Переместить" /></td></tr>
		</table>
	</div>
	
	<div id="helpform" class="popupform">
		help here
	</div>
	
	<xsl:apply-templates select="/root/message" />
	
<!-- folders and pictures ********************************************************************* -->
	<div class="album" id="all_images">
		<xsl:apply-templates select="/root/list" />
		<div class="antifloat"></div>
	</div>

<!-- some common info ************************************************************************* -->
	</body>
	</html>
</xsl:template>

<!-- folder path info ************************************************************************* -->
<xsl:template match="folder">
	<div class="folder_path_element">
		<span>
			<xsl:if test="not (@root = 'yes')">
				<xsl:text>&#160;/&#160;</xsl:text>
			</xsl:if>
			<xsl:choose>
				<xsl:when test="@current = 'yes'">
					<xsl:value-of select="caption" disable-output-escaping="yes" />
				</xsl:when>
				<xsl:otherwise>
					<a href="main.php?id={id}"><xsl:value-of select="caption" disable-output-escaping="yes"  /></a>
				</xsl:otherwise>
			</xsl:choose>
		</span>
	</div>
</xsl:template>

<!-- album and items ************************************************************************** -->
<xsl:template match="albumitem">

	<div>
		<xsl:choose>
			<xsl:when test="./public = '0'">
				<xsl:attribute name="class">album_item album_public</xsl:attribute>
			</xsl:when>
			<xsl:when test="./public = '1'">
				<xsl:attribute name="class">album_item album_private</xsl:attribute>
			</xsl:when>
			<xsl:when test="./public = '2'">
				<xsl:attribute name="class">album_item album_hidden</xsl:attribute>
			</xsl:when>
			<xsl:otherwise>
				<xsl:attribute name="class">album_item album_public</xsl:attribute>
			</xsl:otherwise>
		</xsl:choose>	
		<div class="cb_div">
			<xsl:if test="/root/options/@admin = 'yes'">
				<input type="checkbox" name="checkbox_{@id}" id="cb_{@id}" class="cb" />
			</xsl:if>
		</div>
		<xsl:choose>
			<xsl:when test="./type = 1">
				<a class="preview_div">
					<xsl:choose>
						<xsl:when test="./public = '2'">
							<xsl:attribute name="href">main.php?id=<xsl:value-of select="./@id" /></xsl:attribute>
						</xsl:when>
						<xsl:otherwise>
							<xsl:attribute name="href">main.php?id=<xsl:value-of select="./name" /></xsl:attribute>
						</xsl:otherwise>
					</xsl:choose>
					&#160;<img src="images/folder.gif" class="preview" alt="" />&#160;
				</a>
			</xsl:when>
			<xsl:otherwise>
				<div class="img_link preview_div">
					<a href="image.php?id={@id}" data-gallery="prettyPhoto[gal]" title="{description}">
						&#160;<img src="image.php?id={@id}&amp;preview" class="preview" alt="{caption}" />&#160;
					</a>
				</div>
			</xsl:otherwise>
		</xsl:choose>	
		<div class="caption_div">
			<xsl:if test="./type = 0">
				<input type="text" value="{full_path}" style="width: 80%; border: 1px blue solid; display: block; margin-left: 8%;" />
			</xsl:if>
			<xsl:value-of select="caption" disable-output-escaping="yes" />
		</div>
		<div class="keywords_div">
			<xsl:value-of select="keywords" disable-output-escaping="yes" />
		</div>
		<div class="description_div">
			<xsl:value-of select="description" disable-output-escaping="yes" />
		</div>
	</div>
</xsl:template>

<xsl:template match="message">
	<div id="errorform" class="popupform">
	<span class="block"><xsl:value-of select="." /></span>
	<input type="button" id="ef_ok" class="block" value="OK" />
	</div>
</xsl:template>

<!-- placeholders ***************************************************************************** -->
<xsl:template match="parentinfo|albuminfo|userinfo">
</xsl:template>

</xsl:stylesheet>