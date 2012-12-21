<?php

/**
 * virtuemart table class, with some additional behaviours.
 *
 *
 * @package	VirtueMart
 * @subpackage Helpers
 * @author Max Milbers
 * @copyright Copyright (c) 2011 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */
defined('_JEXEC') or die();

jimport('joomla.user.user');

/**
 * Replaces JTable with some more advanced functions and fitting to the nooku conventions
 *
 * checked_out = locked_by,checked_time = locked_on
 *
 * Enter description here ...
 * @author Milbo
 *
 */
class VmTable extends JTable{

	protected $_pkey = '';
	protected $_pkeyForm = '';
	protected $_obkeys = array();
	protected $_unique = false;
	protected $_unique_name = array();
	protected $_orderingKey = 'ordering';
	// 	var $_useSlug = false;
	protected $_slugAutoName = '';
	protected $_slugName = '';
	protected $_loggable = false;
	public $_xParams = 0;
	public $_varsToPushParam = array();
	var $_translatable = false;
	protected $_translatableFields = array();

	static $_cache = NULL;

	function __construct( $table, $key, &$db ){

		$this->_tbl		= $table;
		$this->_tbl_key	= $key;
		$this->_db		=& $db;
		$this->_pkey = $key;
		$this->_cache = NULL;
	}

	function setPrimaryKey($key, $keyForm=0){
		$error = JText::sprintf('COM_VIRTUEMART_STRING_ERROR_PRIMARY_KEY', JText::_('COM_VIRTUEMART_' . strtoupper($key)));
		$this->setObligatoryKeys('_pkey', $error);
		$this->_pkey = $key;
		$this->_pkeyForm = empty($keyForm) ? $key : $keyForm;
		$this->$key = 0;
	}

	public function setObligatoryKeys($key){
		$error = JText::sprintf('COM_VIRTUEMART_STRING_ERROR_OBLIGATORY_KEY', JText::_('COM_VIRTUEMART_' . strtoupper($key)));
		$this->_obkeys[$key] = $error;
	}

	public function setUniqueName($name){
		$error = JText::sprintf('COM_VIRTUEMART_STRING_ERROR_NOT_UNIQUE_NAME', JText::_('COM_VIRTUEMART_' . strtoupper($name)));
		$this->_unique = true;
		$this->_obkeys[$name] = $error;
		$this->_unique_name[$name] = $error;
	}

	public function setLoggable(){
		$this->_loggable = true;
		$this->created_on = false;
		$this->created_by = 0;
		$this->modified_on = '';
		$this->modified_by = 0;
	}

	/**
	 *
	 * @author Patrick Kohl,
	 * @author Max Milbers
	 */
	public function setTranslatable($langFields){

		$this->_translatableFields = $langFields;
		$this->_translatableFields['slug'] = 'slug';
		$this->_translatable = true;

		if(!defined('VMLANG')){
			if (!class_exists( 'VmConfig' )) require(JPATH_COMPONENT_ADMINISTRATOR.DS.'helpers'.DS.'config.php');
			VmConfig::loadConfig();
		}
		$this->_langTag = VMLANG;
		$this->_tbl_lang = $this->_tbl.'_'.$this->_langTag;
	}

	public function getTranslatableFields(){

		return $this->_translatableFields;
	}

	public function setLockable(){
		$this->locked_on = '';
		$this->locked_by = 0;
	}

	function setOrderable($key='ordering', $auto=true){
		$this->_orderingKey = $key;
		$this->_orderable = 1;
		$this->_autoOrdering = $auto;
		$this->$key = 0;
	}

	function setSlug($slugAutoName, $key = 'slug'){
		// 		$this->_useSlug = true;
		$this->_slugAutoName = $slugAutoName;
		$this->_slugName = $key;
		$this->$key = '';
		$this->setUniqueName($key);

	}

	var $_tablePreFix = '';
	function setTableShortCut($prefix){
		$this->_tablePreFix = $prefix.'.';
	}

	/**
	 * This function defines a database field as parameter field, which means that some values get injected there
	 * As delimiters are used | for the pair and = for key, value
	 *
	 * @author Max Milbers
	 * @param string $paramsFieldName
	 * @param string $varsToPushParam
	 */
	function setParameterable($paramsFieldName,$varsToPushParam,$overwrite = false){

		//if($this->_xParams===0)
		$this->_xParams = $paramsFieldName;

		if($overwrite){
			$this->_varsToPushParam = $varsToPushParam;
		} else {
			$this->_varsToPushParam = array_merge((array)$varsToPushParam,(array)$this->_varsToPushParam);
		}

		foreach($this->_varsToPushParam as $k=>$v){
			if(!isset($this->$k))$this->$k = $v[0];
		}
		//vmdebug('setParameterable called '.$this->_xParams,$this->_varsToPushParam);
	}

