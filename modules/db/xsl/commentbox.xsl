<?xml version="1.0" encoding="utf-8"?>

<!--

standard comments list

-->

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />


<xsl:template match="/comments">

	<div class="commentbox-outer-wrapper" data-attach-handlers="commentsFormHandlers" data-report-id="{report_id}">
		<div class="commentbox-comment-list">
			<a class="commentbox-print-icon" target="_blank" href="">
				<img src="images/printer.gif" alt="print" />
			</a>
			<xsl:apply-templates select="comment" />
		</div>

		<form class="commentbox-adder-form" action="." method="post" role="form" enctype="multipart/form-data">

			<input type="hidden" name="ajaxproxy" value="db" />
			<input type="hidden" name="action"    value="call_api" />
			<input type="hidden" name="method"    value="comments_add" />
			<input type="hidden" name="row_id"    value="{main_object_id}" />

			<div class="form-group">
				<label for="commentbox-add-area">Добавить комментарий:</label>
				<textarea id="commentbox-add-area" class="form-control" wrap="physical" rows="6" name="comment_text"></textarea>
			</div>
			
			<div class="form-group">
				<label for="commentbox-add-files">Прикрепить файлы:</label>
				<input id="commentbox-add-files" type="file" name="attachthis[]" class="commentbox-add-file form-control" multiple="multiple" />
			</div>
			
			<div class="edit-dialog-buttons">
				<input type="button" class="btn btn-primary" value="Добавить комментарий" data-button-action="form-submit" />
				<input type="button" class="btn btn-primary" value="Закрыть" data-button-action="form-cancel" />
			</div>
		</form>
	</div>
	
</xsl:template>


<xsl:template match="comment">
	<div class="commentbox" data-comment-id="{id}">
		<p class="commentbox-single-header">
			<img class="commentbox-single-delete-button" src="images/red_cross_diag.png" alt="del"  data-button-action="comment_delete" />
			<xsl:value-of select="used_id" />
			<xsl:value-of select="stamp" />
		</p>
		<p class="commentbox-single-object-info">
			<!-- <xsl:text>[comment target]</xsl:text> -->
			<xsl:if test="attached_name!=''">
				<a class="commentbox-single-attached-info" href="" target="_blank">
					<img src="images/floppy.png" alt="download" />
					<xsl:value-of select="attached_name" />
				</a>
			</xsl:if>
		</p>
		<p class="commentbox-single-text">
			<xsl:value-of select="comment_text" />
		</p>
	</div>
</xsl:template>


</xsl:stylesheet>