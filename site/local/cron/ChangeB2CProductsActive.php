<?php
/**
 * Created by PhpStorm.
 * User: Zakhar
 * Date: 29.04.2017
 * Time: 17:35
 */

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use \Epages\Helper\ChangeB2CProductsActive;
$ob = new ChangeB2CProductsActive(CATALOG_IBLOCK_ID, OFFERS_CATALOG_IBLOCK_ID);