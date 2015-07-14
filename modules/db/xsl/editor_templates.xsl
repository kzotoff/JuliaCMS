<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/edit-dialog">

	<div class="edit-dialog-wrapper" data-attach-handlers="editorialFormHandlers">

		<form class="form-vertical edit-dialog-form" method="post" role="form">

			<input type="hidden" name="ajaxproxy"  value="db"          />
			<input type="hidden" name="action"     value="call_api"    />
			<input type="hidden" name="method"     value="record_save">
				<xsl:if test="new_record='1'">
					<xsl:attribute name="value">record_insert</xsl:attribute>
				</xsl:if>
			</input>
			
			<input type="hidden" name="report_id"  value="{report_id}" />
			<input type="hidden" name="row_id"     value="{row_id}"    />
			<input type="hidden" name="new_record" value="{row_id}"    />

			<div class="form-group">
				<label for="edit_caption">Тема</label>
				<input name="edit_caption" id="edit_caption" type="text" class="form-control" value="{fields/field[@field_name='caption']/value}" />
			</div>

			<div class="form-group">
				<label for="edit_message">Сообщение</label>
				<textarea name="edit_message" id="edit_message" class="form-control apply_tinymce" style="width: 700px; height: 300px;"><xsl:value-of select="fields/field[@field_name='message']/value" /></textarea>
			</div>
			
			<div class="edit-dialog-buttons">
				<input type="button" class="btn btn-primary" value="Сохранить" data-button-action="form-submit" />
				<input type="button" class="btn btn-primary" value="Отмена" data-button-action="form-cancel" />
			</div>
		</form>

	</div>

</xsl:template>

</xsl:stylesheet>