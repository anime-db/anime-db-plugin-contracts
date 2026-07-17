# anime-db/plugin-contracts

Контракты плагинов (интерфейсы и DTO) для плагинов AnimeDB v2.

Этот пакет определяет только контракт между хост-приложением AnimeDB и его
плагинами: интерфейсы и плоские DTO. Он не содержит ни реализации плагинов,
ни бизнес-логики хост-приложения.

## SearchByPluginInterface

Реализуется плагинами, которые умеют искать/сопоставлять тайтл с внешним
источником.

```php
use AnimeDb\PluginContracts\SearchByPluginCandidate;
use AnimeDb\PluginContracts\SearchByPluginInterface;

class MySourcePlugin implements SearchByPluginInterface
{
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
                    pluginId: $this->getId(),
                    name: $match->title,
                    externalId: $match->id,
                );
            }
        }

        return $candidates;
    }
}
```

### `SearchByPluginCandidate`

Одно совпадение поиска. Намеренно лёгкий класс, чтобы его было дёшево
создавать в большом количестве, пока плагин обходит крупный внешний каталог:

- `getPluginId()` — id плагина, который создал кандидата.
- `getName()` — найденное название, как оно указано во внешнем источнике.
- `getExternalId()` — внешний id найденной записи, чтобы вызывающая сторона
  могла выполнить последующий `findById()` без дополнительного поиска.

### `$onHeartbeat`

`find()` принимает опциональный heartbeat-колбэк типа `callable(): void`.
Плагин *может* вызывать его между внутренними шагами (страницы пагинации,
повторные попытки) долгого поиска; делать это не обязательно, а вызывающая
сторона, которой это не нужно, просто не передаёт аргумент (`null`).

## PHPStan-правила

Пакет поставляет набор правил PHPStan, которыми переиспользуют и CI реестра
плагинов (при приёме сторонних плагинов), и клиентская валидация
хост-приложения (при установке кастомного ZIP-плагина) — одна и та же
логика проверки в одном месте.

Если в проекте установлен [`phpstan/extension-installer`](https://github.com/phpstan/extension-installer),
правила подключаются автоматически после `composer require anime-db/plugin-contracts`.
Без него — добавьте `extension.neon` пакета в свой `phpstan.neon`:

```neon
includes:
    - vendor/anime-db/plugin-contracts/extension.neon
```

### `NoDangerousPrimitivesRule`

Запрещает плагину напрямую вызывать низкоуровневые примитивы, которые
должны идти через абстракции хост-приложения: `exec`, `shell_exec`,
`passthru`, `system`, `popen`, `proc_open`, `proc_close`, `pcntl_exec`,
`file_get_contents`, любые функции `curl_*`, оператор обратных кавычек
(`` `command` ``), `eval`, а также динамические вызовы через
переменные-функции (`$fn()`) и переменные-переменные (`$$name`).

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
