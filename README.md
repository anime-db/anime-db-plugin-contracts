# anime-db/plugin-contracts

Контракты плагинов (интерфейсы и DTO) для плагинов AnimeDB v2.

Этот пакет определяет только контракт между хост-приложением AnimeDB и его
плагинами: интерфейсы и плоские DTO. Он не содержит ни реализации плагинов,
ни бизнес-логики хост-приложения.

## Установка

```bash
composer require anime-db/plugin-contracts
```

Пакет пока не опубликован на Packagist и лежит в приватном репозитории —
до публикации добавьте в свой `composer.json` VCS-репозиторий и настройте
аутентификацию Composer для приватного GitHub-репозитория:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/anime-db/anime-db-plugin-contracts.git"
        }
    ]
}
```

## Интерфейсы

### `PluginInterface`

Общий предок всех остальных интерфейсов контракта. Единственный метод —
`resolveExternalId()` — нужен любому типу плагина одинаково, а заодно
служит общим маркером/тегом для реестра плагинов.

```php
use AnimeDb\PluginContracts\PluginInterface;

class MySourcePlugin implements PluginInterface
{
    public function resolveExternalId(array $urls): ?string
    {
        foreach ($urls as $url) {
            if (preg_match('#^https://my-source\.example/anime/(\d+)$#', $url, $m)) {
                return $m[1];
            }
        }

        return null;
    }
}
```

Плагин получает список **всех** внешних ссылок, уже привязанных к записи
каталога, сам разбирает их по паттерну URL своего вендора и возвращает
найденный id или `null`, если среди ссылок нет «своей». Хост-приложению не
нужно знать, какая ссылка какому плагину принадлежит — это делегировано
плагину.

### `SearchByPluginInterface`

Реализуется плагинами, которые умеют искать/сопоставлять тайтл с внешним
источником.

```php
use AnimeDb\PluginContracts\SearchByPluginCandidate;
use AnimeDb\PluginContracts\SearchByPluginInterface;

class MySourcePlugin implements SearchByPluginInterface
{
    // Собственный, известный только этому плагину id — из его manifest.json,
    // не из контракта: PluginInterface такого метода не предоставляет.
    private const ID = 'my-vendor-my-source';

    public function resolveExternalId(array $urls): ?string
    {
        // ...
    }

    public function find(string $name, ?callable $onHeartbeat = null): array
    {
        $candidates = [];

        foreach ($this->fetchPages($name) as $page) {
            // Let the caller know the search is still progressing, e.g. to
            // refresh a background job lock. Safe to skip if not given.
            if ($onHeartbeat !== null) {
                $onHeartbeat();
            }

            foreach ($page as $match) {
                $candidates[] = new SearchByPluginCandidate(
                    pluginId: self::ID,
                    name: $match->title,
                    externalId: $match->id,
                );
            }
        }

        return $candidates;
    }
}
```

#### `SearchByPluginCandidate`

Одно совпадение поиска. Намеренно лёгкий класс, чтобы его было дёшево
создавать в большом количестве, пока плагин обходит крупный внешний каталог:

- `getPluginId()` — id плагина, который создал кандидата (значение задаёт
  сам плагин/вызывающая сторона — контракт не диктует, откуда оно берётся).
- `getName()` — найденное название, как оно указано во внешнем источнике.
- `getExternalId()` — внешний id найденной записи, чтобы вызывающая сторона
  могла выполнить последующий `findById()` без дополнительного поиска.

#### `$onHeartbeat`

`find()` принимает опциональный heartbeat-колбэк типа `callable(): void`.
Плагин *может* вызывать его между внутренними шагами (страницы пагинации,
повторные попытки) долгого поиска; делать это не обязательно, а вызывающая
сторона, которой это не нужно, просто не передаёт аргумент (`null`).

### `FillerInterface`

Расширяет `SearchByPluginInterface` — плагин, умеющий заполнять карточку,
обязан уметь и искать (`find()` переиспользуется как есть, отдельного
метода поиска с полными объектами нет).

```php
use AnimeDb\PluginContracts\FillerInterface;
use AnimeDb\PluginContracts\PluginAnimeData;

class MySourcePlugin implements FillerInterface
{
    // ... find(), resolveExternalId() как у SearchByPluginInterface

    public function findById(string $externalId): ?PluginAnimeData
    {
        $data = $this->fetchDetails($externalId);

        return $data === null ? null : new PluginAnimeData(
            title: $data->title,
            genres: $this->mapGenres($data->genres),
            episodesCount: $data->episodes,
            // остальные поля — по мере доступности у источника
        );
    }

