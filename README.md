# lara-mcp

Навчальний Laravel-проєкт для MCP (Model Context Protocol). Це застосунок на базі Livewire starter kit: один список завдань, який можна вести у браузері, через чат з AI-помічником і через MCP-клієнт.

Вебсторінки, чат і MCP-сервер читають і змінюють одну таблицю `todos`.

## Що вміє проєкт

- Сторінка `/todos`: створення, редагування, мітки, дата виконання, позначка «виконано» і видалення.
- Сторінка `/chat`: український AI-помічник, який створює, показує і оновлює ті самі завдання.
- MCP-сервер `Todo Server` (версія `0.0.1`):
  - HTTP: `POST /mcp/todo`
  - локальний сервер із іменем `todo` (stdio через `php artisan mcp:start todo`)
- Обліковий запис: реєстрація, вхід, скидання пароля, підтвердження пошти, двофакторна автентифікація і passkeys. Список завдань і чат доступні без входу.

Інтерфейс англійською (`APP_LOCALE=en`). Відповіді чату українською.

## Стек

PHP 8.4, Laravel 13, Livewire 4, Flux, Laravel AI, Laravel MCP, Laravel Reverb, Fortify, Vite, Tailwind CSS. База за замовчуванням: SQLite. Черги, сесії і кеш теж у цій базі. Пошта пишеться в лог.

## Вимоги

- PHP 8.4 або новіший і розширення: `bcmath`, `ctype`, `curl`, `dom`, `fileinfo`, `mbstring`, `openssl`, `pdo`, `pdo_sqlite`, `tokenizer`, `xml`
- Composer 2
- Node.js 22.13 або новіший і npm (команда `php artisan dev` потребує саме цю версію Node)
- Для чату: ключ Gemini (`GEMINI_API_KEY`)
- Для живого потоку відповіді в чаті: Laravel Reverb
- Для варіанта з Docker: Docker і Docker Compose

## Встановлення на своєму комп’ютері

У корені проєкту:

```bash
touch database/database.sqlite
composer setup
```

`composer setup` робить усе інше для першого запуску:

1. `composer install`
2. копіює `.env.example` у `.env`, якщо `.env` ще немає
3. `php artisan key:generate`
4. `php artisan migrate --force`
5. `npm install`
6. `npm run build`

Повторний `composer setup` знову викликає `key:generate` і замінює `APP_KEY`. Для вже налаштованої копії краще ставити залежності окремо: `composer install` і `npm install`.

### Ключ для чату

У `.env` додайте ключ Gemini (Google AI Studio). Без нього список завдань і MCP працюють, а `/chat` відповідає текстом `The assistant could not reply.`

```dotenv
GEMINI_API_KEY=ваш-ключ
```

Провайдер за замовчуванням у `config/ai.php` це `gemini`, модель тексту `gemini-3.1-flash-lite`. Інший провайдер можна вибрати там само і покласти його ключ у `.env` (`OPENAI_API_KEY`, `ANTHROPIC_API_KEY` тощо).

### Reverb поза Docker

У `.env.example` стоїть `REVERB_HOST=reverb`. Це ім’я сервісу в Docker. Якщо сервер запускається прямо на машині, PHP не зможе надіслати трансляцію на хост `reverb`. Для локального запуску змініть:

```dotenv
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST=localhost
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

`REVERB_SERVER_HOST=0.0.0.0` і `REVERB_SERVER_PORT=8080` лишайте: це адреса, на якій слухає сам Reverb. `APP_URL` має збігатися з адресою в браузері, за замовчуванням `http://localhost:8000`.

Після зміни змінних, які бачить Vite (`VITE_*`), перезапустіть `npm run dev` або зберіть ресурси знову: `npm run build`.

### Тестовий користувач

```bash
php artisan db:seed
```

Створюється користувач:

- пошта: `test@example.com`
- пароль: `password`
- пошта вже підтверджена

