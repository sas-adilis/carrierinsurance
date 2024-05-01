/**
 * 2024 Adilis.
 * Offer insurance on your deliveries.
 *
 * @author Adilis <contact@adilis.fr>
 * @copyright 2024 SAS Adilis
 * @license http://www.adilis.fr
 */

$(document).ready(function() {
    const $insuranceWrapper = $('#insurance_wrapper');
    const $insuranceInput = $('input[name="insurance"]');
    $('.js-shipping-form').append($insuranceWrapper);
    $insuranceWrapper.show();
    $insuranceInput.on('change', function() {
        $.ajax({
            url: $insuranceWrapper.attr('data-ajax-url'),
            data : {
                ajax: 1,
                action : 'updateInsurance',
                value : parseInt($insuranceInput.filter(':checked').val()),
                id_cart: $('#cart_summary_cart_id').val()
            },
            dataType: 'json'
        }).done(function( data ) {
            $('#delivery-option-select').trigger('change');
            $('#js-insurance-amount').html('+' + data.amount);
        });
    });

    $insuranceInput.trigger('change');
});