    public function getFillableFields(): array
    {
        return ['title', 'genres', 'episodesCount'];
    }
}
```

`findById()` возвращает полностью заполненный `PluginAnimeData` по
известному внешнему id — результат кэшируется на стороне вызывающего кода.
Точечное заполнение одного поля — не отдельный метод плагина, а обёртка
хост-приложения над этим же вызовом: плагин не обязан знать про
«заполнение одного поля».

`getFillableFields()` — список полей `PluginAnimeData`, которые плагин
реально умеет заполнять; хост использует его, чтобы показать кнопки
«заполнить из источника» только для поддерживаемых полей.

#### `PluginAnimeData`

Плоский DTO с данными, заполненными плагином из внешнего источника.
Специально не привязан ни к какой ORM-сущности хост-приложения:
обязательно только `title`, всё остальное — nullable и может быть не
заполнено плагином, который этого не поддерживает. Закрытые словари
(жанр, тема, демография, тип) — контрактные enum'ы (`GenreCode`,
`ThemeCode`, `Demographic`, `AnimeType`), а не произвольные строки, чтобы
не терять типобезопасность при развязке с внутренними enum'ами
хост-приложения.

Поля: `title`, `alternativeNames`, `descriptions`, `genres`, `themes`,
`demographic`, `studios`, `type`, `datePremiere`, `dateEnd`,
`durationMinutes`, `episodesCount`, `countries`, `cover`, `images`.

### `SyncInterface`

Синхронизация пользовательских списков между хост-приложением и внешним
источником. В отличие от интерактивных Filler/Widget/Search, sync
настраивается один раз и работает в фоне — по двум независимым
направлениям.

```php
use AnimeDb\PluginContracts\SyncInterface;
use AnimeDb\PluginContracts\SyncItem;
use AnimeDb\PluginContracts\SyncStatus;

class MySourcePlugin implements SyncInterface
{
    public function push(SyncItem $item): void
    {
        // отправить изменение статуса на внешний источник
    }

    public function pull(): iterable
    {
        foreach ($this->fetchUserList() as $entry) {
            yield new SyncItem(
                externalId: $entry->id,
                status: SyncStatus::Watching, // нормализовано из словаря источника
                title: $entry->title,
            );
        }
    }
}
```

`SyncStatus` — закрытый словарь статусов просмотра, общий для
хост-приложения и всех sync-плагинов: `Plan`, `Watching`, `Completed`,
`Dropped`, `OnHold`. Плагин нормализует собственный словарь источника
(например, `"watching"`/`"completed"` MAL) в этот enum один раз, в своём
адаптере; хост-приложение затем сопоставляет его со своим внутренним
представлением статуса.

### `CatalogWidgetInterface` и `EntryWidgetInterface`

Два отдельных интерфейса для двух разных контекстов размещения виджета —
с разным входом, поэтому не один интерфейс с опциональным параметром:

```php
use AnimeDb\PluginContracts\CatalogWidgetInterface;
use AnimeDb\PluginContracts\EntryWidgetInterface;

// Виджет на общей странице каталога (например, "новинки") — без контекста.
class NewReleasesWidget implements CatalogWidgetInterface
{
    public function resolveExternalId(array $urls): ?string
    {
        return null; // виджету не нужен свой внешний id
    }

    public function render(): string
    {
        return '<div class="new-releases">...</div>';
    }
}

// Виджет на странице отдельной записи каталога — получает id записи.
class RelatedTitlesWidget implements EntryWidgetInterface
{
    public function resolveExternalId(array $urls): ?string
    {
        // ...
    }

    public function render(string $localId): string
    {
        return '<div class="related-titles">...</div>';
    }
}
```

Плагин может объявлять несколько виджетов — по одному классу на виджет,
каждый переключается независимо в UI хост-приложения. Вывод — голая
HTML-строка, не структурированные данные: это покрывает и случаи, когда
виджет — не список записей каталога. Визуальная консистентность для
частого случая «список записей» — опциональный хелпер на стороне
хост-приложения, не жёсткая схема в этом контракте.

Виджету, которому нужен свой внешний id источника (чтобы сходить в свой
API), доступен `resolveExternalId()`, унаследованный от `PluginInterface`:
вызывающий код резолвит и кэширует id на своей стороне перед вызовом
`render()`.

## Закрытые словари (enum'ы)

Значения синхронизированы 1:1 со словарями MyAnimeList; пакет не зависит
от внутренних enum'ов хост-приложения — сопоставление на его стороне.

- **`AnimeType`** — тип тайтла: `Tv`, `Movie`, `Ova`, `Ona`, `Special`, `Music`.
- **`Demographic`** — демографическая ось MAL: `Shounen`, `Shoujo`, `Seinen`, `Josei`, `Kids`.
- **`GenreCode`** — ось жанров MAL (18 значений, например `Action`, `Comedy`, `Fantasy`, `SliceOfLife`) — список может расширяться минорными версиями.
- **`ThemeCode`** — ось тем MAL (51 значение, например `Isekai`, `Mecha`, `School`, `TimeTravel`) — список может расширяться минорными версиями.

## Манифест плагина (`manifest.json`)

Пакет также содержит общий парсер и валидатор файла `manifest.json`,
который каждый плагин несёт в корне своего ZIP-архива. Компонент нужен,
чтобы не дублировать логику разбора и проверки манифеста в двух местах:
CI реестра маркета (перед приёмом плагина в статический реестр) и
клиентский инсталлятор хост-приложения (перед установкой кастомного ZIP
из недоверенного источника).

```json
{
    "id": "vendor-shikimori",
    "name": "Shikimori",
    "version": "1.0.0",
    "description": "Описание плагина",
    "author": "Vendor Name",
    "type": "integration",
    "features": {"filler": true, "related_widget": true, "sync": true},
    "require": {
        "core": ">=2.0.0",
        "php": ">=8.2",
        "plugin-contracts": "^2.0"
    },
    "update_url": "https://example.com/plugins/registry.json"
}
```

```php
use AnimeDb\PluginContracts\Manifest\ManifestParser;
use AnimeDb\PluginContracts\Manifest\ManifestValidator;

