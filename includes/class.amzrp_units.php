<?php
/**
 * Amazon Related Products
 * Author: Alain Gonzalez
 * Plugin URI: http://web-argument.com/amazon-related-products-wordpress-plugin/
*/

$amzrp_units_preview = array(
                             "list"=>array(
							               "name"=>"List",
							               "description"=>__("Display products in a list of items with thubnails"),
							               "link"=>"http://yahoo.com",
										   "image"=>"list.png"
										   ),
							 "grid"=>array(
							               "name"=>"Grid",
							               "description"=>__("Display products in a grid of multiple columns"),
							               "link"=>"http://yahoo.com",
										   "image"=>"grid.png"
										   )									   										   
										   
						);							 			   


class AMZRP_UNIT
{
	public $id;
	
	public $name;
	
	public $description;
	
	public $prefix = "amzrp_unit_";
	
	public $options = array();
	
	function __construct( $id, $name, $description, $options ) {
	     $this->id = $id;
		 $this->name = $name;
		 $this->description = $description;
		 $this->options = $options;		
	}
	
	public function form(){
		exit ("Must be over-ridden in a sub-class");
	}
	
	public function pre_render($id){
		exit ("Must be over-ridden in a sub-class");
	}
	
	public function render($options){
		exit ("Must be over-ridden in a sub-class");
	}
	
	public function get_name($elem){
		return $this->prefix.$this->id."[".$elem."]";		
	}
	
	public function get_id($elem){		
       return $this->prefix.$this->id."_".$elem;		
	}
	
	public function get_option($elem){
		
       global $amzrp_units;
	   $options = $amzrp_units->get_options($this->id);
	   
	   if (array_key_exists($elem,$options)){
		   return $options[$elem];
	   } else {
		   return false;
	   }	   
		
	}
	
	public function is_enabled(){
		if (array_key_exists('enable',$this->options) && $this->options['enable']){
		   return true;
		} else {
			return false;
		}			
	}
	

	public function unit_enable(){
		$this->options['enable'] = true;
	}
	
	public function unit_disable(){
		$this->options['enable'] = false;
	}		
	
}



class AMZRP_UNITS_FACTORY {
	
	public $units = array();
	
	public $default_unit = 'list';
	
	function __construct() {
		$this->load_units();
	}

	public function register($unit_class) {	   
		$unit = new $unit_class();
		if ($this->is_register($unit->id)){
		    unset($unit);
		} else {		 
			$this->units[$unit->id] = $unit;
			$this->units[$unit->id]->options = $this->get_options($unit->id);
		}		
	}

	public function is_register($id){
	   if (array_key_exists($id, $this->units)){
	       return true;
	   } else {
	     return false;	   
	   }
	}
	
	public function load_units() {
	
		$amzrp_units_list = array ();
		$units_root = AMZRP_PLUGIN_DIR."/units";
	
		$units_dir = @ opendir( $units_root);
		$units_files = array();
		if ( $units_dir ) {
			while (($file = readdir( $units_dir ) ) !== false ) {
				if ( substr($file, 0, 1) == '.' )
					continue;							
				if ( is_dir( $units_root.'/'.$file ) ) {				
					$units_subdir = @ opendir( $units_root.'/'.$file );				
					if ( $units_subdir ) {
						while (($subfile = readdir( $units_subdir ) ) !== false ) {
							
							if ( substr($subfile, 0, 1) == '.' )
								continue;
							if ( substr($subfile, -4) == '.php' )
								$amzrp_units_list[] = "$file";
						}
						closedir( $units_subdir );
					}
				} 
			}
			closedir( $units_dir );
		}
	
		if ( count($amzrp_units_list) > 0 ) {
			foreach ($amzrp_units_list as $unit) {
				$fn_file = $units_root."/".$unit."/functions.php";
				if (file_exists($fn_file)){
						require_once ($fn_file);
				}
	
			}
			
			return $amzrp_units_list;
		}	
	
	}
	
