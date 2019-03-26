<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

require_once $_SERVER['DOCUMENT_ROOT'] . '/local/lib/SentOrderToBitrix24/order_functions.php';

use CIBlockElement as Element;
use CIBlockSection as Section;
use Bitrix\Main\Loader,
    Bitrix\Main;
use Bitrix\Sale,
    Bitrix\Main\Application,
    Bitrix\Main\Web\Uri,
    Bitrix\Main\Web\HttpClient,
    Bitrix\Sale\Order;

Bitrix\Main\Loader::includeModule("sale");
Bitrix\Main\Loader::includeModule("iblock");

//ставим событие на создание заказа, а потом вызываем функцию
$eventManager = \Bitrix\Main\EventManager::getInstance();
$eventManager->addEventHandler('sale', 'OnSaleOrderSaved', ['SentOrderToB24','mainMethod']);


//echo 'YO!';

//$obj = new SentOrderToB24;
//echo $obj->test();
//
//
//$order_id = 6695;
//$orderMassive = $obj->mainMethod($order_id);
//
//echo '<pre>';
//print_r($orderMassive);
//echo '</pre>';

