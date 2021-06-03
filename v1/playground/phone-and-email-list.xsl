<?xml version="1.0" encoding="UTF-8" ?>
<?nackasmu-title "Telefonnummer och e-postadresser"?>
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <!--<xsl:output encoding="UTF-8" indent="yes" method="xml" />-->
    <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes" omit-xml-declaration="yes"/>
    <xsl:template match="/">
        <html>
            <head>
                <link rel="stylesheet" type="text/css" href="core.css" />
                <title>Telefonlista - kort och bred</title>
                <meta content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" name="viewport" />
            </head>
            <body>
                <h1>Telefonlista</h1>
                <table>
                    <thead>
                        <tr>
                            <th colspan="3">Scout</th>
                            <th colspan="3">Kontakt 1</th>
                            <th colspan="3">Kontakt 2</th>
                        </tr>
                    </thead>
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
        <xsl:variable name="age" select="2013 - substring(@ssn, 1, 4)" />
        <tr>
            <xsl:call-template name="genericPerson">
                <xsl:with-param name="node" select="." />
            </xsl:call-template>
            <xsl:call-template name="genericPerson">
                <xsl:with-param name="node" select="parent[1]" />
            </xsl:call-template>
            <xsl:call-template name="genericPerson">
                <xsl:with-param name="node" select="parent[2]" />
            </xsl:call-template>
        </tr>
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
        <td>
            <xsl:for-each select="$node/emailAddress">
                <address>
                    <xsl:value-of select="@address" />
                </address>
            </xsl:for-each>
        </td>
    </xsl:template>
</xsl:stylesheet>