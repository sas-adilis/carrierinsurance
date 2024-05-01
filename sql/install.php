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

$sql = [];

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'cart_insurance` (
            `id_cart` int(10) NOT NULL,
            `amount_tax_excl` decimal(20,6) DEFAULT 0.000000,
            `amount_tax_incl` decimal(20,6) DEFAULT 0.000000,
            `amount_tax` decimal(20,6) DEFAULT 0.000000,
            `tax_rate` decimal(10,3) DEFAULT 0.000000,
			PRIMARY KEY  (`id_cart`),
			UNIQUE KEY (`id_cart`)
			) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;';

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}
