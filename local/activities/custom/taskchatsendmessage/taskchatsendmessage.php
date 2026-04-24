<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\SystemException;
use Bitrix\Tasks\Integration\IM\Task as TaskImIntegration;

Loc::loadMessages(__FILE__);

class CBPTaskChatSendMessage extends CBPActivity
{
    private const RESULT_SUCCESS = 'Y';
    private const RESULT_FAIL = 'N';

    public function __construct($name)
    {
        parent::__construct($name);

        $this->arProperties = [
            'TaskId' => 0,
            'SenderId' => null,
            'MessageText' => '',
            'MessageId' => 0,
            'IsSuccess' => self::RESULT_FAIL,
            'ErrorMessage' => '',
        ];
    }

    public function Execute()
    {
        $this->resetResultProperties();

        try {
            $this->ensureModulesLoaded();

            $taskId = $this->resolveTaskId();
            $senderId = $this->resolveSenderId();
            $messageText = $this->resolveMessageText();

            $chatId = $this->resolveChatId($taskId, $senderId);
            if ($chatId <= 0) {
                throw new SystemException(Loc::getMessage('TASKCHATSENDMESSAGE_ERROR_CHAT_NOT_FOUND'));
            }

            $messageId = $this->sendMessageToChat($chatId, $senderId, $messageText);
            $this->markSuccess($taskId, $chatId, $messageId);
        } catch (\Throwable $exception) {
            $this->markFailure($exception->getMessage());
        }

        return CBPActivityExecutionStatus::Closed;
    }

    protected function resetResultProperties(): void
    {
        $this->setPropertyValue('MessageId', 0);
        $this->setPropertyValue('IsSuccess', self::RESULT_FAIL);
        $this->setPropertyValue('ErrorMessage', '');
    }

    protected function resolveMessageText(): string
    {
        $messageText = trim((string)$this->MessageText);
        if ($messageText === '') {
            throw new SystemException(Loc::getMessage('TASKCHATSENDMESSAGE_ERROR_EMPTY_MESSAGE'));
        }

        return $messageText;
    }

    protected function sendMessageToChat(int $chatId, int $senderId, string $messageText): int
    {
        $messageId = (int)CIMChat::AddMessage([
            'TO_CHAT_ID' => $chatId,
            'FROM_USER_ID' => $senderId,
            'MESSAGE' => $messageText,
            'SYSTEM' => 'N',
            'URL_PREVIEW' => 'Y',
        ]);

        if ($messageId <= 0) {
            throw new SystemException(Loc::getMessage('TASKCHATSENDMESSAGE_ERROR_SEND_FAILED'));
        }

        return $messageId;
    }

    protected function markSuccess(int $taskId, int $chatId, int $messageId): void
    {
        $this->setPropertyValue('MessageId', $messageId);
        $this->setPropertyValue('IsSuccess', self::RESULT_SUCCESS);

        $this->WriteToTrackingService(
            Loc::getMessage('TASKCHATSENDMESSAGE_TRACKING_SUCCESS', [
                '#TASK_ID#' => $taskId,
                '#CHAT_ID#' => $chatId,
                '#MESSAGE_ID#' => $messageId,
            ])
        );
    }

    protected function markFailure(string $errorMessage): void
    {
        $this->setPropertyValue('ErrorMessage', $errorMessage);
        $this->setPropertyValue('IsSuccess', self::RESULT_FAIL);

        $this->WriteToTrackingService(
            Loc::getMessage('TASKCHATSENDMESSAGE_TRACKING_ERROR', [
                '#ERROR#' => $errorMessage,
            ]),
            0,
            CBPTrackingType::Error
        );
    }

    protected function ensureModulesLoaded(): void
    {
        if (!Loader::includeModule('tasks')) {
            throw new SystemException(Loc::getMessage('TASKCHATSENDMESSAGE_ERROR_TASKS_MODULE'));
        }

        if (!Loader::includeModule('im')) {
            throw new SystemException(Loc::getMessage('TASKCHATSENDMESSAGE_ERROR_IM_MODULE'));
        }
    }

    protected function resolveTaskId(): int
    {
        $taskId = (int)$this->TaskId;
        if ($taskId <= 0) {
            throw new SystemException(Loc::getMessage('TASKCHATSENDMESSAGE_ERROR_TASK_ID'));
        }

        $taskExists = \Bitrix\Tasks\Internals\TaskTable::query()
            ->setSelect(['ID'])
            ->where('ID', $taskId)
            ->setLimit(1)
            ->fetch();

        if (!$taskExists) {
            throw new SystemException(Loc::getMessage('TASKCHATSENDMESSAGE_ERROR_TASK_NOT_FOUND', ['#TASK_ID#' => $taskId]));
        }

        return $taskId;
    }

