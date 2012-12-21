<?php
/**
* @version $Id$
* @package JCE
* @copyright Copyright (C) 2005 Ryan Demmer. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* JCE is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
*/
defined( '_VALID_MOS' ) or die( 'Restricted Access.' );

$version = "1.1.2";
error_reporting(E_ALL ^ E_NOTICE );

require_once( $mainframe->getCfg('absolute_path') . '/mambots/editors/jce/jscripts/tiny_mce/libraries/classes/jce.class.php' );
require_once( $mainframe->getCfg('absolute_path') . '/mambots/editors/jce/jscripts/tiny_mce/libraries/classes/jce.utils.class.php' );

if( file_exists( $mainframe->getCfg('absolute_path') . '/components/com_phpshop/phpshop_parser.php' )) {
	require_once( $mainframe->getCfg('absolute_path') . '/components/com_phpshop/phpshop_parser.php' );
	$shoplang = new phpShopLanguage();
} elseif( file_exists( $mainframe->getCfg('absolute_path') . '/components/com_virtuemart/virtuemart_parser.php' )) {
	require_once($mainframe->getCfg('absolute_path') . '/components/com_virtuemart/virtuemart_parser.php' );
	$shoplang = new vmLanguage();
}

$jce = new JCE();
$jce->setPlugin('productsnap');

require_once( $jce->getPluginPath() . '/classes/manager.class.php' );
//Setup languages
include_once( $jce->getLibPath() . '/langs/' . $jce->getLanguage() . '.php' );
include_once(  $jce->getPluginPath() . '/langs/' .$jce->getPluginLanguage() . '.php' );

//Load Plugin Parameters
$params = $jce->getPluginParams();

$base_dir = $jce->getBaseDir( true );
$base_url = $base_dir;

$manager = new productSnapshotManager( $base_dir, $base_url );

$jce->setAjax( array( 'getProperties', &$manager, 'getProperties' ) );
$jce->setAjax( array( 'getDimensions', &$manager, 'getDimensions' ) );

$jce->processAjax();

$def_align 	= $params->get( 'align', 'left' );
$def_border = $params->get( 'border', '0' );
$def_hspace = $params->get( 'hspace', '5' );
$def_vspace = $params->get( 'vspace', '5' );
$iso = explode('=', _ISO );

echo "<?xml version=\"1.0\" encoding=\"{$iso['1']}\"?>";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $jce->translate('iso');?>" />
	<title><?php echo $jce->translate('desc').' : '.$version;?></title>
	<?php
	echo $jce->printLibJs( 'tiny_mce_utils' );
	echo $jce->printLibJs( 'mootools' );
	echo $jce->printLibJs( 'utils' );
	echo $jce->printLibJs( 'window' );
	echo $jce->printLibJs( 'manager' );
	echo $jce->printPluginJs( 'products' );
	echo $jce->printPluginJs( 'manager' );
	echo $jce->printTinyJs( 'utils/form_utils' );
	echo $jce->printTinyJs( 'utils/mctabs' );
	echo $jce->printPLuginJs( 'selectableelements' );
	echo $jce->printLibJs( 'dtree' ); 
	echo $jce->printLibCss( 'common', true );
	echo $jce->printPluginCss( 'manager' );
	echo $jce->printLibCss( 'dtree' );
	?>
	<script type="text/javascript">
		jce.setPlugin('productsnap');
		jce.set("base_url", "<?php echo $base_url; ?>");
		jce.set("align", "<?php echo $params->get( 'align', 'left' );?>");
		jce.set("border", "<?php echo $params->get( 'border', '0' );?>");
		jce.set("border_width", "<?php echo $params->get( 'border_width', '1' );?>");
		jce.set("border_style", "<?php echo $params->get( 'border_style', 'solid' );?>");
		jce.set("border_color", "<?php echo $params->get( 'border_color', '#000000' );?>");
		jce.set("hspace", "<?php echo $params->get( 'hspace', '5' );?>");
		jce.set("vspace", "<?php echo $params->get( 'vspace', '5' );?>");
		var help_lang 	= "<?php echo $jce->getPluginLanguage(); ?>";
		var pasteAction = '';
		var source_dir 	= '';
		jce.set("def_align", "<?php echo $def_align;?>" );
		jce.set("def_border","<?php echo $def_border;?>");
		jce.set("def_hspace", "<?php echo $def_hspace;?>");
		jce.set("def_vspace", "<?php echo $def_vspace;?>");
		var d = new dTree('d', jce.getLibUrl());
	</script>
