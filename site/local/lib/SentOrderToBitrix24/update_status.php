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


$data = json_decode(file_get_contents("php://input"));
$request_list = json_decode(json_encode($data), true);

//$request_list = Application::getInstance()->getContext()->getRequest()->toArray();
//$request_list['order_id'] = 6748;
//$request_list['status_id'] = 'N'; //оплачен;



$res = null;

//if($request_list['order_id'] > 0 ) {
//    $order = Sale\Order::load($request_list['order_id']);
//
//    $order->setField('STATUS_ID', $request_list['status_id']);
//    $order->save();
//    $res = $order->save();
//}
//
echo json_encode(['result' => 1]);
//print_r($res);

$file = $_SERVER['DOCUMENT_ROOT'].'/local/lib/SentOrderToBitrix24/IncomeData.log';
file_put_contents($file, print_r($request_list, true), FILE_APPEND | LOCK_EX);

?>
