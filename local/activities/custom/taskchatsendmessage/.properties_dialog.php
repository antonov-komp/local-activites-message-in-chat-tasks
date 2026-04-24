<?php
if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) {
    die();
}

use Bitrix\Main\Localization\Loc;

Loc::loadMessages(__FILE__);

$currentValues = is_array($arCurrentValues) ? $arCurrentValues : [];

if (!array_key_exists('task_id', $currentValues)) {
    $currentValues['task_id'] = '';
}
if (!array_key_exists('sender_id', $currentValues)) {
    $currentValues['sender_id'] = '';
}
if (!array_key_exists('message_text', $currentValues)) {
    $currentValues['message_text'] = '';
}
?>
<tr>
    <td align="right" width="40%"><span class="adm-required-field">*</span> <?= htmlspecialcharsbx(Loc::getMessage('TASKCHATSENDMESSAGE_PROPERTY_TASK_ID')) ?>:</td>
    <td width="60%">
        <?= CBPDocument::ShowParameterField('int', 'task_id', $currentValues['task_id'], ['size' => 30], $formName) ?>
    </td>
</tr>
<tr>
    <td align="right"><span class="adm-required-field">*</span> <?= htmlspecialcharsbx(Loc::getMessage('TASKCHATSENDMESSAGE_PROPERTY_SENDER_ID')) ?>:</td>
    <td>
        <?= CBPDocument::ShowParameterField('user', 'sender_id', $currentValues['sender_id'], [], $formName) ?>
    </td>
</tr>
<tr>
    <td align="right" valign="top"><span class="adm-required-field">*</span> <?= htmlspecialcharsbx(Loc::getMessage('TASKCHATSENDMESSAGE_PROPERTY_MESSAGE_TEXT')) ?>:</td>
    <td>
        <?= CBPDocument::ShowParameterField('text', 'message_text', $currentValues['message_text'], ['rows' => 6, 'cols' => 60], $formName) ?>
    </td>
</tr>
