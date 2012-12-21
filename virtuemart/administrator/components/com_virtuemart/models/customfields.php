<?php
/**
 *
 * Description
 *
 * @package    VirtueMart
 * @subpackage
 * @author Max Milbers
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved by the author.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id:$
 */

// Check to ensure this file is included in Joomla!
defined ('_JEXEC') or die('Restricted access');

if (!class_exists ('VmModel')) {
	require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'vmmodel.php');
}

/**
 * Model for VirtueMart Customs Fields
 *
 * @package        VirtueMart
 */
class VirtueMartModelCustomfields extends VmModel {

	/**
	 * constructs a VmModel
	 * setMainTable defines the maintable of the model
	 *
	 * @author Max Milbers
	 */
	function __construct () {

		parent::__construct ('virtuemart_customfield_id');
		$this->setMainTable ('product_customfields');
	}

	/** @return autorized Types of data **/
	function getField_types () {

		return array('S' => 'COM_VIRTUEMART_CUSTOM_STRING',
		             'D' => 'COM_VIRTUEMART_DATE',
		             'T' => 'COM_VIRTUEMART_TIME',
		             'M' => 'COM_VIRTUEMART_IMAGE',
		             'B' => 'COM_VIRTUEMART_CUSTOM_BOOLEAN',
		             'G' => 'COM_VIRTUEMART_CUSTOM_GROUP',
		             'A' => 'COM_VIRTUEMART_CHILD_GENERIC_VARIANT',
		             'X' => 'COM_VIRTUEMART_CUSTOM_EDITOR',
		             'Y' => 'COM_VIRTUEMART_CUSTOM_TEXTAREA',
		             'E' => 'COM_VIRTUEMART_CUSTOM_EXTENSION',
		             'R'=>'COM_VIRTUEMART_RELATED_PRODUCT',
					'Z'=>'COM_VIRTUEMART_RELATED_CATEGORY'
		);

		// 'U'=>'COM_VIRTUEMART_CUSTOM_CART_USER_VARIANT',
		// 'C'=>'COM_VIRTUEMART_CUSTOM_PRODUCT_CHILD',
		// 'G'=>'COM_VIRTUEMART_CUSTOM_PRODUCT_CHILD_GROUP',
		//
	}


	/**
	 * Gets a single custom by virtuemart_customfield_id
	 *
	 * @param string $type
	 * @param string $mime mime type of custom, use for exampel image
	 * @return customobject
	 */
	function getCustomfield () {

		if(empty($this->data)){
			$this->data = $this->getTable ('product_customfields');
			$this->data->load ($this->_id);
		}

		return $this;
	}

	static function bindCustomEmbeddedFieldParams(&$obj,$fieldtype){
		//vmdebug('bindCustomEmbeddedFieldParams begin',$obj);
		$obj ->_xParams = 'custom_param';
		VirtueMartModelCustomfields::bindParameterableByFieldType($obj,$fieldtype);
		//vmdebug('bindCustomEmbeddedFieldParams middle',$obj);
		$obj ->_xParams = 'customfield_param';
		VirtueMartModelCustomfields::bindParameterableByFieldType($obj,$fieldtype);
		//vmdebug('bindCustomEmbeddedFieldParams end',$obj);
	}

	public static function getProductCustomSelectFieldList(){

		$q = 'SELECT c.`virtuemart_custom_id`, `custom_parent_id`, c.`virtuemart_vendor_id`, `custom_jplugin_id`, `custom_element`, `admin_only`, `custom_title`, `custom_tip`,
		c.`custom_value`, `custom_desc`, `field_type`, `is_list`, `is_hidden`, `is_cart_attribute`, `is_input`, `layout_pos`, `custom_param`, c.`shared`, c.`published`, c.`ordering`, ';
		$q .= 'field.`virtuemart_customfield_id`, `virtuemart_product_id`, field.`customfield_value`, field.`customfield_price`,
		field.`customfield_param`, field.`published` as fpublished, field.`override`, field.`disabler`, field.`ordering`
		FROM `#__virtuemart_customs` AS c LEFT JOIN `#__virtuemart_product_customfields` AS field ON c.`virtuemart_custom_id` = field.`virtuemart_custom_id` ';
		return $q;
	}

	function getCustomEmbeddedProductCustomField($virtuemart_customfield_id){

		$db= JFactory::getDBO ();
		$q = VirtueMartModelCustomfields::getProductCustomSelectFieldList();
		if($virtuemart_customfield_id){
			$q .= ' WHERE `virtuemart_customfield_id` ="' . (int)$virtuemart_customfield_id . '"';
		}
		$db->setQuery ($q);
		$field = $db->loadObject ();
		if($field){
			VirtueMartModelCustomfields::bindCustomEmbeddedFieldParams($field,$field->field_type);
		}

		return $field;
	}

