<?php
/**
* @version $Id$
* @package martlanguages
* @copyright (C) 2005 Soeren Eberhardt
*
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
*/

/** ensure this file is being included by a parent file */
defined( '_VALID_MOS' ) or die( 'Direct Access to this location is not allowed.' );

// ensure user has access to this function
if (!$acl->acl_check( 'administration', 'config', 'users', $my->usertype )) {
	mosRedirect( 'index2.php', _NOT_AUTH );
}
global $tokenFile, $module;
$module = mosGetParam( $_REQUEST, 'module', 'common' );
$tokenFile = $mosConfig_absolute_path ."/administrator/components/$option/languageTokens_$module.arr";

require_once( $mainframe->getPath( 'admin_html' ) );
require_once( $mosConfig_absolute_path ."/administrator/components/$option/compat.php42x.php" );
require_once( $mosConfig_absolute_path ."/administrator/components/$option/languagemanager.class.php" );

$task = trim( strtolower( mosGetParam( $_REQUEST, "task", "" ) ) );
$cid = mosGetParam( $_REQUEST, "cid", array(0) );

if (!is_array( $cid )) {
	$cid = array(0);
}

switch ($task) {
	case "new":
	editLanguageSource( "", $option );
	break;

	case "edit_source":
	editLanguageSource( $cid, $option );
	break;

	case "save_source":
	saveLanguageSource( $option, Array(), true );
	break;

	case "apply_source":
	saveLanguageSource( $option, Array(), false );
	editLanguageSource( $cid, $option );
	break;

	case "remove":
	removeLanguage( $cid, $option );
	break;
	
	case "list_tokens":
	listLanguageTokens( $option );
	break;
	
	case "edit_tokens":
	editLanguageTokens( $option );
	break;

	case "save_tokens":
		ini_set( 'memory_limit', '32M');
		saveLanguageTokens( $option );
		listLanguageTokens( $option );
		break;
	
	case "rebuild_tokens":
	rebuildLanguageTokens( $option );
	break;
	
	case "upload_pack":
	uploadPack( $option );
	break;

	case "upload_pack2":
	uploadPack2( $option );
	break;

	case "export_pack":
	exportPack( $option );
	break;

	case "export_pack2":
	exportPack2( $option );
	break;

	case "cancel":
	mosRedirect( "index2.php?option=$option&module=$module" );
	break;

	default:
	viewLanguages( $option );
	break;
}

/**
* Compiles a list of installed languages
*/
function viewLanguages( $option ) {
	global $languages;
	global $mainframe;
	global $mosConfig_lang, $mosConfig_absolute_path, $mosConfig_list_limit;
	global $module;
	
	$limit = $mainframe->getUserStateFromRequest( "viewlistlimit", 'limit', $mosConfig_list_limit );
	$limitstart = $mainframe->getUserStateFromRequest( "view{$option}limitstart", 'limitstart', 0 );

	// get current languages
	$cur_language = $mosConfig_lang;
	$rows = array();
	$rowid = 0;
	$phpFilesInDir = getLanguageFileNames($module);
	
	foreach($phpFilesInDir as $phpfile) {

		$row = new StdClass();
		$row->id = $rowid;
		$row->language = basename( $phpfile, ".php" );

		// if current than set published
		if ($cur_language == $row->language) {
			$row->published	= 1;
		} else {
			$row->published = 0;
		}

		$rows[] = $row;
		$rowid++;
	}

	require_once( $GLOBALS['mosConfig_absolute_path'] . '/administrator/includes/pageNavigation.php' );
	$pageNav = new mosPageNav( count( $rows ), $limitstart, $limit );

	$rows = array_slice( $rows, $pageNav->limitstart, $pageNav->limit );

	HTML_martlanguages::showLanguages( $cur_language, $rows, $pageNav, $option );
}


function editLanguageSource( $p_lname, $option) {
	global $tokenFile, $module;
	$content = "";
	
	if( !empty( $p_lname )) {
		foreach( $p_lname as $language )
			$languagesArr[] = readLanguageIntoArray( $language, $module );
	}
	else {
		$languagesArr = Array( "0" => Array( "languageCode" => "newLanguage")
							);
	}
	
	$englishLanguageArr = getTokenFile( $tokenFile );
	
	HTML_martlanguages::editLanguageSource( $englishLanguageArr, $languagesArr, $option );

}

