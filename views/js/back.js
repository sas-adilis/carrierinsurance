/**
 * 2024 Adilis.
 * Offer insurance on your deliveries.
 *
 * @author Adilis <contact@adilis.fr>
 * @copyright 2024 SAS Adilis
 * @license http://www.adilis.fr
 */

let $tableRanges;
let $tableRangesModel;
let $typeIsAmount = false;

$(document).ready(function() {
    $('#module_form').removeAttr('novalidate');

    $tableRanges = $('#table-ranges');
    $tableRangesModel = $tableRanges.find('tr#model');

    const $typeSelect = $('#CI_CALCULATION_METHOD');
    $typeSelect.on('change', function() {
        if ($(this).val() === 'amount') {
            $tableRanges.find('.input-group-percent').hide();
            $tableRanges.find('.input-group-amount').show();
            $tableRanges.find('.js-amount').attr('required', true);
            $tableRanges.find('.js-percent').removeAttr('required');
            $typeIsAmount = true;
        } else {
            $tableRanges.find('.input-group-amount').removeAttr('required').hide();
            $tableRanges.find('.input-group-percent').attr('required', true).show();
            $tableRanges.find('.js-percent').attr('required', true);
            $tableRanges.find('.js-amount').removeAttr('required');
            $typeIsAmount = false;
        }
        checkRows();
    });
    $typeSelect.trigger('change');

    $tableRanges.on('click', '.js-btn-add', function() {
        const $currentRow = $(this).closest('tr');
        const {to} = getRowWithRanges($currentRow);
        const $newRow = createRow(to, Math.max(9999, to + 1));
        $newRow.insertAfter($currentRow);
       checkRows();
    });

    $tableRanges.on('click', '.js-btn-delete', function() {
        const $currentRow = $(this).closest('tr');
        $currentRow.remove();
        insertOneLineIfEmpty();
        checkRows();
    });

    insertOneLineIfEmpty();

    $tableRanges.on('change', '.js-from, .js-to', checkRows);

    $tableRanges.find('.js-float-only').on('keyup change', function(e) {
        const $this = $(this);
        const value = $this.val();
        const regex = /[^0-9.]/g;
        let newValue = value.replace(',', '.').replace(regex, '');
        // keep only 2 decimals after dot
        const dotIndex = newValue.indexOf('.');
        if (dotIndex !== -1) {
            const decimals = newValue.substring(dotIndex + 1);
            if (decimals.length > 2) {
                newValue = newValue.substring(0, dotIndex + 3);
            }
        }

        if (value !== newValue) {
            $this.val(newValue);
        }
    });

});

function insertOneLineIfEmpty() {
    if (!$tableRanges.find('tbody tr:not(#model)').length) {
        const $newRow = createRow(0, 99999);
        $newRow.insertAfter($tableRangesModel);
        checkRows();
    }

}

function getRowWithRanges($row) {
    const $from = $row.find('.js-from');
    const from = parseFloat($from.val()) || 0;
    const $to = $row.find('.js-to');
    const to = parseFloat($to.val()) || 0;
    return {
        $from,
        from,
        $to,
        to
    };
}

function createRow(from, to) {
    const $newRow = $tableRangesModel.clone();
    $newRow.removeAttr('id');
    $newRow.find('input').removeAttr('disabled');
    $newRow.find('.js-from').attr('required', true).val(from);
    $newRow.find('.js-to').attr('required', true).val(to);
    return $newRow;
}

function checkRows() {
    const $rows = $tableRanges.find('tbody tr:not(#model)');
    let hasError = false;
    let lastTo = 0;
    $rows.each(function() {
        const $row = $(this);
        const {$from, from, $to, to} = getRowWithRanges($row);

        if (from > to) {
            hasError = true;
            $to.addClass('is-invalid');
        } else {
            $to.removeClass('is-invalid');
        }
        if (from < lastTo) {
            hasError = true;
            $from.addClass('is-invalid');
        } else {
            $from.removeClass('is-invalid');
        }

        const $amountInput = $typeIsAmount ? $row.find('.js-amount') : $row.find('.js-percent');
        const amount = parseFloat($amountInput.val());
        if (isNaN(amount) || amount < 0) {
            hasError = true;
            $amountInput.addClass('is-invalid');
        } else {
            $amountInput.removeClass('is-invalid');
        }

        lastTo = to;
    });
    return !hasError;
}
