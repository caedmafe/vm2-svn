<?php
	defined('_JEXEC') or die();
	$class='vmcustom-textinput';

	//if ($field->custom_price_by_letter) $class='vmcustom-textinput';
$product = $viewData[0];
$params = $viewData[1];
?>

	<input class="<?php echo $class ?>"
	       type="text" value=""
	       size="<?php echo $params->custom_size ?>"
	       name="customProductData[<?php echo $product->virtuemart_product_id ?>][<?php echo $params->virtuemart_custom_id ?>][<?php echo $params->virtuemart_customfield_id ?>][comment]"><br />
<?php
	// preventing 2 x load javascript
	static $textinputjs;
	if ($textinputjs) return true;
	$textinputjs = true ;
	//javascript to update price
	$document = JFactory::getDocument();
	$document->addScriptDeclaration('
jQuery(document).ready( function($) {
	jQuery(".vmcustom-textinput").keyup(function() {
			formProduct = $(this).parents("form.product");
			virtuemart_product_id = formProduct.find(\'input[name="virtuemart_product_id[]"]\').val();
		Virtuemart.setproducttype(formProduct,virtuemart_product_id);
		});

});
	');