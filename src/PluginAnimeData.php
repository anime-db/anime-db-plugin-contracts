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
        private readonly string $title,
        private readonly ?array $alternativeNames = null,
        private readonly ?array $descriptions = null,
        private readonly ?array $genres = null,
        private readonly ?array $themes = null,
        private readonly ?string $demographic = null,
        private readonly ?array $studios = null,
        private readonly ?string $type = null,
        private readonly ?DateTimeImmutable $datePremiere = null,
        private readonly ?DateTimeImmutable $dateEnd = null,
        private readonly ?int $durationMinutes = null,
        private readonly ?int $episodesCount = null,
        private readonly ?array $countries = null,
        private readonly ?string $cover = null,
        private readonly ?array $images = null,
    ) {
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return string[]|null
     */
    public function getAlternativeNames(): ?array
    {
        return $this->alternativeNames;
    }

    /**
     * @return string[]|null
     */
    public function getDescriptions(): ?array
    {
        return $this->descriptions;
    }

    /**
     * @return string[]|null
     */
    public function getGenres(): ?array
    {
        return $this->genres;
    }

    /**
     * @return string[]|null
     */
    public function getThemes(): ?array
    {
        return $this->themes;
    }

    public function getDemographic(): ?string
    {
        return $this->demographic;
    }

    /**
     * @return string[]|null
     */
    public function getStudios(): ?array
    {
        return $this->studios;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function getDatePremiere(): ?DateTimeImmutable
    {
        return $this->datePremiere;
    }

    public function getDateEnd(): ?DateTimeImmutable
    {
        return $this->dateEnd;
    }

    public function getDurationMinutes(): ?int
    {
        return $this->durationMinutes;
    }

    public function getEpisodesCount(): ?int
    {
        return $this->episodesCount;
    }

    /**
     * @return string[]|null
     */
    public function getCountries(): ?array
    {
        return $this->countries;
    }

    public function getCover(): ?string
    {
        return $this->cover;
    }

    /**
     * @return string[]|null
     */
    public function getImages(): ?array
    {
        return $this->images;
    }
}
