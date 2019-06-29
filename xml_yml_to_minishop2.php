<?php

// msImportGods || В простонародье импорт богов или MiniShop2 импорт товаров из XML и YML
// Подключение API
// define('MODX_API_MODE', true);
// require_once dirname(dirname(dirname(dirname(__FILE__)))) . '/config/config.inc.php';
// require_once MODX_BASE_PATH . 'index.php';
// require 'index.php';

// Включаем обработку ошибок
$modx->setLogTarget(XPDO_CLI_MODE ? 'ECHO' : 'HTML');
$modx->setLogLevel($is_debug ? modX::LOG_LEVEL_INFO : modX::LOG_LEVEL_INFO);
$modx->getService('error','error.modError');
$modx->lexicon->load('minishop2:default');
$modx->lexicon->load('minishop2:manager');

// Параметры
$file = 'catalog.xml'; // Путь до файла
$arrParams = []; // Куда что
$templateOffer = 2; // Шаблон товара
$templateCategory = 3; // Шаблон категории
$templateSubCategory = 5; // Шаблон вложенной категории
$msProductCategory = 30; // id категории, грубо говоря parent куда запихивать товары в магазине
$msProductImagesPath = 'images/goods/big/'; // Путь от корня сайта до папки с изображениями товара
$msVendorImagePath = 'images/goods/brands/'; // Путь от корня сайта до папки с изображениями производителя
$needCategory = '3488325110'; // Категория из файла для парсинга
// Параметры х

// Разбор файла
$data = simplexml_load_file($file);
// print_r($data);
// Разбор файла х

// Информация о магазине
// Информация о магазине х

// Перебор категорий
$arrNeedCategories = []; // Подкатегории
foreach ($data->categories->category as $row) {
	$arrCategory = (array)$row;
	if ($arrCategory['@attributes']['parentId'] == $needCategory)
		$arrNeedCategories[] = $arrCategory['@attributes']['id'];
}
// Перебор категорий х

// Перебор товаров
$arrOffers = []; // Все товары;
foreach ($data->offers->offer as $row)
	if (in_array($row->categoryId, $arrNeedCategories))
		$arrOffers[] = $row;
// Перебор товаров х

// Обработка параметров
$arrOffersFix = [];
foreach ($arrOffers as $oOffer){
	// - Парамтеры из html table в массив
	// print_r($oOffer->description);

	$arrOfferParams = [];
	$arrOffer = (array)$oOffer;
	$arrTableTr = explode('<tr', $arrOffer['parameters']);
	$arrTableTr = array_slice($arrTableTr, 1);

	foreach ($arrTableTr as $arrTableTrItem){
		$arrTableTd = explode('<td', $arrTableTrItem);
		$arrOfferParams['"'.substr(strip_tags($arrTableTd[1]), 13).'"'] = substr(strip_tags($arrTableTd[2]), 1);
	}
	$arrOffer['parameters'] = $arrOfferParams;

	$arrOffer['id'] = $arrOffer['@attributes']['id'];

	$arrOffersFix[] = $arrOffer;
}
$arrOffers = $arrOffersFix;
// echo strval($row->file) // имя HTML файла с карточкой товара
// echo strval($row->price) // цена товара
// echo strval($row->currencyId) // валюта в которой указана цена товара
// echo strval($row->priceRub) // цена товара в рублях
// echo strval($row->categoryId) // ID категории, к которой относится товар
// echo strval($row->picture) // имя файла с картинкой товара
// echo strval($row->picturebrand) // имя файла с картинкой бренда
// echo strval($row->vendor) // имя бренда
// echo strval($row->vendor_url) // URL бренда
// echo strval($row->model) // название товара
// echo strval($row->description) // описание товара
// echo strval($row->parameters) // параметры товара со ссылками на сайт бренда и инструкцию в формате PDF
// Обработка параметров х

// Вывод всех параметров товаров
// $arrOffersParams = [];
// foreach ($arrOffers as $arrOffer)
//   foreach ($arrOffer['parameters'] as $arrOfferParamName => $arrOfferParamValue )
//     if (!in_array($arrOfferParamName, $arrOffersParams))
//       $arrOffersParams[] = $arrOfferParamName;
// Вывод всех параметров товаров х

