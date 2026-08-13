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

use AnimeDb\PluginContracts\Filler\FillerInterface;

/**
 * Sync user lists between the host application and an external source.
 *
 * Unlike the interactive filler/widget/search features, sync is configured once and
 * runs in the background. It covers two independent directions: push (local state to
 * the external source) and pull (external source to local state).
 *
 * Extends FillerInterface: pull() creates local records from a remote list, and a
 * "bare" record with nothing but a title is not a valid card. A sync plugin must be
 * able to fill in a card (find(), findById(), getFillableFields()), so a sync-only
 * plugin without a filler is impossible at the contract level.
 */
interface SyncInterface extends FillerInterface
{
    /**
     * Push a local list item change to the external source.
     *
     * Returns the source-confirmed state of the entry after the write: the
     * authoritative post-write value, both by content (the source may normalize the
     * write, e.g. a lossy status mapping) and by time (the source's own
     * {@see SyncItem::$updatedAt}). The caller (host) uses it to seed its sync
     * snapshot precisely — otherwise the snapshot drifts from reality and produces
     * phantom changes on the next reconciliation.
     *
     * If the source's API does not return the record or a timestamp on write, the
     * plugin returns a {@see SyncItem} with what it actually sent and
     * `updatedAt: null` — the host falls back to its own value in that case.
     */
    public function push(SyncItem $item): SyncItem;

    /**
     * Pull the user's list from the external source.
     *
     * @return iterable<SyncItem>
     */
    public function pull(): iterable;
}
