<?xml version="1.0" encoding="utf-8"?>

<!--

standard table transformation

-->

<xsl:stylesheet id="document" version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
<xsl:output method="html" encoding="utf-8" indent="yes" />

<xsl:template match="/">
	<div class="datablock_wrapper" id="datablock_wrapper_{/report/datablock_id}" data-report-id="{report/report_id}">
	
		<xsl:variable name="table_width" select="sum(report/header/field_caption/@width)" />
	
		<div class="datablock_table_wrapper_header" id="datablock_table_wrapper_header_{/report/datablock_id}">
			
			<table class="datablock_table_header" style="width: {$table_width}px;" id="datablock_table_header_{/report/datablock_id}">
				<tr>
					<xsl:for-each select="report/header/field_caption">
						<td data-field="{@field}" style="width: {@width}px;"><xsl:value-of select="." /></td>
					</xsl:for-each>
				</tr>
			</table>

		</div>
		
		<div class="datablock_table_wrapper_content" id="datablock_table_wrapper_content_{/report/datablock_id}">

			<table class="datablock_table_content" style="width: {$table_width}px;" id="datablock_table_content_{/report/datablock_id}">
				<colgroup>
					<xsl:for-each select="report/header/field_caption">
						<col style="width: {@width}px;" />
					</xsl:for-each>
				</colgroup>
				<xsl:apply-templates select="report/data_set/data_row" />
			</table>

		</div>
		
		<div class="json_data">
			<xsl:value-of select="report/json" />
		</div>
		
	</div>

</xsl:template>


<xsl:template match="data_row">
	<tr data-row-id="{@id}" class="table_data_row">
		<xsl:for-each select="./data">
			<td data-field-name="{@field}"><xsl:value-of select="." /></td>
		</xsl:for-each>
	</tr>
</xsl:template>


</xsl:stylesheet>