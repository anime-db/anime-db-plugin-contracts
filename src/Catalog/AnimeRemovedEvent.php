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

namespace AnimeDb\PluginContracts\Catalog;

use AnimeDb\PluginContracts\Background\BackgroundTaskHandlerInterface;
use AnimeDb\PluginContracts\Background\BackgroundTaskQueueInterface;
use AnimeDb\PluginContracts\Model\AnimeId;

/**
 * Dispatched by the core after a catalog entry has been removed and that change has already
 * been committed — never before.
 *
 * `$lastValues` always holds all keys of {@see AnimeField} (unlike
 * {@see AnimeChangedEvent::$changes}, which holds only changed fields), each in the shape
 * given in the table of {@see AnimeField}.
 *
 * The entry no longer exists, so nothing may be written by the `AnimeId` from this event.
 * The plugin's own data for the removed entry ({@see \AnimeDb\PluginContracts\PluginData\PluginDataStoreInterface})
 * is deleted by the host together with the entry, so there is nothing to clean up. If the
 * subscriber needs background work (for example on the side of an external source), it
 * submits a task, copying what it needs from `$lastValues` into JSON-serializable scalars
 * itself: value objects are not put into the task payload.
 *
 * The subscriber is called synchronously. It may write through the contract's own stores,
 * but the recommended pattern is to do nothing beyond calling
 * {@see BackgroundTaskQueueInterface::submit()} and to leave the actual work to
 * {@see BackgroundTaskHandlerInterface::handle()}. An exception thrown by a subscriber is
 * isolated by the host: it is logged and the remaining subscribers are still called.
 */
final class AnimeRemovedEvent
{
    /**
     * @param array<value-of<AnimeField>, mixed> $lastValues
     */
    public function __construct(
        public readonly AnimeId $anime,
        public readonly array $lastValues,
    ) {
    }
}
