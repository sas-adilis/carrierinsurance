<div id="insurance_wrapper" class="form-group row" style="display: none;">
    <div class="col-md-6 col-xl-3 text-md-right col-form-label">
        <span class="float-right">
          Assurance
        </span>
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
    <div class="col" id="js-insurance-amount">

    </div>

</div>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelector('.js-shipping-form').appendChild(document.getElementById('insurance_wrapper'));
        document.getElementById('insurance_wrapper').style.removeProperty('display');

        $('input[name="insurance"]').on('change', function() {
            $.ajax({
                url: "{Context::getContext()->link->getModuleLink('carrier_insurance', 'ajax')}",
                data : {
                    ajax: 1,
                    action : 'updateInsurance',
                    value : $('input[name="insurance"]:checked').val(),
                    id_cart: $('#cart_summary_cart_id').val()
                },
                dataType: 'json'
            }).done(function( data ) {
                console.log(data);
                $('#delivery-option-select').trigger('change');
                if (data.amount_numeric > 0) {
                    $('#js-insurance-amount').html('(+' + data.amount + ')');
                } else {
                    $('#js-insurance-amount').html('');
                }
            });
        });

        $('input[name="insurance"]:checked').trigger('change');
    });
</script>