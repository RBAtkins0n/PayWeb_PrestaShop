{*
 * Copyright (c) 2018 PayGate (Pty) Ltd
 *
 * Author: App Inlet (Pty) Ltd
 * 
 * Released under the GNU General Public License
 *}

{if $status == 'ok'}
    <p>{l s='Your order on' mod='paygate'} <span class="bold">{$shop_name}</span> {l s='is complete.' mod='paygate'}
        <br /><br /><span class="bold">{l s='Your order will be shipped as soon as possible.' mod='paygate'}</span>
        <br /><br />{l s='For any questions or for further information, please contact our' mod='paygate'} <a href="{$link->getPageLink('contact', true)}">{l s='customer support' mod='paygate'}</a>.
    </p>
{else}
    {if $status == 'pending'}
        <p>{l s='Your order on' mod='paygate'} <span class="bold">{$shop_name}</span> {l s='is pending.' mod='paygate'}
            <br /><br /><span class="bold">{l s='Your order will be shipped as soon as we receive your EFT.' mod='paygate'}</span>
            <br /><br />{l s='For any questions or for further information, please contact our' mod='paygate'} <a href="{$link->getPageLink('contact', true)}">{l s='customer support' mod='paygate'}</a>.
        </p>
    {else}
        <p class="warning">
            {l s='We noticed a problem with your order. If you think this is an error, you can contact our' mod='paygate'} 
            <a href="{$link->getPageLink('contact', true)}">{l s='customer support' mod='paygate'}</a>.
        </p>
    {/if}
{/if}