	/**
	 * This function must be
	 * Takes the bounded values at obj of the field $xParams
	 * and adds them as attributs of obj
	 * @param $obj
	 * @param $xParams
	 * @param $varsToPushParam
	 */
	static function bindParameterable(&$obj,$xParams,$varsToPushParam){

		//$paramFields = $obj->$xParams;
		//vmdebug('$obj->_xParams '.$xParams.' $obj->$xParams ',$paramFields);
		if(!empty($obj->$xParams)){

			//	if(strpos($obj->$xParams,'|')!==false){
			$params = explode('|', $obj->$xParams);
			foreach($params as $item){

				$item = explode('=',$item);
				$key = $item[0];
				unset($item[0]);

				$item = implode('=',$item);

				if(!empty($item) && isset($varsToPushParam[$key][1]) ){
					$obj->$key = json_decode($item);
				}
			}
			/*	} else {
					$params = json_decode($obj->$xParams);
					foreach($params as $key=>$item){
						if(!empty($item) && isset($varsToPushParam[$key][1]) ){
							$obj->$key = $item;
						}
						//unset($item[0]);
					}
				}
	*/
		} else {
			if(empty($xParams)){
				vmdebug('There are bindParameterables, but $xParams is emtpy, this is a programmers error '.$obj);
			}
		}

		foreach($varsToPushParam as $key=>$v){
			if(!isset($obj->$key)){
				$obj->$key = $v[0];
			}
		}
	}

	/**
	 * Load the fieldlist
	 */
	public function loadFields()
	{
		$_fieldlist = array();
		$_q = 'SHOW COLUMNS FROM `'.$this->_tbl.'`';
		$this->_db->setQuery($_q);
		$_fields = $this->_db->loadObjectList();
		if (count($_fields) > 0) {
			foreach ($_fields as $key => $_f) {
				$_fieldlist[$_f->Field] = $_f->Default;
			}
			$this->setProperties($_fieldlist);
		}
	}

	/**
	 * Get the columns from database table.
	 *
	 * adjusted to vm2, We use the same, except that the cache is not a global static,.. we use static belonging to table
	 * @return  mixed  An array of the field names, or false if an error occurs.
	 *
	 * @since   11.1
	 */

	public function getFields()
	{
		if ($this->_cache === NULL)
		{
			// Lookup the fields for this table only once.
			$name = $this->_tbl;
			$fields = $this->_db->getTableColumns($name, false);

			if (empty($fields))
			{
				$e = new JException(JText::_('JLIB_DATABASE_ERROR_COLUMNS_NOT_FOUND'));
				$this->setError($e);
				return false;
			}
			$this->_cache = $fields;
		}

		return $this->_cache;
	}


	function checkDataContainsTableFields($from, $ignore=array()){

		if(empty($from))
		return false;
		$fromArray = is_array($from);
		$fromObject = is_object($from);

		if(!$fromArray && !$fromObject){
			vmError(get_class($this) . '::check if data contains table fields failed. Invalid from argument <pre>' . print_r($from, 1) . '</pre>');
			return false;
		}
		if(!is_array($ignore)){
			$ignore = explode(' ', $ignore);
		}

		foreach($this->getProperties() as $k => $v){
			// internal attributes of an object are ignored
			if(!in_array($k, $ignore)){

				if($fromArray && isset($from[$k])){
					return true;
				}else if($fromObject && isset($from->$k)){
					return true;
				}
			}
		}
		vmdebug('VmTable developer notice, table ' . get_class($this) . ' means that there is no data to store. When you experience that something does not get stored as expected, please write in the forum.virtuemart.net');
		return false;
	}

	function setLoggableFieldsForStore(){

		if($this->_loggable){

			$date = JFactory::getDate();
			$today = $date->toMySQL();
			$user = JFactory::getUser();

			$pkey = $this->_pkey;
			//Lets check if the user is admin or the mainvendor
			if(!class_exists('Permissions')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'permissions.php');
			$admin = Permissions::getInstance()->check('admin');
			if($admin){
				if(empty($this->$pkey) and empty($this->created_on)){
					$this->created_on = $today;
				}

				if(empty($this->$pkey) and empty($this->created_by)){
					$this->created_by = $user->id;
				}
			} else {
				if(empty($this->$pkey)){
					$this->created_on = $today;
					$this->created_by = $user->id;
				}
			}

			//ADDED BY P2 PETER
			if($this->created_on=="0000-00-00 00:00:00"){
				$this->created_on = $this->$today;
			}
			//END ADD

			$this->modified_on = $today;
			$this->modified_by = $user->id;
		}

		if(isset($this->locked_on)){
			//Check if user is allowed to store, then disable or prevent storing
			$this->locked_on = 0;
		}
	}

