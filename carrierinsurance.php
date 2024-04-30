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
    public function __construct()
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
            require_once $this->getLocalPath() . 'sql/install.php';
        }

        return
            parent::install()
            && $this->registerHook('displayAfterCarrier')
            && $this->registerHook('actionCarrierProcess')
            && $this->registerHook('displayAdminOrderSide')
            && $this->registerHook('displayBackofficeHeader')
            && $this->registerHook('actionPresentCart')
            && $this->registerHook('actionPresentOrder')
            && $this->registerHook('displayPDFInvoiceBeforeTotal');
    }

    public function getContent(): string
    {
        if (Tools::isSubmit('submit' . $this->name . 'Module')) {
            $amount_key = Tools::getValue('CI_TYPE') == 'amount' ? 'amount' : 'percent';
            $posted_ranges = $this->getPostedRanges();
            $lastTo = 0;
            foreach ($posted_ranges as $key => $range) {
                if ($range['from'] > $range['to']) {
                    $this->context->controller->errors[] = sprintf(
                        $this->l('Invalid range #%d: the "from" value must be lower than the "to" value'),
                        $key + 1
                    );
                    break;
                }

                if ($range['from'] < $lastTo) {
                    $this->context->controller->errors[] = sprintf(
                        $this->l('Invalid range #%d: the "from" value must be greater than the previous "to" value'),
                        $key + 1
                    );
                    break;
                }

                if (empty($range[$amount_key])) {
                    $this->context->controller->errors[] = sprintf(
                        $this->l('Invalid range #%d: the "%s" value must be filled'),
                        $key + 1,
                        $amount_key
                    );
                    break;
                }

                $lastTo = $range['to'];
            }

            $this->context->controller->errors = array_unique($this->context->controller->errors);
            if (!count($this->context->controller->errors)) {
                Configuration::updateValue('CI_TYPE', Tools::getValue('CI_TYPE'));
                Configuration::updateValue('CI_RANGES', json_encode($posted_ranges));
                Configuration::updateValue('CI_ID_TAX_RULES_GROUP', (int) Tools::getValue('CI_ID_TAX_RULES_GROUP'));
                Configuration::updateValue('CI_ID_CMS', (int) Tools::getValue('CI_ID_CMS'));
                Configuration::updateValue('CI_FREE_AMOUNT', (float) Tools::getValue('CI_FREE_AMOUNT'));

                $redirect_after = $this->context->link->getAdminLink('AdminModules', true);
                $redirect_after .= '&conf=4&configure=' . $this->name . '&module_name=' . $this->name;
                Tools::redirectAdmin($redirect_after);
            }
        }

        return $this->renderForm();
    }

    private function renderForm(): string
    {
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit' . $this->name . 'Module';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false);
        $helper->currentIndex .= '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = [
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
            'currency' => new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT')),
            'fields_value' => [
                'CI_TYPE' => Tools::getValue('CI_TYPE', Configuration::get('CI_TYPE')),
                'CI_FREE_AMOUNT' => (float) Tools::getValue('CI_FREE_AMOUNT', Configuration::get('CI_FREE_AMOUNT')),
                'CI_ID_CMS' => (int) Tools::getValue('CI_ID_CMS', Configuration::get('CI_ID_CMS')),
                'CI_ID_TAX_RULES_GROUP' => (int) Tools::getValue('CI_ID_TAX_RULES_GROUP', Configuration::get('CI_ID_TAX_RULES_GROUP')),
            ],
        ];

        $ranges = Tools::isSubmit('submit' . $this->name . 'Module') ? $this->getPostedRanges() : $this->getSavedRanges();

        return $helper->generateForm([
            [
                'form' => [
                    'legend' => [
                        'title' => $this->l('Parameters'),
                        'icon' => 'icon-cogs',
                    ],
                    'input' => [
                        [
                            'type' => 'select',
                            'name' => 'CI_TYPE',
                            'id' => 'CI_TYPE',
                            'label' => $this->l('Insurance calculation method'),
                            'required' => true,
                            'options' => [
                                'query' => [
                                    ['id' => 'amount', 'name' => $this->l('Fixed amount')],
                                    ['id' => 'percent_order', 'name' => $this->l('Percentage of the order')],
                                    ['id' => 'percent_shipping', 'name' => $this->l('Percentage of the shipping amount')],
                                ],
                                'id' => 'id',
                                'name' => 'name',
                            ],
                        ],
                        [
                            'type' => 'ranges',
                            'name' => 'CI_RANGES',
                            'class' => 'fixed-width-md',
                            'id' => 'CI_RANGES',
                            'ranges' => $ranges,
                        ],
                        [
                            'type' => 'select',
                            'name' => 'CI_ID_TAX_RULES_GROUP',
                            'id' => 'CI_ID_TAX_RULES_GROUP',
                            'label' => $this->l('Taxes'),
                            'required' => true,
                            'options' => [
                                'default' => ['value' => null, 'label' => $this->l('No tax')],
                                'query' => TaxRulesGroup::getTaxRulesGroups(),
                                'id' => 'id_tax_rules_group',
                                'name' => 'name',
                            ],
                        ],
                        [
                            'type' => 'text',
                            'name' => 'CI_FREE_AMOUNT',
                            'class' => 'fixed-width-md',
                            'id' => 'CI_FREE_AMOUNT',
                            'label' => $this->l('Free insurance from'),
                            'desc' => $this->l('Enter the amount of the order, excluding shipping costs, from which insurance is to be offered. Enter 0 to deactivate the feature.'),
                            'required' => true,
                            'suffix' => Context::getContext()->currency->getSign('right'),
                            'maxlength' => 11,
                        ],
                        [
                            'type' => 'select',
                            'name' => 'CI_ID_CMS',
                            'id' => 'CI_ID_CMS',
                            'label' => $this->l('CMS help page'),
                            'desc' => $this->l('Please select a CMS page that describes how insurance works'),
                            'options' => [
                                'default' => ['value' => null, 'label' => $this->l('I don\'t have a dedicated CMS page')],
                                'query' => CMS::getCMSPages(Context::getContext()->cookie->id_lang, null, true, (int) Configuration::get('PS_SHOP_DEFAULT')),
                                'id' => 'id_cms',
                                'name' => 'meta_title',
                            ],
                        ],
                    ],
                    'submit' => [
                        'title' => $this->l('Save'),
                    ],
                ],
            ],
        ]);
    }

    private function getPostedRanges(): array
    {
        $ranges = Tools::getValue('ranges');
        $posted_ranges = [];
        $amount_key = Tools::getValue('CI_TYPE') == 'amount' ? 'amount' : 'percent';
        foreach ($ranges['from'] as $key => $from) {
            $posted_ranges[] = [
                'from' => (float) $from,
                'to' => (float) $ranges['to'][$key] ?? 0,
                'amount' => $amount_key == 'percent' || empty($ranges['amount'][$key]) ? null : (float) $ranges['amount'][$key],
                'percent' => $amount_key == 'amount' || empty($ranges['percent'][$key]) ? null : (float) $ranges['percent'][$key],
            ];
        }

        return $posted_ranges;
    }

    private function cartHaveInsurance($id_cart): bool
    {
        return (int) Db::getInstance()->getValue('
            SELECT id_cart
            FROM ' . _DB_PREFIX_ . 'cart_insurance
            WHERE id_cart=' . (int) $id_cart
        ) > 0;
    }

    /**
     * @return array{amount_tax_excl: float, amount_tax_incl: float}
     */
    private function getCartAmountsSaved($id_cart): array
    {
        $cache_key = 'CarrierInsurance::getCartAmountsSaved_' . $id_cart;
        if (!Cache::isStored($cache_key)) {
            $amounts = Db::getInstance()->getRow('
                SELECT amount_tax_excl, amount_tax_incl
                FROM ' . _DB_PREFIX_ . 'cart_insurance
                WHERE id_cart=' . (int) $id_cart
            );
            Cache::store($cache_key, $amounts);
        }

        return Cache::retrieve($cache_key);
    }

    private function getCartAmountTaxInclSaved($id_cart): float
    {
        return (float) $this->getCartAmountsSaved($id_cart)['amount_tax_incl'];
    }

    private function getCartAmountTaxExclSaved($id_cart): float
    {
        return (float) $this->getCartAmountsSaved($id_cart)['amount_tax_excl'];
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
     * @return array{amount_tax_excl: float, amount_tax_incl: float}|false
     *
     * @throws Exception
     *                   /**
     */
    public function calculateCartAmounts(Cart $cart)
    {
        if (!Validate::isLoadedObject($cart)) {
            return false;
        }

        $cart_amount = $cart->getOrderTotal(false, CartCore::BOTH_WITHOUT_SHIPPING);
        if (
            (float) Configuration::get('CI_FREE_AMOUNT') > 0
            && $cart_amount >= (float) Configuration::get('CI_FREE_AMOUNT')
        ) {
            return ['amount_tax_excl' => 0, 'amount_tax_incl' => 0];
        }

        $ranges = $this->getSavedRanges();
        foreach ($ranges as $range) {
            if ($cart_amount >= $range['from'] && $cart_amount < $range['to']) {
                switch (Configuration::get('CI_TYPE')) {
                    case 'percent_order':
                        $amount_insurance_tax_excl = Tools::ps_round($cart_amount * $range['percent'] / 100, 2);
                        break;
                    case 'percent_shipping':
                        $shipping_amount = $cart->getOrderTotal(false, CartCore::ONLY_SHIPPING);
                        $amount_insurance_tax_excl = Tools::ps_round($shipping_amount * $range['percent'] / 100, 2);
                        break;
                    case 'amount':
                        $amount_insurance_tax_excl = Tools::ps_round($cart_amount * $range['percent'] / 100, 2);
                }
            }
        }

        if (!isset($amount_insurance_tax_excl)) {
            return false;
        }

        $id_tax_rules_group = (int) Configuration::get('CI_ID_TAX_RULES_GROUP');
        if (
            $id_tax_rules_group > 0
            && ($tax_rules_group = new TaxRulesGroup($id_tax_rules_group))
            && Validate::isLoadedObject($tax_rules_group)
        ) {
            $id_address = (int) $cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')};
            $address = Address::initialize($id_address);
            $tax_manager = TaxManagerFactory::getManager($address, $id_tax_rules_group);
            $tax_calculator = $tax_manager->getTaxCalculator();
            $amount_insurance_tax_incl = $tax_calculator->addTaxes($amount_insurance_tax_excl);
        } else {
            $amount_insurance_tax_incl = $amount_insurance_tax_excl;
        }

        return ['amount_tax_excl' => $amount_insurance_tax_excl, 'amount_tax_incl' => $amount_insurance_tax_incl];
    }

    /**
     * @throws PrestaShopDatabaseException
     */
    public function hookActionCarrierProcess($params)
    {
        if (
            Tools::getValue('action') == 'selectDeliveryOption'
            && Validate::isLoadedObject($params['cart'])
        ) {
            $this->processCartCarrier($params['cart']);
        }
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws Exception
     */
    private function processCartCarrier(Cart $cart)
    {
        Db::getInstance()->delete('cart_insurance', 'id_cart=' . (int) $cart->id, 1);
        $have_selected_insurance = (int) Tools::getValue('carrier_insurance');
        if ($have_selected_insurance) {
            $amounts = $this->calculateCartAmounts($cart);
            if ($amounts !== false) {
                Db::getInstance()->insert('cart_insurance', [
                    'id_cart' => (int) $cart->id,
                    'amount_tax_excl' => (float) $amounts['amount_tax_excl'],
                    'amount_tax_incl' => (float) $amounts['amount_tax_incl'],
                ], false, false, Db::REPLACE);
            }
        }
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     * @throws PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException
     */
    public function HookDisplayAdminOrderSide($params)
    {
        $order = new Order($params['id_order']);
        if (
            Validate::isLoadedObject($order)
            && self::cartHaveInsurance($order->id_cart)
        ) {
            $amount_numeric = self::getCartAmountSaved($order->id_cart, $this->isOrderViewTaxIncluded($order));
            $amount = $this->context->getCurrentLocale()->formatPrice(
                $amount_numeric,
                (new Currency($order->id_currency))->iso_code
            );
            $this->context->smarty->assign([
                'amount_numeric' => $amount_numeric,
                'amount' => $amount_numeric > 0 ? $amount : $this->l('Free'),
            ]);

            return $this->display(__FILE__, 'views/templates/hook/admin-order.tpl');
        }

        return '';
    }

    public function hookDisplayBackOfficeHeader($params)
    {
        if (
            Tools::getValue('controller') == 'AdminOrders'
            && Tools::getValue('action') == 'addorder'
        ) {
            $this->context->smarty->assign([
                'have_insurance' => Validate::isLoadedObject($this->context->cart) && self::cartHaveInsurance($this->context->cart->id),
            ]);

            return $this->display(__FILE__, 'views/templates/hook/admin-order-create.tpl');
        }

        if (
            Tools::getValue('controller') == 'AdminModules'
            && Tools::getValue('configure') == $this->name
        ) {
            $this->context->controller->addJS($this->_path . 'views/js/back.js');
            $this->context->controller->addCSS($this->_path . 'views/css/back.css');
        }
    }

    /**
     * @throws PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException
     * @throws Exception
     */
    public function hookDisplayAfterCarrier($params)
    {
        $insurance_amount = $this->calculateCartAmount($this->context->cart);
        if ($insurance_amount !== false) {
            $taxIncluded = $this->isCartViewTaxIncluded($this->context->cart);
            $this->context->smarty->assign([
                'have_insurance' => self::cartHaveInsurance($this->context->cart->id),
                'amount_numeric' => $insurance_amount,
                'amount' => $this->context->getCurrentLocale()->formatPrice(
                    $insurance_amount,
                    (new Currency($this->context->cart->id_currency))->iso_code
                ),
                'tax_label' => $taxIncluded ? $this->l('Tax incl.') : $this->l('Tax excl.'),
                'ajax_url' => $this->context->link->getModuleLink(
                    $this->name,
                    'ajax',
                    ['ajax' => 1, 'process' => 'updateInsurance']
                ),
                'id_cms' => (int) Configuration::get('CI_ID_CMS'),
                'cms_url' => $this->context->link->getCMSLink(
                    (int) Configuration::get('CI_ID_CMS'),
                    null,
                    $this->context->language->iso_code
                ),
            ]);

            return $this->display(__FILE__, 'views/templates/hook/carrier-extra-content.tpl');
        }

        return '';
    }

    /**
     * @throws PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException
     */
    public function hookActionPresentCart($params)
    {
        $cart = $params['cart'];
        $params['presentedCart']['subtotals']['insurance'] = null;
        if (self::cartHaveInsurance($cart->id)) {
            $amount_numeric = self::getCartAmountSaved($cart->id, $this->isCartViewTaxIncluded($cart));
            $amount = $this->context->getCurrentLocale()->formatPrice(
                $amount_numeric,
                (new Currency($cart->id_currency))->iso_code
            );
            $params['presentedCart']['subtotals']['insurance'] = [
                'type' => 'insurance',
                'label' => $this->l('Insurance'),
                'amount' => $amount_numeric,
                'value' => $amount_numeric > 0 ? $amount : $this->l('Free'),
            ];
        }
    }

    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException
     */
    public function hookActionPresentOrder($params)
    {
        $orderId = $params['presentedOrder']->getDetails()->getId();
        $cartId = Cart::getCartIdByOrderId($orderId);

        if (self::cartHaveInsurance($cartId)) {
            $order = new Order($orderId);
            $amount_numeric = self::getCartAmountSaved($cartId, $this->isOrderViewTaxIncluded($order));
            $amount = $this->context->getCurrentLocale()->formatPrice(
                $amount_numeric,
                (new Currency($order->id_currency))->iso_code
            );
            -$params['presentedOrder']->getSubtotals()->appendArray([
                'insurance' => [
                    'type' => 'insurance',
                    'label' => $this->l('Insurance'),
                    'amount' => $amount_numeric,
                    'value' => $amount_numeric > 0 ? $amount : $this->l('Free'),
                ],
            ]);
        }
    }

    public function hookDisplayPDFInvoiceBeforeTotal($params)
    {
        $order = $params['order'];
        if (self::cartHaveInsurance($order->id_cart)) {
            $amount = $this->getAmountForCart($order->id_cart);
            $this->context->smarty->assign([
                'insurance_amount' => Tools::displayPrice($amount),
            ]);

            return $this->display(__FILE__, 'views/templates/hook/pdf-total-tab-invoice.tpl');
        }
    }

    private function isOrderViewTaxIncluded(Order $order): bool
    {
        $customer = new Customer($order->id_customer);
        $taxCalculationMethod = Group::getPriceDisplayMethod((int) $customer->id_default_group);

        return $taxCalculationMethod == PS_TAX_INC;
    }

    private function isCartViewTaxIncluded(Cart $cart): bool
    {
        $customer = new Customer($cart->id_customer);
        $taxCalculationMethod = Group::getPriceDisplayMethod((int) $customer->id_default_group);

        return $taxCalculationMethod == PS_TAX_INC;
    }

    private function getSavedRanges()
    {
        return json_decode(Configuration::get('CI_RANGES'), true) ?? [];
    }

    /**
     * @throws Exception
     */
    private function calculateCartAmount(Cart $cart)
    {
        $taxIncluded = $this->isCartViewTaxIncluded($cart);
        $amounts = $this->calculateCartAmounts($cart);
        if ($amounts !== false) {
            return $taxIncluded ? $amounts['amount_tax_incl'] : $amounts['amount_tax_excl'];
        }

        return false;
    }
}
