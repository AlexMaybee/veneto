<?php

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
header('Content-type: application/json');

class TheBestClass{

    public function getProductData($ID){
            $IBLOCK_ID = 20;
            $arSelect = Array('ID','NAME','CATALOG_QUANTITY'); // При запросе CATALOG_QUANTITY выдает весь массив с каталога в виде CATALOG_*
            $arFilter = Array("IBLOCK_ID"=>$IBLOCK_ID, 'ID' => $ID);
            $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
            while($ob = $res->GetNextElement())
            {
                $arFields = $ob->GetFields();
                $data = array('ID' => $arFields['ID'], 'NAME' => $arFields['NAME'],'STORE_QUANTITY' => $arFields['CATALOG_QUANTITY']);

            }
            $this->sentData($data);
    }

    private function sentData($data){
        echo json_encode($data);
    }
    
}


$obj = new TheBestClass();
if($_POST['action'] == 'GIVE_ME_PRODUCT_STORE_DATA') $obj->getProductData($_POST['productId']);