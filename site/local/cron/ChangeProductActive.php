<?php
/**
 * Created by PhpStorm.
 * User: k0mar
 * Date: 4/13/17
 * Time: 17:41
 */
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php');

use \Epages\Helper\ChangeProductActive;
$ob = new ChangeProductActive(CATALOG_IBLOCK_ID, OFFERS_CATALOG_IBLOCK_ID);