    protected function resolveSenderId(): int
    {
        $senderId = $this->extractUserId($this->SenderId);
        if ($senderId <= 0) {
            throw new SystemException(Loc::getMessage('TASKCHATSENDMESSAGE_ERROR_SENDER_ID'));
        }

        $userExists = \CUser::GetByID($senderId)->Fetch();
        if (!$userExists) {
            throw new SystemException(Loc::getMessage('TASKCHATSENDMESSAGE_ERROR_SENDER_NOT_FOUND', ['#USER_ID#' => $senderId]));
        }

        return $senderId;
    }

    protected function extractUserId($sender): int
    {
        if (is_array($sender)) {
            $sender = reset($sender);
        }

        if (is_string($sender) && preg_match('/\d+/', $sender, $matches)) {
            return (int)$matches[0];
        }

        return (int)$sender;
    }

    protected function resolveChatId(int $taskId, int $senderId): int
    {
        if (!class_exists(TaskImIntegration::class)) {
            throw new SystemException(Loc::getMessage('TASKCHATSENDMESSAGE_ERROR_TASK_IM_INTEGRATION'));
        }

        $chatId = (int)TaskImIntegration::getChatId($taskId);

        if ($chatId <= 0) {
            $chatId = (int)TaskImIntegration::addChat($taskId, $senderId);
        }

        if ($chatId <= 0) {
            $chatData = TaskImIntegration::getChatData($taskId);
            if (is_array($chatData) && isset($chatData['CHAT_ID'])) {
                $chatId = (int)$chatData['CHAT_ID'];
            }
        }

        return $chatId;
    }

    public static function GetPropertiesDialog(
        $documentType,
        $activityName,
        $workflowTemplate,
        $workflowParameters,
        $workflowVariables,
        $currentValues = null,
        $formName = ''
    ) {
        $currentValues = is_array($currentValues) ? $currentValues : [];
        $currentValues['task_id'] = (string)($currentValues['task_id'] ?? '');
        $currentValues['sender_id'] = (string)($currentValues['sender_id'] ?? '');
        $currentValues['message_text'] = (string)($currentValues['message_text'] ?? '');

        return new CBPActivityPropertiesDialog(
            __FILE__,
            [
                'documentType' => $documentType,
                'activityName' => $activityName,
                'workflowTemplate' => $workflowTemplate,
                'workflowParameters' => $workflowParameters,
                'workflowVariables' => $workflowVariables,
                'currentValues' => $currentValues,
                'formName' => $formName,
            ]
        );
    }

    public static function GetPropertiesDialogValues(
        $documentType,
        $activityName,
        &$workflowTemplate,
        &$workflowParameters,
        &$workflowVariables,
        $currentValues,
        &$errors
    ) {
        $errors = [];

        $taskId = trim((string)($currentValues['task_id'] ?? ''));
        $senderId = trim((string)($currentValues['sender_id'] ?? ''));
        $messageText = trim((string)($currentValues['message_text'] ?? ''));

        if ($taskId === '') {
            $errors[] = [
                'code' => 'NotExist',
                'parameter' => 'TaskId',
                'message' => Loc::getMessage('TASKCHATSENDMESSAGE_ERROR_TASK_ID'),
            ];
        }

        if ($senderId === '') {
            $errors[] = [
                'code' => 'NotExist',
                'parameter' => 'SenderId',
                'message' => Loc::getMessage('TASKCHATSENDMESSAGE_ERROR_SENDER_ID'),
            ];
        }

        if ($messageText === '') {
            $errors[] = [
                'code' => 'NotExist',
                'parameter' => 'MessageText',
                'message' => Loc::getMessage('TASKCHATSENDMESSAGE_ERROR_EMPTY_MESSAGE'),
            ];
        }

        if (!empty($errors)) {
            return false;
        }

        $properties = [
            'TaskId' => $taskId,
            'SenderId' => $senderId,
            'MessageText' => $messageText,
        ];

        $activity = &CBPWorkflowTemplateLoader::FindActivityByName($workflowTemplate, $activityName);
        $activity['Properties'] = $properties;

        return true;
    }
}
