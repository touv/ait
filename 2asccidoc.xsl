<xsl:stylesheet 
xmlns:xsl="http://www.w3.org/1999/XSL/Transform" 
xmlns:strings="http://exslt.org/strings"
extension-element-prefixes="strings"
version="1.0">
<xsl:output method="text" version="1.0" indent="yes" standalone="yes" />
<xsl:template match="/">
<xsl:apply-templates select="//memberdef[@kind='function']"/>
</xsl:template>
<xsl:template match="memberdef[@kind='function']">
<xsl:text>
// ----------------------------------------------------------------------------
</xsl:text>
<xsl:value-of select="./name"/>
<xsl:text>
</xsl:text>
<xsl:value-of select="strings:padding(string-length(./name), string('^'))" />
<xsl:text>

Synopsis
++++++++

    </xsl:text>
<xsl:choose>
<xsl:when test="count(.//simplesect[@kind='return']/para) = 0">
<xsl:text>void</xsl:text>
</xsl:when>
<xsl:otherwise>
<xsl:value-of select=".//simplesect[@kind='return']/para"/>
</xsl:otherwise>
</xsl:choose>
<xsl:text> </xsl:text>
<xsl:value-of select="concat(substring-after(./definition,'::'),./argsstring)"/>
<xsl:text>

Description
+++++++++++

</xsl:text>
<xsl:value-of select="./detaileddescription/para"/>
<xsl:text>

Paramètres
++++++++++

</xsl:text>
<xsl:choose>
<xsl:when test="count(.//parameterlist[@kind='param']/parameteritem) = 0">
<xsl:text>Aucun.</xsl:text>
</xsl:when>
<xsl:otherwise>
<xsl:apply-templates select=".//parameterlist[@kind='param']/parameteritem"/>
</xsl:otherwise>
</xsl:choose>
<xsl:text>

Retour
++++++

</xsl:text>
<xsl:choose>
<xsl:when test="count(.//simplesect[@kind='return']/para) = 0">
<xsl:text>NULL.</xsl:text>
</xsl:when>
<xsl:otherwise>
<xsl:value-of select=".//simplesect[@kind='return']/para"/>
</xsl:otherwise>
</xsl:choose>
<xsl:text>
</xsl:text>
</xsl:template>
<xsl:template match="parameterlist[@kind='param']/parameteritem">
<xsl:value-of select=".//parametername"/>
<xsl:text> </xsl:text>
<xsl:text>*</xsl:text>
<xsl:value-of select="substring-before(.//parameterdescription/para,' ')"/>
<xsl:text>*:: </xsl:text>
<xsl:value-of select="substring-after(.//parameterdescription/para,' ')"/>
<xsl:text>
</xsl:text>
</xsl:template>
<xsl:template match="*|@*|comment()"/>
</xsl:stylesheet>
