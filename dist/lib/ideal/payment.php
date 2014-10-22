<?php
require_once "sisow.cls5.php";
$sisow = new Sisow("2537278813", "f1bcac04ef461e7a84757e6394ba205b1f63936e");
if (isset($_POST["issuerid"])) {
	$sisow->purchaseId = $_POST["purchaseid"];
	$sisow->description = $_POST["description"];
	$sisow->amount = $_POST["amount"];
	$sisow->payment = $_POST["payment"];
	$sisow->issuerId = $_POST["issuerid"];
	$sisow->returnUrl = "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["PHP_SELF"];
	if (($ex = $sisow->TransactionRequest()) < 0) {
		header("Location: payment.php?ex=" . $ex . "&ec=" . $sisow->errorCode . "&em=" . $sisow->errorMessage);
		exit;
	}
	header("Location: " . $sisow->issuerUrl);
}
else if (isset($_GET["trxid"])) {
	$sisow->StatusRequest($_GET["trxid"]);
	// if ($sisow->status == Sisow::statusSuccess) {
	//     echo $sisow->consumerAccount;
	//     echo $sisow->consumerName;
	// }
	header("Location: payment.php?status=" . $sisow->status);
	exit;
}
else {
	// there are 2 methods for filling the available issuers in the select/dropdown
	// below, the REST method DirectoryRequest is used
	$sisow->DirectoryRequest($select, true);
}
?>

<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
 
<html xmlns="http://www.w3.org/1999/xhtml" >
 
<head><title>
	Sisow
</title>

<link rel="stylesheet" href="https://www.sisow.nl/Sisow/css/styleopd.css" type="text/css" />
<link rel="stylesheet" href="https://www.sisow.nl/Sisow/css/style.css" type="text/css" />
<link rel="stylesheet" href="https://www.sisow.nl/Sisow/css/style_table.css" type="text/css" />
<link href="../images/sisow_blauw.ico" rel="shortcut icon" type="image/x-icon" />
<script type="text/javascript">
    function betaal() {
        _form = document.getElementById("body_form");
        _form.submit();
    }
</script>
</head>

<body>

<form name="body_form" method="post" xaction="pay.php" id="body_form">
 
<table cellpadding="0" cellspacing="0" id="body_table" width="980" height="100%" align="center">
 
  <tr>
    <td class="logo" height="200" width="274" background="https://www.sisow.nl/Sisow/images/header/logo.jpg" valign="top" />
    <td class="top_info2" height="200" width="339" background="https://www.sisow.nl/Sisow/images/header/midden.jpg" valign="top">
      <span class="welkom">Welkom</span>
    </td>
    <td class="menu" height="200" width="367" background="https://www.sisow.nl/Sisow/images/header/menu.jpg" valign="top" style="padding-top: 20px; text-align: center; vertical-align: middle;">
      &nbsp;
    </td>
  </tr>
  
  <tr>
    <td colspan="3">
      <h2>Sisow betaling</h2>
      <img src="https://www.sisow.nl/Sisow/images/header/line.jpg" width="980" height="1" /><br />
    </td>
  </tr>
 
  
  <tr>
    <td colspan="3" class="content">
      <br />
      <div id="uplinks">
	
  
      <table cellpadding="0" cellspacing="0" width="525" align="center" class="detail_table">
        <tr>
          <td class="top"><div style="color: #008ed0;">&euro;</div></td>
        </tr>
        <tr>
          <td class="header"><div style="color: #008ed0;">iDEAL betaling</div></td>
        </tr>
        <tr>
          <td class="row" align="left">
          <table cellpadding="0" cellspacing="0" width="93%" align="left" class="detail_row">
            <tr>
              <td>
                <table cellpadding="0" cellspacing="0" width="100%">
                  <tr>
                    <td width="125">Betalingskenmerk</td>
                    <td><input type="text" name="purchaseid" value="Kenmerk" maxlength="16" style="color: #008ed0; width: 200px;" /></td>
 
                    <td rowspan="8" style="background-image: url(../images/ideal/idealklein.gif); background-position: center top; width: 170px;">&nbsp;</td>
 
                  </tr>

                  <tr><td colspan="3">&nbsp;</td></tr>

                  <tr>
                    <td>Omschrijving</td>
                    <td><input type="text" name="description" value="Omschrijving" maxlength="32" style="color: #008ed0; width: 200px;" /></td>
                    <td>&nbsp;</td>
                  </tr>

                  <tr><td colspan="3">&nbsp;</td></tr>
 
                 <tr>
                    <td>Bedrag</td>
                    <td><input type="text" name="amount" value="2.34" maxlength="10" style="color: #008ed0; width: 200px;" /></td>
                    <td>&nbsp;</td>
                  </tr>
 
                  <tr><td colspan="3">&nbsp;</td></tr>
 
                  <tr>
                    <td>Betaalmethode</td>
                    <td>
                      <select name="payment" style="width: 200px; color: #008ed0">
                        <option value="">iDEAL</option>
                        <option value="sofort">DIRECTebanking</option>
                        <option value="mistercash">MisterCash</option>
                        <option value="webshop">WebShop GiftCard</option>
                        <option value="podium">Podium Cadeaukaart</option>
                      </select>
                    </td>
                    <td>&nbsp;</td>
                  </tr>
 
                  <tr><td colspan="3">&nbsp;</td></tr>
 
                  <tr>
                    <td>Bank</td>
                    <td><?php echo $select ?></td>
					<!-- below the other method for filling the available issuers
					  <select name="issuerid">
						<script type="text/javascript" src="https://www.sisow.nl/Sisow/iDeal/issuers.js"></script>
					  </select>
					-->
                    <td>&nbsp;</td>
                  </tr>
 
                  <tr><td colspan="3">&nbsp;</td></tr>
                </table>
              </td>
            </tr>
          </table>
          </td>
        </tr>
        <tr>
          <td class="footer" valign="top">
            <table cellpadding="0" cellspacing="0" style="width: 500px; font-family: Verdana; font-size: 10px;">
              <tr style="height: 30px;">
                <td style="text-align: right; xwidth: 100px;">
		  <input type="button" onclick="this.disabled=true;document.body_form.submit()" value="Ga verder" title="Betaal" />
                  <!--<a href="javascript:this.href='';betaal();" id="aBetaal" style="color: #008ed0; text-decoration: none;">Betaal</a>
                  <img src="../images/table/pijltje_b.jpg" alt="" />-->
                </td>
              </tr>
            </table>
          </td>
        </tr>
      </table>
 
</div>
    </td>
  </tr>
 
 
  <tr>
    <td class="bg_bottom2" height="19" colspan="3">&nbsp;</td>
  </tr>
  <tr>
    <td class="bottom_left" height="25" colspan="2" align="left">&nbsp;</td>
    <td class="bottom_right" height="25" align="right">&copy; Copyright - sisow</td>
  </tr>
 
</table>

</form>
 
</body>
 
</html>
