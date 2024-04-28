<div id="order-insurance-total-container" class="col-sm text-center">
    <p class="text-muted mb-0">
        <strong>{l s='Insurance' mod='carrierinsurance'}</strong>
    </p>
    <div class="insurance-price">
        <strong id="orderInsuranceTotal">{$amount}</strong>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#order-insurance-total-container').insertAfter('#order-shipping-total-container');
    });
</script>