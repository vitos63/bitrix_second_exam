<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
global $APPLICATION;
$current_dir_property = $APPLICATION -> GetDirProperty(
	'ex2_meta',
	false,
	false
);

$current_page_property = $APPLICATION -> GetPageProperty(
	'ex2_meta',
	false,
	false
);

if ($current_page_property){
    $current_property = str_replace('#count#', $arResult['COUNT_REVIEWS'], $current_page_property);
}

elseif ($current_dir_property){
    $current_dir_property = str_replace('#count#', $arResult['COUNT_REVIEWS'], $current_dir_property);
}

$APPLICATION -> SetPageProperty(
    'ex2_meta',
    $current_property,
    null
);