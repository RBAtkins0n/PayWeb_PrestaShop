{*
 * Copyright (c) 2018 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 * 
 * Released under the GNU General Public License
 *}
<div class="alert alert-danger">
{if empty($status) || $status == 2}
	{l s='Transaction declined' mod='paygate'}
{else if $status == 4}
	{l s='Transaction cancelled' mod='paygate'}
{/if}
</div>
<p>Please <strong><a href="index.php?controller=order&step=3">{l s='click here' mod='paygate'}</a></strong> to try again.</p>