<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/">
	<xsl:text disable-output-escaping='yes'>&lt;!DOCTYPE html></xsl:text>
	<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>gallery! admin</title>
	<link rel="stylesheet" type="text/css" href="gallery.css" />
	<link rel="stylesheet" type="text/css" href="security.css" />
	<script language="javascript" src="jquery.js" type="text/javascript"></script>
	<script language="javascript" src="security.js" type="text/javascript"></script>
	</head>
	<body>
	
	<span class="sys_message"><xsl:value-of select='/root/message' /></span>
	<a href='main.php'>Готово, вернуться к картинкам</a>
	<hr />
	
	<span class="header">Добавить пользователя</span>
	<form action="security.php" method="post">
	<input type="hidden" name="action" value="adduser" />
	<table class="commonform">
	<tr><td class="cf_l w100">Логин</td><td class="cf_r w300"><input class="textinput" type="text" name="login" /></td></tr>
	<tr><td class="cf_l">Пароль</td><td class="cf_r"><input class="textinput" type="password" name="password1" /></td></tr>
	<tr><td class="cf_l">Еще раз</td><td class="cf_r"><input class="textinput" type="password" name="password2" /></td></tr>
	<tr><td class="cf_c" colspan="2"><input type="submit" value="Создать" class="onetimebutton" /></td></tr>
	</table>
	</form>
	<hr />
	
	<span class="header">Все пользователи</span>
	<select id="userlist">
	<xsl:apply-templates select="/root/userlist/user" />
	</select>
	
	<hr />
	<xsl:apply-templates select="/root/current_folder" />

	</body>
	</html>
</xsl:template>

<xsl:template match="/root/current_folder">
	<span class="header">Настройки доступа для альбома &quot;<xsl:value-of select="/root/current_folder/caption" />&quot;</span>
	<span>Видимость папки
	<select name="public_value" id="publicfolder">
	<xsl:choose>
		<xsl:when test="/root/current_folder/public = 0">
			<option value="0" selected="selected">Общедоступная</option>
			<option value="1">Приватная</option>
			<option value="2">Скрытая</option>
		</xsl:when>
		<xsl:when test="/root/current_folder/public = 1">
			<option value="0">Общедоступная</option>
			<option value="1" selected="selected">Приватная</option>
			<option value="2">Скрытая</option>
		</xsl:when>
		<xsl:when test="/root/current_folder/public = 2">
			<option value="0">Общедоступная</option>
			<option value="1">Приватная</option>
			<option value="2" selected="selected">Скрытая</option>
		</xsl:when>
	</xsl:choose>
	</select>
	</span>
	<div id="userlistdiv">
	<span>Кому разрешен доступ:</span>
	<select multiple="multiple" id="accesslist">
	<xsl:apply-templates select="/root/current_folder/acl/user" />
	</select>
	</div>
	<input type="button" id="adduser" value="Добавить" class="onetimebutton" />
	<input type="button" id="deluser" value="Удалить" class="onetimebutton" />
	<hr />
</xsl:template>

<xsl:template match="/root/userlist/user|/root/current_folder/acl/user">
<option value="{@id}"><xsl:value-of select="." /></option>
</xsl:template>

</xsl:stylesheet>