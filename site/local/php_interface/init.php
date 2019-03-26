<?php
include_once $_SERVER['DOCUMENT_ROOT'] . '/local/vendor/autoload.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/local/lib/Geniusee/Esputnik/esputnik_api.php';
include_once $_SERVER['DOCUMENT_ROOT'] . '/local/lib/Geniusee/LiqPay/liqpay.php';

//отправка заказов в crm
include_once $_SERVER['DOCUMENT_ROOT'] . '/local/lib/SentOrderToBitrix24/order_functions.php';


require 'constants.php';
require 'functions.php';

$file_path = $_SERVER['DOCUMENT_ROOT'] . '/_data/phone_numbers/numbers.php';
$GLOBALS['numbers'] = include_once $file_path;


$eventManager = \Bitrix\Main\EventManager::getInstance();


//заказы в срм при создании
//$eventManager->addEventHandler('sale', 'OnSaleOrderSaved', ['SentOrderToB24','mainMethod']);
$eventManager->addEventHandler('sale', 'OnSaleOrderSaved', 'sentOrderToCrm');
function sentOrderToCrm(\Bitrix\Main\Event $event){
    $orderObject = $event->getParameter("ENTITY");
    $orderId = $orderObject->getId();

    $isNew = $event->getParameter("IS_NEW");

    if($isNew){
        $classObj = new SentOrderToB24;
        $result = $classObj->mainMethod($orderId);

        $file = $_SERVER['DOCUMENT_ROOT'].'/local/lib/SentOrderToBitrix24/SentOrderLog.log';
        file_put_contents($file, print_r(['date'=>date('d.m.Y H:i:s'),'result' => $result], true), FILE_APPEND | LOCK_EX);
    }

}


//-- events for main

$eventManager->addEventHandler(
    'main',
    'onBeforeUserLoginByHttpAuth',
    'onBeforeUserLoginByHttpAuthHandler'
);
$eventManager->addEventHandler(
    'main',
    'OnBeforeEventSend',
    'esputnikEventHandler'
);
$eventManager->addEventHandler(
    'main',
    'OnAdminTabControlBegin',
    'AddVirtualGroupsIndexLink'
);
$eventManager->addEventHandler(
    'main',
    'OnUserTypeBuildList',
    [
        'Epages\\UserTypeGroupProps', 'GetUserTypeDescription'
    ]
);
$eventManager->addEventHandler(
    'main',
    'OnBuildGlobalMenu',
    [
        'Epages\Events\OnBuildGlobalMenu', 'OnBuildGlobalMenuHandler'
    ]
);
//-- end

//-- events for iblock
$eventManager->addEventHandler(
    'iblock',
    'OnAfterIBlockElementAdd',
    'DoIBlockAfterSave'
);
$eventManager->addEventHandler(
    'iblock',
    'OnAfterIBlockElementUpdate',
    'DoIBlockAfterSave'
);
$eventManager->addEventHandler(
    'iblock',
    'OnAfterIBlockElementAdd',
    'reindexElementsVirtualGroups'
);
/*$eventManager->addEventHandler(
    'iblock',
    'OnAfterIBlockElementUpdate',
    'reindexElementsVirtualGroups'
);*/
/*$eventManager->addEventHandler(
    'iblock',
    'OnBeforeIBlockSectionUpdate',
    [
        '\Epages\Events\OnBeforeIBlockSectionUpdate', 'calculateMinPriceSection'
    ]
);*/
$eventManager->addEventHandler(
    'iblock',
    'OnBeforeIBlockSectionUpdate',
    [
        '\Epages\Events\OnBeforeIBlockSectionUpdate', 'blockActivatingSection'
    ]
);
//-- end

//-- events for catalog
$eventManager->addEventHandler(
    'catalog',
    'OnGetDiscountResult',
    "OnGetDiscountResultHandler"
);
$eventManager->addEventHandler(
    'catalog',
    'OnGetOptimalPriceResult',
    "OnGetOptimalPriceResultHandler"
);
$eventManager->addEventHandler(
    'catalog',
    'OnPriceAdd',
    'DoIBlockAfterSave'
);
$eventManager->addEventHandler(
    'catalog',
    'OnPriceUpdate',
    'DoIBlockAfterSave'
);
$eventManager->addEventHandler(
  'catalog',
  'OnDiscountAdd',
  'DoIBlockAfterSave'
);
$eventManager->addEventHandler(
  'catalog',
  'OnDiscountUpdate',
  'DoIBlockAfterSave'
);
$eventManager->addEventHandler(
    'catalog',
    'OnPriceUpdate',
    'reindexElementsVirtualGroups'
);
//-- end

