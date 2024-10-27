/**
 * Amazon Related Products
 * Author: Alain Gonzalez
 * Plugin URI: http://web-argument.com/amazon-related-products-wordpress-plugin/
*/

(function($){
	
	var amzrp = {
		       
			   ajaxUrl : "",
			   
			   Ads : {},
	
			   Ad : function(ops){
					this.settings = ops;	
					this.Init();		
			   },
	
			   AdProto : {
		
					  settings : {},
				  
					  Init : function() {				    			    
							  
							  this.GetAdContent();
					  },
					  
					  GetAdContent : function(){			
						  var _this = this;			
						  jQuery.post(
							  amzrp.ajaxUrl,				
							  _this.settings,
							  function(response){
								 _this.RenderAd(response,_this)
							  }
						  );		
					  },
					  
					  RenderAd : function(response,_this){
						  
						  $('#'+_this.settings.div_pref+_this.settings.id).html(response);
						  
						  $(".amzrp-more-info").click(function(){
								$(".amzrp-disclosure").hide();										
								$(this).closest(".amzrp-disclosure-cont").siblings(".amzrp-disclosure").show();										   
								return false;							   
						  });
						  
						  $(".close-disclosure").click(function(){
								$(this).parent(".amzrp-disclosure").hide();							   
								return false;							   
						  });						  
						  
					
					}
					  
			   }	
	
	};	
	
    amzrp.Ad.prototype = amzrp.AdProto;
	
	jQuery(document).ready(function($){
		  if (typeof amzrpAds != "undefined" && amzrpUrl != "undefined"){
			  amzrp.ajaxUrl = amzrpUrl;
			  for (var i = 0; i < amzrpAds.length; i++) {
				  amzrp.Ads[amzrpAds[i]['id']] = new amzrp.Ad(amzrpAds[i]);
			  }
		  }	  

	  
	 });

})(jQuery);