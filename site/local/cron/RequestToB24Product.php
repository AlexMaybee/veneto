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
            //объединяем таким образом уже имеющиеся данные с только полученными (ниже то же самое), повторяющиеся поля перезаписываются автоматом
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
        'CATALOG_GROUP_1','CATALOG_QUANTITY', //внезапно эти поля перестало отдавать по умлдчанию, пришлось запросить. CATALOG_GROUP_1 - содержит базовую цену (торговый каталог - цены)
        'PROPERTY_250','PROPERTY_618','PROPERTY_502','PROPERTY_525','PROPERTY_589','PROPERTY_523','PROPERTY_226','PROPERTY_251',
        'PROPERTY_460','PROPERTY_531'
    );
    $prod_sale_offers_res4 = getSaleOffersByFilter($prodData['ID'],$offerFields_part4);

    foreach ($prod_sale_offers_res4 as $val1){
        foreach ($val1 as $val2){
            $val2['DETAIL_PICTURE_PATH'] = 'https://veneto.ua'.CFile::GetPath($val2["DETAIL_PICTURE"]);
          //  $val2['PARENT_PRODUCT_DESCR'] = $prodData['PROPERTY_399_VALUE']['TEXT']; //описание у них находится в самом товаре в Property_399, поэтому забираю так
            unset($val2["DETAIL_PICTURE"]); //удаляем поле, оно не нужно
            $products[$number]['SALE_OFFERS'][$val2['ID']] = array_merge($products[$number]['SALE_OFFERS'][$val2['ID']], $val2);
        }
    }

}


/*echo '<pre>';
print_r($products);
echo '</pre>';*/


