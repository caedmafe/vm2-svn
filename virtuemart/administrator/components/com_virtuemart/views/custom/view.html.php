<?php
/**
*
* Description
*
* @package	VirtueMart
* @subpackage
* @author
* @link http://www.virtuemart.net
* @copyright Copyright (c) 2004 - 2010 VirtueMart Team. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* @version $Id: view.html.php 3006 2011-04-08 13:16:08Z Milbo $
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die('Restricted access');

// Load the view framework
if(!class_exists('VmView'))require(JPATH_VM_ADMINISTRATOR.DS.'helpers'.DS.'vmview.php');

/**
 * HTML View class for the VirtueMart Component
 *
 * @package		VirtueMart
 * @author
 */
class VirtuemartViewCustom extends VmView {

	function display($tpl = null) {

		// Load the helper(s)


		$this->loadHelper('html');
		$this->loadHelper('vmcustomplugin');
		$model = VmModel::getModel();
		if (!class_exists ('Permissions')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'permissions.php');
		}
		// TODO Make an Icon for custom
		$this->SetViewTitle('PRODUCT_CUSTOM_FIELD');


		$layoutName = JRequest::getWord('layout', 'default');
		if ($layoutName == 'edit') {
			$this->addStandardEditViewCommands();
			$customPlugin = '';
			$this->loadHelper('parameterparser');
			$custom = $model->getCustom();
			$customfields = VmModel::getModel('customfields');
// 			vmdebug('VirtuemartViewCustom',$custom);
			JPluginHelper::importPlugin('vmcustom');
			$dispatcher = JDispatcher::getInstance();
			$retValue = $dispatcher->trigger('plgVmOnDisplayEdit',array($custom->virtuemart_custom_id,&$customPlugin));

			$this->SetViewTitle('PRODUCT_CUSTOM_FIELD', $custom->custom_title);

			$selected=0;
			if(!empty($custom->custom_jplugin_id)) {
				$selected = $custom->custom_jplugin_id;
			}
			$pluginList = self::renderInstalledCustomPlugins($selected);
			$this->assignRef('customPlugin',	$customPlugin);

			$this->assignRef('pluginList',$pluginList);
			$this->assignRef('custom',	$custom);
			$this->assignRef('customfields',	$customfields);

        }
        else {

			JToolBarHelper::custom('createClone', 'copy', 'copy',  JText::_('COM_VIRTUEMART_CLONE'), true);
			JToolBarHelper::custom('toggle.admin_only.1', 'publish','', JText::_('COM_VIRTUEMART_TOGGLE_ADMIN'), true);
			JToolBarHelper::custom('toggle.admin_only.0', 'unpublish','', JText::_('COM_VIRTUEMART_TOGGLE_ADMIN'), true);
			JToolBarHelper::custom('toggle.is_hidden.1', 'publish','', JText::_('COM_VIRTUEMART_TOGGLE_HIDDEN'), true);
			JToolBarHelper::custom('toggle.is_hidden.0', 'unpublish','', JText::_('COM_VIRTUEMART_TOGGLE_HIDDEN'), true);

			$this->addStandardDefaultViewCommands();
			$this->addStandardDefaultViewLists($model);

			$customs = $model->getCustoms(JRequest::getInt('custom_parent_id'),JRequest::getWord('keyword'));
			$this->assignRef('customs',	$customs);

			$pagination = $model->getPagination();
			$this->assignRef('pagination', $pagination);


		}

