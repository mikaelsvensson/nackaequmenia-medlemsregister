<?xml version="1.0" encoding="UTF-8" ?>
<?nackasmu-title "Fotografier"?>
<xsl:stylesheet version="1.0"
xmlns:xsl="http://www.w3.org/1999/XSL/Transform">

    <!--<xsl:output encoding="UTF-8" indent="yes" method="xml" />-->
    <xsl:output method="html" version="1.0" encoding="UTF-8" indent="yes" omit-xml-declaration="yes"/>
    <xsl:template match="/">
        <html>
            <head>
                <link rel="stylesheet" type="text/css" href="core.css" />
                <title>Fotografier</title>
                <meta content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" name="viewport" />
                <style type="text/css">
                    body {
                        margin: 1em 0 1em 1em;
                        padding: 0;
                    }
                    #toc {
                        float: right;
                        max-width: 7em;
                        border-left: 1px solid #ddd;
                        padding: 0 0 0 0.5em;
                    }
                    #toc ul {
                        list-style-type: none;
                        margin: 0;
                        padding: 0;
                    }
                    #toc ul li {
                        margin: 0 0 0.2em 0;
                        padding: 0;
                        white-space: nowrap;
                        overflow: hidden;
                    }
                    .scout {
                        float: left; 
                        border: 1px solid #ddd; 
                        text-align: center; 
                        margin: 1em 1em 0 0; 
                        padding: 0.5em;
                    }
                    .parent {
                        border-top: 1px solid #ddd; 
                        padding-top: 0.5em; 
                        margin-top: 0.5em; 
                        width: 150px; 
                        height: 170px
                    }
                    .parent img {
                        margin: 0 0 0.5em 0;
                    }
                </style>
            </head>
            <body>
                <h1>Fotografier</h1>
                <div id="toc">
                    Genvägar:
                    <ul>
                        <xsl:for-each select="//person[not(@left) and parent]">
                            <xsl:sort select="@name" />
                            <li>
                                <a href="#{@name}"><xsl:value-of select="@name" /></a>                            
                            </li>
                        </xsl:for-each>
                    </ul>
                </div>
                <xsl:apply-templates select="//person[not(@left) and parent]">
                    <xsl:sort select="@name" />
                </xsl:apply-templates>
            </body>
        </html>
    </xsl:template>
    <xsl:template match="person">
        <div class="scout">
            <a name="{@name}" />
            <strong><xsl:value-of select="@name" /></strong>
            <xsl:for-each select="parent">
                <div class="parent">
                <xsl:if test="photo">
                    <img src="photos/{photo/@url}"/>
                    <br />
                </xsl:if>
                <xsl:value-of select="@name" />
                </div>
            </xsl:for-each>
        </div>
    </xsl:template>
</xsl:stylesheet>