<?php
/**
 * Amazon Related Products
 * Author: Alain Gonzalez
 * Unit: Grid
 * Plugin URI: http://web-argument.com/amazon-related-products-wordpress-plugin/
*/

class AMZRP_Grid extends AMZRP_UNIT {

    public $default = array('title'=>'Amazon Related Products',
						    'width' => 550, 
						    'height' => 900, 
						    'number' => 6 );  
						   
	public function __construct() {		
		parent::__construct(		    
	 		'grid', // ID
			'Grid', // Name
			__("Display products in a grid of multiple columns."),
			$this->default// Args
		);
	}

	public function pre_render($id){
		wp_register_style( 'amzrp_grid_style', plugins_url('/styles/amzrp-grid.css', __FILE__) );
		wp_enqueue_style( 'amzrp_grid_style' );		
		return "<div id='ad-".$id."' class='amzrp-grids'></div>";
	}

	public function render($amzrp_items_obj){	
		
		$amzrp_items = $amzrp_items_obj->Items->Item;
		
		$amzrp_ad = "<div class='amzrp-grid-cont' style='width:".$this->options['width']."px; height:".$this->options['height']."px'>".
					"<h3>".$this->options['title']."</h3>";
		$i = 0;				 
		foreach($amzrp_items as $item){	
		
		    $title = (strlen($item->ItemAttributes->Title) > 65) ? substr($item->ItemAttributes->Title,0,65).'...' : $item->ItemAttributes->Title;	
			$binding = ($item->ItemAttributes->Binding != "")?"<span class='amzrp-list-binding'>".$item->ItemAttributes->Binding."</span><br />":"";
			$price = amzrp_get_price($item, $amzrp_items_obj);
					  	
			$amzrp_ad .= "<div class='amzrp-grid'>".
						     "<a href='".$item->DetailPageURL."' class='amzrp-grid-thumb-cont' target='_blank'><img src='".$item->MediumImage->URL."' class='amzrp-grid-thumb' width='".$item->MediumImage->Width."' height='".$item->MediumImage->Height."'/></a>".
						     "<a href='".$item->DetailPageURL."' target='_blank'><span class='amzrp-grid-title'>".$title."</span></a><br />".
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
			<td width="30" align="right"><?php _e("Width") ?></td>
			<td><input name="<?php echo $this->get_name('width') ?>" id="<?php echo $this->get_id('width') ?>" type="text" size="5" value="<?php echo $this->get_option('width') ?>"  <?php echo ($this->is_enabled())?"":"disabled" ?>/></td>
		  </tr>
		  <tr>
			<td width="30" align="right"><?php _e("Height") ?></td>
			<td><input name="<?php echo $this->get_name('height') ?>" id="<?php echo $this->get_id('height') ?>" type="text" size="5" value="<?php echo $this->get_option('height') ?>" <?php echo ($this->is_enabled())?"":"disabled" ?>/></td>
		  </tr>
		  <tr>
			<td width="30" align="right"><?php _e("Number") ?></td>
			<td><input name="<?php echo $this->get_name('number') ?>" id="<?php echo $this->get_id('number') ?>" type="text" size="3" value="<?php echo $this->get_option('number') ?>" <?php echo ($this->is_enabled())?"":"disabled" ?>/></td>
		  </tr>                    
        </table>
         <?php
	}	

}

add_action( 'amzrp_units_init', create_function( '', 'amzrp_register_unit( "AMZRP_Grid" );' ) );

add_action('amzrp_admin_unit_script','amzrp_admin_grid_script');

function amzrp_admin_grid_script(){
	wp_register_style( 'amzrp_grid_style', plugins_url('/styles/amzrp-grid.css', __FILE__) );
	wp_enqueue_style( 'amzrp_grid_style' );	
}

?>