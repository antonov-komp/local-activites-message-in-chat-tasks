<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

$arActivityDescription = [
    'NAME' => GetMessage('TASKCHATSENDMESSAGE_ACTIVITY_NAME'),
    'DESCRIPTION' => GetMessage('TASKCHATSENDMESSAGE_ACTIVITY_DESCRIPTION'),
    'TYPE' => 'activity',
    'CLASS' => 'TaskChatSendMessage',
    'JSCLASS' => 'BizProcActivity',
    'CATEGORY' => [
        'ID' => 'other',
    ],
    'PROPERTIES' => [
        'TaskId' => [
            'Name' => GetMessage('TASKCHATSENDMESSAGE_PROPERTY_TASK_ID'),
            'Type' => 'int',
            'Required' => true,
        ],
        'SenderId' => [
            'Name' => GetMessage('TASKCHATSENDMESSAGE_PROPERTY_SENDER_ID'),
            'Type' => 'user',
            'Required' => true,
        ],
        'MessageText' => [
            'Name' => GetMessage('TASKCHATSENDMESSAGE_PROPERTY_MESSAGE_TEXT'),
            'Type' => 'text',
            'Required' => true,
        ],
    ],
    'RETURN' => [
        'MessageId' => [
            'NAME' => GetMessage('TASKCHATSENDMESSAGE_RETURN_MESSAGE_ID'),
            'TYPE' => 'int',
        ],
        'IsSuccess' => [
            'NAME' => GetMessage('TASKCHATSENDMESSAGE_RETURN_IS_SUCCESS'),
            'TYPE' => 'string',
        ],
        'ErrorMessage' => [
            'NAME' => GetMessage('TASKCHATSENDMESSAGE_RETURN_ERROR_MESSAGE'),
            'TYPE' => 'string',
        ],
    ],
    'ADDITIONAL_RESULT' => [
        'MessageId' => [
            'NAME' => GetMessage('TASKCHATSENDMESSAGE_RETURN_MESSAGE_ID'),
            'TYPE' => 'int',
        ],
        'IsSuccess' => [
            'NAME' => GetMessage('TASKCHATSENDMESSAGE_RETURN_IS_SUCCESS'),
            'TYPE' => 'string',
        ],
        'ErrorMessage' => [
            'NAME' => GetMessage('TASKCHATSENDMESSAGE_RETURN_ERROR_MESSAGE'),
            'TYPE' => 'string',
        ],
    ],
    'ROBOT_SETTINGS' => [
        'CATEGORY' => 'employee',
        'GROUP' => ['communication'],
        'SORT' => 900,
    ],
];
