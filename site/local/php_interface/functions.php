<?php
use Epages\Helper\DataIblock;
use \Bitrix\Main\Application;
use \Bitrix\Main\Service\Geoip;
//console.log php varibles
// function console_log($data)
// {
//     echo '<script>';
//     echo 'console.log(' . json_encode($data) . ')';
//     echo '</script>';
// }

//for save variable in session
Class GarbageStorage{
    private static $storage = array();
    public static function set($name, $value){ self::$storage[$name] = $value;}
    public static function get($name){ return self::$storage[$name];}
}

	//для дебага
	function _l($msg){
			$fp = fopen($_SERVER['DOCUMENT_ROOT']."/local/message.log", "a");
			if(is_array($msg)) $msg=var_export($msg, true);
			fputs($fp, '['.date('Y-m-d H:i:s')."]\t".$msg."\n");
			fclose($fp);
	}

	function _m($msg, $die=0){

		if(is_array($msg)){
			echo "<pre>";
			var_export($msg);
			echo "</pre>";
		}else{
			echo $msg;
		}

		if($die) die();

	}

	function _ma($msg){
		if($GLOBALS['USER']->IsAdmin())
			_m($msg);
	}

	function _md($msg, $die=0){
		if($_GET["debug"]=="Y") _m($msg, $die);
	}

	function _cl($msg){
		echo '<script>';
		echo 'console.log('.json_encode($msg).');';
		echo '</script>';
	}

	function _cla($msg){
		if($GLOBALS['USER']->IsAdmin())
			_c($msg);
	}

function _dump($msg){
    echo "<pre>";
    var_dump($msg);
}

function mSet(){
  $GLOBALS['mSet'][] = memory_get_peak_usage(true)/1024/1024;
}

function plural_form($number,$before,$after) {
    $cases = array(2,0,1,1,1,2);
    echo $before[($number%100>4 && $number%100<20)? 2: $cases[min($number%10, 5)]].' '.$number.' '.$after[($number%100>4 && $number%100<20)? 2: $cases[min($number%10, 5)]];
}

function getParentSectionID($childSectionId) {
    $parentSectionId = 0;
    $ibsTreeResource = \CIBlockSection::GetNavChain( false, $childSectionId, array( "ID" ) );
    if($sectionItem = $ibsTreeResource->Fetch()){
        $parentSectionId = $sectionItem["ID"];
    }

    return $parentSectionId;
}

//в комментариях к товару и к постам в блоге извлекаем имя автора из возможных мест возможными способами
function getAuthorName ($comment, $lang) {
    $name = "";

    if (!empty($comment["AUTHOR_NAME"])) {
        $name = trim($comment["AUTHOR_NAME"]);
    } else {
        $rsUser = CUser::GetByID($comment["AUTHOR_ID"]);
        $arUser = $rsUser->Fetch();

        if (!empty($arUser["NAME"])) {
            $name = trim($arUser["NAME"]);
        }
    }

    if ("" === $name) {
        if ("ua" === $lang) {
            $name = "Гість";
        } else {
            $name = "Гость";
        }
    }

    return $name;
}

/**
 * Доступные типы цен
 *
 * @return array
 */
function getAvailablePricesCode()
{
    return ['BASE'];
}

function pre($str, $bexit=false, $background = "#fff")
{
    global $USER;
    if ($USER->GetID()==1 || $_SESSION["DEVELOPER_DEBUG"]) {
        print "<pre style='background: $background'>";
        print_r($str);
        print "</pre>";
        if ($bexit) {
            exit;
        }
    }
    if ($_REQUEST["DEVELOPER_DEBUG"])
        $_SESSION["DEVELOPER_DEBUG"] = true;

}

function V7ExchangeOnAfterIBlockElementAdd($arFields)
{
    Epages\Exchange\Processing::OnAfterIBlockElementAdd($arFields);
}

function V7ExchangeOnAfterIBlockElementUpdate($arFields)
{
    Epages\Exchange\Processing::OnAfterIBlockElementUpdate($arFields);
}

