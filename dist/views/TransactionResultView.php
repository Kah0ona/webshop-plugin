<?php 
 //possible success url: 
 //http://localhost/~marten/wordpress/success/?status=Success&trxid=TEST080486601601&ec=33&sha1=8ef02ea650c86184e75d491ef62a12dae7b61930
 //	echo sha1("333349995253750745746a71baa15a76e3883e800867760234b18b2e04e");
class TransactionResultView extends GenericView {
	
	protected $status=null;
	protected $trxid=null;
	protected $Order_id=null;
	protected $sha1=null;
	
	public function render($data=null) { 
		$this->status = $_GET['status'];
		if($this->model->getOptions()->getOption('UseSisow')){
			$this->trxid = $_GET['trxid'];
			$this->Order_id = $_GET['ec'];
			$this->sha1 = $_GET['sha1'];
			$msg = '';
			if($this->status == 'Success'){
				$msg = 'Uw bestelling is succesvol verwerkt en betaald.'; 
				$class = 'success'; 
			}
			elseif($this->status == 'OK'){
				$msg = 'Uw bestelling is succesvol verwerkt.';
				$class = 'success';
			}
			elseif($this->status == 'Cancelled'){
				$msg = 'Uw bestelling is geannuleerd.'; 
				$class = 'info';
			}
			elseif($this->status == 'Declined'){
				$msg = 'Uw bestelling is niet geaccepteerd. Neem contact met ons op als u denkt dat dit niet klopt. We hebben uw bestelling al wel vast opgeslagen, maar aangemerkt als onbetaald.';
				$class='error';
			}
			elseif($this->status == 'Failure'){
				$msg = 'Er is  iets misgegaan met de betaling. We hebben de bestelling opgeslagen, maar aangemerkt als onbetaald. Neem contact met ons op als u denkt dat dit niet klopt.'; 
				$class = 'error';
			}
			elseif($this->status == 'Exception'){
				$msg = 'Er is hoogstwaarschijnlijk iets misgegaan met de betaling. Onze creditcard provider heeft niet kunnen verifiëren of de betaling is geaccepteerd. We hebben uw bestelling wel opgeslagen, maar aangemerkt als onbetaald. Neem a.u.b. contact met ons op.'; 
				$class = 'error';
			}
			elseif($this->status == 'Exception'){
				$msg = 'Er is hoogstwaarschijnlijk iets misgegaan met de betaling. Onze creditcard provider heeft niet kunnen verifiëren of de betaling is geaccepteerd. We hebben uw bestelling wel opgeslagen, maar aangemerkt als onbetaald. Neem a.u.b. contact met ons op.'; 
				$class = 'error';
			}
			elseif($this->status == 'Expired'){
				$msg = 'Uw sessie is verlopen. De betaling is niet gelukt. We hebben uw bestelling wel opgeslagen, maar aangemerkt als onbetaald. Neem contact met ons op als u denkt dat dit niet klopt.'; 
				$class = 'error';
			}
			else {
				$class = 'info';
			}

		} elseif ($this->model->getOptions()->getOption('UseTargetpay')){
			//fetch the status of the trxid by hitting an ajax call
			$this->trxid = $_GET['trxid'];
			
			// https://www.targetpay.com/ideal/check
			$res = $this->doTargetpayStatusCheck();
			$msg = $res['msg'];
			$class = $res['class'];
		} else {
			if($this->status == 'OK' || $this->status == 'Success'){
				$msg = 'Uw bestelling is succesvol verwerkt.';
				$class = 'success';
			} else {
				$msg = 'Er is iets misgegaan met de bestelling, dit is een onbekende fout. Neem a.u.b. contact met ons op.'; 
				$class = 'error';
			}

		}
						
		?>
		<div class='alert alert-<?php echo $class; ?> result_message'><?php echo $msg; ?></div>
		<?php

		if($this->status == 'Success' || $this->status == 'OK'){
			echo $this->model->getTrackingPixel(); //if none avail, will echo empty string
			
			
			//reset the shoppingcart:
			$_SESSION['shoppingCart'] = '[]';
			

		}
	}
	/** Targetpay check */
	public function doTargetpayStatusCheck() {
		$url = 'https://www.targetpay.com/ideal/check?trxid='.$this->trxid.
			'&rtlo='.$this->model->getOptions()->getOption('TargetpayLayoutCode');


		$result = $this->model->curl_fetch($url);
		
	
		if($result == '000000 OK'){
			return array('msg'=>'Uw bestelling is succesvol verwerkt en betaald.',
						 'class'=>'success'	);
		} else {
			return array('msg'=>'Er is  iets misgegaan met de betaling. We hebben de bestelling opgeslagen, maar aangemerkt als onbetaald. Neem contact met ons op als u denkt dat dit niet klopt. ',//.$this->model->getOptions()->getOption('TargetpayLayoutCode').' '.$result.' '.$this->trxid,
						 'class'=>'success'	);
		}
   
		  
	}
}
?>
