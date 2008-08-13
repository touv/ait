<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version='1.0'>

	<xsl:import href="file:///usr/share/docbook-xsl/xhtml/chunk.xsl"/>

	<xsl:param name="html.stylesheet" select="'../default.css docbook.css'"/>
	<xsl:param name="section.autolabel" select="1" />
	<xsl:param name="generate.chapter.toc" select="1" />
	<xsl:param name="css.decoration" select="0" />
	<xsl:param name="toc.max.depth" select="2" />

	<xsl:template name="user.header.navigation">
		<xsl:comment>#include virtual="header.html"</xsl:comment>
		<xsl:comment>#include virtual="navigation.html"</xsl:comment>
		<xsl:comment>#include virtual="startcontent.html"</xsl:comment>
	</xsl:template>

	<xsl:template name="user.footer.navigation">
			<xsl:comment>#include virtual="stopcontent.html"</xsl:comment>
			<xsl:comment>#include virtual="../signature.html"</xsl:comment>
		<xsl:comment>#include virtual="footer.html"</xsl:comment>
	</xsl:template>


</xsl:stylesheet>