	function getCustomEmbeddedProductCustomFields($productIds,$virtuemart_custom_id=0,$cartattribute=-1,$forcefront=FALSE){

		//vmSetStartTime('getCustomEmbeddedProductCustomFields');
		$app = JFactory::getApplication();
		$db= JFactory::getDBO ();
		$q = VirtueMartModelCustomfields::getProductCustomSelectFieldList();

		if(is_array($productIds) and count($productIds)>0){
			$q .= 'WHERE `virtuemart_product_id` IN ('.implode(',', $productIds).')';
		} else if(!empty($productIds)){
			$q .= 'WHERE `virtuemart_product_id` = "'.$productIds.'" ';
		} else {
			return array();
		}
		if(!empty($virtuemart_custom_id)){
			$q .= ' AND c.`virtuemart_custom_id`= "' . (int)$virtuemart_custom_id.'"';
		}
		if($cartattribute!=-1){
			$q .= ' AND ( `is_cart_attribute` = 1 OR `is_input` = 1) ';
		}
		if($forcefront or $app->isSite()){
			$q .= ' AND c.`published` = "1" ';
			$forcefront = true;
		}

		if(!empty($virtuemart_custom_id) and $virtuemart_custom_id!==0){
			$q .= ' ORDER BY field.`ordering` ASC';
		} else {
			if($forcefront or $app->isSite()){
				$q .= ' GROUP BY c.`virtuemart_custom_id`';
			}

			$q .= ' ORDER BY field.`ordering`,`virtuemart_custom_id` ASC';
		}

		$db->setQuery ($q);
		$productCustoms = $db->loadObjectList ();
		$err=$db->getErrorMsg();
		if($err){
			vmError('getCustomEmbeddedProductCustomFields error in query '.$err);
		}
		//vmdebug('getCustomEmbeddedProductCustomGroup '.$db->getQuery());
		if($productCustoms){

			$customfield_ids = array();
			$customfield_override_ids = array();
			foreach($productCustoms as $field){
				//vmdebug('$idField',$idField);
				if($field->override!=0){
					$customfield_override_ids[] = $field->override;
				}

				$customfield_ids[] = $field->virtuemart_customfield_id;
			}

			$virtuemart_customfield_ids = array_unique( array_diff($customfield_ids,$customfield_override_ids));

			foreach ($productCustoms as $k =>$field) {
				if(in_array($field->virtuemart_customfield_id,$virtuemart_customfield_ids)){

					if($forcefront and $field->disabler){
						unset($productCustoms[$k]);
					} else {
						VirtueMartModelCustomfields::bindCustomEmbeddedFieldParams($field,$field->field_type);
					}

				} else{
					unset($productCustoms[$k]);
				}
			}
			//vmTime('loadtime of customs','getCustomEmbeddedProductCustomFields');
			return $productCustoms;
		} else {
			return array();
		}
	}