function OnGetDiscountResultHandler(&$arResult)
{
    if (count($arResult)>1)
    {
        $arTrace = array_reverse(Bitrix\Main\Diag\Helper::getBackTrace(5, 1));
        if ($arTrace[0]["function"] != "GetOptimalPrice")
        {
            $PRODUCT_ID = $arTrace[1]["args"][0];
            if ($PRODUCT_ID == 28418)
            {
                unset($arResult[0]);
            }
            /*if ($PRODUCT_ID == 28420)
            {
                $arResult = array();
            }
            elseif ($PRODUCT_ID == 28421)
            {
                unset($arResult[0]);
                $arResult[1]["NAME"] = "Новая сверхскидка";
               // pre($arResult);
            } */
        }
    }
}
function OnGetOptimalPriceResultHandler(&$arResult)
{
    static $availableContracts;
    if ($_SESSION["SELECTED_CONTRACT"])
    {
        if (!isset($availableContracts))
        {
            $availableContracts = Epages\Helper\Partner::getAvailableConracts();
        }
        $arContract = false;

        foreach($availableContracts as $contract)
        {
            if($contract["ID"] == $_SESSION["SELECTED_CONTRACT"]) {
                $arContract = $contract;
                break;
            }
        }

        if (!is_array($arContract) && count($availableContracts)>0)
        {
            $arContract = $availableContracts[0];
            $_SESSION["SELECTED_CONTRACT"] = $arContract["ID"];
        }
        if (!is_array($arContract))
        {
            unset($_SESSION["SELECTED_CONTRACT"]);
        }
        else
        {
            $arAvailabeTariffs = array();
            if ($arContract["PROPERTY_ID_TARIF_VALUE"])
                $arAvailabeTariffs[] = $arContract["PROPERTY_ID_TARIF_VALUE"];
            if ($arContract["PROPERTY_ID_TARIF_AKC_VALUE_ID"])
                $arAvailabeTariffs[] = $arContract["PROPERTY_ID_TARIF_AKC_VALUE_ID"];

            $newDiscountList = array();
            foreach ($arResult["DISCOUNT_LIST"] as $discount)
            {
                if ($arDiscount = \Bitrix\Catalog\DiscountTable::getList(array("filter" => array("ID" => $discount["ID"]), "select" => array("XML_ID")))->fetch())
                {
                    if (strpos($arDiscount["XML_ID"], "1C_")===0)
                    {
                        $arMatch = explode("_", $arDiscount["XML_ID"]);
                        $tarif =  $arMatch[1];
                        if (in_array($tarif, $arAvailabeTariffs))
                            $newDiscountList[] = $discount;
                    }
                    else
                    {
                        $newDiscountList[] = $discount;
                    }
                }

            }
            $result = CCatalogDiscount::applyDiscountList($arResult["PRICE"]["PRICE"], $arResult["PRICE"]["CURRENCY"], $newDiscountList);
            $result['UNROUND_PRICE'] = $result['PRICE'];
            $result['PRICE'] = Bitrix\Catalog\Product\Price::roundPrice(
                $arResult["PRICE"]['CATALOG_GROUP_ID'],
                $result['PRICE'],
                $result['CURRENCY']
            );
            //pre($result);
            $basePrice = $arResult['RESULT_PRICE']["BASE_PRICE"];
            $minPrice = $result['PRICE'];
            /*
            $dbBasketItems = CSaleBasket::GetList(
                array(
                    "NAME" => "ASC",
                    "ID" => "ASC"
                ),
                array(
                    "FUSER_ID" => CSaleBasket::GetBasketUserID(),
                    "LID" => SITE_ID,
                    "ORDER_ID" => "NULL",
                    "PRODUCT_ID" => $arResult["PRODUCT_ID"]
                ),
                false,
                false,
                array("ID", "QUANTITY")
            );
            if ($arItems = $dbBasketItems->Fetch()) {

                $quantity = $arItems["QUANTITY"];

                $discountQuantity = floor($quantity / 2);

                if ($discountQuantity > 0) {
                    $allPrice = $minPrice * $quantity;
                    $quantresult = CCatalogDiscount::applyDiscountList($minPrice, $arResult["PRICE"]["CURRENCY"], $newDiscountList);
                    $minPrice = $price = ($allPrice - $minPrice * $discountQuantity + $quantresult["PRICE"] * $discountQuantity) / $quantity;
                    //$arResult["DISCOUNT_LIST"][] = $discount[0];
                }
            }
            */
            $discountValue = ($basePrice > $minPrice ? $basePrice - $minPrice : 0);
            $arResult['RESULT_PRICE']['DISCOUNT'] = $discountValue;
            $arResult['RESULT_PRICE']['DISCOUNT_PRICE'] = $result['PRICE'];
            $arResult['DISCOUNT_PRICE'] = $result['PRICE'];
            $arResult['RESULT_PRICE']['UNROUND_DISCOUNT_PRICE'] = $result['UNROUND_PRICE'];
            $arResult['RESULT_PRICE']['PERCENT'] = (
            $basePrice > 0 && $discountValue > 0
                ? roundEx((100*$discountValue)/$basePrice, CATALOG_VALUE_PRECISION)
                : 0
            );
            $arResult["DISCOUNT"] = $newDiscountList[0];

        }

        /*
        if (count($arResult["DISCOUNT_LIST"])>1) {


            $discount = array(array_shift($arResult["DISCOUNT_LIST"]));
            $result = CCatalogDiscount::applyDiscountList($arResult["PRICE"]["PRICE"], $arResult["PRICE"]["CURRENCY"], $arResult["DISCOUNT_LIST"]);
            $result['UNROUND_PRICE'] = $result['PRICE'];
            $result['PRICE'] = Bitrix\Catalog\Product\Price::roundPrice(
                $arResult["PRICE"]['CATALOG_GROUP_ID'],
                $result['PRICE'],
                $result['CURRENCY']
            );
            $basePrice = $arResult['RESULT_PRICE']["BASE_PRICE"];
            $minPrice = $result['PRICE'];

            $dbBasketItems = CSaleBasket::GetList(
                array(
                    "NAME" => "ASC",
                    "ID" => "ASC"
                ),
                array(
                    "FUSER_ID" => CSaleBasket::GetBasketUserID(),
                    "LID" => SITE_ID,
                    "ORDER_ID" => "NULL",
                    "PRODUCT_ID" => $arResult["PRODUCT_ID"]
                ),
                false,
                false,
                array("ID", "QUANTITY")
            );
            if ($arItems = $dbBasketItems->Fetch()) {

                $quantity = $arItems["QUANTITY"];

                $discountQuantity = floor($quantity / 2);
                if ($discountQuantity > 0) {
                    $allPrice = $minPrice * $quantity;
                    $quantresult = CCatalogDiscount::applyDiscountList($minPrice, $arResult["PRICE"]["CURRENCY"], $discount);
                    $minPrice = $price = ($allPrice - $minPrice * $discountQuantity + $quantresult["PRICE"] * $discountQuantity) / $quantity;
                    //$arResult["DISCOUNT_LIST"][] = $discount[0];
                }
            }
            $discountValue = ($basePrice > $minPrice ? $basePrice - $minPrice : 0);
            $arResult['RESULT_PRICE']['DISCOUNT'] = $discountValue;
            $arResult['RESULT_PRICE']['DISCOUNT_PRICE'] = $result['PRICE'];
            $arResult['DISCOUNT_PRICE'] = $result['PRICE'];
            $arResult['RESULT_PRICE']['UNROUND_DISCOUNT_PRICE'] = $result['UNROUND_PRICE'];
            $arResult['RESULT_PRICE']['PERCENT'] = (
            $basePrice > 0 && $discountValue > 0
                ? roundEx((100*$discountValue)/$basePrice, CATALOG_VALUE_PRECISION)
                : 0
            );
            $arResult["DISCOUNT"] = $arResult["DISCOUNT_LIST"][0];


        } */

    }
}
/**
 * Runs on events:
 * - OnAfterIBlockElementAdd
 * - OnAfterIBlockElementUpdate
 * - OnPriceAdd
 * - OnPriceUpdate
 * - OnDiscountAdd
 * - OnDiscountUpdate
 *
 * Calculates min and max prices and saves them into propd
 * @param      $arg1
 * @param bool $arg2
 */
