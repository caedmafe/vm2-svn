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
* @package VirtueMart
* @subpackage devtools
*/
class HTML_martlanguages {

	function showLanguages( $cur_lang, &$rows, &$pageNav, $option ) {
		global $my, $module;
		?>
		<form action="index2.php" method="post" name="adminForm">
		<table class="adminheading">
		<tr>
			<th class="langmanager">
			VirtueMart Language Manager - 
			Module:
			<?php
			$tempModuleDirs = mosReadDirectory(HTML_martlanguages::getLanguageFilePath());
			$moduleDirs = array();
			for ($i=0;$i<count($tempModuleDirs);$i++) {
				if (!is_dir(HTML_martlanguages::getLanguageFilePath() . "/" . $tempModuleDirs[$i])) {
					unset($tempModuleDirs[$i]);
				} else {
					$moduleDirs[]->module = $tempModuleDirs[$i];
				}
			}
			echo mosHTML::selectList( $moduleDirs, 'module', '', 'module', 'module', $module );
			?>
			<input type="submit" class="button" value="Change" />
			</th>
			
		</tr>
		</table>
		
		<table >
			<tr>
			Directory Permissions:
			<?php mosHTML::writableCell( HTML_martlanguages::getLanguageFilePath() . "/" . $module, 0 ); ?>
			</tr>
		</table>
		<table class="adminlist">
		<tr>
			<th width="20">#</th>
			<th width="20"><input type="checkbox" name="toggle" value="" onclick="checkAll(<?php echo count( $rows ); ?>);" /></th>
			<th class="title">Language</th>
			<th width="30">current mosConfig_lang?</th>
			<th width="30">is writable?</th>
		</tr>
		<?php
		$k = 0;
		for ($i=0, $n=count( $rows ); $i < $n; $i++) {
			$row = &$rows[$i];
			?>
			<tr class="<?php echo "row$k"; ?>">
				<td width="20"><?php echo $pageNav->rowNumber( $i ); ?></td>
				<td width="20">
				<input type="checkbox" id="cb<?php echo $i;?>" name="cid[]" value="<?php echo $row->language; ?>" onClick="isChecked(this.checked);" />
				</td>
				<td><a href="#edit" onclick="hideMainMenu();return listItemTask('cb<?php echo $i;?>','edit_source')"><?php echo $row->language;?></a></td>
				<td  align="center"><?php
				if ($row->published == 1) {	 ?>
					<img src="images/tick.png" alt="Published"/>
					<?php
				} else { ?>
					&nbsp;
				<?php
				}
				?>
				</td>
				<td><?php
				if (is_writable( HTML_martlanguages::getLanguageFilePath()."/".$module."/".$row->language.".php" )) {	 ?>
					<img src="images/tick.png" alt="Writable"/>
					<?php
				} else {
					?>
					<img src="images/publish_x.png" alt="Unwritable"/>
				<?php
				}
				?>
				</td>
			</tr>
		<?php
		}
		?>
		</table>
		<?php echo $pageNav->getListFooter(); ?>

		<input type="hidden" name="option" value="<?php echo $option;?>" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="hidemainmenu" value="0" />
		<input type="hidden" name="boxchecked" value="0" />
		</form>
		<?php
	}

