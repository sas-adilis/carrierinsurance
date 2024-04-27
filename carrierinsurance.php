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

class CarrierInsurance extends Module
{

    function __construct()
    {
        $this->name = 'carrierinsurance';
        $this->author = 'Adilis';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->tab = 'administration';
        $this->version = '1.1.3';
        $this->displayName = $this->l('Carrier insurance');
        $this->description = $this->l('Offer insurance on your deliveries');

        parent::__construct();
    }

    public function install(): bool
    {
        if (file_exists($this->getLocalPath() . 'sql/install.php')) {
            require_once($this->getLocalPath() . 'sql/install.php');
        }

        return
            parent::install() &&
            $this->registerHook('displayAfterCarrier') &&
            $this->registerHook('actionCarrierProcess') &&
            $this->registerHook('displayAdminOrderSide') &&
            $this->registerHook('displayBackofficeHeader') &&
            $this->registerHook('actionPresentCart') &&
            $this->registerHook('actionPresentOrder') &&
            $this->registerHook('displayPDFInvoiceBeforeTotal');
    }

    private function cartHaveInsurance($id_cart): bool
    {
        return (int)Db::getInstance()->getValue('
            SELECT id_cart
            FROM '._DB_PREFIX_.'cart_insurance
            WHERE id_cart='.(int)$id_cart
        ) > 0;
    }

    /**
     * @return array{amount_tax_excl: float, amount_tax_incl: float}
     */
    public function getCartAmountsSaved($id_cart): array {
        $cache_key = 'CarrierInsurance::getCartAmountsSaved_' . $id_cart;
        if (!Cache::isStored($cache_key)) {
            $amounts = Db::getInstance()->getRow('
                SELECT amount_tax_excl, amount_tax_incl
                FROM '._DB_PREFIX_.'cart_insurance
                WHERE id_cart='.(int)$id_cart
            );
            Cache::store($cache_key, $amounts);
        }
        return Cache::retrieve($cache_key);
    }

    public function getCartAmountTaxInclSaved($id_cart): float
    {
        return (float)$this->getCartAmountsSaved($id_cart)['amount_tax_incl'];
    }

    public function getCartAmountTaxExclSaved($id_cart): float
    {
        return (float)$this->getCartAmountsSaved($id_cart)['amount_tax_excl'];
    }

    private function getCartAmountSaved($id_cart, $taxIncluded = true): float
    {
        return $taxIncluded ? self::getCartAmountTaxInclSaved($id_cart) : self::getCartAmountTaxExclSaved($id_cart);
    }

    private static function isCheckoutPage(): bool
    {
        return (Context::getContext()->controller->php_self ?? '') == 'order';
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    private function processCartCarrier($cart): bool
    {
        $have_selected_insurance = (int)Tools::getValue('carrier_insurance');
        if ($have_selected_insurance) {
            $amount = $this->getAmountForCart($cart->id);
            Db::getInstance()->insert('cart_insurance', [
                'id_cart' => (int)$cart->id,
                'amount_tax_excl' => $amount,
                'amount_tax_incl' => $amount,
            ], false, false, Db::REPLACE);
        } else {
            Db::getInstance()->delete('cart_insurance', 'id_cart='.(int)$cart->id, 1);
        }
        return true;
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     * @throws \PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException
     */
    public function HookDisplayAdminOrderSide($params) {
        $order = new Order($params['id_order']);
        if (
            Validate::isLoadedObject($order) &&
            self::cartHaveInsurance($order->id_cart)
        ) {
            $amount_numeric = self::getCartAmountSaved($order->id_cart, $this->isOrderViewTaxIncluded($order));
            $amount = $this->context->getCurrentLocale()->formatPrice(
                $amount_numeric,
                (new Currency($order->id_currency))->iso_code
            );
            $this->context->smarty->assign(array(
                'amount' =>  $amount,
                'amount_numeric' => $amount_numeric
            ));
            return $this->display(__FILE__, 'views/templates/hook/admin-order.tpl');
        }
        return '';
    }

    public function hookDisplayBackOfficeHeader($params) {
       /* $this->context->smarty->assign(array(
            'have_insurance' => self::cartHaveInsurance(Validate::isLoadedObject($this->context->cart) ? $this->context->cart->id : 0),
            'amount' => self::getAmountForCart(),
            'amount_display' => Tools::displayPrice(self::getAmountForCart())
        ));
        return $this->display(__FILE__, 'views/templates/hook/admin-order-create.tpl');*/
    }

    /**
     * @throws \PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException
     */
    public function hookActionPresentCart($params) {
        $cart = $params['cart'];
        $params['presentedCart']['subtotals']['insurance'] = null;
        if (self::cartHaveInsurance($cart->id)) {
            $amount_numeric = self::getCartAmountSaved($cart->id, $this->isCartViewTaxIncluded($cart));
            $amount = $this->context->getCurrentLocale()->formatPrice(
                $amount_numeric,
                (new Currency($cart->id_currency))->iso_code
            );
            $params['presentedCart']['subtotals']['insurance'] = [
                "type" => "insurance",
                "label" => $this->l('Insurance'),
                "amount" => $amount_numeric,
                "value" => $amount
            ];
        }
    }

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShopException
     * @throws \PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException
     */
    public function hookActionPresentOrder($params) {
        $orderId = $params['presentedOrder']->getDetails()->getId();
        $cartId = Cart::getCartIdByOrderId($orderId);

        if (self::cartHaveInsurance($cartId)) {
            $order = new Order($orderId);
            $amount_numeric = self::getCartAmountSaved($cartId, $this->isOrderViewTaxIncluded($order));
            $amount = $this->context->getCurrentLocale()->formatPrice(
                $amount_numeric,
                (new Currency($order->id_currency))->iso_code
            );
-            $params['presentedOrder']->getSubtotals()->appendArray([
                'insurance' => [
                    'type' => 'insurance',
                    "label" => $this->l('Insurance'),
                    'amount' => $amount_numeric,
                    'value' => $amount,
                ]
            ]);
        }
    }

    public function hookDisplayPDFInvoiceBeforeTotal($params) {
        $order = $params['order'];
        if (self::cartHaveInsurance($order->id_cart)) {
            $amount = $this->getAmountForCart($order->id_cart);
            $this->context->smarty->assign(array(
                'insurance_amount' => Tools::displayPrice($amount)
            ));
            return $this->display(__FILE__, 'views/templates/hook/pdf-total-tab-invoice.tpl');
        }
    }

    private function isOrderViewTaxIncluded(Order $order): bool
    {
        $customer = new Customer($order->id_customer);
        $taxCalculationMethod = Group::getPriceDisplayMethod((int) $customer->id_default_group);
        return ($taxCalculationMethod == PS_TAX_INC);
    }

    private function isCartViewTaxIncluded(Cart $cart): bool
    {
        $customer = new Customer($cart->id_customer);
        $taxCalculationMethod = Group::getPriceDisplayMethod((int) $customer->id_default_group);
        return ($taxCalculationMethod == PS_TAX_INC);
    }
}