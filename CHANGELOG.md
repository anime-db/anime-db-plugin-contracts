# Changelog

## 0.26.3

- Manifest: added optional boolean field `link_rules` (`Manifest::$linkRules`, default `false`).
  `true` means the package root carries `link-rules.json`. Allowed for `local` and `integration`,
  rejected by `ManifestValidator` for `translation`.
- Added `Manifest\LinkRulesValidator` for the declarative `link-rules.json` format (`version: 1`):
  returns the full list of errors. Rules are data, not plugin code.
- `local` may ship without `src/` if it declares `link_rules` (relaxes the existing type, no new
  `PluginType` case). The contract does not check `src/` or the file's presence.
- Additive optional extension, released as a patch; plugins that declare `link_rules` require
  `^0.26.3`.

## 0.26.2

- `Filler\PluginAnimeData`: added optional last constructor argument `?array $externalUrls = null`
  (`string[]|null`): absolute `http(s)` urls of pages describing the same title (encyclopedias,
  catalogs, wikis, the official site), including the source's own page; not streaming, stores,
  social networks or related titles. Its name in `FillerInterface::getFillableFields()` is
  `externalUrls`. Additive optional extension, released as a patch; plugins that fill the field
  require `^0.26.2`.
- `ExternalIdResolutionInterface::resolveExternalId()` now **permits** a plugin to recognize its id
  from the URL of a source sharing the same id space, when no URL matches the plugin's own pattern;
  an own-pattern URL always takes precedence. Existing implementations remain conforming; the host
  is unaffected. No signature changes.

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
