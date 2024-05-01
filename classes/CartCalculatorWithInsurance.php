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

use PrestaShop\PrestaShop\Core\Cart\AmountImmutable;
use PrestaShop\PrestaShop\Core\Cart\Calculator;

class CartCalculatorWithInsurance extends Calculator
{
    /**
     * @throws ReflectionException
     *
     * @noinspection PhpMissingParentConstructorInspection
     */
    public function __construct($calculator)
    {
        $reflectedSourceObject = new ReflectionClass($calculator);
        $reflectedSourceObjectProperties = $reflectedSourceObject->getProperties();

        foreach ($reflectedSourceObjectProperties as $reflectedSourceObjectProperty) {
            $propertyName = $reflectedSourceObjectProperty->getName();
            $reflectedSourceObjectProperty->setAccessible(true);
            $this->$propertyName = $reflectedSourceObjectProperty->getValue($calculator);
        }
    }

    public function getTotal($ignoreProcessedFlag = false)
    {
        $total = parent::getTotal($ignoreProcessedFlag);

        if (Module::isEnabled('carrierinsurance')) {
            $id_cart = $this->getCart()->id;
            $amounts = Db::getInstance()->getRow('SELECT amount_tax_incl, amount_tax_excl FROM ' . _DB_PREFIX_ . 'cart_insurance WHERE id_cart = ' . (int) $id_cart);
            if (!$amounts) {
                return $total;
            }
            $amount = new AmountImmutable($amounts['amount_tax_incl'], $amounts['amount_tax_excl']);

            return $total->add($amount);
        }

        return $total;
    }
}
