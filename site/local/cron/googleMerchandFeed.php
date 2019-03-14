<?php
$HOST = 'https://veneto.ua';
//$HOST = 'https://veneto.devlab.in.ua';
//$HOST = 'https://demo.devlab.in.ua';
ini_set("memory_limit","980M");
set_time_limit(0);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

$filename = $_SERVER["DOCUMENT_ROOT"].'/googleMerchandFeed.xml';
$fp = fopen($filename, "w");
$resCleanFile = ftruncate($fp, 0);
if($resCleanFile){
  echo 'ОК. Файл '.$filename.' успешно очищен.<br>';
}
fclose($fp);

CModule::IncludeModule("iblock");
CModule::IncludeModule("sale");
CModule::IncludeModule("socialservices");
$resArray = array();
$XMLsave ='<?xml version="1.0" encoding="UTF-8"?>'.PHP_EOL.
'<rss version = "2.0"'.PHP_EOL.
    'xmlns:g = "http://base.google.com/ns/1.0">'.PHP_EOL.
    "\t". "<channel>";

$arFilter = array(
  'IBLOCK_ID' => 9,
//  'ACTIVE' => 'Y',
//  "SECTION_ID" => 1484,//del this string after testing
//  "INCLUDE_SUBSECTIONS" => "Y"
);

$select = array(
  "ID",
  "NAME",
  "DETAIL_PICTURE",
  "IBLOCK_SECTION_ID",
  "DETAIL_TEXT",
  "CODE",
  "ACTIVE"
);
$items = CIBlockElement::GetList(array(), $arFilter, false, false, $select);
while ($item = $items->Fetch()){
//  echo '<pre>';print_r($item);echo '</pre>';
  $resArrayTemp = array();
  $resArrayTemp['OFFERS']=CCatalogSKU::getOffersList($item['ID'], $iblockID = 9, $skuFilter = array("ACTIVE" => "Y"), $fields = array("NAME", "PROPERTY_SHIFR"), $propertyFilter = array());
  $resArrayTemp['ID']=$item['ID'];
  $resArrayTemp['NAME']=$item['NAME'];
  $resArrayTemp['DETAIL_PICTURE']=CFile::GetPath($item['DETAIL_PICTURE']);
  $resArrayTemp['IBLOCK_SECTION_ID']=$item['IBLOCK_SECTION_ID'];
  $resArrayTemp['DETAIL_TEXT']=$item['DETAIL_TEXT'];
  $resArrayTemp['CODE']=$item['CODE'];
  $resArrayTemp['ACTIVE']=$item['ACTIVE'];

    $resArray[$item['ID']]=$resArrayTemp;
}

////echo '<pre>';print_r($resArray);echo '</pre>';
echo 'Обработанно товаров: ' . count($resArray)  . '<br>';

foreach($resArray as $key => $val){
  foreach($val['OFFERS'][$key] as $k => $v){
    $ar_price = CCatalogProduct::GetOptimalPrice($k, 1, $USER->GetUserGroupArray(), 'N');
    $resArray[$key]['OFFERS'][$key][$k]['PRICE'] = $ar_price['RESULT_PRICE']['BASE_PRICE'].' UAH';
    $resArray[$key]['OFFERS'][$key][$k]['SALE_PRICE'] = $ar_price['RESULT_PRICE']['DISCOUNT_PRICE'].' UAH';
    if($ar_price['RESULT_PRICE']['DISCOUNT_PRICE'] == $ar_price['RESULT_PRICE']['BASE_PRICE']){
      $resArray[$key]['OFFERS'][$key][$k]['SALE_PRICE'] = '';
    }
  }
  $nav = CIBlockSection::GetNavChain(false, $val['IBLOCK_SECTION_ID']);
  $navResArrayTemp = array();
  while ($sectionItem = $nav->Fetch()){
    $navArrTemp = array();
    $navArrTemp[] = $sectionItem['NAME'];
    $navResArrayTemp['NAV'][] = $navArrTemp;
  }

  foreach($navResArrayTemp['NAV'] as $navKey => $navVal){
    $resArray[$key]['NAV'][] = $navVal[0];
  }
}


