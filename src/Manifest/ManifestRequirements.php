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
 * Flat DTO for the `require` block of a plugin manifest.
 *
 * `core` and `php` are author-declared lower bounds only (a single `>=` constraint,
 * no upper bound) — the upper bound a plugin actually supports is not self-declared,
 * it is determined by the registry's own empirical compatibility check, which is out
 * of scope for this package. `pluginContracts` is an optional, more specific
 * compatibility signal than the core version alone and may be any valid semver
 * constraint (e.g. `^2.0`).
 */
final class ManifestRequirements
{
    public function __construct(
        public readonly string $core,
        public readonly string $php,
        public readonly ?string $pluginContracts = null,
    ) {
    }
}