//-- events for sale
$eventManager->addEventHandler(
    'sale',
    'OnBeforeBasketAdd',
    'OnBeforeBasketAddHandler'
);
// $eventManager->addEventHandler(
//     'sale',
//     'OnBeforeBasketAdd',
//     'OnBeforeBasketAddHandler'
// );
$eventManager->addEventHandler(
    'sale',
    'OnSaleOrderEntitySaved',
    'OnStatusChange'
);
$eventManager->addEventHandler(
    'sale',
    'OnBeforeSaleBasketItemEntityDeleted',
    ['Epages\Events\OnSaleBasketItemEntityDeleted', 'checkProductSet']
);
$eventManager->addEventHandler(
    'sale',
    'OnSaleBasketItemBeforeSaved',
    ['Epages\Events\OnSaleBasketItemBeforeSaved', 'checkProductSet']
);
//-- end

//-- events for search
/*$eventManager->addEventHandler(
    'search',
    'BeforeIndex',
    ['Epages\Events\OnSearch', 'changePhraseForDiffLang']
);*/
//-- end

$eventManager->addEventHandler(
    'sale',
    'OnAfterSaleOrderFinalAction',
    ['Epages\Events\OnAfterSaleOrderFinalAction', 'recalculateCustomPrices']
);
//$eventManager->addEventHandler(
//    'sale',
//    'OnOrderNewSendEmail',
//    'ModifyOrderSaleMails'
//);

AddEventHandler("sale", "OnOrderNewSendEmail", "ModifyOrderSaleMails");

AddEventHandler('form', 'onBeforeResultAdd', 'SendEmailEsputnikIndividualSize');

AddEventHandler("sale", "OnBasketAdd", "SyncBasketAdd");

AddEventHandler("sale", "OnBasketUpdate", "SyncBasketQnt");

AddEventHandler("sale", "OnBeforeBasketDelete", "SyncBasketDelete");

AddEventHandler("sale", "OnOrderAdd", "OnOrderAddHandler");

AddEventHandler("main", "OnUserTypeBuildList", array("MyCurledTypeAktsii", "GetUserTypeDescription"));
AddEventHandler("main", "OnUserTypeBuildList", array("MyCurledTypeAktsiiUA", "GetUserTypeDescription"));

AddEventHandler("main", "OnUserTypeBuildList", array("MyCurledTypeArticles", "GetUserTypeDescription"));
AddEventHandler("main", "OnUserTypeBuildList", array("MyCurledTypeArticlesUA", "GetUserTypeDescription"));

//dartin1
/* регистрация обработчика события */
AddEventHandler('bxmaker.authuserphone', 'onSendCode', 'bxmaker_authuserphone_onSendCode');
function bxmaker_authuserphone_onSendCode($arFields)
{
    SetCookie("user-username", "", 0, "/");
    SetCookie("user-delivery", "", 0, "/");
    SetCookie("user-phone", "", 0, "/");
    SetCookie("user-city", "", 0, "/");
    SetCookie("user-email", "", 0, "/");
    //send_sms($arFields['PHONE'], 'Ваш временный код - ' . $arFields['CODE']);
    $user = 'geekunitsteam@gmail.com';
    $password = 'B8Uks9kli';
    $send_sms_url = 'https://esputnik.com/api/v1/message/sms';
    $from = 'Veneto';
    $text = $arFields['CODE'];
    $number = $arFields['PHONE'];
    $json_value = new stdClass();
    $json_value->text = $text;
    $json_value->from = $from;
    $json_value->phoneNumbers = array($number);
    function send_request($url, $json_value, $user, $password)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json_value));
        curl_setopt($ch, CURLOPT_HEADER, 1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Accept: application/json', 'Content-Type: application/json'));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_USERPWD, $user . ':' . $password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);
        curl_close($ch);
        var_dump($output);
    }

    send_request($send_sms_url, $json_value, $user, $password);
    return true;
}

/* регистрация обработчика события */
AddEventHandler('bxmaker.authuserphone', 'onUserAdd', 'bxmaker_authuserphone_onUserAdd');

