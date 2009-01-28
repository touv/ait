<?xml version='1.0'?>
<xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform" version='1.0'>

	<xsl:import href="file:///etc/asciidoc/docbook-xsl/chunked.xsl"/>

    <xsl:param name="html.stylesheet" select="'../default.css ../docbook.css'"/>
	<xsl:param name="chapter.autolabel" select="1" />
	<xsl:param name="section.autolabel" select="1" />
	<xsl:param name="generate.chapter.toc" select="1" />
	<xsl:param name="css.decoration" select="0" />
    <xsl:param name="toc.max.depth" select="5" />
    <xsl:param name="chunk.section.depth" select="2" />
    <xsl:param name="generate.section.toc.level" select="1" />
    <xsl:param name="toc.section.depth" select="1" />

<!--<xsl:param name="generate.toc">-->
<!--book      toc-->
<!--chapter   toc,title-->
<!--article   toc,title-->
<!--sect1     toc-->
<!--sect2     toc-->
<!--sect3     toc-->
<!--sect4     toc-->
<!--sect5     toc-->
<!--section   toc-->
<!--set       toc,title-->
<!--</xsl:param>-->



	<xsl:template name="user.header.navigation">
		<xsl:comment>#include virtual="../docbook.header.html"</xsl:comment>
		<xsl:comment>#include virtual="../docbook.navigation.html"</xsl:comment>
		<xsl:comment>#include virtual="../docbook.startcontent.html"</xsl:comment>
	</xsl:template>

	<xsl:template name="user.footer.navigation">
		<xsl:comment>#include virtual="../docbook.stopcontent.html"</xsl:comment>
		<xsl:comment>#include virtual="../signature.html"</xsl:comment>
	</xsl:template>


</xsl:stylesheet>

