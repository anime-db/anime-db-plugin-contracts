# anime-db/plugin-contracts

Контракты плагинов (интерфейсы и DTO) для плагинов AnimeDB v2.

Этот пакет определяет только контракт между хост-приложением AnimeDB и его
плагинами: интерфейсы и плоские DTO. Он не содержит ни реализации плагинов,
ни бизнес-логики хост-приложения.

## Установка

```bash
composer require anime-db/plugin-contracts
```

Пакет пока не опубликован на Packagist — до публикации добавьте в свой
`composer.json` VCS-репозиторий:

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

### `ExternalIdResolutionInterface`

Способность резолвить собственный внешний id плагина. Единственный метод —
`resolveExternalId()` — нужен интерфейсам, которым требуется эта способность:
`SearchByPluginInterface`, `SyncInterface`, `EntryWidgetInterface`,
`CatalogWidgetInterface` и транзитивно `FillerInterface`. Интерфейс называет
способность, а не категорию плагина — по этой же причине его **не**
реализует `DownloadCandidateSearchInterface`: `search()` принимает
свободный текстовый запрос, а не список ссылок, а идентичность кандидата
несёт `AnimeSearchResultItem::$externalId`.

Это **не** общий предок всех плагинов: плагин, который реагирует на события
каталога и не обращается ни к какому внешнему источнику (`type: local` в
манифесте), не реализует этот интерфейс — `resolveExternalId()` ему не нужен.
Категория «интеграция» живёт только в манифестном `type`
(`integration`/`translation`/`local`), кодового маркера-категории нет.
Перечисление установленных плагинов делается из манифестов, а не из
маркер-интерфейса.

```php
use AnimeDb\PluginContracts\ExternalIdResolutionInterface;

class MySourcePlugin implements ExternalIdResolutionInterface
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
    // не из контракта: ExternalIdResolutionInterface такого метода не предоставляет.
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

Расширяет `FillerInterface`: pull() создаёт локальные записи из списка
пользователя на внешнем источнике, а «голая» запись с одним заголовком —
невалидная карточка. Sync-плагин обязан уметь и заполнять карточку
(`find()`, `findById()`, `getFillableFields()`), поэтому sync-плагина без
филлера не существует на уровне контракта.

```php
use AnimeDb\PluginContracts\PluginAnimeData;
use AnimeDb\PluginContracts\SearchByPluginCandidate;
use AnimeDb\PluginContracts\SyncInterface;
use AnimeDb\PluginContracts\SyncItem;
use AnimeDb\PluginContracts\SyncStatus;

class MySourcePlugin implements SyncInterface
{
    // ... find(), findById(), getFillableFields() как у FillerInterface

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

// Виджет на странице отдельной записи каталога — получает внешний id,
// уже резолвнутый хостом через resolveExternalId() этого же плагина.
class RelatedTitlesWidget implements EntryWidgetInterface
{
    public function resolveExternalId(array $urls): ?string
    {
        // ...
    }

