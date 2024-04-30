<div id="insurance_wrapper" class="form-group row" style="display: none;">
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
<script type="text/javascript">
    $(document).ready(function() {
        const $insuranceWrapper = $('#insurance_wrapper');
        const $insuranceInput = $('input[name="insurance"]');
        $('.js-shipping-form').append($insuranceWrapper);
        $insuranceWrapper.show();
        $insuranceInput.on('change', function() {
            $.ajax({
                url: "{Context::getContext()->link->getModuleLink('carrierinsurance', 'ajax')}",
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
</script>