		parent::display($tpl);
	}

	function renderInstalledCustomPlugins($selected)
	{
		$db = JFactory::getDBO();

		if (JVM_VERSION===1) {
			$table = '#__plugins';
			$enable = 'published';
			$ext_id = 'id';
		}
		else {
			$table = '#__extensions';
			$enable = 'enabled';
			$ext_id = 'extension_id';
		}
		$q = 'SELECT * FROM `'.$table.'` WHERE `folder` = "vmcustom" AND `'.$enable.'`="1" ';
		$db->setQuery($q);

		$results = $db->loadAssocList($ext_id);
        $lang =JFactory::getLanguage();
		foreach ($results as &$result) {
        $filename = 'plg_' .strtolower ( $result['name']).'.sys';

        $lang->load($filename, JPATH_ADMINISTRATOR);
		//print_r($lang);
		}
		return VmHTML::select( 'custom_jplugin_id', $results, $selected,"",$ext_id, 'name');

		//return JHtml::_('select.genericlist', $result, 'custom_jplugin_id', null, $ext_id, 'name', $selected);
	}

	private $_hidden = array();

	/**
	 * This displays a custom handler.
	 *
	 * @param string $html atttributes, Just for displaying the fullsized image
	 */
	public function displayCustomFields ($datas) {

		$identify = ''; // ':'.$this->virtuemart_custom_id;
		if (!class_exists ('VmHTML')) {
			require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'html.php');
		}
		if ($datas->field_type) {
			$this->addHidden ('field_type', $datas->field_type);
		}
		$this->addHiddenByType ($datas);

		//$html = '<div id="custom_title">'.$datas->custom_title.'</div>';
		$html = "";
		//$html = ' <table class="admintable"> ';

		$model = VmModel::getModel();

		if (!Permissions::getInstance ()->check ('admin')) {
			$readonly = 'readonly';
		}
		else {
			$readonly = '';
		}
		// only input when not set else display
		if ($datas->field_type) {
			$html .= VmHTML::row ('value', 'COM_VIRTUEMART_CUSTOM_FIELD_TYPE', $datas->field_types[$datas->field_type]);
		}
		else {
			$html .= VmHTML::row ('select', 'COM_VIRTUEMART_CUSTOM_FIELD_TYPE', 'field_type', $this->getOptions ($datas->field_types), $datas->field_type, VmHTML::validate ('R'));
		}
		$html .= VmHTML::row ('input', 'COM_VIRTUEMART_TITLE', 'custom_title', $datas->custom_title, VmHTML::validate ('S'));
		$html .= VmHTML::row ('booleanlist', 'COM_VIRTUEMART_PUBLISHED', 'published', $datas->published);
		$html .= VmHTML::row ('select', 'COM_VIRTUEMART_CUSTOM_GROUP', 'custom_parent_id', $model->getParentList ($datas->virtuemart_custom_id), $datas->custom_parent_id, '');
		$html .= VmHTML::row ('booleanlist', 'COM_VIRTUEMART_CUSTOM_IS_CART_ATTRIBUTE', 'is_cart_attribute', $datas->is_cart_attribute);
		$html .= VmHTML::row ('booleanlist', 'COM_VIRTUEMART_CUSTOM_IS_CART_INPUT', 'is_input', $datas->is_input);
		$html .= VmHTML::row ('input', 'COM_VIRTUEMART_DESCRIPTION', 'custom_desc', $datas->custom_desc);
		// change input by type
		$html .= VmHTML::row ('textarea', 'COM_VIRTUEMART_DEFAULT', 'custom_value', $datas->custom_value);
		$html .= VmHTML::row ('input', 'COM_VIRTUEMART_CUSTOM_TIP', 'custom_tip', $datas->custom_tip);
		$html .= VmHTML::row ('input', 'COM_VIRTUEMART_CUSTOM_LAYOUT_POS', 'layout_pos', $datas->layout_pos);
		//$html .= VmHTML::row('booleanlist','COM_VIRTUEMART_CUSTOM_GROUP','custom_parent_id',$this->getCustomsList(),  $datas->custom_parent_id,'');
		$html .= VmHTML::row ('booleanlist', 'COM_VIRTUEMART_CUSTOM_ADMIN_ONLY', 'admin_only', $datas->admin_only);
		$html .= VmHTML::row ('booleanlist', 'COM_VIRTUEMART_CUSTOM_IS_LIST', 'is_list', $datas->is_list);
		$html .= VmHTML::row ('booleanlist', 'COM_VIRTUEMART_CUSTOM_IS_HIDDEN', 'is_hidden', $datas->is_hidden);

		// $html .= '</table>';  removed
		$html .= VmHTML::inputHidden ($this->_hidden);

		return $html;
	}

	/**
	 * child classes can add their own options and you can get them with this function
	 *
	 * @param array $optionsarray
	 */
	private function getOptions ($field_types) {

		$options = array();
		foreach ($field_types as $optionName=> $langkey) {
			$options[] = JHTML::_ ('select.option', $optionName, JText::_ ($langkey));
		}
		return $options;
	}

	/**
	 * Use this to adjust the hidden fields of the displaycustomHandler to your form
	 *
	 * @author Max Milbers
	 * @param string $name for exampel view
	 * @param string $value for exampel custom
	 */
	public function addHidden ($name, $value = '') {

		$this->_hidden[$name] = $value;
	}

	/**
	 * Adds the hidden fields which are needed for the form in every case
	 *
	 * @author Max Milbers
	 * OBSELTE ?
	 */
	private function addHiddenByType ($datas) {

		$this->addHidden ('virtuemart_custom_id', $datas->virtuemart_custom_id);
		$this->addHidden ('option', 'com_virtuemart');

	}
}
// pure php no closing tag