$resItemsArray = array();
$cn = 1;
foreach ($resArray as $key => $value){
  foreach($value['OFFERS'][$key] as $k => $v){
    $resItemsArray[$k]['id'] = $k;
    $resItemsArray[$k]['title'] = $v['NAME'];
    $resItemsArray[$k]['description'] = $value['DETAIL_TEXT'];
    $resItemsArray[$k]['link'] = $HOST . '/' . $value['CODE'].'#offer='.$k;
    $resItemsArray[$k]['image_link'] = $HOST . '/' . $value['DETAIL_PICTURE'];
    $resItemsArray[$k]['condition'] = 'новый';
    $resItemsArray[$k]['availability'] = ($value['ACTIVE'] == 'Y' ? 'In stock' : 'Out of stock');
    $resItemsArray[$k]['price'] = $v['PRICE'];
    $resItemsArray[$k]['sale_price'] = $v['SALE_PRICE'];
    $resItemsArray[$k]['brand'] = 'Veneto';
    $resItemsArray[$k]['mpn'] = $v['PROPERTY_SHIFR_VALUE'];
    $navItemString = 'Главная > ';
    foreach($value['NAV'] as $navItem){
      $navItemString .= $navItem . ' > ';
    }
    $navItemString = substr($navItemString, 0, -3);
    $resItemsArray[$k]['product_type'] = $navItemString;
  }
}
$availab = '';
foreach($resItemsArray as $key => $value){
  $value['description'] = strip_tags($value['description']);
  $value['description'] = str_replace("&nbsp;","",$value['description']);
  $value['description'] = str_replace("&","&#x26;",$value['description']);

  if($value['description'] == ''){
      $value['description'] = 'Описание отсутствует';
  }

  $XMLsave .= PHP_EOL."\t\t"."<item>" . PHP_EOL.
      "\t\t\t"."<g:id>" . $value['id'] . "</g:id>" . PHP_EOL.
      "\t\t\t"."<title>" . $value['title'] . "</title>" . PHP_EOL.
      "\t\t\t"."<g:description>" . $value['description'] . "</g:description>" . PHP_EOL.
      "\t\t\t"."<g:product_type>" . $value['product_type'] . "</g:product_type>" . PHP_EOL.
      "\t\t\t"."<link>" . $value['link'] . "</link>" . PHP_EOL.
      "\t\t\t"."<g:image_link>" . $value['image_link'] . "</g:image_link>" . PHP_EOL.
      "\t\t\t"."<g:condition>" . $value['condition'] . "</g:condition>" . PHP_EOL.
      "\t\t\t"."<g:availability>" . $value['availability'] . "</g:availability>" . PHP_EOL.
      "\t\t\t"."<g:price>" . $value['price'] . "</g:price>" . PHP_EOL.
      "\t\t\t"."<g:brand>" . $value['brand'] . "</g:brand>" . PHP_EOL.
      "\t\t\t"."<g:mpn>" . $value['mpn'] . "</g:mpn>" . PHP_EOL.
      "\t\t"."</item>";
}

$XMLsave.= PHP_EOL.
    "\t".
    '</channel>'.PHP_EOL.
    '</rss>';
//echo '<pre>';print_r($resItemsArray);echo '</pre>';
echo 'Обработанно торговых предложений: ' . count($resItemsArray)  . '<br>';

//Запись.
$filename = $_SERVER["DOCUMENT_ROOT"].'/googleMerchandFeed.xml';
$fp = fopen($filename, "w");
fwrite($fp, $XMLsave);
fclose($fp);
if($filename){

  $now = date('h:i:s, j-m-y');

  echo 'OK. Результат успешно сохранен в файл '.$filename.' '.$now.'<br>';
  echo 'memory_get_peak_usage = '.memory_get_peak_usage(true)/1024/1024 . 'Mb<br>';
  echo '<a href="/googleMerchandFeed.xml" target="_blank">Открыть </a>файл с результатом (откроется в новой вкладке)<br>';
  echo '<a href="/googleMerchandFeed.xml" download>Скачать </a>файл с результатом<br>';
}



