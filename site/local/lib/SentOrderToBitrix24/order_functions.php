<?php
//require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");


use CIBlockElement as Element;
use CIBlockSection as Section;
use Bitrix\Main\Loader,
    Bitrix\Main;
use Bitrix\Sale,
    Bitrix\Main\Application,
    Bitrix\Main\Web\Uri,
    Bitrix\Main\Web\HttpClient,
    Bitrix\Sale\Order,
    Bitrix\Main\Event; //это для получения объкта заказа

Bitrix\Main\Loader::includeModule("sale");
Bitrix\Main\Loader::includeModule("iblock");

class SentOrderToB24{

    const IBLOCK_REGIONS = 49;

    public function test(){
        return 'Test Message of SentOrderToB24 class!';
    }

    //главный метод, который будет запускать вложенные функции
    public function mainMethod($order_id){

        //получаем боолльшой массив со всех ахинеей по заказу
        $order_massive = Bitrix\Sale\Order::load($order_id);

        //получение коллекции по заказу (чуть меньше ахинеи в массиве)
        $propertyCollection = $order_massive->getPropertyCollection();
        $paymentCollection = $order_massive->getPaymentCollection();
        //получение товара
        $basket = $order_massive->getBasket();

        //данные заказа
        $orderData = [];

        $orderData['ORDER_ID'] = $order_massive->getId(); //ID заказа
        $orderData['ORDER_STATUS'] = $order_massive->getField("STATUS_ID"); //Статус заказа

        $orderData['USER_NAME'] = trim($propertyCollection->getItemByOrderPropertyId(27)->getValue()); //имя, оно в свойствах № 27
        $orderData['USER_CITY'] = trim($propertyCollection->getItemByOrderPropertyId(28)->getValue()); //город, оно в свойствах № 28

        //  $orderData['user_phone'] = $propertyCollection->getItemByOrderPropertyId(29)->getValue(); //тел, оно в свойствах № 29
        //  $orderData['user_email'] = $propertyCollection->getItemByOrderPropertyId(30)->getValue(); //email, оно в свойствах № 30
        $orderData['USER_COMMENTS'] = trim($order_massive->getField("USER_DESCRIPTION")); //комментарии пользователя
        $orderData['SHIPPING_METHOD'] = $order_massive->getField("DELIVERY_ID"); //тип службы доставки
        $orderData['SHIPPING_PRICE'] = $order_massive->getField("PRICE_DELIVERY"); //стоимость доставки

        $orderData['PAYMENT_METHOD'] = $order_massive->getField("PAY_SYSTEM_ID"); //тип оплаты

        $orderData['ORDER_PRICE'] = $order_massive->getPrice(); //Сумма заказа
        $orderData['ORDER_IS_PAYED'] = $paymentCollection->isPaid(); //Заказ оплачен? true/false

        $orderData['SITE_LANG'] = $order_massive->getSiteId(); //язык сайта
        $orderData['USER_TYPE'] = $order_massive->getPersonTypeId(); //1 - физ, 2 - юр, 3 - ФОП, 4 - физ B2C
        //$orderData['USER_ID'] = $order_massive->getUserId(); //id пользователя - для запроса

        $orderData['PHONE'] = trim($propertyCollection->getPhone()->getValue());
        $orderData['EMAIL'] = trim($propertyCollection->getUserEmail()->getValue());
        $orderData['SHIPPING_ADDRESS'] = $propertyCollection->getAddress()->getValue();



        //Синхрон полей

        //область
        $regionMassive = self::getCityInfoblockCatalogByValue(['NAME' => $orderData['USER_CITY'],'IBLOCK_ID' => self::IBLOCK_REGIONS]); //город, оно в свойствах № 28
        if($regionMassive)
            $regionName = explode(' ',$regionMassive['SECTION_NAME_REGION'])[0];



        //получаем список полей из битрикса и привязіваем по значению при помощи explode( названия областей должны быть одинаковы и там и там)
        $orderData['USER_REGION_ID'] = '';

        $urlQ = 'crm.deal.userfield.list';
        $b24FieldsMassive =  self::askBitrix24($urlQ,[]);
        if($b24FieldsMassive['result']){
            foreach ($b24FieldsMassive['result'] as $field){
                if($field['FIELD_NAME'] == 'UF_CRM_1433939257'){
                    foreach ($field['LIST'] as $enumValue){
                        if(trim($regionName) == trim($enumValue['VALUE']))
                            $orderData['USER_REGION_ID'] = $enumValue['ID']; //передаем ID области из Б24
                    }
                }
            }
        }




        //Статус заказа
        $order_status = '';
        switch($orderData['ORDER_STATUS']){
            case 'N': //Принят, ожидается оплата
                $order_status = 336;
                break;
            case 'P': //Оплачен, формируется к отправке
                $order_status = 337;
                break;
            case 'F': //Выполнен
                $order_status = 338;
                break;
        }


        //Способ доставки
        $shipment_method = '';
        switch($orderData['SHIPPING_METHOD']){
            case 7: //Курьер
                $shipment_method = 70; //Курьерская доставка
                break;
            case 9: //Самовывоз
                $shipment_method = 72; //Самовывоз
                break;
        }

        //Способ оплаты
        $payment_method = '';
        switch($orderData['PAYMENT_METHOD']){
            case 7: //Внутренний счет
                $payment_method = 76; //Безналичный расчет
                break;
            case 8: //Оплата при доставке
                $payment_method = 270;
                break;
            case 9: //Оплата кредитной картой
                $payment_method = 74;
                break;
            case 10: //Купить в рассрочку
                $payment_method = 272; //Рассрочка от Венето???
                break;
            case 11: //Оплата наличными
                $payment_method = 78; //Наличный расчет
                break;
        }


        //Синхрон полей



        $products = [];
        foreach ($basket as $key => $basketItem) {

            $product = [
                'PRODUCT_ID' => $basketItem->getField('PRODUCT_ID'),
                'NAME' => $basketItem->getField('NAME'),
                "QUANTITY" => $basketItem->getQuantity(),
                "PRICE" => $basketItem->getPrice(),
            ];

            $crm_product_id = $this->checkProductCatalog($product);

            $products[] = [
                'PRODUCT_ID' => ($crm_product_id > 0) ? $crm_product_id : 0,
                'ID_SITE' => $product['PRODUCT_ID'],
                //  'IBLOCK_SECTION_ID' => $basketItem->getField('IBLOCK_SECTION_ID'),
                'NAME' => $basketItem->getField('NAME'),
                "QUANTITY" => $basketItem->getQuantity(),
                "PRICE" => $basketItem->getPrice(),
            ];
        }
        $orderData['products'] = $products;



        //ищем контакт клиента в crm или создаем там же и получаем id
        $contactID = $this->checkContact($orderData);

        //новые поля сделки
        $newDealFields =
            [
            'fields' => [
                "TITLE" => 'Заказ из сайта ['.$orderData['SITE_LANG'].'] #' . $orderData['ORDER_ID'],
                "CONTACT_ID" => $contactID,
                "CATEGORY_ID" => 0, //направление интернет-магазин
                "SOURCE_ID" => 'STORE', // источник - интернет-магазин
                "ASSIGNED_BY_ID" => 4, //Анастасия Алябушева, 11074 - мой ид
                "UF_CRM_1553272714" => $orderData['ORDER_ID'], //ID заказа на сайте
                "UF_CRM_1553273189" => $order_status, //статус заказа на сайте
                "UF_CRM_1424960096" => 68, //Источник заказа - Сайт
                "UF_CRM_1425638753" => $orderData['SHIPPING_ADDRESS'], //Адрес
                "UF_CRM_1425639048" => $orderData['USER_CITY'], //Город
                "UF_CRM_1433939257" => $orderData['USER_REGION_ID'], //Область
                "UF_CRM_1553274251" => $orderData['USER_COMMENTS'], //Клмментарий к заказу от клиента
                "UF_CRM_1424784665" => $shipment_method, //Способ доставки
                "UF_CRM_1427290501" => [$payment_method], //Способ оплаты В МАССИВЕ ОБЯЗАТЕЛЬНО!!!
                "UF_CRM_1433937125" => ($orderData['ORDER_IS_PAYED'] > 0) ? $orderData['ORDER_IS_PAYED'] : 0, //Товар оплачен?
            ],
        ];

        //создаем сделку
        $arResult = $this->createDeal($newDealFields, $products);

        $this->logData([$orderData,$newDealFields,'res' => $arResult]);

        $result = false;
        if($arResult['result'] > 0) return $result = 'Order #'.$orderData['ORDER_ID'].' was sent to CRM!';
        else $result = 'Error with sent order #'.$orderData['ORDER_ID'];
        //return [$orderData,$newDealFields,'res' => $arResult];
        return $result;

    }


