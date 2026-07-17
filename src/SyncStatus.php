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

namespace AnimeDb\PluginContracts;

/**
 * Watch status of a title in a user's list, as used by {@see SyncItem}.
 *
 * Closed vocabulary shared by the host application and every sync plugin. A plugin
 * normalizes its source's own status dictionary (e.g. "watching"/"completed") into
 * this enum once, in its adapter; the host then maps it to its own internal status
 * representation.
 */
enum SyncStatus: string
{
    case Plan = 'plan';
    case Watching = 'watching';
    case Completed = 'completed';
    case Dropped = 'dropped';
    case OnHold = 'on_hold';
}
