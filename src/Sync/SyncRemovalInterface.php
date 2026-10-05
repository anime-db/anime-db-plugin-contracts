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

namespace AnimeDb\PluginContracts\Sync;

/**
 * Optional capability of a sync plugin: remove an item from the user's list on the source.
 *
 * Not part of {@see SyncInterface}: a sync plugin implements it additionally when the
 * source can delete list entries. The host checks it with `instanceof` and calls it only
 * on an explicit user action.
 */
interface SyncRemovalInterface extends SyncInterface
{
    /**
     * Remove the user's list entry for the title `$externalId` on the source.
     *
     * `$externalId` is the same id as {@see SyncItem::$externalId}. Only the entry in the
     * user's list is removed; the title itself is not, it does not belong to the user.
     * Other list entries are not affected.
     *
     * Idempotent: if the title is not in the list, the method returns normally and does
     * not throw.
     *
     * @throws \AnimeDb\PluginContracts\OAuth\ReauthRequiredException when authorization is required,
     *                                                                as for {@see SyncInterface::push()}/{@see SyncInterface::pull()}
     * @throws \Throwable                                             on any other failure (network, 5xx). The plugin does not
     *                                                                retry network failures and 5xx itself, retries are the
     *                                                                host's concern. Backoff on 429 and a token refresh with
     *                                                                a retry are allowed.
     */
    public function remove(string $externalId): void;
}
