<?php
/**
* @version $Id$
* @package Mambo
* @subpackage Languages
* @copyright (C) 2000 - 2005 Miro International Pty Ltd
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
* Mambo is Free Software
*/

/** ensure this file is being included by a parent file */
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

require_once( $mainframe->getPath( 'toolbar_html' ) );

switch ($task) {
	case "new":
	case "edit_source":
	case "apply_source":
		TOOLBAR_martlanguages::_EDIT_SOURCE();
		break;
	
	case "new_token":
	case "edit_tokens":
		TOOLBAR_martlanguages::_EDIT_TOKEN();
		break;
	
	case "list_tokens":
	case "rebuild_tokens":
	case "upload_pack":
	case "upload_pack2":	
	case "export_pack":
	case "export_pack2":	
		TOOLBAR_martlanguages::_MAIN_BUTTONS();
		break;
	
	default:
		TOOLBAR_martlanguages::_DEFAULT();
		break;
}
?>
