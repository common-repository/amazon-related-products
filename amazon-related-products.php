<?php
/*
Plugin Name: Amazon Related Products 
Plugin URI: http://web-argument.com/amazon-related-products-wordpress-plugin/?utm_source=plugin_link&utm_medium=options&utm_campaign=amzrp&utm_content=plugin
Description: Insert contextual Amazon products into your content.  
Version: 0.1.1
Author: Alain Gonzalez
Author URI: http://web-argument.com/
*/

/*  Copyright 2011  Alain Gonzalez-Garcia  (email : alaingoga@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as 
    published by the Free Software Foundation.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

define('AMZRP_PLUGIN_ID','amz-related-products');
define('AMZRP_PLUGIN_FILE', plugin_basename( __FILE__ ) );
define('AMZRP_PLUGIN_DIR', WP_PLUGIN_DIR."/".dirname(plugin_basename(__FILE__)));
define('AMZRP_PLUGIN_URL', WP_PLUGIN_URL."/".dirname(plugin_basename(__FILE__)));
define('AMZRP_VERSION','0.1');

require(AMZRP_PLUGIN_DIR."/includes/class.amzrp_widget.php");
require(AMZRP_PLUGIN_DIR."/includes/class.amzrp_units.php");
require(AMZRP_PLUGIN_DIR."/includes/class.amzp_api_request.php");
require(AMZRP_PLUGIN_DIR."/includes/amzrp_regions.php");
require(AMZRP_PLUGIN_DIR."/functions.php");

?>
