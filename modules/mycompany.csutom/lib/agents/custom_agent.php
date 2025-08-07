<?php

use Bitrix\Main\Loader;
class MyAgent{
public static function Agent_ex_610($last_time_exec=''){
    if (Loader::includeModule("iblock")){
		$countReviews = MyAgent::ReviewsCount($last_time_exec);
		echo($last_time_exec);

    CEventLog::Add([
        "SEVERITY" => "INFO",
        "AUDIT_TYPE_ID" => "ex2_610",
        "MODULE_ID" => "main",
        'DESCRIPTION' => "Запуск агента ex2_610. С ".$last_time_exec." изменилось ".$countReviews." рецензий",
    ]);
    }
    


    return "\\" . __METHOD__ . "(\"" . (new DateTime()) -> format('d.m.Y H:i:s') . "\");";
}

public static function ReviewsCount($last_time_exec=''){
	$date = $last_time_exec ?: (new DateTime())->format('d.m.Y H:i:s');
    $reviews = CIBlockElement::GetList(
        [],
        ['IBLOCK_ID' => 5, '<=TIMESTAMP_X' => $date],
        false,
        false,
        ['ID', 'TIMESTAMP_X']
    );

    $countReviews = 0;
    while ($review = $reviews -> GetNext()){
        $countReviews = $countReviews + 1;
    }
	echo($countReviews);
    return $countReviews;
}

}