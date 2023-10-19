<?php
$_SERVER["DOCUMENT_ROOT"] = "/home/bitrix/www";
$DOCUMENT_ROOT = $_SERVER["DOCUMENT_ROOT"];

define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

include_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

global $USER;
$USER = new CUser;
$USER->Authorize(1);

sleep(1);
$id_lid = stripslashes($argv[1]); // id лида (stripslashes - снимаем экранирование )
CModule::IncludeModule('iblock');
CModule::IncludeModule('crm');
CModule::IncludeModule('tasks');
$lead  =  new  \CCrmLead( true );

$res = CCrmEvent::GetList([], [
    'ENTITY_TYPE'=> "LEAD",
    'ENTITY_ID' => $id_lid,
    'EVENT_TYPE' => "2", // тип почта
], false);

while($arEvent = $res->Fetch()){
    if(!empty($arEvent["FILES"])){
        $foos = unserialize($arEvent["FILES"]);
        $nfile = reset($foos);

        $rsFile = \CFile::GetFileArray($nfile);
        $arUpdateData = [
            "COMMENTS" => $arEvent["EVENT_TEXT_1"], // Помещаем содержание письма в комментарий
            "UF_CRM_1695289911615" =>  CFile::MakeFileArray(reset($rsFile)), // как получить файл прикрепленный к письму?
        ];
        $arOptions = [];
        $upRes = $lead->Update($id_lid, $arUpdateData, true, true, $arOptions);
        $newFileId = CFile::MakeFileArray($rsFile['ID']);

        //загружаем файл
        $storage = Bitrix\Disk\Driver::getInstance()->getStorageByUserId(1);
        $folder = $storage->getFolderForUploadedFiles();
        $arFile = CFile::MakeFileArray($rsFile['ID']);
        $file = $folder->uploadFile($arFile, array(
            'NAME' => $arFile["name"],
            'CREATED_BY' => 1
        ), array(), true);
        $FILE_ID = $file->getId();

        $oTaskItem = new CTaskItem(100, 1);

        $oTaskItem = array(
            "TITLE" => "заголовок",
            "DESCRIPTION" => 'описание',
            "RESPONSIBLE_ID" => 1,
            "UF_TASK_WEBDAV_FILES" => Array("n$FILE_ID"),
        );
        $taskItem = \CTaskItem::add($oTaskItem, 1);

//        $file = fopen($_SERVER['DOCUMENT_ROOT'] . '/log/__debug.log', 'a');
//        fwrite(
//            $file,
//            __FILE__ . ' [' . __LINE__ . ']' . PHP_EOL . '(' . date('Y-m-d H:i:s').')' . PHP_EOL
//            . print_r($newFileId, TRUE) . PHP_EOL . PHP_EOL
//        );
//        fclose($file);

    }
}

$USER->Logout();
