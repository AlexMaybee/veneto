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
               // 'UF_CRM_1553693726', //причина отмены заказа
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

                            //делаем запрос на сайт и меняем статус на WON
                            $siteFields = [
                                'order_id' => $dealResult[0]['UF_CRM_1553272714'],
                                'status_id' => $orderStatus['site_order_status'],
                            ];

                            $sendRes = json_decode(json_encode(self::sentDataToCRM($siteFields)), true);

                            if($sendRes['result'] != false) $arFields['UF_CRM_1553273189'] = $orderStatus['crm_order_status']; //Статус заказа "Выполнен"
                            else  self::logData(['date' => date('d.m.Y H:i:s'),'result' => $sendRes]);

                            //$arFields['BUS_UPDATE_RESULT'] = $sendRes;

                        }
                        //если смена на стадию проигрыша - пока отменил
                        elseif($arFields['STAGE_ID'] === 'LOSE' && ($arFields['STAGE_ID'] != $dealResult[0]['STAGE_ID'])){
                            //делаем запрос на сайт и меняем статус на LOSE
                            $siteFields = [
                                'order_id' => $dealResult[0]['UF_CRM_1553272714'],
                                'status_id' => $orderStatus['site_order_status'],
                                'reasons_text' => $arFields['UF_CRM_1553693726'],
                            ];
                            $sendRes = json_decode(json_encode(self::sentDataToCRM($siteFields)), true);
                            if($sendRes['result'] != false) $arFields['UF_CRM_1553273189'] = $orderStatus['crm_order_status']; //Статус заказа "Выполнен"

                            $arFields['BUS_UPDATE_RESULT'] = $sendRes;

                           // self::logData(['updated_fields' => $arFields,'already_data' => $dealResult]);

                        }
                        else{

                            //делаем запрос на сайт и меняем статус на WON
                            $siteFields = [
                                'order_id' => $dealResult[0]['UF_CRM_1553272714'],
                                'status_id' => $orderStatus['site_order_status'],
                            ];

                            $sendRes = json_decode(json_encode(self::sentDataToCRM($siteFields)), true);

                            if($sendRes['result'] != false) $arFields['UF_CRM_1553273189'] = $orderStatus['crm_order_status']; //Статус заказа "Выполнен"
                            else  self::logData(['date' => date('d.m.Y H:i:s'),'result' => $sendRes]);

                          //  $arFields['UF_CRM_1553273189'] = $orderStatus['crm_order_status']; //Иначе статус заказа всегда "Принят, ожидается оплата"
                        }
                    }

                }

            }

            //self::logData(['updated_fields' => $arFields,'already_data' => $dealResult]);

        }



    }


    private function logData($arFields){
        $file = $_SERVER['DOCUMENT_ROOT'].'/local/lib/bus_products_sinkhron/errorLog.log';
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
        $siteOrderStatus = 'N';
        $crmOrderStatus = 336; //Принят, ожидается оплата
        switch($dealStageId){
            case 'WON':
                $siteOrderStatus = 'F';
                $crmOrderStatus = 338; //Выполнен
                break;
            case 'LOSE': //добавлен просто так, если вдруг придется проигрышную сделку прикручивать.
                $siteOrderStatus = 'CANCEL';
                $crmOrderStatus = 339; //Отменен
                break;
            default:
                $siteOrderStatus = 'N';
                $crmOrderStatus = 336; //Принят, ожидается оплата
                break;

        }
        return ['site_order_status' => $siteOrderStatus, 'crm_order_status' => $crmOrderStatus];
    }

}
