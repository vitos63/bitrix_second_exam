<?php
use Bitrix\Main\Mail\Event;
use Bitrix\Main\Loader;

define('CONTENT_EDITORS', 5);
define('IBLOCK_REVIEWS_ID', 5);

AddEventHandler("iblock", "OnBeforeIBlockElementUpdate", Array("ReviewsClass", "PreviewHandler"));
AddEventHandler("iblock", "OnBeforeIBlockElementAdd", Array("ReviewsClass", "PreviewHandler"));
AddEventHandler("iblock", "OnAfterIBlockElementUpdate", Array("ReviewsClass", "AuthorHandler"));
AddEventHandler("main", "OnBeforeUserUpdate", Array("MessageClass", "OnBeforeUserUpdateHandler"));
AddEventHandler("main", "OnAfterUserUpdate", Array("MessageClass", "OnAfterUserUpdateHandler"));
AddEventHandler('main', 'OnBeforeEventSend', Array("MyForm", "my_OnBeforeEventSend"));
AddEventHandler("search", "BeforeIndex", Array("SearchClass", "BeforeIndexHandler"));
AddEventHandler("main", "OnBuildGlobalMenu", Array("MenuClass", "MyOnBuildGlobalMenu"));



class ReviewsClass{
    protected static $oldAuthor = null;
    public static function PreviewHandler(&$arFields){
        if ($arFields['IBLOCK_ID'] == 5){
            if (strlen($arFields['PREVIEW_TEXT'])<5){
                global $APPLICATION;
                $APPLICATION->throwException("Текст анонса слишком короткий:". strlen($arFields["PREVIEW_TEXT"]));
                return false;
            }

            $arFields['PREVIEW_TEXT'] = str_replace('#del#', '', $arFields['PREVIEW_TEXT']);
            
            self::$oldAuthor = CIBlockElement::GetList(
                array(),
                array('ID' => $arFields['ID']),
                false,
                false,
                array('PROPERTY_AUTHOR')
            ) -> GetNext()['PROPERTY_AUTHOR_VALUE'];
        }

    }

    public static function AuthorHandler(&$arFields){
        if ($arFields['IBLOCK_ID'] == 5){
            $currentAuthor = CIBlockElement::GetList(
                    array(),
                    array('ID' => $arFields['ID']),
                    false,
                    false,
                    array('PROPERTY_AUTHOR')
                ) -> GetNext()['PROPERTY_AUTHOR_VALUE'];
            if ($currentAuthor != self::$oldAuthor){
                CEventLog::Add(
                    array(
                        "SEVERITY" => "INFO",
                        'MODULE_ID' => 'iblock',
                        'ITEM_ID' => $arFields['ID'],
                        'AUDIT_TYPE_ID' => 'ex2_590',
                        'DESCRIPTION' => GetMessage('REVIEW_CHANGE_AUTHOR', [
                            '#ID#' => $arFields['ID'],
                            '#OLD_AUTHOR#' => self::$oldAuthor,
                            '#NEW_AUTHOR#' => $currentAuthor
                        ]))
                );
            }
        }
    }
}


class MessageClass{
    protected static $oldUserClass = null;
    public static function OnBeforeUserUpdateHandler(&$arFields){
        $userId = $arFields['ID'];
        $user = CUser::GetByID(
            $userId
        ) -> Fetch();
        self::$oldUserClass = $user['UF_USER_CLASS'];
    }

    public static function OnAfterUserUpdateHandler(&$arFields){
        $userId = $arFields['ID'];
        $user = CUser::GetByID(
            $userId
        ) -> Fetch();
        $currentUserClass = $user['UF_USER_CLASS'];
        if ($currentUserClass != self::$oldUserClass){
            Event::send(
                array(
                    'EVENT_NAME' => 'EX2_AUTHOR_INFO',
                    "LID" => "s1",
                    "C_FIELDS" => array(
                        "OLD_USER_CLASS" => self::$oldUserClass,
                        "NEW_USER_CLASS" => $currentUserClass
                    ),
                )
            );
        }
    }
}

