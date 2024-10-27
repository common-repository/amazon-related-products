<?php 
/**
 * Amazon Related Products
 * Author: Alain Gonzalez
 * Plugin URI: http://web-argument.com/amazon-related-products-wordpress-plugin/
*/

/**
  * Init
  *
*/ 

$amzrp_ads_number = 0;

if (!isset($amzrp_options)){
  $amzrp_options = amzrp_get_options();
}

if (!isset($amzrp_units)){
  $amzrp_units = new AMZRP_UNITS_FACTORY();
}

add_action( 'init', 'amzrp_init' );

function amzrp_init(){
  do_action('amzrp_units_init');
}


// Default Options
function amzrp_get_options ($default = false){
   
    global $amzrp_options;					   

	$amzrp_default = array(
							'region' => 'com',
							'public_key' => '',
							'private_key' => '',
							'associate_tag' => '',
							'keywords' => '',
							'search_index' => '',
							'unit' => 'List',		
							'units' => array(),							
							'use_tags' => 1,
							'insert_first' => 0,
							'insert_last' => 0,
							'version' => AMZRP_VERSION
							);
	
	if ($default) {
	  update_option('amzrp_op', $amzrp_default);
	  $amzrp_options = $amzrp_default;
	  return $amzrp_default;
	} 
	
    $options = get_option('amzrp_op');    
   
	if (isset($options) && !empty($options)){
		return $options;
	} else {
		return $amzrp_default;
	}
}

// if both logged in and not logged in users can send this AJAX request,
// add both of these actions, otherwise add only the appropriate one
add_action( 'wp_ajax_nopriv_amzrp_call', 'amzrp_call' );
add_action( 'wp_ajax_amzrp_call', 'amzrp_call' );
 
function amzrp_call($default = false) {

	if (!$default) {
	
		$nonce = $_POST['amzrp_nonce'];
		
		// check to see if the submitted nonce matches with the
		// generated nonce we created earlier
		if ( ! wp_verify_nonce( $nonce, 'amzrp-nonce' ) )
		   die ( 'Busted!');
	   
	}
	
	global $amzrp_options;	
	$options = $amzrp_options;
		
	extract($options);
	if (!$default){
	  extract($_POST);	
    }
				   
	$amzrp = new AMZRP_API_REQUEST( 
	                   $public_key, 
					   $private_key,
					   $associate_tag, 
					   $region 
					   );								   
			   
    $amzrp_response = $amzrp->getItemByKeyword($keywords, $search_index);
	
	if ($amzrp_response){

		amzrp_render_ad($unit,$amzrp_response);
	
	} else if (!$default){	
	
	   amzrp_call(true);
	}

	
	// IMPORTANT: don't forget to "exit"
	exit;
}

function amzrp_render_ad($unit,$amzrp_items) {    
	
	global $amzrp_units;

	if (!$amzrp_units->units[$unit]->is_enabled()){
		$unit = $amzrp_units->default_unit;
	}
	
	$amzrp_units->units[$unit]->render($amzrp_items);	
}


/**
  * The Sortcode
  *
*/  
add_shortcode('amz-related-products', 'amzrp_sc');

