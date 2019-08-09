<?
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
use Bitrix\Main\Web\HttpClient;

 ?>
 <?

$filter = ['NAME' => 'Запорожье','IBLOCK_ID' => 49];
$select = ['IBLOCK_SECTION_ID'/*,'ID','NAME'*/];
$result = getCityInfoblockCatalogByValue($filter,$select);
$regionName = explode(' ',$result['SECTION_NAME_REGION'])[0];

//запрос полей сделки
$b24RegionId = '';

$urlQ = 'crm.deal.userfield.list';
$b24FieldsMassive =  askBitrix24($urlQ,[]);
if($b24FieldsMassive['result']){
  foreach ($b24FieldsMassive['result'] as $field){
     if($field['FIELD_NAME'] == 'UF_CRM_1433939257'){
       foreach ($field['LIST'] as $enumValue){
        if(trim($regionName) == trim($enumValue['VALUE']))
         $b24RegionId = $enumValue['ID'];
       }
     }
  }
}

/*echo '<pre>';
//print_r(explode(' ',$result['SECTION_NAME_REGION']));
//print_r($b24FieldsMassive);
print_r($regionName);
print_r($b24RegionId);*/

function getCityInfoblockCatalogByValue($filter,$select){
    $result = false;
    $resMassive = CIBlockElement::GetList(["SORT"=>"ASC"],$filter,false,false,$select);
    if($arRes = $resMassive->getNext()) {
        $catalogMAssive = CIBlockSection::GetByID($arRes['IBLOCK_SECTION_ID']);
        if($arCatalog = $catalogMAssive->getNext()) $arRes['SECTION_NAME_REGION'] = $arCatalog['NAME'];
        $result = $arRes;
    }
    return $result;
}


function askBitrix24($url, $queryData)
{
    $httpClient = new Bitrix\Main\Web\HttpClient();
    $httpClient->setHeader('Content-Type', 'application/json', true);
    $response = $httpClient->post('https://crm.veneto.ua/rest/11074/5n4ji6gph3d0qg7u/' . $url . '.json', json_encode($queryData));
    return json_decode($response,true);
}

//Функции Битрикс24
/*function add($id,$phone){

CModule::IncludeModule("sale"); // подключение модуля продаж

$dbBasketItems = CSaleBasket::GetList(array(), array("ORDER_ID" => 2065), false, false, array());
 while ($arItems = $dbBasketItems->Fetch()) {
 //print_r($arItems['NAME']);
 $prod[]=array("PRODUCT_NAME" => $arItems['NAME'], "PRICE" => round($arItems['PRICE'],2), "QUANTITY" => $arItems['QUANTITY']);
 }

	
    $queryData = http_build_query(array(
        "id"=> 3080,
            "rows" =>   $prod,
    ));

	
	$url = 'crm.lead.productrows.set.json';
    $res = webhook($queryData, $url);
	print_r($res);
	
	

}


function webhook($queryData, $url)
{
    $queryUrl = 'https://crm.veneto.ua/rest/1236/4sx8bxsbleq3kz98/'.$url;
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $queryUrl,
        CURLOPT_POSTFIELDS => $queryData,
    ));

    $result = curl_exec($curl);
    curl_close($curl);

    $result = json_decode($result, 1);
    return $result;
}

$orderId=127878311;
$formData['PHONE']='+38 (333) 333-33-33';
add($orderId,$formData['PHONE']);*/
?>