	function editLanguageSource( $englishLanguageArr, $languagesArr, $option ) {
		global $module;
		$language_path = HTML_martlanguages::getLanguageFilePath() . '/';
		
		?>
		
	    <table cellpadding="1" cellspacing="1" border="0" width="100%">
		<tr>
	        <td width="270"><table class="adminheading"><tr><td><span class="langmanager">Language File Editor</span></td></tr></table>
		</td>
	        <td width="240">
			<?php
		foreach( $languagesArr as $language) {
			$language_file = $language_path . $module . "/" . $language["languageCode"] . ".php";
			?>
				<span class="componentheading"><?php echo $module . "/" . $language["languageCode"]; ?>.php is :
					<b><?php echo is_writable($language_file) ? '<font color="green"> Writeable</font>' : '<font color="red"> Unwriteable</font>' ?></b>
				</span>
			<br/>
			<?php
		}
		?>
	        </td>
	    </tr>
	   <tr>
		<td colspan="2">
		<table width="30%" align="left">
			<tr><td class="quote">You don't need to use / write down HTML Entities like &amp;amp; for &amp; or &amp;uuml; for &uuml; (just examples).
			<br/>Just write down the original characters.
			 
			</td></tr>
		  </table>
		  <table width="30%" align="right">
			<tr><td width="100%">Legend</td></tr>
			<tr><td width="100%"><input size="40" type="text" value="Everything Ok!" readonly="readonly" name="bla" /></td></tr>
			<tr><td width="100%"><input size="40" type="text" style="background-color:silver;" value="Field needs to be translated!" readonly="readonly" name="bla" /></td></tr>
			<tr><td width="100%"><input size="40" type="text" style="background-color:orange;color:blue;" value="Field is set in language file, but EMPTY" readonly="readonly" name="bla" /></td></tr>
			<tr><td width="100%"><input size="40" type="text" style="background-color:red;color:yellow;" value="Field is MISSING in language file" readonly="readonly" name="bla" /></td></tr>
		  </table>
		</td>
	   </tr>
	</table>
	<form action="index2.php" method="post" name="adminForm">
	<table class="adminform">
	        <tr>
			<th width="20%">Token Name<br/>
				<span class="smallgrey">English Definition</span>
			</th>
			<?php
			$i = 0;
			$count= count( $languagesArr );
			$width = 80 / $count;
			foreach( $languagesArr as $language) { ?>
				<th width="<?php echo $width ?>%">Definition for: <?php 
					echo $module . " / ";
					if( $language["languageCode"] == "newLanguage" ) { ?>
						<input type="text" value="<?php echo $language["languageCode"]; ?>" name="language[<?php echo $i ?>][languageCode]" />
						<?php
					} else {
						echo $language["languageCode"]; 
						?>
						<input type="hidden" value="<?php echo $language["languageCode"]; ?>" name="language[<?php echo $i ?>][languageCode]" />
						<?php
					}
					$i++;
					?>
				</th>
		<?php } ?>
		</tr>
		<?php
		
		foreach( $englishLanguageArr as $token => $value) {
			if( $token != "languageCode" ) {
				if ($token == "CHARSET") {
					for( $i=0; $i < $count; $i++ ) {
						$value = @$languagesArr[$i][$token];
						echo "<tr>
						<td width=\"20%\" style='background-color:#eeeeee'><div style=\"text-align:right;\">$token</div></td>
						<td style='background-color:#eeeeee'>$value
						<input type=\"hidden\" name=\"language[$i][$token]\" value=\"$value\" /></td>
						</tr>";
					}
				} else {
					$englishText = htmlentities(stripslashes($value));
					echo "<tr>
					<td width=\"20%\"><div style=\"text-align:right;\">$token<br/>
						<strong>".wordwrap( $englishText, 70, "<br/>", false )."</strong>
					</div></td>";
					for( $i=0; $i < $count; $i++ ) {
						echo "<td width=\"".$width."%\">";
						if( !isset( $languagesArr[$i][$token] ))
							$style= "background-color:red;color:yellow;";
						elseif( empty( $languagesArr[$i][$token] ))
							$style= "background-color:orange;color:blue;";
						elseif( $languagesArr[$i][$token] == $value && $languagesArr[$i]['languageCode'] != 'english')
							$style = "background-color:silver;";
						else
							$style= "";
						$func = getEncodeFunc(@$languagesArr[$i]['CHARSET']);
						$text = $func(@$languagesArr[$i][$token]);
						$text = stripslashes($text);
						$text = str_replace( '"', '&quot;', $text );
						
						if( strlen( $text ) > 60 || strlen($englishText) > 60) {
							echo "<textarea style=\"$style\" name=\"language[$i][$token]\" cols=\"60\" rows=\"3\">$text</textarea>";
						}
						else {
							echo "<input type=\"text\" size=\"60\" style=\"$style\" name=\"language[$i][$token]\" value=\"$text\" /></td>\n";
						}
					}
					echo "</tr>\n";
				}
			}
		}
		?>
	</table>
		<input type="hidden" name="option" value="<?php echo $option;?>" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="module" value="<?php echo $module;?>" />
		<?php
		foreach( $languagesArr as $language) {
			?>
			<input type="hidden" name="cid[]" value="<?php echo $language["languageCode"];?>" />
			<?php
		}
		?>
		</form>
	<?php
	}
	function viewTokens( $tokenArr, $option ) {
		global $messages, $module;
		if (!empty( $messages ))
			if( is_array( $messages )) {
				echo "<div class=\"message\">";
				foreach( $messages as $message ) {
					echo $message."<br/>";
				}
				echo "</div>";
			}
		?>
		<form action="index2.php" method="post" name="adminForm">
		<table class="adminheading">
			<tr>
				<th class="langmanager">
				VirtueMart Language Tokens  - 
				Module: 
				<?php
				$tempModuleDirs = mosReadDirectory(HTML_martlanguages::getLanguageFilePath());
				$moduleDirs = array();
				for ($i=0;$i<count($tempModuleDirs);$i++) {
					if (!is_dir(HTML_martlanguages::getLanguageFilePath() . "/" . $tempModuleDirs[$i])) {
						unset($tempModuleDirs[$i]);
					} else {
						$moduleDirs[]->module = $tempModuleDirs[$i];
					}
				}
				echo mosHTML::selectList( $moduleDirs, 'module', '', 'module', 'module', $module );
				?>
				<input type="button" class="button" value="Change" onclick="submitbutton('list_tokens')" />
				</th>
			</tr>
		</table>
		<p>This is a list of all variable names used in the english language file (english.php). Those variable names are called &quot;tokens&quot;.<br/>
		You can click on &quot;Edit Tokens&quot; to modify this list and add, modify or remove those tokens.
		</p>
		<table class="adminlist">
			<tr>
				<th width="20">#</th>
				<th class="title">Token Name</th>
				<th class="title">default Value (for english)</th>
			</tr>
			
			<?php
			$i = 1;
			foreach( $tokenArr as $token => $default) {
				if ($token=='CHARSET') {
					echo "<tr>
						<td style='background-color:#eeeeee'>$i</td>
						<td style='background-color:#eeeeee'>$token</td>
						<td style='background-color:#eeeeee'>".htmlentities(stripslashes($default))."</td>
						</tr>";
					$i++;
				} else {
					echo "<tr>
						<td>$i</td>
						<td>$token</td>
						<td>".htmlentities(stripslashes($default))."</td>
						</tr>";
					$i++;
				}
			}
			?>
		</table>
		<input type="hidden" name="option" value="<?php echo $option;?>" />
		<input type="hidden" name="task" value="" />
		</form>
		<?php
	}
	function editTokens( $tokenArr, $option ) {
		global $mosConfig_live_site, $module;
		?>
		<table class="adminheading">
			<tr>
				<th class="langmanager">
				<form action="index2.php" method="post" name="changeModuleForm">
				VirtueMart Language Tokens :: EDIT - 
				Module: 
				<?php
				$tempModuleDirs = mosReadDirectory(HTML_martlanguages::getLanguageFilePath());
				$moduleDirs = array();
				for ($i=0;$i<count($tempModuleDirs);$i++) {
					if (!is_dir(HTML_martlanguages::getLanguageFilePath() . "/" . $tempModuleDirs[$i])) {
						unset($tempModuleDirs[$i]);
					} else {
						$moduleDirs[]->module = $tempModuleDirs[$i];
					}
				}
				echo mosHTML::selectList( $moduleDirs, 'module', '', 'module', 'module', $module );
				?>
				<input type="submit" class="button" value="Change" />
				<input type="hidden" name="option" value="<?php echo $option;?>" />
				<input type="hidden" name="task" value="edit_tokens" />
				</form>
				</th>
			</tr>
		</table>
		<p class="componentheading">Here you can edit the list of variable names used in the language files. Those variable names are called &quot;tokens&quot;.</p>
		<p align="left">You can <strong>add</strong> a Token by clicking on &quot;Add Token&quot;.<br/>
		After you submit the additions, those new tokens are automatically added to all available language files, using the default value.
		</p>
		<p align="left">You can <strong>modify</strong> token names and their default value.<br/>
		After you submit the changes, the renamed tokens are automatically renamed in all available language files.
		</p>
		<p align="left">You can <strong>remove</strong> language tokens. To do so, just empty the token name field.<br/>
		After you submit the changes, all tokens with an empty field name are removed from all available language files.
		</p>
		  <a style="cursor:pointer;" onclick="addField();" class="toolbar" onmouseout="MM_swapImgRestore();"  onmouseover="MM_swapImage('newField','','<?php echo $mosConfig_live_site."/administrator/images/new_f2.png" ?>',1);">
			<img src="<?php echo $mosConfig_live_site."/administrator/images/new.png" ?>" name="newField" border="0" align="absmiddle" />
			&nbsp;Add new Token
		  </a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		  <a style="cursor:pointer;" onclick="showCodeArea();" class="toolbar" onmouseout="MM_swapImgRestore();"  onmouseover="MM_swapImage('codeField','','<?php echo $mosConfig_live_site."/administrator/images/apply_f2.png" ?>',1);">
			<img src="<?php echo $mosConfig_live_site."/administrator/images/apply.png" ?>" name="codeField" border="0" align="absmiddle" />
			&nbsp;Insert PHP code from a language file
		  </a>
		<form action="index2.php" method="post" name="adminForm">
		<textarea name="language_phpcode" id="language_phpcode" rows="25" cols="80" style="display:none;" class="inputbox"></textarea>
		<div id="newfieldspace"></div>
		<table class="adminlist">
			<tr>
				<th width="20">#</th>
				<th class="title">Token Name</th>
				<th class="title">default Value (for english)</th>
			</tr>
			
			<?php
			$i = 1;
			foreach( $tokenArr as $token => $default) {
				if ($token=='CHARSET') {
					echo "<tr>
					<td style='background-color:#eeeeee'>$i</td>
					<td style='background-color:#eeeeee'>
						$token
						<input type=\"hidden\" value=\"$token\" name=\"tokens[$i][value]\" />
						<input type=\"hidden\" value=\"$token\" name=\"tokens[$i][current]\" />
					</td>
					<td style='background-color:#eeeeee'><span class=\"smallgrey\">".htmlentities(stripslashes($default))."</span></td>
					</tr>";
					$i++;
				} else {
					echo "<tr>
					<td>$i</td>
					<td>
						<input type=\"text\" value=\"$token\" name=\"tokens[$i][value]\" size=\"45\" />
						<input type=\"hidden\" value=\"$token\" name=\"tokens[$i][current]\" />
					</td>
					<td><span class=\"smallgrey\">".htmlentities(stripslashes($default))."</span></td>
					</tr>";
					$i++;
				}
			}
			?>
		</table>
		<input type="hidden" name="option" value="<?php echo $option;?>" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="module" value="<?php echo $module;?>" />
		</form>
		<script type="text/javascript">
		function showCodeArea() {
			document.getElementById('language_phpcode').style.display ='';
		}
		function addField() {
		  saveFormCache();
		  document.getElementById( 'newfieldspace').innerHTML += '<table class="adminlist"><tr>'
			   +' <td>'+fieldNum+'</td>'
			   + '<td><input type="text" id="token_'+fieldNum+'" name="tokens['+fieldNum+'][value]" size="45" />'
			   + '<input type="hidden" name="tokens['+fieldNum+'][current]" /><br/><span class="smallgrey">Token Name</span></td>'
			   +' <td><textarea id="text_'+fieldNum+'" name="tokens['+fieldNum+'][default_text]" cols="45" rows="2"></textarea><br/><span class="smallgrey">default value</span></td>'
			   +'</tr></table>';
		  
		  restoreFormCache();
		  
		  addedFields[counter] = fieldNum;
		  fieldNum++;
		  counter++;
		  
		}
		function saveFormCache() {
			if( counter < 1 )
				return;

			for (var i = 0; i < addedFields.length; i++) {
				tokenArray[addedFields[i]] = document.getElementById( 'token_'+addedFields[i] ).value;
				textArray[addedFields[i]] = document.getElementById( 'text_'+addedFields[i] ).value;
			}
		}
		function restoreFormCache() {
			if( counter > 0)
				for (var i = 0; i <addedFields.length; i++) {
					document.getElementById('token_'+addedFields[i]).value = tokenArray[addedFields[i]];
					document.getElementById('text_'+addedFields[i]).value = textArray[addedFields[i]];
				}
		}
		var fieldNum = <?php echo $i ?>;
		var addedFields = new Array();
		var tokenArray = new Array();
		var textArray = new Array();
		var counter = 0;
		</script>
		<?php
	}
	
