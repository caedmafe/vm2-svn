<?php
/**
 * Show a list of images and folders.
 * @author $Author: soeren_nb $
 * @version $Id$
 * @package ImageManager
 */
defined( '_VALID_MOS' ) or die( 'Restricted Access.' );

$version = "1.1.2";
error_reporting( E_ALL ^ E_NOTICE );

require_once( $mainframe->getCfg('absolute_path') . '/mambots/editors/jce/jscripts/tiny_mce/libraries/classes/jce.class.php' );
require_once( $mainframe->getCfg('absolute_path') . '/mambots/editors/jce/jscripts/tiny_mce/libraries/classes/jce.utils.class.php' );

if( file_exists( $mainframe->getCfg('absolute_path') . '/components/com_phpshop/phpshop_parser.php' )) {
	require_once( $mainframe->getCfg('absolute_path') . '/components/com_phpshop/phpshop_parser.php' );
} elseif( file_exists( $mainframe->getCfg('absolute_path') . '/components/com_virtuemart/virtuemart_parser.php' )) {
	require_once($mainframe->getCfg('absolute_path') . '/components/com_virtuemart/virtuemart_parser.php' );
}
if( !$perm->check('admin,storeadmin')) {
	return false;
}
$prefix = class_exists('vmlanguage') ? '{vm}' : 'pshop';

$jce = new JCE();
$jce->setPlugin('productsnap');

require_once( $jce->getPluginPath() . '/classes/manager.class.php' );
//Setup languages
include_once( $jce->getLibPath() . '/langs/' . $jce->getLanguage() . '.php' );
include_once(  $jce->getPluginPath() . '/langs/' . $jce->getPluginLanguage() . '.php' );

//Load Plugin Parameters
$params = $jce->getPluginParams();