class MyCustomAgent {
    public static function Agent_ex_610($lastTimeExec = ''){
        if (Loader::IncludeModule('iblock')){
			$lastTimeExec = $lastTimeExec ?: (new DateTime()) -> format('d.m.Y H:i:s');
            $countReviews = MyCustomAgent::CountReviews($lastTimeExec);
            CEventLog::Add(
				array(
					'AUDIT_TYPE_ID' => 'ex2_610',
					'MODULE_ID' => 'iblock',
					'ITEM_ID' => 5,
					'SITE_ID' => 's1',
					'DESCRIPTION' => "Запуск агента ex2_610. С" .$lastTimeExec . " изменилось " .$countReviews ." рецензий ",
					)
				);
			return "MyCustomAgent::Agent_ex_610('.$lastTimeExec.');";
        }
    }

    public static function CountReviews($lastTimeExec = ''){
        $date = $lastTimeExec ?: (new DateTime()) -> format('d.m.Y H:i:s');
        $reviews = CIBlockElement::GetList(
            array(),
            array('IBLOCK_ID' => 5, '>TIMESTAMP_X' => $date) ,
            false,
            false,
            array()
        );
        $reviewsCount = $reviews -> SelectedRowsCount();
        return $reviewsCount;
    }
}

class MyForm{
    public static function my_OnBeforeEventSend($arFields, $arTemplate){
        if ($arTemplate['EVENT_NAME'] == 'USER_INFO'){
            $user = CUser::GetByID(
                $arFields['USER_ID']
            ) -> Fetch();
            $userClassXML = $user['UF_USER_CLASS'];
            $userClass = CUserFieldEnum::GetList(
            [],
            ['ID' => $userClassXML]
            )->Fetch() ['VALUE'];
            $arFields['CLASS'] = 'Класс' . $userClass;
        }
    }
}

class SearchClass{
    public static function BeforeIndexHandler($arFields){
        if ($arFields['PARAM2'] == 5){
            $authorId = CIBlockElement::GetList(
                [],
                array('ID' => $arFields['ITEM_ID']),
                false,
                false,
                ['PROPERTY_AUTHOR']
            ) -> Fetch()['PROPERTY_AUTHOR_VALUE'];
            if ($authorId){
                $userClass = CUser::GetByID($authorId) -> Fetch();
                $userClassXML = $userClass['UF_USER_CLASS'];
                $userClass = CUserFieldEnum::GetList(
                [],
                ['ID' => $userClassXML]
                )->Fetch() ['VALUE'];
                $arFields['TITLE'] .= ' Класс: ' .$userClass;
            }
            return $arFields;
        }
    }
}

class MenuClass{
    public static function MyOnBuildGlobalMenu(&$aGlobalMenu, &$aModuleMenu){
        global $USER;
        $userId = $USER -> GetId();
        $userGroups = CUser::GetUserGroup(
            $userId
        );
        if (in_array(CONTENT_EDITORS ,$userGroups)){
            foreach($aGlobalMenu as $key=>$value){
                if ($key != 'global_menu_content'){
                    unset($aGlobalMenu[$key]);
                }
            }

            foreach($aModuleMenu as $key=>$value){
                if ($aModuleMenu[$key]['parent_menu'] != 'global_menu_content'){
                    unset($aModuleMenu[$key]);
                }
            }

            $aGlobalMenu[GetMessage('FAST_MENU_ID')] = [
                'menu_id' => GetMessage('FAST_MENU_ID'),
                'text' => GetMessage('FAST_MENU_TEXT'),
                'title' => GetMessage('FAST_MENU_TEXT'),
                'items_id' => GetMessage('FAST_MENU_ID'),
                'items' => array()
            ];
            $aModuleMenu[] = [
                'parent_menu' => GetMessage('FAST_MENU_ID'),
                'text' => GetMessage('FIRST_LINK_TEXT'),
                'title' => GetMessage('FIRST_LINK_TEXT'),
                'url' => GetMessage('FIRST_LINK')
            ];
            $aModuleMenu[] = [
                'parent_menu' => GetMessage('FAST_MENU_ID'),
                'text' => GetMessage('SECOND_LINK_TEXT'),
                'title' => GetMessage('SECOND_LINK_TEXT'),
                'url' => GetMessage('SECOND_LINK')
            ];                
        }
    }
}
