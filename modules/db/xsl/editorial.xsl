<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/edit-dialog">

	<div class="edit-dialog-wrapper" data-attach-handlers="editorialFormHandlers">

		<xsl:if test="categories/category">
			<div class="edit-dialog-categories">
				<xsl:for-each select="categories/category">
					<input type="button" class="btn btn-default" data-action="show-category" data-category="{.}" value="{.}">
						<xsl:if test="./@all='all'">
							<xsl:attribute name="data-show-all">yes</xsl:attribute>
						</xsl:if>
					</input>
				</xsl:for-each>
			</div>
		</xsl:if>

		<form class="form-horizontal" method="post" action="." data-form-name="edit-dialog-form">

			<input type="hidden" name="ajaxproxy" value="db"          />
			<input type="hidden" name="action"    value="call_api"    />
			<input type="hidden" name="method"    value="record_save">
				<xsl:if test="new_record='1'">
					<xsl:attribute name="value">record_insert</xsl:attribute>
				</xsl:if>
			</input>

			<input type="hidden" name="report_id" value="{report_id}" />
			<input type="hidden" name="row_id"    value="{row_id}"    />

			<xsl:for-each select="fields/field">
				<div class="form-group">
					<xsl:attribute name="data-categories">
						<xsl:for-each select="categories/category">
							<xsl:text>/</xsl:text><xsl:value-of select="." /><xsl:text>/</xsl:text>
						</xsl:for-each>
					</xsl:attribute>
					<label class="control-label col-sm-4" for="edit_{@field_name}"><xsl:value-of select="caption" /></label>
					<xsl:choose>
						<xsl:when test="type='select'">
							<xsl:text>SELECT HERE!</xsl:text>
						</xsl:when>
						<xsl:otherwise>
							<div class="col-sm-8">
								<input name="edit_{@field_name}" type="text" class="form-control" id="edit_{@field_name}" value="{value}">
									<xsl:if test="@readonly='readonly'">
										<xsl:attribute name="class">form-control disabled</xsl:attribute>
										<xsl:attribute name="readonly">readonly</xsl:attribute>
									</xsl:if>
								</input>
							</div>
						</xsl:otherwise>
					</xsl:choose>
				</div>
			</xsl:for-each>
			
		</form>

		<div class="edit-dialog-buttons">
			<input type="button" class="btn btn-primary" value="Сохранить" data-button-action="form-submit" data-form-submit="edit-dialog-form" />
			<input type="button" class="btn btn-primary" value="Отмена"    data-button-action="form-cancel" />
		</div>


	</div>

</xsl:template>


</xsl:stylesheet>