    //запросы в crm
    private function askBitrix24($url, $queryData)
    {
        $httpClient = new Bitrix\Main\Web\HttpClient();
        $httpClient->setHeader('Content-Type', 'application/json', true);
        $response = $httpClient->post('https://crm.veneto.ua/rest/11074/5n4ji6gph3d0qg7u/' . $url . '.json', json_encode($queryData));
        return json_decode($response,true);
    }

    //поиск контакта
    private function checkContact($data){
        $check_element = array(
            'filter' => array(
                "PHONE" => $data['PHONE']
            ),
            'select' => array(
                "ID","PHONE","ASSIGNED_BY_ID"
            ),
        );
        $check_contact_url = 'crm.contact.list';
        $check_contact_res = $this->askBitrix24($check_contact_url, $check_element);
        if(!empty($check_contact_res['result'])) {

            if ($check_contact_res['result'][0]['ID'] > 0 ) {
                $res =  $check_contact_res['result'][0]['ID'];
            } else {
                $res = $this->addContact($data);
            }
        } else {
            $res = $this->addContact($data);
        }
        return $res;
    }

    //создание контакта
    private function addContact($data){
        $queryData = array(
            'fields' => array(
                "PHONE" =>   array(array("VALUE" => $data['PHONE'], "VALUE_TYPE" => "WORK")),
                "EMAIL" =>   array(array("VALUE" => $data['EMAIL'], "VALUE_TYPE" => "WORK")),
                "NAME" => $data['USER_NAME'],
                "ASSIGNED_BY_ID" => 4, //Анастасия Алябушева
            ),
        );

        $url = 'crm.contact.add';
        $res = $this->askBitrix24($url, $queryData);
        return $res['result'];
    }


