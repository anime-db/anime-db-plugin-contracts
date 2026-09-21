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
 * A plugin's own role interface for executing tasks it previously queued through
 * {@see BackgroundTaskQueueInterface::submit()}. Unlike that interface, this one is
 * not core-provided — the core calls into the plugin's implementation, in a
 * background process, the same way it calls into any other plugin role interface.
 *
 * A plugin distinguishes its own task kinds by {@see BackgroundTask::$name}; one
 * handler per plugin covers all of that plugin's tasks, it does not register a
 * separate class per task kind.
 */
interface BackgroundTaskHandlerInterface
{
    /**
     * Executes $task.
     *
     * Because {@see BackgroundTaskQueueInterface::submit()} does not deduplicate and
     * does not guarantee a task runs exactly once, this method must be idempotent and
     * must start by checking whether the work $task describes is still needed at all
     * — by the time it runs, the state that made the task necessary (files on disk,
     * the plugin's own stored data, an external tool's availability) may already have
     * changed. Only the handler is in a position to know that at execution time; the
     * queue is not, which is why this check lives here rather than at submit() time.
     */
    public function handle(BackgroundTask $task): void;
}
