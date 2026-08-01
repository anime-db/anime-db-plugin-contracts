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

namespace AnimeDb\PluginContracts\Catalog;

use AnimeDb\PluginContracts\ExternalIdResolutionInterface;
use AnimeDb\PluginContracts\Filler\PluginAnimeData;
use AnimeDb\PluginContracts\Model\AnimeType;
use AnimeDb\PluginContracts\Model\GenreCode;
use AnimeDb\PluginContracts\Model\ThemeCode;

/**
 * Flat, immutable read-only projection of a catalog record's current state,
 * returned by {@see CatalogReaderInterface::read()}.
 *
 * Not to be confused with {@see PluginAnimeData}: that DTO is "write-shaped"
 * data a plugin fills in from an external source, this one is "read-shaped"
 * current catalog state as merged from all sources. Deliberately not the
 * host application's Doctrine entity: exposing that would leak the host's
 * internal representation into the contract.
 *
 * List fields use an empty array rather than null when nothing is known —
 * unlike {@see PluginAnimeData}, where null distinguishes "not filled by this
 * plugin" from "known to be empty", a read projection of already-merged
 * state has no such distinction to make.
 *
 * `sources` carries every external source URL already attached to the
 * record, so a plugin can resolve its own external id the same way it does
 * in {@see ExternalIdResolutionInterface::resolveExternalId()}. `externalId`
 * is that same id, pre-resolved by the host for the calling plugin — a
 * cheap lookup against a dedicated external-id table, so a widget rendered
 * on every HTMX request does not have to re-parse `sources` each time.
 */
final class AnimeView
{
    /**
     * @param string[]    $alternativeNames
     * @param GenreCode[] $genres
     * @param ThemeCode[] $themes
     * @param string[]    $sources          external source URLs already attached to the record
     */
    public function __construct(
        public readonly string $title,
        public readonly array $alternativeNames,
        public readonly ?AnimeType $type,
        public readonly array $genres,
        public readonly array $themes,
        public readonly ?int $episodesCount,
        public readonly array $sources,
        /**
         * This plugin's own external id on `sources`, pre-resolved by the host, or
         * null if the record is not linked to this plugin's source.
         */
        public readonly ?string $externalId,
    ) {
    }
}