    public function render(?string $externalId): string
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

Виджет на странице записи живёт в пространстве внешнего источника, а не
локальной БД хоста: `resolveExternalId()`, унаследованный от
`ExternalIdResolutionInterface`, резолвится хостом заранее, и результат
передаётся в `EntryWidgetInterface::render()` как `?string $externalId` —
`null`, если запись не сопоставлена с источником плагина.

### `LlmServiceInterface`

Сервис ядра, дающий плагину доступ к локальной LLM. Мотив: некоторые
источники отдают метаданные свободным человеческим текстом (например,
описание релиза на форуме, где каждый автор пишет как хочет), и парсер
такого текста в структурированные данные должен жить в ядре одним
экземпляром, а не дублироваться в каждом плагине.

```php
use AnimeDb\PluginContracts\LlmServiceInterface;

class MyForumFillerPlugin
{
    public function __construct(
        private readonly LlmServiceInterface $llm,
    ) {
    }

    private function parseReleasePost(string $rawText): array
    {
        $prompt = "Верни JSON с полями title, episodesCount, genres. Текст поста:\n".$rawText;

        return $this->llm->parse($prompt);
    }
}
```

Плагин сам формирует промпт (в том числе явно просит вернуть JSON), ядро
прогоняет локальную модель, дополнительно подкрепляет JSON-режим своим
системным промптом и декодит ответ в ассоциативный массив. Плагин получает
сервис через DI по тому же принципу, что и PSR-18 HTTP-клиент (см. ниже):
type-hint интерфейса в конструкторе — реализация целиком на стороне
хост-приложения. Плагин, которому нужен этот сервис, декларирует это в
`manifest.json` флагом `features.llm` (см. раздел «Манифест плагина»).

### `DownloadCandidateSearchInterface`

Интерактивный пользовательский поиск скачиваемых кандидатов по внешнему
источнику — отдельная функция от `SearchByPluginInterface`. Разница:
`SearchByPluginInterface` — лёгкое распознавание тайтла при сканировании
тысяч папок (`SearchByPluginCandidate`: id плагина + название + внешний
id). `DownloadCandidateSearchInterface` — по явному запросу пользователя,
возвращает богатые элементы для показа в UI и постановки действий над
ними (в т.ч. «скачать»).

```php
use AnimeDb\PluginContracts\AnimeId;
use AnimeDb\PluginContracts\AnimeSearchResult;
use AnimeDb\PluginContracts\AnimeSearchResultAction;
use AnimeDb\PluginContracts\AnimeSearchResultItem;
use AnimeDb\PluginContracts\DownloadCandidateSearchInterface;
use AnimeDb\PluginContracts\DownloadServiceInterface;
use AnimeDb\PluginContracts\DownloadSource;

class ExampleDownloadSearchPlugin implements DownloadCandidateSearchInterface
{
    public function __construct(
        private readonly DownloadServiceInterface $downloads,
    ) {
    }

    public function search(string $query): AnimeSearchResult
    {
        $items = [];

        foreach ($this->fetchResults($query) as $candidate) {
            $items[] = new AnimeSearchResultItem(
                title: $candidate->title,
                externalId: $candidate->id, // сводит кандидата к записи каталога
                image: $candidate->coverBase64, // плагин сам фетчит и уменьшает превью
                fields: ['quality' => $candidate->quality, 'size' => $candidate->size],
                actions: [new AnimeSearchResultAction('download', 'Скачать')],
                meta: $candidate->source, // непрозрачно для ядра, вернётся как есть в runAction()
            );
        }

        return new AnimeSearchResult($items);
    }

    public function runAction(string $actionId, string $meta, AnimeId $anime): void
    {
        if ($actionId === 'download') {
            $this->downloads->enqueue(DownloadSource::magnet($meta), $anime);
        }
    }
}
```

#### `AnimeSearchResult` / `AnimeSearchResultItem` / `AnimeSearchResultAction`

Нейтральные, аниме-типизированные DTO без семантики конкретного источника:

- `AnimeSearchResult::$items` — список `AnimeSearchResultItem`.
- `AnimeSearchResultItem` — `title`, `externalId` (id кандидата на
  источнике, по которому кандидат сводится к записи каталога — новой или
  уже существующей; если у источника нет канонического id, крайний
  фолбэк — стабильный хеш от magnet/торрент-файла), `image`
  (превью-обложка в base64 или `null`, плагин фетчит и уменьшает её сам —
  клиент не ходит во внешнюю сеть напрямую), `fields` (произвольные
  визуализируемые доп. поля, `label => value`), `actions` (список
  `AnimeSearchResultAction`), `meta` (непрозрачная для ядра строка,
  специфичная для плагина).
- `AnimeSearchResultAction` — `id` действия и `label` для показа
  пользователю.

`meta` ядро не интерпретирует — только переносит и возвращает плагину
как есть при вызове `runAction()`. Всё специфичное для источника (как
понимать `fields`, что делает конкретное действие) — на стороне плагина.

**Про защиту `meta`:** DTO уходит на клиента (в т.ч. мобильного) и
возвращается обратно при `runAction()`. Ядро оборачивает `meta` в
подписанный конверт (HMAC на секрете приложения) на отдаче и верифицирует
на возврате — защита от подмены на клиенте. Это ответственность
хост-приложения; на уровне контракта `meta` — просто строка.

### `DownloadServiceInterface`

Сервис ядра для постановки задачи на скачивание. Плагин не работает с
менеджером загрузок и очередью напрямую — только просит поставить задачу
и получает `DownloadTaskId`, который сохраняет в свой срез метаданных
аниме (закачка идёт долго, и `DownloadCompletedEvent` может прийти уже
после перезапуска приложения, поэтому хранить id только в памяти нельзя).

```php
use AnimeDb\PluginContracts\AnimeId;
use AnimeDb\PluginContracts\DownloadCompletedEvent;
use AnimeDb\PluginContracts\DownloadServiceInterface;
use AnimeDb\PluginContracts\DownloadSource;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExampleDownloadPlugin implements EventSubscriberInterface
{
    public function __construct(
        private readonly DownloadServiceInterface $downloads,
    ) {
    }

    public function startDownload(string $magnetUri, AnimeId $anime): void
    {
        $taskId = $this->downloads->enqueue(DownloadSource::magnet($magnetUri), $anime);

        // сохранить $taskId в собственный срез метаданных $anime
    }

    public static function getSubscribedEvents(): array
    {
        return [DownloadCompletedEvent::class => 'onDownloadCompleted'];
    }

    public function onDownloadCompleted(DownloadCompletedEvent $event): void
    {
        // сопоставить $event->task с сохранённым ранее id;
        // если совпало — обогатить карточку из скачанных файлов
        // (тяжёлую работу увести в свою фоновую задачу)
    }
}
```

#### `DownloadSource`

Именованные конструкторы с самовалидацией — они же документируют, что
вообще поддерживается:

- `DownloadSource::magnet(string $uri): self` — валидирует форму
  `magnet:?xt=urn:btih:...`.
- `DownloadSource::torrentFile(string $path): self` — валидирует, что
  путь оканчивается на `.torrent`.

Обе выбрасывают `\InvalidArgumentException` при некорректном значении.
Набор расширяемый — например, `::url()` в будущем — без изменения уже
написанных плагинов.

#### `AnimeId` / `DownloadTaskId` / `DownloadCompletedEvent`

`AnimeId` — id записи каталога, `DownloadTaskId` — id поставленной
задачи, возвращаемый `enqueue()`. `DownloadCompletedEvent` — событие
завершения закачки (`$anime`, `$task`), на которое плагин подписывается
штатным Symfony `EventSubscriberInterface`: это обычный класс, без
привязки к базовому классу события Symfony — диспетчеру достаточно имени
класса, чтобы разослать событие подписчикам.

### PSR-18 HTTP-клиент

Плагины не создают HTTP-клиент сами — получают **преднастроенный**
PSR-18 `Psr\Http\Client\ClientInterface` через DI (type-hint в
конструкторе). Это позволяет включать прокси централизованно, в фабрике
клиента на стороне хост-приложения, прозрачно для плагинов. Отдельного
интерфейса в этом пакете для этого не заводится — соглашение, не
контрактный тип.

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
    "features": {"filler": true, "related_widget": true, "sync": true, "llm": true},
    "require": {
        "core": ">=2.0.0",
        "php": ">=8.2",
        "plugin-contracts": "^2.0"
    },
    "update_url": "https://example.com/plugins/registry.json"
}
```

```php
use AnimeDb\PluginContracts\Manifest\InvalidManifestException;
use AnimeDb\PluginContracts\Manifest\InvalidManifestJsonException;
use AnimeDb\PluginContracts\Manifest\ManifestParser;

$parser = new ManifestParser();

try {
    // parse() сам декодирует и валидирует содержимое — отдельно вызывать
    // decode()/validate() перед ним не нужно.
    $manifest = $parser->parse($rawManifestJson);
} catch (InvalidManifestJsonException $exception) {
    // $rawManifestJson — не валидный JSON или JSON не-объект на верхнем уровне.
    return;
} catch (InvalidManifestException $exception) {
    foreach ($exception->errors as $error) {
        // $error->field — dot-path, например "require.core" или "features.filler"
        // $error->message — человекочитаемая причина
        printf("%s: %s\n", $error->field, $error->message);
    }

    return;
}
```

`decode()` и `ManifestValidator::validate()` остаются публичными отдельно от
`parse()` — для потребителей (например, UI клиентского инсталлятора),
которым нужен полный список ошибок валидации ещё до того, как решать,
вызывать ли `parse()` вообще:

```php
use AnimeDb\PluginContracts\Manifest\ManifestValidator;

$data = $parser->decode($rawManifestJson); // бросает InvalidManifestJsonException
$errors = (new ManifestValidator())->validate($data);
```

Обязательные поля манифеста — `id`, `name`, `version`, `type`. `type` —
закрытый словарь (`PluginType::Integration`, `PluginType::Translation` или
`PluginType::Local`): обычный код-плагин, интегрирующийся с внешним
источником, объявляет `features` (плоский набор булевых флагов); чисто
декларативный ресурс переводов — `locales` (список кодов локалей) вместо
`features`; код-плагин `local`, реагирующий на события каталога и не
ходящий в сеть (реализует Symfony `EventSubscriberInterface`, не
`ExternalIdResolutionInterface`), не объявляет ни `features`, ни `locales`.

Пакет не проверяет конкретные ключи `features` (кроме того, что значения —
булевы) — это открытый набор флагов по соглашению между плагином и хостом.
Флаг `llm: true` — плагин декларирует, что ему нужен `LlmServiceInterface`.

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

Проверяет, что классы, объявляющие реализацию `ExternalIdResolutionInterface`
(и всех интерфейсов, которые его расширяют: `FillerInterface`,
`SearchByPluginInterface`, `SyncInterface`, `CatalogWidgetInterface`,
`EntryWidgetInterface`) или `DownloadCandidateSearchInterface`, имеют
сигнатуры методов, точно совпадающие с сигнатурами из установленной
версии этого пакета. Ловит рассинхронизацию между версией контракта, под
которую написан плагин, и версией, реально установленной у потребителя —
то, что одна успешная компиляция DI-контейнера может не заметить.
