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

namespace AnimeDb\PluginContracts\Manifest;

/**
 * Closed dictionary of plugin kinds a `manifest.json` can declare.
 *
 * `Integration` is a regular code plugin (any combination of filler/widget/sync/search
 * features). `Translation` is a purely declarative resource with no code — a set of
 * locale files — and declares `locales` instead of `features`.
 */
enum PluginType: string
{
    case Integration = 'integration';
    case Translation = 'translation';
}
