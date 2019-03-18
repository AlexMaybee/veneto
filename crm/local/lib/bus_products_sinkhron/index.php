<?
define("NOT_CHECK_PERMISSIONS",true); //эти 2 строки  для того, чтобы не нужно было авторизироваться для доступа к странице-получателе, т.е. к этой
define("CHECK_PERMISSIONS", "N");
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

require_once($_SERVER['DOCUMENT_ROOT'].'/local/lib/bus_products_sinkhron/class.php');


CModule::IncludeModule('crm');
CModule::IncludeModule('iblock');
CModule::IncludeModule('catalog'); //это для типа цен


$obj = new Bus_Products_Import;

$data = json_decode(file_get_contents("php://input"));
$data = json_decode(json_encode($data), true);


if($data['action'] == 'import_products_and_sale_offers'){


    $mainResult = $obj->mainSinkhronMethod($data['products']);
   // echo json_encode(['date' => date('d.m.Y H:i:s'), $mainResult['result'],$mainResult['message']]); //ответ
    echo json_encode(array('date' => date('d.m.Y H:i:s'), 'data' => $mainResult)); //ответ
    if(!$mainResult['result']){
        $obj->logging(1,[$mainResult,$data['products']]);
    }

}
else{
    echo json_encode($error = ['date' => date('d.m.Y H:i:s'),'error' => 'Request error: wrong request action!']);
    $obj->logging(2,['action' => $data['action'],'error' => 'Request error: wrong request action!']);

}
//$log_res = $obj->logging(3,$data); //тестовое логирование


//старт работ по сохранению товаров и товарных предложений
//$testMassive = [
//    'action' => '',
//    'products' => [
//        [
//            'ID' => 22334,
//           // 'ID' => 2222,
//            'NAME' => 'ТЕСТОВЫЙ РАЗДЕЛ 1',
//            'SALE_OFFERS' => [
//                2233444 => [
//                    'ID' => 2233444,
//                    'NAME' => 'ТЕСТОВОЕ Товарное предложене обнова 1',
//                    'CATALOG_QUANTITY' => 500, //доступное кол-во
//                    'IBLOCK_ID' => 20, //тот же блок
//                ],
//                2233455 => [
//                    'ID' => 2233455,
//                    'NAME' => 'ТЕСТОВОЕ Товарное предложение обнова 2',
//                    'CATALOG_QUANTITY' => 200, //доступное кол-во
//                    'IBLOCK_ID' => 20, //тот же блок
//                ],
//            ]
//        ],
//        [
//            'ID' => 33333,
//            'NAME' => '2 ТЕСТОВЫЙ РАЗДЕЛ',
//            'SALE_OFFERS' => [
//                33311 => [
//                    'ID' => 33311,
//                    'NAME' => '333 ТЕСТОВОЕ Товарное предложене',
//                    'CATALOG_QUANTITY' => 320, //доступное кол-во
//                ],
//                33322 => [
//                    'ID' => 33322,
//                    'NAME' => '444 ТЕСТОВОЕ Товарное предложение',
//                    'CATALOG_QUANTITY' => 45, //доступное кол-во
//                ],
//            ]
//        ],
//
//    ]
//
//];
//
//$mainResult = $obj->mainSinkhronMethod($testMassive['products']);
//echo '<pre>';
//print_r($mainResult);

//пробуем работать с товарными предложениями
/*$sale_offers = [
    2233444 => [
        'ID' => 2233444,
        'NAME' => 'ТЕСТОВОЕ Товарное предложение 1',
        'CATALOG_QUANTITY' => 4000, //доступное кол-во
    ],
    2233455 => [
        'ID' => 2233455,
        'NAME' => 'ТЕСТОВОЕ Товарное предложение 2',
        'CATALOG_QUANTITY' => 3400, //доступное кол-во
    ],
];
$sale_offers_result = [];
foreach ($sale_offers as $key => $sale_off){
    $resu = $obj->workWithProductOffers($sale_off,20,141);
    if(!$resu['result']) $sale_offers_result['error'][] = $resu['message'];
    else{
        //$sale_offers_result['result'][] = $resu['result'];
        $sale_offers_result['message'][] = $resu['message'];
    }
}

echo '<pre>';
print_r($sale_offers_result);*/


?>