function saveLanguageSource( $option, $langArray = Array(), $doRedirect = true ) {
	global $tokenFile, $module;
	
	if( empty( $langArray ))
		$languages = mosGetParam( $_POST, 'language', Array(0) );
	else
		$languages[0] = $langArray;

	if (empty( $languages )) {
		mosRedirect( "index2.php?option=$option&mosmsg=Operation failed: No language received." );
	}
	
	foreach( $languages as $language ) {
		$languageName = $language["languageCode"];
		
		$file = getLanguageFilePath()."/$module/$languageName.php";
			/*
		if (is_writable( $file ) == false) {
			mosRedirect( "index2.php?option=$option&mosmsg=Operation failed: The file is not writable." );
		}
		*/
		$contents = "<?php
if( !defined( '_VALID_MOS' ) && !defined( '_JEXEC' ) ) die( 'Direct Access to '.basename(__FILE__).' is not allowed.' ); 
/**
*
* @package VirtueMart
* @subpackage languages
* @copyright Copyright (C) 2004-2008 soeren - All rights reserved.
* @translator soeren
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
* VirtueMart is free software. This version may have been modified pursuant
* to the GNU General Public License, and as distributed it includes or
* is derivative of works licensed under the GNU General Public License or
* other free or open source software licenses.
* See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
*
* http://virtuemart.net
*/
global \$VM_LANG;
\$langvars = array (
	'CHARSET' => '" . $language['CHARSET'] . "'";
		$eng_lang_loaded = false;
		if( empty( $langArray )) 
			$func = getDecodeFunc($language['CHARSET']);
		else
			$func = "strval";
		foreach( $language as $token => $value ) {
			// not to process emty tokens means: removing them!
			if( $token != "languageCode" && $token != "CHARSET" && !empty($token) ) {
				// Prevent situations like  &amp;uuml;
				//means don't encode HTML Entities again
				$value = str_replace( '&amp;', '&', $value );
				// Allow HTML Tags
				$value = str_replace( '&quot;', '"', $value );
				
				$value = str_replace( '\"', '"', $value );
				$value = str_replace( '&lt;', '<', $value );
				$value = str_replace( '&gt;', '>', $value );
				$value = $func($value);
				if (!get_magic_quotes_gpc() || !empty( $langArray ) ) {
					$value = str_replace( '\'', '\\\'', $value );
				}
				if( empty( $value )) {
					if( !$eng_lang_loaded ) {
						$englishLanguageArr = getTokenFile( $tokenFile );
					}
					$value = $englishLanguageArr[$token];
				}
				
				$contents .= ",
	'$token' => '$value'";
			}
		}
		$contents .= "
); \$VM_LANG->initModule( '" . $module . "', \$langvars );
?>";
		if( !file_put_contents( $file, $contents ) )
			if ( $doRedirect )
				mosRedirect( "index2.php?option=$option&mosmsg=Operation failed: Failed to write $file." );
			else
				return false;
	}
	if( $doRedirect )
		mosRedirect( "index2.php?option=$option&module=$module" );
	else
		return true;

}

/**
* Remove the selected language
*/
function removeLanguage( $cid, $option, $client ) {
	global $mosConfig_lang, $module;	

	$cur_language = $mosConfig_lang;
	$ok = true;
	$message = "";
	foreach( $cid as $language ) {
		if ($cur_language == $language) {
			echo "<script>alert(\"You can not delete language in use.\"); window.history.go(-1); </script>\n";
			exit();
		}

		$lang_path = getLanguageFilePath()."/$module/$language.php";
		if( !unlink($lang_path))
			$ok = false;
			$message .= $language.",";
	}
	if( $ok )
		mosRedirect( "index2.php?option=$option&mosmsg=Successfully removed Language(s) $message" );
	else
		mosRedirect( "index2.php?option=$option&mosmsg=Failed to remove Language(s) $message" );
}
#########################
function listLanguageTokens( $option ) {
	global $tokenFile;
	$tokenArr = getTokenFile( $tokenFile );
	HTML_martlanguages::viewTokens( $tokenArr, $option );
}

