# Changelog

## 0.27.0

- BC: removed `Manifest\LinkRulesValidator`, use `LinkRules\LinkRulesValidator`. `LinkRules\LinkRulesValidator` is `final` again.
- Added namespace `AnimeDb\PluginContracts\Folder\`: `RecordFolderWriterInterface` (`write()`,
  `writeCover()`, `inspect()`) for writing files into a record's own folder by bare file name and
  bytes (no paths in either direction), enum `RecordFolderFileState` (`Absent`, `Written`,
  `Modified`), and exceptions `RecordHasNoFolderException`, `StorageNotWritableException`,
  `ForeignFileException`. Unavailable storage is reported with the existing
  `Media\StorageUnavailableException`. Available to `local` plugins only, per-plugin scoped.
- `AnimeView`: added trailing optional constructor parameters `description`, `datePremiere`,
  `dateEnd`, `durationMinutes`, `studios`, `countries` (defaults `null`/`[]`).
- Plugins that use `Folder\RecordFolderWriterInterface` or the new `AnimeView` fields require `^0.27`.

## 0.26.4

- Link rules moved to their own namespace `AnimeDb\PluginContracts\LinkRules\`:
  `LinkRules\LinkRulesValidator`, `LinkRules\LinkRulesApplier`, `LinkRules\LinkRulesExamplesChecker`
  (`ManifestValidationError` stays in `Manifest\` and is reused).
- Deprecated: `Manifest\LinkRulesValidator` (released in 0.26.3) is now a thin `@deprecated`
  subclass of `LinkRules\LinkRulesValidator`; it keeps working and will be removed in the next
  minor version. Switch to the new name.
- Added `LinkRules\LinkRulesApplier::apply()`: the single implementation of how `link-rules.yaml`
  rules are applied to an already normalized link (first matching rule, `\A(?:…)\z` with the `u`
  flag, result must be an absolute `https` link with a host, a rule that fails to run is skipped and
  reported through the optional `$onRuleError` callback; `pcre.backtrack_limit` is set for the call
  and restored afterwards). No change to the `link-rules.yaml` format or to `LinkRulesValidator`.
- Added `LinkRules\LinkRulesExamplesChecker::check()` for the `link-rules.examples.yaml` file
  (`version: 1`, `examples` maps a link to the expected result or `null`). Every example is run
  through `LinkRulesApplier`, every rule must win on at least one example. Takes parsed arrays and
  returns the full list of `ManifestValidationError`.
- Additive optional extension, released as a patch; no new dependencies.

## 0.26.3

- Manifest: added optional boolean field `link_rules` (`Manifest::$linkRules`, default `false`).
  `true` means the package root carries `link-rules.yaml`. Allowed for `local` and `integration`,
  rejected by `ManifestValidator` for `translation`.
- Added `Manifest\LinkRulesValidator` for the declarative `link-rules.yaml` format (`version: 1`,
  `rules` is a map of expression to replacement with `$N`/`${N}`; no limit on the number of rules).
  Takes an already parsed array (the contract has no YAML dependency) and returns the full list of
  errors. An expression must compile both on its own and wrapped in `\A(?:…)\z` with the `u` flag.
  Rules are data, not plugin code.
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
