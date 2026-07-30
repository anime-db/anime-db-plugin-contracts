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
 * Identifier of a catalog anime entry, as known to the host application.
 *
 * A thin value object rather than a plain int: {@see DownloadServiceInterface::enqueue()}
 * and {@see DownloadCompletedEvent} both carry it, and a typed wrapper keeps it from
 * being confused with any other numeric id crossing the contract (e.g. a
 * {@see DownloadTaskId}).
 */
final class AnimeId
{
    public function __construct(
        public readonly int $value,
    ) {
    }
}
