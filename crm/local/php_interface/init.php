<?php

CJSCore::Init(array("jquery"));

include_once 'functions.php';

//статус сделки в статус заказа
//include_once $_SERVER['DOCUMENT_ROOT'] . "/local/lib/bus_products_sinkhron/order_status_sent_to_bus.php";

AddEventHandler('main', 'OnBeforeProlog', 'checkCrmStoreDeal');



AddEventHandler("crm", "OnAfterCrmDealAdd", "printInfo");
function printInfo(&$arFields)
{
    CModule::IncludeModule("CRM");
    AddMessage2Log($arFields);
if (strstr($arFields['ADDITIONAL_INFO'], 'БЫСТРЫЙ ЗАКАЗ')){
	DeleteDeal($arFields['ID']);
}
}


function DeleteDeal($id){
	AddMessage2Log('delete '.$arFields['ID']);
	$deal = new CCrmDeal;
    $rs = $deal->Delete($id);
}


