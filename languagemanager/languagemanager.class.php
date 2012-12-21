<?php
/**
* @version $Id$
* @package martlanguages
* @copyright (C) 2005 Soeren Eberhardt
*
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

class vmLanguageManager {
	var $modules = array();
	function initModule($module,&$vars) {
		$this->modules[$module] =& $vars;
	}
}
?>