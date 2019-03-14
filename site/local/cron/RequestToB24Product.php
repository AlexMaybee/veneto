<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");
use Bitrix\Main\Web\HttpClient;


CModule::IncludeModule('iblock');
CModule::IncludeModule('catalog');


$arParams['IBLOCK_ID'] = 9;

//список товаров в виде id + название
$products = getProducts($arParams['IBLOCK_ID']);
//echo '<pre>';
//print_r($products);
//echo '</pre>';

//получение торгового предложения для товара в массив товара отдельным полем SALE_OFFERS
foreach ($products as $number => $prodData){

    //делим запрос на части, т.к. больше 61 join mysql не поддерживает
    $offerFields_part1 = array(
        'ID','NAME',
        'PROPERTY_122','PROPERTY_123','PROPERTY_124',
        'PROPERTY_172','PROPERTY_173','PROPERTY_174','PROPERTY_175','PROPERTY_176','PROPERTY_177','PROPERTY_178','PROPERTY_179','PROPERTY_180',
    );

    $prod_sale_offers_res1 = getSaleOffersByFilter($prodData['ID'],$offerFields_part1);

    foreach ($prod_sale_offers_res1 as $val1){
        foreach ($val1 as $val2){
            $products[$number]['SALE_OFFERS'][$val2['ID']] = $val2;
        }
    }

    //делим запрос на части, т.к. больше 61 join mysql не поддерживает
    $offerFields_part2 = array(
        'ID',
        'PROPERTY_181','PROPERTY_182','PROPERTY_183','PROPERTY_184','PROPERTY_185','PROPERTY_186','PROPERTY_187','PROPERTY_188','PROPERTY_189',
        'PROPERTY_190','PROPERTY_191','PROPERTY_192','PROPERTY_193','PROPERTY_194','PROPERTY_195','PROPERTY_196','PROPERTY_197','PROPERTY_198',
    );
    $prod_sale_offers_res2 = getSaleOffersByFilter($prodData['ID'],$offerFields_part2);

    foreach ($prod_sale_offers_res2 as $val1){
        foreach ($val1 as $val2){
            $products[$number]['SALE_OFFERS'][$val2['ID']] = array_merge($products[$number]['SALE_OFFERS'][$val2['ID']], $val2);
        }
    }

    //делим запрос на части, т.к. больше 61 join mysql не поддерживает
    $offerFields_part3 = array(
        'ID',
        'PROPERTY_199','PROPERTY_200','PROPERTY_201','PROPERTY_202','PROPERTY_203','PROPERTY_204','PROPERTY_205','PROPERTY_206','PROPERTY_207',
        'PROPERTY_208','PROPERTY_209','PROPERTY_210','PROPERTY_211','PROPERTY_212','PROPERTY_213','PROPERTY_214','PROPERTY_215',
    );
    $prod_sale_offers_res3 = getSaleOffersByFilter($prodData['ID'],$offerFields_part3);

    foreach ($prod_sale_offers_res3 as $val1){
        foreach ($val1 as $val2){
            $products[$number]['SALE_OFFERS'][$val2['ID']] = array_merge($products[$number]['SALE_OFFERS'][$val2['ID']], $val2);
        }
    }

    //делим запрос на части, т.к. больше 61 join mysql не поддерживает
    $offerFields_part4 = array(
        'ID','DETAIL_PICTURE', //получить путь картинки методом!!!
        'PROPERTY_250','PROPERTY_618','PROPERTY_502','PROPERTY_525','PROPERTY_589','PROPERTY_523','PROPERTY_226','PROPERTY_251',
        'PROPERTY_460','PROPERTY_531'
    );
    $prod_sale_offers_res4 = getSaleOffersByFilter($prodData['ID'],$offerFields_part4);

    foreach ($prod_sale_offers_res4 as $val1){
        foreach ($val1 as $val2){
            $val2['DETAIL_PICTURE_PATH'] = 'https://veneto.ua'.CFile::GetPath($val2["DETAIL_PICTURE"]);
            unset($val2["DETAIL_PICTURE"]); //удаляем поле, оно не нужно
            $products[$number]['SALE_OFFERS'][$val2['ID']] = array_merge($products[$number]['SALE_OFFERS'][$val2['ID']], $val2);
        }
    }

}


/*echo '<pre>';
print_r($products);
echo '</pre>';*/


$sent_res = sentDataToCRM(['action' => 'import_products_and_sale_offers', 'products' => $products]);
print_r($sent_res);


//echo '<pre>';
//print_r($prod_predlojenie);



//список товаров (имя + id)
function getProducts($iblock){
    $products = [];
    $arSelect = Array("ID",'NAME'); // Уменьшил количество полей
    $arFilter = Array("IBLOCK_ID"=> $iblock, "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y"/*, "SECTION_ID"=> array(17, 18, 40)*/);
    $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
    while($ob = $res->GetNextElement())
    {
        $arFields = $ob->GetFields();
        $products[] = $arFields;
    }
    return $products;
}

function getSaleOffersByFilter($prodID,$offerFields){
    $iblockId = 9;
    $offersFilter = array('ACTIVE' => 'Y', 'ACTIVE_DATE' => 'Y', '>CATALOG_PRICE_1' => 0);
    $propertyFields = array('ID', 'PROPERTY_TYPE', 'MULTIPLE', 'USER_TYPE');
    $res = CCatalogSku::getOffersList(
        $prodID,$iblockId, $offersFilter, $offerFields, array(), array('USE_PROPERTY_ID' => 'Y', 'PROPERTY_FIELDS' => $propertyFields));
    return $res;
}


function sentDataToCRM($queryData){
    $httpClient = new HttpClient();
    $httpClient->setHeader('Content-Type', 'application/json', true);
    //$result = $httpClient->post('https://everydayfitness.com.ua/api/'.$url, json_encode($queryData));
    $result = $httpClient->post('https://crm.veneto.ua/local/lib/bus_products_sinkhron/index.php', json_encode($queryData));
    return json_decode($result);
}