	/**
	 * Technic to inject params as table attributes
	 * @author Max Milbers
	 * $TableJoins array of table names to add and left join to find ID
	 */
	function load($oid=NULL,$overWriteLoadName=0,$andWhere=0,$tableJoins= array(),$joinKey = 0){

		if( $overWriteLoadName!=0 ){
			$k = $overWriteLoadName;
		} else {
			$k = $this->_pkey;
		}


		if ($oid !== NULL) {
			$this->$k = $oid;
		} else {
			$oid = $this->$k;
		}

		// 		vmdebug('load '.$oid);
		if ($oid === NULL) {
			$oid = 0;
		}
		else if(empty($oid)){
			if(!empty($this->_xParams)){
				foreach($this->_varsToPushParam as $key=>$v){
					if(!isset($this->$key)){
						$this->$key = $v[0];
					}
				}
			}
			return $this;
		}

		//$this->reset();

		$db = $this->getDBO();

		//Version load the tables using JOIN
		if($this->_translatable){
			$mainTable = $this->_tbl.'_'.VMLANG ;
			$select = 'SELECT `'.$mainTable.'`.* ,`'.$this->_tbl.'`.* ';
			$from   = ' FROM `'.$mainTable.'` JOIN '.$this->_tbl.' using (`'.$this->_tbl_key.'`)';
		} else {
			$mainTable = $this->_tbl ;
			$select = 'SELECT `'.$mainTable.'`.* ';
			$from = ' FROM `'.$mainTable .'` ';
		}

		if (count($tableJoins)) {
			if (!$joinKey) $joinKey = $this->_tbl_key ;
			foreach ($tableJoins as $tableId => $table) {
				$select .= ',`'.$table.'`.`'.$tableId.'` ';
				$from   .= ' LEFT JOIN `'.$table.'` on `'.$table.'`.`'.$joinKey.'`=`'. $mainTable .'`.`'.$joinKey.'`';
			}
		}
		//the cast to int here destroyed the query for keys like virtuemart_userinfo_id, so no cast on $oid
		// 		$query = $select.$from.' WHERE '. $mainTable .'.`'.$this->_tbl_key.'` = "'.$oid.'"';
		if ($andWhere===0)
			$andWhere = '';
		$query = $select.$from.' WHERE '. $mainTable .'.`'.$k.'` = "'.$oid.'"'.$andWhere;

		$db->setQuery( $query );

		$result = $db->loadAssoc( );
		$error = $db->getErrorMsg();
		// 		vmdebug('vmtable load '.$db->getQuery(),$result);
		if(!empty($error )){
			vmError('vmTable load x' . $db->getErrorMsg() );
			return false;
		}
		//vmdebug('load',$result );
		if($result){
			$this->bind($result);
			if(!empty($this->_xParams)){
				//Maybe better to use for $this an &
				self::bindParameterable($this,$this->_xParams,$this->_varsToPushParam);
			}

			if (count($tableJoins)) {
				foreach ($tableJoins as $tableId => $table) {
					if(isset( $result[$tableId] )) $this->$tableId = $result[$tableId];
				}
			}
		}


		return $this;

	}


	/**
	 * Technic to inject params as table attributes
	 * @author Max Milbers
	 */
	function store($updateNulls = false){

		$this->setLoggableFieldsForStore();

		$this->storeParams();

		return parent::store($updateNulls);

	}


	function storeParams(){
		if(!empty($this->_xParams)){
			$paramFieldName = $this->_xParams;
			$this->$paramFieldName = '';
			foreach($this->_varsToPushParam as $key=>$v){

				if(isset($this->$key)){
					$this->$paramFieldName .= $key.'='.json_encode($this->$key).'|';
				} else {
					$this->$paramFieldName .= $key.'='.json_encode($v[0]).'|';
				}
				unset($this->$key);
			}
		}
		return true;
	}


	function checkCreateUnique($tbl_name,$name){

		$i = 0;

		while($i<20){

			$tbl_key = $this->_tbl_key;
			$q = 'SELECT `'.$name.'` FROM `'.$tbl_name.'` WHERE `'.$name.'` =  "'.$this->$name.'"  AND `'.$this->_tbl_key.'`!='.$this->$tbl_key ;
			$this->_db->setQuery($q);
			$existingSlugName =$this->_db->loadResult();

			if(!empty($existingSlugName)){
				if($i==0){
					if(JVM_VERSION===1) $this->$name = $this->$name . JFactory::getDate()->toFormat("%Y-%m-%d-%H-%M-%S").'_';
					else $this->$name = $this->$name . JFactory::getDate()->format('Y-m-d-H-i-s').'_';
				} else{
					$this->$name = $this->$name.rand(1,9);
				}
			} else {
				return true;
			}
			$i++;
		}

		return false;

	}