########################
function editLanguageTokens( $option ) {
	global $tokenFile;
	$tokenArr = getTokenFile( $tokenFile );
	HTML_martlanguages::editTokens( $tokenArr, $option );
}

########################
function saveLanguageTokens( $option ) {
	global $tokenFile, $messages, $module;
	
	unlink ($tokenFile);
	
	$tokens = mosGetParam( $_POST, "tokens", Array(0) );
	$language_phpcode = @$_POST['language_phpcode'];
	if( get_magic_quotes_gpc() ) $language_phpcode = stripslashes($language_phpcode);
	
	if( trim($language_phpcode) ) {
		$tokens_from_source = token_get_all('?><?php'.$language_phpcode.'?>');
		
		foreach ($tokens_from_source as $token) {
			
			if( is_array( $token )) {
				
				list($id, $text) = $token;
				
				switch( $id ) {
					// $_PHPSHOP_BLABLA
					case T_VARIABLE: 
						
						$key = substr( $text, 1 );
						
						break;
					case T_CONSTANT_ENCAPSED_STRING:
						if( !empty( $key )) {
							$value = substr( $text, 1, strlen( $text )-2 );
							
							$tokens[] = array( 'value' => $key, 'default_text' => $value );
						}
						$key = "";
						break;
					default:
						break;
				}
			}
		}
	}
	if( !empty( $tokens )) {
	
		$newTokens = Array();
		$changedTokens = Array();
		$removedTokens = Array();
		$messages = Array();
		foreach( $tokens as $languageToken ) {
			
			// Get new tokens
			if( empty( $languageToken["current"] )) {
				$newTokens[] = Array( "value" => $languageToken["value"],
											"default_text" => $languageToken["default_text"]
										);
			}
			// Get renamed or removed tokens
			elseif( $languageToken["current"] != $languageToken["value"] ) {
				if( empty( $languageToken["value"] ) && !empty( $languageToken['current']))
					$removedTokens[] = Array( "value" => $languageToken["current"] );
				else
					$changedTokens[] = Array( "old_name" => $languageToken["current"],
											"new_name" => $languageToken["value"]
										);
			}
			// else: do nothing
		
		}
		
		// Update all language files if necessary
		if( !empty( $newTokens ) || !empty( $changedTokens ) || !empty( $removedTokens ))
			$langFiles = getLanguageFileNames($module);
			
		if( !empty( $newTokens ) || !empty( $removedTokens )) {
			// Add the new token(s) to ALL available language files
			// using the text from default_value
			foreach( $langFiles as $langFile ) {
				$langName = basename( $langFile, ".php" );
				
				$langArr = readLanguageIntoArray( $langName, $module );
				foreach( $newTokens as $replacement ) {
					if( !array_key_exists( $replacement['value'], $langArr ))
						$langArr[$replacement['value']] = str_replace('\\\'','\'',$replacement['default_text']);
				}
				foreach( $removedTokens as $removement ) {
					unset( $langArr[$removement['value']] );
				}
				// Finally write the language file back
				if( !saveLanguageSource( $option, $langArr, false ))
					$messages[] = "Failed writing $langName Language file while adding/removing tokens";
				else
					$messages[] = "Successfully added/removed tokens in $langName Language file";

			}
		}
		if( !empty( $changedTokens )) {
			// Change the modified token names
			// in all available language files
			foreach( $langFiles as $langFile ) {
				$langName = basename( $langFile, ".php" );
				$langContent = readLanguageIntoString( $langName, $module );
				foreach( $changedTokens as $replacement ) {
					if( !stristr( "'".$replacement['new_name']."' =>", $langContent ))
						$langContent = str_replace( "'".$replacement['old_name']."' =>", "'".$replacement['new_name']."' =>", $langContent );
				}
				// Finally write the language file back
				if( !writeStringIntoLanguage( $langName, $module, $langContent ))
					$messages[] = "Failed writing the Language file $langFile while replacing tokens";
				else
					$messages[] = "Successfully replaced tokens in the Language file ($langFile)";
			}
		}
		if( !empty( $newTokens ) || !empty( $changedTokens ) || !empty( $removedTokens ))
			updateTokenFile( $tokenFile );
	}

}

