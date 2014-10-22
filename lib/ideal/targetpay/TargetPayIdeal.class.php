<?php

/**
* TargetPay ideal example class
* 
*  SVE
*  11-01-2009
*  10-05-2011 MKh ongedefinieerde parameters $this->strLanguage en 
*             $this->strCurrency verwijderd.
*/
  
# requires the init class
require_once ( 'TargetPay.class.php' );
  
class TargetPayIdeal extends TargetPay {
  
  # ofcourse construct
  public function __construct( $intRtlo ) {
    # call parent constructor 
    parent::__construct( $intRtlo );
  }
  
  /**
  * @Desc start payment
  * @Return array ( trxid, idealReturnUrl )
  */
  
    public function startPayment () {
          
          # Build parameter string
          //$aParameters = $this->getBaseRequest();
          $aParameters = array();
          $aParameters['rtlo'] = $this->intRtlo;
          $aParameters['bank'] = $this->idealIssuer;
          $aParameters['description'] = $this->strDescription;
          $aParameters['amount'] =  $this->idealAmount;
          $aParameters['returnurl'] = $this->strReturnUrl;
          $aParameters['reporturl'] = $this->strReportUrl;
          
          # do request
          $strResponse = $this->getResponse( $aParameters, 'https://www.targetpay.com/ideal/start?');
          $aResponse = explode('|', $strResponse );

          # Bad response
          if ( !isset ( $aResponse[1] ) ) {
            throw new Exception( 'Error' . $aResponse[0] );    
          }
          
          $iTrxID = explode ( ' ', $aResponse[0] );
          
          # We return TRXid and url to rederict
          return array ( $iTrxID[1], $aResponse[1] );
  }
  /**
   * Validate the payment now by trxId
   *
   * @return bool
   */
  
   public function validatePayment ( $intTrxId, $iOnce = 1, $iTest = 0 ) {
       
       try {
        
           # Build parameter string
           $aParameters = array();
           $aParameters['rtlo'] = $this->intRtlo;
           $aParameters['trxid'] = $intTrxId;
           $aParameters['once'] = $iOnce;
           $aParameters['test'] = $iTest; 
           
           # do request
           $strResponse = $this->getResponse ( $aParameters , 'https://www.targetpay.com/ideal/check?');
           $aResponse = explode('|', $strResponse );

           # Bad response
           if (  $aResponse[0] != '000000 OK' ) {
                throw new Exception( $aResponse[0] );    
           }
           
           return true;
       
       }    
       catch( Exception $e ) {
      
        # error, could not proceed 
        echo $e->getMessage();
      
      } 
               
   }
   
  
  /**
  * 
  * @Desc set ideal return url
  * 
  */
  
  public function setIdealReturnUrl ( $strReturnUrl ) {
    $this->strReturnUrl = $strReturnUrl;  
     return $this;   
  }
  
  /**
  * 
  * @Desc set ideal return url
  * 
  */
  
  public function setIdealReportUrl ( $strReportUrl ) {
    $this->strReportUrl = $strReportUrl;  
     return $this;   
  }
  
  /**
  * 
  * @Desc set ideal description for transaction
  * 
  */
  
  public function setIdealDescription ( $strDescription ) {
    $this->strDescription = $strDescription;  
     return $this;   
  }
  
  /**
  * @Desc set ideal amount
  * 
  */
  
  public function setIdealAmount ( $intIdealAmount ) {
    
      # Is this a valid ideal amount?
      if ( is_numeric ( $intIdealAmount ) && $intIdealAmount > 0 ) {
        $this->idealAmount = $intIdealAmount;    
      }
      else {
        throw new Exception( 'Invalid ideal amount, please check.' );   
      }
       return $this;
  }
  
  /**
  * @Desc set ideal issuer
  * 
  */
  
  public function setIdealissuer ( $intIdealIssuer ) {

  	  $this->idealIssuer = $intIdealIssuer;    

  	  return $this;
  }
  
  /**
  * Get available issuers, and return array
  *
  * @return array
  */
    
//  public static function getBanks() {
//    return array('0031'=>'ABN AMRO Bank', '0761'=>'ASN Bank', '0081'=>'Fortis Bank', '0091'=>'Friesland Bank', '0721'=>'ING Bank', '0021'=>'Rabobank', '0751'=>'SNS Bank', '0771'=>'SNS Regio Bank');
//  }
  
}
  
  
?>