// Перебор всех производителей
$arrVendors = [];
foreach ($arrOffers as $arrOffer)
  if (!array_key_exists($arrOffer['vendor'], $arrVendors))
    $arrVendors[$arrOffer['vendor']] = array(
      'vendor_url' => $arrOffer['vendor_url'],
      'picturebrand' => $arrOffer['picturebrand'],
    );

// - Перебираем что получилось
foreach ($arrVendors as $sVendorName => &$arrVendor) {
  // -- Добавляем если его нет в магазине
  if (!$oVendor = $modx->getObject('msVendor', array('name' => $sVendorName))) {
  	$oVendor = $modx->newObject('msVendor');
    $oVendor->set('name', $sVendorName);
    $oVendor->set('logo', $msVendorImagePath . $arrVendor['picturebrand']);
    $oVendor->set('description', $arrVendor['vendor_url']);
  	$oVendor->save();
  }
  else{
    // --- Обновляем инфу о производителе если надо
    if ($oVendor->get('logo') != $msVendorImagePath . $arrVendor['picturebrand']) {
      $oVendor->set('logo', $msVendorImagePath . $arrVendor['picturebrand']);
      $oVendor->save();
    }
  }

  // -- Добавляем в масси в id производителя в магазине
  if ($oVendor)
    $arrVendor['id'] = $oVendor->get('id');
}

$modx->log(modX::LOG_LEVEL_INFO,  'Производители');
$modx->log(modX::LOG_LEVEL_INFO,  print_r($arrVendors,1));
// Перебор всех производителей х

// Перебор всех опций категории
$arrOptions = [];
$sQuery = "SELECT * FROM ". $modx->getOption('table_prefix') ."ms2_options";
$result = $modx->query($sQuery);
$resOptions = $result->fetchAll(PDO::FETCH_ASSOC);
foreach($resOptions as $resOption)
  $arrOptions['"'.$resOption['caption'].'"'] = 'options-'.$resOption['key'];

$modx->log(modX::LOG_LEVEL_INFO,  'Категории');
$modx->log(modX::LOG_LEVEL_INFO,  print_r($arrOptions,1));
// Перебор всех опций категории х

