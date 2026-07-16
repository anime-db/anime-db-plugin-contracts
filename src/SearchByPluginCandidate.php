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
 * A single search match returned by {@see SearchByPluginInterface::find()}.
 *
 * Deliberately lightweight: only the fields needed for cheap matching and a
 * follow-up lookup. No extra metadata (type, year, etc.) is carried here —
 * a plugin implementing search may extend this class to add disambiguation
 * fields specific to its own UI needs.
 */
class SearchByPluginCandidate
{
    public function __construct(
        private readonly string $pluginId,
        private readonly string $name,
        private readonly string $externalId,
    ) {
    }

    /**
     * Id of the plugin that produced this candidate.
     */
    public function getPluginId(): string
    {
        return $this->pluginId;
    }

    /**
     * Matched title, as found by the plugin in the external source.
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * External id of the matched record, usable for a follow-up findById()
     * call without an extra lookup step.
     */
    public function getExternalId(): string
    {
        return $this->externalId;
    }
}