	/**
	 * @author Max Milbers
	 * @author Patrick Kohl
	 * @param $field
	 * @param $product_id
	 * @param $row
	 */
	public function displayProductCustomfieldBE ($field, $product_id, $row) {

		//This is a kind of fallback, setting default of custom if there is no value of the productcustom
		$field->customfield_value = empty($field->customfield_value) ? $field->custom_value : $field->customfield_value;
		$field->customfield_price = empty($field->customfield_price) ? 0 : $field->customfield_price;

		//the option "is_cart_attribute" gives the possibility to set a price, there is no sense to set a price,
		//if the custom is not stored in the order.
		if ($field->is_input) {
			if(!class_exists('VirtueMartModelVendor')) require(JPATH_VM_ADMINISTRATOR.DS.'models'.DS.'vendor.php');
			if(!class_exists('VirtueMartModelCurrency')) require(JPATH_VM_ADMINISTRATOR.DS.'models'.DS.'currency.php');
			$vendor_model = VmModel::getModel('vendor');
			$vendor_model->setId(1);
			$vendor = $vendor_model->getVendor();
			$currency_model = VmModel::getModel('currency');
			$vendor_currency = $currency_model->getCurrency($vendor->vendor_currency);

			$priceInput = '<span style="white-space: nowrap;"><input type="text" size="12" style="text-align:right;" value="' . $field->customfield_price . '" name="field[' . $row . '][customfield_price]" /> '.$vendor_currency->currency_symbol."</span>";
		}
		else {
			$priceInput = ' ';
		}

		switch ($field->field_type) {

			case 'A':
				//vmdebug('displayProductCustomfieldBE $field',$field);
				if(!isset($field->withParent)) $field->withParent = 0;
				if(!isset($field->parentOrderable)) $field->parentOrderable = 0;
				//vmdebug('displayProductCustomfieldBE',$field);
				if (!class_exists('VmHTML')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'html.php');
				$html = JText::_('COM_VIRTUEMART_CUSTOM_WP').VmHTML::checkbox('field[' . $row . '][withParent]',$field->withParent,1,0,'').'<br />';
				$html .= JText::_('COM_VIRTUEMART_CUSTOM_PO').VmHTML::checkbox('field[' . $row . '][parentOrderable]',$field->parentOrderable,1,0,'');

				$options = array();
				$options[] = array('value' => 'product_name' ,'text' =>JText::_('COM_VIRTUEMART_PRODUCT_FORM_NAME'));
				$options[] = array('value' => 'product_sku', 'text' => JText::_ ('COM_VIRTUEMART_PRODUCT_SKU'));
				$options[] = array('value' => 'slug', 'text' => JText::_ ('COM_VIRTUEMART_PRODUCT_ALIAS'));
				$options[] = array('value' => 'product_length', 'text' => JText::_ ('COM_VIRTUEMART_PRODUCT_LENGTH'));
				$options[] = array('value' => 'product_width', 'text' => JText::_ ('COM_VIRTUEMART_PRODUCT_WIDTH'));
				$options[] = array('value' => 'product_height', 'text' => JText::_ ('COM_VIRTUEMART_PRODUCT_HEIGHT'));
				$options[] = array('value' => 'product_weight', 'text' => JText::_ ('COM_VIRTUEMART_PRODUCT_WEIGHT'));

				$html .= JHTML::_ ('select.genericlist', $options, 'field[' . $row . '][customfield_value]', '', 'value', 'text', $field->customfield_value) . '</td><td>' . $priceInput;
				return $html;
				// 					return 'Automatic Childvariant creation (later you can choose here attributes to show, now product name) </td><td>';
				break;
			/* string or integer */
			case 'B':
			case 'S':

				if($field->is_list){
					$options = array();
					$values = explode (';', $field->custom_value);

					foreach ($values as $key => $val) {
						$options[] = array('value' => $val, 'text' => $val);
					}

					$currentValue = $field->customfield_value;
					return JHTML::_ ('select.genericlist', $options, 'field[' . $row . '][customfield_value]', NULL, 'value', 'text', $currentValue) . '</td><td>' . $priceInput;
				} else{
					return '<input type="text" value="' . $field->customfield_value . '" name="field[' . $row . '][customfield_value]" /></td><td>' . $priceInput;
					break;
				}

				break;
			/* parent hint, this is a GROUP and should be G not P*/
			case 'G':
				return $field->customfield_value . '<input type="hidden" value="' . $field->customfield_value . '" name="field[' . $row . '][customfield_value]" /></td><td>';
				break;
			/* image */
			case 'M':
				if (empty($product)) {
					$vendorId = 1;
				}
				else {
					$vendorId = $product->virtuemart_vendor_id;
				}
				$q = 'SELECT `virtuemart_media_id` as value,`file_title` as text FROM `#__virtuemart_medias` WHERE `published`=1
					AND (`virtuemart_vendor_id`= "' . $vendorId . '" OR `shared` = "1")';
				$this->_db->setQuery ($q);
				$options = $this->_db->loadObjectList ();
				return JHTML::_ ('select.genericlist', $options, 'field[' . $row . '][customfield_value]', '', 'value', 'text', $field->customfield_value) . '</td><td>' . $priceInput;
				break;

			case 'D':
				return vmJsApi::jDate ($field->customfield_value, 'field[' . $row . '][customfield_value]', 'field_' . $row . '_customvalue') .'</td><td>'. $priceInput;
				break;

			//'X'=>'COM_VIRTUEMART_CUSTOM_EDITOR',
			case 'X':
				return '<textarea class="mceInsertContentNew" name="field[' . $row . '][customfield_value]" id="field-' . $row . '-customfield_value">' . $field->customfield_value . '</textarea>
						<script type="text/javascript">// Creates a new editor instance
							tinymce.execCommand("mceAddControl",true,"field-' . $row . '-customfield_value")
						</script></td><td>' . $priceInput;
				//return '<input type="text" value="'.$field->customfield_value.'" name="field['.$row.'][customfield_value]" /></td><td>'.$priceInput;
				break;
			//'Y'=>'COM_VIRTUEMART_CUSTOM_TEXTAREA'
			case 'Y':
				return '<textarea id="field[' . $row . '][customfield_value]" name="field[' . $row . '][customfield_value]" class="inputbox" cols=80 rows=50 >' . $field->customfield_value . '</textarea></td><td>' . $priceInput;
				//return '<input type="text" value="'.$field->customfield_value.'" name="field['.$row.'][customfield_value]" /></td><td>'.$priceInput;
				break;
			/*Extended by plugin*/
			case 'E':

				$html = '<input type="hidden" value="' . $field->customfield_value . '" name="field[' . $row . '][customfield_value]" />';
				if (!class_exists ('vmCustomPlugin')) {
					require(JPATH_VM_PLUGINS . DS . 'vmcustomplugin.php');
				}
				//vmdebug('displayProductCustomfieldBE $field',$field);
				JPluginHelper::importPlugin ('vmcustom', $field->custom_element);
				$dispatcher = JDispatcher::getInstance ();
				$retValue = '';
				$dispatcher->trigger ('plgVmOnProductEdit', array($field, $product_id, &$row, &$retValue));

				return $html . $retValue  . '</td><td>'. $priceInput;
				break;

			/* related category*/
			case 'Z':
				if (!$product_id) {
					return '';
				} // special case it's category ID !
				$q = 'SELECT * FROM `#__virtuemart_categories_' . VMLANG . '` JOIN `#__virtuemart_categories` AS p using (`virtuemart_category_id`) WHERE `published`=1 AND `virtuemart_category_id`= "' . (int)$product_id . '" ';
				$this->_db->setQuery ($q);
				//echo $this->_db->_sql;
				if ($category = $this->_db->loadObject ()) {
					$q = 'SELECT `virtuemart_media_id` FROM `#__virtuemart_category_medias` WHERE `virtuemart_category_id`= "' . (int)$product_id . '" ';
					$this->_db->setQuery ($q);
					$thumb = '';
					if ($media_id = $this->_db->loadResult ()) {
						$thumb = $this->displayCustomMedia ($media_id,'category');
					}
					$display = '<input type="hidden" value="' . $product_id . '" name="field[' . $row . '][customfield_value]" />';
					return $display . JHTML::link (JRoute::_ ('index.php?option=com_virtuemart&view=category&task=edit&virtuemart_category_id=' . $product_id), $thumb . ' ' . $category->category_name, array('title' => $category->category_name)) . $display;
				}
				else {
					return 'no result';
				}
			/* related product*/
			case 'R':
				if (!$product_id) {
					return '';
				}

				$q = 'SELECT `product_name`,`product_sku`,`product_s_desc` FROM `#__virtuemart_products_' . VMLANG . '` AS l LEFT JOIN `#__virtuemart_products` using (`virtuemart_product_id`) WHERE `virtuemart_product_id`=' . (int)$product_id ;
				$this->_db->setQuery ($q);
				$related = $this->_db->loadObject ();
				$err = $this->_db->getErrorMsg();
				if(!empty($err)){
					vmError('Error in get R '.$err,'Error in get R ');
				}
				$display = $related->product_name . '(' . $related->product_sku . ')';
				$display .= '<input type="hidden" value="' . $product_id . '" name="field[' . $row . '][customfield_value]" />';

				$q = 'SELECT `virtuemart_media_id` FROM `#__virtuemart_product_medias`WHERE `virtuemart_product_id`= "' . (int)$product_id . '" AND (`ordering` = 0 OR `ordering` = 1)';
				$this->_db->setQuery ($q);
				$thumb = '';
				if ($media_id = $this->_db->loadResult ()) {
					vmdebug('Show media');
					$thumb = $this->displayCustomMedia ($media_id);
				}
				$title= $related->product_s_desc?  $related->product_s_desc :'';
				return $display . JHTML::link (JRoute::_ ('index.php?option=com_virtuemart&view=product&task=edit&virtuemart_product_id=' . $product_id), $thumb . '<br /> ' . $related->product_name, array('title' => $title));
				break;


		}
	}

