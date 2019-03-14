<?php

class Bus_Products_Import{

    public function testFunction(){
        echo 'Test function from Bus_Products_Import class';
    }

    //это главный метод, который в цикле будет запускать массив товаров + искать/создавать разделы (названия товаров) --
    // в которых будут лежать товарные предложения
    public function mainSinkhronMethod($data){
        $iblock_id = 20;
        $result = [
            'result' => false,
            'message' => false,
        ];
        foreach ($data as $product_massive){

            //ищем раздел (папку) по ID товара из Бус
            $catalogCheckFilter = [
                'IBLOCK_ID' => $iblock_id, //обязательно!
                'UF_BUS_PRODUCT_ID' => $product_massive['ID'], //это ID товара
            ];
            $catalogCheckSelect = ['ID','NAME','DESCRIPTION','DETAIL_PICTURE'];
            $catalorResultMassive = $this->getSectionFromProductCatalog($catalogCheckFilter,$catalogCheckSelect);

            //если раздел/папка НЕ найдены, создаем новый сохранияем в него товары
            if(!$catalorResultMassive ){

                //создаем раздел
                $newCatalogMassive = [
                    'NAME' => $product_massive['NAME'],
                    'DESCRIPTION' => 'Раздел для товара '.$product_massive['NAME'].', в котором находятся все его товарные предложения',
                    'IBLOCK_ID' => $iblock_id, //обязательно!
                    'UF_BUS_PRODUCT_ID' => $product_massive['ID'], //это ID товара
                ];
                $newCatalogId = $this->addSectionToProductCatalog($newCatalogMassive);

                //если не создался каталог, выдаем ошибку, иначе продолжаем сохранение товарных предложений
                if(!$newCatalogId['id']) $result['message'] = $newCatalogId['error'];
                else{
                   // $result['result'][] = ['new_catalog_id' => $newCatalogId['id']];

                    //в цикле проверяем все товарные предложения на существование, если находим, то обновляем
                    foreach ($product_massive['SALE_OFFERS'] as $key => $sale_off){
                        $resu = $this->workWithProductOffers($sale_off,$iblock_id,$newCatalogId['id']);
                        if(!$resu['result']) $result['message'][$sale_off['ID']] = $resu['message'];
                        else{
                            //$sale_offers_result['result'][] = $resu['result'];
                            $result['result'][$sale_off['ID']] = $resu['message'];
                        }
                    }
                }


            }
            //если раздел/папка найдены, то записываем его ID - Здесь будет больше действий
            else{
                $catalog_id = $catalorResultMassive[0]['ID'];

                //в цикле проверяем все товарные предложения на существование, если находим, то обновляем
                //в цикле проверяем все товарные предложения на существование, если находим, то обновляем
                foreach ($product_massive['SALE_OFFERS'] as $key => $sale_off){
                    $resu = $this->workWithProductOffers($sale_off,$iblock_id,$catalog_id);
                    if(!$resu['result']) $result['message'][$sale_off['ID']] = $resu['message'];
                    else{
                        //$sale_offers_result['result'][] = $resu['result'];
                        $result['result'][$sale_off['ID']] = $resu['message'];
                    }
                }

                //$result['result'][] = [$catalog_id, $catalorResultMassive[0]['NAME']];
            }


        }

        //$result['result'] = $data;

        return $result;
    }


