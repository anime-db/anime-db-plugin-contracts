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
 * Contract for plugins that can search/match by title.
 */
interface SearchByPluginInterface extends ExternalIdResolutionInterface
{
    /**
     * Search for candidates matching the given title.
     *
     * $onHeartbeat, when given, may be called by the plugin between internal
     * steps (retries, pagination pages) of a long-running search, so the
     * caller can refresh a "work in progress" signal (e.g. extend a
     * background job lock). The plugin is not required to call it, and the
     * caller is not required to pass it.
     *
     * @param callable(): void|null $onHeartbeat
     *
     * @return SearchByPluginCandidate[]
     */
    public function find(string $name, ?callable $onHeartbeat = null): array;
}
