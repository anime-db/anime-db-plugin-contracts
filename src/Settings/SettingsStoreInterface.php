<?php

declare(strict_types=1);

/**
 * AnimeDb package.
 *
 * @author    Peter Gribanov <info@peter-gribanov.ru>
 * @copyright Copyright (c) 2026, Peter Gribanov
 * @license   https://gnu.org GPL-3.0-or-later
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
 * along with this program. If not, see <https://gnu.org>.
 */

namespace AnimeDb\PluginContracts\Settings;

/**
 * Core-provided read/write access to a plugin's own settings — configuration
 * values as well as tokens/secrets a plugin's OAuth flow has obtained.
 *
 * The host application provides this service; a plugin only ever consumes
 * it via constructor injection, type-hinting this interface, the same way it
 * obtains {@see \AnimeDb\PluginContracts\PluginData\PluginDataStoreInterface}.
 * A plugin never implements this interface itself. The instance handed to a
 * plugin is scoped to that plugin's own id — no plugin id appears in the
 * method signatures because the instance already knows it, and a plugin
 * cannot reach another plugin's settings through this interface. This
 * scoping is isolation from key collisions between plugins, not a security
 * boundary: a plugin is trusted code once installed, running arbitrary PHP
 * in the host process by design.
 *
 * There is deliberately no `flush()` here: how and when a write actually
 * lands is an implementation concern of the host application, not something
 * the contract dictates.
 */
interface SettingsStoreInterface
{
    /**
     * Read this plugin's stored settings.
     *
     * Returns an empty array if nothing has been stored yet.
     *
     * @return array<string, mixed>
     */
    public function read(): array;

    /**
     * Atomically read-modify-write this plugin's stored settings.
     *
     * The host executes $modifier under an exclusive lock on this plugin's
     * settings: it reads the current settings, passes them to $modifier, and
     * persists whatever $modifier returns, all as one atomic step. This is
     * the only way to write settings through this interface, so a plugin has
     * no way to persist settings outside of this lock.
     *
     * $modifier is called exactly once per call to update(), with no
     * retries. It must return the complete new settings array, not a delta:
     * returning something other than an array is an error, not a value to
     * silently coerce, so that a modifier that forgets its `return`
     * statement fails loudly instead of quietly wiping out every setting
     * this plugin has stored.
     *
     * For a partial edit, the atomicity update() gives you is not enough on
     * its own — $modifier still has to merge, not replace:
     *
     * ```php
     * $store->update(fn (array $settings): array => [...$settings, 'api_endpoint' => $value]);
     * ```
     *
     * Returning a value that ignores the $settings argument
     * (`fn () => $newPayload`) is only legitimate for a genuine "revoke
     * everything" reset, never for "change one field" — otherwise it
     * reintroduces the same lost-update race this method exists to close.
     *
     * $modifier must not perform I/O of any kind (no network calls, no file
     * access, no token exchange): the lock it runs under is shared with
     * every other write to this plugin's settings, and a slow $modifier
     * serializes all of them. Fetch whatever value $modifier needs to write
     * (e.g. a freshly obtained token) before calling update(), and only
     * place that already-available value into the array inside $modifier.
     *
     * $modifier must not call back into this store — neither read() nor
     * update() — from within itself. Both reacquire the same exclusive lock
     * this call already holds in the same process, so a nested call
     * deadlocks rather than failing fast.
     *
     * @param callable(array<string, mixed>): array<string, mixed> $modifier
     *
     * @throws ConcurrentWriteException if the host could not acquire the
     *                                  lock on this plugin's settings because another writer already holds it
     */
    public function update(callable $modifier): void;
}
