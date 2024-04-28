{**
 * 2024 Adilis.
 * Manage returns and exchanges easily and quickly
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
                    <th class="fixed-width-lg text-center">
                        <i class="icon icon-question-circle label-tooltip" data-toggle="tooltip" title="{l s='Order amount (included), excluding shipping costs' mod='carrierinsurance'}"></i>
                        {l s='From >=' mod='carrierinsurance'}
                    </th>
                    <th class="fixed-width-lg text-center">
                        <i class="icon icon-question-circle label-tooltip" data-toggle="tooltip" title="{l s='Order amount (excluded), excluding shipping costs' mod='carrierinsurance'}"></i>
                        {l s='< to' mod='carrierinsurance'}
                    </th>
                    <th class="fixed-width-xs"></th>
                    <th class="fixed-width-lg">{l s='Insurance amount' mod='carrierinsurance'}</th>
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
                            <span class="input-group-addon">{$currency->sign}</span>
                        </div>
                    </td>
                    <td>
                        <div class="input-group">
                            <input disabled type="text" name="ranges[to][]" class="form-control js-to js-float-only" value="" />
                            <span class="input-group-addon">{$currency->sign}</span>
                        </div>
                    </td>
                    <td>=</td>
                    <td>
                        <div class="input-group input-group-amount">
                            <input disabled type="text" name="ranges[amount][]" class="form-control js-amount js-float-only" value="" />
                            <span class="input-group-addon">{$currency->sign}</span>
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
                                <input type="text" name="ranges[from][]" class="form-control js-from js-float-only" value="{$range.from}" />
                                <span class="input-group-addon">{$currency->sign}</span>
                            </div>
                        </td>
                        <td>
                            <div class="input-group">
                                <input type="text" name="ranges[to][]" class="form-control js-to js-float-only" value="{$range.to}" />
                                <span class="input-group-addon">{$currency->sign}</span>
                            </div>
                        </td>
                        <td>=</td>
                        <td>
                            <div class="input-group input-group-amount">
                                <input type="text" name="ranges[amount][]" class="form-control js-amount js-float-only" value="{$range.amount}" />
                                <span class="input-group-addon">{$currency->sign}</span>
                            </div>
                            <div class="input-group input-group-percent">
                                <input type="text" name="ranges[percent][]" class="form-control js-percent js-float-only" value="{$range.percent}" />
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