function DoIBlockAfterSave($arg1, $arg2 = false)
{
    $ELEMENT_ID = false;
    $IBLOCK_ID = false;
    $OFFERS_IBLOCK_ID = false;
    $OFFERS_PROPERTY_ID = false;
    global $USER;

    //Check for catalog event
    if (is_array($arg2) && $arg2["PRODUCT_ID"] > 0) {
        //Get iblock element
        $rsPriceElement = CIBlockElement::GetList(
            array(),
            array(
                "ID" => $arg2["PRODUCT_ID"],
            ),
            false,
            false,
            array("ID", "IBLOCK_ID")
        );
        if ($arPriceElement = $rsPriceElement->Fetch()) {
            $arCatalog = CCatalog::GetByID($arPriceElement["IBLOCK_ID"]);
            if (is_array($arCatalog)) {
                //Check if it is offers iblock
                if ($arCatalog["OFFERS"] == "Y") {
                    //Find product element
                    $rsElement = CIBlockElement::GetProperty(
                        $arPriceElement["IBLOCK_ID"],
                        $arPriceElement["ID"],
                        "sort",
                        "asc",
                        array("ID" => $arCatalog["SKU_PROPERTY_ID"])
                    );
                    $arElement = $rsElement->Fetch();
                    if ($arElement && $arElement["VALUE"] > 0) {
                        $ELEMENT_ID = $arElement["VALUE"];
                        $IBLOCK_ID = $arCatalog["PRODUCT_IBLOCK_ID"];
                        $OFFERS_IBLOCK_ID = $arCatalog["IBLOCK_ID"];
                        $OFFERS_PROPERTY_ID = $arCatalog["SKU_PROPERTY_ID"];
                    }
                } //or iblock wich has offers
                elseif ($arCatalog["OFFERS_IBLOCK_ID"] > 0) {
                    $ELEMENT_ID = $arPriceElement["ID"];
                    $IBLOCK_ID = $arPriceElement["IBLOCK_ID"];
                    $OFFERS_IBLOCK_ID = $arCatalog["OFFERS_IBLOCK_ID"];
                    $OFFERS_PROPERTY_ID = $arCatalog["OFFERS_PROPERTY_ID"];
                } //or it's regular catalog
                else {
                    $ELEMENT_ID = $arPriceElement["ID"];
                    $IBLOCK_ID = $arPriceElement["IBLOCK_ID"];
                    $OFFERS_IBLOCK_ID = false;
                    $OFFERS_PROPERTY_ID = false;
                }
            }
        }
    } //Check for iblock event
    elseif (is_array($arg1) && $arg1["ID"] > 0 && $arg1["IBLOCK_ID"] > 0) {
        //Check if iblock has offers
        $arOffers = CIBlockPriceTools::GetOffersIBlock($arg1["IBLOCK_ID"]);
        if (is_array($arOffers)) {
            $ELEMENT_ID = $arg1["ID"];
            $IBLOCK_ID = $arg1["IBLOCK_ID"];
            $OFFERS_IBLOCK_ID = $arOffers["OFFERS_IBLOCK_ID"];
            $OFFERS_PROPERTY_ID = $arOffers["OFFERS_PROPERTY_ID"];
        }
    }

    if ($ELEMENT_ID) {
        static $arPropCache = array();
        if (!array_key_exists($IBLOCK_ID, $arPropCache)) {
            //Check for MINIMAL_PRICE property
            $rsProperty = CIBlockProperty::GetByID("MINIMUM_PRICE", $IBLOCK_ID);
            $arProperty = $rsProperty->Fetch();
            if ($arProperty)
                $arPropCache[$IBLOCK_ID] = $arProperty["ID"];
            else
                $arPropCache[$IBLOCK_ID] = false;
        }

        if ($arPropCache[$IBLOCK_ID]) {
            //Compose elements filter
            $arProductID = array($ELEMENT_ID);
            if ($OFFERS_IBLOCK_ID) {
                $rsOffers = CIBlockElement::GetList(
                    array(),
                    array(
                        "IBLOCK_ID" => $OFFERS_IBLOCK_ID,
                        "PROPERTY_" . $OFFERS_PROPERTY_ID => $ELEMENT_ID,
                        "ACTIVE_DATE" => "Y",
                        "ACTIVE" => "Y",
                    ),
                    false,
                    false,
                    array("ID")
                );
                while ($arOffer = $rsOffers->Fetch())
                    $arProductID[] = $arOffer["ID"];
            }

            $minPrice = 9999999999;
            $maxPrice = false;
            //Get prices
            $rsPrices = CPrice::GetList(
                array(),
                array(
                    "BASE" => "Y",
                    "PRODUCT_ID" => $arProductID,
                )
            );
          //$arGroups = $USER->GetUserGroupArray();
          $percDiscount = false;
            while ($arPrice = $rsPrices->Fetch()) {
                $PRICE = intval($arPrice["PRICE"]);

                if ($minPrice > $PRICE) {
                  $minPrice = $PRICE;
                }

                if ($maxPrice < $PRICE) {
                  $maxPrice = $PRICE;
                }

                   $percDiscount = CCatalogProduct::GetOptimalPrice($arPrice["PRODUCT_ID"], 1, false, 'N');
                  if ($percDiscount['RESULT_PRICE']['PERCENT']) {
                    $percDiscount = ($percDiscount['RESULT_PRICE']['PERCENT'] / 100);
                  }else{
                    $percDiscount = false;
                  }
                }
            }

            //Save found minimal price into property
            if ($minPrice !== false) {
              if ($percDiscount !== false) {
                $minPrice = round(($minPrice - ($minPrice * $percDiscount)), 2);
              }
              CIBlockElement::SetPropertyValuesEx(
                $ELEMENT_ID,
                $IBLOCK_ID,
                array(
                  "MINIMUM_PRICE" => $minPrice,
                )
              );
            }



                CIBlockElement::SetPropertyValuesEx(
                    $ELEMENT_ID,
                    $IBLOCK_ID,
                    array(
                        "MAXIMUM_PRICE" => $maxPrice,
                    )
                );
        }

}
function OnBeforeBasketAddHandler(&$arFields)
{
    if ($_REQUEST["RS_AUTHOR_WIDTH"])
    {
        $arFields["PROPS"][]  = array(
            "NAME" => "Ширина",
            "CODE" => "WIDTH",
            "VALUE" => $_REQUEST["RS_AUTHOR_WIDTH"]
        );
    }
    if ($_REQUEST["RS_AUTHOR_HEIGHT"])
    {
        $arFields["PROPS"][]  = array(
            "NAME" => "Длина",
            "CODE" => "HEIGHT",
            "VALUE" => $_REQUEST["RS_AUTHOR_HEIGHT"]
        );
    }

}
function onBeforeUserLoginByHttpAuthHandler(&$arAuth)
{
    if ($arAuth["basic"]["username"]=="bitrix_support")
        unset($arAuth["basic"]);
}

