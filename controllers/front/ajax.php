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

class CarrierInsuranceAjaxModuleFrontController extends ModuleFrontController
{
    /**
     * @throws PrestaShopDatabaseException
     * @throws PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException
     */
    public function initContent()
    {
        parent::initContent();

        if (Tools::getValue('action') == 'updateInsurance') {
            $have_selected_insurance = (int) Tools::getValue('value');
            $id_cart = (int) Tools::getValue('id_cart');
            $amount_numeric = $amount = 0;
            if ($id_cart && ($cart = new Cart($id_cart)) && Validate::isLoadedObject($cart)) {
                if ($have_selected_insurance) {
                    $amounts = $this->module->calculateCartAmounts($cart);
                    if ($amounts === false) {
                        $amounts = [
                            'amount_tax_excl' => 0,
                            'amount_tax_incl' => 0,
                        ];
                    }
                    $amount_numeric = $amounts['amount_tax_incl'];
                    Db::getInstance()->insert('cart_insurance', [
                        'id_cart' => (int) $id_cart,
                        'amount_tax_excl' => (float) $amounts['amount_tax_excl'],
                        'amount_tax_incl' => (float) $amounts['amount_tax_incl'],
                        'amount_tax' => (float) $amounts['amount_tax'],
                        'tax_rate' => (float) $amounts['tax_rate'],
                    ], false, false, Db::REPLACE);
                } else {
                    Db::getInstance()->delete(
                        'cart_insurance',
                        'id_cart=' . (int) $id_cart,
                        1
                    );
                }

                $amount = $this->context->getCurrentLocale()->formatPrice(
                    $amount_numeric,
                    (new Currency($cart->id_currency))->iso_code
                );
            }
            echo json_encode([
                'result' => 'ok',
                'have_selected_insurance' => $have_selected_insurance,
                'amount_numeric' => $amount_numeric,
                'amount' => $amount_numeric > 0 ? $amount : $this->l('Free'),
            ]);
            exit;
        }
    }
}