	/**
	 * @author Max Milbers
	 * @param
	 */
	function check(){

		if(!empty($this->_slugAutoName)){

			$slugAutoName = $this->_slugAutoName;
			$slugName = $this->_slugName;

			if(in_array($slugAutoName,$this->_translatableFields)){
				$checkTable = $this->_tbl.'_'.VMLANG;
			} else {
				$checkTable = $this->_tbl;
			}

			if(empty($this->$slugName)){
				// 				vmdebug('table check use _slugAutoName '.$slugAutoName.' '.$slugName);
				if(!empty($this->$slugAutoName)){
					$this->$slugName = $this->$slugAutoName;
				} else {
					vmError('VmTable '.$checkTable.' Check not passed. Neither slug nor obligatory value at '.$slugAutoName.' for auto slug creation is given');
					return false;
				}

			}

			//if (!class_exists('VmMediaHandler')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'mediahandler.php');
			//vmdebug('check $slug before stringURLSafe',$this->$slugName);
			//$this->$slugName = vmFile::makeSafe( $this->$slugName );

			//$lang = JFactory::getLanguage();
			//$this->$slugName = $lang->transliterate($this->$slugName);
			if(JVM_VERSION===1) $this->$slugName = JFilterOutput::stringURLSafe($this->$slugName);
			else $this->$slugName = JApplication::stringURLSafe($this->$slugName);

			$valid = $this->checkCreateUnique($checkTable,$slugName);
			if(!$valid){
				return false;
			}

		}


		foreach($this->_obkeys as $obkeys => $error){
			if(empty($this->$obkeys)){
				if(empty($error)){
					$error = 'Serious error cant save ' . $this->_tbl . ' without ' . $obkeys;
				}else {
				//	$error = get_class($this).' '.JText::_($error);
					$error = get_class($this).' '.$error;
				}
				$this->setError($error);
				vmError($error);
				return false;
			}
		}

		if($this->_unique){
			if(empty($this->_db))$this->_db = JFactory::getDBO();
			foreach($this->_unique_name as $obkeys => $error){

				if(empty($this->$obkeys)){
					// 					vmError(JText::sprintf('COM_VIRTUEMART_NON_UNIQUE_KEY',$this->$obkeys));
					$this->setError($error);
					vmError('Non unique '.$this->_unique_name.' '.$error);
					return false;
				} else {

					$valid = $this->checkCreateUnique($this->_tbl,$obkeys);
					if(!$valid){
						return false;
					}

				}

			}
		}

		if(isset($this->virtuemart_vendor_id )){

			$multix = Vmconfig::get('multix','none');
			//Lets check if the user is admin or the mainvendor
			if(!class_exists('Permissions')) require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'permissions.php');

			$virtuemart_vendor_id = false;
			if( $multix == 'none' and get_class($this)!=='TableVmusers'){

				$this->virtuemart_vendor_id = 1;

			} else {

				$loggedVendorId = Permissions::getInstance()->isSuperVendor();
				$admin = Permissions::getInstance()->check('admin');

				$tbl_key = $this->_tbl_key ;
				if(get_class($this)!=='TableVmusers'){
					$q = 'SELECT `virtuemart_vendor_id` FROM `' . $this->_tbl . '` WHERE `' . $this->_tbl_key.'`="'.$this->$tbl_key.'" ';
					$this->_db->setQuery($q);
					$virtuemart_vendor_id = $this->_db->loadResult();
				} else {
					$q = 'SELECT `virtuemart_vendor_id`,`user_is_vendor` FROM `' . $this->_tbl . '` WHERE `' . $this->_tbl_key.'`="'.$this->$tbl_key.'" ';
					$this->_db->setQuery($q);
					$vmuser = $this->_db->loadRow();

					if($vmuser and count($vmuser)===2){
						$virtuemart_vendor_id = $vmuser[0];
						$user_is_vendor = $vmuser[1];

						if($multix == 'none' ){
							if(empty($user_is_vendor)){
								$this->virtuemart_vendor_id = 0;
							} else {
								$this->virtuemart_vendor_id = 1;
							}
							return true;
						} else {
							if (!$admin) {
								$this->virtuemart_vendor_id = $loggedVendorId;
								return true;
							}
						}
					}
				}

				if(!$admin and !empty($virtuemart_vendor_id) and !empty($loggedVendorId) and $loggedVendorId!=$virtuemart_vendor_id ){

					//vmWarn('COM_VIRTUEMART_NOT_SAME_VENDOR',$loggedVendorId,$virtuemart_vendor_id
					//vmWarn('Stop try to hack this store, you got logged');
					vmdebug('Hacking attempt stopped, logged vendor '.$loggedVendorId.' but data belongs to '.$virtuemart_vendor_id);
					return false;
				} else if (!$admin) {
					if($virtuemart_vendor_id){
						$this->virtuemart_vendor_id = $virtuemart_vendor_id;
						vmdebug('Non admin is storing using loaded vendor_id');
					} else {
						//No id is stored, even users are allowed to use for the storage and vendorId, no change
					}

				} else if (!empty($virtuemart_vendor_id) and $loggedVendorId!=$virtuemart_vendor_id) {
					vmInfo('Admin with vendor id '.$loggedVendorId.' is using for storing vendor id '.$this->virtuemart_vendor_id);
					vmdebug('Admin with vendor id '.$loggedVendorId.' is using for storing vendor id '.$this->virtuemart_vendor_id);
					$this->virtuemart_vendor_id = $virtuemart_vendor_id;
				}
			}

			//tables to consider for multivendor
			//if(get_class($this)!== 'TableOrders' and get_class($this)!== 'TableInvoices' and get_class($this)!== 'TableOrder_items'){
		}

