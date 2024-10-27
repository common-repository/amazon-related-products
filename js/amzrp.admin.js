/**
 * Amazon Related Products
 * Author: Alain Gonzalez
 * Plugin URI: http://web-argument.com/amazon-related-products-wordpress-plugin/
*/

(function($){	
	$(document).ready(function($){
		
		$("#region").change(function(){
			$this = $(this);
			amzrpSettings.region = $this.val();
			amzrpSettings.action = "amzrp_select_region"
			amzrpAjaxAdmin( amzrpSettings, function(response){
								if (typeof response != "undefined"){									
									$search_index = $("#search_index");
									$search_index.empty(); 
									$.each(response, function(key, value) {
									  $search_index.append($("<option></option>")
										 .attr("value", value).text(value));
									});
								}
							}
			);
	    });
		
		$("#previews").click(function(){
			var $answer = $("#test_answ").html("");
			var $indicator = $("#test_ind").show();						  
			amzrpSettings.public_key = $("#public_key").val();
			amzrpSettings.private_key = $("#private_key").val();
			amzrpSettings.associate_tag = $("#associate_tag").val();
			amzrpSettings.region = $("#region").val();
			amzrpSettings.unit = $("#unit").val();
			amzrpSettings.search_index = $("#search_index").val();
			amzrpSettings.keywords = $("#keywords").val();			
			amzrpSettings.action = "amzrp_previews";
			
			amzrpAjaxAdmin(amzrpSettings,function(response){
								if (typeof response != "undefined" && response != ""){
                                     $("#previews-cont").html(response);
                                     $answer.html(response);
									 $indicator.hide();
									 
									$(".amzrp-more-info").click(function(){
										$(".amzrp-disclosure").hide();
										
										$(this).closest(".amzrp-disclosure-cont")
											   .siblings(".amzrp-disclosure").show();
												   
											return false;							   
									});
									
									$(".close-disclosure").click(function(){
										$(this).parent(".amzrp-disclosure").hide();							   
										return false;							   
									});								 
									 									 
								} 
							}
			);									  
		
			return false;
		});
		
		
		
		if ($("#amzrp-sc-cont").length > 0) {
		    amzrpShortcode.Init();
		}
		
		$(".amzrp-more-info").click(function(){
			$(this).siblings(".amzrp-disclosure").show();							   
			return false;							   
		});		

  
	 });
	 
	 var amzrpAjaxAdmin = function(settings, callBack){
			$.post(
				settings.ajaxUrl,				
                settings,
				callBack
			);		 
	 };
	 
	 var amzrpShortcode = {
		                   defaultShc : "amz-related-products",
						   
						   shc : "",
						   
		                   Init: function(){
							   var _this = this;
							   $("input,select").change(function(){
								  _this.UpdateSC(); 
							   });
							  $("#amzrp-add-sc").click(function(){
								  _this.UpdateSC();
								  _this.InsertSC();
								  return false; 
							   });							   
							  $("#amzrp-reset-sc").click(function(){
								  _this.ResetShc();
								  return false; 
							   });							   
						   },
						   
						   UpdateSC: function(){
							   var shc = "["+this.defaultShc;
							   $("input,select").not("input[type='button'], input[name*='region']").each(function(index){
								   var $this = $(this);
								   if ($this.val() != ""){
									  shc += " "+$this.attr("id")+"='"+$this.val()+"'";
								   }
							   });
							    shc +="]";
								this.shc = shc;
								$("#amzrp-sc").val(shc);
						   }, 
						   
						   InsertSC: function(){							   
								var win = window.dialogArguments || opener || parent || top;
								win.send_to_editor(this.shc);							   
						   },
						   
						   ResetShc: function(){
							   var shc = "["+this.defaultShc+"]";
							   this.shc = shc;
							   $("#amzrp-sc").val(shc);
						   }
		 
	 };

})(jQuery);