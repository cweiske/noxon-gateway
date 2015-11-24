<?xml version="1.0" encoding="utf-8"?>
<xsl:stylesheet version="1.0"
  xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
  xmlns="http://www.w3.org/1999/xhtml">
  <xsl:template match="ListOfItems">
    <html>
      <body>
       <xsl:for-each select="Item[ItemType='Previous']">
        <pre>
         <a>
          <xsl:attribute name="href">
           <xsl:value-of select="UrlPrevious"/>
          </xsl:attribute>
          <xsl:text>&lt; back</xsl:text>
         </a>
        </pre>
       </xsl:for-each>

       <xsl:if test="Item[ItemType='Message']">
        <h2>Messages</h2>
        <xsl:for-each select="Item[ItemType='Message']">
         <p>
          <xsl:value-of select="Message"/>
         </p>
        </xsl:for-each>
       </xsl:if>

       <pre style="border: 1px solid black">

        <xsl:for-each select="Item">
         <xsl:if test="ItemType='Display'">
          <xsl:text>[d] </xsl:text>
          <xsl:value-of select="Display"/><xsl:text>
</xsl:text>
         </xsl:if>

         <xsl:if test="ItemType='Dir'">
          <xsl:text>[D] </xsl:text>
          <a>
           <xsl:attribute name="href">
            <xsl:value-of select="UrlDir"/>
           </xsl:attribute>
           <xsl:value-of select="Title"/>
          </a>
          <xsl:text>
</xsl:text>
         </xsl:if>

         <xsl:if test="ItemType='ShowOnDemand'">
          <xsl:text>[S] </xsl:text>
           <a>
            <xsl:attribute name="href">
             <xsl:value-of select="ShowOnDemandURL"/>
            </xsl:attribute>
            <xsl:value-of select="ShowOnDemandName"/>
           </a>
           <xsl:text>
</xsl:text>
         </xsl:if>

         <xsl:if test="ItemType='ShowEpisode'">
          <xsl:text>[E] </xsl:text>
          <a>
           <xsl:attribute name="href">
            <xsl:value-of select="ShowEpisodeURL"/>
           </xsl:attribute>
           <xsl:value-of select="ShowEpisodeName"/>
          </a>
           <xsl:text>
</xsl:text>
         </xsl:if>

        </xsl:for-each>
       </pre>

       <xsl:if test="Item[ItemType='ShowEpisode']">
        <h2>Show episodes</h2>
        <xsl:for-each select="Item[ItemType='ShowEpisode']">
         <h3>
          <a>
           <xsl:attribute name="href">
            <xsl:value-of select="ShowEpisodeURL"/>
           </xsl:attribute>
           <xsl:value-of select="ShowEpisodeName"/>
          </a>
         </h3>
         <p>
          <xsl:value-of select="ShowDesc"/>
          (<xsl:value-of select="ShowMime"/>)
         </p>
        </xsl:for-each>
       </xsl:if>

       <xsl:if test="Item[ItemType='Station']">
        <h2>Internet radio stations</h2>
        <xsl:for-each select="Item[ItemType='Station']">
         <h3>
          #<xsl:value-of select="StationId"/>:
          <a>
           <xsl:attribute name="href">
            <xsl:value-of select="StationUrl"/>
           </xsl:attribute>
           <xsl:value-of select="StationName"/>
          </a>
         </h3>
         <p>
          <xsl:value-of select="StationDesc"/>
          (<xsl:value-of select="ShowMime"/>)
         </p>
        </xsl:for-each>
       </xsl:if>

      </body>
    </html>
  </xsl:template>
</xsl:stylesheet>
