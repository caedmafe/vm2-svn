<?php

/**
 *
 * Category model for the cart
 *
 * @package	VirtueMart
 * @subpackage Cart
 * @author RolandD
 * @author Max Milbers
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id$
 */
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');


/**
 * Model class for the cart
 * Very important, use ALWAYS the getCart function, to get the cart from the session
 * @package	VirtueMart
 * @subpackage Cart
 * @author RolandD
 * @author Max Milbers
 */
class VirtueMartCart {

	//	var $productIds = array();
	var $products = array();
	var $_inCheckOut = false;
	var $_dataValidated = false;
	var $_confirmDone = false;
	var $_lastError = NULL; // Used to pass errmsg to the cart using addJS()
	//todo multivendor stuff must be set in the add function, first product determines ownership of cart, or a fixed vendor is used
	var $vendorId = 1;
	var $lastVisitedCategoryId = 0;
	var $virtuemart_shipmentmethod_id = 0;
	var $virtuemart_paymentmethod_id = 0;
	var $automaticSelectedShipment = false;
	var $automaticSelectedPayment  = false;
	var $BT = 0;
	var $ST = 0;
	var $tosAccepted = NULL;
	var $customer_comment = '';
	var $couponCode = '';
	var $order_language = '';
	var $cartData = NULL;
	var $lists = NULL;
	var $order_number=NULL; // added to solve emptying cart for payment notification
	// 	var $user = null;
// 	var $prices = null;
	var $pricesUnformatted = NULL;
	var $pricesCurrency = NULL;
	var $paymentCurrency = NULL;
	var $STsameAsBT = 0;
	var $productParentOrderable = TRUE;

	var $cartProductsData = array();
	private static $_cart = NULL;
	private static $_triesValidateCoupon;
	var $useSSL = 1;
	// 	static $first = true;

	private function __construct() {
		$this->useSSL = VmConfig::get('useSSL',0);
		$this->useXHTML = true;
		$this->cartProductsData = array();
		self::$_triesValidateCoupon=0;
	}

	/**
	 * Get the cart from the session
	 *
	 * @author Max Milbers
	 * @access public
	 * @param array $cart the cart to store in the session
	 */
	public static function getCart($setCart=true, $options = array()) {

		//What does this here? for json stuff?
		if (!class_exists('JTable')
		)require(JPATH_VM_LIBRARIES . DS . 'joomla' . DS . 'database' . DS . 'table.php');
// 		JTable::addIncludePath(JPATH_VM_ADMINISTRATOR . DS . 'tables');

		if(empty(self::$_cart)){
			$session = JFactory::getSession($options);
			$cartSession = $session->get('vmcart', 0, 'vm');

			if (!empty($cartSession)) {
				$sessionCart = unserialize( $cartSession );

				self::$_cart = new VirtueMartCart;

				//self::$_cart->products = $sessionCart->products;

				self::$_cart->cartProductsData = $sessionCart->cartProductsData;
				//vmdebug('getCart product',self::$_cart->cartProductsData);
				self::$_cart->vendorId	 							= $sessionCart->vendorId;
				self::$_cart->lastVisitedCategoryId	 			= $sessionCart->lastVisitedCategoryId;
				self::$_cart->virtuemart_shipmentmethod_id	= $sessionCart->virtuemart_shipmentmethod_id;
				self::$_cart->virtuemart_paymentmethod_id 	= $sessionCart->virtuemart_paymentmethod_id;
				self::$_cart->automaticSelectedShipment 		= $sessionCart->automaticSelectedShipment;
				self::$_cart->automaticSelectedPayment 		= $sessionCart->automaticSelectedPayment;
				self::$_cart->BT 										= $sessionCart->BT;
				self::$_cart->ST 										= $sessionCart->ST;
				self::$_cart->tosAccepted 							= $sessionCart->tosAccepted;
				self::$_cart->customer_comment 					= base64_decode($sessionCart->customer_comment);
				self::$_cart->couponCode 							= $sessionCart->couponCode;
				self::$_cart->cartData 								= $sessionCart->cartData;
				self::$_cart->order_number							= $sessionCart->order_number;

				self::$_cart->lists 									= $sessionCart->lists;

				self::$_cart->pricesCurrency						= $sessionCart->pricesCurrency;
				self::$_cart->paymentCurrency						= $sessionCart->paymentCurrency;

				self::$_cart->_inCheckOut 							= $sessionCart->_inCheckOut;
				self::$_cart->_dataValidated						= $sessionCart->_dataValidated;
				self::$_cart->_confirmDone							= $sessionCart->_confirmDone;
				self::$_cart->STsameAsBT							= $sessionCart->STsameAsBT;

				$lang = JFactory::getLanguage();
				self::$_cart->order_language = $lang->getTag();
			}

		}

		if(empty(self::$_cart)){
			self::$_cart = new VirtueMartCart;
		}

		if ( $setCart == true ) {
			self::$_cart->setPreferred();
			self::$_cart->setCartIntoSession();
		}

		//vmdebug('getCart',self::$_cart);
		return self::$_cart;
	}

	/*
	 * Set non product info in object
	*/
	public function setPreferred() {

		$usermodel = VmModel::getModel('user');
		$user = $usermodel->getCurrentUser();

		if (empty($this->BT) || (!empty($this->BT) && count($this->BT) <=1) ) {
			foreach ($user->userInfo as $address) {
				if ($address->address_type == 'BT') {
					$this->saveAddressInCart((array) $address, $address->address_type,false);
				}
			}
		}

		if (empty($this->virtuemart_shipmentmethod_id) && !empty($user->virtuemart_shipmentmethod_id)) {
			$this->virtuemart_shipmentmethod_id = $user->virtuemart_shipmentmethod_id;
		}

		if (empty($this->virtuemart_paymentmethod_id) && !empty($user->virtuemart_paymentmethod_id)) {
			$this->virtuemart_paymentmethod_id = $user->virtuemart_paymentmethod_id;
		}

		//$this->tosAccepted is due session stuff always set to 0, so testing for null does not work
		if((!empty($user->agreed) || !empty($this->BT['agreed'])) && !VmConfig::get('agree_to_tos_onorder',0) ){
				$this->tosAccepted = 1;
		}
	}

