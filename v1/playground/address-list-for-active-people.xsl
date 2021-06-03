<?xml version="1.0" encoding="UTF-8" ?>
<?nackasmu-title "Excel-vänlig adresslista för alla aktiva scouter"?>
<?nackasmu-content-type "text/plain"?>
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="text" encoding="iso-8859-1"/>
    <xsl:strip-space elements="*" />
    <xsl:template match="/">
        <column>#Scout - Namn,</column>
        <column>Scout - Gatuadress,</column>
        <column>Scout - Postadress,</column>
        <column>Scout - Telefon,</column>
        <column>Scout - E-post,</column>
        <column>Personnummer,</column>
        <column>Förälder 1 - Namn,</column>
        <column>Förälder 1 - Gatuadress,</column>
        <column>Förälder 1 - Postadress,</column>
        <column>Förälder 1 - Telefon,</column>
        <column>Förälder 1 - E-post,</column>
        <column>Förälder 2 - Namn,</column>
        <column>Förälder 2 - Gatuadress,</column>
        <column>Förälder 2 - Postadress,</column>
        <column>Förälder 2 - Telefon,</column>
        <column>Förälder 2 - E-post,</column>
        <column>Allergier,</column>
        <column>Notering</column>
        <xsl:text>&#10;</xsl:text>
        <xsl:apply-templates select="//person[not(@left)]">
            <xsl:sort select="@name" />
        </xsl:apply-templates>
    </xsl:template>
    <xsl:template match="person">
        <xsl:call-template name="genericPerson">
            <xsl:with-param name="node" select="." />
        </xsl:call-template>
        <xsl:text>,</xsl:text>
        <xsl:value-of select="@ssn" />
        <xsl:text>,</xsl:text>
        <xsl:call-template name="genericPerson">
            <xsl:with-param name="node" select="parent[1]" />
        </xsl:call-template>
        <xsl:text>,</xsl:text>
        <xsl:call-template name="genericPerson">
            <xsl:with-param name="node" select="parent[2]" />
        </xsl:call-template>
        <xsl:text>,"</xsl:text>
        <xsl:value-of select="@allergies" />
        <xsl:text>","</xsl:text>
        <xsl:value-of select="note" />
        <xsl:text>"&#10;</xsl:text>
    </xsl:template>
    <xsl:template name="genericPerson">
        <xsl:param name="node" />
        <xsl:value-of select="$node/@name" />
        <xsl:text>,</xsl:text>
        <xsl:value-of select="$node/address/@street" />
        <xsl:text>,</xsl:text>
        <xsl:value-of select="$node/address/@postal" />
        <xsl:text>,"</xsl:text>
        <xsl:for-each select="$node/phone">
            <xsl:if test="position() != 1">
                <xsl:text>, </xsl:text>
            </xsl:if>
            <xsl:value-of select="@number" />
        </xsl:for-each>
        <xsl:text>","</xsl:text>
        <xsl:for-each select="$node/emailAddress">
            <xsl:if test="position() != 1">
                <xsl:text>, </xsl:text>
            </xsl:if>
            <xsl:value-of select="@address" />
        </xsl:for-each>
        <xsl:text>"</xsl:text>
    </xsl:template>
</xsl:stylesheet>