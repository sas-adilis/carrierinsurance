{**
 * 2024 Adilis.
 * Offer insurance on your deliveries.
 *
 * @author Adilis <contact@adilis.fr>
 * @copyright 2024 SAS Adilis
 * @license http://www.adilis.fr
 *}

<div id="insurance_wrapper" class="form-group row" style="display: none;" data-ajax-url="{Context::getContext()->link->getModuleLink('carrierinsurance', 'ajax')|escape:'htmlall':'UTF-8'}">
    <div class="col-md-6 col-xl-3 text-md-right col-form-label">
        <span class="float-right">{l s='Insurance (Tax incl.)' mod='carrierinsurance'}</span>
    </div>
    <div class="col-auto" style="min-width: 100px;">
        <span class="ps-switch">
          <input id="insurance_0" class="js-insurance-switch" name="insurance" value="0" type="radio" {if !$have_insurance}checked="checked"{/if}>
          <label for="insurance_0">Non</label>
          <input id="insurance_1" class="js-insurance-switch" name="insurance" value="1" type="radio" {if $have_insurance}checked="checked"{/if}>
          <label for="insurance_1">Oui</label>
          <span class="slide-button"></span>
        </span>
    </div>
    <strong class="col" id="js-insurance-amount"></strong>
</div>