/*Get variables
 *param $curr_category The relative path passed
 *param $ret_file The relative file returned from the editor
*/
$curr_category = mosGetParam( $_REQUEST, 'category', mosGetParam( $_COOKIE, 'jce_productsnap_category', '') );
$ret_product = (int)mosGetParam( $_REQUEST, 'ret_product', 0 );
if( $ret_product > 0 ) {
	$db = new ps_DB();
	$db->query('SELECT c.category_id FROM #__'.$prefix.'_category c, #__'.$prefix.'_product_category_xref cx
					WHERE c.category_id=cx.category_id
					AND cx.product_id='.$ret_product );
	$db->next_record();
	$curr_category = $db->f('category_id');
}
//End User Directory Restrictions
$manager = new productSnapshotManager( IMAGEPATH, IMAGEURL );
/*
//If a returned file exists, create the path to the current dir
if( $ret_file ){
    $ret_file = JPath::makePath( $curr_category, $ret_file );
}*/

//Upload action

//End File Actions
$product_list = $manager->getProducts( $curr_category );
$category_list = $manager->getCategories( $curr_category );

/**
 * Draw the products.
 */
function drawProducts( $product_list, &$manager )
{
        global $jce;
        $f = 0;
		foreach( $product_list as $product ) {
                               
	        $id = intval( $product['id'] );
	        $name = $product['name'];
			
	        $shortname = $product['short_name'];
			$icon = $GLOBALS['mosConfig_live_site'].'/mambots/editors/jce/jscripts/tiny_mce/plugins/productsnap/images/package.png'
		?>
		<div class="divList" id="<?php echo "p".$id; ?>" title="<?php echo $name;?>">
			<img src="<?php echo $icon;?>" alt="Placeholder" height="16" width="16" style="vertical-align:middle;" />
			<a href="javascript:selectProduct('p<?php echo $id; ?>');" title="<?php echo $name; ?>"><?php echo $shortname ?></a>
		</div>
		<?php
		$f++;
        }//foreach
}//function drawproducts

/**
 * Draw the directory.
 */
function drawCategories( $category_list, &$manager )
{
        global $jce, $cl;
        $d = 0;
		foreach($category_list as $cat) {
            $fullpath = 'fullpath';
			$id = "d".$d;
          ?>
                <div class="divList" id="<?php echo $id;?>" title="<?php echo $cat['name'];?>">
					<img src="<?php echo $jce->getLibImg('folder.gif');?>" title="<?php echo $cl['folder']; ?>" alt="<?php echo $cl['folder'];?>" height="20" width="20" style="vertical-align:middle;" />
                    <a href="javascript:void(0);" onclick="parent.changeCat('<?php echo $cat['id'];?>');return false;" title="<?php echo $cat['name']; ?>"><?php echo $cat['name']; ?></a>
                </div>
          <?php
		  $d++;
        } //foreach
}//function drawDirs

/**
 * No categories and no products.
 */
function noProducts() {
    global $jce;
	echo '<div class="noResult">'. $jce->translate('no_products'). '</div>';
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>Product List</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<?php 
	echo $jce->printLibJs( 'tiny_mce_utils' );
	echo $jce->printLibJs( 'mootools' );
	echo $jce->printLibJs( 'utils' );
	echo $jce->printLibJs( 'window' );
	echo $jce->printLibJs( 'manager' );
	echo $jce->printTinyJs( 'utils/form_utils' );
	echo $jce->printPluginJs( 'products' );
	echo $jce->printPluginJs( 'selectableelements' );
	echo $jce->printLibCss( 'common' );
	echo $jce->printPluginCss( 'products' );
?>
<script type="text/javascript">
//<![CDATA[
		var lib_url 	= "<?php echo $jce->getLibUrl();?>";
		var plugin_url 	= "<?php echo $jce->getPluginUrl();?>";
		var base_url = "<?php echo IMAGEURL; ?>";
        function init() {
        	var p = parent.document;
			var txt = "<?php echo $jce->translate('select_text1');?>";
        	parent.showMessage( txt, 'info.gif', 'msg' );
            <?php
                $categories = $manager->getCategories();
            ?>
                p.getElementById('treeDetails').innerHTML = '';
			    var html = '';
        		html += '<select class="dirWidth" id="dirPath" name="dirPath" onchange="updateCat(this)">';
        		html += '<option value="">/</option>';
        		<?php foreach( $categories as $cat )
    		        { 
					$sel = ( $curr_category == $cat['id']) ? ' selected="selected"' : '';
                    ?>
            		html += '<option value="<?php echo $cat['id'];?>"<?php echo $sel;?>><?php echo $cat['formatted_name'];?></option>';
            		<?php }?>
        		html += '</select>';
        		p.getElementById('dirlistcontainer').innerHTML = html;
				parent.d.add( 'i0', -1, '<?php echo $cl['tree_root'];?>', 'javascript:changeCat(\'\');');	
				<?php echo $manager->categoryTree();?> 				
				p.getElementById('treeDetails').innerHTML = parent.d;
				
				var curr_dir = "<?php echo $curr_category;?>";
				if(curr_dir == '' || curr_dir == '/'){
					parent.d.closeAll();
				}else{
					var divs = p.getElementsByTagName('div');
					for(var i=0; i<divs.length; i++){
						if(divs[i].className == 'dTreeNode'){
							var children = divs[i].childNodes;
							for(var x=0; x<children.length; x++){
								if(children[x].title == curr_dir && curr_dir != ''){
									parent.d.closeAll();
									var lvl = parseInt(children[x].id.replace(/[^0-9]/g, ''));
									parent.d.openTo(lvl, true);
								}
							}
						}
					}
				}	
            <?php
            if( !empty($error) ){?>
                var error = "<?php echo $error;?>";
                showMessage( p, error, 'error.gif', 'error' );
            <?php }
            if( $ret_product ){?>
                setReturnProduct( '<?php echo $ret_product;?>' );
            <?php }?>
        }
//]]>
</script>
</head>

<body style="background-color:#FFFFFF" onload="init();">
<div id="categoryList" onselectstart="return false" class="div-list">
        <?php //if( count( $category_list ) > 0 ) drawCategories( $category_list, $manager ); ?>
</div>
<?php
if( count( $product_list ) > 0 ) {
	?>
	<div id="productList" onselectstart="return false" class="div-list">
        <?php 
        drawProducts( $product_list, $manager );?>
    </div>
<?php 
}
else{
    noProducts();
}
?>
<script type="text/javascript">
var p = parent.document;
var f = p.forms[0];
if(document.getElementById("productList")){
	var sf = new SelectableElements(document.getElementById("productList"), true);
	sf.onchange = function () {
		var fitems = sf.getSelectedItems();
		var fid = [];
		for (var i=0; i< fitems.length; i++) {
			fid[i] = fitems[i].getAttribute("id").toString();
		}
		
		if(fid.length > 1){
			showNumFiles(fid);
		}
		if(fid.length == 1){
			selectProduct(fid[0] );
			showProductDetails(fid[0]);
		}
		if(fid.length == 0){
			p.getElementById('fileDetails').innerHTML = '';
		}
		if(typeof(sd) != 'undefined'){
			var ditems2 = sd.getSelectedItems();
			for (var j=0; j<ditems2.length; j++){
				sd.setItemSelectedUi(ditems2[j], false);
				sd._selectedItems = [];
			}
		}
	};
}
if(document.getElementById("categoryList")){
	var sd = new SelectableElements(document.getElementById("categoryList"), false);
	sd.onchange = function () {
		var ditems = sd.getSelectedItems();
		var did = [];
		for (var x=0; x< ditems.length; x++) {
			did[x] = ditems[x].getAttribute("id").toString();
		}
		showFolderDetails(did[0]);

		if(typeof(sf) != 'undefined'){
			var fitems2 = sf.getSelectedItems();
			for (var y=0; y<fitems2.length; y++){
				sf.setItemSelectedUi(fitems2[y], false);
				sf._selectedItems = [];
			}
		}
	};
}
</script>
</body>
</html>
