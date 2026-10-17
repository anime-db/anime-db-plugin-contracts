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

namespace AnimeDb\PluginContracts\Catalog;

/**
 * Vocabulary of catalog entry fields visible to plugins, used as keys of
 * {@see AnimeChangedEvent::$changes} and {@see AnimeRemovedEvent::$lastValues}. The
 * values are decoupled from the host's own field names: the host projects its field
 * onto a case here, so renaming a host field does not break plugins.
 *
 * Shape of the value of each field:
 *
 * | Field               | Value type              |
 * |---------------------|-------------------------|
 * | `title`             | `string`                |
 * | `alternative_names` | `list<AnimeName>`       |
 * | `type`              | `?AnimeType`            |
 * | `demographic`       | `?Demographic`          |
 * | `genres`            | `list<GenreCode>`       |
 * | `themes`            | `list<ThemeCode>`       |
 * | `episodes_count`    | `?int`                  |
 * | `sources`           | `list<string>`          |
 * | `description`       | `array<locale, string>` |
 * | `date_premiere`     | `?DateTimeImmutable`    |
 * | `date_end`          | `?DateTimeImmutable`    |
 * | `duration_minutes`  | `?int`                  |
 * | `studios`           | `list<string>`          |
 * | `countries`         | `list<string>`          |
 * | `cover`             | `?string`               |
 *
 * The types are the contract types from `AnimeDb\PluginContracts\Model\`. Details:
 *  - `episodes_count` is null for types without episodes; the key is always present.
 *  - `sources` and `studios` are sorted alphabetically.
 *  - `description`: the key is a locale; the source of a description is not part of the value.
 *  - `date_premiere`, `date_end`: only the date is meaningful, the time is 00:00 UTC.
 *  - `countries` are ISO alpha-2 codes in upper case, sorted alphabetically.
 *  - `cover` is an opaque token equal to the cover file name, without a path.
 *  - Lists are sorted deterministically. Sort keys: `alternative_names` by (`locale` or an
 *    empty string, `role->value`, `name`); `genres` and `themes` by `value`; string lists
 *    alphabetically (`strcmp`).
 *  - A collection is passed as a whole: the full list before and the full list after.
 *
 * Deliberately not in the vocabulary: watch status, watched episodes, rating, notes, tags,
 * storage and path, external ids, gallery.
 *
 * Adding a case is a decision to expose more data to plugins, not an additive edit, and
 * goes through a disclosure review. The enum grows, so a `match` over it must have a
 * `default` branch.
 */
enum AnimeField: string
{
    case Title = 'title';
    case AlternativeNames = 'alternative_names';
    case Type = 'type';
    case Demographic = 'demographic';
    case Genres = 'genres';
    case Themes = 'themes';
    case EpisodesCount = 'episodes_count';
    case Sources = 'sources';
    case Description = 'description';
    case DatePremiere = 'date_premiere';
    case DateEnd = 'date_end';
    case DurationMinutes = 'duration_minutes';
    case Studios = 'studios';
    case Countries = 'countries';
    case Cover = 'cover';
}