	function rebuildTokens($deleted, $option) {
		global $module;
		if ($deleted) {
			echo "Token file deleted for module <strong>'" . $module . "'</strong>.<br/>";
			echo "Now open a language file to rebuild tokens for this module.";
		} else {
			echo "Token file not found for module <strong>'" . $module . "'</strong>!<br/>";
			echo "Open a language file to build tokens for this module.";
		}
		?>
		<form action="index2.php" method="post" name="adminForm">
		<input type="hidden" name="option" value="<?php echo $option;?>" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="module" value="<?php echo $module;?>" />
		<input type="submit" value="Back to Languages List" >
		</form>
		<?php
	}
	
	function uploadPack($option) {
		global $module;
		?>
		<form action="index2.php" method="post" name="adminForm" enctype="multipart/form-data">
		Upload Language Pack: <input type="file" class="input_box" size="57" name="packfile" />
		<input type="hidden" name="option" value="<?php echo $option;?>" />
		<input type="hidden" name="task" value="upload_pack2" />
		<input type="hidden" name="module" value="<?php echo $module;?>" />
		<input type="submit" value="Upload" >
		<p>
		Types allowed: ZIP, TAR.GZ.
		</p>
		<p>
		<em>Warning: current strings and tokens will be overwritten!</em>
		</p>
		</form>
		<?php
	}
	
