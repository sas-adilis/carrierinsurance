{**
 * 2024 Adilis.
 * Offer insurance on your deliveries.
 *
 * @author Adilis <contact@adilis.fr>
 * @copyright 2024 SAS Adilis
 * @license http://www.adilis.fr
 *}
{extends file="helpers/form/form.tpl"}
{block name="input"}
    {if $input.type == 'ranges'}
        <table class="table table-condensed" id="table-ranges">
            <thead>
                <tr>
                    <th class="fixed-width-xs"></th>
                    <th class="fixed-width-lg text-center">{l s='From >=' mod='carrierinsurance'}</th>
                    <th class="fixed-width-lg text-center">{l s='< to' mod='carrierinsurance'}</th>
                    <th class="fixed-width-xs"></th>
                    <th class="fixed-width-xl">{l s='Insurance amount' mod='carrierinsurance'}</th>
                </tr>
            </thead>
            <tbody>
                <tr id="model">
                    <td class="text-nowrap">
                        <i class="icon icon-minus-circle icon-2x js-btn-delete text-danger"></i>
                        <i class="icon icon-plus-circle icon-2x js-btn-add"></i>
                    </td>
                    <td>
                        <div class="input-group">
                            <input disabled type="text" name="ranges[from][]" class="form-control js-from js-float-only" value="" />
                            <span class="input-group-addon">{$currency->sign|escape:'htmlall':'UTF-8'}</span>
                        </div>
                    </td>
                    <td>
                        <div class="input-group">
                            <input disabled type="text" name="ranges[to][]" class="form-control js-to js-float-only" value="" />
                            <span class="input-group-addon">{$currency->sign|escape:'htmlall':'UTF-8'}</span>
                        </div>
                    </td>
                    <td>=</td>
                    <td>
                        <div class="input-group input-group-amount">
                            <input disabled type="text" name="ranges[amount][]" class="form-control js-amount js-float-only" value="" />
                            <span class="input-group-addon">{$currency->sign|escape:'htmlall':'UTF-8'} {l s='Tax excl.' mod='carrierinsurance'}</span>
                        </div>
                        <div class="input-group input-group-percent">
                            <input disabled type="text" name="ranges[percent][]" class="form-control js-percent js-float-only" value="" />
                            <span class="input-group-addon">%</span>
                        </div>
                    </td>
                </tr>
                {foreach from=$input.ranges item=range key=key}
                    <tr>
                        <td class="text-nowrap">
                            <i class="icon icon-minus-circle icon-2x js-btn-delete text-danger"></i>
                            <i class="icon icon-plus-circle icon-2x js-btn-add"></i>
                        </td>
                        <td>
                            <div class="input-group">
                                <input type="text" name="ranges[from][]" class="form-control js-from js-float-only" value="{$range.from|floatval}" />
                                <span class="input-group-addon">{$currency->sign|escape:'htmlall':'UTF-8'}</span>
                            </div>
                        </td>
                        <td>
                            <div class="input-group">
                                <input type="text" name="ranges[to][]" class="form-control js-to js-float-only" value="{$range.to|floatval}" />
                                <span class="input-group-addon">{$currency->sign|escape:'htmlall':'UTF-8'}</span>
                            </div>
                        </td>
                        <td>=</td>
                        <td>
                            <div class="input-group input-group-amount">
                                <input type="text" name="ranges[amount][]" class="form-control js-amount js-float-only" value="{$range.amount|floatval}" />
                                <span class="input-group-addon">{$currency->sign|escape:'htmlall':'UTF-8'} {l s='Tax excl.' mod='carrierinsurance'}</span>
                            </div>
                            <div class="input-group input-group-percent">
                                <input type="text" name="ranges[percent][]" class="form-control js-percent js-float-only" value="{$range.percent|floatval}" />
                                <span class="input-group-addon">%</span>
                            </div>
                        </td>
                    </tr>
                {/foreach}
            </tbody>
        </table>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}