function getSectionArticles($sectionId)
{
    if (CModule::IncludeModule('iblock')) {
        $arFilter = array(
            'IBLOCK_ID' => CATALOG_IBLOCK_ID,
            'ID' => intval($sectionId)
        );
        $db_list = CIBlockSection::GetList(
            array(),
            $arFilter,
            false,
            array(
                'NAME',
                'UF_LINKED_PROMOS',
                'UF_LINKED_PROMOS_UA',
                'UF_LINKED_ARTICLE',
                'UF_LINKED_ARTICLE_UA'
            )
        );
        if ($ar_result = $db_list->GetNext()) {
            if ('ua' === LANGUAGE_ID) {
                $result = array_merge($ar_result['UF_LINKED_PROMOS_UA'], $ar_result['UF_LINKED_ARTICLE_UA']);
            } else {
                $result = array_merge($ar_result['UF_LINKED_PROMOS'], $ar_result['UF_LINKED_ARTICLE']);
            }
            return $result;
        }
    }
}

function reindexElementsVirtualGroups($arg1, $arg2 = false)
{
    $ELEMENT_ID = false;
    $IBLOCK_ID = false;

    //Check for catalog event
    if (is_array($arg2) && $arg2["PRODUCT_ID"] > 0) {
        //Get iblock element
        $rsPriceElement = CIBlockElement::GetList(
            array(),
            array(
                "ID" => $arg2["PRODUCT_ID"],
            ),
            false,
            false,
            array("ID", "IBLOCK_ID")
        );
        if ($arPriceElement = $rsPriceElement->Fetch()) {
            $arCatalog = CCatalog::GetByID($arPriceElement["IBLOCK_ID"]);
            if (is_array($arCatalog)) {
                //Check if it is offers iblock
                if ($arCatalog["OFFERS"] == "Y") {
                    //Find product element
                    $rsElement = CIBlockElement::GetProperty(
                        $arPriceElement["IBLOCK_ID"],
                        $arPriceElement["ID"],
                        "sort",
                        "asc",
                        array("ID" => $arCatalog["SKU_PROPERTY_ID"])
                    );
                    $arElement = $rsElement->Fetch();
                    if ($arElement && $arElement["VALUE"] > 0) {
                        $ELEMENT_ID = $arElement["VALUE"];
                        $IBLOCK_ID = $arCatalog["PRODUCT_IBLOCK_ID"];
                    }
                } //or iblock wich has offers
                elseif ($arCatalog["OFFERS_IBLOCK_ID"] > 0) {
                    $ELEMENT_ID = $arPriceElement["ID"];
                    $IBLOCK_ID = $arPriceElement["IBLOCK_ID"];
                } //or it's regular catalog
                else {
                    $ELEMENT_ID = $arPriceElement["ID"];
                    $IBLOCK_ID = $arPriceElement["IBLOCK_ID"];
                }
            }
        }
    } //Check for iblock event
    elseif (is_array($arg1) && $arg1["ID"] > 0 && $arg1["IBLOCK_ID"] > 0) {
        //Check if iblock has offers
        $arOffers = CIBlockPriceTools::GetOffersIBlock($arg1["IBLOCK_ID"]);
        if (is_array($arOffers)) {
            $ELEMENT_ID = $arg1["ID"];
            $IBLOCK_ID = $arg1["IBLOCK_ID"];
        }
    }

    if ($ELEMENT_ID) {
        \Epages\VirtualGroupsIndexTable::reindexVirtualGroups();
        $indexer = new Epages\Facet\ReindexVirtualGroups($IBLOCK_ID);
        $indexer->reindexElementsVirtualGroup($ELEMENT_ID);
    }
}

function AddVirtualGroupsIndexLink(&$form)
{
    if($GLOBALS["APPLICATION"]->GetCurPage() == "/bitrix/admin/iblock_reindex.php")
    {
        ?>        <div id="reindex_result_div_custom">
            <div class="adm-info-message-wrap adm-info-message-gray">
                <div class="adm-info-message">
                    <div class="adm-info-message-title">Фасетный индес виртуальных групп</div>
                    ПОСЛЕ создания основного фасетного индекса нужно создать его для виртуальных групп <a href="/bitrix/admin/reindex_virtual_groups.php" target="_blank">вручную на этой странице</a>
                </div>
            </div>
        </div>
        <?php
    }
}
//console.log php varibles
// function console_log($data)
// {
//     echo '<script>';
//     echo 'console.log(' . json_encode($data) . ')';
//     echo '</script>';
// }

function setStandardSeoParams($iblockId, $sectionFilter)
{
    CModule::IncludeModule('iblock');

    global $APPLICATION;
    $rsSection = CIBlockSection::GetList(array(), $sectionFilter, false, array('ID'));
    $arResultSEO = $rsSection->GetNext();
    $ipropValues = new Bitrix\Iblock\InheritedProperty\SectionValues($iblockId, $arResultSEO["ID"]);
    $arResult["IPROPERTY_VALUES"] = $ipropValues->getValues();

    if ($arResult["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"] != "") {
        $APPLICATION->SetTitle($arResult["IPROPERTY_VALUES"]["SECTION_PAGE_TITLE"]);
    } elseif (isset($arResult["NAME"])) {
        $APPLICATION->SetTitle($arResult["NAME"]);
    }

    $browserTitle = \Bitrix\Main\Type\Collection::firstNotEmpty(
        $arResult["IPROPERTY_VALUES"], "SECTION_META_TITLE"
    );
    if (is_array($browserTitle))
        $APPLICATION->SetPageProperty("title", implode(" ", $browserTitle));
    elseif ($browserTitle != "")
        $APPLICATION->SetPageProperty("title", $browserTitle);

    $metaKeywords = \Bitrix\Main\Type\Collection::firstNotEmpty(
        $arResult["IPROPERTY_VALUES"], "SECTION_META_KEYWORDS"
    );
    if (is_array($metaKeywords))
        $APPLICATION->SetPageProperty("keywords", implode(" ", $metaKeywords));
    elseif ($metaKeywords != "")
        $APPLICATION->SetPageProperty("keywords", $metaKeywords);

    $metaDescription = \Bitrix\Main\Type\Collection::firstNotEmpty(
        $arResult["IPROPERTY_VALUES"], "SECTION_META_DESCRIPTION"
    );
    if (is_array($metaDescription))
        $APPLICATION->SetPageProperty("description", implode(" ", $metaDescription));
    elseif ($metaDescription != "")
        $APPLICATION->SetPageProperty("description", $metaDescription);
}

