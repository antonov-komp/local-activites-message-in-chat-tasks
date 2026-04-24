# TaskChatSendMessage — как работает Activity

## Назначение
`TaskChatSendMessage` — кастомное Activity для Бизнес-процессов Bitrix24 (коробка), которое отправляет текстовое сообщение в чат конкретной задачи.

Activity принимает:
- `TaskId` — ID задачи;
- `SenderId` — пользователь-отправитель;
- `MessageText` — текст сообщения (включая BB-коды).

И возвращает:
- `MessageId` — ID созданного сообщения;
- `IsSuccess` — `Y` / `N`;
- `ErrorMessage` — текст ошибки (если `IsSuccess = N`).

---

## Где лежит код
- Описание Activity: `local/activities/custom/taskchatsendmessage/.description.php`
- Логика Activity: `local/activities/custom/taskchatsendmessage/taskchatsendmessage.php`
- Форма настроек в редакторе БП: `local/activities/custom/taskchatsendmessage/.properties_dialog.php`
- Локализация (RU):
  - `local/activities/custom/taskchatsendmessage/lang/ru/.description.php`
  - `local/activities/custom/taskchatsendmessage/lang/ru/.properties_dialog.php`
  - `local/activities/custom/taskchatsendmessage/lang/ru/taskchatsendmessage.php`

---

## Как Activity отображается в редакторе БП
В дизайнере БП Activity показывает 3 обязательных поля:
1. **ID задачи** (`TaskId`) — числовое поле, поддерживает подстановки/макросы БП.
2. **Отправитель** (`SenderId`) — стандартный пользовательский селектор Bitrix (`type=user`).
3. **Текст сообщения** (`MessageText`) — многострочный текст с поддержкой вставки значений документа.

Форма рендерится через `.properties_dialog.php`, а сохранение выполняется методом `GetPropertiesDialogValues()`.

---

## Пошаговая логика выполнения (Execute)
При запуске `Execute()` Activity делает следующее:

1. **Сбрасывает выходные значения по умолчанию**
   - `MessageId = 0`
   - `IsSuccess = N`
   - `ErrorMessage = ''`

2. **Проверяет модули**
   - подключает `tasks`;
   - подключает `im`.
   Если модуль не загрузился — выбрасывает исключение.

3. **Валидирует входные параметры**
   - `TaskId` должен быть > 0 и существовать в `TaskTable`;
   - `SenderId` должен быть корректным и существовать как пользователь;
   - `MessageText` не должен быть пустым.

4. **Получает/создаёт чат задачи**
   - пытается получить чат через `Bitrix\Tasks\Integration\IM\Task::getChatId($taskId)`;
   - если чата нет — инициирует создание через `addChat($taskId, $senderId)`;
   - если всё ещё нет — делает fallback через `getChatData($taskId)`.

5. **Отправляет сообщение**
   - вызывает `CIMChat::AddMessage()` с:
     - `TO_CHAT_ID`
     - `FROM_USER_ID`
     - `MESSAGE`
     - `SYSTEM = 'N'`
     - `URL_PREVIEW = 'Y'`

6. **Фиксирует результат**
   - при успехе:
     - `MessageId = <ID>`
     - `IsSuccess = Y`
     - пишет success в tracking service;
   - при ошибке:
     - `IsSuccess = N`
     - `ErrorMessage = <текст>`
     - пишет ошибку в tracking service (`CBPTrackingType::Error`).

---

## Пример использования в БП
Типовой сценарий:
1. Получить/вычислить ID задачи в переменную.
2. Добавить `TaskChatSendMessage`.
3. Заполнить:
   - `TaskId`: `{=Variable:TaskId}`
   - `SenderId`: конкретный пользователь или поле документа
   - `MessageText`: например
     ```text
     [B]Задача обновлена[/B]
     Ответственный: [USER={=Document:ASSIGNED_BY_ID}]{=Document:ASSIGNED_BY_PRINTABLE}[/USER]
     Подробнее: [URL={=Document:UF_LINK}]{=Document:UF_LINK}[/URL]
     ```
4. После Activity добавить развилку по `IsSuccess`:
   - если `Y` — логировать `MessageId`;
   - если `N` — логировать `ErrorMessage`.

---

## Диагностика и частые проблемы

### 1) Activity есть в списке, но форма пустая
Проверить наличие файла `.properties_dialog.php` и очистить кеш БП/managed cache.

### 2) Сообщение не отправляется
Проверить:
- что модули `tasks` и `im` установлены и активны;
- что `TaskId` существует;
- что `SenderId` — реальный пользователь с правами.

### 3) Чат задачи не найден
Это возможно при ленивой инициализации чатов. Activity уже пытается создать чат автоматически через интеграцию Tasks/IM.

---

## Что важно по совместимости
- Activity не бросает фатальные PHP-ошибки наружу: ошибки переводятся в `IsSuccess = N` + `ErrorMessage`.
- Для последующих действий БП рекомендуется всегда проверять `IsSuccess`.
