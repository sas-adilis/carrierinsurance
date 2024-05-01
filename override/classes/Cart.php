<?php
/**
 * 2024 Adilis.
 * Offer insurance on your deliveries.
 *
 * @author Adilis <contact@adilis.fr>
 * @copyright 2024 SAS Adilis
 * @license http://www.adilis.fr
 */
if (!defined('_PS_VERSION_')) {
    exit;
}
class Cart extends CartCore
{
    public function newCalculator($products, $cartRules, $id_carrier, $computePrecision = null, bool $keepOrderPrices = false)
    {
        $calculator = parent::newCalculator($products, $cartRules, $id_carrier, $computePrecision, $keepOrderPrices);
        if (Module::isEnabled('carrierinsurance')) {
            require_once _PS_MODULE_DIR_ . 'carrierinsurance/classes/CartCalculatorWithInsurance.php';

            return new CartCalculatorWithInsurance($calculator);
        }

        return $calculator;
    }
}
