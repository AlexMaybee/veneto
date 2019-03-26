<?
require($_SERVER ["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

define("NOT_CHECK_PERMISSIONS",true);
define("CHECK_PERMISSIONS", "N");
header('Content-type: application/json');

use Bitrix\Main\Loader,
    Bitrix\Main;
use Bitrix\Sale;
use \Bitrix\Main\Application;

Bitrix\Main\Loader::includeModule("sale");
Bitrix\Main\Loader::includeModule("iblock");


$request_list = Application::getInstance()->getContext()->getRequest()->toArray();
//$request_list['order_id'] = 6748;
//$request_list['status_id'] = 'N'; //оплачен;

$res = null;
if($request_list['order_id'] > 0 ) {
    $order = Sale\Order::load($request_list['order_id']);

    $order->setField('STATUS_ID', $request_list['status_id']);
    $order->save();
    $res = $order->save();
}

echo json_decode($res);
//print_r($res);

?>
