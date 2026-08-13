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

namespace AnimeDb\PluginContracts\Settings;

/**
 * Thrown by {@see SettingsStoreInterface::update()} when the host could not
 * acquire the exclusive lock on this plugin's settings because another
 * writer already holds it.
 *
 * The host attempts a non-blocking acquire rather than queueing behind the
 * current writer, so this is an expected, recoverable outcome rather than a
 * fault: a background task (e.g. an OAuth refresh-token rotation) should
 * retry later, and an interactive settings save should tell the user the
 * store is busy and to try again, rather than treating either as a hard
 * failure.
 */
final class ConcurrentWriteException extends \RuntimeException
{
}
