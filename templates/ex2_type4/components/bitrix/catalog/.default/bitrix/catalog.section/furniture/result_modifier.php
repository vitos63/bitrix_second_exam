<?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();



foreach ($arResult['ITEMS'] as $key => $arItem)
{
	$arItem['PRICES']['PRICE']['PRINT_VALUE'] = number_format((float)$arItem['PRICES']['PRICE']['PRINT_VALUE'], 0, '.', ' ');
	$arItem['PRICES']['PRICE']['PRINT_VALUE'] .= ' '.$arItem['PROPERTIES']['PRICECURRENCY']['VALUE_ENUM'];

	$arResult['ITEMS'][$key] = $arItem;
}


$reviews = CIBlockElement::GetList(
	[],
	['IBLOCK_ID' => 5],
	false,
	false,
	['PROPERTY_AUTHOR', 'PROPERTY_PRODUCT', 'NAME', 'ID'],
);

$productIds = array();
$authors = CGroup::GetGroupUser(
	6
);
$reviewsArray = array();

$publish_authors = CUser::GetList(
    ($by = 'ID'),
    ($order = 'ASC'),
    ['UF_AUTHOR_STATUS' => 35],
    ['SELECT' => ['UF_AUTHOR_STATUS']]
);

$publishAuthorsIDs = array();

while ($author = $publish_authors -> Fetch()){
	$publishAuthorsIDs[] = $author['ID'];
}


foreach ($arResult['ITEMS'] as $key=>$value){
	$productIds[] = $value['ID'];
}

while ($review = $reviews->GetNext()){
	
	if (in_array($review['PROPERTY_PRODUCT_VALUE'], $productIds) && in_array($review['PROPERTY_AUTHOR_VALUE'], $authors) && in_array($review['PROPERTY_AUTHOR_VALUE'], $publishAuthorsIDs)){
		$reviewsArray [$review['PROPERTY_PRODUCT_VALUE']][] = $review['NAME'];
	}
	
}

foreach ($arResult['ITEMS'] as $key=>$value ){
	if ($reviewsArray[$value['ID']]){
	$arResult['ITEMS'][$key]['REVIEW'] = $reviewsArray[$value['ID']];
	}
}

$firstReview = '';
$countProductReviews = 0;
foreach ($arResult['ITEMS'] as $key=>$value){
	if ($arResult['ITEMS'][$key]['REVIEW']){
		$countProductReviews = $countProductReviews + count($arResult['ITEMS'][$key]['REVIEW']);
		if (!$firstReview){
			$firstReview = array_key_first($arResult['ITEMS'][$key]['REVIEW']);
			$firstReview = $arResult['ITEMS'][$key]['REVIEW'][$firstReview];
		}
	}
}

if ($firstReview){
	$arResult['FIRST_REVIEW'] = $firstReview;
}


$arResult['COUNT_REVIEWS'] = $countProductReviews;

$this-> __component->SetResultCacheKeys(['COUNT_REVIEWS']);
