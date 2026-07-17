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

use DateTimeImmutable;

/**
 * Flat DTO carrying anime data filled in by a plugin from an external source.
 *
 * Deliberately not tied to any host application's ORM entity or database
 * schema: `title` is the only required field, everything else is nullable
 * and may be left unset by a plugin that does not support it. Genre, theme,
 * demographic and type codes are plain strings rather than enums, so the
 * contract does not depend on the host application's internal enums.
 */
class PluginAnimeData
{
    /**
     * @param string[]|null $alternativeNames
     * @param string[]|null $descriptions
     * @param string[]|null $genres
     * @param string[]|null $themes
     * @param string[]|null $studios
     * @param string[]|null $countries
     * @param string[]|null $images
     */
    public function __construct(
        public readonly string $title,
        public readonly ?array $alternativeNames = null,
        public readonly ?array $descriptions = null,
        public readonly ?array $genres = null,
        public readonly ?array $themes = null,
        public readonly ?string $demographic = null,
        public readonly ?array $studios = null,
        public readonly ?string $type = null,
        public readonly ?DateTimeImmutable $datePremiere = null,
        public readonly ?DateTimeImmutable $dateEnd = null,
        public readonly ?int $durationMinutes = null,
        public readonly ?int $episodesCount = null,
        public readonly ?array $countries = null,
        public readonly ?string $cover = null,
        public readonly ?array $images = null,
    ) {
    }
}
