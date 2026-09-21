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

namespace AnimeDb\PluginContracts\Background;

use AnimeDb\PluginContracts\Model\AnimeId;

/**
 * A unit of work a plugin hands to {@see BackgroundTaskQueueInterface::submit()} for
 * later execution by its own {@see BackgroundTaskHandlerInterface::handle()}.
 */
final class BackgroundTask
{
    public function __construct(
        /**
         * The plugin's own name for this kind of task, used to tell its tasks apart from
         * one another inside {@see BackgroundTaskHandlerInterface::handle()}. The core
         * does not interpret this value in any way — it is opaque to the queue and to
         * routing, which is done by plugin id, not by this string.
         */
        public readonly string $name,
        public readonly ?AnimeId $anime = null,
        /**
         * Arbitrary data the handler needs to do the work. Must be JSON-serializable:
         * a task can sit in the queue across a process restart, so it is persisted to
         * disk between {@see BackgroundTaskQueueInterface::submit()} and
         * {@see BackgroundTaskHandlerInterface::handle()} rather than kept in memory —
         * objects, resources and closures do not survive that trip.
         *
         * @var array<string, mixed>
         */
        public readonly array $payload = [],
    ) {
    }
}
