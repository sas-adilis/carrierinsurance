<?php
/**
 * 2024 Adilis.
 * Offer insurance on your deliveries
 *
 * @author Adilis <contact@adilis.fr>
 * @copyright 2024 SAS Adilis
 * @license http://www.adilis.fr
 */
if (!defined('_PS_VERSION_')) {
    exit;
}

class HTMLTemplateInvoice extends HTMLTemplateInvoiceCore
{
    /**
     * @throws PrestaShopException
     */
    protected function getTaxBreakdown()
    {
        $breakdowns = parent::getTaxBreakdown();
        Hook::exec('actionPDFInvoiceTaxBreakdown', [
            'order' => $this->order,
            'breakdowns' => &$breakdowns,
        ]);

        return $breakdowns;
    }
}
