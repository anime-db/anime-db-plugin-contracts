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

namespace AnimeDb\PluginContracts\Model;

/**
 * Media type of a title.
 *
 * A closed dictionary, not free-form input, so it is a contract-owned enum
 * rather than a plain string: values are kept 1:1 with the host
 * application's own type enum, but this package does not depend on it, so
 * the host maps this enum to its internal one instead of sharing it.
 */
enum AnimeType: string
{
    case Tv = 'tv';
    case Movie = 'movie';
    case Ova = 'ova';
    case Ona = 'ona';
    case Special = 'special';
    case Music = 'music';
}
