<?php
/**
* TargetPay Example class
* 
* SVE
* 
* 11-01-2009
*/
  
abstract class TargetPay {
    
   /**
   * @var int rtlo partner ID 
   */
    protected $intRtlo = 114821;
    
    
    /**
    * @desc construction class
    * @Var int rtlo partner ID
    */
    
    public function __construct( $intRtlo ) {
        $this->setRtlo ( $intRtlo );
    }
    
   /**
   * Get response for a targetpay request
   * 
   * @param array $aParams
   * @return string
   */
   /*
    protected function getResponse( $aParams, $sRequest = 'https://www.targetpay.com/api/plugandpay?'  ) {
      
        # convert params
        $strParamString = $this->makeParamString( $aParams );

        # get request
        $ch = curl_init($sRequest.$strParamString);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1) ;
		$strResponse = curl_exec($ch);
		curl_close($ch);
       
        if ( $strResponse === false )
            throw new Exception('Could not fetch response');
        
        return $strResponse;
    
	} */


	protected function getResponse($aParams, $url = 'https://www.targetpay.com/api/plugandpay?' ) {
			$params = $this->makeParamString($aParams);
			$ch = curl_init();
			$url = $url.''.$params;
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, FALSE);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE); 
			$strResponse = curl_exec($ch);
			curl_close($ch);

			if ( $strResponse === false )
				throw new Exception('Could not fetch response');

			return $strResponse;
	}
     
   /**
   * Make string from params
   * 
   * @param array $aParams
   * @return string
   */
   
   protected function makeParamString( $aParams ) {
      
        $strString = '';
        foreach ( $aParams as $strKey => $strValue ) 
          $strString .= '&' . urlencode($strKey) . '=' . urlencode($strValue);
        
        # remove first &  
        return substr( $strString ,1 )  ;          
    
    }
    
    /**
    * Get the base request with IP, RTLO, domain,
    * 
    * @return array
    */
    protected function getBaseRequest() {
      
      # return array with base parameters
      $aParams = array();
      $aParams['action'] = 'start';
      $aParams['ip'] = $_SERVER['REMOTE_ADDR'];
      $aParams['domain'] = $this->strDomain ;
      $aParams['rtlo'] = $this->intRtlo ;
        
        return $aParams;
    
    }
    
    /**
    * @desc set domain
    * 
    */
    
    public function setDomain ( $strDomain ) {
        $this->strDomain = $strDomain;   
    }
    
    /**
    * @desc set rtlo partner id
    * @Var int rtlo partner ID
    */
    
    public function setRtlo ( $intRtlo ) {
        $this->intRtlo = $intRtlo;   
    }
    
    /**
    * Return rtlo
    *
    * @return int
    */ 
     
    public function getRtlo () {
        return $this->intRtlo;   
    }     
}

?>
