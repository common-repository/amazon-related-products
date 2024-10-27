<?php
/**
 * Amazon Related Products
 * Author: Alain Gonzalez
 * Plugin URI: http://web-argument.com/amazon-related-products-wordpress-plugin/
*/

    class AMZRP_API_REQUEST
    {
         
		 public $public_key     = "";
        
         public $private_key    = "";
        
         public $associate_tag  = "";
    
		 public $response_group = "Medium"; 
		 
		 public $region = "";

		function __construct( $public_key, $private_key, $associate_tag, $region ) {
			$this->public_key = $public_key;
			$this->private_key = $private_key;
			$this->associate_tag = $associate_tag;
			$this->region = $region;						
		}
        
   
        /**
         * Check if the xml received from Amazon is valid
         * 
         * @param mixed $response xml response to check
         * @return bool false if the xml is invalid
         * @return mixed the xml response if it is valid
         * @return exception if we could not connect to Amazon
         */
        private function verifyXmlResponse($response)
        {
							
			if ($response === False)
            {
                return false;
            }
            else
            {
				
				$valid_response = $response->Items->Item->ItemAttributes->Title;

                if (isset($valid_response) && !empty($valid_response))
                {
                    return ($response);
                }
                else
                {
                    return false;
                }
            }
        }
        
        
        /**
         * Query Amazon with the issued parameters
         * 
         * @param array $parameters parameters to query around
         * @return simpleXmlObject xml query response
         */
        private function queryAmazon($parameters)
        {
            return $this->signed_request($this->region, $parameters, $this->public_key, $this->private_key, $this->associate_tag);
        }
        
        
        /**
         * Return details of products searched by various types
         * 
         * @param string $search search term
         * @param string $search_index search search_index         
         * @param string $searchType type of search
         * @return mixed simpleXML object
         */
        public function searchProducts($search, $search_index, $searchType = "UPC")
        {
            $allowedTypes = array("UPC", "TITLE", "ARTIST", "KEYWORD");
            $allowedCategories = array("Music", "DVD", "VideoGames");
            
            switch($searchType) 
            {
                case "UPC" :    $parameters = array("Operation"     => "ItemLookup",
                                                    "ItemId"        => $search,
                                                    "SearchIndex"   => $search_index,
                                                    "IdType"        => "UPC",
                                                    "ResponseGroup" => $this->response_group);
                                break;
                
                case "TITLE" :  $parameters = array("Operation"     => "ItemSearch",
                                                    "Title"         => $search,
                                                    "SearchIndex"   => $search_index,
                                                    "ResponseGroup" => $this->response_group);
                                break;
            
            }
            
            $xml_response = $this->signed_request($parameters);
            
            return $this->verifyXmlResponse($xml_response);  
			
        }
        
        
        /**
         * Return details of a product searched by UPC
         * 
         * @param int $upc_code UPC code of the product to search
         * @param string $search_index type of the product
         * @return mixed simpleXML object
         */
        public function getItemByUpc($upc_code, $search_index)
        {
            $parameters = array("Operation"     => "ItemLookup",
                                "ItemId"        => $upc_code,
                                "SearchIndex"   => $search_index,
                                "IdType"        => "UPC",
                                "ResponseGroup" => $this->response_group);
                                
            $xml_response = $this->signed_request($parameters);
            
            return $this->verifyXmlResponse($xml_response);  

        }
        
        
        /**
         * Return details of a product searched by ASIN
         * 
         * @param int $asin_code ASIN code of the product to search
         * @return mixed simpleXML object
         */
        public function getItemByAsin($asin_code)
        {
            $parameters = array("Operation"     => "ItemLookup",
                                "ItemId"        => $asin_code,
                                "ResponseGroup" => $this->response_group);
                                
            $xml_response = $this->signed_request($parameters);
            
            return $this->verifyXmlResponse($xml_response);  

        }
        
        
        /**
         * Return details of a product searched by keyword
         * 
         * @param string $keyword keyword to search
         * @param string $search_index type of the product
         * @return mixed simpleXML object
         */
        public function getItemByKeyword($keyword, $search_index)
        {
            $parameters = array("Operation"   => "ItemSearch",
                                "Keywords"    => $keyword,
                                "SearchIndex" => $search_index,
								"ResponseGroup" => $this->response_group);
            
                   
            $xml_response = $this->signed_request($parameters);
            
            return $this->verifyXmlResponse($xml_response);           
        }
		
		
		public function signed_request($params)
		{
		
			$protocol = stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://';
			$method = "GET";
			$host = "webservices.amazon.".$this->region;
			$uri = "/onca/xml";
			
			$params["Service"]          = "AWSECommerceService";
			$params["AWSAccessKeyId"]   = $this->public_key;
			$params["AssociateTag"]     = $this->associate_tag;
			$params["Timestamp"]        = gmdate("Y-m-d\TH:i:s\Z");
			$params["Version"]          = "2011-08-01";
			
		
			/* The params need to be sorted by the key, as Amazon does this at
			  their end and then generates the hash of the same. If the params
			  are not in order then the generated hash will be different thus
			  failing the authetication process.
			*/
			ksort($params);
			
			$canonicalized_query = array();
		
			foreach ($params as $param=>$value)
			{
				$param = str_replace("%7E", "~", rawurlencode($param));
				$value = str_replace("%7E", "~", rawurlencode($value));
				$canonicalized_query[] = $param."=".$value;
			}
			
			$canonicalized_query = implode("&", $canonicalized_query);
		
			$string_to_sign = $method."\n".$host."\n".$uri."\n".$canonicalized_query;
			
			/* calculate the signature using HMAC with SHA256 and base64-encoding.
			   The 'hash_hmac' function is only available from PHP 5 >= 5.1.2.
			*/
			$signature = base64_encode(hash_hmac("sha256", $string_to_sign, $this->private_key, True));
			
			/* encode the signature for the request */
			$signature = str_replace("%7E", "~", rawurlencode($signature));
			
			/* create request */
			$request = $protocol.$host.$uri."?".$canonicalized_query."&Signature=".$signature;
			
	        // I prefer using CURL 
		    if (function_exists('curl_version')){			
				
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL,$request);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_TIMEOUT, 15);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
			
				$xml_response = curl_exec($ch);
				
				curl_close($ch);
			
			} else {
			
				// If cURL doesn't work for you, then use the 'file_get_contents' function as given below.
				$xml_response = file_get_contents($request);
		
			}
			
			
			if ( $xml_response === False )
			{
				return False;
			}
			else
			{
				// parse XML 
				$parsed_xml = @simplexml_load_string($xml_response);
				return ($parsed_xml === False) ? False : $parsed_xml;
			}

			
		}		
		
		

    } // end of class


?>