	function uploadPack2($message, $uploaded, $option) {
		global $module;
		echo $message;
		
		if (!$uploaded) {
			?>
			<form action="index2.php" method="post" name="uploadForm">
			<input type="hidden" name="option" value="<?php echo $option;?>" />
			<input type="hidden" name="task" value="upload_pack" />
			<input type="hidden" name="module" value="<?php echo $module;?>" />
			<input type="submit" value="Back to Upload Form" >
			</form>
			<?php
		}
		?>
		<form action="index2.php" method="post" name="adminForm">
		<input type="hidden" name="option" value="<?php echo $option;?>" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="module" value="<?php echo $module;?>" />
		<input type="submit" value="Back to Languages List" >
		</form>
		<?php
	}

	function exportPack($languages,$option) {
		global $module;
		?>
		<script type="text/javascript">
		function checkform(tf) {
			if (tf.task.value!='export_pack2') {
				return true;
			} else {
				if (tf.language.value=='') {
					alert("Please select the language to export!");
					return false;
				} else {
					var atleastone = false;
					for (i=0;i<tf.elements.length;i++) {
						if (tf.elements[i].type=="checkbox") 
							if (tf.elements[i].checked) 
								atleastone = true;
					}
					if (!atleastone) {
						alert("Please select one or more modules to export!");
						return false;
					} else {
						return true;
					}
				}
			}
		}
		</script>
		<form action="index2.php" method="post" name="adminForm" onsubmit="return checkform(this)">
		<p>
		<label for="language">Language to Export:</label>
		<select name="language" id="language">
		<option value="">-- SELECT --</option>
		<?php
		foreach ($languages as $languagefile) {
			$language = substr($languagefile,0,strlen($languagefile)-4);
			echo '<option value="' . $language . '">' . $language . '</option>';
		}
		?>
		</select>
		
		<input type="hidden" name="option" value="<?php echo $option;?>" />
		<input type="hidden" name="task" value="export_pack2" />
		<input type="hidden" name="module" value="<?php echo $module;?>" />
		<input type="submit" value="Export" >
		</p>
		
		<p>
		<fieldset>
		<legend>Module(s) to Export:</legend>
		<?php
		$tempModuleDirs = mosReadDirectory(HTML_martlanguages::getLanguageFilePath());
		$moduleDirs = array();
		for ($i=0;$i<count($tempModuleDirs);$i++) {
			if (!is_dir(HTML_martlanguages::getLanguageFilePath() . "/" . $tempModuleDirs[$i])) {
				unset($tempModuleDirs[$i]);
			} else {
				$moduleDirs[]->module = $tempModuleDirs[$i];
			}
		}
		foreach ($moduleDirs as $moduleDirObj) {
			$moduleDir = $moduleDirObj->module;
			echo '<input type="checkbox" name="modules[]" value="' . $moduleDir . '" id="mod' . $moduleDir . '" />
				<label for="mod' . $moduleDir . '">' . $moduleDir . '</label><br/>';
		}
		?>
		</fieldset>
		</p>
		</form>
		<?php
	}

	function exportPack2($message, $option) {
		global $module;
		echo $message;
		?>
		<form action="index2.php" method="post" name="adminForm">
		<input type="hidden" name="option" value="<?php echo $option;?>" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="module" value="<?php echo $module;?>" />
		<input type="submit" value="Back to Languages List" >
		</form>
		<?php
	}

	function getLanguageFilePath() {
		global $mosConfig_absolute_path;
	
		// The path WITHOUT a trailing slash
		$languagePath = $mosConfig_absolute_path.'/administrator/components/com_virtuemart/languages';
		return $languagePath;
	}

	
}
?>