//eSputnik
function esputnikEventHandler($arFields, $arTemplate)
{ //_l($emails);
	$mess = $arTemplate["MESSAGE"];
	$subject = $arTemplate["SUBJECT"];
	foreach($arFields as $keyField => $arField)
	{
		$mess = str_replace('#' . $keyField . '#', $arField, $mess);
		$subject = str_replace('#' . $keyField . '#', $arField, $subject);
	}
    $subject = str_replace('#SERVER_NAME#', SITE_SERVER_NAME, $subject);
    $subject = str_replace('#SITE_NAME#', "Венето", $subject);
	$mess = str_replace('#SERVER_NAME#', SITE_SERVER_NAME, $mess);
	$mess = str_replace('#SITE_NAME#', "Венето", $mess);
	$arEmails = array($arFields['EMAIL']);
	if ($arFields['BCC'])
		$arEmails[] = $arFields['BCC'];
//     $arEmails[] = 'store@veneto.ua';
	$emailResult = \Geniusee\Esputnik\esputnikApi::sendEmail("marketing@veneto.ua",
		$subject, $mess, $arEmails);
//     $arEmails = ['store@veneto.ua', 'oleksandr.voznyi@devlab.in.ua'];
//     $emailResult = \Geniusee\Esputnik\esputnikApi::sendEmail("marketing@veneto.ua",
// 		$subject, $mess, $arEmails);
	return false;
}
function OnStatusChange(Bitrix\Main\Event $event)
{
	$order = $event->getParameter("ENTITY");
	$oldValues = $event->getParameter("VALUES");
	$arOrderVals = $order->getFields()->getValues();
	if($arOrderVals['STATUS_ID'] == 'F' && isset($oldValues['STATUS_ID']) && $oldValues['STATUS_ID'] != 'F')
	{
		$userResult = CUser::GetByID($arOrderVals['USER_ID']);
		$user = $userResult->GetNext();
		\Geniusee\Esputnik\esputnikApi::createEvent($user['EMAIL'], 'orderSuccess',
            array(
                array("name"=>"orderId", "value" => $arOrderVals['ID'])
            ));
	}

}
function stemming_letter_ua()
{
    return "АаБбВвГгҐґДдЕеЄєЖжЗзИиІіЇїЙйКкЛлМмНнОоПпРрСсТтУуФфХхЦцЧчШшЩщЬьЮюЯя";//украинский язык
}
function ModifyOrderSaleMails($orderID, &$eventName, &$arFields)
{
    if(CModule::IncludeModule("sale") && CModule::IncludeModule("iblock"))
    {
        //СОСТАВ ЗАКАЗА РАЗБИРАЕМ SALE_ORDER НА ЗАПЧАСТИ
        $strOrderList = "";
        $dbBasket = CSaleBasket::GetList(
            array("NAME" => "ASC"),
            array("ORDER_ID" => $orderID),
            false,
            false,
            array("PRODUCT_ID", "ID", "NAME", "QUANTITY", "PRICE", "CURRENCY")
        );
        while ($arBasketItems = $dbBasket->Fetch())
        {
            $productResult = CIBlockElement::GetByID($arBasketItems['PRODUCT_ID']);
            $productElement = $productResult->GetNextElement();
            $product = $productElement->GetFields();
            $product['PROPERTIES'] = $productElement->GetProperties();
            $fullName = DataIblock::parseName($arBasketItems["NAME"]);
            //$img = CFile::GetPath($product['DETAIL_PICTURE']);
            $img = CFile::ResizeImageGet($product['DETAIL_PICTURE'], array("width" => "200", "height" => "200"), BX_RESIZE_IMAGE_PROPORTIONAL);
            $img_link = 'https://veneto.ua'.$img["src"];
            $product_link = 'https://veneto.ua'.$product["DETAIL_PAGE_URL"];
            $size = ($product['PROPERTIES']['MATTRESS_SIZE']['VALUE'])?:$product['PROPERTIES']['SP_SIZE']['VALUE'];
            $arBasketItems['PRICE'] = round($arBasketItems['PRICE']);
            $strOrderList .= "<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"body\" style=\"border-collapse:separate;mso-table-lspace:0pt;mso-table-rspace:0pt;width:100%;\">
                         <tbody><tr>
                             <td style=\"font-family:sans-serif;font-size:14px;vertical-align:middle;max-width:35%;width: 35%;padding-right: 30px;padding-top: 20px;padding-bottom: 20px;\">
                                 <a href=\"$product_link\" target=\"_blank\">
                                    <img src=\"$img_link\" style=\"max-width:100%;width: 100%\">
                                 </a>
                             </td>
                             <td style=\"font-family:sans-serif;font-size:14px;vertical-align:middle;width: 40%;text-align: left;padding-top: 20px;padding-bottom: 20px;padding-right: 35px;\">
                                 <h4 style=\"font-size: 11px; color: #526277;font-weight: bold;margin-top: 0;margin-bottom: 4px;\">$fullName[0]</h4>
                                 <h3 style=\"font-size: 14px; color: #526277;font-weight: bold;margin-top: 0;margin-bottom: 9px;\">$fullName[1]</h3>

                                    <table border=\"0\" cellpadding=\"0\" cellspacing=\"0\" class=\"body\" style=\"border-collapse:separate;mso-table-lspace:0pt;mso-table-rspace:0pt;width:100%;\">
                                         <tbody><tr>
                                             <td style=\"font-family:sans-serif;font-size:14px;vertical-align:middle;width: 100%;\">
                                                 <p style=\"font-size: 10px;color: #526277;margin-top: 0;margin-bottom: 0;\">Размер: <span style=\"font-size: 10px;color: #526277;font-weight: bold;background-color: #e7e7e7;display: inline-block; padding: 3px 10px;border-radius: 10px;\">$size СМ</span></p>
                                             </td>
                                         </tr>
                                    </tbody></table>

                             </td>
                             <td style=\"font-family:sans-serif;font-size:14px;vertical-align:middle;width: 25%;text-align: left;padding-top: 20px;padding-bottom: 20px;\">
                                 <h3 style=\"font-size: 14px; color: #526277;font-weight: bold;\">$arBasketItems[QUANTITY] x $arBasketItems[PRICE] грн</h3>
                             </td>
                         </tr>
                        </tbody></table>";
        }
        //ОБЪЯВЛЯЕМ ПЕРЕМЕННУЮ ДЛЯ ПИСЬМА
        $arFields["ORDER_TABLE_ITEMS"] = $strOrderList;
    }
}