		return true;
	}

	/**
	 * As shortcat, Important the & MUST be there, even in php5.3
	 *
	 * @author Max Milbers
	 * @param array/obj $data input data as assoc array or obj
	 * @param boolean $preload You can preload the data here too preserve not updated data
	 * @return array/obj $data the updated data
	 */
	public function bindChecknStore(&$data,$preload=false){

		$tblKey = $this->_tbl_key;
		$ok = true;
		if($this->_translatable){
			if(!class_exists('VmTableData'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmtabledata.php');
			$db = JFactory::getDBO();

			$langTable = new VmTableData($this->_tbl_lang,$tblKey,$db);
			$langTable->setPrimaryKey($tblKey);
			$langData = array();
			$langObKeys = array();
			$langUniqueKeys = array();

			if(is_object($data)){

				foreach($this->_translatableFields as $name){
					if(!empty($data->$name)){
						$langData[$name] = $data->$name;
					} else {
						$langData[$name] = '';
					}
					unset($this->$name);

					if(!empty($this->_unique_name[$name])){
						$langUniqueKeys[$name] = JText::sprintf('COM_VIRTUEMART_STRING_ERROR_NOT_UNIQUE_NAME', JText::_('COM_VIRTUEMART_' . strtoupper($name)));
						unset($this->_unique_name[$name]);
						$langObKeys[$name] = JText::sprintf('COM_VIRTUEMART_STRING_ERROR_OBLIGATORY_KEY', JText::_('COM_VIRTUEMART_' . strtoupper($name)));
						unset($this->_obkeys[$name]);
					}

					if(!empty($this->_obkeys[$name])){
						$langObKeys[$name] = JText::sprintf('COM_VIRTUEMART_STRING_ERROR_OBLIGATORY_KEY', JText::_('COM_VIRTUEMART_' . strtoupper($name)));
						unset($this->_obkeys[$name]);
					}

				}
				// 				$langTable->$tblKey = $data->$tblKey;
			} else {
				foreach($this->_translatableFields as $name){
					if(!empty($data[$name])){
						$langData[$name] = $data[$name];
					} else {
						$langData[$name] = '';
					}
					unset($this->$name);

					if(!empty($this->_unique_name[$name])){
						$langUniqueKeys[$name] = JText::sprintf('COM_VIRTUEMART_STRING_ERROR_NOT_UNIQUE_NAME', JText::_('COM_VIRTUEMART_' . strtoupper($name)));
						unset($this->_unique_name[$name]);
						$langObKeys[$name] = JText::sprintf('COM_VIRTUEMART_STRING_ERROR_OBLIGATORY_KEY', JText::_('COM_VIRTUEMART_' . strtoupper($name)));
						unset($this->_obkeys[$name]);
					}

					if(!empty($this->_obkeys[$name])){
						$langObKeys[$name] = JText::sprintf('COM_VIRTUEMART_STRING_ERROR_OBLIGATORY_KEY', JText::_('COM_VIRTUEMART_' . strtoupper($name)));
						unset($this->_obkeys[$name]);
					}

				}
				// 				$langTable->$tblKey = $data[$tblKey];
			}

			$langTable->_unique_name = $langUniqueKeys;
			$langTable->_obkeys = $langObKeys;

			$langTable->_slugAutoName = $this->_slugAutoName;
			unset($this->_slugAutoName);

			$langTable->_slugName = 'slug';
			unset($this->_slugName);

			$langTable->setProperties($langData);
			$langTable->_translatable = false;

			//We must check the langtable BEFORE we store the normal table, cause the langtable is often defining if there are enough data to store it (for exmple the name)

			if($ok){
				//vmdebug('my langtable before bind',$langTable->id);
				if(!$langTable->bind($data)){
					$ok = false;
					$msg = 'bind';
					// 			vmdebug('Problem in bind '.get_class($this).' '.$this->_db->getErrorMsg());
					vmdebug('Problem in bind '.get_class($this).' ');
				}
			}

			if($ok){
				if(!$langTable->check()){
					$ok = false;
					vmdebug('Check returned false '.get_class($langTable).' '.$this->_tbl.' '.$langTable->_db->getErrorMsg());
				}
			}

			if($ok){
				$this->bindChecknStoreNoLang($data,$preload);

				$langTable->$tblKey = !empty($this->$tblKey) ? $this->$tblKey : 0;
				vmdebug('bindChecknStoreNoLang my $tblKey '.$tblKey.' '.$langTable->$tblKey);
				if($ok and $preload){
					if(!empty($langTable->$tblKey)){
						$id = $langTable->$tblKey;
						if(!$langTable->load($id)){
							$ok = false;
							vmdebug('Preloading of language table failed, no id given, cannot store '.$this->_tbl);
						}
					}
				}

				if($ok){
					if(!$langTable->bind($data)){
						$ok = false;
						vmdebug('Problem in bind '.get_class($this).' ');
					}
				}

				if($ok){
					if(!$langTable->check()){
						$ok = false;
						vmdebug('Check returned false '.get_class($langTable).' '.$this->_tbl.' '.$langTable->_db->getErrorMsg());
					}
				}

				if($ok){
					if(!$langTable->store()){
						$ok = false;
						// $msg .= ' store';
						vmdebug('Problem in store with langtable '.get_class($langTable).' with '.$tblKey.' = '.$this->$tblKey.' '.$langTable->_db->getErrorMsg());
					}
				}
			}


		} else {

			if(!$this->bindChecknStoreNoLang($data,$preload)){
				$ok= false;
			}
		}

		return $ok;
	}


	function bindChecknStoreNoLang(&$data,$preload=false){

		$tblKey = $this->_tbl_key;

		if($preload){
			if(is_object($data)){
				if(!empty($data->$tblKey)){
					$this->load($data->$tblKey);
				}
			}else {
				if(!empty($data[$tblKey])){
					$this->load($data[$tblKey]);
				}
			}

			if($this->_translatable){
				foreach( $this->_translatableFields as $name){
					unset($this->$name);
				}
			}
			//vmdebug('bindChecknStoreNoLang language unloaded, why?');
		}

		$ok = true;
		$msg = '';

		if(!$this->bind($data)){
			$ok = false;
			$msg = 'bind';
			// 			vmdebug('Problem in bind '.get_class($this).' '.$this->_db->getErrorMsg());
			vmdebug('Problem in bind '.get_class($this).' ');
		}

		if($ok){
			if(!$this->checkDataContainsTableFields($data)){
				$ok = false;
				//    			$msg .= ' developer notice:: checkDataContainsTableFields';
			}
		}

		if($ok){
			if(!$this->check()){
				$ok = false;
				$msg .= ' check';
				vmdebug('Check no lang returned false '.get_class($this).' '.$this->_db->getErrorMsg());
				return false;
			}
		}

		if($ok){
			if(!$this->store()){
				$ok = false;
				$msg .= ' store';
				vmdebug('Problem in store '.get_class($this).' '.$this->_db->getErrorMsg());
				return false;
			}
		}


		if(is_object($data)){
			$data->$tblKey = !empty($this->$tblKey) ? $this->$tblKey : 0;
		}else {
			$data[$tblKey] = !empty($this->$tblKey) ? $this->$tblKey : 0;
		}

		// 		vmdebug('bindChecknStore '.get_class($this).' '.$this->_db->getErrorMsg());
		//This should return $ok and not the data, because it is already updated due use of reference
		return $data;
	}

	/**
	 * Description
	 *
	 * @author Joomla Team, Max Milbers
	 * @access public
	 * @param $dirn
	 * @param $where
	 */
	function move($dirn, $where='', $orderingkey=0){

		if(!empty($orderingkey))
		$this->_orderingKey = $orderingkey;

		if(!in_array($this->_orderingKey, array_keys($this->getProperties()))){
			vmError(get_class($this) . ' does not support ordering');
			return false;
		}

		$k = $this->_tbl_key;

		$orderingKey = $this->_orderingKey;

		$sql = 'SELECT `' . $this->_tbl_key . '`, `' . $this->_orderingKey . '` FROM ' . $this->_tbl;

		if($dirn < 0){
			$sql .= ' WHERE `' . $this->_orderingKey . '` < ' . (int)$this->$orderingKey;
			$sql .= ( $where ? ' AND ' . $where : '');
			$sql .= ' ORDER BY `' . $this->_orderingKey . '` DESC';
		}else if($dirn > 0){
			$sql .= ' WHERE `' . $this->_orderingKey . '` > ' . (int)$this->$orderingKey;
			$sql .= ( $where ? ' AND ' . $where : '');
			$sql .= ' ORDER BY `' . $this->_orderingKey . '`';
		}else {
			$sql .= ' WHERE `' . $this->_orderingKey . '` = ' . (int)$this->$orderingKey;
			$sql .= ( $where ? ' AND ' . $where : '');
			$sql .= ' ORDER BY `' . $this->_orderingKey . '`';
		}

		$this->_db->setQuery($sql, 0, 1);


		$row = NULL;
		$row = $this->_db->loadObject();
		if(isset($row)){
			$query = 'UPDATE ' . $this->_tbl
			. ' SET `' . $this->_orderingKey . '` = ' . (int)$row->$orderingKey
			. ' WHERE ' . $this->_tbl_key . ' = "' . $this->_db->getEscaped($this->$k) . '"'
			;
			$this->_db->setQuery($query);

			if(!$this->_db->query()){
				$err = $this->_db->getErrorMsg();
				JError::raiseError(500, get_class( $this ).':: move isset row $this->$k'.$err);
			}

			$query = 'UPDATE ' . $this->_tbl
			. ' SET `' . $this->_orderingKey . '` = ' . (int)$this->$orderingKey
			. ' WHERE ' . $this->_tbl_key . ' = "' . $this->_db->getEscaped($row->$k) . '"'
			;
			$this->_db->setQuery($query);

			if(!$this->_db->query()){
				$err = $this->_db->getErrorMsg();
				JError::raiseError(500, get_class( $this ).':: move isset row $row->$k'.$err);
			}

			$this->ordering = $row->ordering;
		}else {
			$query = 'UPDATE ' . $this->_tbl
			. ' SET `' . $this->_orderingKey . '` = ' . (int)$this->$orderingKey
			. ' WHERE ' . $this->_tbl_key . ' = "' . $this->_db->getEscaped($this->$k) . '"'
			;
			$this->_db->setQuery($query);

			if(!$this->_db->query()){
				$err = $this->_db->getErrorMsg();
				JError::raiseError(500,  get_class( $this ).':: move update $this->$k'. $err);
			}
		}
		return true;
	}

	/**
	 * Returns the ordering value to place a new item last in its group
	 *
	 * @access public
	 * @param string query WHERE clause for selecting MAX(ordering).
	 */
	function getNextOrder($where='', $orderingkey = 0){

		$where = $this->_db->getEscaped($where);
		$orderingkey = $this->_db->getEscaped($orderingkey);

		if(!empty($orderingkey))
		$this->_orderingKey = $orderingkey;
		if(!in_array($this->_orderingKey, array_keys($this->getProperties()))){
			vmError(get_class($this) . ' does not support ordering');
			return false;
		}

		$query = 'SELECT MAX(`' . $this->_orderingKey . '`)' .
	' FROM ' . $this->_tbl .
		($where ? ' WHERE ' . $where : '');

		$this->_db->setQuery($query);
		$maxord = $this->_db->loadResult();

		if($this->_db->getErrorNum()){
			vmError(get_class($this) . ' getNextOrder ' . $this->_db->getErrorMsg());
			return false;
		}
		return $maxord + 1;
	}

	/**
	 * Compacts the ordering sequence of the selected records
	 *
	 * @access public
	 * @param string Additional where query to limit ordering to a particular subset of records
	 */
	function reorder($where='', $orderingkey = 0){

		$where = $this->_db->getEscaped($where);
		$orderingkey = $this->_db->getEscaped($orderingkey);

		if(!empty($orderingkey))
		$this->_orderingKey = $orderingkey;
		$k = $this->_tbl_key;

		if(!in_array($this->_orderingKey, array_keys($this->getProperties()))){
			vmError(get_class($this) . ' does not support ordering');
			return false;
		}

		if($this->_tbl == '#__content_frontpage'){
			$order2 = ", content_id DESC";
		}else {
			$order2 = "";
		}

		$query = 'SELECT ' . $this->_tbl_key . ', ' . $this->_orderingKey
		. ' FROM ' . $this->_tbl
		. ' WHERE `' . $this->_orderingKey . '` >= 0' . ( $where ? ' AND ' . $where : '' )
		. ' ORDER BY `' . $this->_orderingKey . '` ' . $order2
		;
		$this->_db->setQuery($query);
		if(!($orders = $this->_db->loadObjectList())){
			vmError(get_class($this) . ' reorder ' . $this->_db->getErrorMsg());
			return false;
		}
		$orderingKey = $this->_orderingKey;
		// compact the ordering numbers
		for($i = 0, $n = count($orders); $i < $n; $i++){
			if($orders[$i]->$orderingKey >= 0){
				if($orders[$i]->$orderingKey != $i + 1){
					$orders[$i]->$orderingKey = $i + 1;
					$query = 'UPDATE ' . $this->_tbl
					. ' SET `' . $this->_orderingKey . '` = "' . $this->_db->getEscaped($orders[$i]->$orderingKey) . '"
					 WHERE ' . $k . ' = "' . $this->_db->getEscaped($orders[$i]->$k) . '"'
					;
					$this->_db->setQuery($query);
					$this->_db->query();
				}
			}
		}

		return true;
	}

	/**
	 * Checks out a row
	 *
	 * @access public
	 * @param	integer	The id of the user
	 * @param 	mixed	The primary key value for the row
	 * @return	boolean	True if successful, or if checkout is not supported
	 */
	function checkout($who, $oid = NULL){
		if(!in_array('locked_by', array_keys($this->getProperties()))){
			return true;
		}

		$k = $this->_tbl_key;
		if($oid !== NULL){
			$this->$k = $oid;
		}

		$date = JFactory::getDate();
		$time = $date->toMysql();

		$query = 'UPDATE ' . $this->_db->nameQuote($this->_tbl) .
	' SET locked_by = ' . (int)$who . ', locked_on = "' . $this->_db->getEscaped($time) . '"
			 WHERE ' . $this->_tbl_key . ' = "' . $this->_db->getEscaped($this->$k) . '"';
		$this->_db->setQuery($query);

		$this->locked_by = $who;
		$this->locked_on = $time;

		return $this->_db->query();
	}

	/**
	 * Checks in a row
	 *
	 * @access	public
	 * @param	mixed	The primary key value for the row
	 * @return	boolean	True if successful, or if checkout is not supported
	 */
	function checkin($oid=NULL){
		if(!(
		in_array('locked_by', array_keys($this->getProperties())) ||
		in_array('locked_on', array_keys($this->getProperties()))
		)){
			return true;
		}

		$k = $this->_tbl_key;

		if($oid !== NULL){
			$this->$k = $oid;
		}

		if($this->$k == NULL){
			return false;
		}

		$query = 'UPDATE ' . $this->_db->nameQuote($this->_tbl) .
	' SET locked_by = 0, locked_on = "' . $this->_db->getEscaped($this->_db->getNullDate()) . '"
				 WHERE ' . $this->_tbl_key . ' = "' . $this->_db->getEscaped($this->$k) . '"';
		$this->_db->setQuery($query);

		$this->locked_by = 0;
		$this->locked_on = '';

		return $this->_db->query();
	}

	/**
	 * Check if an item is checked out
	 *
	 * This function can be used as a static function too, when you do so you need to also provide the
	 * a value for the $against parameter.
	 *
	 * @static
	 * @access public
	 * @param integer  $with  	The userid to preform the match with, if an item is checked out
	 * 				  			by this user the function will return false
	 * @param integer  $against 	The userid to perform the match against when the function is used as
	 * 							a static function.
	 * @return boolean
	 */
	function isCheckedOut($with = 0, $against = NULL){
		if(isset($this) && is_a($this, 'JTable') && is_null($against)){
			$against = $this->get('locked_by');
		}

		//item is not checked out, or being checked out by the same user
		if(!$against || $against == $with){
			return false;
		}

		$session = JTable::getInstance('session');
		return $session->exists($against);
	}

	/**
	 * toggle (0/1) a field
	 * or invert by $val
	 * @author impleri
	 * @author Max Milbers
	 * @param string $field the field to toggle
	 * @param boolean $val field value (0/1)
	 * @todo could make this multi-id as well...
	 */
	function toggle($field, $val = NULL){
		if($val === NULL){
			$this->$field = !$this->$field;
		}else {
			$this->$field = $val;
		}
		$k = $this->_tbl_key;
		$q = 'UPDATE `'.$this->_tbl.'` SET `'.$field.'` = "'.$this->$field.'" WHERE `'.$k.'` = "'.$this->$k.'" ';
		$this->_db->setQuery($q);
		if(!$res = $this->_db->query()){
			vmError('There was an error toggling ',$this->_db->getErrorMsg());
		}

		return $res;
	}

	public function resetErrors(){

		$this->_errors = array();
	}

	// TODO add Translatable delete  ???
	//
	function delete( $oid=NULL , $where = 0 ){

		$k = $this->_tbl_key;

		if ($oid) {
			$this->$k = intval( $oid );
		}

		$mainTableError = $this->checkAndDelete($this->_tbl,$where);

		if($this->_translatable){

			$langs = VmConfig::get('active_languages',array()) ;
			if (!$langs) $langs[]= VMLANG;
			if(!class_exists('VmTableData'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmtabledata.php');
			foreach($langs as $lang){
				$lang = strtolower(strtr($lang,'-','_'));
				$langError = $this->checkAndDelete($this->_tbl.'_'.$lang);
				$mainTableError = min($mainTableError,$langError);
			}
		}

		return $mainTableError;
	}

	function checkAndDelete($table,$where = 0){
		$ok = 1;
		$k = $this->_tbl_key;

		if($where!==0){
			$whereKey = $where;
		} else {
			$whereKey = $this->_pkey;
		}

		$query = 'SELECT `'.$this->_tbl_key.'` FROM `'.$table.'` WHERE '.$whereKey.' = "' .$this->$k . '"';
		$this->_db->setQuery( $query );
		// 		vmdebug('checkAndDelete',$query);
		$list = $this->_db->loadResultArray();
		// 		vmdebug('checkAndDelete',$list);
		if($list){

			foreach($list as $row){
				$ok = $row;
				$query = 'DELETE FROM `'.$table.'` WHERE '.$this->_tbl_key.' = "'.$row.'"';
				$this->_db->setQuery( $query );

				if (!$this->_db->query()){
					$this->setError($this->_db->getErrorMsg());
					vmError('checkAndDelete '.$this->_db->getErrorMsg());
					$ok = 0;
				}
			}

		}
		return $ok;
	}

}