    //поиск товара
    private function checkProductCatalog($productData) {
        $check_element = array(
            'filter' => array(
                'PROPERTY_174' => $productData['PRODUCT_ID'],
            ),
            'select' => array(
                "ID"
            ),
        );
        $check_el_url = 'crm.product.list';
        $check_el_res = $this->askBitrix24($check_el_url, $check_element);

        if(!empty($check_el_res['result'])) {

            if ($check_el_res['result'][0]['ID'] > 0 ) {
                $res =  $check_el_res['result'][0]['ID'];
            } else {
                $res = $this->addProduct($productData);
            }
        } else {
            $res = $this->addProduct($productData);
        }
        return $res;
    }

    //создание товара
    private function addProduct($productData) {
        $queryData = array(
            'fields' => array(
                'NAME' => $productData['NAME'],
                'PROPERTY_174' => $productData['PRODUCT_ID'], //Вставляем ID товара на сайте в поле, по которому будет искать при обмене
                'PRICE' => $productData['PRICE'],
            )

        );
        $url = 'crm.product.add';
        $res = $this->askBitrix24($url, $queryData);
        return $res['result'];
    }

    private function createDeal($orderData, $productData) {
        $url = 'crm.deal.add';
        $res = $this->askBitrix24($url, $orderData);
        if($res['result'] > 0 ) {
            $arResult = $this->addProducttoOrder($res['result'], $productData);
        } else {
            $arResult = false;
        }
        return $arResult;
    }

    private function addProducttoOrder($ID, $productData) {
        $queryData = array(
            'id' => $ID,
            'rows' => $productData
        );
        $url = 'crm.deal.productrows.set';
        $res = $this->askBitrix24($url, $queryData);
        return $res;
    }

    private function logData($data){
            $file = $_SERVER['DOCUMENT_ROOT'].'/local/lib/SentOrderToBitrix24/TestOrderLog.log';
            file_put_contents($file, print_r($data, true), FILE_APPEND | LOCK_EX);
    }


    //09.08.2019 Получение области по имени города из ИБ 49
    private function getCityInfoblockCatalogByValue($filter){
        $result = false;
        $resMassive = CIBlockElement::GetList(["SORT"=>"ASC"],$filter,false,false,[/*'ID','NAME',*/'IBLOCK_SECTION_ID']);
        if($arRes = $resMassive->getNext()) {
            $catalogMAssive = CIBlockSection::GetByID($arRes['IBLOCK_SECTION_ID']);
            if($arCatalog = $catalogMAssive->getNext()) $arRes['SECTION_NAME_REGION'] = $arCatalog['NAME'];
            $result = $arRes;
        }
        return $result;
    }


}



////test!!!
//$obj = new SentOrderToB24;
////echo $obj->test();
//
//
//$order_id = 6748;
//$orderMassive = $obj->mainMethod($order_id);
//echo '<pre>';
//print_r($orderMassive);
//echo '</pre>';
