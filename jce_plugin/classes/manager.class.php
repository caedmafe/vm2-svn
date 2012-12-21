<?php
/**
 * productSnapshotManager Class.
 * @author soeren
 */
class productSnapshotManager extends Manager {
        /**
         * Constructor. Create a new Manager instance.
         * @param array $lang language array, see langs/en.php;
         * @param string $mosConfig_live_site Joomla/Mambo configuration variable
         * @param string $mosConfig_absolute_path Joomla/Mambo configuration variable
         */
        function productSnapshotManager( $base_dir, $base_url ) {
                $this->base_dir = $base_dir;
                $this->base_url = $base_url;
        }
		function getProperties( $pid ) {
			global $jce;
			clearstatcache();
			$db = new ps_DB();
			$prefix = class_exists('vmlanguage') ? '{vm}' : 'pshop';
			
			$db->query('SELECT * FROM #__'.$prefix.'_product WHERE product_id='.(int)$pid );
			$db->next_record();
			if( $db->f('product_thumb_image') ) {
				$path = IMAGEPATH.'product/'.$db->f('product_thumb_image');
				$url = IMAGEURL.'product/'.$db->f('product_thumb_image');
			} else {
				$path = IMAGEPATH.NO_IMAGE;
				$url = IMAGEURL.NO_IMAGE;
			}
			$ext = JFile::getExt( $path );
			$dim = @getimagesize( $path );
			
			$date = JCEUtils::formatDate( $db->f('mdate') );
            $size = JCEUtils::formatSize( filesize( $path ) );
			
			$pw = ( $dim[0] >= 120 ) ? 120 : $dim[0];
            $ph = ( $pw / $dim[0] ) * $dim[1];
			
			if( $ph > 120 ){
				$ph = 120;
           		$pw = ( $ph / $dim[1] ) * $dim[0];
			}
								
			$html = '<div>' . $jce->translate('dimensions') . ': ' . $dim[0] . ' x ' . $dim[1] . '</div>';
			$html .= '<div>' . $jce->translate('size') . ': ' . $size . '</div>';
			$html .= '<div>' . $jce->translate('modified') . ': ' . $date . '</div>';
			$html .= '<div style="text-align:center; margin-top:5px;"><img src="' . $url . '" width="' . $pw . '" height="' . $ph . '"/></div>';

			return "<script type=\"text/javascript\">
					showProperties('" . $jce->ajaxHTML( $html ) . "');
					document.getElementById('desc').innerHTML ='".str_replace("\n", "", str_replace( "\r", "", htmlspecialchars($db->f('product_s_desc'), ENT_QUOTES )))."';
					document.getElementById('alignSampleImg').src ='".$url."';
					</script>";
		}
		
		function getDimensions( $pid ) {		
			$db = new ps_DB();
			$prefix = class_exists('vmlanguage') ? '{vm}' : 'pshop';
			
			$db->query('SELECT * FROM #__'.$prefix.'_product WHERE product_id='.(int)$pid );
			$db->next_record();
			if( $db->f('product_thumb_image') ) {
				$path = IMAGEPATH.'product/'.$db->f('product_thumb_image');
				$url = IMAGEURL.'product/'.$db->f('product_thumb_image');
			} else {
				$path = IMAGEPATH.NO_IMAGE;
				$url = IMAGEURL.NO_IMAGE;
			}
			$dim = @getimagesize( $path );
			
			return "<script>getDimensions('" . $dim[0] . "','" . $dim[1] . "');</script>";
		}
		