## Запуск

Одна команда піднімає все потрібне для локальної роботи:

```bash
composer dev
```

Це те саме, що `php artisan dev`. Laravel стартує процеси з таблиці нижче, а пакет Reverb додає до них свій. Список саме цієї копії: `php artisan dev:list`.

| Ім’я | Команда | Навіщо |
| --- | --- | --- |
| `server` | `php artisan serve` | сайт на `http://localhost:8000` |
| `queue` | `php artisan queue:listen --tries=1 --timeout=0` | черги (`QUEUE_CONNECTION=database`) |
| `logs` | `php artisan pail --timeout=0` | логи в терміналі. Потрібне розширення `pcntl`, на Linux воно зазвичай є |
| `vite` | `npm run dev` | стилі і скрипти, зокрема Laravel Echo |
| `reverb` | `php artisan reverb:start` | WebSocket для живого тексту в чаті, порт `8080` |

Зупинка: `Ctrl+C`. Окремий термінал з `php artisan reverb:start` потрібен лише тоді, коли сайт запущено без `composer dev`, наприклад одним `php artisan serve`.

Перевірка, що застосунок живий: [http://localhost:8000/up](http://localhost:8000/up).

Без `composer dev` можна зібрати ресурси один раз (`npm run build`) і запустити лише `php artisan serve`. Тоді стилі будуть зі збірки, а не з гарячого оновлення Vite. Для чату в інших терміналах тримайте `php artisan reverb:start` і `php artisan queue:listen`. Листи в лог пишуться і без черги: поштовий драйвер `log` віддає їх одразу.

## Встановлення через Laravel Sail

Sail є в `require-dev`. PHP на хості все одно потрібен, щоб виконати `composer install`.

```bash
composer install
cp .env.example .env
touch database/database.sqlite
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm install
./vendor/bin/sail npm run dev
```

`compose.yaml` піднімає два сервіси:

- `laravel.test` публікує сайт на порт `${APP_PORT:-80}`, Vite на `${VITE_PORT:-5173}` і порти інспектора MCP `6274` та `6275` лише на `127.0.0.1`
- `reverb` виконує `php artisan reverb:start` і публікує порт `${REVERB_PORT:-8080}`

Усередині мережі Sail хост `reverb` з `.env` правильний, міняти його не треба. Браузер ходить на Reverb через `VITE_REVERB_HOST=localhost`.

Сайт: [http://localhost](http://localhost). Якщо порт 80 зайнятий, перед `sail up` задайте в `.env` наприклад `APP_PORT=8088` і відкривайте `http://localhost:8088`. Тоді оновіть і `APP_URL`.

Далі команди Artisan і npm запускайте через Sail: `./vendor/bin/sail artisan ...`, `./vendor/bin/sail npm ...`. Зупинка: `./vendor/bin/sail stop`.

## Сторінки

| Адреса | Хто бачить | Що там |
| --- | --- | --- |
| `/` | усі | вітальна сторінка Laravel, посилання на Todos, Log in, Register |
| `/todos` | усі | список завдань |
| `/chat` | усі | чат з помічником |
| `/register`, `/login` | гості | реєстрація і вхід, зокрема passkey |
| `/forgot-password` | гості | лист для скидання пароля |
| `/dashboard` | увійшов і підтвердив пошту | порожня панель-заготовка |
| `/settings/profile` | увійшов | ім’я, пошта, видалення облікового запису |
| `/settings/appearance` | увійшов і підтвердив пошту | світла, темна або системна тема |
| `/settings/security` | увійшов, підтвердив пошту і ще раз ввів пароль | пароль, 2FA, passkeys |

На `/todos` і `/chat` шапка веде на Todos, Chat, а для гостя ще на Log in. Після входу з’являється Dashboard.

Листи (підтвердження пошти, скидання пароля) не надсилаються назовні: `MAIL_MAILER=log`. Посилання з листа шукайте в `storage/logs/laravel.log` або у вкладці `logs` команди `composer dev`.

## Як користуватись списком завдань

Відкрийте [http://localhost:8000/todos](http://localhost:8000/todos).

Нове завдання, форма **New todo**:

1. **Title** обов’язковий, до 255 символів.
2. **Notes** не обов’язкові, до 5000 символів.
3. **Due date** не обов’язкова.
4. **Label**: введіть текст і натисніть **Add label**. Порожня мітка і повтор тієї самої мітки ігноруються. Одна мітка до 50 символів. Мітки є лише у вебформі: MCP їх не читає і не записує.
5. **Add todo**. З’явиться повідомлення `Todo created.`

Список ділиться на **To do** і **Completed**. Відкриті йдуть вище виконаних, усередині групи новіші (більший `id`) вище.

Для відкритого завдання:

- **Mark complete** переносить його в Completed
- **Edit** міняє назву, нотатки, дату і мітки, потім **Save** або **Cancel**
- **Delete** питає підтвердження і видаляє запис назавжди

Для виконаного замість **Mark complete** є **Mark incomplete**. Порожні стани: `Nothing left to do.` і `No completed todos yet.`

Видалити завдання можна тільки тут. Інструменти MCP завдання не видаляють.

## Як користуватись чатом

Відкрийте [http://localhost:8000/chat](http://localhost:8000/chat). Потрібні `GEMINI_API_KEY`, запущений сайт і, для потоку тексту, Reverb.

Напишіть повідомлення і натисніть **Send**. Помічник відповідає українською. Він бачить історію лише цієї відкритої сторінки: оновлення сторінки розмову стирає. У базу розмова не пишеться, змінюються тільки завдання.

Помічник сам викликає інструменти списку. Перед створенням або зміною він має спочатку прочитати список, щоб не дублювати завдання і взяти справжній `id`. Він не вигадує `id` і не змінює завдання без запиту.

Приклади:

- `Покажи всі завдання`
- `Додай завдання купити молоко, два літри, на 2026-10-30`
- `Познач «купити молоко» виконаним`
- `Прибери дату в завдання 3`
- `Склади план з відкритих завдань`

Якщо `composer dev` працює, текст відповіді з’являється поступово. Якщо в картці чату написано `Broadcasting is not connected.`, фронтенд зібрався без Echo: запустіть `composer dev` або `npm run dev`. Якщо цього напису немає, але текст приходить лише цілком наприкінці запиту, Reverb не приймає з’єднання: у `php artisan dev:list` має бути процес `reverb`, а поза Docker `REVERB_HOST` має бути `127.0.0.1`.

Помилка провайдера або порожній ключ дають одну фразу помічника: `The assistant could not reply.` Деталі винятку падають у лог.

## Обліковий запис

Реєстрація на `/register`: ім’я, пошта, пароль. Поки пошта не підтверджена, `/dashboard`, тема і безпека недоступні. Посилання підтвердження лежить у логу.

Вхід на `/login`: пошта і пароль або passkey. Passkey прив’язаний до `APP_URL`, тому відкривайте сайт рівно з тієї адреси, що в `APP_URL` (для локальної копії це `http://localhost:8000`, не `127.0.0.1`, якщо в `.env` інший хост).

**Settings → Profile**: ім’я і пошта. Зміна пошти знову вимагає підтвердження. Внизу можна видалити обліковий запис.

**Settings → Appearance**: Light, Dark, System. Вибір зберігається в браузері.

**Settings → Security** спочатку просить поточний пароль. Далі можна змінити пароль, увімкнути двофакторну автентифікацію (застосунок-автентифікатор і коди відновлення) і зареєструвати passkey. Вимкнення 2FA і керування ключами теж на цій сторінці.

Вихід: меню користувача на Dashboard, пункт logout.

## MCP

Сервер зареєстрований у `routes/ai.php`. Файл підхоплює Laravel MCP, у `bootstrap/app.php` його прописувати не треба.

```php
Mcp::web('/mcp/todo', TodoServer::class);
Mcp::local('todo', TodoServer::class);
```

Обидва способи працюють з тією самою базою, що й сайт. Окремої авторизації на `/mcp/todo` немає: хто досягає процесу або HTTP-порту, той може змінювати список. Для навчання на `localhost` цього достатньо.

`php artisan mcp:start todo` сам у терміналі не запускайте. Команда чекає JSON-RPC на stdin і виглядає як зависла. Її має стартувати MCP-клієнт.

### Локальний сервер у Cursor

Створіть `.cursor/mcp.json` у корені проєкту (файл у `.gitignore`) або додайте сервер у налаштуваннях Cursor. `artisan` беріть абсолютним шляхом, тоді робочий каталог клієнта не важливий.

```json
{
  "mcpServers": {
    "todo": {
      "command": "php",
      "args": ["/АБСОЛЮТНИЙ/ШЛЯХ/ДО/ПРОЄКТУ/artisan", "mcp:start", "todo"]
    }
  }
}
```

Після збереження ввімкніть сервер `todo` у списку MCP. Сайт для цього способу може бути вимкнений: Artisan піднімає застосунок сам і ходить у SQLite.

Той самий блок `mcpServers` підходить для Claude Desktop та інших клієнтів зі stdio.

### HTTP-сервер

Спочатку має працювати сайт (`composer dev` або `php artisan serve`). У клієнті, який уміє streamable HTTP:

```json
{
  "mcpServers": {
    "todo": {
      "url": "http://127.0.0.1:8000/mcp/todo"
    }
  }
}
```

Під Sail замініть порт на той, де відкритий сайт (`http://127.0.0.1/mcp/todo`, якщо це порт 80).

### Інспектор

Інспектор відкриває вебінтерфейс, де можна викликати інструменти і промпти. Потрібні Node і `npx`.

```bash
php artisan mcp:inspector todo
php artisan mcp:inspector mcp/todo
```

Перша команда для локального сервера `todo`, друга для HTTP-маршруту. Для HTTP сайт уже має слухати порт. Під Sail: `./vendor/bin/sail artisan mcp:inspector todo`. Команда друкує налаштування, які можна перенести в клієнт.

### Інструменти

Імена збігаються з тими, що бачить клієнт.

**list-todos-tool.** Лише читання. Повертає завдання. Без `completed` віддає всі: спочатку відкриті, потім виконані, усередині групи за спаданням `id`.

| Поле | Тип | Обов’язкове | Зміст |
| --- | --- | --- | --- |
| `completed` | boolean | ні | `true` лише виконані, `false` лише відкриті. Пропустіть поле, щоб отримати весь список |

Відповідь: `todos`, масив об’єктів `id`, `title`, `description`, `due_date` (`YYYY-MM-DD` або `null`), `completed`. Міток у відповіді немає.

**create-todo-tool.** Створює відкрите завдання. Перед викликом варто прочитати список, щоб не додати дублікат.

| Поле | Тип | Обов’язкове | Зміст |
| --- | --- | --- | --- |
| `title` | string | так | до 255 символів, пробіли по краях зрізаються, порожній рядок відхиляється |
| `description` | string або null | ні | до 5000 символів, порожній рядок зберігається як `null` |
| `due_date` | string або null | ні | `YYYY-MM-DD`, порожній рядок зберігається як `null` |

Відповідь: створене завдання з тими самими полями і `completed: false`.

**update-todo-tool.** Повторний виклик з тими самими даними безпечний. Потрібен `id` зі списку і хоча б одне поле для зміни. Якщо передати лише `id`, інструмент відповість помилкою.

| Поле | Тип | Обов’язкове | Зміст |
| --- | --- | --- | --- |
| `id` | integer | так | наявний запис у `todos` |
| `title` | string | ні | нові назва, ті самі обмеження, що при створенні |
| `description` | string або null | ні | новий опис; `null` або порожній рядок очищає опис |
| `due_date` | string або null | ні | нова дата `YYYY-MM-DD`; `null` прибирає дату |
| `completed` | boolean | ні | `true` виконано, `false` відкрити знову |

Відповідь: завдання після зміни.

### Промпти

Промпт не змінює базу сам. Він віддає клієнту інструкцію, а клієнт уже викликає інструменти.

**capture-todo-prompt.** Записати чернетку новим відкритим завданням. Аргумент `note` (обов’язковий, до 5000 символів) і необов’язковий `due_date` (`YYYY-MM-DD`). Клієнт має спочатку викликати `list-todos-tool`. Якщо таке завдання вже є, нове не створюється. Інакше `create-todo-tool`: коротка назва, решта в опис.

**plan-open-todos-prompt.** План лише з відкритих завдань. Необов’язковий `focus`, наприклад `робота` або `дім`, до 255 символів. Клієнт викликає `list-todos-tool` з `completed: false` і нічого не створює і не змінює. Якщо відкритих немає, так і каже.

**complete-todo-prompt.** Позначити наявне завдання виконаним. Аргумент `task` (обов’язковий, до 255 символів): назва або опис. Клієнт шукає відкрите завдання через `list-todos-tool` і викликає `update-todo-tool` з його `id` і `completed: true`. Кілька збігів: перепитує. Жодного: каже, що не знайшов. Нове завдання не створює.

### Що MCP не робить

- не ставить і не читає мітки `label`
- не видаляє завдання
- не бачить розмову з `/chat`

Мітки, поставлені на сайті, у базі лишаються. Наступне оновлення через MCP їх не затирає, бо інструмент це поле не надсилає.

## Корисні команди

```bash
php artisan migrate
php artisan db:seed
php artisan tinker
composer lint
composer lint:check
composer types:check
composer test
```

`composer test` очищає конфіг, перевіряє стиль Pint, проганяє PHPStan (рівень 7) і PHPUnit. Тести самі піднімають окрему базу `testing` і не чіпають `database/database.sqlite`.

Окремо: `php artisan test`, `./vendor/bin/pint`, `./vendor/bin/phpstan analyse`.

## Дані завдання

Таблиця `todos`:

| Поле | Тип | Зміст |
| --- | --- | --- |
| `id` | integer | ключ |
| `title` | string | назва |
| `description` | text, null | нотатки |
| `label` | JSON-масив рядків | мітки, за замовчуванням `[]`, лише веб |
| `due_date` | date, null | дата виконання |
| `completed` | boolean | за замовчуванням `false` |
| `created_at`, `updated_at` | timestamp | службові |

## Якщо щось не працює

- **500 одразу після клону.** Немає `.env`, `APP_KEY` або файлу бази. `touch database/database.sqlite`, потім `composer setup` на чистій копії.
- **Помилка сесій або кешу.** Не виконані міграції. `php artisan migrate`. Сесії, кеш і черги лежать у SQLite, не в файлах.
- **Стилі «розсипані».** Не зібраний фронтенд. `composer dev` або `npm run build`.
- **Чат одразу здається.** Порожній або невірний `GEMINI_API_KEY`, або провайдер відхилив запит. Дивіться лог.
- **Немає потоку відповіді.** Процес `reverb` не входить у `php artisan dev:list`, або поза Docker лишився `REVERB_HOST=reverb`. Окремо: `php artisan reverb:start`.
- **`mcp:start` мовчить.** Так і має бути. Підключайте клієнт або інспектор.
- **Клієнт пише, що сервера `todo` немає.** Дивіться реєстрацію в `routes/ai.php` і що `args` вказують на `artisan` цього проєкту.
- **Лист не прийшов.** Він у `storage/logs/laravel.log`.