#########################
function rebuildLanguageTokens( $option ) {
	global $tokenFile, $module;
	
	if (file_exists($tokenFile)) {
		unlink ($tokenFile);
		$deleted = true;
	} else {
		$deleted = false;
	}
	HTML_martlanguages::rebuildTokens( $deleted, $option );
}


#########################
function getTokenFile( $tokenFile ) {
	// have a look at the english language file
	if( !file_exists( $tokenFile )) {
		return updateTokenFile( $tokenFile );
	}
	else
		return unserialize( file_get_contents( $tokenFile ));
	
}
##########################
function updateTokenFile( $tokenFile ) {
	global $module;
	
	// have a look at the english language file
	$tokenArr = readLanguageIntoArray('english',$module);
	unset( $tokenArr["languageCode"] );
	if( $tokenArr ) {
		file_put_contents( $tokenFile, serialize( $tokenArr ) );
		return $tokenArr;
	}
	else
		return false;
	
}
############################
# returns an Array of a language file
# array( "_PHPSHOP_BLABLA" => "English Meaning",
#		"_PHPSHOP_MOREBLABLA => "Another text",
#		....
#		"languageCode"=> "english"
#		)
###########################
function readLanguageIntoArray( $language="english", $langmodule ) {
	global $VM_LANG;
	if( file_exists( getLanguageFilePath()."/$langmodule/$language.php" )) {
		$VM_LANG = new vmLanguageManager();
		include (getLanguageFilePath()."/$langmodule/$language.php");
		if ($VM_LANG->modules[$langmodule]) {
			$virtuemartlanguage = $VM_LANG->modules[$langmodule];
			$virtuemartlanguage["languageCode"] = $language;
			return $virtuemartlanguage;
		} else {
			return false;
		}
	} else {
		return false;
	}
}
function readLanguageIntoString( $language="english", $langmodule ) {
	if( file_exists( getLanguageFilePath()."/$langmodule/$language.php" )) {
		$source = file_get_contents( getLanguageFilePath()."/$langmodule/$language.php" );
		return $source;
	} else {
		return false;
	}
}
function writeStringIntoLanguage( $language, $langmodule, $contents ) {
	$file = getLanguageFilePath()."/$langmodule/$language.php";
	if( file_exists( $file )) {
		if( file_put_contents( $file, $contents ))
			return true;
		else 
			return false;
	}
	else
		return false;
}
function getLanguageFileNames($langmodule) {
	
	// Read the template dir to find templates
	$languageBaseDir = mosPathName( getLanguageFilePath() );

	$phpFilesInDir = mosReadDirectory($languageBaseDir . '/' . $langmodule,'.php$');

	return $phpFilesInDir;
}
function getLanguageFilePath() {
	global $mosConfig_absolute_path;
	
	// The path WITHOUT a trailing slash
	$languagePath = $mosConfig_absolute_path.'/administrator/components/com_virtuemart/languages';
	return $languagePath;
}
function getEncodeFunc($langCharset) {
	$func = 'strval';
	// get global charset setting
	$iso = explode( '=', @constant('_ISO') );
	// If $iso[1] is NOT empty, it is Mambo or Joomla! 1.0.x - otherwise Joomla! >= 1.5
	$charset = !empty( $iso[1] ) ? $iso[1] : 'utf-8';
	// Prepare the convert function if necessary
	if( strtolower($charset)=='utf-8' && stristr($langCharset, 'iso-8859-1' ) ) {
		$func = 'utf8_encode';
	} elseif( stristr($charset, 'iso-8859-1') && strtolower($langCharset)=='utf-8' ) {
		$func = 'utf8_decode';
	}
	if( !function_exists( $func )) {
		$func = 'strval';
	}
	return $func;
}
function getDecodeFunc($langCharset) {
	$func = 'strval';
	// get global charset setting
	$iso = explode( '=', @constant('_ISO') );
	// If $iso[1] is NOT empty, it is Mambo or Joomla! 1.0.x - otherwise Joomla! >= 1.5
	$charset = !empty( $iso[1] ) ? $iso[1] : 'utf-8';
	// Prepare the convert function if necessary
	if( strtolower($charset)=='utf-8' && stristr($langCharset, 'iso-8859-1' ) ) {
		$func = 'utf8_decode';
	} elseif( stristr($charset, 'iso-8859-1') && strtolower($langCharset)=='utf-8' ) {
		$func = 'utf8_encode';
	}
	if( !function_exists( $func )) {
		$func = 'strval';
	}
	return $func;
}