function bxmaker_authuserphone_onUserAdd($arFields)
{
    CModule::IncludeModule("sale");
    //создаём профиль
    //PERSON_TYPE_ID - идентификатор типа плательщика, для которого создаётся профиль
    $arProfileFields = array(
        "NAME" => "Профиль покупателя (" . $arFields['PHONE'] . ')',
        "USER_ID" => $arFields['USER_ID'],
        "PERSON_TYPE_ID" => 1
    );
    $PROFILE_ID = CSaleOrderUserProps::Add($arProfileFields);

    //если профиль создан
    if ($PROFILE_ID) {
        //формируем массив свойств
        $PROPS = Array(
            array(
                "USER_PROPS_ID" => $PROFILE_ID,
                "ORDER_PROPS_ID" => 3,
                "NAME" => "phone",
                "VALUE" => $arFields['PHONE']
            ),
        );
        //добавляем значения свойств к созданному ранее профилю
        foreach ($PROPS as $prop)
            CSaleOrderUserPropsValue::Add($prop);
    }
}


AddEventHandler("main", "OnAfterUserRegister", "OnBeforeUserRegisterHandler");

function OnBeforeUserRegisterHandler(&$arFields)
{/*
    //создаём профиль
    //PERSON_TYPE_ID - идентификатор типа плательщика, для которого создаётся профиль
    $arProfileFields = array(
        "NAME" => "Профиль покупателя (".$arFields['PHONE'].')',
        "USER_ID" => $arFields['USER_ID'],
        "PERSON_TYPE_ID" => 1
    );
    $PROFILE_ID = CSaleOrderUserProps::Add($arProfileFields);

    //если профиль создан
    if ($PROFILE_ID)
    {
        //формируем массив свойств
        $PROPS=Array(
            array(
                "USER_PROPS_ID" => $PROFILE_ID,
                "ORDER_PROPS_ID" => 3,
                "NAME" => "Телефон",
                "VALUE" => $arFields['PHONE']
            )
        );
        //добавляем значения свойств к созданному ранее профилю
        foreach ($PROPS as $prop)
            CSaleOrderUserPropsValue::Add($prop);
    }*/
}

AddEventHandler("main", "OnBeforeUserUpdate", "OnAfterUserUpdateHandler");
function OnAfterUserUpdateHandler(&$arFields)
{
    CModule::IncludeModule("sale");
    if (CUser::IsAuthorized()) {
        //получим ID профиля($PROFILE_ID2) по ID пользователя, подразумевается что профиль всего один!
        $res1 = CSaleOrderUserProps::GetList(
            array(),
            array('USER_ID' => $arFields['ID']),
            false,
            false,
            array('ID')
        );
        while ($res2 = $res1->Fetch()) {
            $PROFILE_ID2 = $res2['ID'];
        };
        CSaleOrderUserPropsValue::DeleteAll($PROFILE_ID2);
        $PROPS2 = Array(
            array(
                "USER_PROPS_ID" => $PROFILE_ID2,
                "ORDER_PROPS_ID" => 1,
                "NAME" => "name",
                "VALUE" => $arFields['NAME']
            ),
            array(
                "USER_PROPS_ID" => $PROFILE_ID2,
                "ORDER_PROPS_ID" => 3,
                "NAME" => "phone",
                "VALUE" => $arFields["PERSONAL_PHONE"]
            ),
            array(
                "USER_PROPS_ID" => $PROFILE_ID2,
                "ORDER_PROPS_ID" => 7,
                "NAME" => "street",
                "VALUE" => $arFields['PERSONAL_STREET']
            ),
            array(
                "USER_PROPS_ID" => $PROFILE_ID2,
                "ORDER_PROPS_ID" => 8,
                "NAME" => "e-mail",
                "VALUE" => $arFields['EMAIL']
            ),
        );
        //добавляем значения свойств к созданному ранее профилю
        foreach ($PROPS2 as $prop2)
            CSaleOrderUserPropsValue::Add($prop2);
    };
}

;
AddEventHandler("main", "OnBeforeUserLogout", Array("Logout", "OnBeforeUserLogoutHandler"));

class Logout
{
    function OnBeforeUserLogoutHandler($arParams)
    {
        SetCookie("user-username", "", 0, "/");
        SetCookie("user-delivery", "", 0, "/");
        SetCookie("user-phone", "", 0, "/");
        SetCookie("user-city", "", 0, "/");
        SetCookie("user-email", "", 0, "/");
    }
}

AddEventHandler("main", "OnBeforeUserLogin", Array("Login", "OnBeforeUserLoginHandler"));

class Login
{
    // создаем обработчик события "OnBeforeUserLogin"
    function OnBeforeUserLoginHandler(&$arFields)
    {
        SetCookie("user-username", "", 0, "/");
        SetCookie("user-delivery", "", 0, "/");
        SetCookie("user-phone", "", 0, "/");
        SetCookie("user-city", "", 0, "/");
        SetCookie("user-email", "", 0, "/");
    }
}

