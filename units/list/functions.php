<?php
/**
 * Amazon Related Products
 * Author: Alain Gonzalez
 * Unit: List
 * Plugin URI: http://web-argument.com/amazon-related-products-wordpress-plugin/
*/

class AMZRP_List extends AMZRP_UNIT {

    public $default = array('title'=>'Amazon Related Products',
						    'number' => 5, 
							'enable'=>true );

	public function __construct() {
		parent::__construct(
	 		'list', // ID
			'List', // Name
			__("Display products in a list of items with thumbnails."),
			$this->default // Args
		);
	}
	
	public function pre_render($id){
		wp_register_style( 'amzrp_list_style', plugins_url('/styles/amzrp-list.css', __FILE__) );
		wp_enqueue_style( 'amzrp_list_style' );		
		return "<div id='ad-".$id."' class='amzrp'></div>";
	}
	
	public function render($amzrp_items_obj){
		
		$amzrp_items = $amzrp_items_obj->Items->Item;
	
		$amzrp_ad = "<div class='amzrp-list-cont'>".
					"<h3>".$this->options['title']."</h3>";
						 
     	$i = 0;				 
		foreach($amzrp_items as $item){		    	
		    $title = (strlen($item->ItemAttributes->Title) > 70) ? substr($item->ItemAttributes->Title,0,70).'...' : $item->ItemAttributes->Title;	
			$binding = ($item->ItemAttributes->Binding != "")?"<span class='amzrp-list-binding'>".$item->ItemAttributes->Binding."</span><br />":"";
			$price = amzrp_get_price($item, $amzrp_items_obj);
			
			$amzrp_ad .= "<div class='amzrp-list'>".
						 	 "<a href='".$item->DetailPageURL."' class='amzrp-list-thumb-cont' target='_blank'><img src='".$item->SmallImage->URL."' width='".$item->SmallImage->Width."' height='".$item->SmallImage->Height."' /></a>".
						 	 "<a href='".$item->DetailPageURL."' target='_blank'><span class='amzrp-list-title'>".$title."</span></a><br />".
							 $binding.
						     $price.
						 "</div>";
			if ($i == ($this->options['number'] - 1)) break;
			$i++;			 
		}
		$amzrp_ad .= "</div>";
		echo $amzrp_ad;
	}	
	
	public function form(){ 
	     ?>         
		<table border="0" cellspacing="10" cellpadding="0">
 		  <tr>
			<td width="30" align="right"><?php _e("Title") ?></td>
			<td><input name="<?php echo $this->get_name('title') ?>" id="<?php echo $this->get_id('title') ?>" type="text" size="25" value="<?php echo $this->get_option('title') ?>" <?php echo ($this->is_enabled())?"":"disabled" ?>/></td>
		  </tr>       
		  <tr>
			<td width="30" align="right"><?php _e("Number") ?></td>
			<td><input name="<?php echo $this->get_name('number') ?>" id="<?php echo $this->get_id('number') ?>" type="text" size="3" value="<?php echo $this->get_option('number') ?>" <?php echo ($this->is_enabled())?"":"disabled" ?>/></td>
		  </tr>
        </table>  
         <?php
	}	

}

add_action( 'amzrp_units_init', create_function( '', 'amzrp_register_unit( "AMZRP_List" );' ) );

add_action('amzrp_admin_unit_script','amzrp_admin_list_script');

function amzrp_admin_list_script(){
	wp_register_style( 'amzrp_list_style', plugins_url('/styles/amzrp-list.css', __FILE__) );
	wp_enqueue_style( 'amzrp_list_style' );	
}

?>