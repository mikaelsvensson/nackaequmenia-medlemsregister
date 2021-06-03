<?xml version="1.0" encoding="UTF-8" ?>
<?nackasmu-title "VCF-fil för alla aktiva scouter"?>
<!--
<?nackasmu-content-type "text/plain"?>
<?nackasmu-content-type "text/vcard"?>
<?nackasmu-content-type "text/vcard"?>
<?nackasmu-content-type "text/x-vcard"?>
-->
<?nackasmu-noauth-download?>
<?nackasmu-content-type "application/octet-stream"?>
<?nackasmu-content-disposition 'attachment; filename="nacka-smu-contacts.VCF"'?>
<!--
-->
<xsl:stylesheet version="1.0"
                xmlns:str="http://exslt.org/strings"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
    <xsl:output method="text" encoding="utf-8"/>
    <xsl:strip-space elements="*" />
    
    <xsl:key name="people" match="//person | //parent" use="@name" />
        
    <xsl:template match="/">

<!--
        <xsl:for-each-group select="//person[not(@left)] | //person[not(@left)]/parent" group-by="@name">
            <xsl:sort select="@name"/>
            <xsl:apply-templates select="."/>
        </xsl:for-each-group>
    -->        
        <!--
        <xsl:apply-templates select="//person[not(@left)]">
            <xsl:sort select="@name" />
        </xsl:apply-templates>
            -->
        <xsl:apply-templates select="//person[not(@left) and generate-id(.)=generate-id(key('people',@name)[1])] | //parent[not(../@left) and generate-id(.)=generate-id(key('people',@name)[1])]">
            <xsl:sort select="@name" />
        </xsl:apply-templates>
    </xsl:template>
    <xsl:template match="person | parent">
        <xsl:call-template name="genericPerson">
            <xsl:with-param name="node" select="." />
        </xsl:call-template>
        <!--
        <xsl:if test="parent[1]">
            <xsl:call-template name="genericPerson">
                <xsl:with-param name="node" select="parent[1]" />
            </xsl:call-template>
        </xsl:if>
        <xsl:if test="parent[2]">
            <xsl:call-template name="genericPerson">
                <xsl:with-param name="node" select="parent[2]" />
            </xsl:call-template>
        </xsl:if>
            -->
    </xsl:template>
    <xsl:template name="genericPerson">
        <xsl:param name="node"/>
        <xsl:text>BEGIN:VCARD&#10;</xsl:text>
        <!--
            -->
        <xsl:text>VERSION:2.1&#10;</xsl:text>
        <xsl:text>N:</xsl:text>
        <xsl:value-of select="$node/@name" />
        <xsl:variable name="children" select="/data/person[parent/@name = $node/@name]"/>
        <xsl:if test="$children">
            <xsl:text> (</xsl:text>
            <xsl:for-each select="$children">
                <xsl:if test="position() &gt; 1">
                    <xsl:text>, </xsl:text>
                </xsl:if>
                <xsl:value-of select="substring-before(@name, ' ')" />
            </xsl:for-each>
            <xsl:text>)</xsl:text>
        </xsl:if>
        <xsl:text>&#10;</xsl:text>
        <xsl:if test="$node/photo">
            <xsl:choose>
                <xsl:when test="substring($node/photo/@url, (string-length($node/photo/@url) - string-length('jpg')) + 1) = 'jpg'">
                    <xsl:text>PHOTO;JPEG:http://www.nackasmu.se/medlemsregister/photos/</xsl:text><xsl:value-of select="str:encode-uri($node/photo/@url,false())"/><xsl:text>&#10;</xsl:text>
                </xsl:when>
                <xsl:when test="substring($node/photo/@url, (string-length($node/photo/@url) - string-length('gif')) + 1) = 'gif'">
                    <xsl:text>PHOTO;GIF:http://www.nackasmu.se/medlemsregister/photos/</xsl:text><xsl:value-of select="$node/photo/@url"/><xsl:text>&#10;</xsl:text>
                </xsl:when>
            </xsl:choose>
        </xsl:if>
        <xsl:if test="$node/phone[starts-with(@number, '07')]">
            <xsl:text>TEL;CELL;VOICE:</xsl:text><xsl:value-of select="str:replace($node/phone[starts-with(@number, '07')][1]/@number, ' ', '')"/><xsl:text>&#10;</xsl:text>
        </xsl:if>
        <xsl:if test="$node/phone[not(starts-with(@number, '07'))]">
            <xsl:text>TEL;HOME;VOICE:</xsl:text><xsl:value-of select="str:replace($node/phone[not(starts-with(@number, '07'))][1]/@number, ' ', '')"/><xsl:text>&#10;</xsl:text>
        </xsl:if>
        <xsl:if test="$node/@ssn">
            <xsl:text>BDAY:</xsl:text><xsl:value-of select="substring($node/@ssn, 0, 9)"/><xsl:text>&#10;</xsl:text>
        </xsl:if>
        <xsl:for-each select="$node/emailAddress">
            <xsl:text>EMAIL:</xsl:text><xsl:value-of select="@address"/><xsl:text>&#10;</xsl:text>
        </xsl:for-each>
        <xsl:if test="$node/node | $node/@allergies | $node/parent | $children">
            <xsl:text>NOTE:</xsl:text>
            <xsl:if test="$node/@allergies">
                <xsl:value-of select="concat('Allergier: ', $node/@allergies, '. ')"/>
            </xsl:if>
            <xsl:if test="$node/parent">
                <xsl:text>Föräldrar: </xsl:text>
                <xsl:for-each select="$node/parent">
                    <xsl:if test="position() &gt; 1">
                        <xsl:text>, </xsl:text>
                    </xsl:if>
                    <xsl:value-of select="@name" />
                </xsl:for-each>
                <xsl:text>. </xsl:text>
            </xsl:if>
            <xsl:if test="$children">
                <xsl:text>Barn: </xsl:text>
                <xsl:for-each select="$children">
                    <xsl:if test="position() &gt; 1">
                        <xsl:text>, </xsl:text>
                    </xsl:if>
                    <xsl:value-of select="@name" />
                </xsl:for-each>
                <xsl:text>. </xsl:text>
            </xsl:if>
            <xsl:value-of select="$node/note"/>
            <xsl:text>&#10;</xsl:text>            
        </xsl:if>
        <xsl:apply-templates select="//address[0]"/>
        <xsl:text>END:VCARD&#10;</xsl:text>
    </xsl:template>
    <xsl:template match="address">
        <xsl:text>ADR;TYPE=HOME:;;</xsl:text>
        <!-- Street      -->
        <xsl:value-of select="$node/address/@street" />
        <xsl:text>;</xsl:text>
        <!-- City        -->
        <xsl:value-of select="$node/address/@postal" />
        <xsl:text>;</xsl:text>
        <!-- State       -->
        <xsl:text>;</xsl:text>
        <!-- Postcode    -->
        <xsl:text>;</xsl:text>
        <!-- Country     -->
        <xsl:text>SE</xsl:text>
        <xsl:text>&#10;</xsl:text>
    </xsl:template>
</xsl:stylesheet>