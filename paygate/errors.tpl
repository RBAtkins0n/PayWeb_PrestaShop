{*
 * Copyright (c) 2018 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 * 
 * Released under the GNU General Public License
 *}
<div class="payViaPaygate">
	<form id="payViaPaygate" action="" method="post">
	    <p class="payment_module">
		    <input type="hidden" name="PAY_REQUEST_ID" value="{$data.PAY_REQUEST_ID}" />
		    <input type="hidden" name="CHECKSUM" value="{$data.CHECKSUM}" />
		    <a href="#" onclick="return false;">{'Pay Via'}
		        <img align="right" alt="Pay Via paygate" title="Pay Via Paygate" src="{$base_dir}modules/paygate/paylogo.png">
				<noscript><input type="image" src="{$base_dir}modules/paygate/paylogo.png"></noscript></br>
				<label><font color="red" size="2">{$data.errors}</font></label>
			</a>
		</p>
	</form>
</div>
<div class="clear"></div>