	/**
	 * Set the cart in the session
	 *
	 * @author RolandD
	 *
	 * @access public
	 * @param array $cart the cart to store in the session
	 */
	public function setCartIntoSession() {

		$session = JFactory::getSession();

		$sessionCart = new stdClass();

		// 		$sessionCart->products = $products;
	//	$sessionCart->products = $this->products;
		// 		echo '<pre>'.print_r($products,1).'</pre>';die;
		$sessionCart->cartProductsData = $this->cartProductsData;
		$sessionCart->vendorId	 							= $this->vendorId;
		$sessionCart->lastVisitedCategoryId	 			= $this->lastVisitedCategoryId;
		$sessionCart->virtuemart_shipmentmethod_id	= $this->virtuemart_shipmentmethod_id;
		$sessionCart->virtuemart_paymentmethod_id 	= $this->virtuemart_paymentmethod_id;
		$sessionCart->automaticSelectedShipment 		= $this->automaticSelectedShipment;
		$sessionCart->automaticSelectedPayment 		= $this->automaticSelectedPayment;
		$sessionCart->order_number 		            = $this->order_number;

		$sessionCart->BT 										= $this->BT;
		$sessionCart->ST 										= $this->ST;
		$sessionCart->tosAccepted 							= $this->tosAccepted;
		$sessionCart->customer_comment 					= base64_encode($this->customer_comment);
		$sessionCart->couponCode 							= $this->couponCode;
		$sessionCart->order_language 						= $this->order_language;
		$sessionCart->cartData 								= $this->cartData;
		$sessionCart->lists 									= $this->lists;

		$sessionCart->pricesCurrency						= $this->pricesCurrency;
		$sessionCart->paymentCurrency						= $this->paymentCurrency;

		//private variables
		$sessionCart->_inCheckOut 							= $this->_inCheckOut;
		$sessionCart->_dataValidated						= $this->_dataValidated;
		$sessionCart->_confirmDone							= $this->_confirmDone;
		$sessionCart->STsameAsBT							= $this->STsameAsBT;

		$session->set('vmcart', serialize($sessionCart),'vm');

	}

	/**
	 * Remove the cart from the session
	 *
	 * @author Max Milbers
	 * @access public
	 */
	public function removeCartFromSession() {
		$session = JFactory::getSession();
		$session->set('vmcart', 0, 'vm');
	}

	public function setDataValidation($valid=false) {
		$this->_dataValidated = $valid;
	}

	public function getDataValidated() {
		return $this->_dataValidated;
	}

	public function getInCheckOut() {
		return $this->_inCheckOut;
	}

	public function setOutOfCheckout(){
		$this->_inCheckOut = false;
		$this->setCartIntoSession();
	}
	
	/**
	 * Set the last error that occured.
	 * This is used on error to pass back to the cart when addJS() is invoked.
	 * @param string $txt Error message
	 * @author Oscar van Eijk
	 */
	private function setError($txt) {
		$this->_lastError = $txt;
	}

	/**
	 * Retrieve the last error message
	 * @return string The last error message that occured
	 * @author Oscar van Eijk
	 */
	public function getError() {
		return ($this->_lastError);
	}

