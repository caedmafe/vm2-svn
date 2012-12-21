<?php
defined( '_VALID_MOS' ) or die( 'Restricted Access.' );
global $mainframe;
require_once( dirname(__FILE__) . '../../../libraries/classes/jce.utils.class.php' );
$title = mosGetParam( $_REQUEST, 'a', '' );
?>
<!DOCTYPE html public "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
	<title><?php echo $title; ?></title>
	<style type="text/css">
    body {
          font-family: Verdana, Helvetica, Arial, Sans-Serif;
          font: message-box;
        }
    </style>
    <script language="Javascript">
	<!--
	// http://www.xs4all.nl/~ppk/js/winprop.html
	function CrossBrowserResizeInnerWindowTo(newWidth, newHeight) {
		if (self.innerWidth) {
			frameWidth  = self.innerWidth;
			frameHeight = self.innerHeight;
		} else if (document.documentElement && document.documentElement.clientWidth) {
			frameWidth  = document.documentElement.clientWidth;
			frameHeight = document.documentElement.clientHeight;
		} else if (document.body) {
			frameWidth  = document.body.clientWidth;
			frameHeight = document.body.clientHeight;
		} else {
			return false;
		}
		if (document.layers) {
			newWidth  -= (parent.outerWidth - parent.innerWidth);
			newHeight -= (parent.outerHeight - parent.innerHeight);
		}
		// original code
		//parent.window.resizeTo(newWidth, newHeight);

		// fixed code: James Heinrich, 20 Feb 2004
		parent.window.resizeBy(newWidth - frameWidth, newHeight - frameHeight);
		
		x = parseInt(screen.width / 2.0) - (newWidth / 2.0);
        y = parseInt(screen.height / 2.0) - (newHeight / 2.0);
        parent.window.moveTo(x, y);

		return true;
	}
	// -->
	</script>
</head>
<body style="margin: 0px;">
<?php
$src = mosGetParam( $_REQUEST, 'img', '' );
$path = JPath::makePath( $mainframe->getCfg('absolute_path'), $src );
$url = JPath::makePath( $mainframe->getCfg('live_site'), $src );
$dim = @getimagesize( $path );
$w = $dim[0];
$h = $dim[1];

	echo '<script type="text/javascript">'."\n";
	echo 'CrossBrowserResizeInnerWindowTo('.$w.', '.$h.');'."\n";
	echo 'document.writeln(\'<div align="center" style="vertical-align:middle;"><img onclick="window.close();" src="'.$url.'" title="'.$title.'" alt="'.$title.'" width="'.$w.'" height="'.$h.'" border="0" style="cursor: pointer;" /></div>\');';
	echo '</script>';
?>
</body>
</html>
