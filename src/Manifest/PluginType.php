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

namespace AnimeDb\PluginContracts\Manifest;

/**
 * Closed dictionary of plugin kinds a `manifest.json` can declare.
 *
 * `Integration` is a regular code plugin (any combination of filler/widget/sync/search
 * features). `Translation` is a purely declarative resource with no code — a set of
 * locale files — and declares `locales` instead of `features`. `Local` is a code plugin
 * whose code never talks to an external source — neither directly nor through an
 * abstraction the host provides; it can still declare widgets in `features` (everything
 * but the `filler`/`sync`/`search` role keys, which name integration-only roles), ship its
 * own translations, and react to catalog events — reacting to catalog events is one
 * possible use, not the type's defining trait. `locales` — the languages of the
 * translation catalogs a plugin ships — is required for `Translation` and optional for
 * `Integration` and `Local`.
 */
enum PluginType: string
{
    case Integration = 'integration';
    case Translation = 'translation';
    case Local = 'local';
}