//для проверки присутствия товара в каком-то комплекте, нужна, чтоб выводить иконку комплекта в разделе
function checkIfProductInSet ($ID) {
    $result = false;

    if (intval($ID) > 0) {
        $arSelect = Array("ID");
        $arFilter = Array("IBLOCK_ID" => 41, "ACTIVE_DATE" => "Y", "ACTIVE" => "Y");
        $arFilter[] = array(
            "LOGIC" => "OR",
            array("PROPERTY_PRODUCT" => $ID),
            array("PROPERTY_ADDITIONAL_PRODUCT" => $ID));
        $res = \CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);

        while ($ob = $res->GetNextElement()) {
            $result = true;
            break;
        }
    }

    return $result;
}

function SendEmailEsputnikIndividualSize($WEB_FORM_ID, &$arFields, &$arrVALUES)
{
    global $USER;
    $mess = "";

    switch ($WEB_FORM_ID) {
        case 1:
            $mess .= "<div><b>Пользователь</b> - #NAME#</div>";
            $mess .= "<div><b>Телефон</b> - #PHONE#</div>";
            $mess .= "<div><b>Email</b> - #EMAIL#</div>";
            $subject = "Помощь профессионала!";

            $mess = str_replace('#NAME#', $arrVALUES["form_text_1"], $mess);
            $mess = str_replace('#PHONE#', $arrVALUES["form_text_2"], $mess);
            $mess = str_replace('#EMAIL#', $arrVALUES["form_email_3"], $mess);
            break;
        case 2:
        case 6:
            switch ($arrVALUES["form_radio_SIMPLE_QUESTION_723"]) {
                case 5:
                case 25:
                    $type = "Дилер";
                    break;
                case 6:
                case 26:
                    $type = "Представитель сегмента Отели или HoReCa";
                    break;
            }

            $mess .= "<div><b>Категория</b> - #TYPE#</div>";
            $mess .= "<div><b>Пользователь</b> - #NAME#</div>";
            $mess .= "<div><b>Телефон</b> - #PHONE#</div>";
            $mess .= "<div><b>Email</b> - #EMAIL#</div>";
            $subject = "Бизнес партнер!";

            $mess = str_replace('#NAME#', $arrVALUES["form_text_8"], $mess);
            $mess = str_replace('#PHONE#', $arrVALUES["form_text_4"], $mess);
            $mess = str_replace('#EMAIL#', $arrVALUES["form_email_7"], $mess);
            $mess = str_replace('#TYPE#', $type, $mess);
            break;
        case 3:
            $mess .= "<div><b>Пользователь</b> - #RS_USER_NAME#</div>";
            $mess .= "<div><b>Телефон</b> - #PHONE#</div>";
            $mess .= "<div><b>Заказ индивидуального размера</b> - #SIMPLE_QUESTION_974_RAW#</div>";
            $mess .= "<div><b>Название товара</b> - #MATRESS_NAME#</div>";
            $subject = "Заказ индивидуального размера!";

            $mess = str_replace('#RS_USER_NAME#', $arrVALUES["form_text_9"], $mess);
            $mess = str_replace('#PHONE#', $arrVALUES["form_text_10"], $mess);
            $mess = str_replace('#RS_USER_ID#', $USER::GetID(), $mess);
            $mess = str_replace('#SIMPLE_QUESTION_974_RAW#', $arrVALUES["form_text_12"], $mess);
            $mess = str_replace('#MATRESS_NAME#', $arrVALUES["matress_name"], $mess);
            break;
        case 4:
        case 5:
            $mess .= "<div><b>Пользователь</b> - #NAME#</div>";
            $mess .= "<div><b>Город</b> - #CITY#</div>";
            $mess .= "<div><b>Email</b> - #EMAIL#</div>";
            $mess .= "<br><br>";
            $mess .= "<div>#MESSAGE#</div>";
            $subject = "Напишите нам!";

            $mess = str_replace('#NAME#', $arrVALUES["form_text_19"], $mess);
            $mess = str_replace('#CITY#', $arrVALUES["city"], $mess);
            $mess = str_replace('#EMAIL#', $arrVALUES["form_email_23"], $mess);
            $mess = str_replace('#MESSAGE#', $arrVALUES["form_textarea_24"], $mess);
            break;
    }

    $emails = array(
        //"alexwithlenovo@gmail.com",
        "store@veneto.ua"
    );

    $emailResult = \Geniusee\Esputnik\esputnikApi::sendEmail("marketing@veneto.ua", $subject, $mess, $emails);
}

function SyncBasketAdd($ID, $arFields) {
    $fuserID = CSaleBasket::GetBasketUserID();

    if(SITE_ID == 'ru'){
        $other_site = 'ua';
    }elseif (SITE_ID == 'ua') {
        $other_site = 'ru';
    }

    if($ID) {

        $arBasketItems = array();
        $dbBasketItems = CSaleBasket::GetList(
            array("NAME" => "ASC","ID" => "ASC"),
            array("FUSER_ID" => $fuserID,
                "LID" => SITE_ID,
                "ORDER_ID" => "NULL"
            ),
            false,
            false,
            array(
                "ID",
                "MODULE",
                "NAME",
                "PRODUCT_ID",
                "QUANTITY",
                "PRICE",
                "LID"
            )
        );

        while ($arItems = $dbBasketItems->Fetch()) {
            $arBasketItems[] = $arItems; //корзина для текущего пользователя на текущем сайте
        }

        foreach ($arBasketItems as $key => $arBasketItem) {
            if($arBasketItem['ID'] == $ID){
                $PRODUCT_ID = $arBasketItem['PRODUCT_ID'];
                $PRICE = $arBasketItem['PRICE'];
            }
        }
        unset($dbBasketItems);
        unset($arBasketItems);

        $arBasketItems = array();
        $dbBasketItems = CSaleBasket::GetList(
            array("NAME" => "ASC","ID" => "ASC"),
            array("FUSER_ID" => $fuserID,
                "LID" => $other_site,
                "ORDER_ID" => "NULL"
            ),
            false,
            false,
            array("ID", "MODULE", "NAME", "PRODUCT_ID", "QUANTITY", "PRICE", "LID")
        );

        while ($arItems = $dbBasketItems->Fetch()) {
            $arBasketItems[] = $arItems; //корзина для текущего пользователя на втором сайте
        }

        $add_to_another_cart = true;
        foreach ($arBasketItems as $key => $arBasketItem) {
            if($arBasketItem['PRODUCT_ID'] == $PRODUCT_ID && $arBasketItem['PRICE'] == $PRICE){
                $add_to_another_cart = false;
                $qnt = $arBasketItem['QUANTITY'] + $arFields['QUANTITY'];
                $arFields2 = array("QUANTITY" => $qnt, "LID" => $other_site);
//                 CSaleBasket::Update($arBasketItem['ID'], $arFields2);
            }
        }

        unset($dbBasketItems);
        unset($arBasketItems);

        if ($add_to_another_cart) {
            $arFields['LID'] = $other_site;
            $arFields['IGNORE_CALLBACK_FUNC'] = "Y";
            CSaleBasket::Add($arFields);
        }

    }

    unset($_SESSION['SALE_USER_BASKET_QUANTITY'][SITE_ID][$fuserID]);
    unset($_SESSION['SALE_USER_BASKET_QUANTITY'][$other_site][$fuserID]);
}

