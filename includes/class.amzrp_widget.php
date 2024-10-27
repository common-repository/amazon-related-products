<?php
/**
 * Amazon Related Products
 * Author: Alain Gonzalez
 * Plugin URI: http://web-argument.com/amazon-related-products-wordpress-plugin/
*/

class AMZRP_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
	 		'amzrp_widget', // Base ID
			'Amazon Related Products', // Name
			array( 'description' => __( 'Amazon Related Products Widget based on your keywords') ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );

		echo $before_widget;			
		if (count($instance)){
			$parm = "";
			foreach ($instance as $key => $value){	
		       $params .= $key."='".$value."' "; 
			}
			echo do_shortcode('[amz-related-products '.$params.']');
		}
		echo $after_widget;
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		foreach ($new_instance as $key => $value){
		  $instance[$key] = strip_tags( $value );
		}

		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		
		global $amzrp_options;	
		$options = $amzrp_options;
		
		$instance = amzrp_array_merge($options,$instance);
		
		extract($instance);

		
?>		
    <table width="80%" border="0" cellspacing="10" cellpadding="0">
    
      <tr>
        <td width="200" align="right"><strong><?php _e("Region") ?></strong></td>
        <td>
            <input name="region-deafault" type="text" size="23" value="<?php echo amzrp_get_region(esc_attr( $instance["region"] )) ?>" disabled="disabled" />
            <input id="<?php echo $this->get_field_id( "region" ) ?>" type="hidden" size="23" name="<?php echo $this->get_field_name( "region" ) ?>" value="<?php echo esc_attr( $instance["region"] ) ?>" />    
        </td>
      </tr> 

      <tr>
        <td align="right"><strong><?php _e("Amazon Category") ?></strong></td>
        <td>

        <select id="<?php echo $this->get_field_id( "search_index" ) ?>" name="<?php echo $this->get_field_name( "search_index" ) ?>" >
        <?php		
		         $amzrp_search_index = amzrp_get_search_index($region);
				 foreach($amzrp_search_index as $value){
                      echo "<option value='".$value."' ".(($value == $search_index)?"selected":"").">".$value."</option>";
				 }
        ?>
        </select>   
        </td>
      </tr>

      <tr>
        <td align="right"><strong><?php _e("Keywords") ?></strong></td>
        <td>
        <input id="<?php echo $this->get_field_id( "keywords" ) ?>" type="text" size="23" name="<?php echo $this->get_field_name( 'keywords' ) ?>" value="<?php echo esc_attr( $instance['keywords'] ) ?>" />
        </td>
      </tr>              

      <tr>
        <td align="right"><strong><?php _e("Unit") ?></strong></td>
        <td>
        <select id="<?php echo $this->get_field_id( "unit" ) ?>" name="<?php echo $this->get_field_name( 'unit' ) ?>">
        <?php
		   global $amzrp_units;
		   $units_enabled = $amzrp_units->get_enables();		
		   foreach($units_enabled as $unit_obj){
				echo "<option value='".$unit_obj->id."' ".(($unit_obj->id == $unit)?"selected":"").">".$unit_obj->name."</option>";
		   }
        ?>
        </select>   
        </td>
      </tr>
          
    </table> 
<?php 
	}

} // class AMZRP_Widget

?>