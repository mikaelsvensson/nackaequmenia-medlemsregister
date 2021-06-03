<?xml version="1.0" encoding="UTF-8" ?>
<?nackasmu-title "Telefonlista - lång och smal"?>
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <!--<xsl:output encoding="UTF-8" indent="yes" method="xml" />-->
    <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes" omit-xml-declaration="yes"/>
    <xsl:template match="/">
        <html>
            <head>
                <link rel="stylesheet" type="text/css" href="core.css" />
                <title>Telefonlista - lång och smal</title>
                <meta content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" name="viewport" />
            </head>
            <body>
                <h1>Telefonlista</h1>
                <table>
                    <tbody>
                        <xsl:apply-templates select="//person[not(@left)]">
                            <xsl:sort select="@name" />
                        </xsl:apply-templates>
                    </tbody>
                </table>
            </body>
        </html>
    </xsl:template>
    <xsl:template match="person">
        <tr>
            <xsl:call-template name="genericPerson">
                <xsl:with-param name="node" select="." />
            </xsl:call-template>
        </tr>
        <xsl:if test="parent[1]">
            <tr class="indent no-border">
                <xsl:call-template name="genericPerson">
                    <xsl:with-param name="node" select="parent[1]" />
                </xsl:call-template>
            </tr>
        </xsl:if>
        <xsl:if test="parent[2]">
            <tr class="indent no-border">
                <xsl:call-template name="genericPerson">
                    <xsl:with-param name="node" select="parent[2]" />
                </xsl:call-template>
            </tr>
        </xsl:if>
    </xsl:template>
    <xsl:template name="genericPerson">
        <xsl:param name="node" />
        <td>
            <xsl:value-of select="$node/@name" />
        </td>
        <td>
            <xsl:for-each select="$node/phone">
                <address>
                    <xsl:value-of select="@number" />
                </address>
            </xsl:for-each>
        </td>
    </xsl:template>
</xsl:stylesheet>