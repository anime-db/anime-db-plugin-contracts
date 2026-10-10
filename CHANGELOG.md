# Changelog

## 0.26.2

- Added `Network\DatagramChannelFactoryInterface`, `Network\DatagramChannelInterface` and
  `Network\DatagramUnavailableException`: an optional host-provided UDP channel. A channel
  holds one local socket for the whole `open()` … `close()`; `open()` throws
  `DatagramUnavailableException` while a proxy is configured in the host. Additive optional
  extension, released as a patch; plugins that use it require `^0.26.2`.
- Classified `DatagramChannelFactoryInterface` as a network type in
  `NoNetworkAccessInLocalPluginsRule`, documented the channel in the README.

## 0.26.1

- Added `Cache\PluginCacheDirectoryInterface` with a single method `path(): string`: an
  absolute path to the plugin's own existing writable directory for files restorable from the
  network. Additive optional extension, released as a patch; plugins that use it require
  `^0.26.1`.
- Documented the new service in the README, classified it as non-network in
  `NoNetworkAccessInLocalPluginsRule`, and documented that durable plugin state belongs in
  `SettingsStoreInterface`.

## 0.26.0

- `Sync\SyncItem`: added required argument `?AnimeType $type` as the fourth constructor
  argument, right after `title`, without a default value. `null` means the source does not
  report a type or its value does not map to `AnimeType`. In `push()` the type is `null` in
  both directions.
- Breaking change: positional `new SyncItem(...)` calls must be updated. On 0.x a breaking
  change is released as a minor version by the convention of this package.
