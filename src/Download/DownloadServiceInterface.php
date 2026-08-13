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

use AnimeDb\PluginContracts\Model\AnimeId;

/**
 * Core-provided service for queuing a download, neutral with respect to whichever
 * download manager/backend the host application actually uses.
 *
 * A plugin never touches the download manager or its queue directly — it only asks
 * for a task to be queued and gets back a {@see DownloadTaskId} to remember. Once the
 * download finishes, the core dispatches a {@see DownloadCompletedEvent} the plugin can
 * subscribe to (via a regular Symfony `EventSubscriberInterface`) to match against ids
 * it persisted earlier and react, e.g. by filling in the card from the downloaded files.
 */
interface DownloadServiceInterface
{
    /**
     * Queue a download task for the given source, attached to the given anime.
     */
    public function enqueue(DownloadSource $source, AnimeId $anime): DownloadTaskId;
}