		function getCategories( $category_id='' ) {
			
			require_once(CLASSPATH.'ps_product_category.php');
			$category = new ps_product_category();
			$categories = $category->getCategoryTreeArray();

			// Copy the Array into an Array with auto_incrementing Indexes
			$key = array_keys($categories);
			$size = sizeOf($key);
			$category_tmp = Array();
			for ($i=0; $i<$size; $i++) {
				$category_tmp[$i] = &$categories[$key[$i]];
			}
	
			$html = "";
			/** FIRST STEP
		    * Order the Category Array and build a Tree of it
		    **/
			$nrows = count( $category_tmp );
	
			$id_list = array();
			$row_list = array();
			$depth_list = array();
	
			for($n = 0 ; $n < $nrows ; $n++) {
				if($category_tmp[$n]["category_parent_id"] == 0) { 
					array_push($id_list,$category_tmp[$n]["category_child_id"]);
					array_push($row_list,$n);
					array_push($depth_list,0);
				}
			}
			$loop_count = 0;
			while(count($id_list) < $nrows) {
				if( $loop_count > $nrows ) { break; }
				$id_temp = array();
				$row_temp = array();
				$depth_temp = array();
				for($i = 0 ; $i < count($id_list) ; $i++) {
					$id = $id_list[$i];
					$row = $row_list[$i];
					$depth = $depth_list[$i];
					array_push($id_temp,$id);
					array_push($row_temp,$row);
					array_push($depth_temp,$depth);
					for($j = 0 ; $j < $nrows ; $j++) {
						if(($category_tmp[$j]["category_parent_id"] == $id)
							&& (array_search($category_tmp[$j]["category_child_id"],$id_list) == NULL)) { 
								array_push($id_temp,$category_tmp[$j]["category_child_id"]);
								array_push($row_temp,$j);
								array_push($depth_temp,$depth + 1);
						}
					}
					if (array_key_exists($j, $category_tmp)) 
					if( empty( $categories[@$category_tmp[$j]["category_parent_id"]] )) {
	
						array_push($id_temp,"");
						array_push($row_temp,"");
						array_push($depth_temp,"");
					}
				}
				$id_list = $id_temp;
				$row_list = $row_temp;
				$depth_list = $depth_temp;
				$loop_count++;
			}
	
			// Fix the empty Array Fields
			if( $nrows < count( $row_list ) ) {
				$nrows = count( $row_list );
			}
			$array = array();
			// Now show the categories
			for($n = 0 ; $n < $nrows ; $n++) {
	
				if( !isset( $row_list[$n] ) || !isset( $category_tmp[$row_list[$n]]["category_child_id"] ) ) {
					continue;
				}	
	
				$catname = shopMakeHtmlSafe( $category_tmp[$row_list[$n]]["category_name"] );

				$array[] = array( 'name' => $catname,
          						'formatted_name' => str_repeat("&nbsp;-&nbsp;-&nbsp;",$depth_list[$n]) . $catname
													. ps_product_category::products_in_category( $category_tmp[$row_list[$n]]["category_child_id"] ),
								'id' => $category_tmp[$row_list[$n]]["category_child_id"],
								'parent' => $category_tmp[$row_list[$n]]["category_parent_id"],
								'depth' => $depth_list[$n]
							);
			
			}
			
			return $array;
		}
		function getProducts($category_id) {
			if( empty( $category_id )) return array();
			$db= new ps_DB();
			global $ps_vendor_id;
			$prefix = class_exists('vmlanguage') ? '{vm}' : 'pshop';
			
			$q = "SELECT * FROM (`#__{$prefix}_product` p, `#__{$prefix}_category` c, `#__{$prefix}_product_category_xref` cx) WHERE ";
			$q  .= "cx.category_id='".$category_id."' ";
			$q .= "\nAND c.category_id=cx.category_id ";
			$q .= "\nAND p.product_id=cx.product_id ";
			$q .= "\nAND p.vendor_id=$ps_vendor_id ";
			$q .= "\nAND p.product_parent_id='0' ";
			$q .= "\nORDER BY p.product_name ASC";
			
			$db->query( $q );
			$array = array();
			
			while( $db->next_record() ) {
				$array[] = array( 'name' => $db->f('product_name'),
						'id' => $db->f('product_id'),
                        'short_name' => substr( $db->f('product_name'), 0, 50 )
                        );
			}
			return $array;
		}
		function categoryTree()
		{
			require_once(CLASSPATH.'ps_product_category.php');
			//$category = new ps_product_category();
			$dirs = $this->getCategories();
			$d = '';		
			foreach( $dirs as $tree )
			{
				$id = $tree['id'];
				$parent = $tree['parent'];
				$name = $tree['name'];				
				$did = $id + 1;
				
				$d .= "parent.d.add('i" . $id . "','i" . $parent . "','" . $name . "', 'javascript:changeCat(\'". $id ."\')', '". $name ."');\n";
			}
			return $d;
		}
}

?>