	/**
	 * @author Max Milbers
	 * @param $product
	 * @param $customfield
	 */
	public function displayProductCustomfieldFE (&$product, &$customfield) {

		if(!isset($customfield->display))$customfield->display = '';

		//if (!class_exists ('VmHTML')) require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'html.php');
		//if (!class_exists ('CurrencyDisplay')) require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'currencydisplay.php');
		//$currency = CurrencyDisplay::getInstance ();

		if (!class_exists ('calculationHelper')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'calculationh.php');
		}
		$calculator = calculationHelper::getInstance ();
		$calculator ->_product = $product;
		if (!class_exists ('vmCustomPlugin')) {
			require(JPATH_VM_PLUGINS . DS . 'vmcustomplugin.php');
		}

		if ($customfield->field_type == "E") {

			JPluginHelper::importPlugin ('vmcustom');
			$dispatcher = JDispatcher::getInstance ();
			$ret = $dispatcher->trigger ('plgVmOnDisplayProductFE', array(&$product, &$customfield));
			return; // $customfield->display;
		}

		$fieldname = 'field['.$product->virtuemart_product_id.'][' . $customfield->virtuemart_customfield_id . '][customfield_value]';
		$customProductDataName = 'customProductData['.$product->virtuemart_product_id.']['.$customfield->virtuemart_custom_id.']';

		//This is a kind of fallback, setting default of custom if there is no value of the productcustom
		$customfield->customfield_value = empty($customfield->customfield_value) ? $customfield->custom_value : $customfield->customfield_value;
		//$value = $customfield->customfield_value;
		$type = $customfield->field_type;

