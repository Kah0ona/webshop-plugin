<?php
class TransactionResultModel extends GenericModel {

	protected $options=null;

	function __construct($options) {
		$this->options=$options;
	} 
	/**
	* returns empty string if no pixel is set, otherwise returns this field
	*/
	public function getTrackingPixel(){
		$trackingPixel = $this->getOptions()->getOption('tracking_pixel');
		if($trackingPixel == null || trim($trackingPixel) == ""){ 
			return "";
		}
		
		return $this->replaceValuesInPixel($trackingPixel);
		
	}
	public function replaceValuesInPixel($pixelHtml){
		//get order id from session
		$transactionId 		= $_SESSION['Order__id'];
		$transactionAmount  = $_SESSION['transactionAmount'];
		if($transactionId == null || $transactionId == ""){
			return "<!-- warning, tracking pixel not rendering because Order__id was not available in session. -->";
		}		
		if($transactionAmount == null || $transactionAmount == ""){
			return "<!-- warning, tracking pixel not rendering because transactionAmount was not available in session. -->";
		}
		
		$pixelHtml = str_replace('{transactionID}', $transactionId, $pixelHtml);
		$pixelHtml = str_replace('{transactionAmount}', $transactionAmount, $pixelHtml);				
		
		return $pixelHtml;
	}
}
?>