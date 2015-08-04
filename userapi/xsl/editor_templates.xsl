<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/edit-dialog">

	<div class="edit-dialog-wrapper" data-attach-handlers="editorialFormHandlers editTemplateHandlers">
<!--		<div class="form-vertical" role="form" data-form-container="edit-dialog-form"> -->
		<form class="form-vertical" role="form" data-form-name="edit-dialog-form">

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
				<input name="edit_templates_caption" id="edit_caption" type="text" class="form-control" value="{fields/field[@field_name='templates_caption']/value}" />
			</div>

			<div class="form-group">
				<label for="edit_message">Сообщение</label>
				<textarea name="edit_templates_message" id="edit_message" class="form-control apply_tinymce" style="height: 200px;"><xsl:value-of select="fields/field[@field_name='templates_message']/value" /></textarea>
			</div>
		
		</form>
<!--		</div> -->
		
		<div data-meaning="comments-dynamic-box">
		</div>

		<hr />
		<div class="edit-dialog-buttons">
			<input type="button" class="btn btn-primary" value="Сохранить" data-button-action="form-submit" data-form-submit="edit-dialog-form" />
			<input type="button" class="btn btn-primary" value="Отмена" data-button-action="form-cancel" />
		</div>

	</div>

</xsl:template>

</xsl:stylesheet>