AddEventHandler("search", "BeforeIndex", "BeforeIndexHandler");
// создаем обработчик события "BeforeIndex"
function BeforeIndexHandler($arFields)
{
    if (!CModule::IncludeModule("iblock"))
        return $arFields;
    if ($arFields["MODULE_ID"] == "iblock" && $arFields["PARAM2"] == 9) {
        $db_props_eng = CIBlockElement::GetProperty(
            $arFields["PARAM2"],
            $arFields["ITEM_ID"],
            array("sort" => "asc"),
            Array("CODE" => "SEARCH_NAME_ENG"));
        if ($ar_props = $db_props_eng->Fetch())
            $arFields["TITLE"] .= " " . $ar_props["VALUE"];
        $db_props_ru = CIBlockElement::GetProperty(
            $arFields["PARAM2"],
            $arFields["ITEM_ID"],
            array("sort" => "asc"),
            Array("CODE" => "SEARCH_NAME_RU"));
        if ($ar_props = $db_props_ru->Fetch())
            $arFields["TITLE"] .= " " . $ar_props["VALUE"];
        $db_props_ua = CIBlockElement::GetProperty(
            $arFields["PARAM2"],
            $arFields["ITEM_ID"],
            array("sort" => "asc"),
            Array("CODE" => "SEARCH_NAME_UA"));
        if ($ar_props = $db_props_ua->Fetch())
            $arFields["TITLE"] .= " " . $ar_props["VALUE"];
        $db_props_default = CIBlockElement::GetProperty(
            $arFields["PARAM2"],
            $arFields["ITEM_ID"],
            array("sort" => "asc"),
            Array("CODE" => "RU_NAME"));
        if ($ar_props = $db_props_default->Fetch())
            $arFields["TITLE"] .= " " . $ar_props["VALUE"];
        $arFields["BODY"] = $arFields["TITLE"];
    }
    else {
        $arFields["TITLE"] = "";
        $arFields["BODY"] = "" ;
    }
    return $arFields; // вернём изменения
}

//My functions for phones
function getOperator($number){
  $number_clean = preg_replace('![^0-9]+!', '', $number);
    if(preg_match('/^050|^066|^095|^099/', $number)) {
        echo '<a href="tel:'.$number_clean.'" class="option icon-vodafone">'.$number.'</span>';
    }
    if(preg_match('/^039|^067|^068|^096|^097|^098/', $number)) {
        echo '<a href="tel:'.$number_clean.'" class="option icon-kievstar">'.$number.'</span>';
    }
    if(preg_match('/^063|^073|^093/', $number)) {
        echo '<a href="tel:'.$number_clean.'" class="option icon-lifecell">'.$number.'</span>';
    }
    if(preg_match('/^0800|^0 800|^0-800/', $number)) {
        echo '<a href="tel:'.$number_clean.'" class="option number icon-phone binct-phone-number-1">'.$number.'</span>';
    }
}
/*
function getFirstOperator($number){
    if(preg_match('/^050|^066|^095|^099/', $number[0])) {
        echo '<a href="tel:'.$number[0].'" class="option icon-vodafone">'.$number[0].'</span>';
    }
    if(preg_match('/^039|^067|^068|^096|^097|^098/', $number[0])) {
        echo '<a href="tel:'.$number[0].'" class="option icon-kievstar">'.$number[0].'</span>';
    }
    if(preg_match('/^063|^073|^093/', $number[0])) {
        echo '<a href="tel:'.$number[0].'" class="option icon-lifecell">'.$number[0].'</span>';
    }
    if(preg_match('/^0800|^0 800|^0-800/', $number[0])) {
        echo '<a href="tel:'.$number[0].'" class="option number icon-phone binct-phone-number-1">'.$number[0].'</span>';
    }
}

function getSingleNumberForHeader($number){
    if(preg_match('/^050|^066|^095|^099/', $number[0])) {
        echo '<span class="option icon-vodafone arr">'.$number[0].'</span>';
    }
    if(preg_match('/^039|^067|^068|^096|^097|^098/', $number[0])) {
        echo '<span class="option icon-kievstar arr">'.$number[0].'</span>';
    }
    if(preg_match('/^063|^073|^093/', $number[0])) {
        echo '<span class="option icon-lifecell arr">'.$number[0].'</span>';
    }
    if(preg_match('/^0800|^0 800|^0-800/', $number[0])) {
        echo '<span class="number icon-phone arr binct-phone-number-1">'.$number[0].'</span>';
    }
}*/

