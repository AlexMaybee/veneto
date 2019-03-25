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
                foreach ($product_massive['SALE_OFFERS'] as $key => $sale_off2){
                    $resu = $this->workWithProductOffers($sale_off2,$iblock_id,$catalog_id);
                    if(!$resu['result']) $result['message'][$sale_off2['ID']] = $resu['message'];
                    else{
                        //$sale_offers_result['result'][] = $resu['result'];
                        $result['result'][$sale_off2['ID']] = $resu['message'];
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
            'PROPERTY_174' => $productOfferMassive['ID'], //ID на сайте
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
                'MEASURE' => 9, //Ед. измерения, шт.
                'SECTION_ID' => $section_id,
                'ACTIVE' => $productOfferMassive['ACTIVE'], //активность Y/N
//                'DETAIL_TEXT' => $productOfferMassive['PARENT_PRODUCT_DESCR'],//описание вставлено из товара (который здесь является разделом)
//                "DETAIL_TEXT_TYPE" => 'html',
                //'PREVIEW_PICTURE' => $productOfferMassive['DETAIL_PICTURE_PATH'], //картинка маленькая
                //'DETAIL_PICTURE' => $productOfferMassive['DETAIL_PICTURE_PATH'], //картинка большая
                'PRICE' => $productOfferMassive['CATALOG_PRICE_1'],
                "PROPERTY_VALUES" => [
                    '174' => $productOfferMassive['ID'], //ID в БУС
                    '175' => $productOfferMassive['PROPERTY_123_VALUE'], //Длина
                    '176' => $productOfferMassive['PROPERTY_122_VALUE'], //Ширина
                    '177' => $productOfferMassive['PROPERTY_124_VALUE'], //ШифрТМЦ (Шифр)
                    '178' => $productOfferMassive['PROPERTY_172_VALUE'], //Размер спального места
                    '179' => $productOfferMassive['PROPERTY_173_VALUE'], //Выбор патины
                    '180' => $productOfferMassive['PROPERTY_174_VALUE'], //Выбор цвета
                    '181' => $productOfferMassive['PROPERTY_175_VALUE'], //Стиль кровати
                    '182' => $productOfferMassive['PROPERTY_176_VALUE'], //Кровать для
                    '183' => $productOfferMassive['PROPERTY_177_VALUE'], //Габариты ШхГхВ, см
                    '184' => $productOfferMassive['PROPERTY_178_VALUE'], //Выбор ткани
                    '185' => $productOfferMassive['PROPERTY_179_VALUE'], //Размер каркаса, см
                    '186' => $productOfferMassive['PROPERTY_180_VALUE'], //Жесткость
                    '187' => $productOfferMassive['PROPERTY_181_VALUE'], //Количество ламелей
                    '188' => $productOfferMassive['PROPERTY_182_VALUE'], //Ширина ламели, мм
                    '189' => $productOfferMassive['PROPERTY_183_VALUE'], //Расстояние между ламелями
                    '190' => $productOfferMassive['PROPERTY_184_VALUE'], //Высота каркаса
                    '191' => $productOfferMassive['PROPERTY_185_VALUE'], //Размер в разложенном виде, см
                    '192' => $productOfferMassive['PROPERTY_186_VALUE'], //Размер в сложенном виде, см
                    '193' => $productOfferMassive['PROPERTY_187_VALUE'], //Размер матраса
                    '194' => $productOfferMassive['PROPERTY_188_VALUE'], //Основа матраса
                    '195' => $productOfferMassive['PROPERTY_189_VALUE'], //Тип спального места
                    '196' => $productOfferMassive['PROPERTY_190_VALUE'], //Высота матраса
                    '197' => $productOfferMassive['PROPERTY_191_VALUE'], //Жесткость матраса
                    '198' => $productOfferMassive['PROPERTY_192_VALUE'], //Максимальный вес
                    '199' => $productOfferMassive['PROPERTY_193_VALUE'], //Ткань чехла
                    '200' => $productOfferMassive['PROPERTY_194_VALUE'], //Особенности ткани
                    '201' => $productOfferMassive['PROPERTY_195_VALUE'], //Рулонный
                    '202' => $productOfferMassive['PROPERTY_196_VALUE'], //Эффект зима-лето
                    '203' => $productOfferMassive['PROPERTY_197_VALUE'], //Механизм трансформации
                    '204' => $productOfferMassive['PROPERTY_198_VALUE'], //Модель матраса
                    '205' => $productOfferMassive['PROPERTY_199_VALUE'], //Размер сложенный, см
                    '206' => $productOfferMassive['PROPERTY_200_VALUE'], //Размер разложенный, см
                    '207' => $productOfferMassive['PROPERTY_201_VALUE'], //Размер подушки, см
                    '208' => $productOfferMassive['PROPERTY_202_VALUE'], //Чехол
                    '209' => $productOfferMassive['PROPERTY_203_VALUE'], //Наполнитель
                    '210' => $productOfferMassive['PROPERTY_204_VALUE'], //Тип подушки
                    '211' => $productOfferMassive['PROPERTY_205_VALUE'], //Форма подушки
                    '212' => $productOfferMassive['PROPERTY_206_VALUE'], //Высота подушки
                    '213' => $productOfferMassive['PROPERTY_207_VALUE'], //Назначение подушки
                    '214' => $productOfferMassive['PROPERTY_208_VALUE'], //Размер одеяла, см
                    '215' => $productOfferMassive['PROPERTY_209_VALUE'], //Наполнитель одеяла
                    '216' => $productOfferMassive['PROPERTY_210_VALUE'], //Сезон одеяла
                    '217' => $productOfferMassive['PROPERTY_211_VALUE'], //Наполнитель наматрасника
                    '218' => $productOfferMassive['PROPERTY_212_VALUE'], //Высота наматрасника
                    '219' => $productOfferMassive['PROPERTY_213_VALUE'], //Жесткость наматрасника
                    '220' => $productOfferMassive['PROPERTY_214_VALUE'], //Цвет наматрасника
                    '221' => $productOfferMassive['PROPERTY_215_VALUE'], //Состав наматрасника
                    '222' => $productOfferMassive['PROPERTY_250_VALUE'], //Сторона дивана
                    '223' => $productOfferMassive['PROPERTY_618_VALUE'], //Категория кроватей
                    '224' => $productOfferMassive['PROPERTY_502_VALUE'], //Не стандарт
                    '225' => $productOfferMassive['PROPERTY_525_VALUE'], //Размер наматрасника
                    '226' => $productOfferMassive['PROPERTY_589_VALUE'], //Размер подматрасника
                    '227' => $productOfferMassive['PROPERTY_523_VALUE'], //Размер топпера
                    '228' => $productOfferMassive['PROPERTY_226_VALUE'], //РозничнаяЦена
                    '229' => $productOfferMassive['PROPERTY_531_VALUE'], //Чехол одеяла
                ],
            ];


            //Если поле картиинки не пустое, то при создании заполняем картинку
            if(!empty($productOfferMassive['DETAIL_PICTURE_PATH'])){
                $newImgId = $this->getIncomeFile($productOfferMassive['DETAIL_PICTURE_PATH']);
                $createSaleOfferFields['DETAIL_PICTURE'] = $newImgId;
                $createSaleOfferFields['PREVIEW_PICTURE'] = $newImgId;
                // $this->log(array($productData['IMAGE'],$createProdFields));
            }



            $createSaleOfferResID = $this->createNewProductWithProp($createSaleOfferFields); //возвращает ID нов. товара
            if(!$createSaleOfferResID) $result['message'] = $productOfferMassive['ID'].' '.$productOfferMassive['NAME'].' sale offer isn\'t created!';
            else{

                //Если товарное предложение успешно создано, то обновляем то, что не заполнилось при создании (Доступное кол-во, например)
                $result['result'] = $createSaleOfferResID;
                $result['message'] = /*$productOfferMassive['ID'].' '.*/$productOfferMassive['NAME'].' sale offer imported successfully as #'.$createSaleOfferResID.'!';

                //обновляем базовую цену
                $this->setBaseCurrency($createSaleOfferResID,$productOfferMassive['CATALOG_PRICE_1'],'UAH');


                $updateSaleOfferFields = [
                    'PRICE' => $productOfferMassive['CATALOG_PRICE_1'],
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
                'MEASURE' => 9, //Ед. измерения, шт.
                'SECTION_ID' => $section_id,
                'ACTIVE' => $productOfferMassive['ACTIVE'], //активность Y/N
//                'DETAIL_TEXT' => $productOfferMassive['PARENT_PRODUCT_DESCR'],//описание вставлено из товара (который здесь является разделом)
//                "DETAIL_TEXT_TYPE" => 'html',
                'PRICE' => $productOfferMassive['CATALOG_PRICE_1'], //Базовая цена
                "PROPERTY_VALUES" => [
                    '174' => $productOfferMassive['ID'], //ID в БУС
                    '175' => $productOfferMassive['PROPERTY_123_VALUE'], //Длина
                    '176' => $productOfferMassive['PROPERTY_122_VALUE'], //Ширина
                    '177' => $productOfferMassive['PROPERTY_124_VALUE'], //ШифрТМЦ (Шифр)
                    '178' => $productOfferMassive['PROPERTY_172_VALUE'], //Размер спального места
                    '179' => $productOfferMassive['PROPERTY_173_VALUE'], //Выбор патины
                    '180' => $productOfferMassive['PROPERTY_174_VALUE'], //Выбор цвета
                    '181' => $productOfferMassive['PROPERTY_175_VALUE'], //Стиль кровати
                    '182' => $productOfferMassive['PROPERTY_176_VALUE'], //Кровать для
                    '183' => $productOfferMassive['PROPERTY_177_VALUE'], //Габариты ШхГхВ, см
                    '184' => $productOfferMassive['PROPERTY_178_VALUE'], //Выбор ткани
                    '185' => $productOfferMassive['PROPERTY_179_VALUE'], //Размер каркаса, см
                    '186' => $productOfferMassive['PROPERTY_180_VALUE'], //Жесткость
                    '187' => $productOfferMassive['PROPERTY_181_VALUE'], //Количество ламелей
                    '188' => $productOfferMassive['PROPERTY_182_VALUE'], //Ширина ламели, мм
                    '189' => $productOfferMassive['PROPERTY_183_VALUE'], //Расстояние между ламелями
                    '190' => $productOfferMassive['PROPERTY_184_VALUE'], //Высота каркаса
                    '191' => $productOfferMassive['PROPERTY_185_VALUE'], //Размер в разложенном виде, см
                    '192' => $productOfferMassive['PROPERTY_186_VALUE'], //Размер в сложенном виде, см
                    '193' => $productOfferMassive['PROPERTY_187_VALUE'], //Размер матраса
                    '194' => $productOfferMassive['PROPERTY_188_VALUE'], //Основа матраса
                    '195' => $productOfferMassive['PROPERTY_189_VALUE'], //Тип спального места
                    '196' => $productOfferMassive['PROPERTY_190_VALUE'], //Высота матраса
                    '197' => $productOfferMassive['PROPERTY_191_VALUE'], //Жесткость матраса
                    '198' => $productOfferMassive['PROPERTY_192_VALUE'], //Максимальный вес
                    '199' => $productOfferMassive['PROPERTY_193_VALUE'], //Ткань чехла
                    '200' => $productOfferMassive['PROPERTY_194_VALUE'], //Особенности ткани
                    '201' => $productOfferMassive['PROPERTY_195_VALUE'], //Рулонный
                    '202' => $productOfferMassive['PROPERTY_196_VALUE'], //Эффект зима-лето
                    '203' => $productOfferMassive['PROPERTY_197_VALUE'], //Механизм трансформации
                    '204' => $productOfferMassive['PROPERTY_198_VALUE'], //Модель матраса
                    '205' => $productOfferMassive['PROPERTY_199_VALUE'], //Размер сложенный, см
                    '206' => $productOfferMassive['PROPERTY_200_VALUE'], //Размер разложенный, см
                    '207' => $productOfferMassive['PROPERTY_201_VALUE'], //Размер подушки, см
                    '208' => $productOfferMassive['PROPERTY_202_VALUE'], //Чехол
                    '209' => $productOfferMassive['PROPERTY_203_VALUE'], //Наполнитель
                    '210' => $productOfferMassive['PROPERTY_204_VALUE'], //Тип подушки
                    '211' => $productOfferMassive['PROPERTY_205_VALUE'], //Форма подушки
                    '212' => $productOfferMassive['PROPERTY_206_VALUE'], //Высота подушки
                    '213' => $productOfferMassive['PROPERTY_207_VALUE'], //Назначение подушки
                    '214' => $productOfferMassive['PROPERTY_208_VALUE'], //Размер одеяла, см
                    '215' => $productOfferMassive['PROPERTY_209_VALUE'], //Наполнитель одеяла
                    '216' => $productOfferMassive['PROPERTY_210_VALUE'], //Сезон одеяла
                    '217' => $productOfferMassive['PROPERTY_211_VALUE'], //Наполнитель наматрасника
                    '218' => $productOfferMassive['PROPERTY_212_VALUE'], //Высота наматрасника
                    '219' => $productOfferMassive['PROPERTY_213_VALUE'], //Жесткость наматрасника
                    '220' => $productOfferMassive['PROPERTY_214_VALUE'], //Цвет наматрасника
                    '221' => $productOfferMassive['PROPERTY_215_VALUE'], //Состав наматрасника
                    '222' => $productOfferMassive['PROPERTY_250_VALUE'], //Сторона дивана
                    '223' => $productOfferMassive['PROPERTY_618_VALUE'], //Категория кроватей
                    '224' => $productOfferMassive['PROPERTY_502_VALUE'], //Не стандарт
                    '225' => $productOfferMassive['PROPERTY_525_VALUE'], //Размер наматрасника
                    '226' => $productOfferMassive['PROPERTY_589_VALUE'], //Размер подматрасника
                    '227' => $productOfferMassive['PROPERTY_523_VALUE'], //Размер топпера
                    '228' => $productOfferMassive['PROPERTY_226_VALUE'], //РозничнаяЦена
                    '229' => $productOfferMassive['PROPERTY_531_VALUE'], //Чехол одеяла
                ],
            ];
            $updateSaleOfferResID = $this->updateProduct($productOfferSearchRes[0]['ID'],$updateSaleOfferFields); //true/false
            if(!$updateSaleOfferResID) $result['message'] = $productOfferMassive['ID'].' '.$productOfferMassive['NAME'].' sale offer isn\'t updated!';
            else{
                $result['result'] = $updateSaleOfferResID;
                $result['message'] = /*$productOfferMassive['ID'].' '.*/$productOfferMassive['NAME'].' sale offer updated!';

                //обновляем базовую цену
                $this->setBaseCurrency($productOfferSearchRes[0]['ID'],$productOfferMassive['CATALOG_PRICE_1'],'UAH');


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

    //Пробуем записать правильно Валюту Базовой цены (пока именно она не меняется)
    private function setBaseCurrency($productId,$basePrice,$baseCurrency){
        return CPrice::SetBasePrice($productId,$basePrice,$baseCurrency);
    }

    // картинки он сохраняет в свою папку и отдает ее ID
    private function getIncomeFile($foreignPath){
        return $newId = CFile::MakeFileArray($foreignPath);
    }

    //метод логирования данных
    public function logging($flag,$data){

        switch ($flag){
            case 3: //test
                $filename = 'Products_Sale_Offers.log';
                break;
            case 2: //wrong action
                $filename = 'Wrong_Actions.log';
                break;
            case 1: //Error while import
                $filename = 'Import_Error.log';
                break;
            default:
                $filename = 'Default.log';
                break;
        }
        $file = $_SERVER['DOCUMENT_ROOT'].'/local/lib/bus_products_sinkhron/'.$filename;
        file_put_contents($file, print_r(array('date' => date('d.m.Y H:i:s'),$data), true), FILE_APPEND | LOCK_EX);
    }

}