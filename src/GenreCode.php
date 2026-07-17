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
 * The genre axis of MAL's 4-axis taxonomy (genres/explicit_genres/themes/demographics) —
 * themes and demographics are separate axes, see {@see ThemeCode} and {@see Demographic}.
 *
 * A closed dictionary, not free-form input, so it is a contract-owned enum
 * rather than a plain string: values are kept 1:1 with the host
 * application's own genre enum, but this package does not depend on it, so
 * the host maps this enum to its internal one instead of sharing it. MAL's
 * genre list is periodically extended, so adding a new case here is a minor
 * (not breaking) version bump.
 */
enum GenreCode: string
{
    case Action = 'action';
    case Adventure = 'adventure';
    case AvantGarde = 'avant-garde';
    case AwardWinning = 'award-winning';
    case BoysLove = 'boys-love';
    case Comedy = 'comedy';
    case Drama = 'drama';
    case Fantasy = 'fantasy';
    case GirlsLove = 'girls-love';
    case Gourmet = 'gourmet';
    case Horror = 'horror';
    case Mystery = 'mystery';
    case Romance = 'romance';
    case SciFi = 'sci-fi';
    case SliceOfLife = 'slice-of-life';
    case Sports = 'sports';
    case Supernatural = 'supernatural';
    case Suspense = 'suspense';
}
