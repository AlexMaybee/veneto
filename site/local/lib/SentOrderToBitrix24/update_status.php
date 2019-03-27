<?
require($_SERVER ["DOCUMENT_ROOT"] . "/bitrix/modules/main/include/prolog_before.php");

define("NOT_CHECK_PERMISSIONS",true);
define("CHECK_PERMISSIONS", "N");
header('Content-type: application/json');

use Bitrix\Main\Loader,
    Bitrix\Main;
use Bitrix\Sale;
use \Bitrix\Main\Application;
use \Bitrix\Sale\Order;

Bitrix\Main\Loader::includeModule("sale");
Bitrix\Main\Loader::includeModule("iblock");


$data = json_decode(file_get_contents("php://input"));
$request_list = json_decode(json_encode($data), true);

//$request_list = Application::getInstance()->getContext()->getRequest()->toArray();
//$request_list['order_id'] = 6768;
//$request_list['status_id'] = 'CANCEL';
//$request_list['reasons_text'] = 'ПОТОМУ ЧТО ТАК ВЫШЛО! КАКАЯ_ТО ПРИЧИНА!';



$res = null;

if($request_list['order_id'] > 0 ) {

    $answ = [
        'result' => false,
        'error' => false,
    ];

    //если статус - отмена, запуск другой функции
    if($request_list['status_id'] === 'CANCEL'){
        $res = CSaleOrder::CancelOrder($request_list['order_id'],'Y',$request_list['reasons_text']);
        $answ['result'] = $res;
    }
    else{

        //на всякий случай сначала убираем отмену с заказа!!!
        $answ['cancel_order_status_changed'] = CSaleOrder::CancelOrder($request_list['order_id'],'N','');

        //а потом уже обновляем статус
        $order = Sale\Order::load($request_list['order_id']);

        $order->setField('STATUS_ID', $request_list['status_id']);
        $order->save();
        $res = $order->save();


        if(!$res->isSuccess()) $answ['error'] = $res->getError(); //Проверка, что статус обновлен
        $answ['result'] = $res->isSuccess();
    }


}

echo json_encode($answ);
//echo json_encode(['result' => 1]);
//print_r($res);

$file = $_SERVER['DOCUMENT_ROOT'].'/local/lib/SentOrderToBitrix24/UpdateOrderStatusesFromCrm.log';
file_put_contents($file, print_r(['date' => $request_list,'result' => $answ], true), FILE_APPEND | LOCK_EX);

?>
