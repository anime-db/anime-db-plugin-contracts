# anime-db/plugin-contracts

Plugin contracts (interfaces and DTOs) for AnimeDB v2 plugins.

This package defines only the contract between the AnimeDB host application
and its plugins: interfaces and flat DTOs. It contains no plugin
implementations and no host-application business logic.

## SearchByPluginInterface

Implemented by plugins that can search/match a title against an external
source.

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

A single search match. Deliberately lightweight, so it stays cheap to
produce in bulk while a plugin walks a large external catalog:

- `getPluginId()` — id of the plugin that produced the candidate.
- `getName()` — matched title, as found in the external source.
- `getExternalId()` — external id of the matched record, so the caller can
  run a follow-up `findById()` without an extra lookup step.

### `$onHeartbeat`

`find()` accepts an optional `callable(): void` heartbeat callback. A plugin
*may* call it between internal steps (pagination pages, retries) of a
long-running search; it is not required to, and callers not running in a
context that needs it simply omit the argument (`null`).