</head>
<body id="productsnap" onLoad="tinyMCEPopup.executeOnLoad('init();');" style="display: none">
    <form action="<?php echo $jce->getPluginFile('products.php');?>" target="manager" name="uploadForm" id="uploadForm" method="post" enctype="multipart/form-data">
    <input type="hidden" name="itemsList" id="itemsList" />
    <input type="hidden" name="width" id="width" />
    <input type="hidden" name="height" id="height" />
	<div class="tabs">
			<ul>
				<li id="general_tab" class="current"><span><a href="javascript:mcTabs.displayTab('general_tab','general_panel');" onMouseDown="return false;"><?php echo $jce->translate('article_image');?></a></span></li>
				<li id="swap_tab"><span><a href="javascript:mcTabs.displayTab('swap_tab','swap_panel');" onMouseDown="return false;"><?php echo $jce->translate('swap_image');?></a></span></li>
                <li id="advanced_tab"><span><a href="javascript:mcTabs.displayTab('advanced_tab','advanced_panel');" onMouseDown="return false;"><?php echo $jce->translate('advanced');?></a></span></li>
			</ul>
		</div>

		<div class="panel_wrapper">
			<div id="general_panel" class="panel current" style="height: auto;max-height:250px;">
				<fieldset>
						<legend><?php echo $jce->translate('article_image');?></legend>
						
						<fieldset style="float: right;width: 250px;display:inline;"><legend><?php echo $jce->translate('preview'); ?></legend>
							<span>Lorem ipsum, Dolor sit amet, consectetuer adipiscing loreum ipsum edipiscing elit, sed diam...</span>
							<div id="previewContainer">
								<div id="nameInPreview" style="font-weight:bold;"></div>
									<img id="alignSampleImg" src="<?php echo $jce->getPluginImg('sample.gif');?>" alt="{$lang_advimage_example_img}" />
									<span id="desc">Lorem ipsum, Dolor sit amet, consectetuer adipiscing loreum ipsum edipiscing elit, sed diam....</span>
									<div id="showPricePreview" style="font-weight:bold;"><?php echo $shoplang->_PHPSHOP_CART_PRICE ?>: $x.xxx,xx</div>
									<div id="showAddToCartPreview" style="font-weight:bold;"><a href="#"><?php echo $shoplang->_PHPSHOP_CART_ADD_TO ?></a></div>
							</div>
							<span>Lorem ipsum, Dolor sit amet, consectetuer adipiscing loreum ipsum edipiscing elit, sed diam...</span>
						</fieldset>
						
						
						
							<input name="product_name" type="hidden" id="product_name" value="" />									
							<input id="title" name="title" type="hidden" value="" />
							<input id="product_id"  name="product_id" type="hidden" value="" /></p>
							<input id="category_id" name="category_id" type="hidden" value="" />
						<table align="left">
							<tr>
								<td colspan="2">
									<input type="checkbox" name="show_desc" id="show_desc" onchange="changeAppearance();" checked="checked" />
									<label id="show_desclabel" for="show_desc"><?php echo $jce->translate('show_desc');?></label>
									
									<input type="checkbox" name="show_price" id="show_price" onchange="changeAppearance();" checked="checked" />
									<label id="show_pricelabel" for="show_price"><?php echo $jce->translate('show_price');?></label>
									
									<input type="checkbox" name="show_addtocart" id="show_addtocart" onchange="changeAppearance();" checked="checked" />
									<label id="show_addtocartlabel" for="show_addtocart"><?php echo $jce->translate('show_addtocart');?></label>
								</td>
							</tr>
							<tr>
								<td>
									<label id="vspacelabel" for="vspace"><?php echo $jce->translate('vspace');?> </label> / <label id="hspacelabel" for="hspace"><?php echo $jce->translate('hspace');?></label>
								</td>
								<td>
									<input name="vspace" type="text" id="vspace" value="" size="3" maxlength="3" onchange="changeAppearance();updateStyle();" /> / 
									<input name="hspace" type="text" id="hspace" value="" size="3" maxlength="3" onchange="changeAppearance();updateStyle();" />
								</td>
							</tr>
							<tr>
								<td>
									<label id="alignlabel" for="image_align"><?php echo $jce->translate('image_align');?></label>
								</td>
								<td>
									<select name="image_align" id="image_align" onchange="changeAppearance();updateStyle();">
										<option value="default"><?php echo $jce->translate('align_default');?></option>
										<option value="top"><?php echo $jce->translate('align_top');?></option>
										<option value="middle"><?php echo $jce->translate('align_middle');?></option>
										<option value="bottom"><?php echo $jce->translate('align_bottom');?></option>
										<option value="left"><?php echo $jce->translate('align_left');?></option>
										<option value="right"><?php echo $jce->translate('align_right');?></option>
									</select>
								</td>
							</tr>
							<tr>
								<td>
									<label id="container_marginlabel" for="container_margin"><?php echo $jce->translate('container_margin');?> </label> / 
									<label id="container_paddinglabel" for="container_padding"><?php echo $jce->translate('container_padding');?> </label>
								</td>
								<td>
									<input name="container_margin" type="text" id="container_margin" value="" size="6" onchange="changeAppearance();updateStyle();" /> / 
									<input name="container_padding" type="text" id="container_padding" value="" size="6" onchange="changeAppearance();updateStyle();" />
								</td>
							</tr>
							<tr>
								<td>
									<label id="text_alignlabel" for="text_align"><?php echo $jce->translate('text_align');?></label>
								</td>
								<td>
									<select name="text_align" id="text_align" onchange="changeAppearance();updateStyle();">
										<option value="default"><?php echo $jce->translate('align_default');?></option>
										<option value="top"><?php echo $jce->translate('align_top');?></option>
										<option value="middle"><?php echo $jce->translate('align_middle');?></option>
										<option value="bottom"><?php echo $jce->translate('align_bottom');?></option>
										<option value="left"><?php echo $jce->translate('align_left');?></option>
										<option value="right"><?php echo $jce->translate('align_right');?></option>
									</select>
								</td>
							</tr>
							<tr>
								<td>
									<label id="container_widthlabel" for="container_width"><?php echo $jce->translate('container_width');?> </label>
								</td>
								<td>
									<input name="container_width" type="text" id="container_width" value="" size="6" onchange="changeAppearance();updateStyle();" />
								</td>
							</tr>
							<tr>
								<td>
									<label id="containeralignlabel" for="container_align"><?php echo $jce->translate('container_align');?></label>
								</td>
								<td>							
									<select name="container_align" id="container_align" onchange="changeAppearance();updateStyle();">
										<option value="none"><?php echo $jce->translate('align_default');?></option>
										<option value="left"><?php echo $jce->translate('align_left');?></option>
										<option value="right"><?php echo $jce->translate('align_right');?></option>
									</select>
								</td>
							</tr>
							<tr>
								<td colspan="2">
									<?php echo $jce->translate('border');?>-
								
								
								<table cellspacing="0">
								<tr>
									<td><label for="border_width"><?php echo $jce->translate('border_width');?>:</label></td>
									<td>
									<select id="border_width" name="border_width" onchange="changeAppearance();updateStyle();">
										<option value="0">0</option>
										<option value="1" selected="selected">1</option>
										<option value="2">2</option>
										<option value="3">3</option>
										<option value="4">4</option>
										<option value="5">5</option>
										<option value="6">6</option>
										<option value="7">7</option>
										<option value="8">8</option>
										<option value="9">9</option>
									</select>
									</td>
									<td><label for="border_style"><?php echo $jce->translate('border_style');?>:</label></td>
									<td>
										<select id="border_style" name="border_style" onchange="changeAppearance();updateStyle();">
											<option value="none">none</option>
											<option value="solid" selected="selected">solid</option>
											<option value="dashed">dashed</option>
											<option value="dotted">dotted</option>
											<option value="double">double</option>
											<option value="groove">groove</option>
											<option value="inset">inset</option>
											<option value="outset">outset</option>
											<option value="ridge">ridge</option>
										</select>
									</td>
									<td>
										<label for="border_color"><?php echo $jce->translate('border_color');?>:</label>
										<input name="border_color" value="<?php echo $params->get( 'border_color', '#000000' );?>" id="border_color" type="hidden" onchange="updateColor('border_color_pick','border_color');changeAppearance();updateStyle();" />
									</td>
									<td id="border_color_pickcontainer">&nbsp;</td>
								</tr>
							</table>
								</td>
							</tr>
						</table>
						
				</fieldset>
			</div>
            <div id="swap_panel" class="panel">
				<fieldset>
					<legend><?php echo $jce->translate('swap_image');?></legend>

					<input type="checkbox" id="onmousemovecheck" name="onmousemovecheck" class="checkbox" onclick="changeMouseMove();" />
					<label id="onmousemovechecklabel" for="onmousemovecheck"><?php echo $jce->translate('swap_image');?></label>

					<table border="0" cellpadding="4" cellspacing="0" width="100%">
							<tr>
								<td class="column1"><label id="onmouseoversrclabel" for="onmouseoversrc"><?php echo $jce->translate('mouseover');?></label></td>
								<td><table border="0" cellspacing="0" cellpadding="0">
									<tr>
									  <td><input id="onmouseoversrc" class="large_input" name="onmouseoversrc" type="text" value="" /></td>
									</tr>
								  </table></td>
							</tr>
							<tr>
								<td class="column1"><label id="onmouseoutsrclabel" for="onmouseoutsrc"><?php echo $jce->translate('mouseout');?></label></td>
								<td class="column2"><table border="0" cellspacing="0" cellpadding="0">
									<tr>
									  <td><input id="onmouseoutsrc" class="large_input" name="onmouseoutsrc" type="text" value="" /></td>
									</tr>
								  </table></td>
							</tr>
					</table>
				</fieldset>
            </div>
			<div id="advanced_panel" class="panel">
				<fieldset>
					<legend><?php echo $jce->translate('advanced');?></legend>

					<table border="0" cellpadding="4" cellspacing="0">
						<tr>
							<td class="column1"><label id="stylelabel" for="style"><?php echo $jce->translate('styles');?></label></td>
							<td><input id="style" name="style" class="large_input" type="text" value="" /></td>
						</tr>
						<tr>
							<td><label id="classlabel" for="classlist"><?php echo $jce->translate('class_list');?></label></td>
	                        <td><select id="classlist" name="classlist">
									<option value="" selected><?php echo $jce->translate('not_set');?></option>
								 </select>
						    </td>
						</tr>
						<tr>
							<td class="column1"><label id="idlabel" for="id"><?php echo $jce->translate('id');?></label></td>
							<td><input id="id" name="id" type="text" value="" /></td>
						</tr>

						<tr>
							<td class="column1"><label id="dirlabel" for="dir"><?php echo $jce->translate('lang_dir');?></label></td>
							<td>
								<select id="dir" name="dir" onChange="changeAppearance();">
										<option value=""><?php echo $jce->translate('not_set');?></option>
										<option value="ltr"><?php echo $jce->translate('ltr');?></option>
										<option value="rtl"><?php echo $jce->translate('rtl');?></option>
								</select>
							</td>
						</tr>

						<tr>
							<td class="column1"><label id="langlabel" for="lang"><?php echo $jce->translate('lang_code');?></label></td>
							<td>
								<input id="lang" name="lang" type="text" value="" />
							</td>
						</tr>

						<tr>
							<td class="column1"><label id="usemaplabel" for="usemap"><?php echo $jce->translate('image_map');?></label></td>
							<td>
								<input id="usemap" name="usemap" type="text" value="" />
							</td>
						</tr>

						<tr>
							<td class="column1"><label id="longdesclabel" for="longdesc"><?php echo $jce->translate('long_desc');?></label></td>
							<td><table border="0" cellspacing="1" cellpadding="0">
									<tr>
									  <td><input id="longdesc" name="longdesc" type="text" value="" /></td>
									</tr>
								</table></td>
						</tr>
					</table>
				</fieldset>
			</div>
		</div>
	<fieldset>
	<legend><?php echo $jce->translate('browse');?></legend>
    <table class="properties" cellpadding="0" cellspacing="0">
		<tr>
			<td colspan="5" style="vertical-align:top">
				<div id="msgIcon">
        			<img id="imgMsgContainer" src="<?php echo  $jce->getLibImg('spacer.gif');?>" width="16" height="16" border="0" alt="Message" title="Message" />
   				 </div>
    			<div id="msgDiv">
        			<span id="msgContainer" style="vertical-align:top;"></span>
    			</div>
			</td>
		</tr>
		<tr>
			<td colspan="5" style="vertical-align:top">
				 <div id="dirListBlock">
        			<label for="dirlistcontainer" style="vertical-align:middle;"><?php echo $jce->translate('cat');?></label>&nbsp;
        			<div id="dirlistcontainer" style="vertical-align:middle;"></div>
    			</div>
    			<div id="dirImg" style="display: inline;"><a href="javascript:void(0)" onclick="javascript: goUpCat();" title="<?php echo $jce->translate('dir_up');?>" class="toolbar"><img src="<?php echo $jce->getLibImg('dir_up.gif');?>" width="20" height="20" border="0" alt="<?php echo $jce->translate('dir_up');?>" /></a></div>

				<div id="hlpIcon" style="display: inline;"><a href="javascript:void(0)" onclick="javascript: openHelp('productsnap');" class="toolbar"><img src="<?php echo  $jce->getLibImg('help.gif');?>" border="0" alt="<?php echo $jce->translate('help');?>" width="20" height="20" title="<?php echo $jce->translate('help');?>" /></a></div>
			</td>
		</tr>
		<tr>
			<td style="vertical-align:top"><div id="spacerDiv"></div></td>
			<td style="vertical-align:top"><?php echo $jce->sortName();?></td>
			<td colspan="2" style="vertical-align:top"><?php 
				echo "<div id=\"searchDiv\">";
				echo "<div id=\"searchValueLabel\"><img src=\"" . $jce->getLibImg('search.gif') . "\" width=\"16\" height=\"16\" alt=\"" . $jce->translate('search') . "\" title=\"" . $jce->translate('search') . "\" style=\"vertical-align:middle;\" /></div>";
				echo "<input type=\"text\" id=\"searchValue\" style=\"width: 170px;\" onkeyup=\"searchProduct(this.value);\" />";
				echo "</div>";
				?></td>
		</tr>
		<tr>
			<td style="vertical-align:top">
				<div id="treeBlock">
					<div id="treeTitle">
						<?php echo $jce->translate('categories');?>
					</div>
					<div id="treeDetails"></div>
				</div>
			</td>
			<td colspan="2" style="vertical-align:top"><div id="fileContainer"></div></td>
			<td style="vertical-align:top">
				<div id="infoBlock">
					<div id="infoTitle">
						<?php echo $jce->translate('details');?>
					</div>
					<div id="fileDetails"></div>
				</div>
			</td>
			<td style="vertical-align:top">
				<div id="toolsList">
				</div>
			</td>
		</tr>
	</table>
    </fieldset>
	<div class="mceActionPanel">
		<div style="float: right;">
    		<input type="button" class="button" id="refresh" name="refresh" value="<?php echo $jce->translate('refresh');?>" onclick="refreshAction();" />
			<input type="button" id="insert" name="insert" value="{$lang_insert}" onclick="insertAction();" />
			<input type="button" id="cancel" name="cancel" value="<?php echo $jce->translate('cancel');?>" onclick="cancelAction();" />
		</div>
	</div>
    </form>
</body> 
</html>