function SyncBasketQnt($iId, $aFields) {
    $fuserID = CSaleBasket::GetBasketUserID();

    if(SITE_ID == 'ru'){
        $other_site = 'ua';
    }elseif (SITE_ID == 'ua') {
        $other_site = 'ru';
    }

    $arBasketItems1 = array();
    $dbBasketItems = CSaleBasket::GetList(
        array("NAME" => "ASC","ID" => "ASC"),
        array("FUSER_ID" => $fuserID,
            "LID" => SITE_ID,
            "ORDER_ID" => "NULL"
        ),
        false,
        false,
        array("ID", "MODULE", "NAME", "PRODUCT_ID", "QUANTITY", "PRICE", "LID")
    );

    while ($arItems = $dbBasketItems->Fetch()) {
        $arBasketItems1[] = $arItems; //корзина для текущего пользователя на текущем сайте
    }

    foreach ($arBasketItems1 as $key => $arBasketItem) {
        $PRODUCT_IDs[] = $arBasketItem['PRODUCT_ID'];
        $PRODUCTS_QNT[] = $arBasketItem['QUANTITY'];
    }

    $arBasketItems2 = array();
    $dbBasketItems = CSaleBasket::GetList(
        array("NAME" => "ASC","ID" => "ASC"),
        array("FUSER_ID" => $fuserID,
            "LID" => $other_site,
            "ORDER_ID" => "NULL"
        ),
        false,
        false,
        array("ID", "MODULE", "NAME", "PRODUCT_ID", "QUANTITY", "PRICE", "LID")
    );

    while ($arItems = $dbBasketItems->Fetch()) {
        $arBasketItems2[] = $arItems; //корзина для текущего пользователя на втором сайте
    }

    foreach ($arBasketItems2 as $key => $arBasketItem) {
        $key2 = array_search($arBasketItem['PRODUCT_ID'], $PRODUCT_IDs);

        $arFields2 = array("QUANTITY" => $PRODUCTS_QNT[$key2], "LID" => $other_site);

        CSaleBasket::Update($arBasketItem['ID'], $arFields2);

    }

    unset($_SESSION['SALE_USER_BASKET_QUANTITY'][SITE_ID][$fuserID]);
    unset($_SESSION['SALE_USER_BASKET_QUANTITY'][$other_site][$fuserID]);
}

function SyncBasketDelete($iId)
{
    $fuserID = CSaleBasket::GetBasketUserID();

    if(SITE_ID == 'ru'){
        $other_site = 'ua';
    }elseif (SITE_ID == 'ua') {
        $other_site = 'ru';
    }

    $arBasketItems = array();
    $dbBasketItems = CSaleBasket::GetList(
        array("NAME" => "ASC","ID" => "ASC"),
        array("FUSER_ID" => $fuserID,
            "LID" => SITE_ID,
            "ORDER_ID" => "NULL"
        ),
        false,
        false,
        array("ID", "MODULE", "NAME", "PRODUCT_ID", "QUANTITY", "PRICE", "LID")
    );
    while ($arItems = $dbBasketItems->Fetch())
    {
        $arBasketItems[] = $arItems; //корзина для текущего пользователя на текущем сайте
    }

    foreach ($arBasketItems as $key => $arBasketItem) {
        if($arBasketItem['ID'] == $iId){
            $PRODUCT_ID = $arBasketItem['PRODUCT_ID'];
        }
    }
    unset($dbBasketItems);
    unset($arBasketItems);


    $arBasketItems = array();
    $dbBasketItems = CSaleBasket::GetList(
        array("NAME" => "ASC","ID" => "ASC"),
        array("FUSER_ID" => $fuserID,
            "LID" => $other_site,
            "ORDER_ID" => "NULL"
        ),
        false,
        false,
        array("ID", "MODULE", "NAME", "PRODUCT_ID", "QUANTITY", "PRICE", "LID")
    );
    while ($arItems = $dbBasketItems->Fetch())
    {
        $arBasketItems[] = $arItems; //корзина для текущего пользователя на втором сайте
    }

    foreach ($arBasketItems as $key => $arBasketItem) {
        if($arBasketItem['PRODUCT_ID'] == $PRODUCT_ID){
            CSaleBasket::Delete($arBasketItem['ID']);
        }
    }

    unset($dbBasketItems);
    unset($arBasketItems);
    unset($_SESSION['SALE_USER_BASKET_QUANTITY'][SITE_ID][$fuserID]);
    unset($_SESSION['SALE_USER_BASKET_QUANTITY'][$other_site][$fuserID]);
}

