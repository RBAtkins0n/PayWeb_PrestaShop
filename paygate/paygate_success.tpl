{*
 * Copyright (c) 2018 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 * 
 * Released under the GNU General Public License
 *}
{if isset($sent)}
	<h2>{l s='Your order on' mod='paygate'} {$shop_name} {l s={$status} mod='paygate'}</h2>
	<p>{$sent}</p>
	<p>{l s='For any questions or for further information, please contact our' mod='paygate'} <a href="{$link->getPageLink('contact-form.php', true)}">{l s='customer support' mod='paygate'}</a>.</p>
{else}
	<div class="alert alert-danger">
		{if empty($status) || $status == 'has been declined.'}
			{l s='Something went wrong, please try again' mod='paygate'}
		{elseif $status == 'has been cancelled.'}
			{l s='Transaction cancelled' mod='paygate'}
		{/if}
	</div>
	<p>Please <a href="{$link->getPageLink('order')}">{l s='click here' mod='paygate'}</a> to try again.</p>
{/if}