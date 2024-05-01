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

class CarrierInsurance extends Module
{
    const RANGES_BASE_ORDER = 'order';
    const RANGES_BASE_SHIPPING = 'shipping';
    const RANGE_BEHAVIOR_HIGHEST = 'highest';
    const RANGE_BEHAVIOR_DISABLE = 'disable';
    const CALCULATION_METHOD_AMOUNT = 'amount';
    const CALCULATION_METHOD_PERCENT_ORDER = 'percent_order';
    const CALCULATION_METHOD_PERCENT_SHIPPING = 'percent_shipping';

    private static $hookDisplayPDFInvoiceBeforeTotalCalled = false;

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
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => _PS_VERSION_];

        parent::__construct();
    }

    public function install(): bool
    {
        if (file_exists($this->getLocalPath() . 'sql/install.php')) {
            require_once $this->getLocalPath() . 'sql/install.php';
        }

        Configuration::updateValue('CI_CALCULATION_METHOD', 'percent_order');
        Configuration::updateValue('CI_ID_TAX_RULES_GROUP', 0);
        Configuration::updateValue('CI_ID_CMS', 0);
        Configuration::updateValue('CI_RANGE_BEHAVIOR', self::RANGE_BEHAVIOR_DISABLE);
        Configuration::updateValue('CI_RANGES_BASE', self::RANGES_BASE_ORDER);

        return
            parent::install()
            && $this->registerHook('displayAfterCarrier')
            && $this->registerHook('actionCarrierProcess')
            && $this->registerHook('displayAdminOrderSide')
            && $this->registerHook('displayBackofficeHeader')
            && $this->registerHook('displayHeader')
            && $this->registerHook('actionPresentCart')
            && $this->registerHook('actionPresentOrder')
            && $this->registerHook('displayPDFInvoiceBeforeTotal')
            && $this->registerHook('actionPDFInvoiceTaxBreakdown');
    }

    public function getContent(): string
    {
        if ($this->testHookDisplayPDFInvoiceBeforeTotal() === false) {
            $this->context->controller->errors[] = $this->l('The hook displayPDFInvoiceBeforeTotal is not installed. Please read the documentation.');
        }

        if (Tools::isSubmit('submit' . $this->name . 'Module')) {
            $amount_key = Tools::getValue('CI_CALCULATION_METHOD') == 'amount' ? 'amount' : 'percent';
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
                Configuration::updateValue('CI_CALCULATION_METHOD', Tools::getValue('CI_CALCULATION_METHOD'));
                Configuration::updateValue('CI_RANGES_BASE', Tools::getValue('CI_RANGES_BASE'));
                Configuration::updateValue('CI_RANGE_BEHAVIOR', Tools::getValue('CI_RANGE_BEHAVIOR'));
                Configuration::updateValue('CI_RANGES', json_encode($posted_ranges));
                Configuration::updateValue('CI_ID_TAX_RULES_GROUP', (int) Tools::getValue('CI_ID_TAX_RULES_GROUP'));
                Configuration::updateValue('CI_ID_CMS', (int) Tools::getValue('CI_ID_CMS'));

                $redirect_after = $this->context->link->getAdminLink('AdminModules');
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
                'CI_CALCULATION_METHOD' => Tools::getValue('CI_CALCULATION_METHOD', Configuration::get('CI_CALCULATION_METHOD')),
                'CI_ID_CMS' => (int) Tools::getValue('CI_ID_CMS', Configuration::get('CI_ID_CMS')),
                'CI_ID_TAX_RULES_GROUP' => (int) Tools::getValue('CI_ID_TAX_RULES_GROUP', Configuration::get('CI_ID_TAX_RULES_GROUP')),
                'CI_RANGES_BASE' => Tools::getValue('CI_RANGES_BASE', Configuration::get('CI_RANGES_BASE')),
                'CI_RANGE_BEHAVIOR' => Tools::getValue('CI_RANGE_BEHAVIOR', Configuration::get('CI_RANGE_BEHAVIOR')),
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
                            'type' => 'radio',
                            'label' => $this->l('Ranges based on'),
                            'name' => 'CI_RANGES_BASE',
                            'class' => 't',
                            'br' => true,
                            'values' => [
                                [
                                    'id' => 'CI_RANGES_BASE_' . self::RANGES_BASE_ORDER,
                                    'value' => self::RANGES_BASE_ORDER,
                                    'label' => $this->l('On the amount of the order, excluding tax and shipping'),
                                ],
                                [
                                    'id' => 'CI_RANGES_BASE_' . self::RANGES_BASE_SHIPPING,
                                    'value' => self::RANGES_BASE_SHIPPING,
                                    'label' => $this->l('On shipping costs tax excluded'),
                                ],
                            ],
                        ],
                        [
                            'type' => 'select',
                            'name' => 'CI_ID_TAX_RULES_GROUP',
                            'id' => 'CI_ID_TAX_RULES_GROUP',
                            'label' => $this->l('Tax rule'),
                            'options' => [
                                'default' => ['value' => null, 'label' => $this->l('No tax')],
                                'query' => TaxRulesGroup::getTaxRulesGroups(),
                                'id' => 'id_tax_rules_group',
                                'name' => 'name',
                            ],
                        ],
                        [
                            'type' => 'select',
                            'name' => 'CI_CALCULATION_METHOD',
                            'id' => 'CI_CALCULATION_METHOD',
                            'label' => $this->l('How the amount of insurance is calculated'),
                            'options' => [
                                'query' => [
                                    ['id' => self::CALCULATION_METHOD_AMOUNT, 'name' => $this->l('Fixed amount')],
                                    ['id' => self::CALCULATION_METHOD_PERCENT_ORDER, 'name' => $this->l('Percentage of the order')],
                                    ['id' => self::CALCULATION_METHOD_PERCENT_SHIPPING, 'name' => $this->l('Percentage of the shipping amount')],
                                ],
                                'id' => 'id',
                                'name' => 'name',
                            ],
                        ],

                        [
                            'type' => 'select',
                            'label' => $this->l('Out-of-range behavior'),
                            'name' => 'CI_RANGE_BEHAVIOR',
                            'options' => [
                                'query' => [
                                    [
                                        'id' => self::RANGE_BEHAVIOR_HIGHEST,
                                        'name' => $this->l('Apply the cost of the highest defined range'),
                                    ],
                                    [
                                        'id' => self::RANGE_BEHAVIOR_DISABLE,
                                        'name' => $this->l('Disable Insurance'),
                                    ],
                                ],
                                'id' => 'id',
                                'name' => 'name',
                            ],
                            'hint' => $this->l('Out-of-range behavior occurs when no defined range matches the customer\'s cart.'),
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
        $amount_key = Tools::getValue('CI_CALCULATION_METHOD') == 'amount' ? 'amount' : 'percent';
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
                SELECT amount_tax_excl, amount_tax_incl, amount_tax, tax_rate
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

        if (Configuration::get('CI_RANGES_BASE')) {
            $base_range_amount = (float) $cart->getOrderTotal(false, CartCore::BOTH_WITHOUT_SHIPPING);
        } else {
            $base_range_amount = (float) $cart->getOrderTotal(false, CartCore::ONLY_SHIPPING);
        }
        $ranges = $this->getSavedRanges();
        foreach ($ranges as $range) {
            if (
                (
                    $base_range_amount >= $range['from']
                    && $base_range_amount < $range['to']
                ) || (
                    Configuration::get('CI_RANGE_BEHAVIOR') == self::RANGE_BEHAVIOR_HIGHEST
                    && $range === end($ranges)
                )
            ) {
                switch (Configuration::get('CI_CALCULATION_METHOD')) {
                    case 'percent_order':
                        $cart_amount = $cart->getOrderTotal(false, CartCore::BOTH_WITHOUT_SHIPPING);
                        $amount_insurance_tax_excl = Tools::ps_round($cart_amount * $range['percent'] / 100, 2);
                        break;
                    case 'percent_shipping':
                        $shipping_amount = $cart->getOrderTotal(false, CartCore::ONLY_SHIPPING);
                        $amount_insurance_tax_excl = Tools::ps_round($shipping_amount * $range['percent'] / 100, 2);
                        break;
                    case 'amount':
                        $amount_insurance_tax_excl = Tools::ps_round($range['amount'], 2);
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

        return [
            'amount_tax_excl' => $amount_insurance_tax_excl,
            'amount_tax_incl' => $amount_insurance_tax_incl,
            'amount_tax' => $amount_insurance_tax_incl - $amount_insurance_tax_excl,
            'tax_rate' => isset($tax_calculator) ? $tax_calculator->getTotalRate() : 0,
        ];
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
                    'amount_tax' => (float) $amounts['amount_tax'],
                    'tax_rate' => (float) $amounts['tax_rate'],
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

            return $this->display(__FILE__, 'views/templates/hook/admin-order-view.tpl');
        }

        return '';
    }

    public function hookDisplayBackOfficeHeader($params)
    {
        if (
            Tools::getValue('controller') == 'AdminOrders'
            && Tools::getValue('action') == 'addorder'
        ) {
            $this->context->controller->addJS($this->_path . 'views/js/admin-order-add.js');
            $this->context->smarty->assign([
                'have_insurance' => Validate::isLoadedObject($this->context->cart) && self::cartHaveInsurance($this->context->cart->id),
            ]);

            return $this->display(__FILE__, 'views/templates/hook/admin-order-add.tpl');
        }

        if (
            Tools::getValue('controller') == 'AdminOrders'
            && Tools::getValue('action') == 'vieworder'
        ) {
            $this->context->controller->addJS($this->_path . 'views/js/admin-order-view.js');
        }

        if (
            Tools::getValue('controller') == 'AdminModules'
            && Tools::getValue('configure') == $this->name
        ) {
            $this->context->controller->addJS($this->_path . 'views/js/admin-configure.js');
            $this->context->controller->addCSS($this->_path . 'views/css/admin-configure.css');
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
            $params['presentedOrder']->getSubtotals()->appendArray([
                'insurance' => [
                    'type' => 'insurance',
                    'label' => $this->l('Insurance'),
                    'amount' => $amount_numeric,
                    'value' => $amount_numeric > 0 ? $amount : $this->l('Free'),
                ],
            ]);
        }
    }

    /**
     * @throws PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException
     */
    public function hookDisplayPDFInvoiceBeforeTotal($params)
    {
        self::$hookDisplayPDFInvoiceBeforeTotalCalled = true;
        $order = $params['order'];
        if (self::cartHaveInsurance($order->id_cart)) {
            $amount_numeric = $this->getCartAmountTaxExclSaved($order->id_cart);
            $amount = $this->context->getCurrentLocale()->formatPrice(
                $amount_numeric,
                (new Currency($order->id_currency))->iso_code
            );

            $this->context->smarty->assign([
                'insurance_amount' => $amount_numeric > 0 ? $amount : $this->l('Free'),
            ]);

            return $this->display(__FILE__, 'views/templates/hook/pdf-total-tab-invoice.tpl');
        }

        return '';
    }

    private function isOrderViewTaxIncluded(Order $order): bool
    {
        $customer = new Customer($order->id_customer);
        $taxCalculationMethod = Group::getPriceDisplayMethod($customer->id_default_group);

        return $taxCalculationMethod == PS_TAX_INC;
    }

    private function isCartViewTaxIncluded(Cart $cart): bool
    {
        $customer = new Customer($cart->id_customer);
        $taxCalculationMethod = Group::getPriceDisplayMethod($customer->id_default_group);

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

    public function hookDisplayHeader()
    {
        if (self::isCheckoutPage()) {
            $this->context->controller->addJS($this->_path . 'views/js/checkout.js');
        }
    }

    public function hookActionPDFInvoiceTaxBreakdown($params)
    {
        $order = $params['order'];
        if (Validate::isLoadedObject($order) && self::cartHaveInsurance($order->id_cart)) {
            $breakdowns = $params['breakdowns'];
            $amounts = $this->getCartAmountsSaved($order->id_cart);
            if ($amounts['amount_tax'] > 0) {
                if (isset($breakdowns['shipping_tax'][$amounts['tax_rate']])) {
                    $breakdowns['shipping_tax'][$amounts['tax_rate']]['total_price_tax_excl'] += $amounts['amount_tax_excl'];
                    $breakdowns['shipping_tax'][$amounts['tax_rate']]['total_amount'] += $amounts['amount_tax'];
                    $breakdowns['shipping_tax'][$amounts['tax_rate']]['total_tax_excl'] += $amounts['amount_tax_excl'];
                } else {
                    if (!isset($breakdowns['shipping_tax'])) {
                        $breakdowns['shipping_tax'] = [];
                    }
                    $breakdowns['shipping_tax'][$amounts['tax_rate']] = [
                        'id_tax' => 0,
                        'rate' => $amounts['tax_rate'],
                        'total_price_tax_excl' => (float) $amounts['amount_tax_excl'],
                        'total_amount' => (float) $amounts['amount_tax'],
                        'total_tax_excl' => (float) $amounts['amount_tax_excl'],
                    ];
                }
                $params['breakdowns'] = $breakdowns;
            }
        }
    }

    /**
     * @throws PrestaShopException
     * @throws PrestaShopDatabaseException
     */
    private function testHookDisplayPDFInvoiceBeforeTotal(): bool
    {
        $random_invoice_id = (int) Db::getInstance()->getValue('SELECT id_order_invoice FROM ' . _DB_PREFIX_ . 'order_invoice ORDER BY RAND()');
        if (!$random_invoice_id) {
            return true;
        }

        $order_invoice = new OrderInvoice((int) $random_invoice_id);
        if (!Validate::isLoadedObject($order_invoice)) {
            return true;
        }

        $pdf = new PDF($order_invoice, PDFCore::TEMPLATE_INVOICE, Context::getContext()->smarty);
        $pdf->render('S');

        return self::$hookDisplayPDFInvoiceBeforeTotalCalled;
    }
}
