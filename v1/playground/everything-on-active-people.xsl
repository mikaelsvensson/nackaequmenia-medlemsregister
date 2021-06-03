<?xml version="1.0" encoding="UTF-8" ?>
<?nackasmu-title "All information om aktiva scouter"?>
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <!--<xsl:output encoding="UTF-8" indent="yes" method="xml" />-->
    <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes" omit-xml-declaration="yes"/>
    <xsl:template match="/">
        <html>
            <head>
                <link rel="stylesheet" type="text/css" href="core.css" />
                <link rel="stylesheet" type="text/css" href="jquery.tablesorter.css" />
                <title>All information om aktiva scouter</title>
                <script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
                <script type="text/javascript" src="jquery.tablesorter.js"></script>
                <script type="text/javascript">
                    $(document).ready(function() 
                        { 
                            $("#table").tablesorter(); 
                        } 
                    ); 
                </script>
                <meta content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" name="viewport" />
            </head>
            <body>
                <h1>Medlemsregister</h1>
                <table id="table" class="tablesorter">
                    <thead>
                        <tr>
                            <th colspan="5">Scout</th>
                            <th colspan="4">Ålder och grupp</th>
                            <th colspan="2">Noteringar</th>
                            <th colspan="5">Förälder 1</th>
                            <th colspan="5">Förälder 2</th>
                            <th colspan="5">Fotopublicering</th>
                        </tr>
                        <tr>
                            <th>Namn</th>
                            <th>Gatuadress</th>
                            <th>Postadress</th>
                            <th>Telefon</th>
                            <th>E-post</th>
                            <th>Personnummer</th>
                            <th>Ung. ålder</th>
                            <th>Åldersgrupp</th>
                            <th>Patrull</th>
                            <th>Allergier</th>
                            <th>Notering</th>
                            <th>Namn</th>
                            <th>Gatuadress</th>
                            <th>Postadress</th>
                            <th>Telefon</th>
                            <th>E-post</th>
                            <th>Namn</th>
                            <th>Gatuadress</th>
                            <th>Postadress</th>
                            <th>Telefon</th>
                            <th>E-post</th>
                            <th>Nacka SMU-reklam</th>
                            <th>Scoutreklam</th>
                            <th>Fotografportfolio</th>
                            <th>På internet</th>
                            <th>Visa namn</th>
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
            <td>
                <xsl:value-of select="@ssn" />
            </td>
            <td>
                <xsl:value-of select="$age" />
            </td>
            <td>
                <xsl:choose>
                    <xsl:when test="$age &lt;= 10">Spårare</xsl:when>
                    <xsl:when test="$age &lt;= 12">Upptäckare</xsl:when>
                    <xsl:when test="$age &lt;= 15">Äventyrare</xsl:when>
                    <xsl:when test="$age &lt;= 18">Utmanare</xsl:when>
                    <xsl:when test="$age &lt;= 25">Rover</xsl:when>
                    <xsl:otherwise>Gammal som gatan</xsl:otherwise>
                </xsl:choose>
            </td>
            <td>
                <xsl:value-of select="@group" />
            </td>
            <td>
                <xsl:value-of select="@allergies" />
            </td>
            <td>
                <xsl:value-of select="note" />
            </td>
            <xsl:call-template name="genericPerson">
                <xsl:with-param name="node" select="parent[1]" />
            </xsl:call-template>
            <xsl:call-template name="genericPerson">
                <xsl:with-param name="node" select="parent[2]" />
            </xsl:call-template>
            <xsl:call-template select="modelRelease" name="modelRelease" />
        </tr>
    </xsl:template>
    <xsl:template name="genericPerson">
        <xsl:param name="node" />
        <td>
            <xsl:value-of select="$node/@name" />
        </td>
        <td>
            <xsl:value-of select="$node/address/@street" />
        </td>
        <td>
            <xsl:value-of select="$node/address/@postal" />
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
    <xsl:template name="modelRelease">
        <xsl:choose>
            <xsl:when test="modelRelease">
                <td>
                    <xsl:value-of select="modelRelease/@nackaSmuPromotion" />
                </td>
                <td>
                    <xsl:value-of select="modelRelease/@scoutPromotion" />
                </td>
                <td>
                    <xsl:value-of select="modelRelease/@photographerPortfolio" />
                </td>
                <td>
                    <xsl:value-of select="modelRelease/@onInternet" />
                </td>
                <td>
                    <xsl:value-of select="modelRelease/@namePermitted" />
                </td>
            </xsl:when>
            <xsl:otherwise>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </xsl:otherwise>
        </xsl:choose>
    </xsl:template>
</xsl:stylesheet>