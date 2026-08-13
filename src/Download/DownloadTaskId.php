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

namespace AnimeDb\PluginContracts\Download;

/**
 * Identifier of a queued download task, returned by {@see DownloadServiceInterface::enqueue()}.
 *
 * A plugin is expected to persist this id in its own slice of the anime's metadata:
 * a download can take a long time, and {@see DownloadCompletedEvent} may arrive after
 * an application restart, so the plugin cannot rely on keeping the id only in memory.
 * On the event, the plugin matches {@see DownloadCompletedEvent::$task} against what
 * it persisted to recognize its own completed task.
 */
final class DownloadTaskId
{
    public function __construct(
        public readonly string $value,
    ) {
    }
}