############################
function uploadPack($option) {
	HTML_martlanguages::uploadPack( $option );
}

############################
function uploadPack2($option) {
	global $mosConfig_absolute_path;
	
	if ($_FILES['packfile']['tmp_name']=='') {
		$message = "No file uploaded!";
		$uploaded = false;
	} else {
		$tmpname = $_FILES['packfile']['tmp_name'];
		$filename = $_FILES['packfile']['name'];
		$filetype = '';
		if (strtolower(substr($filename,strlen($filename)-7,7))=='.tar.gz') {
			$filetype = 'tar.gz';
		}
		if (strtolower(substr($filename,strlen($filename)-4,4))=='.zip') {
			$filetype = 'zip';
		}
		if ($filetype=='') {
			$message = "File type not allowed: please use ZIP or TAR.GZ!";
			$uploaded = false;
		} else {
			$message = "File uploaded: " . $filename;
			
			$base_Dir 		= mosPathName( $mosConfig_absolute_path . '/media' );
			$tmpdir 		= uniqid( 'langpack_' );
			$extractdir 	= mosPathName( $base_Dir . $tmpdir );

			if ($filetype=='zip') {
				require_once( $mosConfig_absolute_path . '/administrator/includes/pcl/pclzip.lib.php' );
				require_once( $mosConfig_absolute_path . '/administrator/includes/pcl/pclerror.lib.php' );
				$zipfile = new PclZip( $tmpname );
				$iswindows = substr(PHP_OS, 0, 3) == 'WIN';
				if($iswindows) {
					define('OS_WINDOWS',1);
				} else {
					define('OS_WINDOWS',0);
				}

				$ret = $zipfile->extract( PCLZIP_OPT_PATH, $extractdir );
				if($ret == 0) {
					$message .= 'Error during ZIP extraction: "'.$zipfile->errorName(true).'"';
				}
			} else {
				require_once( $mosConfig_absolute_path . '/includes/Archive/Tar.php' );
				$archive = new Archive_Tar( $tmpname );
				$archive->setErrorHandling( PEAR_ERROR_PRINT );

				if (!$archive->extractModify( $extractdir, '' )) {
					$message .= 'Error during TAR.GZ extraction';
				}
			}
			
			// pack is uploaded end now extracted in $extractdir...
			if (!is_dir($extractdir . '/languages')) {
				$message .= "<p>'language' folder not found in package!";
				$message .= "<br/>Language packs should have this structure: <strong>languages/module/language.php</strong>";
				$message .= "</p>";
				$uploaded = false;
			} else {
				$uploaded = true;
			}
			
			$vmlang_base_path = getLanguageFilePath();
			
			// reading 'language' content and putting in VM folder...
			$dh1 = @opendir($extractdir . '/languages');
		    while (false !== ($moduledir = readdir($dh1))) {
		        if($moduledir=='.' || $moduledir=='..' || $moduledir=='index.html') continue;
				$dh2 = @opendir($extractdir . '/languages/' . $moduledir);
				$message .= "<p><strong>Module '" . $moduledir . "':</strong><ul>";
			    while (false !== ($langfile = readdir($dh2))) {
			        if($langfile=='.' || $langfile=='..' || $langfile=='index.html') continue;
					$sourcefile = $extractdir . '/languages/' . $moduledir . '/' . $langfile;
					$destfile = $vmlang_base_path . '/' . $moduledir . '/' . $langfile;
					if (!file_exists($vmlang_base_path . '/' . $moduledir)) {
						mkdir($vmlang_base_path . '/' . $moduledir);
					}
					copy ($sourcefile,$destfile);
					$message .= "<li>" . $langfile . "</li>";
				}
				$message .= "</ul></p>";
				$moduleTokenFile = $mosConfig_absolute_path ."/administrator/components/$option/languageTokens_$moduledir.arr";
				if (file_exists($moduleTokenFile)) {
					unlink($moduleTokenFile);
				}
		    }
			
			SureRemoveDir($extractdir, true);
		}
	}
	HTML_martlanguages::uploadPack2( $message, $uploaded, $option );
}

