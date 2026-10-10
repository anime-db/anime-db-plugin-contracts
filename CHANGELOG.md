# Changelog

## 0.26.1

- Added `Cache\PluginCacheDirectoryInterface` with a single method `path(): string`: an
  absolute path to the plugin's own existing writable directory for files restorable from the
  network. Additive optional extension, released as a patch; plugins that use it require
  `^0.26.1`.

## 0.26.0

- `Sync\SyncItem`: added required argument `?AnimeType $type` as the fourth constructor
  argument, right after `title`, without a default value. `null` means the source does not
  report a type or its value does not map to `AnimeType`. In `push()` the type is `null` in
  both directions.
- Breaking change: positional `new SyncItem(...)` calls must be updated. On 0.x a breaking
  change is released as a minor version by the convention of this package.