$parser = new ManifestParser();
$validator = new ManifestValidator();

// decode() отделён от parse(): он бросает InvalidManifestJsonException только
// при синтаксически невалидном JSON или JSON не-объекте на верхнем уровне.
$data = $parser->decode($rawManifestJson);

$errors = $validator->validate($data);
if ($errors !== []) {
    foreach ($errors as $error) {
        // $error->field — dot-path, например "require.core" или "features.filler"
        // $error->message — человекочитаемая причина
        printf("%s: %s\n", $error->field, $error->message);
    }

    return;
}

// parse() безопасен только после того, как validate() не вернул ошибок —
// сам он повторную валидацию содержимого не делает.
$manifest = $parser->parse($rawManifestJson);
```

Обязательные поля манифеста — `id`, `name`, `version`, `type`. `type` —
закрытый словарь (`PluginType::Integration` или `PluginType::Translation`):
обычный код-плагин объявляет `features` (плоский набор булевых флагов),
чисто декларативный ресурс переводов — `locales` (список кодов локалей)
вместо `features`.

`require.core` и `require.php` — только нижняя граница версии (`>=X.Y.Z`,
без верхней границы: верхнюю границу совместимости определяет отдельный
эмпирический механизм реестра, не сам манифест). `require.plugin-contracts`
— опциональный, более точный сигнал совместимости, чем версия ядра целиком,
и допускает любой валидный семвер-констрейнт (например, `^2.0`). Синтаксис
всех трёх констрейнтов проверяется через `composer/semver`.

## PHPStan-правила

Пакет поставляет набор правил PHPStan, которыми переиспользуют и CI реестра
плагинов (при приёме сторонних плагинов), и клиентская валидация
хост-приложения (при установке кастомного ZIP-плагина) — одна и та же
логика проверки в одном месте.

Пакет не тянет `phpstan/phpstan` как рантайм-зависимость — PHPStan нужен
только тем, кто реально подключает эти правила. Чтобы включить их, в своём
проекте установите `phpstan/phpstan` и [`phpstan/extension-installer`](https://github.com/phpstan/extension-installer);
после этого правила подключатся автоматически, как только `anime-db/plugin-contracts`
окажется среди зависимостей проекта (в `require` или `require-dev`).
Без `extension-installer` — добавьте `extension.neon` пакета в свой `phpstan.neon` вручную:

```neon
includes:
    - vendor/anime-db/plugin-contracts/extension.neon
```

### `NoDangerousPrimitivesRule`

Запрещает плагину напрямую вызывать низкоуровневые примитивы, которые
должны идти через абстракции хост-приложения:

- запуск процессов и сырых сетевых сокетов — всегда, независимо от
  аргументов: `exec`, `shell_exec`, `passthru`, `system`, `popen`,
  `proc_open`, `proc_close`, `pcntl_exec`, `fsockopen`, `pfsockopen`,
  `stream_socket_client`, `stream_socket_server`, любые функции `curl_*`;
- чтение/включение файла со статически известным URL (схема обёртки вида
  `https://`, `ftp://` и т.п. в первом аргументе): `file_get_contents`,
  `fopen`, `copy`, `file`, `readfile`, `include`/`include_once`/
  `require`/`require_once`. Вызов с локальным путём (например, конфигом
  или шаблоном плагина) не запрещён — правило целится в скрытый сетевой
  запрос под видом файловой операции, а не в файловый ввод-вывод как
  таковой;
- оператор обратных кавычек (`` `command` ``) и `eval`;
- динамические вызовы через переменные-функции (`$fn()`, но не через
  array-callable вида `[$this, 'method']`) и переменные-переменные
  (`$$name`).

Правило не защищает от намеренного обфусцированного обхода — оно поднимает
планку для типичного случая и служит автоматическим гейтом перед публикацией
плагина в реестре.

### `ContractConformanceRule`

Проверяет, что классы, объявляющие реализацию `PluginInterface` (и всех
интерфейсов, которые его расширяют: `FillerInterface`,
`SearchByPluginInterface`, `SyncInterface`, `CatalogWidgetInterface`,
`EntryWidgetInterface`), имеют сигнатуры методов, точно совпадающие с
сигнатурами из установленной версии этого пакета. Ловит рассинхронизацию
между версией контракта, под которую написан плагин, и версией, реально
установленной у потребителя — то, что одна успешная компиляция
DI-контейнера может не заметить.
