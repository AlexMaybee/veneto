<?php
//require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Web\HttpClient;

AddEventHandler("crm", "OnBeforeCrmDealUpdate", Array("MyDealEventsClass", "WatchDealStage"));//в массиве только измененные поля


class MyDealEventsClass{

    public function WatchDealStage(&$arFields){

        if($arFields['ID'] > 0){
            //достаем данные сделки до изменения
            $dealFilter = ['ID' => $arFields['ID']];
            $dealSelect = ['ID','TITLE','STAGE_ID','ASSIGNED_BY_ID',
                'CATEGORY_ID', //это нужно, чтобы срабатывало только в направлении Интернет-магазина
                'UF_CRM_1553272714', //id заказа на сайте
                'UF_CRM_1553273189', //статус заказа на сайте
            ];
            $dealResult = self::getDealDataByFilterForSiteSynchron($dealFilter,$dealSelect);
            if($dealResult){
                if($dealResult[0]['CATEGORY_ID'] === '0'){ //CATEGORY_ID === string!!! //Если направление === "интернет-магазин"
                 //   $arFields['TEST_CATEGORY'] = 'CATEGORY_ID === 0!!!';

                    if($dealResult[0]['UF_CRM_1553272714'] > 0){ //если заполнено поле с ID заказа на сайте
                    //    $arFields['TEST_SITE_ID'] = 'SITE_ID больше 0!';


                        //конверт стадии сделки в статус заказа сайта
                        $orderStatus = self::switchDealStageToOrderStatus($arFields['STAGE_ID']);

                        //проверяем стадии, чтобы была смена на WON с любой другой
                        if($arFields['STAGE_ID'] === 'WON' && ($arFields['STAGE_ID'] != $dealResult[0]['STAGE_ID'])){
                            //делаем запрос на сайт и меняем статус

                            $siteFields = [
                                'order_id' => $dealResult[0]['UF_CRM_1553272714'],
                                'status_id' => $orderStatus,
                            ];

                            $sendRes = json_decode(json_encode(self::sentDataToCRM($siteFields)), true);

                            if($sendRes['result'] > 0) $arFields['UF_CRM_1553273189'] = 338; //Статус заказа "Выполнен"

                            $arFields['BUS_UPDATE_RESULT'] = $sendRes;

                           // $arFields['TEST_STAGE_ID'] = 'WOOOON!';
                        }
                        else{
                            $arFields['UF_CRM_1553273189'] = 336; //Иначе статус заказа всегда "Принят, ожидается оплата"
                        }
                    }

                }

            }

            self::logData(['updated_fields' => $arFields,'already_data' => $dealResult]);

        }



    }


    private function logData($arFields){
        $file = $_SERVER['DOCUMENT_ROOT'].'/local/lib/bus_products_sinkhron/TestLog.log';
        file_put_contents($file, print_r($arFields, true), FILE_APPEND | LOCK_EX);
    }

    private function getDealDataByFilterForSiteSynchron($filter,$select){
        $db_list = CCrmDeal::GetListEx(array('ID' => 'DESC'), $filter, false, false, $select, array()); //получение пользовательских полей сделки по ID

        $result = array();
        while ($dealsList = $db_list->Fetch()) {
            $result[] = $dealsList;
        }

        if($result) return $result;
        return false;
    }

    //отправка статуса на сайт, подключение выше
    private function sentDataToCRM($queryData){
        $httpClient = new HttpClient();
        $httpClient->setHeader('Content-Type', 'application/json', true);
        $result = $httpClient->post('https://veneto.ua/local/lib/SentOrderToBitrix24/update_status.php', json_encode($queryData));
        return json_decode($result);
    }

    //конверт стадии сделки в статус заказа
    private function switchDealStageToOrderStatus($dealStageId){
        $orderStatus = 'N';
        switch($dealStageId){
            case 'WON':
                $orderStatus = 'F';
                break;
            case 'LOSE': //добавлен просто так, если вдруг придется проигрышную сделку прикручивать.
                $orderStatus = 'NONE';
                break;

        }
        return $orderStatus;
    }

}
