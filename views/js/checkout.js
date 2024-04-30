$(document).ready(function(){
    const $deliveryForm = $(prestashop.selectors.checkout.deliveryFormSelector);
    if ($deliveryForm.length) {
        if (!$deliveryForm.find('#carrier_insurance_wrapper').length) {
            const $insuranceWrapper = $('#carrier_insurance_wrapper');
            $deliveryForm.find('.delivery-options').after($insuranceWrapper);
        }
    }
});