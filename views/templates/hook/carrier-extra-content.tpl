{**
 * 2024 Adilis.
 * Offer insurance on your deliveries.
 *
 * @author Adilis <contact@adilis.fr>
 * @copyright 2024 SAS Adilis
 * @license http://www.adilis.fr
 *}

<div id="carrier_insurance_wrapper">
    <div class="float-xs-left">
      <span class="custom-checkbox">
        <input id="carrier_insurance" name="carrier_insurance" type="checkbox" value="1" {if $have_insurance}checked="checked"{/if} data-url="{$ajax_url|escape:'htmlall':'UTF-8'}">
        <span><i class="material-icons rtl-no-flip checkbox-checked"></i></span>
      </span>
    </div>
    <div class="condition-label">
        <label class="js-terms" for="carrier_insurance">
            {if $amount_numeric > 0}
                {l s='I would like to take advantage of the delivery guarantee for an amount of %amount% %tax_label%.' sprintf=[
                    '%amount%' => $amount,
                    '%tax_label%' => $tax_label
                ] mod='carrierinsurance'}
            {else}
                {l s='I would like to take advantage of the free delivery guarantee.' mod='carrierinsurance'}
            {/if}
            {if $id_cms > 0}
                {l s='I confirm that i have read the terms and conditions of the %open_tag%optional customer guarantee%close_tag% and agree to them without reservation (%open_tag%click here for more information on the delivery guarantee%close_tag%).' sprintf=[
                    '%open_tag%' => '<a href="'|cat:$cms_url|cat:'" target="_blank" rel="nofollow">',
                    '%close_tag%' => '</a>'
                ] d='Module.Carrierinsurance'}
            {/if}
        </label>
    </div>
</div>
<br/><br/>