//$realTestMassive = [
//    [
//        'ID' => 71246,
//        'NAME' => 'Ортопедичний диван «Кларк»',
//        'SALE_OFFERS' => [
//            67072 => [
//                'ID' => 67072,
//                'NAME' => 'Ортопедический прямой диван «Кларк», Magelan Tourqoise',
//                'PROPERTY_122_VALUE' => '1800', //Ширина
//                'PROPERTY_123_VALUE' => '2000', //Длина
//                'PROPERTY_124_VALUE' => '0950123123', //ШифрТМЦ (Шифр)
//                'PROPERTY_172_VALUE' => '140х200', //Размер спального места
//                'PROPERTY_173_VALUE' => 'Можна выбрать', //Выбор патины
//                'PROPERTY_174_VALUE' => 'Magelan Tourqoise', //Выбор цвета
//                'PROPERTY_175_VALUE' => 'Модерн', //Стиль кровати
//                'PROPERTY_176_VALUE' => 'Взрослых', //Кровать для
//                'PROPERTY_177_VALUE' => '1800x2000x500', //Габариты ШхГхВ, см
//                'PROPERTY_178_VALUE' => 'Seattle', //Выбор ткани
//                'PROPERTY_179_VALUE' => '120х190', //Размер каркаса, см
//                'PROPERTY_180_VALUE' => 'Medium', //Жесткость
//                'CATALOG_PRICE_1' => 29028.30, //Базовая цена (Тип цены "Цена") в торговый каталог - цены
//                'CATALOG_QUANTITY' => 25, //Доступное количество в торговый каталог - параметры
//                'PROPERTY_181_VALUE' => '26+8 дополнительных', //Количество ламелей
//                'PROPERTY_182_VALUE' => 68, //Ширина ламели, мм
//                'PROPERTY_183_VALUE' => 'Расстояние между ламелями lalala', //Расстояние между ламелями
//                'PROPERTY_184_VALUE' => 'Высота каркаса nanana', //Высота каркаса
//                'PROPERTY_185_VALUE' => '80х190', //Размер в разложенном виде, см
//                'PROPERTY_186_VALUE' => '80х30х108', //Размер в сложенном виде, см
//                'PROPERTY_187_VALUE' => '160x190', //Размер матраса
//                'PROPERTY_188_VALUE' => 'Беспружинный', //Основа матраса
//                'PROPERTY_189_VALUE' => 'Двуспальное', //Тип спального места
//                'PROPERTY_190_VALUE' => '24 см', //Высота матраса
//                'PROPERTY_191_VALUE' => 'Hard / Medium+', //Жесткость матраса
//                'PROPERTY_192_VALUE' => 'Слонячий', //Максимальный вес
//                'PROPERTY_193_VALUE' => 'Cтеганый стретчевый чехол', //Ткань чехла
//                'PROPERTY_194_VALUE' => 'Антибактериальная', //Особенности ткани
//                'PROPERTY_195_VALUE' => 'Так', //Рулонный
//                'PROPERTY_196_VALUE' => 'Да', //Эффект зима-лето
//                'PROPERTY_197_VALUE' => 'Клик-кляк', //Механизм трансформации
//                'PROPERTY_198_VALUE' => 'Классический пуговичный', //Модель матраса
//                'PROPERTY_199_VALUE' => '2000x1500 см', //Размер сложенный, см
//                'PROPERTY_200_VALUE' => '2000x3500 см', //Размер разложенный, см
//                'PROPERTY_201_VALUE' => '74х42х12', //Размер подушки, см
//                'PROPERTY_202_VALUE' => 'Biorytmic Sleep, съемный с молнией', //Чехол
//                'PROPERTY_203_VALUE' => 'Натуральный латекс', //Наполнитель
//                'PROPERTY_204_VALUE' => 'Подушки для диванов', //Тип подушки
//                'PROPERTY_205_VALUE' => 'Прямоугольная', //Форма подушки
//                'PROPERTY_206_VALUE' => 'Низкая', //Высота подушки
//                'PROPERTY_207_VALUE' => 'Никакое', //Назначение подушки
//                'PROPERTY_208_VALUE' => '172х205', //Размер одеяла, см
//                'PROPERTY_209_VALUE' => 'Шерсть', //Наполнитель одеяла
//                'PROPERTY_210_VALUE' => '4 сезона', //Сезон одеяла
//                'PROPERTY_211_VALUE' => 'Memory Flex', //Наполнитель наматрасника
//                'PROPERTY_212_VALUE' => '6', //Высота наматрасника
//                'PROPERTY_213_VALUE' => 'Soft', //Жесткость наматрасника
//                'PROPERTY_214_VALUE' => 'ЧОрный', //Цвет наматрасника
//                'PROPERTY_215_VALUE' => 'Ничо так! Даже ошень!', //Состав наматрасника
//                'PROPERTY_250_VALUE' => 'Правый угол', //Сторона дивана
//                'PROPERTY_618_VALUE' => 'Односпальные кровати', //Категория кроватей
//                'PROPERTY_502_VALUE' => 'Нестандарт', //Не стандарт checkbox
//                'PROPERTY_525_VALUE' => '140x190', //Размер наматрасника
//                'PROPERTY_589_VALUE' => '150х200', //Размер подматрасника
//                'PROPERTY_523_VALUE' => '160x190', //Размер топпера
//                'PROPERTY_226_VALUE' => '22328.3333', //РозничнаяЦена (РозничнаяЦена)
//                'PROPERTY_531_VALUE' => '100% хлопок', //Чехол одеяла
//                'DETAIL_PICTURE_PATH' => 'https://veneto.ua/upload/iblock/c98/c98fda0ab300360c12de43f9edd6a02f.jpg', //путь к картинке
//            ],
//            71247 => [
//                'ID' => 71247,
//                'NAME' => 'Ортопедический прямой диван «Кларк», Queens Chocolate',
//                'PROPERTY_122_VALUE' => '800', //Ширина
//                'PROPERTY_123_VALUE' => '4000', //Длина
//                'PROPERTY_124_VALUE' => '095015637', //ШифрТМЦ (Шифр)
//                'PROPERTY_172_VALUE' => '140х200', //Размер спального места
//                'PROPERTY_173_VALUE' => 'Можна выбрать', //Выбор патины
//                'PROPERTY_174_VALUE' => 'Magelan Tourqoise', //Выбор цвета
//                'PROPERTY_175_VALUE' => 'Модерн', //Стиль кровати
//                'PROPERTY_176_VALUE' => 'Взрослых', //Кровать для
//                'PROPERTY_177_VALUE' => '1800x2000x500', //Габариты ШхГхВ, см
//                'PROPERTY_178_VALUE' => 'Seattle', //Выбор ткани
//                'PROPERTY_179_VALUE' => '120х190', //Размер каркаса, см
//                'PROPERTY_180_VALUE' => 'Medium', //Жесткость
//                'CATALOG_PRICE_1' => 29028.30, //Базовая цена (Тип цены "Цена") в торговый каталог - цены
//                'CATALOG_QUANTITY' => 25, //Доступное количество в торговый каталог - параметры
//                'PROPERTY_181_VALUE' => '26+8 дополнительных', //Количество ламелей
//                'PROPERTY_182_VALUE' => 68, //Ширина ламели, мм
//                'PROPERTY_183_VALUE' => 'Расстояние между ламелями lalala', //Расстояние между ламелями
//                'PROPERTY_184_VALUE' => 'Высота каркаса nanana', //Высота каркаса
//                'PROPERTY_185_VALUE' => '80х190', //Размер в разложенном виде, см
//                'PROPERTY_186_VALUE' => '80х30х108', //Размер в сложенном виде, см
//                'PROPERTY_187_VALUE' => '160x190', //Размер матраса
//                'PROPERTY_188_VALUE' => 'Беспружинный', //Основа матраса
//                'PROPERTY_189_VALUE' => 'Двуспальное', //Тип спального места
//                'PROPERTY_190_VALUE' => '24 см', //Высота матраса
//                'PROPERTY_191_VALUE' => 'Hard / Medium+', //Жесткость матраса
//                'PROPERTY_192_VALUE' => 'Слонячий', //Максимальный вес
//                'PROPERTY_193_VALUE' => 'Cтеганый стретчевый чехол', //Ткань чехла
//                'PROPERTY_194_VALUE' => 'Антибактериальная', //Особенности ткани
//                'PROPERTY_195_VALUE' => 'Так', //Рулонный
//                'PROPERTY_196_VALUE' => 'Да', //Эффект зима-лето
//                'PROPERTY_197_VALUE' => 'Клик-кляк', //Механизм трансформации
//                'PROPERTY_198_VALUE' => 'Классический пуговичный', //Модель матраса
//                'PROPERTY_199_VALUE' => '2000x1500 см', //Размер сложенный, см
//                'PROPERTY_200_VALUE' => '2000x3500 см', //Размер разложенный, см
//                'PROPERTY_201_VALUE' => '74х42х12', //Размер подушки, см
//                'PROPERTY_202_VALUE' => 'Biorytmic Sleep, съемный с молнией', //Чехол
//                'PROPERTY_203_VALUE' => 'Натуральный латекс', //Наполнитель
//                'PROPERTY_204_VALUE' => 'Подушки для диванов', //Тип подушки
//                'PROPERTY_205_VALUE' => 'Прямоугольная', //Форма подушки
//                'PROPERTY_206_VALUE' => 'Низкая', //Высота подушки
//                'PROPERTY_207_VALUE' => 'Никакое', //Назначение подушки
//                'PROPERTY_208_VALUE' => '172х205', //Размер одеяла, см
//                'PROPERTY_209_VALUE' => 'Шерсть', //Наполнитель одеяла
//                'PROPERTY_210_VALUE' => '4 сезона', //Сезон одеяла
//                'PROPERTY_211_VALUE' => 'Memory Flex', //Наполнитель наматрасника
//                'PROPERTY_212_VALUE' => '6', //Высота наматрасника
//                'PROPERTY_213_VALUE' => 'Soft', //Жесткость наматрасника
//                'PROPERTY_214_VALUE' => 'Жоско! Ошень!', //Цвет наматрасника
//                'PROPERTY_215_VALUE' => 'Ничо так! Даже ошень!', //Состав наматрасника
//                'PROPERTY_250_VALUE' => 'Правый угол', //Сторона дивана
//                'PROPERTY_618_VALUE' => 'Односпальные кровати', //Категория кроватей
//                'PROPERTY_502_VALUE' => 'Нестандарт', //Не стандарт checkbox
//                'PROPERTY_525_VALUE' => '140x190', //Размер наматрасника
//                'PROPERTY_589_VALUE' => '150х200', //Размер подматрасника
//                'PROPERTY_523_VALUE' => '160x190', //Размер топпера
//                'PROPERTY_226_VALUE' => '22328.3333', //РозничнаяЦена (РозничнаяЦена)
//                'PROPERTY_531_VALUE' => '100% хлопок', //Чехол одеяла
//                'DETAIL_PICTURE_PATH' => 'https://veneto.ua/upload/iblock/c98/c98fda0ab300360c12de43f9edd6a02f.jpg', //путь к картинке
//            ],
//        ],
//    ],
//];