function amzrp_sc($atts) {
	
	global $post;
	global $amzrp_options;
	global $wp_scripts;
	global $amzrp_ads_number;
	global $amzrp_units;	
	
	$options = $amzrp_options;	
	
	extract($options);
	
	$ad_id = wp_generate_password(3, false);
	
	
	$tags_str = "";
	
	if ($use_tags){
		
		$posttags = get_the_tags();
	
		if ($posttags) {
		  $i = 0;
		  foreach($posttags as $tag) {
			$tags_str .= $tag->name. ',';
			if ($i == 2) break;
			$i++; 
		  }
		} 
		
	} 
	
	if (empty($tags_str)){	
		$tags_str = $keywords;
	}

	$amzrp_default_sc =array(
	                    'id' => $ad_id,
						'keywords' => $tags_str,
						'search_index' => $search_index,
						'unit' => $unit
						);			
		
	
	$final_atts = shortcode_atts($amzrp_default_sc, $atts);	
		
	extract($final_atts); 
	
	$json_request = json_encode(array_merge($final_atts, array(	'action' =>'amzrp_call',
																'div_pref' => 'ad-',
																"amzrp_nonce" => wp_create_nonce('amzrp-nonce')
																)));	
	
	if (!wp_script_is( 'amzrp-ajax-request')){

		wp_enqueue_script( 'amzrp-ajax-request', plugin_dir_url( __FILE__ ) . 'js/amzrp.0.1.js', array( 'jquery' ) );	
	
		$data = $wp_scripts->get_data('amzrp-ajax-request', 'data')."\n";
		$data .=  "var amzrpUrl = '".admin_url( 'admin-ajax.php' )."'; \n"; 
		$data .=  "var amzrpAds = new Array(); \n"; 
		$data .=  "amzrpAds[".$amzrp_ads_number++."] = ".$json_request."; \n";
		$wp_scripts->add_data('amzrp-ajax-request', 'data',$data);
		
	} else {
		
		$data = $wp_scripts->get_data('amzrp-ajax-request', 'data');
		$data .=  "amzrpAds[".$amzrp_ads_number++."] = ".$json_request."; \n";
		$wp_scripts->add_data('amzrp-ajax-request', 'data',$data);
	
	}
    
	$output = "";
	
	if (array_key_exists($unit,$amzrp_units->units)){

		$unit_to_render = $amzrp_units->units[$unit];

		$is_enabled = $unit_to_render->is_enabled();
		
        if (empty($is_enabled) || !$unit_to_render->is_enabled()) {
		    $unit_to_render = $amzrp_units->units[$amzrp_units->default_unit];
		} 
		$output .= $unit_to_render->pre_render($ad_id);
	}	

    
	return $output;	
}


/**
  * Automatization
  *
*/  

add_filter( 'the_content', 'amzrp_add_shc', 20 );

function amzrp_add_shc( $content ) {

    if( !is_single() ) {
	
	  	return $content;
		
	} else {
		
		global $amzrp_options;
		
		$options = $amzrp_options;	
		
		extract($options);	
		
		$new_content = "";
			
		if ($insert_first){
	
			$new_content = do_shortcode("[amz-related-products]").$content;
			
		} else {
			
		   $new_content = $content;
		}
		
		if ($insert_last) {
			
			$new_content = $new_content.do_shortcode("[amz-related-products]");
			
		}
	
		// Returns the content.
		return $new_content;
	
	}
}



/**
  * Admin
  *
*/ 

if ( is_admin() ){ // admin actions

	// Create menu 
	add_action( 'admin_menu', 'amzrp_plugin_menu' );	

	// Adding WordPress plugin action links	
	add_filter( 'plugin_action_links_' . AMZRP_PLUGIN_FILE, 'amzrp_plugin_action_links' ); 
	
	// Adding WordPress plugin meta links 
	add_filter( 'plugin_row_meta', 'amzrp_plugin_meta_links', 10, 2 );
	
	// Ajax request to after region selection
	add_action( 'wp_ajax_amzrp_select_region', 'amzrp_select_region' );	
	
	// Ajax request previews
	add_action( 'wp_ajax_amzrp_previews', 'amzrp_previews' );		
	
	// Enqueue admin script
	add_action( 'admin_enqueue_scripts', 'amzrp_admin_enqueue_scripts' );
	 
  
} 


function amzrp_admin_enqueue_scripts($hook) {
	
    if( 'settings_page_amz-related-products' == $hook || 'media-upload-popup' == $hook ) {
       
    wp_enqueue_script( 'amzrp_admin_script', plugin_dir_url( __FILE__ ) . 'js/amzrp.admin.js', array( 'jquery' ) );
	wp_localize_script( 'amzrp_admin_script', 'amzrpSettings', array( 'ajaxUrl' => admin_url( 'admin-ajax.php' ), 
	                                                                  'amzrp_admin_nonce' =>  wp_create_nonce('amzrp-admin-nonce'), 
																	  'action' => 'amzrp_select_region') );
	wp_register_style( 'amzrp_admin_style', plugins_url('/styles/amzrp-admin-style.css', __FILE__) );
	wp_enqueue_style( 'amzrp_admin_style' );
	
	do_action('amzrp_admin_unit_script');
																		  
	} else {
		 return;
	}
}

