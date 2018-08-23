{*
 * Copyright (c) 2018 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 * 
 * Released under the GNU General Public License
 *}
<div class="payViaPaygate">
	<form id="payViaPaygate" action="https://secure.paygate.co.za/payweb3/process.trans" method="post">
	    <p class="payment_module">
		    <input type="hidden" name="PAY_REQUEST_ID" value="{$data.PAY_REQUEST_ID}" />
		    <input type="hidden" name="CHECKSUM" value="{$data.CHECKSUM}" />
		    <a href="#" onclick="document.getElementById('payViaPaygate').submit();return false;">{'Pay now via Credit or Debit Card using Paygate (Click here)'}
			    <img align="right" alt="Pay Via paygate" title="Pay Via Paygate" src="{$base_dir}modules/paygate/paylogo.png">
		    </a>
			<noscript><input type="image" src="{$base_dir}modules/paygate/paylogo.png"></noscript>
	    </p>
	</form>
</div>
<div class="clear"></div>