$sent_res = sentDataToCRM(['action' => 'import_products_and_sale_offers', 'products' => $products]);
//$sent_res = sentDataToCRM(['action' => 'import_products_and_sale_offers', 'products' => $realTestMassive]);

print_r($sent_res);
loggingResult($sent_res); //логирование результата

//echo '<pre>';
//print_r($prod_predlojenie);



//список товаров (имя + id)
function getProducts($iblock){
    $products = [];
    $arSelect = Array("ID",'NAME'/*,'PROPERTY_399'*/); // Уменьшил количество полей; PROPERTY_399 - Описание товара на русском
    $arFilter = Array("IBLOCK_ID"=> $iblock, "ACTIVE"=>"Y"/*,"ACTIVE_DATE"=>"Y","SECTION_ID"=> array(17, 18, 40)*/);
    $res = CIBlockElement::GetList(Array(), $arFilter, false, false, $arSelect);
    while($ob = $res->GetNextElement())
    {
        $arFields = $ob->GetFields();
        $products[] = $arFields;
    }
    return $products;
}

//получение товарных предложений
function getSaleOffersByFilter($prodID,$offerFields){
    $iblockId = 9;
    $offersFilter = array('ACTIVE' => 'Y'/*, 'ACTIVE_DATE' => 'Y', '>CATALOG_PRICE_1' => 0*/);
    $propertyFields = array('ID', 'PROPERTY_TYPE', 'MULTIPLE', 'USER_TYPE');
    $res = CCatalogSku::getOffersList(
        $prodID,$iblockId, $offersFilter, $offerFields, array(), array('USE_PROPERTY_ID' => 'Y', 'PROPERTY_FIELDS' => $propertyFields));
    return $res;
}

//отправка данных в crm, подключение выше
function sentDataToCRM($queryData){
    $httpClient = new HttpClient();
    $httpClient->setHeader('Content-Type', 'application/json', true);
    $result = $httpClient->post('https://crm.veneto.ua/local/lib/bus_products_sinkhron/index.php', json_encode($queryData));
    return json_decode($result);
}

function loggingResult($data){

    $filename = 'Products_Export_Result_Log.log';

    $file = $_SERVER['DOCUMENT_ROOT'].'/local/cron/'.$filename;
    file_put_contents($file, print_r(['date' => date('d.m.Y H:i:s'),$data], true), FILE_APPEND | LOCK_EX);
}