function amzrp_select_region(){

	$nonce = $_POST['amzrp_admin_nonce'];
 
	// check to see if the submitted nonce matches with the
	// generated nonce we created earlier
	if ( ! wp_verify_nonce( $nonce, 'amzrp-admin-nonce' ) )
       die ( 'Busted!');
	   
	// generate the response
	$response = json_encode( amzrp_get_search_index($_POST['region']) );
 
	// response output
	header( "Content-Type: application/json" );
	echo $response;

	// IMPORTANT: don't forget to "exit"
	exit;
		
}


function amzrp_previews(){

	$nonce = $_POST['amzrp_admin_nonce'];
 
	// check to see if the submitted nonce matches with the
	// generated nonce we created earlier
	if ( ! wp_verify_nonce( $nonce, 'amzrp-admin-nonce' ) )
       die ( 'Busted!');

    extract($_POST);
	$amzrp = new AMZRP_API_REQUEST( 
								   $public_key, 
								   $private_key, 
								   $associate_tag, 
								   $region 
								   );
			   
    $amzrp_response = $amzrp->getItemByKeyword($keywords, $search_index);
	
	if ($amzrp_response){
	
		amzrp_render_ad($unit,$amzrp_response);
	
	} else {
	
	   _e("<div class='error'>
             <p><strong>Error, your request failed. Please, check the following items:</strong>
			   <ul>
					  <li>Check your Amazon credentials.</li>
					  <li>Try another category, keywords combination.</li>
					  <li>Review your firewall connection.</li>
					  <li>Check if curl is enabled in your PHP server.</li>
					  <li>Check if your server allows file_get_contents script.</li>
			   <ul>
			  </p> 
		  <div>");
	   
	}

	// IMPORTANT: don't forget to "exit"
	exit;
		
}


function amzrp_plugin_menu() {
	add_options_page( 'Amazon Related Products Options', 'Amazon Related Products', 'manage_options', AMZRP_PLUGIN_ID, 'amzrp_plugin_options' );
}


function amzrp_plugin_options() {
	global $amzrp_units;
	global $amzrp_options;
	
	if (!isset($amzrp_options)){
	  $amzrp_options = amzrp_get_options();
	}
	
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	$options = amzrp_get_options();
	
	?>
    
	<div class="wrap">   
	
	<h2><?php _e("Amazon Related Products Settings") ?></h2>

	<?php echo amzrp_plugin_embed_links(); ?>
	
	<?php

    if (count($_POST) > 0 ){		
		
		if(isset($_POST['Restore_Default'])) {
		
			$options = amzrp_get_options(true);
			$amzrp_units->reset_units();
						
		} else {
			
			if(isset($_POST['Submit'])){
		
				  $_POST['use_tags'] = (!isset($_POST['use_tags']))? 0 : 1;
				  $_POST['insert_first'] = (!isset($_POST['insert_first']))? 0 : 1;
				  $_POST['insert_last'] = (!isset($_POST['insert_last']))? 0 : 1;
	  
		  
				  $newoptions = amzrp_array_merge($options , $_POST );
	  
				  if ( $options != $newoptions ){
					  
					  $options = $newoptions;
					  
					  extract($options);					
					  
					  if (empty ($public_key) || empty ($private_key) || empty ($associate_tag)) {
						 echo "<div class='error'><p><strong>".__("Please include your Amazon credentials.")."</strong></p></div>";		   
					  } else {					
						  echo "<div class='updated'><p>".__("The Options has been updated!")."</p></div>";						  
						  
						  $amzrp_options = $options;						  
					  }
						  
				  }						
	    
 	        } 
			
			$amzrp_units->update($_POST);
			
			update_option('amzrp_op', $amzrp_options);		     
			 
		}

	}

    extract($options);					

   ?>  
	
	<form method="POST" name="options" target="_self" enctype="multipart/form-data">
	
	<h2><?php _e("API - Associate Configuration") ?></h2>
    
	<p><?php _e("Please provide your credentials. If you don't have an Amazon affiliate ID or access keys please, visit <a href='https://affiliate-program.amazon.com/' target='_blank'>Amazon Associates</a> and <a href='https://affiliate-program.amazon.com/gp/advertising/api/detail/main.html' target='_blank'>Product Advertising API</a> to get it, its free! ") ?></p>
	
    <table width="80%" border="0" cellspacing="10" cellpadding="0">
      <tr>
        <td width="200" align="right"><strong><?php _e("Amazon Access Key Id") ?></strong></td>
        <td><input name="public_key" id="public_key" type="text" size="55" value="<?php echo $public_key ?>" /></td>
      </tr>
      <tr>
        <td align="right"><strong><?php _e("Amazon Secret Access Key") ?></strong></td>
        <td><input name="private_key" id="private_key" type="text" size="55" value="<?php echo $private_key ?>" /></td>
      </tr>
      <tr>
        <td align="right"><strong><?php _e("Amazon Associate Tag") ?></strong></td>
        <td><input name="associate_tag" id="associate_tag" type="text" size="20" value="<?php echo $associate_tag ?>" /></td>
      </tr>  
      <tr>
        <td width="200" align="right"><strong><?php _e("Region") ?></strong></td>
        <td>
          <select name="region" id="region">
          <?php		
                   global $amzrp_regions;
                   foreach($amzrp_regions as $key => $value){
                        echo "<option value='".$key."' ".(($key == $region)?"selected":"").">".$value."</option>";
                   }
          ?>
          </select>    
        </td>
      </tr>       
    </table> 
    
	<h2 style="padding-top:30px; margin-top:30px; border-top:1px solid #CCCCCC;"><?php _e("Default Configuration") ?></h2>
	
	<p><?php _e("These values will be overridden by the shortcode options.") ?></p>    
    
    <?php amzrp_deafult_options($options); ?> 
    
    <input type="submit" name="Submit" value="<?php _e("Update") ?>" class="button-primary" />&nbsp; &nbsp;
    <input type="button" id="previews" name="Previews" value="<?php _e("Previews") ?>" class="button" />&nbsp;&nbsp;
    <img src="<?php echo AMZRP_PLUGIN_URL."/images/ajax-loader.gif" ?>" style="display:none" id="test_ind"/>
    <div id="previews-cont" style="clear:both; overflow:hidden; padding:20px"></div>   

	<h2 style="padding-top:30px; margin-top:30px; border-top:1px solid #CCCCCC;"><?php _e("Automatization") ?></h2>	

    <table width="80%" border="0" cellspacing="10" cellpadding="0">
      <tr>
        <td width="200" align="right"><input name="use_tags" type="checkbox" value="1" <?php if ($use_tags == "1") echo "checked = \"checked\"" ?> /></td>        
        <td><strong><?php _e("Use tags as keywords") ?></strong></td>
      </tr>

      <tr>
        <td align="right"><input name="insert_first" type="checkbox" value="1" <?php if ($insert_first == "1") echo "checked = \"checked\"" ?> /></td>        
        <td><strong><?php _e("Insert products at the beginning of all the posts") ?></strong></td>
      </tr> 
      
      <tr>
        <td align="right"><input name="insert_last" type="checkbox" value="1" <?php if ($insert_last == "1") echo "checked = \"checked\"" ?> /></td>        
        <td><strong><?php _e("Insert products at the end of all the posts") ?></strong></td>
      </tr>             
    </table> 
          
    <p class="submit">
    <input type="submit" name="Submit" value="<?php _e("Update") ?>" class="button-primary" />
    </p>
    
    <h2 style="padding-top:30px; margin-top:30px; border-top:1px solid #CCCCCC;"><?php _e("Units Configuration") ?></h2>
    
    <?php $amzrp_units->render(); ?>


    <p class="submit" style="padding-top:30px; margin-top:30px; border-top:1px solid #CCCCCC;">
    
    <?php echo amzrp_plugin_embed_links(); ?>
    
    <input type="submit" name="Submit" value="<?php _e("Update") ?>" class="button-primary" />&nbsp; &nbsp;<input type="submit" name="Restore_Default" value="<?php _e("Restore Default") ?>" class="button" />
    </p>
    </form> 
   
    
    </div>
<?php    
}

function amzrp_deafult_options($options){
	
	global $amzrp_units;
	extract($options);
	 
	?>

    <table width="80%" border="0" cellspacing="10" cellpadding="0">
    
         <?php if (get_admin_page_title() != "Amazon Related Products Options"){	?>             
      <tr>
        <td width="200" align="right"><strong><?php _e("Region") ?></strong></td>
        <td>
            <input name="region-deafault" id="region-deafault" type="text" size="23" value="<?php echo amzrp_get_region($region) ?>" disabled="disabled" />
            <input name="region" id="region" type="hidden" size="23" value="<?php echo $region ?>" />    
        </td>
      </tr> 
        <?php  }  ?>
      <tr>
        <td align="right" width="200"><strong><?php _e("Amazon Category") ?></strong></td>
        <td>

        <select name="search_index" id="search_index">
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
        <td><input name="keywords" id="keywords" type="text" size="23" value="<?php echo $keywords ?>" /></td>
      </tr>

      <tr>
        <td align="right"><strong><?php _e("Unit") ?></strong></td>
        <td>
        <select name="unit" id="unit">        
        <?php
		   $units_enabled = $amzrp_units->get_enables();
		   if (count($units_enabled) > 0){	
			   foreach($units_enabled as $unit_obj){
					echo "<option value='".$unit_obj->id."' ".(($unit_obj->id == $unit)?"selected":"").">".$unit_obj->name."</option>";
			   }
		   } else {
			   echo "<option value='' >".__("Please, enable one")."</option>";
		   }
        ?>
        
        </select>   
        </td>
      </tr>
          
    </table> 
    
    <?php
	
}

function amzrp_plugin_action_links( $links ) {
 
	return array_merge(
		array(
			'settings' => '<a href="' . admin_url( 'admin.php?page='.AMZRP_PLUGIN_ID ) . '">'.__( 'Settings' ).'</a>'
		),
		$links
	);
 
}


function amzrp_plugin_meta_links( $links, $file ) {
 
	// create link
	if ( $file == AMZRP_PLUGIN_FILE ) {
		return array_merge(
			$links,
			array( '<a href="http://web-argument.com/amazon-related-products-wordpress-plugin/?utm_source=plugin_link&utm_medium=options&utm_campaign=amzrp&utm_content=demo#examples" target="_blank">Live Demo</a>',
			       '<a href="http://web-argument.com/amazon-related-products-wordpress-plugin/?utm_source=plugin_link&utm_medium=options&utm_campaign=amzrp&utm_content=how_to_use#how-to-use" target="_blank">How To Use</a>'			
			 )
		);
	}
	return $links;
 
}


function amzrp_plugin_embed_links(){ 

   $links_arr = array(
   						array("text"=>__("Plugin Page"),"url"=>"http://web-argument.com/amazon-related-products-wordpress-plugin/?utm_source=plugin_link&utm_medium=options&utm_campaign=amzrp&utm_content=plugin_page"),
						array("text"=>__("How To Use"),"url"=>"http://web-argument.com/amazon-related-products-wordpress-plugin/?utm_source=plugin_link&utm_medium=options&utm_campaign=amzrp&utm_content=how_to_use#how-to-use"),
						array("text"=>__("Examples"),"url"=>"http://web-argument.com/amazon-related-products-wordpress-plugin/?utm_source=plugin_link&utm_medium=options&utm_campaign=amzrp&utm_content=demo#examples"),
						array("text"=>__("Donate"),"url"=>"https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=support%40web%2dargument%2ecom&lc=US&item_name=Web%2dArgument%2ecom&currency_code=USD&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted")
						);
						
   $output = "<p align='center' style='font-size:14px;'>";
   						
   foreach ($links_arr as $link){
	   $output .= "<a href=".$link['url']." target='_blank'>".$link['text']."</a> &nbsp; ";	   
   }
   
   $output .= "</p>";
   
   return $output;   	

}





/**
 * Widget
 */

// register AMZRP_Widget 
add_action( 'widgets_init', create_function( '', 'register_widget( "AMZRP_Widget" );' ) );



/**
 * Editor Button
 */
 
add_action('media_buttons', 'amzrp_media_buttons', 20);
function amzrp_media_buttons($admin = true)
{
	global $post_ID, $temp_ID;

	$media_upload_iframe_src = get_option('siteurl').'/wp-admin/media-upload.php?post_id=$uploading_iframe_ID';

	$iframe_title = __('Amazon Related Products','amzrp-tab');
	
	$tab_name = "amzrp";

	echo "<a class=\"thickbox\" href=\"media-upload.php?post_id={$post_ID}&amp;tab=$tab_name&amp;TB_iframe=true&amp;height=480&amp;width=680\" title=\"$iframe_title\"><img src=\"".AMZRP_PLUGIN_URL."/images/amz-related-products.png\" alt=\"$iframe_title\" /></a>";
	
}

add_action('media_upload_amzrp', 'amzrp_tab_handle');

function amzrp_tab_handle() {
	return wp_iframe('amzrp_tab_process');
}

function amzrp_tab_process($admin = true)
{
	global $amzrp_options;	
	$options = $amzrp_options;
	?>
    <div><h1 style="padding: 0 16px; font-size: 22px; font-weight: 200; line-height: 45px; margin: 0;"><?php _e("Insert Amazon Related Products") ?></h1></div>    
    <?php
	amzrp_deafult_options($options);
	?>
    <table width="80%" border="0" cellspacing="10" cellpadding="0" id="amzrp-sc-cont">
      <tr>
        <td width="200" align="right" valign="top"><strong><?php _e("Shortcode") ?></strong></td>        
        <td><textarea name="amzrp-sc" id="amzrp-sc" cols="45" rows="8">[amz-related-products]</textarea></td>
      </tr>
    </table>
    <div style="padding:30px; ">
        <input type="button" name="amzrp-add-sc" id="amzrp-add-sc" value="<?php _e("Add Shortcode") ?>" class="button-primary" />&nbsp; &nbsp;
        <input type="button" name="amzrp-reset-sc" id="amzrp-reset-sc" value="<?php _e("Reset") ?>" class="button" />
    </div>    
    <?php
}


/**
 * Adding media tab
 */

add_filter('media_upload_tabs', 'amzrp_media_menu'); 
 
function amzrp_media_menu($tabs) {
$newtab = array('amzrp' => __('Amazon Related Products', 'amzrp-tab'));
return array_merge($tabs, $newtab);
}


/**
 * Misc
 */
 
function amzrp_array_merge($default, $new) {
	$out = $default;
	foreach($default as $key => $value) {
		if ( array_key_exists($key, $new) ){
			//if ( !empty ($new[$key]) ) {
			  $out[$key] = $new[$key];
			//}
		} else {
			$out[$key] = $default[$key];
		}
	}
	return $out;
}

function amzrp_get_attr($item,$amzrp_items_obj){

		foreach($amzrp_items_obj->OperationRequest->Arguments->Argument as $arg) {
		
		    $attr = $arg->attributes();

			$name = $attr['Name'];
			$value = $attr['Value'];
			
			if ($name == $item){
			    return date('G:i:s T',strtotime($value));
			}

        }

}

function amzrp_get_price($item, $amzrp_items_obj){

	global $amzrp_options;
	global $amzrp_disclosure;
	
	$timestamp = amzrp_get_attr("Timestamp",$amzrp_items_obj);

	
	$price = (isset($item->ItemAttributes->ListPrice->FormattedPrice))?__("<span class='amzrp-price-list'>List: <span class='amzrp-lt'>").$item->ItemAttributes->ListPrice->FormattedPrice."</span></span><br />":"";
	$new = (isset($item->OfferSummary->LowestNewPrice->FormattedPrice))?__("<span class='amzrp-price-new'>New From: <span class='amzrp-red'>").$item->OfferSummary->LowestNewPrice->FormattedPrice."</span></span><br />":"";
	$used = (isset($item->OfferSummary->LowestUsedPrice->FormattedPrice))?__("<span class='amzrp-price-used'>Used From: <span class='amzrp-red'>").$item->OfferSummary->LowestUsedPrice->FormattedPrice."</span></span><br />":"";

	
	$full_price = "<span class='amzrp-price'><span class='amzrp-price-headline'>Amazon.".$amzrp_options['region']." Price</span><br />".
					  $price.
					  $new.
					  $used.
					  "<span class='amzrp-disclosure-cont'>(as of ".$timestamp.
					  " - <a href='#' class='amzrp-more-info'>More info</a>)</spa></span>".					  
					  "<span class='amzrp-disclosure'>".amzrp_get_disclosure()."<a href='#' class='close-disclosure'>".__("Close")."</a></span>";
				  
	return 	$full_price;
			  
}


?>