	public function render(){
		global $amzrp_units_preview;
			echo "<div id='unit_wrapper'>";
            
            // Default first
			if (array_key_exists( $this->default_unit,$this->units) ) {
				$default_unit = $this->units[$this->default_unit];
				unset($this->units[$this->default_unit]);
				$this->units = array_merge ( array($default_unit->id => $default_unit) , $this->units );
			}

			foreach ($this->units as $unit) {
			     echo "<div class='unit_cont'>";              
					 echo "<h3>".$unit->name."</h3>"; 
					 echo "<p>".$unit->description."</p>";
					 $unit->form();
					 if($unit->is_enabled()){
					   echo "<input type=\"submit\" name=\"Submit\" value=\"".__("Update")."\" class=\"button-primary\" />&nbsp; &nbsp;";
					   if ( $unit->id != $this->default_unit) {
					      echo "<input type=\"submit\" name=\"".$unit->get_name('disable')."\" class=\"button disable\" value=\"".__("Disable")."\" />";
					   }
					 } else {
					   echo "<input type=\"submit\" name=\"".$unit->get_name('enable')."\" class=\"button\" value=\"".__("Enable")."\" />&nbsp;&nbsp;";
					 }
					 echo "</div>";			
			}
			
			foreach ($amzrp_units_preview as $key=>$unit_preview) { 
			                 
				 if (!array_key_exists($key,$this->units)){
					 echo "<div class='unit_cont'>";  
					 $link = "<a href='".$unit_preview['link']."' target='_blank' title='".$unit_preview['name']."'>";
					 $link_close = "</a>";
					 echo "<h3>".$unit_preview['name']."</h3>";
					 echo "<p>".$unit_preview['description']."</p>"; 
					 echo $link."<img src='".AMZRP_PLUGIN_URL."/images/units/".$unit_preview["image"]."' />".$link_close;
					 echo "<div class='clearfix'></div>";
					 echo $link."<input type='submit' name='amzrp_unit_install' value='".__("Install")."' class='button' />".$link_close;
					 echo "</div>";
				 }
				 
			}
			echo "</div>";
		
	}
	
	public function update($post) {
		global $amzrp_options;
		$units_options = $amzrp_options["units"];
		$units = $this->units;
						
		foreach($units as $unit){
			$unit_id = $unit->prefix.$unit->id;
			$unit_options = $unit->options;
			$new_unit_options = array();
			if (array_key_exists($unit_id,$post)){
				if (isset($post[$unit_id]['enable'])){
					$this->units[$unit->id]->unit_enable();
				} else if (isset($post[$unit_id]['disable'])){
					$this->units[$unit->id]->unit_disable();
				} else {
					$this->units[$unit->id]->options = amzrp_array_merge($unit_options, $post[$unit_id]);				
				}
			}
		}
		$this->update_options();
	}
	
	public function update_options() {
		
		global $amzrp_options;
		
        $new_units_op = array();
		
		foreach ($this->units as $unit){			
            $new_units_op[$unit->id] = $unit->options;			
		}
		
		$amzrp_options['units'] = $new_units_op;
		
	}
	
	public function get_options($id) {
		global $amzrp_options;	
		
    	$units = $amzrp_options["units"];

		if (array_key_exists($id,$units)){
			return $units[$id];
		} else if (array_key_exists($id,$this->units)){
			return $this->units[$id]->options;
		} else {			
			return array();
		}
	}

    public function get_enables() {
		$enables = array();
		foreach ($this->units as $unit){
			print_r($unit->options);
			if (array_key_exists('enable',$unit->options) && $unit->options['enable']){
				array_push($enables,$unit);
			}
		}
		return $enables;
				
	}

	public function reset_units(){
		foreach ($this->units as $unit){
			$this->units[$unit->id]->options = $this->units[$unit->id]->default;
		}
		$this->update_options();
	}
		
}

function amzrp_register_unit($unit_class){
	
	global $amzrp_units;

	$amzrp_units->register($unit_class);	

}

function amzrp_get_disclosure(){
	global $amzrp_options;
	$disclosure = __("Product prices and availability are accurate as of the date/time indicated and are subject to change. Any price and availability information displayed on amazon.").$amzrp_options['region'].__(" at the time of purchase will apply to the purchase of this product.");
	return $disclosure;
}




?>