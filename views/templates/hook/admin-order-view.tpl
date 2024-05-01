{**
 * 2024 Adilis.
 * Offer insurance on your deliveries.
 *
 * @author Adilis <contact@adilis.fr>
 * @copyright 2024 SAS Adilis
 * @license http://www.adilis.fr
 *}

<div id="order-insurance-total-container" class="col-sm text-center">
    <p class="text-muted mb-0">
        <strong>{l s='Insurance' mod='carrierinsurance'}</strong>
    </p>
    <div class="insurance-price">
        <strong id="orderInsuranceTotal">{$amount|escape:'htmlall':'UTF-8'}</strong>
    </div>
</div>