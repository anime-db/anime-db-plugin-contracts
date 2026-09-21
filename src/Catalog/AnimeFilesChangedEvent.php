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
 * Dispatched by the core after the set or location of an entry's files on disk has
 * changed and that change has already been persisted — never before. This event does
 * not carry the file list itself: a subscriber that needs it reads the current state
 * separately, because a list attached to the event object would not survive being
 * handed off to {@see BackgroundTaskQueueInterface::submit()} for later processing.
 *
 * A subscriber is called synchronously, on the same request/process that persisted
 * the change, exactly like {@see \AnimeDb\PluginContracts\Download\DownloadCompletedEvent}.
 * The recommended pattern is for a subscriber to do nothing beyond calling
 * {@see BackgroundTaskQueueInterface::submit()}, and to leave any actual work — reading
 * files, filling a card, talking to an external source — to
 * {@see BackgroundTaskHandlerInterface::handle()}. Nothing enforces this: a subscriber
 * that does heavy work inline still runs, synchronously, inside whatever transaction
 * or request triggered the change.
 */
final class AnimeFilesChangedEvent
{
    public function __construct(
        public readonly AnimeId $anime,
        public readonly FilesChangeReason $reason,
    ) {
    }
}
