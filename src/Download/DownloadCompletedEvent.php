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

namespace AnimeDb\PluginContracts\Download;

use AnimeDb\PluginContracts\Model\AnimeId;

/**
 * Dispatched by the core when a task queued through {@see DownloadServiceInterface::enqueue()}
 * finishes.
 *
 * A plain event object, not tied to any particular Symfony event base class — the
 * event dispatcher only needs the class name to route it to subscribers. A plugin
 * subscribes to it with a regular Symfony `EventSubscriberInterface`, matches
 * {@see self::$task} against the {@see DownloadTaskId} it persisted when it called
 * `enqueue()`, and reacts only if it recognizes the task as its own — e.g. by moving
 * heavy card-filling work off into its own background job.
 */
final class DownloadCompletedEvent
{
    public function __construct(
        public readonly AnimeId $anime,
        public readonly DownloadTaskId $task,
    ) {
    }
}
