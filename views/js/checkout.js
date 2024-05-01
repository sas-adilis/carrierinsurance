/**
 * 2024 Adilis.
 * Offer insurance on your deliveries.
 *
 * @author Adilis <contact@adilis.fr>
 * @copyright 2024 SAS Adilis
 * @license http://www.adilis.fr
 */

$(document).ready(function(){
    const $deliveryForm = $(prestashop.selectors.checkout.deliveryFormSelector);
    if ($deliveryForm.length) {
        if (!$deliveryForm.find('#carrier_insurance_wrapper').length) {
            const $insuranceWrapper = $('#carrier_insurance_wrapper');
            $deliveryForm.find('.delivery-options').after($insuranceWrapper);
        }
    }
});