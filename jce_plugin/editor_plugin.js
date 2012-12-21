tinyMCE.importPluginLanguagePack('productsnap');

var TinyMCE_ProductSnapshotPlugin = {
	getInfo : function() {
		return {
			longname : 'VirtueMart Product Snapshot Plugin for JCE',
			author : 'S�ren Eberhardt',
			authorurl : 'http://virtuemart.net',
			infourl : '',
			version : '1.1.2'
		};
	},

	getControlHTML : function(cn) {
		switch (cn) {
			
			case "virtuemart":
				return tinyMCE.getButtonHTML(cn, 'lang_productsnap_desc', '{$pluginurl}/images/virtuemart.gif', 'mceProductSnapshotManager');
		}

		return "";
	},

	execCommand : function(editor_id, element, command, user_interface, value) {
		switch (command) {
			case "mceProductSnapshotManager":
				var template = new Array();

				template['file']   = tinyMCE.getParam('site') + '/index2.php?option=com_jce&no_html=1&task=plugin&plugin=productsnap&file=manager.php';
                template['width']  = 800;
			    template['height'] = 700;
				
				template['width']  += tinyMCE.getLang('lang_productsnap_delta_width', 0);
				template['height'] += tinyMCE.getLang('lang_productsnap_delta_height', 0);

				var inst = tinyMCE.getInstanceById(editor_id);
				var elm = inst.getFocusElement();

				if (elm != null && tinyMCE.getAttrib(elm, 'class').indexOf('mceItem') != -1)
					return true;

				tinyMCE.openWindow(template, {editor_id : editor_id, inline : "yes"});

				return true;
		}

		return false;
	},

	cleanup : function(type, content) {


		return content;
	},

	handleNodeChange : function(editor_id, node, undo_index, undo_levels, visual_aid, any_selection) {
		if (node == null)
			return;

		do {
			if (node.nodeName == "IMG" && tinyMCE.getAttrib(node, 'class').indexOf('mceItem') == -1) {
				tinyMCE.switchClass(editor_id + '_productsnap', 'mceButtonSelected');
				return true;
			}
		} while ((node = node.parentNode));

		tinyMCE.switchClass(editor_id + '_productsnap', 'mceButtonNormal');

		return true;
	},

	/**
	 * Returns the image src from a scripted mouse over image str.
	 *
	 * @param {string} s String to get real src from.
	 * @return Image src from a scripted mouse over image str.
	 * @type string
	 */
	_getImageSrc : function(s) {
		var sr, p = -1;

		if (!s)
			return "";

		if ((p = s.indexOf('this.src=')) != -1) {
			sr = s.substring(p + 10);
			sr = sr.substring(0, sr.indexOf('\''));

			return sr;
		}

		return "";
	}
};

tinyMCE.addPlugin("productsnap", TinyMCE_ProductSnapshotPlugin);