		if (!class_exists ('CurrencyDisplay'))
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'currencydisplay.php');
		$currency = CurrencyDisplay::getInstance ();

		/*if ($customfield->customfield_price > 0) {

			$price = $currency->priceDisplay ((float)$customfield->customfield_price);
		}*/

		switch ($type) {

			case 'A':

				$options = array();

				$session = JFactory::getSession ();
				$virtuemart_category_id = $session->get ('vmlastvisitedcategoryid', 0, 'vm');

				$productModel = VmModel::getModel ('product');

				//Todo preselection as dropdown of children
				//Note by Max Milbers: This is not necessary, in this case it is better to unpublish the parent and to give the child which should be preselected a category
				//Or it is withParent, in that case there exists the case, that a parent should be used as a kind of mini category and not be orderable.
				//There exists already other customs and in special plugins which wanna disable or change the add to cart button.
				//I suggest that we manipulate the button with a message "choose a variant first"
				//if(!isset($customfield->pre_selected)) $customfield->pre_selected = 0;
				$selected = JRequest::getInt ('virtuemart_product_id',0);

				$html = '';
				$uncatChildren = $productModel->getUncategorizedChildren ($customfield->withParent);


				foreach ($uncatChildren as $k => $child) {
					if(!isset($child[$customfield->customfield_value])){
						vmdebug('The child has no value at index '.$customfield->customfield_value,$customfield,$child);
					} else {
						$options[] = array('value' => JRoute::_ ('index.php?option=com_virtuemart&view=productdetails&virtuemart_category_id=' . $virtuemart_category_id . '&virtuemart_product_id=' . $child['virtuemart_product_id']), 'text' => $child[$customfield->customfield_value]);
					}
				}
				//if($customfield->is_list){
					$html .= JHTML::_ ('select.genericlist', $options, $fieldname, 'onchange="window.top.location.href=this.options[this.selectedIndex].value" size="1" class="inputbox"', "value", "text",
						JRoute::_ ('index.php?option=com_virtuemart&view=productdetails&virtuemart_category_id=' . $virtuemart_category_id . '&virtuemart_product_id=' . $selected));
				//} else {
					//the idea is that we can provide instead of a list a multi add possibility, atm this is possible, but needs another layout, we may connect it
				//}

				if($customfield->parentOrderable==0 and $product->product_parent_id==0){
					$product->orderable = FALSE;
				}

				$customfield->display = $html;
				break;

			/*Date variant*/
			case 'D':
				if(empty($customfield->custom_value)) $customfield->custom_value = 'LC2';
				//Customer selects date
				if($customfield->is_input){
					$customfield->display =  '<span class="product_custom_date">' . vmJsApi::jDate ($customfield->customfield_value,$customProductDataName) . '</span>'; //vmJsApi::jDate($field->custom_value, 'field['.$row.'][custom_value]','field_'.$row.'_customvalue').$priceInput;
				}
				//Customer just sees a date
				else {
					$customfield->display =  '<span class="product_custom_date">' . vmJsApi::date ($customfield->customfield_value, $customfield->custom_value, TRUE) . '</span>';
				}

				break;
			/* text area or editor No JText, only displayed in BE */
			case 'X':
			case 'Y':
				$customfield->display =  $customfield->customfield_value;
				break;
			/* string or integer */
			case 'B':
			case 'S':

				if($customfield->is_list){

					/*if($type=='B'){
						if ($customfield->customfield_value == 0){
							$customfield->customfield_value =  'JNO';
						} else {
							$customfield->customfield_value =  'JYES';
						}
					}*/
					//vmdebug('case S $customfield->is_list',$customfield->customfield_value);
					if($customfield->is_input){

						$options = array();
						$values = explode (';', $customfield->custom_value);

						foreach ($values as $key => $val) {
							$options[] = array('value' => $val, 'text' => $val);
						}

						$currentValue = $customfield->customfield_value;
						$customfield->display = JHTML::_ ('select.genericlist', $options, $customProductDataName, NULL, 'value', 'text', $currentValue);

						//$customfield->display =  '<input type="text" readonly value="' . JText::_ ($customfield->customfield_value) . '" name="'.$customProductDataName.'" /> ' . JText::_ ('COM_VIRTUEMART_CART_PRICE') . $price . ' ';
					} else {
						$customfield->display =  JText::_ ($customfield->customfield_value);
					}
				} else {
					if(isset($customfield->is_input)){

						$options = $this->getCustomEmbeddedProductCustomFields($product->allIds,$customfield->virtuemart_custom_id);
						//vmdebug('getProductCustomsFieldCart options',$options,$product->allIds);
						$customfield->options = array();
						foreach ($options as $option) {
							$customfield->options[$option->virtuemart_customfield_id] = $option;
						}

						$default = reset($customfield->options);
						foreach ($customfield->options as $productCustom) {
							$price = self::_getCustomPrice($productCustom->customfield_price, $currency, $calculator);
							$productCustom->text = $productCustom->customfield_value . ' ' . $price;
							//$productCustom->formname = '['.$productCustom->virtuemart_customfield_id.'][selected]';
						}

						$customfield->display = VmHTML::select ($customProductDataName,
							$customfield->options,
							$default->customfield_value,
							'',
							'virtuemart_customfield_id',
							'text',
							FALSE);

					} else {
						$customfield->display =  JText::_ ($customfield->customfield_value);
					}
				}

				break;

			/* parent The parent has a display in the FE?*/
			case 'G':
				//$customfield->display =  '<span class="product_custom_parent">' . JText::_ ($value) . '</span>';
				break;
			/* image */
			case 'M':
				$customfield->display =  $this->displayCustomMedia ($customfield->customfield_value);
				break;
			case 'Z':
				$html = '';
				$q = 'SELECT * FROM `#__virtuemart_categories_' . VMLANG . '` as l JOIN `#__virtuemart_categories` AS c using (`virtuemart_category_id`) WHERE `published`=1 AND l.`virtuemart_category_id`= "' . (int)$customfield->customfield_value . '" ';
				$this->_db->setQuery ($q);
				if ($category = $this->_db->loadObject ()) {
					$q = 'SELECT `virtuemart_media_id` FROM `#__virtuemart_category_medias`WHERE `virtuemart_category_id`= "' . $category->virtuemart_category_id . '" ';
					$this->_db->setQuery ($q);
					$thumb = '';
					if ($media_id = $this->_db->loadResult ()) {
						$thumb = $this->displayCustomMedia ($media_id,'category');
					}
					$customfield->display = JHTML::link (JRoute::_ ('index.php?option=com_virtuemart&view=category&virtuemart_category_id=' . $category->virtuemart_category_id), $thumb . ' ' . $category->category_name, array('title' => $category->category_name));
				}
				break;
			case 'R':

				$q = 'SELECT l.`product_name`, p.`product_parent_id` , l.`product_name`, x.`virtuemart_category_id` FROM `#__virtuemart_products_' . VMLANG . '` as l
					 JOIN `#__virtuemart_products` AS p using (`virtuemart_product_id`)
					 LEFT JOIN `#__virtuemart_product_categories` as x on x.`virtuemart_product_id` = p.`virtuemart_product_id`
					 WHERE p.`published`=1 AND  p.`virtuemart_product_id`= "' . $customfield->customfield_value . '" ';
				$this->_db->setQuery ($q);
				if($related = $this->_db->loadObject ()){

					$q = 'SELECT `virtuemart_media_id` FROM `#__virtuemart_product_medias` WHERE `virtuemart_product_id`= "' . (int)$customfield->customfield_value . '" ORDER BY `ordering` ASC';
					$this->_db->setQuery ($q);
					if ($media_id = $this->_db->loadResult ()) {
						$thumb = $this->displayCustomMedia ($media_id).' ';
						//vmdebug('displayProductCustomfieldFE in R ',$thumb);
						$customfield->display = JHTML::link (JRoute::_ ('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id=' . $customfield->customfield_value . '&virtuemart_category_id=' . $related->virtuemart_category_id), $thumb   . $related->product_name, array('title' => $related->product_name));
					} else {
						$err = $this->_db->getErrorMsg();
						vmdebug('No related product found for '.$customfield->customfield_value.', maybe Product has no category? '.$err);
					}
				} else {
					$err = $this->_db->getErrorMsg();
					vmdebug('No related product found for '.$customfield->customfield_value.', maybe Product has no category? '.$err);
				}
				break;
			}

	}


	/**
	 * There are too many functions doing almost the same for my taste
	 * the results are sometimes slighty different and makes it hard to work with it, therefore here the function for future proxy use
	 *
	 */
	public function displayProductCustomfieldSelected ($product, $html, $trigger) {

		if(isset($product->param)){
			vmTrace('param found, seek and destroy');
			return false;
		}
		$row = 0;
		if (!class_exists ('shopFunctionsF'))
			require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');

		$variantmods = isset($product -> customProductData)?$product -> customProductData:$product -> product_attribute;
		//$variantmods = $product -> customProductData;

		if(!is_array($variantmods)){
			$variantmods = json_decode($variantmods);
		}
		//vmdebug('displayProductCustomfieldSelected $variantmods ',$variantmods);
		foreach ($variantmods as $custom_id => $selected) {

			if(is_object($selected)) $selected = (array)$selected;
			if(is_array($selected)){
				reset($selected);
				$key = key($selected);
				if(isset($key)){
					$customfield_id = $key;
				} else {
					vmError('displayProductCustomfieldSelected unknown stored parameters');
				}
			//	vmdebug('displayProductCustomfieldSelected $custom_id ',$custom_id,$customfield_id,$key,$selected);
			} else {
				$customfield_id = $selected;
			//	vmdebug('displayProductCustomfieldSelected NO PARAMS $custom_id ',$custom_id,$customfield_id);
			}

			if ($customfield_id) {
				$productCustom = self::getCustomEmbeddedProductCustomField ($customfield_id);
				//The stored result in vm2.0.14 looks like this {"48":{"textinput":{"comment":"test"}}}
				//and now {"32":[{"invala":"100"}]}
				if (!empty($productCustom)) {
					$html .= ' <span class="product-field-type-' . $productCustom->field_type . '">';
					if ($productCustom->field_type == "E") {

						$product->productCustom = $productCustom;
						//$product->row = $row;
						if (!class_exists ('vmCustomPlugin'))
							require(JPATH_VM_PLUGINS . DS . 'vmcustomplugin.php');
						JPluginHelper::importPlugin ('vmcustom');
						$dispatcher = JDispatcher::getInstance ();
						//vmdebug('displayProductCustomfieldSelected is PLUGIN use trigger '.$trigger,$selected);
						$dispatcher->trigger ($trigger, array($product, $selected, &$html));

					}
					else {
						//vmdebug('customFieldDisplay $productCustom by self::getProductCustomField $variant: '.$variant.' $selected: '.$selected,$productCustom);
						$value = '';
						if (($productCustom->field_type == "G")) {

							$db = JFactory::getDBO ();
							$db->setQuery ('SELECT  `product_name` FROM `#__virtuemart_products_' . VMLANG . '` WHERE virtuemart_product_id=' . (int)$productCustom->custom_value);
							$child = $db->loadObject ();
							$value = $child->product_name;
						}
						elseif (($productCustom->field_type == "M")) {
							// 						$html .= $productCustom->custom_title.' '.self::displayCustomMedia($productCustom->custom_value);
							$value = self::displayCustomMedia ($productCustom->customfield_value);
						}
						elseif (($productCustom->field_type == "S")) {
							//$value = $productCustom->custom_title.' '.JText::_($productCustom->customfield_value);
							$value = $productCustom->customfield_value;
						}
						else {
							// 						$html .= $productCustom->custom_title.' '.$productCustom->custom_value;
							//vmdebug('customFieldDisplay',$productCustom);
							$value = $productCustom->customfield_value;
						}
						//vmdebug('displayProductCustomfieldSelected ',$productCustom);
						$html .= ShopFunctionsF::translateTwoLangKeys ($productCustom->custom_title, $value);
					}
					$html .= '</span><br />';
				}
				else {
					// falldown method if customfield are deleted
					foreach ((array)$selected as $key => $value) {
						$html .= '<br/ >Couldnt find customfield' . ($key ? '<span>' . $key . ' </span>' : '') . $value;
					}
					//vmdebug ('CustomsFieldOrderDisplay, $item->productCustom empty? ' . $variant);
					vmdebug ('customFieldDisplay, $productCustom is EMPTY ');
				}

			}
		}

	//	vmdebug ('customFieldDisplay html begin: ' . $html . ' end');
		return $html . '</div>';
	}


	/**
	 * TODO This is html and view stuff and MUST NOT be in the model, notice by Max
	 * render custom fields display cart module FE
	 */
	public function CustomsFieldCartModDisplay ($product) {

		return self::displayProductCustomfieldSelected ($product, '<div class="vm-customfield-mod">', 'plgVmOnViewCartModule');

	}

	/**
	 *  TODO This is html and view stuff and MUST NOT be in the model, notice by Max
	 * render custom fields display cart FE
	 */
	public function CustomsFieldCartDisplay ($product) {

		return self::displayProductCustomfieldSelected ($product, '<div class="vm-customfield-cart">', 'plgVmOnViewCart');

	}

	/*
	 * render custom fields display order BE/FE
	*/
	public function CustomsFieldOrderDisplay ($item, $view = 'FE', $absUrl = FALSE) {

		if (!empty($item->product_attribute)) {
			$item->customProductData = json_decode ($item->product_attribute, TRUE);
			if (!empty($item->customProductData)) {
				return self::displayProductCustomfieldSelected ($item, '<div class="vm-customfield-cart">', 'plgVmDisplayInOrder' . $view);
			} else {
				vmdebug ('CustomsFieldOrderDisplay $item->param empty? ');
			}
		}

		return FALSE;
	}

	function displayCustomMedia ($media_id, $table = 'product', $absUrl = FALSE) {

		if (!class_exists ('TableMedias'))
			require(JPATH_VM_ADMINISTRATOR . DS . 'tables' . DS . 'medias.php');
		//$data = $this->getTable('medias');
		$db = JFactory::getDBO ();
		$data = new TableMedias($db);
		$data->load ((int)$media_id);

		if (!class_exists ('VmMediaHandler'))
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'mediahandler.php');
		$media = VmMediaHandler::createMedia ($data, $table);

		return $media->displayMediaThumb ('', FALSE, '', TRUE, TRUE, $absUrl);

	}

	static function _getCustomPrice($customPrice, $currency, $calculator) {
		if ((float)$customPrice) {
			$price = strip_tags ($currency->priceDisplay ($calculator->calculateCustomPriceWithTax ($customPrice)));
			if ($customPrice >0) {
				$price ="+".$price;
			}
		}
		else {
			$price = ($customPrice === '') ? '' :  JText::_ ('COM_VIRTUEMART_CART_PRICE_FREE');
		}
		return $price;
	}

	/**
	 * @param $product
	 * @param $variants ids of the selected variants
	 * @return float
	 */
	public function calculateModificators(&$product, $variants) {

		$modificatorSum = 0.0;

		//VmConfig::$echoDebug=TRUE;
		//vmdebug('calculateModificators $modificatorSum',$variants);
		if(!is_array($variants)) $variants = (array) $variants;
		foreach ($variants as $custom_id => $selvalues) {
			$variant = $selvalues;
			if(is_array($selvalues)){
				foreach($selvalues as $key => $value){
					$variant = $value;
					$selected =$key;
				}
			} else {
				$selected = $selvalues;
			}
			//vmdebug('calculateModificators variant',$variant,$selected);
			if (!empty($selected)) {

				$productCustom = $this->getCustomEmbeddedProductCustomField($selected);

				if (!empty($productCustom) and $productCustom->field_type =='E') {
					if(!class_exists('vmCustomPlugin')) require(JPATH_VM_PLUGINS.DS.'vmcustomplugin.php');
					JPluginHelper::importPlugin('vmcustom');
					$dispatcher = JDispatcher::getInstance();
					$dispatcher->trigger('plgVmCalculateCustomVariant',array(&$product, &$productCustom,$variant,$modificatorSum));
				}

				if (!empty($productCustom->customfield_price)) {

					//TODO adding % and more We should use here $this->interpreteMathOp
					$modificatorSum = $modificatorSum + $productCustom->customfield_price;
				}

			}
		}

// 			echo ' $modificatorSum ',$modificatorSum;
		return $modificatorSum;
	}

	static function setParameterableByFieldType(&$table, $type, $custom_element=0,$custom_jplugin_id=0){

		//$type = $table->field_type;
		if($custom_element===0){
			$custom_element = $table->custom_element;
		}

		if($custom_jplugin_id===0){
			$custom_jplugin_id = $table->custom_jplugin_id;
		}

		$varsToPush = self::getVarsToPush($type);
		$xParams = $table->_xParams;

		if ($type == 'E') {
			JPluginHelper::importPlugin ('vmcustom');
			$dispatcher = JDispatcher::getInstance ();
			$retValue = $dispatcher->trigger ('plgVmGetTablePluginParams', array('custom',$custom_element, $custom_jplugin_id, &$xParams, &$varsToPush));
		}

		if(!empty($varsToPush)){
			$table->setParameterable($xParams,$varsToPush,TRUE);
		}

	}


	static function bindParameterableByFieldType(&$table, $type){

		$varsToPush = self::getVarsToPush($type);
		$xParams = $table->_xParams;
		if ($type == 'E') {
			JPluginHelper::importPlugin ('vmcustom');
			$dispatcher = JDispatcher::getInstance ();
			$retValue = $dispatcher->trigger ('plgVmDeclarePluginParamsCustom', array(&$table));
		}

		if(!empty($varsToPush)){
			VmTable::bindParameterable($table,$table->_xParams,$varsToPush);
		}

	}

	static function getVarsToPush($type){

		$varsToPush = 0;
		if($type=='A'){
			$varsToPush = array(
				'withParent'        => array(0, 'int'),
				'parentOrderable'   => array(0, 'int')
			);
		}
		return $varsToPush;
	}


	/* Save and delete from database
	* all product custom_fields and xref
	@ var   $table	: the xref table(eg. product,category ...)
	@array $data	: array of customfields
	@int     $id		: The concerned id (eg. product_id)
	*/
	public function storeProductCustomfields($table,$datas, $id) {

		//vmdebug('storeProductCustomfields',$datas);
		JRequest::checkToken() or jexit( 'Invalid Token, in store customfields');
		//Sanitize id
		$id = (int)$id;

		//Table whitelist
		$tableWhiteList = array('product','category','manufacturer');
		if(!in_array($table,$tableWhiteList)) return false;

		// Get old IDS
		$this->_db->setQuery( 'SELECT `virtuemart_customfield_id` FROM `#__virtuemart_'.$table.'_customfields` as `PC` WHERE `PC`.virtuemart_'.$table.'_id ='.$id );
		$old_customfield_ids = $this->_db->loadResultArray();

		if (array_key_exists('field', $datas)) {

			foreach($datas['field'] as $key => $fields){

				if(!empty($datas['field'][$key]['virtuemart_product_id']) and (int)$datas['field'][$key]['virtuemart_product_id']!=$id){
					//aha the field is from the parent, what we do with it?
					$fields['override'] = (int)$fields['override'];
					$fields['disabler'] = (int)$fields['disabler'];
					if($fields['override']!=0 or $fields['disabler']!=0){
						//If it is set now as override, store it as clone, therefore set the virtuemart_customfield_id = 0
						$fields['override'] = $fields['virtuemart_customfield_id'];
						$fields['disabler'] = $fields['virtuemart_customfield_id'];
						$fields['virtuemart_customfield_id'] = 0;
						unset($fields['virtuemart_product_id']);
						vmdebug('storeProductCustomfields I am in field from parent and create a clone');
					}
					else {
						//we do not store customfields inherited by the parent, therefore
						vmdebug('storeProductCustomfields I am in field from parent => not storing');
						$key = array_search($fields['virtuemart_customfield_id'], $old_customfield_ids );
						if ($key !== false ){
							vmdebug('storeProductCustomfields unsetting from $old_customfild_ids',$key);
							unset( $old_customfield_ids[ $key ] );
						}
						continue;
					}
				}

				$fields['virtuemart_'.$table.'_id'] =$id;
				$tableCustomfields = $this->getTable($table.'_customfields');
				$tableCustomfields->setPrimaryKey('virtuemart_product_id');
				if (!empty($datas['custom_param'][$key]) and !isset($datas['clone']) ) {
					if (array_key_exists( $key,$datas['custom_param'])) {
						$fields = array_merge ((array)$fields, (array)$datas['custom_param'][$key]);
					}
				}

				VirtueMartModelCustomfields::setParameterableByFieldType($tableCustomfields,$fields['field_type'],$fields['custom_element'],$fields['custom_jplugin_id']);
				vmdebug('storeProductCustomfields I am in field ',$tableCustomfields);
				$tableCustomfields->bindChecknStore($fields);
				$errors = $tableCustomfields->getErrors();

				foreach($errors as $error){
					vmError($error);
				}
				$key = array_search($fields['virtuemart_customfield_id'], $old_customfield_ids );
				if ($key !== false ) unset( $old_customfield_ids[ $key ] );
// 				vmdebug('datas clone',$old_customfield_ids,$fields);
			}
		} else {
			vmdebug('storeProductCustomfields nothing to store',$datas['field']);
		}
		vmdebug('Delete $old_customfield_ids',$old_customfield_ids);
		if ( count($old_customfield_ids) ) {
			// delete old unused Customfields
			$this->_db->setQuery( 'DELETE FROM `#__virtuemart_'.$table.'_customfields` WHERE `virtuemart_customfield_id` in ("'.implode('","', $old_customfield_ids ).'") ');
			$this->_db->query();
			vmdebug('Deleted $old_customfield_ids',$old_customfield_ids);
		}


		JPluginHelper::importPlugin('vmcustom');
		$dispatcher = JDispatcher::getInstance();
		if (isset($datas['plugin_param']) and is_array($datas['plugin_param'])) {
			foreach ($datas['plugin_param'] as $key => $plugin_param ) {
				$dispatcher->trigger('plgVmOnStoreProduct', array($datas, $plugin_param ));
			}
		}

	}

	static public function setEditCustomHidden ($customfield, $i) {

		if (!isset($customfield->virtuemart_customfield_id))
			$customfield->virtuemart_customfield_id = '0';
		if (!isset($customfield->virtuemart_product_id))
			$customfield->virtuemart_product_id = '';
		$html = '
			<input type="hidden" value="' . $customfield->field_type . '" name="field[' . $i . '][field_type]" />
			<input type="hidden" value="' . $customfield->custom_element . '" name="field[' . $i . '][custom_element]" />
			<input type="hidden" value="' . $customfield->custom_jplugin_id . '" name="field[' . $i . '][custom_jplugin_id]" />
			<input type="hidden" value="' . $customfield->virtuemart_custom_id . '" name="field[' . $i . '][virtuemart_custom_id]" />
			<input type="hidden" value="' . $customfield->virtuemart_product_id . '" name="field[' . $i . '][virtuemart_product_id]" />
			<input type="hidden" value="' . $customfield->virtuemart_customfield_id . '" name="field[' . $i . '][virtuemart_customfield_id]" />';

			//<input type="hidden" value="' . $customfield->admin_only . '" checked="checked" name="field[' . $i . '][admin_only]" />';
		return $html;

	}
}
// pure php no closing tag
