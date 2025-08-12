<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

foreach ($arResult['ITEMS'] as $key => $arItem)
{
	$arItem['PRICES']['PRICE']['PRINT_VALUE'] = number_format((float)$arItem['PRICES']['PRICE']['PRINT_VALUE'], 0, '.', ' ');
	$arItem['PRICES']['PRICE']['PRINT_VALUE'] .= ' '.$arItem['PROPERTIES']['PRICECURRENCY']['VALUE_ENUM'];

	$arResult['ITEMS'][$key] = $arItem;
}


$reviews = CIBlockElement::GetList(
	array(),
	array('IBLOCK_ID' => IBLOCK_REVIEWS_ID, '!PROPERTY_AUTHOR' => false),
	false,
	false,
	array('PROPERTY_AUTHOR', 'PROPERTY_PRODUCT', 'ID', 'NAME')
);


$authors = CUser::GetList(
    array(),
    'desc',
    array('UF_AUTHOR_STATUS' => 35),
    array('SELECT' => array('UF_AUTHOR_STATUS'))
);
$authorIds = [];

$authorsInGroup = CGroup::GetGroupUser(
    6
);


while ($rv = $authors -> GetNext()){
    if (in_array($rv['ID'], $authorsInGroup)){
    $authorIds[] = $rv['ID'];
    }
}


while ($rv= $reviews -> GetNext()){
	if (in_array($rv['PROPERTY_AUTHOR_VALUE'], $authorIds)){
		$reviewsNames [$rv["PROPERTY_PRODUCT_VALUE"]][] = $rv['NAME'];
	}
	
}

$reviewsCount = 0;
$firstReview = '';

foreach($arResult['ITEMS'] as $key=>$value){
	if ($reviewsNames[$value['ID']]){
		if (!$firstReview){
			$firstReview = $reviewsNames[$value['ID']][0];
		}
		$arResult['ITEMS'][$key]['REVIEWS'] = $reviewsNames[$value['ID']];
		$reviewsCount = $reviewsCount + count($reviewsNames[$value['ID']]);
	}
}

$arResult['COUNT_REVIEWS'] = $reviewsCount;
$arResult['FIRST_REVIEW'] = $firstReview;

$this-> __component->SetResultCacheKeys(['COUNT_REVIEWS']);