function OnOrderAddHandler($ID, $arFields) {
    $userResult = CUser::GetByID($arFields['USER_ID']);
    $user = $userResult->GetNext();

    $phoneNumber =  preg_replace('/\D/', '', ($user['PERSONAL_PHONE'])?:$arFields['USER_DESCRIPTION']);
    if ($phoneNumber)
        $smsResult = \Geniusee\Esputnik\esputnikApi::sendSms("Дякуємо за Ваше замовлення №".$ID.
            ". Ми зателефонуємо найближчим часом!",
            array($phoneNumber));

    $EsputnikBascket = '{\"cart\":[';
    foreach ($arFields['BASKET_ITEMS'] as $key => $item) {
        $res = CIBlockElement::GetByID($item["PRODUCT_ID"]);
        if($ar_res = $res->GetNext())
            $item['DETAIL_PICTURE'] = $ar_res['DETAIL_PICTURE'];

        $EsputnikBascket .= '{\"name\":\"'.$item['NAME'].'\",\"price\":\"'.round($item['PRICE']).'\",\"url\":\"http:\/\/'.$_SERVER['SERVER_NAME'].str_replace("/", "\/", $item['DETAIL_PAGE_URL']).'\",\"imageurl\":\"http:\/\/'.$_SERVER['SERVER_NAME'].str_replace("/", "\/",CFile::GetPath($item['DETAIL_PICTURE'])).'\"}';

        if(count($arFields['BASKET_ITEMS'])>($key+1))
            $EsputnikBascket.=',';
    }

    $EsputnikBascket.=']}';

    $userResult = CUser::GetByID($arFields['USER_ID']);
    $user = $userResult->GetNext();

    $_SESSION['ECOMMERCE_ORDER_ID'][] = $ID;
    $_SESSION['ECOMMERCE_USER_KEY'] = ($_SESSION['ECOMMERCE_USER_KEY'])?:$user['EMAIL'];

    \Geniusee\Esputnik\esputnikApi::createEvent($_SESSION['ECOMMERCE_USER_KEY'], 'orderComplete',
        '[{"name":"email","value":"'.$user['EMAIL'].'"},{"name":"items","value":"'.$EsputnikBascket.'"}]', true);
}

class MyCurledTypeAktsii extends CUserTypeIBlockElement {
    function GetUserTypeDescription() {
        return array(
            "USER_TYPE_ID" => "iblock_element_aktsii",
            "CLASS_NAME" => "MyCurledTypeAktsii",
            "DESCRIPTION" => "Привязка акций из блога",
            "BASE_TYPE" => "element",
        );
    }


    function GetList($arUserField)
    {
        $iblock_id = 46; // инфоблок блога
        $section_id = 1975; // раздел статей в инфоблоке акций
        $rsElement = false;
        if(CModule::IncludeModule('iblock'))
        {
            $obElement = new CIBlockElementEnumCustom;
            $rsElement = $obElement->GetTreeList($iblock_id, $section_id, $arUserField["SETTINGS"]["ACTIVE_FILTER"]);
        }
        return $rsElement;
    }


}

class MyCurledTypeAktsiiUA extends CUserTypeIBlockElement {
    function GetUserTypeDescription() {
        return array(
            "USER_TYPE_ID" => "iblock_element_aktsii_ua",
            "CLASS_NAME" => "MyCurledTypeAktsiiUA",
            "DESCRIPTION" => "Привязка акций из блога UA",
            "BASE_TYPE" => "element",
        );
    }


    function GetList($arUserField)
    {
        $iblock_id = 47; // инфоблок блога
        $section_id = 1980; // раздел статей в инфоблоке акций
        $rsElement = false;
        if(CModule::IncludeModule('iblock'))
        {
            $obElement = new CIBlockElementEnumCustom;
            $rsElement = $obElement->GetTreeList($iblock_id, $section_id, $arUserField["SETTINGS"]["ACTIVE_FILTER"]);
        }
        return $rsElement;
    }


}

class MyCurledTypeArticles extends CUserTypeIBlockElement {
    function GetUserTypeDescription() {
        return array(
            "USER_TYPE_ID" => "iblock_element_articles",
            "CLASS_NAME" => "MyCurledTypeArticles",
            "DESCRIPTION" => "Привязка статей из блога",
            "BASE_TYPE" => "int",
        );
    }


    function GetList($arUserField)
    {
        $iblock_id = 46; // инфоблок блога
        $section_id = 1974; // раздел статей в инфоблоке блога
        $rsElement = false;
        if(CModule::IncludeModule('iblock'))
        {
            $obElement = new CIBlockElementEnumCustom;
            $rsElement = $obElement->GetTreeList($iblock_id, $section_id, $arUserField["SETTINGS"]["ACTIVE_FILTER"]);
        }
        return $rsElement;
    }


}

class MyCurledTypeArticlesUA extends CUserTypeIBlockElement {
    function GetUserTypeDescription() {
        return array(
            "USER_TYPE_ID" => "iblock_element_articles_ua",
            "CLASS_NAME" => "MyCurledTypeArticlesUA",
            "DESCRIPTION" => "Привязка статей из блога UA",
            "BASE_TYPE" => "int",
        );
    }


    function GetList($arUserField)
    {
        $iblock_id = 47; // инфоблок блога
        $section_id = 1979; // раздел статей в инфоблоке блога
        $rsElement = false;
        if(CModule::IncludeModule('iblock'))
        {
            $obElement = new CIBlockElementEnumCustom;
            $rsElement = $obElement->GetTreeList($iblock_id, $section_id, $arUserField["SETTINGS"]["ACTIVE_FILTER"]);
        }
        return $rsElement;
    }


}

class CIBlockElementEnumCustom extends CDBResult
{
    public static function GetTreeList($IBLOCK_ID, $SECTION_ID, $ACTIVE_FILTER="N")
    {
        $rs = false;
        if(CModule::IncludeModule('iblock'))
        {
            $arFilter = Array(
                "IBLOCK_ID"=>$IBLOCK_ID,
                "SECTION_ID"=>$SECTION_ID,
                "ACTIVE"=>"Y"
            );

            $rs = CIBlockElement::GetList(
                array(
                    "NAME" => "ASC",
                    "ID" => "ASC"
                ),
                $arFilter,
                false,
                false,
                array(
                    "ID",
                    "NAME"
                )
            );
            if($rs)
            {
                $rs = new CIBlockElementEnumCustom($rs);
            }
        }
        return $rs;
    }
    public function GetNext($bTextHtmlAuto=true, $use_tilda=true)
    {
        $r = parent::GetNext($bTextHtmlAuto, $use_tilda);
        if($r)
            $r["VALUE"] = $r["NAME"];

        return $r;
    }
}

function getRealIP() {
  $ip = false;
  if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
    $ips = explode (', ', $_SERVER['HTTP_X_FORWARDED_FOR']);
    for ($i = 0; $i < count($ips); $i++) {
      if (!preg_match("/^(10|172\\.16|192\\.168)\\./", $ips[$i])) {
        $ip = $ips[$i];
        break;
      }
    }
  }
  return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}

function getcity(){
  $ip = getRealIP();
  $currCity = $_COOKIE['geoCity'];
  if (!$currCity) {
    $currCity = \Bitrix\Main\Service\GeoIp\Manager::getCityName($ip, "ru");
  }
  return $currCity;
}