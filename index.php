<?php
#Параметрули
$phpQueryPath = 'lib/phpQuery-onefile.php'; #Путь до библиотеки phpQuery
$siteUrl = 'http://www.tnvd-remont-turbin.ru/'; #адрес сайта
$menuSelector = '#ja-splitmenu li, .ja-innerpad .module table td'; #jQeury селектор меню
$pageTitleSelector = 'h2.contentheading'; #jQeury селектор заголовка, pagetitle
$pageContentSelector = '#ja-current-content'; #jQeury селектор заголовка, pagetitle

#Структура ресурса
// $doc = [];
// $arrDoc['id'] = ''; # id
// $arrDoc['parent'] = ''; # родитель
// $arrDoc['pagetitle'] = ''; # h1
// $arrDoc['menutitle'] = ''; # пункт из меню
// $arrDoc['content'] = ''; # Содержание
// $arrDoc['introtext'] = ''; # Превью
// $arrDoc['alias'] = ''; # Псевдоним
// $arrDoc['uri'] = ''; # Полный путь

// Поехали!
require_once($phpQueryPath); #Подключаем библиотеку
$html = file_get_contents($siteUrl); #Хватаем сайт
$document = phpQuery::newDocument($html); #Создаём объект phpQuery
$menu_li = $document->find($menuSelector); #Получаем меню
$doc_id = 1; #id

#Первый этап, перебираем меню и собираем основу
foreach ($menu_li as $el) {
	$pq = pq($el); #Аналог $ в jQuery

	# $arrDoc['id'] - Получаем id
	$arrDoc['id'] = $doc_id++;

	# $arrDoc['menutitle'] - Добавляем название пункта меню
	$arrDoc['menutitle'] = $pq->find('a')->text();

	# $arrDoc['alias'] - Получаем альяс
	$href = parse_url($pq->find('a')->attr('href')); #Парсим url
	$href = $href['path']; # Берём только вложенный адрес
	$arrDoc['uri'] = $href = ($href{0} == '/') ? substr($href, 1) : $href; # $arrDoc['uri'] - Удаляем слеш вначале
	$href = (substr($href, -1) == '/') ? substr($href, 0, -1) : $href; #Удаляем слеш вконце
	$arrHref = explode('/', $href); 
	$arrDoc['alias'] = $arrHref[count($arrHref)-1];
	$arrDoc['parent_alias'] = $arrHref[count($arrHref)-2];

	# Добавляем в список
	$arrDocs[] = $arrDoc;
}

# Второй этап, дополняем сформированный список
foreach ($arrDocs as &$arrDoc){
	# Подготавливаем страницу для потягивания контента
	$html = file_get_contents($siteUrl . $arrDoc['uri']); #Адрес пункта меню
	$document = phpQuery::newDocument($html); #Создаём объект phpQuery

	# $arrDoc['pagetitle'] - Парсим заголовок страницы
	$arrDoc['pagetitle'] = $document->find($pageTitleSelector)->text(); #Получаем заголовок
	if (!$arrDoc['pagetitle']) #Запасной заголовок, если структура совсем хреновая..
		$arrDoc['pagetitle'] = $document->find('h1')->text();

	# $arrDoc['content'] - Парсим контенцкий
	$arrDoc['content'] = $document->find($pageContentSelector)->html();

	# $arrDoc['parent'] - Определяем вложенность
	if (isset($arrDoc['parent_alias'])){
		foreach ($arrDocs as $arrDocParent){
			$parent_alias = str_replace(array(".html",".htm",".php"), "", $arrDocParent['alias']);

			if ($arrDoc['parent_alias'] == $parent_alias){
				$arrDoc['parent'] = $arrDocParent['id'];
			}
		}
		if (!isset($arrDoc['parent'])) {
			# Если родитель не нашёлся, усыновляем =(
			$arrNewParentDoc['id'] = ( count($arrDocs) + 1 );
			$arrNewParentDoc['alias'] = $arrDoc['parent_alias'];
			$arrNewParentDoc['pagetitle'] = $arrDoc['parent_alias'];
			$arrDocs[] = $arrNewParentDoc;
		}
	}else{
		# По умолчанию ставим нуль
		$arrDoc['parent'] = '0';
	}
}



?><pre><?print_r($arrDocs)?></pre><?

# Пагинация

# Картинки

# TV параметры