function getDefaultNumberForHeader($number){
  //$number = preg_replace('![^0-9]+!', '', $number);
    if(preg_match('/^050|^066|^095|^099/', $number)) {
        echo '<span class="option icon-vodafone arr">'.$number.'</span>';
    }
    if(preg_match('/^039|^067|^068|^096|^097|^098/', $number)) {
        echo '<span class="option icon-kievstar arr">'.$number.'</span>';
    }
    if(preg_match('/^063|^073|^093/', $number)) {
        echo '<span class="option icon-lifecell arr">'.$number.'</span>';
    }
    if(preg_match('/^0800|^0 800|^0-800/', $number)) {
        echo '<span class="number icon-phone arr binct-phone-number-1">'.$number.'</span>';
    }

}

function getOperatorIcon ($number) {
    $icon_class = '';

    if(preg_match('/^050|^066|^095|^099/', $number)) {
        $icon_class = 'icon-vodafone';
    }
    if(preg_match('/^039|^067|^068|^096|^097|^098/', $number)) {
        $icon_class = 'icon-kievstar';
    }
    if(preg_match('/^063|^073|^093/', $number)) {
        $icon_class = 'icon-lifecell';
    }
    if(preg_match('/^0800|^0 800|^0-800/', $number)) {
        $icon_class = 'icon-phone';
    }

    return $icon_class;
}

//Здесь заменить ID highload-блока на тот, который будет на боевом!
function getMyHighloadblock(){
    CModule::IncludeModule('highloadblock');
    $arHLBlock = Bitrix\Highloadblock\HighloadBlockTable::getById(3)->fetch();
    $obEntity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($arHLBlock);
    $strEntityDataClass = $obEntity->getDataClass();
    return $rsData = $strEntityDataClass::getList(array(
        'select' => array('ID','UF_CHECK','UF_WHEN','UF_PLACE'),
        'order' => array('ID' => 'ASC'),
        'limit' => '50',
    ));

}

function getDefaultPhone() {
    CModule::IncludeModule('highloadblock');
    $arHLBlock = Bitrix\Highloadblock\HighloadBlockTable::getById(3)->fetch();
    $obEntity = Bitrix\Highloadblock\HighloadBlockTable::compileEntity($arHLBlock);
    $strEntityDataClass = $obEntity->getDataClass();
    return $rsData = $strEntityDataClass::getList(array(
        'select' => array('ID','UF_CHECK','UF_WHEN','UF_PLACE'),
        'filter' => array('UF_CHECK' => 'default'),
        'order' => array('ID' => 'ASC'),
        'limit' => '50',
    ));
}

//detect user device
function detectMobile() {
  $is_mobile = '0';

  if(preg_match('/(android|up.browser|up.link|mmp|symbian|smartphone|midp|wap|phone)/i', strtolower($_SERVER['HTTP_USER_AGENT']))) {
    $is_mobile=1;
  }

  if((strpos(strtolower($_SERVER['HTTP_ACCEPT']),'application/vnd.wap.xhtml+xml')>0) or ((isset($_SERVER['HTTP_X_WAP_PROFILE']) or isset($_SERVER['HTTP_PROFILE'])))) {
    $is_mobile=1;
  }

  $mobile_ua = strtolower(substr($_SERVER['HTTP_USER_AGENT'],0,4));
  $mobile_agents = array('w3c ','acs-','alav','alca','amoi','andr','audi','avan','benq','bird','blac','blaz','brew','cell','cldc','cmd-','dang','doco','eric','hipt','inno','ipaq','java','jigs','kddi','keji','leno','lg-c','lg-d','lg-g','lge-','maui','maxo','midp','mits','mmef','mobi','mot-','moto','mwbp','nec-','newt','noki','oper','palm','pana','pant','phil','play','port','prox','qwap','sage','sams','sany','sch-','sec-','send','seri','sgh-','shar','sie-','siem','smal','smar','sony','sph-','symb','t-mo','teli','tim-','tosh','tsm-','upg1','upsi','vk-v','voda','wap-','wapa','wapi','wapp','wapr','webc','winw','winw','xda','xda-');

  if(in_array($mobile_ua,$mobile_agents)) {
    $is_mobile=1;
  }

  if (isset($_SERVER['ALL_HTTP'])) {
    if (strpos(strtolower($_SERVER['ALL_HTTP']),'OperaMini')>0) {
      $is_mobile=1;
    }
  }

  if (strpos(strtolower($_SERVER['HTTP_USER_AGENT']),'windows')>0) {
    $is_mobile=0;
  }

  return $is_mobile;
}