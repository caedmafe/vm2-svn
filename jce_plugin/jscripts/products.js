//Javascript functions for the Image Manager. Used by products.php and list.php
var p = parent.document;
var f = p.forms[0];
function resetPreview()
{
	p.getElementById('fileDetails').innerHTML = '';
}

function selectProduct(id)
{
	
	var cat = getSelectValue(f, 'dirPath');
	var name = document.getElementById(id).title;
	var pid = id.replace(/p/, '' );
	
	var url = makePath(tinyMCE.getParam('document_base_url'), base_url);
	
	f.product_name.value = name;
	p.getElementById('nameInPreview').innerHTML = name;
	f.title.value = name;
	f.product_id.value = pid;
	f.category_id.value = cat;
}
function showProductDetails(id)
{
	
    var dir = getSelectValue(f, 'dirPath');
    var name = document.getElementById(id).title;
    
	var pid = id.replace(/p/, '' );
	
	var html = '';
    html += '<div style="font-weight:bold">' + name + '</div>';
    html += '<div>' + jce.getLang('file') + '</div>';
	html += '<div id="fileProperties"></div>';	
	
	p.getElementById('fileDetails').innerHTML = html;
	p.getElementById('fileProperties').innerHTML = '<div style="text-align:center; margin-top:20px;"><img src="' + lib_url + '/images/load.gif" /></div>';

	parent.jce.ajaxSend('getProperties', pid);
	p.getElementById('itemsList').value = name;
}
function showNumFiles(id)
{
	var num = id.length;
    var html = '';
    if(num > 0){
        html += '<div style="font-weight:bold">' + num + ' ' + jce.getLang('files_select') + '.</div>';
    }
    p.getElementById('fileDetails').innerHTML = html;
    
    var dir = getSelectValue(f, 'dirPath');
    var names = new Array();
	var paths = new Array();
    for(var i=0; i<id.length; i++){
        names[i] = document.getElementById(id[i]).title;
		paths[i] = makePath(dir, names[i]);
    }
    paths = paths.join(',');
    p.getElementById('itemsList').value = paths;
}
function showProperties(html, width, height){
	if(document.getElementById('fileDetails')){
		setHTML('fileDetails', xmlDecode(html));	
		jce.set('showProperties', true);
		if(jce.get('fileSelected')){			
			setValue('width', width);
			setValue('tmp_width', width);
			setValue('height', height);
			setValue('tmp_height', height);
			setHTML('dim_loader', '');
			disable('insert', false);
			
			updateStyle();
		}
	}
}
function showFolderDetails(id)
{

	var dir = getSelectValue(f, 'dirPath');
    var name = document.getElementById(id).title;
	var path = makePath(dir, name);

    var html = '';
    html += '<div style="font-weight:bold">' + name + '</div>';
    html += '<div>' + jce.getLang('folder') + '</div>';
    p.getElementById('fileDetails').innerHTML = html;
    p.getElementById('itemsList').value = path;
}
function setReturnProduct(pid)
{
    var elm = document.getElementById('productList');
	if(elm){
		var child = ( elm.childNodes );
		for ( var i = 0; i < child.length; i++ ) {
			if( child[i].tagName == 'DIV' ){
				var val = child[i].getAttribute('title');
				var id = child[i].getAttribute('id');
				if( 'p' + pid == id ){
					sf.setItemSelected(child[i], true);
					p.getElementById("itemsList").value = pid;
				}
			}
		}
	}
}
