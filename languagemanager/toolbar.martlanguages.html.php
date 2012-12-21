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

/**
* @package Mambo
* @subpackage Languages
*/
class TOOLBAR_martlanguages {
	function _DEFAULT() {
		mosMenuBar::startTable();
		mosMenuBar::spacer();
		mosMenuBar::custom( 'cancel', 'copy.png', 'copy_f2.png', 'List Languages', false );
		mosMenuBar::spacer();
		mosMenuBar::addNew();
		mosMenuBar::spacer();
		mosMenuBar::editListX( 'edit_source' );
		mosMenuBar::spacer();
		mosMenuBar::deleteList();
		mosMenuBar::divider();
		mosMenuBar::custom( 'list_tokens', 'copy.png', 'copy_f2.png', 'List all Tokens', false );
		mosMenuBar::custom( 'edit_tokens', 'edit.png', 'edit_f2.png', 'Edit Tokens', false );
		mosMenuBar::custom( 'rebuild_tokens', 'restore.png', 'restore_f2.png', 'Rebuild Tokens', false);
		mosMenuBar::divider();
		mosMenuBar::custom( 'upload_pack', 'upload.png', 'upload_f2.png', 'Upload Pack', false);
		mosMenuBar::custom( 'export_pack', 'save.png', 'save_f2.png', 'Export Pack', false);
		mosMenuBar::endTable();
	}
	function _MAIN_BUTTONS() {
		mosMenuBar::startTable();
		mosMenuBar::spacer();
		mosMenuBar::custom( 'cancel', 'copy.png', 'copy_f2.png', 'List Languages', false );
		mosMenuBar::divider();
		mosMenuBar::custom( 'list_tokens', 'copy.png', 'copy_f2.png', 'List all Tokens', false );
		mosMenuBar::custom( 'edit_tokens', 'edit.png', 'edit_f2.png', 'Edit Tokens', false );
		mosMenuBar::custom( 'rebuild_tokens', 'restore.png', 'restore_f2.png', 'Rebuild Tokens', false);
		mosMenuBar::divider();
		mosMenuBar::custom( 'upload_pack', 'upload.png', 'upload_f2.png', 'Upload Pack', false);
		mosMenuBar::custom( 'export_pack', 'save.png', 'save_f2.png', 'Export Pack', false);
		mosMenuBar::endTable();
	}
	function _NEW() {
		mosMenuBar::startTable();
		mosMenuBar::save();
		mosMenuBar::spacer();
		mosMenuBar::cancel();
		mosMenuBar::endTable();
	}

	function _EDIT_SOURCE(){
		mosMenuBar::startTable();
		mosMenuBar::apply( 'apply_source' );
		mosMenuBar::save( 'save_source' );
		mosMenuBar::spacer();
		mosMenuBar::cancel();
		mosMenuBar::endTable();
	}

	function _EDIT_TOKEN(){
		global $mosConfig_live_site;
		mosMenuBar::startTable();
		mosMenuBar::save( 'save_tokens' );
		mosMenuBar::spacer();
		mosMenuBar::custom( 'list_tokens', 'cancel.png', 'cancel_f2.png', 'Cancel', false );
		mosMenuBar::endTable();
	}

}
?>
