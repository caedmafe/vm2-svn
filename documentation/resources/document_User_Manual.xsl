 <?xml version="1.0" encoding="iso-8859-1"?>
 <xsl:stylesheet xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                version="1.0"
                xmlns="http://www.w3.org/TR/xhtml1/transitional"
>
<!-- add a custom header to every html page                                  -->
<!-- the header will be formatted by the css class "customheader"            -->
<!-- that is defined in the e-novative.css stylsheet                         -->
<!-- applies to: html output only                                            -->
<!-- You can modify the html code between the xsl:template tags or just      -->
<!-- comment the whole <xsl:template ... </xsl:template> block out           -->
<!-- to make the header disappear                                            -->

<xsl:template name="user.header.content">
<div id="customheader">
<img src="images/cart_tn.png" border="0" alt="cart" align="center"/> VirtueMart
</div>
</xsl:template>


<!-- add a custom footer to every html page                                  -->
<!-- the footer will be formatted by the css class "customfooter"            -->
<!-- that is defined in the e-novative.css stylsheet                         -->
<!-- applies to: html output only                                            -->
<!-- You can modify the html code between the xsl:template tags or just      -->
<!-- comment the whole <xsl:template ... </xsl:template> block out.          -->
<!-- to make the footer disappear                                            -->

<xsl:template name="user.footer.content">
<div id="customfooter">
<img src="images/cart_tn.png" border="0" alt="cart" align="center"/> VirtueMart
</div>
</xsl:template>