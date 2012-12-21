<?php
defined('_JEXEC') or 	die( 'Direct Access to ' . basename( __FILE__ ) . ' is not allowed.' ) ;
/**

 * @author Max Milbers
 * @version $Id:$
 * @package VirtueMart
 * @subpackage payment
 * @copyright Copyright (C) 2004-2008 soeren - All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.org
 */

if (!class_exists('vmCustomPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmcustomplugin.php');

class plgVmCustomTextinput extends vmCustomPlugin {

	function __construct(& $subject, $config) {

		parent::__construct($subject, $config);

		$varsToPush = array(	'custom_size'=>array(0.0,'int'),
						    	'custom_price_by_letter'=>array(0.0,'bool')
		);

		$this->setConfigParameterable('customfield_param',$varsToPush);

	}

	// get product param for this plugin on edit
	function plgVmOnProductEdit($field, $product_id, &$row,&$retValue) {
		if ($field->custom_element != $this->_name) return '';
		$html ='
			<fieldset>
				<legend>'. JText::_('VMCUSTOM_TEXTINPUT') .'</legend>
				<table class="admintable">
					'.VmHTML::row('input','VMCUSTOM_TEXTINPUT_SIZE','custom_param['.$row.'][custom_size]',$field->custom_size);
		$options = array(0=>'VMCUSTOM_TEXTINPUT_PRICE_BY_INPUT',1=>'VMCUSTOM_TEXTINPUT_PRICE_BY_LETTER');
		$html .= VmHTML::row('select','VMCUSTOM_TEXTINPUT_PRICE_BY_LETTER_OR_INPUT','custom_param['.$row.'][custom_price_by_letter]',$options,$field->custom_price_by_letter,'','value','text',false);

		//$html .= ($field->custom_price_by_letter==1)?JText::_('VMCUSTOM_TEXTINPUT_PRICE_BY_LETTER'):JText::_('VMCUSTOM_TEXTINPUT_PRICE_BY_INPUT');
			$html .='</td>
		</tr>
				</table>
			</fieldset>';
		$retValue .= $html;
		$row++;
		return true ;
	}

	function plgVmOnDisplayProductFE(&$product,&$group) {
		if ($group->custom_element != $this->_name) return '';
		$group->display .= $this->renderByLayout('default',array(&$product,&$group) );
		return true;
	}

	/**
	 * @see components/com_virtuemart/helpers/vmCustomPlugin::plgVmOnViewCartModule()
	 * @author Patrick Kohl
	 */
	function plgVmOnViewCartModule( $product,$row,&$html) {

		return $this->plgVmOnViewCart($product,$row,$html);
    }

	/**
	 * @see components/com_virtuemart/helpers/vmCustomPlugin::plgVmOnViewCart()
	 * @author Max Milbers
	 */
	function plgVmOnViewCart($product,$row,&$html) {

		if (empty($product->productCustom->custom_element) or $product->productCustom->custom_element != $this->_name) return '';

		foreach($product->customProductData[$product->productCustom->virtuemart_custom_id] as $name => $value){
		//	vmdebug('plgVmOnViewCart ',$name,$value);
			$html .='<span>'.JText::_($product->productCustom->custom_title).' '.$value['comment'].'</span>';
		}

		return true;
    }


	/**
	 *
	 * vendor order display BE
	 */
	function plgVmDisplayInOrderBE($item, $row, &$html) {
		if(!empty($productCustom)){
			$item->productCustom = $productCustom;
		}
		if (empty($item->productCustom->custom_element) or $item->productCustom->custom_element != $this->_name) return '';
		$this->plgVmOnViewCart($item,$row,$html); //same render as cart
    }

	/**
	 *
	 * shopper order display FE
	 */
	function plgVmDisplayInOrderFE($item, $row, &$html) {

		if (empty($item->productCustom->custom_element) or $item->productCustom->custom_element != $this->_name) return '';
		$this->plgVmOnViewCart($item,$row,$html); //same render as cart
    }

	/**
	 * We must reimplement this triggers for joomla 1.7
	 * vmplugin triggers note by Max Milbers
	 */
	public function plgVmOnStoreInstallPluginTable($psType) {
		//Should the textinput use an own internal variable or store it in the params?
		//Here is no getVmPluginCreateTableSQL defined
// 		return $this->onStoreInstallPluginTable($psType);
	}


	function plgVmDeclarePluginParamsCustom(&$data){
		return $this->declarePluginParams('custom',$data->custom_element, $data->custom_jplugin_id, $data);
	}

/*	function plgVmDeclarePluginParamsCustomfield(&$data){
		return $this->declarePluginParams('custom',$data->custom_element, $data->custom_jplugin_id, $data);
	}*/

	function plgVmGetTablePluginParams($psType, $name, $id, &$xParams, &$varsToPush){
		return $this->getTablePluginParams($psType, $name, $id, $xParams, $varsToPush);
	}
	function plgVmSetOnTablePluginParamsCustom($name, $id, &$table,$xParams){
		return $this->setOnTablePluginParams($name, $id, $table,$xParams);
	}

	/**
	 * Custom triggers note by Max Milbers
	 */
	function plgVmOnDisplayEdit($virtuemart_custom_id,&$customPlugin){
		return $this->onDisplayEditBECustom($virtuemart_custom_id,$customPlugin);
	}

	public function plgVmCalculateCustomVariant(&$product, &$productCustomsPrice,$variantValues){
		if ($productCustomsPrice->custom_element !==$this->_name) return ;

		//VmConfig::$echoDebug= 1;
		//vmdebug('plgVmCalculateCustomVariant textinput',$variantValues);
		if (!empty($productCustomsPrice->customfield_price)) {
			//TODO adding % and more We should use here $this->interpreteMathOp
			// eg. to calculate the price * comment text length

			//$selected = array_keys($variantValues);
			//$customVariant = $variantValues[$selected[0]];
			if (!empty($variantValues['comment'])) {
				if ($productCustomsPrice->custom_price_by_letter ==1) {
					$charcount = strlen ($variantValues['comment']);
				} else {
					$charcount = 1.0;
				}
				$productCustomsPrice->customfield_price = $charcount * $productCustomsPrice->customfield_price ;
			} else {
				$productCustomsPrice->customfield_price = 0.0;
			}

		}
		return true;
	}

	public function plgVmDisplayInOrderCustom(&$html,$item, $param,$productCustom, $row ,$view='FE'){
		$this->plgVmDisplayInOrderCustom($html,$item, $param,$productCustom, $row ,$view);
	}

	public function plgVmCreateOrderLinesCustom(&$html,$item,$productCustom, $row ){
// 		$this->createOrderLinesCustom($html,$item,$productCustom, $row );
	}
	function plgVmOnSelfCallFE($type,$name,&$render) {
		$render->html = '';
	}

}

// No closing tag