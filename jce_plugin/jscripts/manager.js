function preinit() {
	// Initialize
	tinyMCE.setWindowArg('mce_windowresize', true);
	tinyMCE.setWindowArg('mce_replacevariables', false);
}
function convertURL(url, node, on_save) {
	return eval("tinyMCEPopup.windowOpener." + tinyMCE.settings['urlconverter_callback'] + "(url, node, on_save);");
}
function getImageSrc(str) {
	var pos = -1;

	if (!str)
		return "";

	if ((pos = str.indexOf('this.src=')) != -1) {
		var src = str.substring(pos + 10);

		src = src.substring(0, src.indexOf('\''));

		if (tinyMCE.getParam('convert_urls'))
			src = convertURL(src, null, true);

		return src;
	}

	return "";
}
function createFileIFrame(category, product) {
    category = ( category != '' ) ? '&category='+category : '';
    product = ( product != '' ) ? '&ret_product='+product : '';
	
	setImg(document, 'load.gif');
	
    document.getElementById('fileContainer').innerHTML = '<iframe class="fileFrame" id="manager" name="manager" src="index2.php?option=com_jce&no_html=1&task=plugin&plugin=productsnap&file=products.php' + category + product + '" frameborder="0"></iframe>';
}
function setIframeSrc(str){
	document.getElementById('manager').src = 'index2.php?option=com_jce&no_html=1&task=plugin&plugin=productsnap&file=products.php' + str;	
}
function init() {
	tinyMCEPopup.resizeToInnerSize();

	var f = document.forms[0];
	var inst = tinyMCE.getInstanceById(tinyMCE.getWindowArg('editor_id'));
	if( inst ) {
		 var html = inst.selection.getSelectedText();
	} else {
		var html = '';
	}
	
	setHTML('border_color_pickcontainer', getColorPickerHTML('border_color_pick','border_color'));
	
	action = "insert";  
    if (html.indexOf("{product_snapshot")!=-1) {
          // let's get the parameters
          var params = html.slice(20,html.length-1);
          var param_array = new Array();
    
          param_array = params.split(",");
          // now the param_array contains 5 fields:
          // 1.product_id
          // 2.showprice
          // 3.showdesc
          // 4.showaddtocart
          // 5.table align
          var action = "update";
    }
	
	f.insert.value = tinyMCE.getLang('lang_' + action, 'Insert', true);
    var category = '';
    var product = '';
    
    f.hspace.value = jce.get("def_hspace");
    f.vspace.value = jce.get("def_vspace");
    f.container_width.value = '150';
    f.container_margin.value = '2';
    f.container_padding.value = '2';
	changeAppearance();
    
	if (action == "update") {

		// Setup form data		
		product=param_array[0].substring( 1 );
		f.product_id.value=product;
        f.category_id.value=category;
      	f.show_price.checked = param_array[1] == 'false' ? false : true;
      	f.show_desc.checked = param_array[2] == 'false' ? false : true;
      	f.show_addtocart.checked = param_array[3] == 'false' ? false : true;
      	selectByValue(f, 'container_align', param_array[4]);
      	
      	if( typeof param_array[5] != 'undefined' ) {
      		try {       		
	      		f.container_width.value = param_array[5];
	      		f.container_margin.value = param_array[6];
	      		f.container_padding.value = param_array[7];      		
	      		selectByValue(f, 'text_align', param_array[8]);
	      		selectByValue(f, 'image_align', param_array[9]);
	      		f.vspace.value = param_array[10];
				f.hspace.value = param_array[11];
	      		selectByValue(f, 'border_with', param_array[12]);
	      		f.border_color.value = param_array[13];  
	      		selectByValue(f, 'border_style', param_array[14]);
	      	} catch(e) {}
      	}
		addClassesToList('classlist', 'advimage_styles');
	
		updateStyle();
		changeAppearance();

		window.focus();
	}else{
		selectByValue(f, 'border_width', '1');
		selectByValue(f, 'container_align', 'none');
		addClassesToList('classlist', 'advimage_styles');
		updateStyle();
	}	

    updateColor('border_color_pick', 'border_color');
	var cookie = Cookie.get("jce_product_category");
	if(cookie){
		category = cookie;	
	}
		
    window.setTimeout('createFileIFrame("' + category + '","' + product + '");', 10);
}
function changeAppearance() {
	var f = document.forms[0];
	var img = document.getElementById('alignSampleImg');
	var container = document.getElementById('previewContainer');

	if (img) {
		try {
			img.align = f.image_align.value;
			img.hspace = f.hspace.value;
			img.vspace = f.vspace.value;
		} catch(e) {}
	}
	container.style.borderLeftWidth = container.style.borderRightWidth = container.style.borderTopWidth = container.style.borderBottomWidth = getSelectValue(f, 'border_width');
	container.style.borderStyle = f.border_style.value;
	container.style.borderColor = f.border_color.value;
	
	if( tinyMCE.isMSIE ) {
		container.style.styleFloat = getSelectValue(f, 'container_align');
	} else {
		container.style.cssFloat = getSelectValue(f, 'container_align');
	}
	if( parseInt( f.container_width.value ) > 10 ) {
		container.style.width = parseInt( f.container_width.value ) + 'px';
	}
	
	container.style.margin = f.container_margin.value;
	container.style.padding = f.container_padding.value;
	try{
		container.align = getSelectValue(f, 'text_align');
	} catch(e) {}
	if( f.show_desc.checked ) {
		document.getElementById('desc').style.display = 'block';
	} else {
		document.getElementById('desc').style.display = 'none';
	}		
	if( f.show_price.checked ) {
		document.getElementById('showPricePreview').style.display = 'block';
	} else {
		document.getElementById('showPricePreview').style.display = 'none';
	}
	if( f.show_addtocart.checked ) {
		document.getElementById('showAddToCartPreview').style.display = 'block';
	} else {
		document.getElementById('showAddToCartPreview').style.display = 'none';
	}

}
function setSwapImageDisabled(state) {
	var f = document.forms[0];
	f.onmousemovecheck.checked = !state;
	f.onmouseoversrc.disabled = state;
	f.onmouseoutsrc.disabled  = state;
}
function setAttrib(elm, attrib, value) {
	var f = document.forms[0];
	var valueElm = f.elements[attrib];

	if (typeof(value) == "undefined" || value == null) {
		value = "";

		if (valueElm)
			value = valueElm.value;
	}

	if (value != "") {
		elm.setAttribute(attrib, value);

		if (attrib == "style")
			attrib = "style.cssText";

		if (attrib == "longdesc")
			attrib = "longDesc";

		if (attrib == "width") {
			attrib = "style.width";
			value = value + "px";
		}
		if (attrib == "height") {
			attrib = "style.height";
			value = value + "px";
		}

		if (attrib == "class")
			attrib = "className";

		eval('elm.' + attrib + "=value;");
	} else
		elm.removeAttribute(attrib);
}
function makeAttrib(attrib, value) {
	var d = document, f = d.forms[0];
	var valueElm = f.elements[attrib];

	if (typeof(value) == "undefined" || value == null) {
		value = "";

		if (valueElm)
			value = valueElm.value;
	}
	
	if(attrib == "align"){
		value = ( value == "left" || value == "right" ) ? "" : value;	
	}

	if (value == "")
		return "";

	// XML encode it
	if(tinyMCE.getParam('encoding') == 'xml'){
		value = value.replace(/&/g, '&amp;');
		value = value.replace(/\"/g, '&quot;');
		value = value.replace(/</g, '&lt;');
		value = value.replace(/>/g, '&gr;');
	}

	return ' ' + attrib + '="' + value + '"';
}
function insertAction() {
	var f = document.forms[0];
	var inst = tinyMCE.getInstanceById(tinyMCE.getWindowArg('editor_id'));
	var elm = inst.getFocusElement();
	var pid = f.product_id.value;
	var cid = f.category_id.value;
	// {product_snapshot:id=XX,showprice,showdesc,showaddtocart,align}
	var html = "{product_snapshot:id=";

	html += pid;
	html += f.show_price.checked ? ',true' : ',false';
	html += f.show_desc.checked ? ',true' : ',false';
	html += f.show_addtocart.checked ? ',true' : ',false';
	html += ',' + getSelectValue(f, 'container_align'); // actually this means "float"
	html += ',' + parseInt(f.container_width.value);
	html += ',' + parseInt(f.container_margin.value);
	html += ',' + parseInt(f.container_padding.value);
	html += ',' + getSelectValue(f, 'text_align');
	html += ',' + getSelectValue(f, 'image_align');
	html += ',' + parseInt(f.vspace.value);
	html += ',' + parseInt(f.hspace.value);
	html += ',' + parseInt(getSelectValue(f, 'border_width'));
	html += ',' + f.border_color.value;
	html += ',' + getSelectValue(f, 'border_style');
	html += ',cid=' + cid;
	html += "}";

	tinyMCEPopup.execCommand("mceInsertContent", false, html);
	
	tinyMCE._setEventsEnabled(inst.getBody(), false);
	tinyMCEPopup.close();
}
function cancelAction() {
	tinyMCEPopup.close();
}
function changeMouseMove() {
	var f = document.forms[0];
	setSwapImageDisabled(!f.onmousemovecheck.checked);
}
function updateStyle() {
	var f = document.forms[0];
	var st = tinyMCE.parseStyle(f.style.value);

	st['container_width'] = f.container_width.value == '' ? '' : f.container_width.value + "px";
	
	if(getSelectValue(f, 'container_align') == 'left' || getSelectValue(f, 'container_align') == 'right'){
		st['float'] = getSelectValue(f, 'container_align');	
	}else{
		st['float'] = '';	
	}				
	if(parseInt(getSelectValue(f, 'border_width')) == 0){
		st['border-width'] = '';
		st['border-color'] = '';
		st['border-style'] = '';			
	}else{
		st['border-width'] = getSelectValue(f, 'border_width') + "px";
		st['border-color'] = f.border_color.value;
		st['border-style'] = getSelectValue(f, 'border_style');	
	}
	if(parseInt(f.vspace.value) == 0){
		st['margin-top'] = st['margin-bottom'] = '';
	}else{
		st['margin-top'] = st['margin-bottom'] = f.vspace.value + 'px';
	}
	if(parseInt(f.hspace.value) == 0){
		st['margin-left'] = st['margin-right'] = '';	
	}else{
		st['margin-left'] = st['margin-right'] = f.hspace.value + 'px';	
	}
	f.style.value = tinyMCE.serializeStyle(st);

}
function changeHeight() {
    var f = document.forms[0];
	if( !f.constrain.checked )
        return;

    if (f.width.value == "" || f.height.value == "")
		return;

	var temp = (f.width.value / f.tmp_width.value) *f.tmp_height.value;
	f.height.value = temp.toFixed(0);
}
function changeWidth() {
    var f = document.forms[0];
	if( !f.constrain.checked )
        return;

    if (f.width.value == "" || f.height.value == "")
		return;

	var temp = (f.height.value / f.tmp_height.value) * f.tmp_width.value;
	f.width.value = temp.toFixed(0);
}
function viewImage()
{
    var img = document.getElementById('itemsList').value;
	var name = stripExtension(basename(img));
	img = makePath(base_url, img);
    var url = tinyMCE.getParam('document_base_url') + 'index2.php?option=com_jce&no_html=1&task=plugin&plugin=productsnap&file=view.php&img=' + img + '&a=' + name;
    openWin(url, 150, 150, 'yes', 'no');
}
function getDimensions(w, h){
	var f = document.forms[0];
	f.width.value = f.tmp_width.value = w;
    f.height.value = f.tmp_height.value = h;
	document.getElementById('dim_loader').innerHTML = '';
	f.insert.disabled = false;
}
function changeCat(newCat)
{
    Cookie.set("jce_productsnap_category", newCat, 1);
	//document.getElementById('manager').contentWindow.changeDir(newDir);
	setIframeSrc('&category=' + encodeURIComponent(newCat));
	showMessage(document, 'Loading', 'load.gif', 'msg');
}
function updateCat(selection)
{
    var f = document.forms[0];
    var newCat = getSelectValue(f, 'dirPath');
	changeCat(newCat);
}
function goUpCat()
{
    var f = document.forms[0];
    var currentCat = getSelectValue(f, 'dirPath');
    if(currentCat.length < 2)
        return false;
    var Cats = currentCat.split('/');
    var search = '';
    for(var i = 0; i < Cats.length-1; i++)
    {
        search += Cats[i]+'/';
    }
    search = search.substr(0, search.length-1);
    changeCat(search);
}
function searchProduct(str)
{
    var ids = new Array();
	var child = window.frames.manager.document.getElementById('productList').childNodes;
	var sf = new SelectableElements(window.frames.manager.document.getElementById('productList'), true);
	p.getElementById('fileDetails').innerHTML = '';
	
	for ( var i = 0; i < child.length; i++ ) {
		var name = child[i].title;
		if( child[i].nodeName == 'DIV' && str != '' && name.substring(0, str.length) == str){
			ids.push(child[i].id);
			sf.setItemSelectedUi(child[i], true);
		}else{
			sf.setItemSelected(child[i], false);
		}
	}
	if(ids.length > 1){
    	//showNumFiles(ids);
	}
	if(ids.length == 1){
		//showFileDetails(ids);	
	}
    if(ids.length > 0){
		var el = window.frames.manager.document.getElementById(ids[0]);
		Scroll.doScroll(el);		
	}
	if(!str){
		window.scroll(0, 0);
        p.getElementById('fileDetails').innerHTML = '';
        p.getElementById('itemsList').value = '';	
	}	
}
// While loading
preinit();