	/**
	 * Add a product to the cart
	 *
	 * @author Max Milbers
	 *
	 * @access public
	 */
	public function add($virtuemart_product_ids=NULL,&$errorMsg='') {
		$mainframe = JFactory::getApplication();
		$updateSession = false;
		$post = JRequest::get('default');

		if(empty($virtuemart_product_ids)){
			$virtuemart_product_ids = JRequest::getVar('virtuemart_product_id', array(), 'default', 'array'); //is sanitized then
		}

		if (empty($virtuemart_product_ids)) {
			vmWarn('COM_VIRTUEMART_CART_ERROR_NO_PRODUCT_IDS');
			return false;
		}

		//VmConfig::$echoDebug=true;
		//vmdebug('cart add',$virtuemart_product_ids,$post);

		$productModel = VmModel::getModel('product');
		$customFieldsModel = VmModel::getModel('customfields');
		//Iterate through the prod_id's and perform an add to cart for each one
		foreach ($virtuemart_product_ids as $p_key => $virtuemart_product_id) {

			$product = false;
			$updateSession = true;
			$productData = array();

			if(empty($virtuemart_product_id)){
				vmWarn('Product could not be added with virtuemart_product_id = 0');
				return false;
			} else {
				$productData['virtuemart_product_id'] = (int)$virtuemart_product_id;
			}

			if(!empty( $post['quantity'][$p_key])){
				$productData['quantity'] = (int) $post['quantity'][$p_key];
			} else {
					continue;
			}

			if(!empty( $post['virtuemart_category_id'][$p_key])){
				$productData['virtuemart_category_id'] = (int) $post['virtuemart_category_id'][$p_key];
			} else {
				$productData['virtuemart_category_id'] = 0;
			}

			if(!empty( $post['customProductData'][$virtuemart_product_id])){
				//$productData['customProductData']
				$customProductData  = $post['customProductData'][$virtuemart_product_id];
			} else {
				$customProductData = array();
			}

			//Now we check if the delivered customProductData is correct and add missing
			if(!$product) $product = $this->getProduct( $productData['virtuemart_product_id'],$productData['quantity']);
			$customfields = $customFieldsModel->getCustomEmbeddedProductCustomFields($product->allIds,0,1);
			$customProductDataTmp=array();
			//VmConfig::$echoDebug=true;
			//vmdebug('cart add product $customProductData',$customProductData);
			foreach($customfields as $customfield){

				if($customfield->is_input==1){
					if(isset($customProductData[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id])){

						if(is_array($customProductData[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id])){
							if(!class_exists('vmFilter'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmfilter.php');
							foreach($customProductData[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id] as &$customData){
								$muh = $customData;
								$value = vmFilter::hl( $customData,array('deny_attribute'=>'*'));
								//to strong
								/* $value = preg_replace('@<[\/\!]*?[^<>]*?>@si','',$value);//remove all html tags  */
								//lets use instead
								$value = JComponentHelper::filterText($value);
								$value = (string)preg_replace('#on[a-z](.+?)\)#si','',$value);//replace start of script onclick() onload()...
								$value = trim(str_replace('"', ' ', $value),"'") ;
								$customData = (string)preg_replace('#^\'#si','',$value);
							}
						}
						$customProductDataTmp[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id] = $customProductData[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id];
					}
					else if(isset($customProductData[$customfield->virtuemart_custom_id])) {
						$customProductDataTmp[$customfield->virtuemart_custom_id] = (int)$customProductData[$customfield->virtuemart_custom_id];

					}
					//	$customProductDataTmp[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id] = $customProductData[$customfield->virtuemart_custom_id][$customfield->virtuemart_customfield_id];
					//}
				} else {
					$customProductDataTmp[$customfield->virtuemart_custom_id] = $customfield->virtuemart_customfield_id;
				}

			}

			//vmdebug('cart add product $customProductDataTmp',$customProductDataTmp);
			$productData['customProductData'] = $customProductDataTmp;

		/*	if(!class_exists('vmCustomPlugin')) require(JPATH_VM_PLUGINS.DS.'vmcustomplugin.php');
			JPluginHelper::importPlugin('vmcustom');
			$dispatcher = JDispatcher::getInstance();
			// on returning false the product have not to be added to cart
			if ( $dispatcher->trigger('plgVmOnAddToCart',array(&$product)) === false )
				continue;
*/
			//vmdebug('cart add',$productData);
			$unsetA = array();
			$found = false;

			//VmConfig::$echoDebug=true;
			//Now lets check if there is already a product stored with the same id, if yes, increase quantity and recalculate
			foreach($this->cartProductsData as $k => &$cartProductData){
				$cartProductData = (array)$cartProductData;
				if(empty($cartProductData['virtuemart_product_id'])){
					$unsetA[] = $k;
				} else {
					if($cartProductData['virtuemart_product_id'] == $productData['virtuemart_product_id']){
						//Okey, the id is already the same, so lets check the customProductData
						if($cartProductData['customProductData'] == $productData['customProductData']){

							vmdebug('Same product variant recognised');
							$cartProductData['quantity'] = $cartProductData['quantity'] + $productData['quantity'];

							if(!$product)$product = $this->getProduct((int) $productData['virtuemart_product_id'],$cartProductData['quantity']);
							if(empty($product->virtuemart_product_id)){
								vmWarn('COM_VIRTUEMART_PRODUCT_NOT_FOUND');
								$unsetA[] = $k;
								//return false;
							} else {

								//$this->checkForQuantities($product, $cartProductData['quantity']);
								//$quantityChecked = true;
							}
							$found = TRUE;
							break;
						} else {
							vmdebug('product variant is different, I add to cart');
						}
					}
				}

				//add products to remove to array
				if($cartProductData['quantity']==0){
					$unsetA[] = $k;
				}

			}

			if(!$found){
				if(!$product)$product = $this->getProduct( $productData['virtuemart_product_id'],$productData['quantity']);
				if(!empty($product->virtuemart_product_id)){
					$this->checkForQuantities($product, $productData['quantity']);
					if(!empty($productData['quantity'])){
						$this->cartProductsData[] = $productData;
					}
				}
			}


			//Remove the products which have quantity=0
			foreach($unsetA as $v){
				unset($this->cartProductsData[$v]);
			}

			$this->prepareCartProducts();
		}
		if ($updateSession== false) return false ;
		// End Iteration through Prod id's
		$this->setCartIntoSession();
		return true;
	}

	/**
	 * Remove a product from the cart
	 *
	 * @author RolandD
	 * @param array $cart_id the cart IDs to remove from the cart
	 * @access public
	 */
	public function removeProductCart($prod_id=0) {
		// Check for cart IDs
		if (empty($prod_id))
		$prod_id = JRequest::getInt('cart_virtuemart_product_id');
		if(isset($this->cartProductsData[$prod_id])){
			unset($this->cartProductsData[$prod_id]);

			$this->setCartIntoSession();
			return true;
		} else {
			vmdebug('removeProductCart $prod_id '.$prod_id,$this->cartProductsData);
			return false;
		}
	}

	/**
	 * Update a product in the cart
	 *
	 * @author Max Milbers
	 * @param array $cart_id the cart IDs to remove from the cart
	 * @access public
	 */
	public function updateProductCart($cart_virtuemart_product_id=0) {

		if (empty($cart_virtuemart_product_id))
		$cart_virtuemart_product_id = JRequest::getInt('cart_virtuemart_product_id');
		if (empty($quantity))
		$quantity = JRequest::getInt('quantity');

		//		foreach($cart_virtuemart_product_ids as $cart_virtuemart_product_id){
		$updated = false;

		if (array_key_exists($cart_virtuemart_product_id, $this->cartProductsData)) {
			if (!empty($quantity)) {
				$productModel = VmModel::getModel('product');

				$product = $productModel -> getProduct($this->cartProductsData[$cart_virtuemart_product_id]['virtuemart_product_id'], $quantity);
				if ($this->checkForQuantities($product, $quantity)) {
					$this->cartProductsData[$cart_virtuemart_product_id]['quantity'] = $quantity;
					$updated = true;
				}
			} else {
				//Todo when quantity is 0,  the product should be removed, maybe necessary to gather in array and execute delete func
				unset($this->cartProductsData[$cart_virtuemart_product_id]);
				$updated = true;
			}
			// Save the cart
			$this->setCartIntoSession();
		}

		if ($updated)
		return true;
		else
		return false;
	}

	/**
	 * Proxy function for getting a product object
	 *
	 * @author Max Milbers
	 * @todo Find out if the include path belongs here? For now it works.
	 * @param int $virtuemart_product_id The product ID to get the object for
	 * @return object The product details object
	 */
	private function getProduct($virtuemart_product_id,$quantity) {
		JModel::addIncludePath(JPATH_VM_ADMINISTRATOR . DS . 'models');
		$model = JModel::getInstance('Product', 'VirtueMartModel');
		//vmdebug('cart getProduct $quantity '.$quantity);
		$product = $model->getProduct($virtuemart_product_id, true, false,true,$quantity);

		if ( VmConfig::get('oncheckout_show_images')){
			$model->addImages($product,1);

		}
		return $product;
	}

	/**
	* Get the category ID from a product ID
	*
	* @author RolandD, Patrick Kohl
	* @access public
	* @return mixed if found the category ID else null
	*/
	public function getCardCategoryId($virtuemart_product_id) {
		$db = JFactory::getDBO();
		$q = 'SELECT `virtuemart_category_id` FROM `#__virtuemart_product_categories` WHERE `virtuemart_product_id` = ' . (int) $virtuemart_product_id . ' LIMIT 1';
		$db->setQuery($q);
		return $db->loadResult();
	}

	/**
	 * Validate the coupon code. If ok,. set it in the cart
	 * @param string $coupon_code Coupon code as entered by the user
	 * @author Oscar van Eijk
	 * TODO Change the coupon total/used in DB ?
	 * @access public
	 * @return string On error the message text, otherwise an empty string
	 */
	public function setCouponCode($coupon_code) {
		if (!class_exists('CouponHelper')) {
			require(JPATH_VM_SITE . DS . 'helpers' . DS . 'coupon.php');
		}
		$prices = $this->getCartPrices();
		$msg = CouponHelper::ValidateCouponCode($coupon_code, $prices['salesPrice']);
		if (!empty($msg)) {
			$this->couponCode = '';
			$this->setCartIntoSession();
			return $msg;
		}
		$this->couponCode = $coupon_code;
		$this->setCartIntoSession();
		return JText::_('COM_VIRTUEMART_CART_COUPON_VALID');
	}

	/**
	 * Check the selected shipment data and store the info in the cart
	 * @param integer $shipment_id Shipment ID taken from the form data
	 * @author Oscar van Eijk
	 */
	public function setShipment($shipment_id) {

	    $this->virtuemart_shipmentmethod_id = $shipment_id;
	    $this->setCartIntoSession();

	}

	public function setPaymentMethod($virtuemart_paymentmethod_id) {
		$this->virtuemart_paymentmethod_id = $virtuemart_paymentmethod_id;
		$this->setCartIntoSession();
	}

	function confirmDone() {

		$this->checkoutData();
		if ($this->_dataValidated) {
			$this->_confirmDone = true;
			$this->confirmedOrder();
		} else {
			$mainframe = JFactory::getApplication();
			$mainframe->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart'), JText::_('COM_VIRTUEMART_CART_CHECKOUT_DATA_NOT_VALID'));
		}
	}

	function checkout($redirect=true) {

		$this->checkoutData($redirect);
		if ($this->_dataValidated && $redirect) {
			$mainframe = JFactory::getApplication();
			$mainframe->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart'), JText::_('COM_VIRTUEMART_CART_CHECKOUT_DONE_CONFIRM_ORDER'));
		}
	}

	private function redirecter($relUrl,$redirectMsg){

		$this->_dataValidated = false;
		$app = JFactory::getApplication();
		if($this->_redirect ){
			$this->setCartIntoSession();
			$app->redirect(JRoute::_($relUrl,$this->useXHTML,$this->useSSL), $redirectMsg);
		} else {
			$this->_inCheckOut = false;
			$this->setCartIntoSession();
			return false;
		}
	}

	private function checkoutData($redirect = true) {

		$this->_redirect = $redirect;
		$this->_inCheckOut = true;

		$this->tosAccepted = JRequest::getInt('tosAccepted', $this->tosAccepted);
		$this->STsameAsBT = JRequest::getInt('STsameAsBT', $this->STsameAsBT);
		$this->customer_comment = JRequest::getVar('customer_comment', $this->customer_comment);

		// no HTML TAGS but permit all alphabet
		$value =	preg_replace('@<[\/\!]*?[^<>]*?>@si','',$this->customer_comment);//remove all html tags
		$value =	(string)preg_replace('#on[a-z](.+?)\)#si','',$value);//replace start of script onclick() onload()...
		$value = trim(str_replace('"', ' ', $value),"'") ;
		$this->customer_comment=	(string)preg_replace('#^\'#si','',$value);//replace ' at start

		if (($this->selected_shipto = JRequest::getVar('shipto', NULL)) !== NULL) {
			JModel::addIncludePath(JPATH_VM_ADMINISTRATOR . DS . 'models');
			$userModel = JModel::getInstance('user', 'VirtueMartModel');
			$stData = $userModel->getUserAddressList(0, 'ST', $this->selected_shipto);
			$this->validateUserData('ST', $stData[0]);
		}

		$this->cartData = $this->prepareCartData();
		//$this->prepareCartPrice( );


		///if (count($this->products) == 0) {
		if (count($this->cartProductsData) == 0) {
			return $this->redirecter('index.php?option=com_virtuemart', JText::_('COM_VIRTUEMART_CART_NO_PRODUCT'));
		} else {
			$redirectMsg = $this->prepareCartProducts();
		/*	foreach ($this->products as $product) {
				$redirectMsg = $this->checkForQuantities($product, $product->quantity);*/
				if (!$redirectMsg) {
					return $this->redirecter('index.php?option=com_virtuemart&view=cart', $redirectMsg);
				}
			//}*/
		}

		// Check if a minimun purchase value is set
		if (($redirectMsg = $this->checkPurchaseValue()) != NULL) {
			return $this->redirecter('index.php?option=com_virtuemart&view=cart' , $redirectMsg);
		}

		//But we check the data again to be sure
		if (empty($this->BT)) {
			$redirectMsg = '';
			return $this->redirecter('index.php?option=com_virtuemart&view=user&task=editaddresscheckout&addrtype=BT' , $redirectMsg);
		} else {
			$redirectMsg = self::validateUserData();
			if (!$redirectMsg) {
				return $this->redirecter('index.php?option=com_virtuemart&view=user&task=editaddresscheckout&addrtype=BT' , '');
			}
		}

		if($this->STsameAsBT!==0){
			$this->ST = $this->BT;
		} else {
			//Only when there is an ST data, test if all necessary fields are filled
			if (!empty($this->ST)) {
				$redirectMsg = self::validateUserData('ST');
				if (!$redirectMsg) {
					return $this->redirecter('index.php?option=com_virtuemart&view=user&task=editaddresscheckout&addrtype=ST' , '');
				}
			}
		}

		// Test Coupon
		if (!empty($this->couponCode)) {
			$prices = $this->getCartPrices();
			if (!class_exists('CouponHelper')) {
				require(JPATH_VM_SITE . DS . 'helpers' . DS . 'coupon.php');
			}
			if(self::$_triesValidateCoupon<8){
				$redirectMsg = CouponHelper::ValidateCouponCode($this->couponCode, $prices['salesPrice']);
			} else{
				$redirectMsg = JText::_('COM_VIRTUEMART_CART_COUPON_TOO_MANY_TRIES');
			}
			self::$_triesValidateCoupon++;// = self::$_triesValidateCoupon + 1;
			if (!empty($redirectMsg)) {

				$this->couponCode = '';
				return $this->redirecter('index.php?option=com_virtuemart&view=cart&task=edit_coupon' , $redirectMsg);
			}
		}
		$redirectMsg = '';

		//Test Shipment and show shipment plugin
		if (empty($this->virtuemart_shipmentmethod_id)) {
			return $this->redirecter('index.php?option=com_virtuemart&view=cart&task=edit_shipment' , $redirectMsg);
		} else {
			if (!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');
			JPluginHelper::importPlugin('vmshipment');
			//Add a hook here for other shipment methods, checking the data of the choosed plugin
			$dispatcher = JDispatcher::getInstance();
			$retValues = $dispatcher->trigger('plgVmOnCheckoutCheckDataShipment', array(  $this));

			foreach ($retValues as $retVal) {
				if ($retVal === true) {
					break; // Plugin completed succesfull; nothing else to do
				} elseif ($retVal === false) {
					// Missing data, ask for it (again)
					return $this->redirecter('index.php?option=com_virtuemart&view=cart&task=edit_shipment' , $redirectMsg);
					// 	NOTE: inactive plugins will always return null, so that value cannot be used for anything else!
				}
			}
		}
		
		//Test Payment and show payment plugin
		if($this->pricesUnformatted['salesPrice']>0.0){
				if (empty($this->virtuemart_paymentmethod_id)) {
				return $this->redirecter('index.php?option=com_virtuemart&view=cart&task=editpayment' , $redirectMsg);
			} else {
				if(!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS.DS.'vmpsplugin.php');
				JPluginHelper::importPlugin('vmpayment');
				//Add a hook here for other payment methods, checking the data of the choosed plugin
				$dispatcher = JDispatcher::getInstance();
				$retValues = $dispatcher->trigger('plgVmOnCheckoutCheckDataPayment', array( $this));

				foreach ($retValues as $retVal) {
					if ($retVal === true) {
						break; // Plugin completed succesful; nothing else to do
					} elseif ($retVal === false) {
						// Missing data, ask for it (again)
						return $this->redirecter('index.php?option=com_virtuemart&view=cart&task=editpayment' , $redirectMsg);
						// 	NOTE: inactive plugins will always return null, so that value cannot be used for anything else!
					}
				}
			}
		}
		

		if (empty($this->tosAccepted)) {

			$userFieldsModel = VmModel::getModel('Userfields');

			$required = $userFieldsModel->getIfRequired('agreed');
			if(!empty($required)){
				$redirectMsg = JText::_('COM_VIRTUEMART_CART_PLEASE_ACCEPT_TOS');
				return $this->redirecter('index.php?option=com_virtuemart&view=cart' , $redirectMsg);
			}
		}

		if(VmConfig::get('oncheckout_only_registered',0)) {
			$currentUser = JFactory::getUser();
			if(empty($currentUser->id)){
				$redirectMsg = JText::_('COM_VIRTUEMART_CART_ONLY_REGISTERED');
				return $this->redirecter('index.php?option=com_virtuemart&view=user&task=editaddresscheckout&addrtype=BT' , $redirectMsg);
			}
		 }

		//Show cart and checkout data overview
		$this->_inCheckOut = false;
		$this->_dataValidated = true;

		$this->setCartIntoSession();

		return true;
	}

	/**
	 * Check if a minimum purchase value for this order has been set, and if so, if the current
	 * value is equal or hight than that value.
	 * @author Oscar van Eijk
	 * @return An error message when a minimum value was set that was not eached, null otherwise
	 */
	private function checkPurchaseValue() {

		$vendor = VmModel::getModel('vendor');
		$vendor->setId($this->vendorId);
		$store = $vendor->getVendor();
		if ($store->vendor_min_pov > 0) {
			$prices = $this->getCartPrices();
			if ($prices['salesPrice'] < $store->vendor_min_pov) {
				if (!class_exists('CurrencyDisplay'))
				require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'currencydisplay.php');
				$currency = CurrencyDisplay::getInstance();
				return JText::sprintf('COM_VIRTUEMART_CART_MIN_PURCHASE', $currency->priceDisplay($store->vendor_min_pov));
			}
		}
		return NULL;
	}

	/**
	 * Test userdata if valid
	 *
	 * @author Max Milbers
	 * @param String if BT or ST
	 * @param Object If given, an object with data address data that must be formatted to an array
	 * @return redirectMsg, if there is a redirectMsg, the redirect should be executed after
	 */
	private function validateUserData($type='BT', $obj = NULL) {

		if(empty($obj)){
			$obj = $this->{$type};
		}

		$usersModel = VmModel::getModel('user');
		return $usersModel->validateUserData($obj,$type);

	}

	/**
	 * This function is called, when the order is confirmed by the shopper.
	 *
	 * Here are the last checks done by payment plugins.
	 * The mails are created and send to vendor and shopper
	 * will show the orderdone page (thank you page)
	 *
	 */
	private function confirmedOrder() {

		//Just to prevent direct call
		if ($this->_dataValidated && $this->_confirmDone) {

			$orderModel = VmModel::getModel('orders');

			if (($orderID = $orderModel->createOrderFromCart()) === false) {
				$mainframe = JFactory::getApplication();
				JError::raiseWarning(500, 'No order created '.$orderModel->getError());
				$mainframe->redirect(JRoute::_('index.php?option=com_virtuemart&view=cart') );
			}
			$this->virtuemart_order_id = $orderID;
			$order= $orderModel->getOrder($orderID);

			$dispatcher = JDispatcher::getInstance();

			JPluginHelper::importPlugin('vmshipment');
			JPluginHelper::importPlugin('vmcustom');
			JPluginHelper::importPlugin('vmpayment');
			JPluginHelper::importPlugin('vmcalculation');
			$returnValues = $dispatcher->trigger('plgVmConfirmedOrder', array($this, $order));
			// may be redirect is done by the payment plugin (eg: paypal)
			// if payment plugin echos a form, false = nothing happen, true= echo form ,
			// 1 = cart should be emptied, 0 cart should not be emptied

		}


	}

	/**
	 * emptyCart: Used for payment handling.
	 *
	 * @author Valerie Cartan Isaksen
	 *
	 */
	public function emptyCart(){

		self::emptyCartValues($this);

		$this->setCartIntoSession();
	}

	/**
	 * emptyCart: Used for payment handling.
	 *
	 * @author Valerie Cartan Isaksen
	 *
	 */
	static public function emptyCartValues($cartData){

		//We delete the old stuff
		$cartData->products = array();
		$cartData->_inCheckOut = false;
		$cartData->_dataValidated = false;
		$cartData->_confirmDone = false;
		$cartData->customer_comment = '';
		$cartData->couponCode = '';
		$cartData->order_language = '';
		$cartData->tosAccepted = NULL;
		$cartData->virtuemart_shipmentmethod_id = 0; //OSP 2012-03-14
		$cartData->virtuemart_paymentmethod_id = 0;
		$cartData->order_number=NULL;

	}

	function saveAddressInCart($data, $type, $putIntoSession = true) {

		// VirtueMartModelUserfields::getUserFields() won't work
		if(!class_exists('VirtueMartModelUserfields')) require(JPATH_VM_ADMINISTRATOR.DS.'models'.DS.'userfields.php' );
		$userFieldsModel = VmModel::getModel('userfields');
		$prefix = '';

		$prepareUserFields = $userFieldsModel->getUserFieldsFor('cart',$type);

		//STaddress may be obsolete
		if ($type == 'STaddress' || $type =='ST') {
			$prefix = 'shipto_';

		} else { // BT
			if(is_array($data)){
				if(!empty($data['agreed'])){
					$this->tosAccepted = $data['agreed'];
				}

				if(empty($data['email'])){
					$address['email'] = JFactory::getUser()->email;
				}

			} else {
				if(!empty($data->agreed)){
					$this->tosAccepted = $data->agreed;
				}

				if(empty($data->email)){
					$jUser = JFactory::getUser();
					$address['email'] = $jUser->email;
				}
			}
		}

		$address = array();
		if(is_array($data)){
			foreach ($prepareUserFields as $fld) {
				if(!empty($fld->name)){
					$name = $fld->name;
					if($fld->required){
						if(!empty($data[$prefix.$name])){
							$address[$name] = $data[$prefix.$name];
						} else {
							$address[$name] = $this->{$type}[$name];
						}
					} else {
						if(!empty($data[$prefix.$name])){
							$address[$name] = $data[$prefix.$name];
						} else {
							$address[$name] = '';
							//vmdebug('saveAddressInCart the $data[$prefix.$name] ($prefix.$name = '.$prefix.$name.' is empty');
						}
					}
				}
			}

		} else {
			foreach ($prepareUserFields as $fld) {
				if(!empty($fld->name)){
					$name = $fld->name;
					if($fld->required){
						if(!empty($data->{$prefix.$name})){
							$address[$name] = $data->{$prefix.$name};
						} else {
							$address[$name] = $this->{$type}[$name];
						}
					}else {
						$address[$name] = $data->{$prefix.$name};
					}
				}
			}

		}

		//dont store passwords in the session
		unset($address['password']);
		unset($address['password2']);

		$this->{$type} = $address;


		if($putIntoSession){
			$this->setCartIntoSession();
		}

	}

	/*
	 * CheckAutomaticSelectedShipment
	* If only one shipment is available for this amount, then automatically select it
	*
	* @author Valérie Isaksen
	*/
	function CheckAutomaticSelectedShipment($cart_prices, $checkAutomaticSelected ) {

		if(count($this->products)==0 ) {
			return false;
		}
		$nbShipment = 0;
		$virtuemart_shipmentmethod_id=0;
		if (!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');

		JPluginHelper::importPlugin('vmshipment');
		if (VmConfig::get('automatic_shipment',1) && $checkAutomaticSelected) {
		    $shipCounter=0;
			$dispatcher = JDispatcher::getInstance();
			$returnValues = $dispatcher->trigger('plgVmOnCheckAutomaticSelectedShipment', array(  $this,$cart_prices, &$shipCounter));
			foreach ($returnValues as $returnValue) {
				 if ( isset($returnValue )) {
					$nbShipment ++;
					if ($returnValue) $virtuemart_shipmentmethod_id = $returnValue;
				}
			}
			if ($nbShipment==1 && $virtuemart_shipmentmethod_id) {
				$this->virtuemart_shipmentmethod_id = $virtuemart_shipmentmethod_id;
				$this->automaticSelectedShipment=true;
				$this->setCartIntoSession();
				return true;
			} else {
				$this->automaticSelectedShipment=false;
				$this->setCartIntoSession();
				return false;
			}
		} else {
			return false;
		}

	}

	/*
	 * CheckAutomaticSelectedPayment
	* If only one payment is available for this amount, then automatically select it
	*
	* @author Valérie Isaksen
	*/
	function CheckAutomaticSelectedPayment($cart_prices,  $checkAutomaticSelected=true) {

		$nbPayment = 0;
		$virtuemart_paymentmethod_id=0;
		if(!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS.DS.'vmpsplugin.php');
		JPluginHelper::importPlugin('vmpayment');
		if (VmConfig::get('automatic_payment',1) && $checkAutomaticSelected ) {
			$dispatcher = JDispatcher::getInstance();
			$paymentCounter=0;
			$returnValues = $dispatcher->trigger('plgVmOnCheckAutomaticSelectedPayment', array( $this, $cart_prices, &$paymentCounter));
			foreach ($returnValues as $returnValue) {
				  if ( isset($returnValue )) {
					 $nbPayment++;
					    if($returnValue) $virtuemart_paymentmethod_id = $returnValue;
				     }
			    }
			if ($nbPayment==1 && $virtuemart_paymentmethod_id) {
				$this->virtuemart_paymentmethod_id = $virtuemart_paymentmethod_id;
				$this->automaticSelectedPayment=true;
				$this->setCartIntoSession();
				return true;
			} else {
				$this->automaticSelectedPayment=false;
				$this->setCartIntoSession();
				return false;
			}
		} else {
			return false;
		}

	}

	/*
	 * CheckShipmentIsValid:
	* check if the selected shipment is still valid for this new cart
	*
	* @author Valerie Isaksen
	*/
	function CheckShipmentIsValid() {
		if ($this->virtuemart_shipmentmethod_id===0)
		return;
		$shipmentValid = false;
		if (!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');

		JPluginHelper::importPlugin('vmshipment');
		$dispatcher = JDispatcher::getInstance();
		$returnValues = $dispatcher->trigger('plgVmOnCheckShipmentIsValid', array( $this));
		foreach ($returnValues as $returnValue) {
			$shipmentValid += $returnValue;
		}
		if (!$shipmentValid) {
			$this->virtuemart_shipmentmethod_id = 0;
			$this->setCartIntoSession();
		}
	}

	/*
	 * Prepare the datas for cart/mail views
	* set product, price, user, adress and vendor as Object
	* @author Patrick Kohl
	* @author Valerie Isaksen
	*/
	function prepareCartViewData(){

		// Get the products for the cart
		$this->cartData = $this->prepareCartData();


		$this->prepareAddressDataInCart();

		$vendorModel = VmModel::getModel('vendor');
		$this->vendor = $vendorModel->getVendor(1);
		$vendorModel->addImages($this->vendor,1);

	}

	/**
	 * Function Description
	 *
	 * @author Max Milbers
	 * @access public
	 * @param array $cart the cart to get the products for
	 * @return array of product objects
	 */
// 	$this->pricesUnformatted = $product_prices;

	public function getCartPrices($checkAutomaticSelected=true) {

		if(!class_exists('calculationHelper')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'calculationh.php');
		$calculator = calculationHelper::getInstance();

		$this->pricesUnformatted = $calculator->getCheckoutPrices($this, $checkAutomaticSelected);

		return $this->pricesUnformatted;
	}

	function prepareCartProducts(){

		if(count($this->cartProductsData)>0){
			$productsModel = VmModel::getModel('product');
			$this->totalProduct = 0;
			$this->productsQuantity = array();
			foreach($this->cartProductsData as $k =>$productdata){
				$productdata = (array)$productdata;
				if(isset($productdata['virtuemart_product_id'])){
					if(empty($productdata['virtuemart_product_id'])){
						unset($this->cartProductsData[$k]);
						continue;
					}
					$productTemp = $productsModel->getProduct($productdata['virtuemart_product_id']);
					if(empty($productTemp->virtuemart_product_id)){
						vmError('prepareCartData virtuemart_product_id is empty','The product is no longer available');
						continue;
					}
					//Very important! must be cloned, else all products with same id get the same productCustomData due the product cache
					$product = clone($productTemp);
					$product -> customProductData = $productdata['customProductData'];
					$product -> quantity = (int)$productdata['quantity'];

					//TODO for what we need the category id? atm it is empty anyway.
					//$product -> virtuemart_category_id = $productdata['virtuemart_category_id'];
					//$product->virtuemart_category_id = $this->getCardCategoryId($product->virtuemart_product_id);

					// No full link because Mail want absolute path and in shop is better relative path
					$product->url = JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id='.$product->virtuemart_product_id.'&virtuemart_category_id='.$product->virtuemart_category_id);//JHTML::link($url, $product->product_name);
					$product->cart_item_id = $k ;

					$this->products[$k] = $product;
					$this->totalProduct += $product -> quantity;

					if(isset($this->productsQuantity[$product->virtuemart_product_id])){
						$this->productsQuantity[$product->virtuemart_product_id] += $product -> quantity;
					} else {
						$this->productsQuantity[$product->virtuemart_product_id] = $product -> quantity;
					}

					$product = null;
				} else {
					vmError('prepareCartData $productdata[virtuemart_product_id] was empty');
				}
			}
		} else {
			//vmdebug('The array count($this->cartProductsData) is 0 ',$this->cartProductsData);
		}


		if(!class_exists('calculationHelper')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'calculationh.php');
		$calculator = calculationHelper::getInstance();

		$this->pricesUnformatted = $calculator->getCheckoutPrices($this);
		return $this->checkCartQuantities();

	}

	private function checkCartQuantities(){

		if(!isset($this->productsQuantity)) return false;
		foreach($this->productsQuantity as $productId => $quantity){
			foreach($this->products as $product){
				if($product->virtuemart_product_id == $productId) break;
			}

			$enough = $this->checkForQuantities($product,$quantity);
			if(!$enough) return FALSE;
		}

		return TRUE;
	}

	/** Checks if the quantity is correct
	 *
	 * @author Max Milbers
	 */
	private function checkForQuantities($product, &$quantity=0) {

		$stockhandle = VmConfig::get('stockhandle','none');
		$mainframe = JFactory::getApplication();
		// Check for a valid quantity
		if (!is_numeric( $quantity)) {
			$errorMsg = JText::_('COM_VIRTUEMART_CART_ERROR_NO_VALID_QUANTITY', false);
			$this->setError($errorMsg);
			vmInfo($errorMsg,$product->product_name);
			return false;
		}
		// Check for negative quantity
		if ($quantity < 1) {
			$errorMsg = JText::_('COM_VIRTUEMART_CART_ERROR_NO_VALID_QUANTITY', false);
			$this->setError($errorMsg);
			vmInfo($errorMsg,$product->product_name);
			return false;
		}

		// Check to see if checking stock quantity
		if ($stockhandle!='none' && $stockhandle!='risetime') {

			$productsleft = $product->product_in_stock - $product->product_ordered;

			// TODO $productsleft = $product->product_in_stock - $product->product_ordered - $quantityincart ;
			if ($quantity > $productsleft ){
				vmdebug('my products left '.$productsleft.' and my quantity '.$quantity);
				if($productsleft>0 and $stockhandle=='disableadd'){
					$quantity = $productsleft;
					$errorMsg = JText::sprintf('COM_VIRTUEMART_CART_PRODUCT_OUT_OF_QUANTITY',$quantity);
					$this->setError($errorMsg);
					vmInfo($errorMsg.' '.$product->product_name);
					// $mainframe->enqueueMessage($errorMsg);
				} else {
					$errorMsg = JText::_('COM_VIRTUEMART_CART_PRODUCT_OUT_OF_STOCK');
					$this->setError($errorMsg); // Private error retrieved with getError is used only by addJS, so only the latest is fine
					// todo better key string
					vmInfo($errorMsg. ' '.$product->product_name);
					// $mainframe->enqueueMessage($errorMsg);
					return false;
				}
			}
		}

		// Check for the minimum and maximum quantities
		$min = $product->min_order_level;
		$max = $product->max_order_level;
		if ($min != 0 && $quantity < $min) {
			//			$this->_error[] = 'Quantity reached not minimum';
			//$quantity = $min;
			$errorMsg = JText::sprintf('COM_VIRTUEMART_CART_MIN_ORDER', $min);
			$this->setError($errorMsg);
			vmInfo($errorMsg. ' '.$product->product_name);
			return false;
		}
		if ($max != 0 && $quantity > $max) {
			//			$this->_error[] = 'Quantity reached over maximum';
			//$quantity = $max;
			$errorMsg = JText::sprintf('COM_VIRTUEMART_CART_MAX_ORDER', $max);
			$this->setError($errorMsg);
			vmInfo($errorMsg. ' '.$product->product_name);
			return false;
		}

		return true;
	}


	/**
	 *
	 */
	function prepareCartData(){

		$this->totalProduct = 0;
		//vmdebug('$this->cartProductsData',$this->cartProductsData);

		$this->prepareCartProducts();

		//return $this->pricesUnformatted;

		//if (empty($product_prices)) return null;
		if(!class_exists('CurrencyDisplay')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'currencydisplay.php');
		$currency = CurrencyDisplay::getInstance();

		if(!class_exists('calculationHelper')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'calculationh.php');
		$calculator = calculationHelper::getInstance();

		$this->pricesCurrency = $currency->getCurrencyForDisplay();

		if(!class_exists('vmPSPlugin')) require(JPATH_VM_PLUGINS.DS.'vmpsplugin.php');
		JPluginHelper::importPlugin('vmpayment');
		$dispatcher = JDispatcher::getInstance();
		$returnValues = $dispatcher->trigger('plgVmgetPaymentCurrency', array( $this->virtuemart_paymentmethod_id, &$this->paymentCurrency));
		$cartData = $calculator->getCartData();

		//vmdebug('prepareCartData',$this->cartProductsData);
		return $cartData ;

	}

	function prepareAddressDataInCart($type='BT',$new = false){

		$userFieldsModel =VmModel::getModel('Userfields');

		if($new){
			$data = NULL;
		} else {
			$data = (object)$this->$type;
		}

		if($type=='ST'){
			$preFix = 'shipto_';
		} else {
			$preFix = '';
		}

		$addresstype = $type.'address';
		$userFieldsBT = $userFieldsModel->getUserFieldsFor('cart',$type);
		$this->$addresstype = $userFieldsModel->getUserFieldsFilled(
		$userFieldsBT
		,$data
		,$preFix
		);

		if(!empty($this->ST) && $type!=='ST'){
			$data = (object)$this->ST;
			if($new){
				$data = NULL;
			}
			$userFieldsST = $userFieldsModel->getUserFieldsFor('cart','ST');
			$this->STaddress = $userFieldsModel->getUserFieldsFilled(
			$userFieldsST
			,$data
			,$preFix
			);
		}

	}

	function prepareAddressRadioSelection(){

		//Just in case
		$this->user = VmModel::getModel('user');

		$this->userDetails = $this->user->getUser();

		$_addressBT = array();

		// Shipment address(es)
		if($this->user){
			$_addressBT = $this->user->getUserAddressList($this->userDetails->JUser->get('id') , 'BT');

			// Overwrite the address name for display purposes
			if(empty($_addressBT[0])) $_addressBT[0] = new stdClass();
			$_addressBT[0]->address_type_name = JText::_('COM_VIRTUEMART_ACC_BILL_DEF');

			$_addressST = $this->user->getUserAddressList($this->userDetails->JUser->get('id') , 'ST');

		} else {

			$_addressBT[0]->address_type_name = '<a href="index.php'
			.'?option=com_virtuemart'
			.'&view=user'
			.'&task=editaddresscart'
			.'&addrtype=BT'
			. '">'.JText::_('COM_VIRTUEMART_ACC_BILL_DEF').'</a>'.'<br />';
			$_addressST = array();
		}

		$addressList = array_merge(
		array($_addressBT[0])// More BT addresses can exist for shopowners :-(
		, $_addressST );

		if($this->user){
			for ($_i = 0; $_i < count($addressList); $_i++) {
				$addressList[$_i]->address_type_name = '<a href="index.php'
				.'?option=com_virtuemart'
				.'&view=user'
				.'&task=editaddresscart'
				.'&addrtype='.(($_i == 0) ? 'BT' : 'ST')
				.'&virtuemart_userinfo_id='.(empty($addressList[$_i]->virtuemart_userinfo_id)? 0 : $addressList[$_i]->virtuemart_userinfo_id)
				. '">'.$addressList[$_i]->address_type_name.'</a>'.'<br />';
			}

			if(!empty($addressList[0]->virtuemart_userinfo_id)){
				$_selectedAddress = (
				empty($this->_cart->selected_shipto)
				? $addressList[0]->virtuemart_userinfo_id // Defaults to 1st BillTo
				: $this->_cart->selected_shipto
				);
				$this->lists['shipTo'] = JHTML::_('select.radiolist', $addressList, 'shipto', NULL, 'virtuemart_userinfo_id', 'address_type_name', $_selectedAddress);
			}else{
				$_selectedAddress = 0;
				$this->lists['shipTo'] = '';
			}


		} else {
			$_selectedAddress = 0;
			$this->lists['shipTo'] = '';
		}

		$this->lists['billTo'] = empty($addressList[0]->virtuemart_userinfo_id)? 0 : $addressList[0]->virtuemart_userinfo_id;
	}

	// Render the code for Ajax Cart
	function prepareAjaxData(){
		// Added for the zone shipment module
		//$vars["zone_qty"] = 0;
		$this->prepareCartData(false);
		$weight_total = 0;
		$weight_subtotal = 0;

/*		//OSP when prices removed needed to format billTotal for AJAX
		if (!class_exists('CurrencyDisplay'))
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'currencydisplay.php');
		$currency = CurrencyDisplay::getInstance();

		foreach ($this->products as $priceKey=>&$product){

			//$vars["zone_qty"] += $product["quantity"];
			$category_id = $this->getCardCategoryId($product->virtuemart_product_id);
			//Create product URL
			$product->url = JRoute::_('index.php?option=com_virtuemart&view=productdetails&virtuemart_product_id='.$product->virtuemart_product_id.'&virtuemart_category_id='.$category_id);

			//** @todo WEIGHT CALCULATION
			//$weight_subtotal = vmShipmentMethod::get_weight($product["virtuemart_product_id"]) * $product->quantity'];
			//$weight_total += $weight_subtotal;


			//$this->data->totalProduct += $product->quantity ;

		}
		VmConfig::$echoDebug = true;
		vmdebug('Strange, this data? ',$this->data);
		$this->data['totalProduct'] = $this->totalProduct;
		$this->data->products = $this->products;

		return $this->data ;*/
	}

}
