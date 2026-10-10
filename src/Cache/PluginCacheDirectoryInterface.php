<?php

/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2026, Peter Gribanov
 * @license   https://www.gnu.org/licenses/gpl-3.0.html GPL-3.0-or-later
 */

/*
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace AnimeDb\PluginContracts\Cache;

/**
 * Core-provided directory on disk where a plugin keeps files that need a real
 * path (e.g. an SQLite file, a lock file for `flock`).
 *
 * This is an optional capability: an additive extension that does not change
 * any existing signature. A plugin that uses it requires `^0.26.1`.
 *
 * A plugin obtains this service via constructor injection, type-hinting this
 * interface. The instance handed to a plugin is scoped to that plugin's own id
 * — no plugin id appears in the method signature because the instance already
 * knows it, the same way as for `PluginDataStoreInterface` and
 * `SettingsStoreInterface`. Every plugin has its own directory.
 *
 * What belongs here: derived data that can be restored from the network
 * (a downloaded dump, an index built from it). What does not: durable plugin
 * state that cannot be restored by a request (attempt timestamps, "banned
 * until" marks, the time of the last download). Such state goes to
 * `SettingsStoreInterface`: the host deletes this directory when the plugin
 * is uninstalled, while plugin settings survive that.
 *
 * Lifetime: the host does not clean the directory while the plugin is
 * installed, and it survives plugin updates. The host deletes it when the
 * plugin is uninstalled. Even so, an empty directory is a normal state (first
 * run, reinstall of the plugin): the plugin must cope with it by restoring
 * the content from the network, not by failing.
 *
 * The directory is not part of a backup, a catalog export or a settings
 * export.
 *
 * This is not a PSR-16 cache and does not replace or cancel a future
 * per-plugin PSR-16 TTL cache. PSR-16 is for "key → data with TTL" values;
 * this directory is for files that need a real filesystem path.
 */
interface PluginCacheDirectoryInterface
{
    /**
     * Absolute path to this plugin's directory.
     *
     * The directory exists and is writable. Creating it is the host's
     * responsibility, not the plugin's.
     */
    public function path(): string;
}
