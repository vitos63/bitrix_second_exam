<?php
AddEventHandler("iblock", "OnBeforeIBlockElementAdd", Array("ReviewsClass", "OnBeforeIBlockElementAddHandler"));
AddEventHandler("iblock", "OnBeforeIBlockElementUpdate", Array("ReviewsClass", "OnBeforeIBlockElementAddHandler"));
AddEventHandler("iblock", "OnBeforeIBlockElementUpdate", Array("ReviewsClass", "OldAuthor"));
AddEventHandler("iblock", "OnAfterIBlockElementUpdate", Array("ReviewsClass", "NewAuthor"));
AddEventHandler("main", "OnBeforeUserUpdate", Array("UserClass", "OnBeforeUserUpdateHandler"));
AddEventHandler("main", "OnAfterUserUpdate", Array("UserClass", "OnAfterUserUpdateHandler"));
AddEventHandler("main", "OnBeforeEventSend", Array("MyForm", "my_OnBeforeEventSend"));
AddEventHandler("search", "BeforeIndex", Array("MyClass", "BeforeIndexHandler"));
AddEventHandler("main", "OnBuildGlobalMenu", "ChangeAdminMenu");

use Bitrix\Main\Mail\Event;

class ReviewsClass{
    protected static $oldAuthor = null;

    public static function OnBeforeIBlockElementAddHandler(&$arFields){
        global $APPLICATION;
        if ($arFields['IBLOCK_ID'] == 5){
            if (strlen($arFields['PREVIEW_TEXT'])<5){
            $APPLICATION -> ThrowException('Текст анонса слишком короткий: ' . strlen($arFields['PREVIEW_TEXT']));
            return false;
        }

        $arFields['PREVIEW_TEXT'] = str_replace('#del#', '', $arFields['PREVIEW_TEXT']);
    }
}

    public static function OldAuthor(&$arFields){
        if ($arFields['IBLOCK_ID']!=5){
            return true;
        }

        $author = CIBlockElement::GetList(
            [],
            ['ID' => $arFields['ID']],
            false,
            false,
            ['PROPERTY_AUTHOR'],
        ) ->Fetch();

        $oldAuthor = $author['PROPERTY_AUTHOR_VALUE']; 
        
        if ($oldAuthor){
            self::$oldAuthor = $oldAuthor;
        }
        
    }

    public static function NewAuthor(&$arFields){
        if ($arFields['IBLOCK_ID']!=5){
            return;
        }

        $author = CIBlockElement::GetList(
            [],
            ['ID' => $arFields['ID']],
            false,
            false,
            ['PROPERTY_AUTHOR'],
        ) -> Fetch();

        $newAuthor = $author['PROPERTY_AUTHOR_VALUE'];

        if (self::$oldAuthor && self::$oldAuthor != $newAuthor){
            CEventLog::Add([
                'SEVERITY' => 'INFO',
                'AUDIT_TYPE_ID' => 'ex2_590',
                'MODULE_ID' => 'iblock',
                'ITEM_ID' => $arFields['ID'], 
                'DESCRIPTION' => 'В рецензии ' .$arFields['ID'] .' изменился автор с ' .self::$oldAuthor .' на ' .$newAuthor
            ]);
        }
    }
}


class UserClass {

    protected static $userClass = null;

    public static function OnBeforeUserUpdateHandler(&$arFields){
        $userId = $arFields['ID'];
        $oldUserClass = CUser::GetByID($userId) -> Fetch();
        self::$userClass = $oldUserClass['UF_USER_CLASS'];
    }



    public static function OnAfterUserUpdateHandler(&$arFields){
        $userId = $arFields['ID'];
        $newUserClass = CUser::GetByID($userId) -> Fetch();
        $newUserClass = $newUserClass['UF_USER_CLASS'];
        if (self::$userClass != $newUserClass){
            Event::send(array(
            "EVENT_NAME" => "EX2_AUTHOR_INFO",
            "LID" => "s1",
            "C_FIELDS" => array(
            "OLD_USER_CLASS" => self::$userClass,
            "NEW_USER_CLASS" => $newUserClass
            ),
            )); 
        }
    }
}


class MyForm{
    public static function my_OnBeforeEventSend($arFields, $arTemplate){
        if ($arTemplate['EVENT_NAME'] == 'USER_INFO'){
            $userId = $arFields["ID"];
            $userClass = CUser::GetByID($userId) ->Fetch()['UF_USER_CLASS'];
            $arFields['CLASS'] = $userClass;
            return $arFields;
        }
        
    }
}

class MyClass{
    public static function BeforeIndexHandler($arFields){
        if ($arFields["MODULE_ID"] == "iblock" && $arFields["PARAM2"] == 5){
            $authorId = CIBlockElement::GetList(
                [],
                ['ID' => $arFields['ITEM_ID']],
                false,
                false,
                ['ID', 'PROPERTY_AUTHOR']
            ) ->Fetch();
        $user = CUser::GetByID($authorId['PROPERTY_AUTHOR_VALUE']) -> Fetch();
        $userClassXML = $user['UF_USER_CLASS'];
        $userClass = CUserFieldEnum::GetList(
            [],
            ['ID' => $userClassXML]
        )->Fetch() ['VALUE'];
        $arFields['TITLE'] .= ' Класс: '. $userClass;
        return $arFields;
    }
    }
}


function ChangeAdminMenu(&$aGlobalMenu, &$aModuleMenu){
    global $USER;
    $userId = $USER -> GetId();
    $userGroups = CUser::GetUserGroup(
        $userId
    );   

    if (in_array(5, $userGroups)){
        foreach ($aGlobalMenu as $key => $value){
            if ($key !== 'global_menu_content'){
                unset($aGlobalMenu[$key]);       
            }
        }
        foreach ($aModuleMenu as $key => $value){
            if ($value['parent_menu'] !== 'global_menu_content'){
                unset($aModuleMenu[$key]);
            }
        }

        $aGlobalMenu['fast'] = [
            'menu_id' => 'fast',
            'text' => 'Быстрый доступ',
            'title' => 'Быстрый доступ',
            'sort' => 100,
            'items_id' => 'fast',
            'items' => [
            ]

        ];
        $aModuleMenu[] = [
            'text' => 'Ссылка 1',
            'url' => 'https://test1',
            'title' => 'Ссылка 1',
            'parent_menu' => 'fast'
        ];

        $aModuleMenu[] = [
            'text' => 'Ссылка 2',
            'url' => 'https://test2',
            'title' => 'Ссылка 2',
            'parent_menu' => 'fast'
        ];
    }
}
