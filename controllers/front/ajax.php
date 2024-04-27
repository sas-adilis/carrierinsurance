<?php

class CarrierInsuranceAjaxModuleFrontController extends ModuleFrontController {

    /**
     * @throws \PrestaShopDatabaseException
     * @throws \PrestaShop\PrestaShop\Core\Localization\Exception\LocalizationException
     */
    public function initContent() {
        parent::initContent();

        if (Tools::getValue('action') == 'updateInsurance') {
            $have_selected_insurance = (int)Tools::getValue('value');
            $id_cart = (int)Tools::getValue('id_cart');
            $amount_numeric = $amount = 0;
            if ($id_cart && ($cart = new Cart($id_cart)) && Validate::isLoadedObject($cart)) {
                if ($have_selected_insurance) {
                    $amount = $this->module->getAmountForCart($id_cart);
                    \Db::getInstance()->insert('cart_insurance', [
                        'id_cart' => (int)$id_cart,
                        'amount_tax_excl' => $amount,
                        'amount_tax_incl' => $amount
                    ], false, false, Db::REPLACE);

                    $amount = $this->context->getCurrentLocale()->formatPrice(
                        $amount,
                        (new Currency($cart->id_currency))->iso_code
                    );
                } else {
                    \Db::getInstance()->delete(
                        'cart_insurance',
                        'id_cart=' . (int)$id_cart,
                        1
                    );
                }
            }

            echo json_encode([
                'result' => 'ok',
                'have_selected_insurance' => $have_selected_insurance,
                'amount_numeric' => $amount_numeric,
                'amount' => $amount
            ]);
            exit;
        }
    }
}