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

/**
 * Core-provided entry point for a plugin to move work out of the request or event
 * that triggered it and into a background process, instead of doing it synchronously
 * inline (e.g. inside an event subscriber).
 *
 * A plugin obtains this service via constructor injection, type-hinting this
 * interface, the same way it obtains {@see \AnimeDb\PluginContracts\PluginData\PluginDataStoreInterface}.
 * The instance handed to a plugin is scoped to that plugin's own id — no plugin id
 * appears in {@see self::submit()}'s signature because the instance already knows it,
 * and a plugin cannot submit a task under another plugin's identity through this
 * interface.
 *
 * There is no method to check whether a task is already queued, and no scheduling,
 * delay or priority of any kind — a task is submitted, and it is executed at some
 * later point, that is the entire contract.
 */
interface BackgroundTaskQueueInterface
{
    /**
     * Queues $task for later execution by the plugin's own
     * {@see BackgroundTaskHandlerInterface::handle()}.
     *
     * Submitting a task that is a duplicate of one already queued is allowed — this
     * interface does not deduplicate. The order in which queued tasks run is not
     * guaranteed, and a queued task may end up executed more than once (e.g. after a
     * retry). Both are the handler's problem, not the queue's: see
     * {@see BackgroundTaskHandlerInterface::handle()} for why deduplication belongs
     * there instead.
     */
    public function submit(BackgroundTask $task): void;
}