function SureRemoveDir($dir, $DeleteMe) {
    if(!$dh = @opendir($dir)) return;
    while (false !== ($obj = readdir($dh))) {
        if($obj=='.' || $obj=='..') continue;
        if (!@unlink($dir.'/'.$obj)) SureRemoveDir($dir.'/'.$obj, true);
    }

    closedir($dh);
    if ($DeleteMe){
        @rmdir($dir);
    }
}

############################
function exportPack($option) {
	HTML_martlanguages::exportPack( getLanguageFileNames('common'), $option );
}

############################
function exportPack2($option) {
	global $mosConfig_absolute_path;
	
	$language = mosGetParam( $_REQUEST, "language", 'english' );
	$modules = mosGetParam( $_REQUEST, "modules", array(0) );
	
	$message  = "Exporting Language '" . $language . "' (modules: ";
	foreach ($modules as $expmodule) {
		$message .= " " . $expmodule;
	}
	$message .= ")...<br/>";
	
	if (count($modules)>1) {
		$languagemodule = $language;
	} else {
		$languagemodule = $language . '-' . $modules[0];
	}
	
	$vmlang_base_path = getLanguageFilePath();
	$base_Dir 		= mosPathName( $mosConfig_absolute_path . '/media' );
	$tmpdir 		= uniqid( 'langpack_' );
	$exportdir 		= mosPathName( $base_Dir . $tmpdir );
	$archivename	= 'LanguagePack_' . $languagemodule . '_' . date('Ymd') . '.tar.gz';
	$expname		= $base_Dir . $archivename;
	
	mkdir($exportdir);
	mkdir($exportdir.'languages');
	
	$files = array();
	
	// copying files to temp dir...
	foreach ($modules as $expmodule) {
		mkdir($exportdir.'languages/'.$expmodule);
		$sourcefile = $vmlang_base_path.'/'.$expmodule.'/'.$language.'.php';
		$destfile = $exportdir.'languages/'.$expmodule.'/'.$language.'.php';
		copy($sourcefile,$destfile);
		$files[] = $destfile;
	}
	
	// now tgzipping files...
	require_once( $mosConfig_absolute_path . '/includes/Archive/Tar.php' );
	$archive = new Archive_Tar( $expname );
	$archive->setErrorHandling( PEAR_ERROR_PRINT );
	$archerror = false;
	if (!$archive->createModify($files,'',$exportdir)) {
		$archerror = true;
	}
	
	SureRemoveDir($exportdir, true);
	
	if (file_exists($expname) && !$archerror) {
		// ripulisce il buffer di output
		ob_clean();
		
		//Begin writing headers
		header('Cache-Control: private, must-revalidate, pre-check=0, post-check=0, max-age=0');
		header('Pragma: no-cache');
		header('Accept-Ranges: bytes');

		//Use the switch-generated Content-Type
		header("Content-Type: application/x-gzip");

		//Force the download
		header("Content-Disposition: attachment; filename=\"$archivename\"");
		header("Content-Transfer-Encoding: binary");
		$len = filesize($expname);
		header("Content-Length: ".$len);
		@set_time_limit(0);
		$fp = @fopen($expname, "rb");
		set_magic_quotes_runtime(0);
		$chunksize = 1*(512*1024); // how many bytes per chunk
		while($fp && !feof($fp)) {
			$buffer = fread($fp, $chunksize);
			print $buffer;
			flush();
			sleep(1);
		}
		set_magic_quotes_runtime(get_magic_quotes_gpc());
		fclose($fp);
		unlink($expname);		
		exit();
	} else {
		$message .= "<p>Archive not created!</p>";
		HTML_martlanguages::exportPack2( $message, $option );
	}
}
?>