// Добавление товаров
$modx->log(modX::LOG_LEVEL_INFO,  'Товары');
foreach ($arrOffers as $arrOffer) {
  // - Параметры
  $data = []; # Массив с данными о товаре
  $action = []; # Действие [ обновление | добавление ]
  // - Параметры х

  // -  Поиск товара в магазине по артиклу (id)
  $q = $modx->newQuery('msProduct');
  $q->select('msProduct.id');
  $q->innerJoin('msProductData', 'Data', 'msProduct.id = Data.id');
  $q->where(array('Data.article' => $arrOffer['id']));
  $q->prepare();
  $exists = $modx->getObject('msProduct', $q);
  $data['id'] = $exists->id;

  if (isset($data['id']))
    $action = 'update';
  else
    $action = 'create';

  $modx->log(modX::LOG_LEVEL_INFO,  'Товар');
  $modx->log(modX::LOG_LEVEL_INFO,  '$action = ' . $action);
  // -  Поиск товара в магазине по артиклу (id) х

  // - Параметры MODX
  // $isfolder = isset($arrOffer['parent']) ? 1 : 0;
  // $data['alias'] = $arrOffer['alias']; # псевдоним для ссылки.
  // $data['menutitle'] = $arrOffer['menutitle']; # пункт меню
  // $data['hidemenu'] = 'Краткая выжимка из статьи.'; # Показывать в меню
  // $data['parent'] = $arrOffer['parent'];
	// $data['id'] = $data['id']; # присваиваем id ресурсу.
	$data['parent'] = $msProductCategory; // Категория куда заливать
	$data['createdby'] = '1'; # присваиваем автора ресурсу.
	$data['template'] = $templateOffer; # присваиваем шаблон ресурсу.
	$data['isfolder'] = 0; # новый ресурс не будет контейнером.
	$data['published'] = '1'; # будет опубликован.
	$data['createdon'] = time(); # дата создания контента.
	$data['pagetitle'] = $arrOffer['model']; # заголовок материала.
	$data['description'] = $arrOffer['description']; # описание, description.
  $data['introtext'] = $arrOffer['code']; # цитата/анонс, introtext. используется под код
	$data['content'] = $arrOffer['description']; # содержимое ресурса.
  // - Параметры MODX x

  // - Параметыр товара MiniShop2
  // $data['old_price'] = $arrOffer['priceRub']; # Старая цена
  // $data['weight'] = $arrOffer['priceRub']) # Вес
  // $data['thumb'] = $arrOffer['priceRub']; # Сжатое изображение
  // $data['made_in'] = $arrOffer['priceRub']; # Страна производитель
  // $data['new'] = $arrOffer['priceRub']; # Новый
  // $data['popular'] = $arrOffer['priceRub']; # Популярный
  // $data['favorite'] = $arrOffer['priceRub']; # Особый
  // $data['tags'] = $arrOffer['priceRub']; # Теги
  // $data['color'] = $arrOffer['priceRub']; # Цвет
  $data['class_key'] = 'msProduct'; // Тип ресурса
  $data['article'] = $arrOffer['id']; # Артикул
  $data['price'] = $arrOffer['priceRub']; # Цена
  $data['image'] = $arrOffer['picture']; # Изображение
  $data['vendor'] = $arrVendors[$arrOffer['vendor']]['id']; # Производитель - номером
  $data['size'] = $arrOffer['parameters']["Размер"]; # Размер
  // - Параметыр товара MiniShop2 x

  // - Опции товара
  foreach ($arrOffer['parameters'] as $key => $value)
    $data[$arrOptions[$key]] = $value;
  // - Опции товара х

  // - Добавляем или обновляем товар
  $modx->log(modX::LOG_LEVEL_INFO,  'article: ' . $data['article']);
  $response = $modx->runProcessor('resource/'.$action, $data);
  $resource = $response->getObject();
	if ($response->isError())
		$modx->log(modX::LOG_LEVEL_ERROR, "Error on $action: \n". print_r($response->getAllErrors(), 1));
  $modx->log(modX::LOG_LEVEL_INFO,  'id: ' . $resource['id']);
  // - Добавляем или обновляем товар х

  // - Изображения
  if (is_array($arrOffer['picture'])) {
    $modx->log(modX::LOG_LEVEL_INFO,  'Изображения');

    foreach ($arrOffer['picture'] as $sOfferImg) {
      if (empty($sOfferImg)) {continue;}
      $image = str_replace('//', '/', MODX_BASE_PATH . $msProductImagesPath . $sOfferImg);

      if (file_exists($image)){
        $response = $modx->runProcessor('gallery/upload',
          array('id' => $resource['id'], 'name' => $sOfferImg, 'file' => $image),
          array('processors_path' => MODX_CORE_PATH.'components/minishop2/processors/mgr/')
        );
				if ($response->isError()) {
					$modx->log(modX::LOG_LEVEL_ERROR, "Error on upload \"$v\": \n". print_r($response->getAllErrors(), 1));
				}
				else {
					$modx->log(modX::LOG_LEVEL_INFO, "Successful upload  \"$v\": \n". print_r($response->getObject(), 1));
				}
			}
      $modx->log(modX::LOG_LEVEL_INFO,  $msProductImagesPath . $image);
    }
  } else {
    if (empty($arrOffer['picture'])) {continue;}
    $image = str_replace('//', '/', MODX_BASE_PATH . $msProductImagesPath . $arrOffer['picture']);

		if (file_exists($image)){
			$response = $modx->runProcessor('gallery/upload',
				array('id' => $resource['id'], 'name' => $sOfferImg, 'file' => $image),
				array('processors_path' => MODX_CORE_PATH.'components/minishop2/processors/mgr/')
			);
			if ($response->isError()) {
				$modx->log(modX::LOG_LEVEL_ERROR, "Error on upload \"$v\": \n". print_r($response->getAllErrors(), 1));
			}
			else {
				$modx->log(modX::LOG_LEVEL_INFO, "Successful upload  \"$v\": \n". print_r($response->getObject(), 1));
			}
		}

    $modx->log(modX::LOG_LEVEL_INFO,  'Изображение');
    $modx->log(modX::LOG_LEVEL_INFO,  $msProductImagesPath.$image);
  }
  $modx->log(modX::LOG_LEVEL_INFO,  (print_r($data,1)));
  $modx->log(modX::LOG_LEVEL_INFO,  '____end____');
  // - Изображения х
}
// Добавление товаров х