    //метод для поиска/обновления и создания товаров
    public function workWithProductOffers($productOfferMassive,$iblock_id,$section_id){
        $result = [
            'result' => false,
            'message' => false,
        ];

        //поиск товарного предложения в базе
        $productOfferFilter = [
            "IBLOCK_ID"=> $iblock_id,
            'PROPERTY_174' => $productOfferMassive['ID'],
        ];
        $productOfferSelect = [
            /* 'ID','NAME',"IBLOCK_SECTION_ID",'DETAIL_TEXT','DETAIL_PICTURE','PURCHASING_PRICE'*/
        ];
        $productOfferSearchRes = $this->searchProductInBase($productOfferFilter,$productOfferSelect);

        //если товарное предложение НЕ найдено, то создаем новое
        if(!$productOfferSearchRes){
            //$result['message'] = $productOfferMassive['ID'].' '.$productOfferMassive['NAME'].' sale product NOT FOUND!';

            $createSaleOfferFields = [
                'NAME' => $productOfferMassive['NAME'],
                //'MEASURE' => 5, //Ед. измерения, шт.
                'SECTION_ID' => $section_id,
                "PROPERTY_VALUES" => [
                    '174' => $productOfferMassive['ID'], //Укр Название
                ],
            ];
            $createSaleOfferResID = $this->createNewProductWithProp($createSaleOfferFields); //возвращает ID нов. товара
            if(!$createSaleOfferResID) $result['message'] = $productOfferMassive['ID'].' '.$productOfferMassive['NAME'].' sale offer isn\'t created!';
            else{

                //Если товарное предложение успешно создано, то обновляем то, что не заполнилось при создании (Доступное кол-во, например)
                $result['result'] = $createSaleOfferResID;
                $result['message'] = /*$productOfferMassive['ID'].' '.*/$productOfferMassive['NAME'].' sale offer imported successfully as #'.$createSaleOfferResID.'!';

                $updateSaleOfferFields = [
                  'QUANTITY' => $productOfferMassive['CATALOG_QUANTITY'], //доступное количество
                ];
                $updateSaleOfferRes = $this->updateProductFields($createSaleOfferResID,$updateSaleOfferFields);
                if(!$updateSaleOfferRes) $result['message'] .= ' Quantity isn\'t updated!';
                else{
                    $result['message'] .= ' Quantity updated!';
                }

            }
        }
        //если товарное предложение найдено, то обновляем старое
        else{

           // $result['message'] = $productOfferMassive['ID'].' '.$productOfferMassive['NAME'].' sale product EXISTS!';

            $updateSaleOfferFields = [
                'NAME' => $productOfferMassive['NAME'],
                //'MEASURE' => 5, //Ед. измерения, шт.
                'SECTION_ID' => $section_id,
                "PROPERTY_VALUES" => [
                    '174' => $productOfferMassive['ID'], //ID в БУС
                ],
            ];
            $updateSaleOfferResID = $this->updateProduct($productOfferSearchRes[0]['ID'],$updateSaleOfferFields); //true/false
            if(!$updateSaleOfferResID) $result['message'] = $productOfferMassive['ID'].' '.$productOfferMassive['NAME'].' sale offer isn\'t updated!';
            else{
                $result['result'] = $updateSaleOfferResID;
                $result['message'] = /*$productOfferMassive['ID'].' '.*/$productOfferMassive['NAME'].' sale offer updated!';

                //Если обновлено товарное предложение, то обновляем и его поля
                $updateSaleOfferFields = [
                    'QUANTITY' => $productOfferMassive['CATALOG_QUANTITY'], //доступное количество
                ];
                $updateSaleOfferRes = $this->updateProductFields($productOfferSearchRes[0]['ID'],$updateSaleOfferFields);
                if(!$updateSaleOfferRes) $result['message'] .= ' Quantity isn\'t updated!';
                else{
                    $result['message'] .= ' Quantity updated!';
                }
            }
          //  $result['result'] = $productOfferSearchRes;
        }

        return $result;
    }


    //для поиска раздела (папки) товара, в котором лежат/будут торговые предложения
    public function getSectionFromProductCatalog($filter,$select){
        $result = [];
        $ar_result  = CIBlockSection::GetList(array(),$filter,false,$select,false);
        while($res = $ar_result->GetNext())
        {
            $result[] = $res;
        }

        if(count($result) > 0) return $result;
        else return false;
    }

    //для создания папки товарных каталогов = id и name товара (или как-то так)
    //@возврат Id нового раздела или ошибку
    public function addSectionToProductCatalog($fields){
        $result = [
            'id' => false,
            'error' => false,
        ];
        $bs = new CIBlockSection;
        $res = $bs->Add($fields);

        if($res) $result['id'] = $res;
        else $result['error'] = $bs->LAST_ERROR;

        return $result;
    }

    //поиск товара в базе по ID
    private function searchProductInBase($arFilter,$arSelect){
        //сортировка по ID, новые сверху (на всяк случай)
        $res = CIBlockElement::GetList(Array('ID'=>'DESC'),$arFilter, false, false, $arSelect);
        $prods = array();
        while($ob = $res->GetNext()){
            $prods[] = $ob;
        }
        if($prods) return $prods;
        else return false;
    }

    //метод добаления нового товара с ценой и свойствами PROPERTY_, возвращает ID созданного товара
    private function createNewProductWithProp($newProdFields){
        return $newProduct = CCrmProduct::Add($newProdFields);
    }

    //обновление полей товара
    private function updateProduct($prodId,$fields){
        return $res = CCrmProduct::Update($prodId, $fields);
    }

    //обновление полей товара // true/false
    private function updateProductFields($prodId,$fields){
        return $res = CCatalogProduct::Update($prodId, $fields);
    }

    //метод логирования данных
    public function logging($data){
        $file = $_SERVER['DOCUMENT_ROOT'].'/local/lib/bus_products_sinkhron/Products_Sale_Offers.log';
        file_put_contents($file, print_r(array('date' => date('d.m.Y H:i:s'),$data), true), FILE_APPEND | LOCK_EX);
    }

}