<?php 
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) {
	die( 'Direct Access to '.basename(__FILE__).' is not allowed.' );
}
/**
*
* @version $Id: uninstall.virtuemart.php 1103 2007-12-21 17:25:40Z gregdev $
* @package VirtueMart
* @subpackage core
* @copyright Copyright (C) 2004-2007 soeren - All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
*
* http://virtuemart.net
*/

jimport('joomla.filesystem.folder');
jimport('joomla.filesystem.file');
jimport('joomla.filesystem.archive');


/**
 * Perform the migration.
 *
 * Step 1: Remove the old Virtuemart files leaving templates and shop images
 * Step 2: Unzip the new 1.5 files
 * Step 3: Run the database conversion sript.
 *
 * @author RickG
 */
function com_vminstall() {
	$mainframe = JFactory::getApplication();
	$patchDir = JRequest::getVar('patchdir', '');
	
	removeOldVmFiles();
	
	if (unzipVmFiles()) {
		$filename = $patchDir.DS.'UPDATE-SCRIPT_VM_1.1.x_to_1.5.0.sql'; 
		if (execSQLFile($filename)) { 
			return;
		}
		else {
			$msg = JText::_('Error executing migration script!');
			$mainframe->redirect('index.php?option=com_virtuemart', $msg);
		}							
	}
	else {
		$msg = JText::_('Error extracting Virtuemart files!');
		$mainframe->redirect('index.php?option=com_virtuemart', $msg);
	}	
} 	
	
	
	/**
	 * Remove old files and folders
	 *
	 * @author RickG
	 */ 	
	function removeOldVmFiles()
	{
		// Clean up backend
		JFolder::delete(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'classes'.DS.'Log');
		JFolder::delete(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'classes'.DS.'nusoap');
		JFolder::delete(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'classes'.DS.'pdf');
		JFolder::delete(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'classes'.DS.'PEAR');
		JFolder::delete(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'classes'.DS.'phpInputFilter');	
		$files = JFolder::files(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'classes');
		for ($i=0, $n=count($files); $i < $n; $i++) {
			$file = $files[$i];
			JFile::delete(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'classes'.DS.$file);
		}
		JFolder::delete(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'html');
		JFolder::delete(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'languages');
		JFolder::delete(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.'sql');		
		$files = JFolder::files(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart');
		for ($i=0, $n=count($files); $i < $n; $i++) {
			$file = $files[$i];
			JFile::delete(JPATH_ROOT.DS.'administrator'.DS.'components'.DS.'com_virtuemart'.DS.$file);
		}		
		
		// Clean up frontend
		JFolder::delete(JPATH_ROOT.DS.'components'.DS.'com_virtuemart'.DS.'js');
		$files = JFolder::files(JPATH_ROOT.DS.'components'.DS.'com_virtuemart');
		for ($i=0, $n=count($files); $i < $n; $i++) {
			$file = $files[$i];
			JFile::delete(JPATH_ROOT.DS.'components'.DS.'com_virtuemart'.DS.$file);
		}
	}		
	
	
	/**
	 * Unzip the new Virtemart files
	 *
	 * @author RickG
	 */ 	
	function unzipVmFiles()
	{
		$patchDir = JRequest::getVar('patchdir', '');
        $zip = JArchive::getAdapter('zip');
        return $zip->extract($patchDir.DS.'virtuemart_1.5.zip', JPATH_ROOT.DS);
	}
	
	
	/**
	 * Parse a sql file executing each sql statement found.
	 *
	 * @author Max Milbers
	 */    
	function execSQLFile($sqlfile) 
	{ 
		// Check that sql files exists before reading. Otherwise raise error for rollback
		if (!file_exists($sqlfile)) {
			return false;
		}
		$buffer = file_get_contents($sqlfile);

		// Graceful exit and rollback if read not successful
		if ( $buffer == false ) {
			return false;
		}

		// Create an array of queries from the sql file
		jimport('joomla.installer.helper');
		$queries = JInstallerHelper::splitSql($buffer);

		if (count($queries) == 0) {
			// No queries to process
			return 0;
		}
		$db = JFactory::getDBO();
		// Process each query in the $queries array (split out of sql file).
		foreach ($queries as $query)
		{			
			$query = trim($query);
			if ($query != '' && $query{0} != '#') {				
				$db->setQuery($query);
				if (!$db->query()) {
					JError::raiseWarning(1, 'JInstaller::install: '.JText::_('SQL Error')." ".$db->stderr(true));
					return false;
				}
			}
		}
			      
    	return true; 
	}