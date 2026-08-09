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

## Оглавление

- [Что реализует плагин](#что-реализует-плагин)
  - [`ExternalIdResolutionInterface`](#externalidresolutioninterface)
  - [`SearchByPluginInterface`](#searchbyplugininterface)
  - [`FillerInterface`](#fillerinterface)
  - [`SyncInterface`](#syncinterface)
  - [`CatalogWidgetInterface` и `EntryWidgetInterface`](#catalogwidgetinterface-и-entrywidgetinterface)
  - [`DownloadCandidateSearchInterface`](#downloadcandidatesearchinterface)
  - [`SettingsPageInterface`](#settingspageinterface)
- [Что предоставляет ядро](#что-предоставляет-ядро)
  - [`CatalogReaderInterface` и `AnimeView`](#catalogreaderinterface-и-animeview)
  - [`LlmServiceInterface`](#llmserviceinterface)
  - [`PluginDataStoreInterface`](#plugindatastoreinterface)
  - [`SettingsStoreInterface`](#settingsstoreinterface)
  - [`DownloadServiceInterface`](#downloadserviceinterface)
  - [PSR-18 HTTP-клиент](#psr-18-http-клиент)
  - [`OAuth\AbstractOAuthClient`](#oauthabstractoauthclient)
- [Общие примитивы](#общие-примитивы)
- [Манифест плагина (`manifest.json`)](#манифест-плагина-manifestjson)
- [PHPStan-правила](#phpstan-правила)

## Что реализует плагин

Интерфейсы, которые реализует сам плагин под конкретный внешний источник.
Все они, кроме `DownloadCandidateSearchInterface` и `SettingsPageInterface`,
наследуют базовую способность `ExternalIdResolutionInterface`.

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
use AnimeDb\PluginContracts\Search\SearchByPluginCandidate;
use AnimeDb\PluginContracts\Search\SearchByPluginInterface;

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
use AnimeDb\PluginContracts\Filler\FillerInterface;
use AnimeDb\PluginContracts\Filler\PluginAnimeData;

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
`ThemeCode`, `Demographic`, `AnimeType`, см. [«Общие примитивы»](#общие-примитивы)),
а не произвольные строки, чтобы не терять типобезопасность при развязке с
внутренними enum'ами хост-приложения.

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
use AnimeDb\PluginContracts\Filler\PluginAnimeData;
use AnimeDb\PluginContracts\Search\SearchByPluginCandidate;
use AnimeDb\PluginContracts\Sync\SyncInterface;
use AnimeDb\PluginContracts\Sync\SyncItem;
use AnimeDb\PluginContracts\Sync\SyncStatus;

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
use AnimeDb\PluginContracts\Widget\CatalogWidgetInterface;
use AnimeDb\PluginContracts\Widget\EntryWidgetInterface;
use AnimeDb\PluginContracts\Widget\WidgetMetadata;

// Виджет на общей странице каталога (например, "новинки") — без контекста.
class NewReleasesWidget implements CatalogWidgetInterface
{
    public static function metadata(): WidgetMetadata
    {
        return new WidgetMetadata('new-releases', 'New releases', 'Shows recently added catalog entries.');
    }

    public function resolveExternalId(array $urls): ?string
    {
        return null; // виджету не нужен свой внешний id
    }

    public function render(): string
    {
        return '<div class="new-releases">...</div>';
    }
}

// Виджет на странице отдельной записи каталога — получает AnimeId записи,
// а не внешний id: сам резолвит его, если он ему вообще нужен.
class RelatedTitlesWidget implements EntryWidgetInterface
{
    public static function metadata(): WidgetMetadata
    {
        return new WidgetMetadata('related-titles', 'Related titles', 'Shows related titles for the current record.');
    }

    public function resolveExternalId(array $urls): ?string
    {
        // ...
    }

    public function render(AnimeId $anime): string
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

`EntryWidgetInterface::render()` получает `AnimeId` записи, а не внешний
id: многим виджетам он вообще не нужен (например, виджету статуса закачки
достаточно `AnimeId`, чтобы прочитать свой срез). Виджету, которому нужен
внешний id, доступны два пути — резолвить самому через `resolveExternalId()`
(унаследован от `ExternalIdResolutionInterface`) по `AnimeView::$sources`,
либо взять уже резолвнутый хостом `AnimeView::$externalId` — в обоих
случаях данные приходят через [`CatalogReaderInterface`](#catalogreaderinterface-и-animeview).

`metadata()` — статический метод, возвращающий `WidgetMetadata`: код-имя
(`name`, шаблон `[a-z0-9-]+` — им хост ключует виджет как
`{pluginId}:{name}` в DI-теге/URL/`features`, менять после релиза нельзя
без миграции), человеческое `title` и `description` для страницы настроек
хоста. Статик — чтобы хост-`TagPluginServicesPass` мог прочитать `name`
при компиляции контейнера без инстанцирования класса виджета; `title`/
`description` — готовые строки для отображения, не ключи перевода
(i18n плагинного UI — отдельная задача).

### `DownloadCandidateSearchInterface`

Интерактивный пользовательский поиск скачиваемых кандидатов по внешнему
источнику — отдельная функция от `SearchByPluginInterface`. Разница:
`SearchByPluginInterface` — лёгкое распознавание тайтла при сканировании
тысяч папок (`SearchByPluginCandidate`: id плагина + название + внешний
id). `DownloadCandidateSearchInterface` — по явному запросу пользователя,
возвращает богатые элементы для показа в UI и постановки действий над
ними (в т.ч. «скачать»).

```php
use AnimeDb\PluginContracts\CandidateSearch\AnimeSearchResult;
use AnimeDb\PluginContracts\CandidateSearch\AnimeSearchResultAction;
use AnimeDb\PluginContracts\CandidateSearch\AnimeSearchResultItem;
use AnimeDb\PluginContracts\CandidateSearch\DownloadCandidateSearchInterface;
use AnimeDb\PluginContracts\Download\DownloadServiceInterface;
use AnimeDb\PluginContracts\Download\DownloadSource;
use AnimeDb\PluginContracts\Model\AnimeId;

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
  фолбэк — стабильный хеш от лежащей в основе ссылки на закачку /
  специфичного для источника идентификатора, так что один и тот же
  кандидат всегда сводится к одному и тому же id), `image`
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

### `SettingsPageInterface`

Собственная страница настроек плагина, встраиваемая в settings-область
хост-приложения (модель Chrome `options_ui`). В отличие от виджетов,
**не** наследует `ExternalIdResolutionInterface`: страница настроек не
привязана к конкретной записи каталога, резолв внешнего id ей не нужен.

```php
use AnimeDb\PluginContracts\Settings\SettingsPageInterface;
use AnimeDb\PluginContracts\Settings\SettingsStoreInterface;

class MySourceSettingsPage implements SettingsPageInterface
{
    public function __construct(
        private readonly SettingsStoreInterface $settings,
        private readonly \Twig\Environment $twig,
    ) {
    }

    public function render(): string
    {
        return $this->twig->render('@my-source/settings.html.twig', [
            'settings' => $this->settings->read(),
        ]);
    }
}
```

Контракт покрывает только рендер. Вся интерактивность — сохранение формы,
кнопка «Авторизоваться», OAuth redirect/callback — идёт через собственные
роуты плагина (`plugin-routing.yaml`); `render()` отдаёт HTMX-форму,
целящуюся в эти роуты. Ядро OAuth за плагин не реализует; плагину со
стандартным Authorization Code + PKCE флоу не нужно реализовывать его
самому — см. `OAuth\AbstractOAuthClient` ниже.

Кнопка «Авторизоваться» — обычная top-level навигация (ссылка/сабмит формы
с перезагрузкой страницы), не HTMX-swap: Electron-обвязка хоста
перехватывает OAuth-редирект браузера только на настоящей top-level
навигации, HTMX-запрос она не увидит, и кнопка молча не сработает.

Обвязка для реализаторов: плагин получает `SettingsStoreInterface` и
прочие host-сервисы через DI и рендерит через host-Twig (локаль и
`csrf_token()` там ambient). Хост оборачивает вызов `render()` в
try/catch, чтобы одна сломанная страница не роняла всю settings-область.
Ровно одна страница настроек на плагин.

## Что предоставляет ядро

Сервисы, которые хост-приложение инжектирует в конструктор плагина
(type-hint интерфейса в конструкторе — реализация целиком на стороне
хост-приложения).

### `CatalogReaderInterface` и `AnimeView`

Read-only проекция текущего состояния записи каталога — сервис ядра,
даваемый плагину через DI, как `LlmServiceInterface` и
`DownloadServiceInterface`. Нужен, когда плагину требуются **общие** поля
записи, а не только свой срез: виджету — отрендерить карточку по `AnimeId`,
донасыщению после закачки — список эпизодов и т.п.

```php
use AnimeDb\PluginContracts\Catalog\AnimeView;
use AnimeDb\PluginContracts\Catalog\CatalogReaderInterface;
use AnimeDb\PluginContracts\Model\AnimeId;
use AnimeDb\PluginContracts\Widget\EntryWidgetInterface;
use AnimeDb\PluginContracts\Widget\WidgetMetadata;

class RelatedTitlesWidget implements EntryWidgetInterface
{
    public function __construct(
        private readonly CatalogReaderInterface $catalog,
    ) {
    }

    public static function metadata(): WidgetMetadata
    {
        return new WidgetMetadata('related-titles', 'Related titles', 'Shows related titles for the current record.');
    }

    public function resolveExternalId(array $urls): ?string
    {
        // ...
    }

    public function render(AnimeId $anime): string
    {
        $view = $this->catalog->read($anime);

        if ($view === null) {
            return '';
        }

        // $view->externalId уже резолвлен хостом для этого плагина —
        // не нужно самому парсить $view->sources на каждый HTMX-рендер.
        return \sprintf('<div class="related-titles">%s</div>', $view->title);
    }
}
```

`AnimeView` — плоский иммутабельный DTO: `title`, `alternativeNames`,
`type`, `genres`, `themes`, `episodesCount`, `sources` (внешние ссылки,
уже привязанные к записи) и `externalId` — собственный внешний id
вызывающего плагина, дешёво резолвнутый хостом заранее (lookup по
таблице external_id, а не парсинг `sources` на каждый вызов). В отличие
от списочных полей `PluginAnimeData`, где `null` означает «плагин-источник
это поле не заполнил», списочные поля `AnimeView` не бывают `null` —
только пустой массив, если ничего не известно: это уже смёрженное
текущее состояние, а не вклад одного источника.

Не путать с `PluginAnimeData` — тот DTO «на запись» из источника, этот —
«на чтение» уже смёрженного состояния каталога. Только чтение: в этом
интерфейсе нет и не будет метода записи — мутации своего среза плагина и
мутации записи целиком остаются задачей других частей контракта, не этой.

### `LlmServiceInterface`

Сервис ядра, дающий плагину доступ к локальной LLM. Мотив: некоторые
источники отдают метаданные свободным человеческим текстом (например,
описание релиза на форуме, где каждый автор пишет как хочет), и парсер
такого текста в структурированные данные должен жить в ядре одним
экземпляром, а не дублироваться в каждом плагине.

```php
use AnimeDb\PluginContracts\Llm\LlmServiceInterface;

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

Реализация всегда обращается к модели по HTTP через PSR-18 клиент, поэтому
`parse()` может бросить исключения трёх категорий (виды исключений внутри
`ClientExceptionInterface`, например `NetworkExceptionInterface` и
`RequestExceptionInterface`, определяет сам PSR-18):

```php
use AnimeDb\PluginContracts\Llm\LlmDisabledException;
use Psr\Http\Client\ClientExceptionInterface;

try {
    $data = $this->llm->parse($prompt);
} catch (LlmDisabledException $exception) {
    // LLM выключен в настройках хост-приложения — плагин продолжает
    // работу без LLM-обогащения, это не сбой.
} catch (ClientExceptionInterface $exception) {
    // сбой транспорта: сеть отвалилась, таймаут, 5xx от бэкенда.
} catch (\JsonException $exception) {
    // ответ модели не удалось декодировать как JSON.
}
```

### `PluginDataStoreInterface`

Сервис ядра для чтения/записи собственного payload плагина по `AnimeId` —
для плагин-инициированных записей, которые не проходят через filler-флоу
(там плагин просто возвращает `PluginAnimeData`, а маппинг во внутреннее
представление и сохранение делает ядро). Пример: обработчик
`DownloadCompletedEvent` донасыщает карточку данными из скачанных файлов —
у него на руках `AnimeId`, но нет способа ни прочитать, ни записать
что-либо без этого сервиса.

```php
use AnimeDb\PluginContracts\Download\DownloadCompletedEvent;
use AnimeDb\PluginContracts\Model\AnimeId;
use AnimeDb\PluginContracts\PluginData\PluginDataStoreInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ExampleDownloadPlugin implements EventSubscriberInterface
{
    public function __construct(
        private readonly PluginDataStoreInterface $store,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [DownloadCompletedEvent::class => 'onDownloadCompleted'];
    }

    public function onDownloadCompleted(DownloadCompletedEvent $event): void
    {
        $known = $this->store->read($event->anime);

        // ... разобрать скачанные файлы, дополнить $known своими полями

        $this->store->write($event->anime, $known);
    }
}
```

`read()` возвращает пустой массив, если для этого плагина и этой записи
ещё ничего не сохранено. `write()` — это **override**, а не merge: ключи,
записанные предыдущим вызовом и отсутствующие в переданных данных,
удаляются. Так плагин может явно очистить часть своего среза.

Экземпляр, который получает плагин через DI, **скоупнут на сам плагин** —
плагин физически не может прочитать или перезаписать срез другого
плагина, потому что метод не принимает id плагина: экземпляр уже знает
свой.

В контракте намеренно нет `flush()` — это была бы протечка реализации.
`write()` выражает намерение «сохрани мои данные»; как и когда это
персистится (write-through в БД, батчинг и т.п.) — забота реализации
сервиса на стороне хост-приложения, не контракта.

### `SettingsStoreInterface`

Сервис ядра для чтения/записи собственных настроек плагина — конфигурации
и токенов/секретов, которые плагин получает в ходе своего OAuth-флоу.
Параллель `PluginDataStoreInterface`, но без `AnimeId`: настройки —
per-плагин, а не per-запись каталога.

```php
use AnimeDb\PluginContracts\Settings\SettingsStoreInterface;

class MySourceSettingsPage
{
    public function __construct(
        private readonly SettingsStoreInterface $settings,
    ) {
    }

    public function saveApiToken(string $token): void
    {
        $current = $this->settings->read();

        $this->settings->write([...$current, 'apiToken' => $token]);
    }

    public function revokeApiToken(): void
    {
        $current = $this->settings->read();
        unset($current['apiToken']);

        $this->settings->write($current);
    }
}
```

`read()` возвращает пустой массив, если для этого плагина ещё ничего не
сохранено. `write()` — это **override**, а не merge: заменяет весь payload
плагина целиком, ключи, отсутствующие в переданных данных, удаляются.
Так плагин отзывает OAuth-токен или чистит поле — просто не передавая его
ключ в очередном `write()`.

Как и `PluginDataStoreInterface`, экземпляр скоупнут на сам плагин (id
плагина не в сигнатурах) и не имеет `flush()` — по тем же причинам.

Скоупинг per-id — это изоляция от коллизий ключей между плагинами, **не**
security-граница: плагин — доверенный код после установки, исполняющий
произвольный PHP в процессе хоста, это нормально by design. Секреты в
хранилище защищены на уровне реализации хост-приложения (шифрование на
диске и т.п.), а не на уровне этого контракта.

### `DownloadServiceInterface`

Сервис ядра для постановки задачи на скачивание. Плагин не работает с
менеджером загрузок и очередью напрямую — только просит поставить задачу
и получает `DownloadTaskId`, который сохраняет в свой срез метаданных
аниме (закачка идёт долго, и `DownloadCompletedEvent` может прийти уже
после перезапуска приложения, поэтому хранить id только в памяти нельзя).

```php
use AnimeDb\PluginContracts\Download\DownloadCompletedEvent;
use AnimeDb\PluginContracts\Download\DownloadServiceInterface;
use AnimeDb\PluginContracts\Download\DownloadSource;
use AnimeDb\PluginContracts\Model\AnimeId;
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

#### `DownloadTaskId` / `DownloadCompletedEvent`

`DownloadTaskId` — id поставленной задачи, возвращаемый `enqueue()`.
`DownloadCompletedEvent` — событие завершения закачки (`$anime`, `$task`),
на которое плагин подписывается штатным Symfony `EventSubscriberInterface`:
это обычный класс, без привязки к базовому классу события Symfony —
диспетчеру достаточно имени класса, чтобы разослать событие подписчикам.

### PSR-18 HTTP-клиент

Плагины не создают HTTP-клиент сами — получают **преднастроенный**
PSR-18 `Psr\Http\Client\ClientInterface` через DI (type-hint в
конструкторе). Это позволяет включать прокси централизованно, в фабрике
клиента на стороне хост-приложения, прозрачно для плагинов. Отдельного
интерфейса в этом пакете для этого не заводится — соглашение, не
контрактный тип. Тот же клиент — с per-plugin User-Agent (некоторые
вендоры, например Shikimori, банят запросы без него) — используется и
`OAuth\AbstractOAuthClient` ниже для обмена/обновления токена.

### `OAuth\AbstractOAuthClient`

Тонкий абстрактный базовый класс OAuth 2.0 Authorization Code + PKCE для
плагинов-источников (Shikimori, MyAnimeList и т.п.), чтобы каждый плагин не
переизобретал PKCE/`state`/обмен кода на токен заново. Живёт в этом пакете,
а не в отдельной библиотеке (`league/oauth2-client` и подобные), потому что
плагин поставляется **без своего `vendor/`** — всё, что ему нужно в
рантайме, должно быть доступно из `plugin-contracts`. Построен на PSR-18
клиенте и PSR-17 фабриках запросов/потоков (`psr/http-factory`), без внешних
OAuth-зависимостей.

```php
use AnimeDb\PluginContracts\OAuth\AbstractOAuthClient;

final class ShikimoriOAuthClient extends AbstractOAuthClient
{
    protected function authorizeEndpoint(): string
    {
        return 'https://shikimori.one/oauth/authorize';
    }

    protected function tokenEndpoint(): string
    {
        return 'https://shikimori.one/oauth/token';
    }

    protected function clientId(): string
    {
        return 'shikimori-client-id';
    }

    protected function clientSecret(): ?string
    {
        return 'shikimori-client-secret';
    }

    protected function scopes(): array
    {
        return [];
    }

    protected function pkceMethod(): string
    {
        return 'S256';
    }

    protected function callbackPath(): string
    {
        return '/oauth/shikimori/callback';
    }
}
```

Плагин подключает эти два метода к своим собственным роутам:

```php
// GET /oauth/shikimori/start — top-level навигация, не HTMX
public function start(): RedirectResponse
{
    return new RedirectResponse($this->oauth->buildAuthorizeUrl());
}

// GET /oauth/shikimori/callback?state=...&code=...
public function callback(Request $request): Response
{
    $this->oauth->handleCallback($request->query->get('state'), $request->query->get('code'));

    return new Response('Авторизация завершена, вкладку можно закрыть.');
}
```

Ни один из методов класса не принимает и не возвращает
Symfony-HTTP-типы (`Request`/`Response`) — это чистая логика (строки,
PSR-18, PSR-17, `SettingsStoreInterface`), иначе `symfony/http-foundation`
стал бы зависимостью контракта. HTTP-обвязку поверх неё пишет сам плагин.

Что берёт на себя базовый класс:

- сборку authorize-URL (`redirect_uri`, `client_id`, `scope`, `state`,
  PKCE `code_challenge`) и генерацию `state`/PKCE code verifier, с
  сохранением обоих в `SettingsStoreInterface` на время сессии
  (`buildAuthorizeUrl()`);
- обмен `code` + verifier на токен и **обязательную** сверку `state`
  через `hash_equals()` — это то, что делает безопасным
  неаутентифицированный loopback callback (`handleCallback()`);
- обновление access-токена по refresh-токену, с ротацией: новый
  refresh-токен пишется в `SettingsStoreInterface` **отдельным** вызовом
  `write()` **до** того, как в настройки попадёт новый access-токен —
  крах процесса между «потратил старый refresh» и «записал новый» не
  теряет токен (`refreshAccessToken()`).

`authorizeEndpoint()`/`tokenEndpoint()` — вендорные домены, **прибитые
гвоздями** в подклассе, а не выведенные из пользовательского
`api_endpoint`: иначе смена домена пользователем (соц. инженерия) уводит
долгоживущий refresh-токен на чужой сервер при плановом обновлении.
Настраиваемым остаётся только *data* `api_endpoint`, не auth-эндпоинты.

`redirect_uri` собирается как `$_SERVER['OAUTH_CALLBACK_ORIGIN']` (задаёт
хост-приложение; читается из `$_SERVER`, не `getenv()`) плюс
`callbackPath()` подкласса. Ожидается, что origin — уже `http://127.0.0.1:<port>`
(буквальный loopback-адрес, RFC 8252 §8.3, не `localhost`) без trailing
slash; класс не хардкодит порт и не резолвит хост сам, только
конкатенирует.

Плагин с нестандартным OAuth-флоу этот класс не использует вообще
(escape hatch) — реализует свои роуты сам.

## Общие примитивы

Namespace `AnimeDb\PluginContracts\Model\` — общие для всех фич value-объекты
и закрытые словари (enum'ы).

`AnimeId` — id записи каталога, как он известен хост-приложению. Тонкий
value-объект, а не голый `int`: используется и стороной плагина
(`EntryWidgetInterface::render()`, `PluginDataStoreInterface`,
`CatalogReaderInterface::read()`), и сервисами ядра (`DownloadServiceInterface`,
`DownloadCompletedEvent`), поэтому он не привязан ни к одному конкретному
namespace фичи.

Закрытые словари (enum'ы). Значения синхронизированы 1:1 со словарями
MyAnimeList; пакет не зависит от внутренних enum'ов хост-приложения —
сопоставление на его стороне.

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
