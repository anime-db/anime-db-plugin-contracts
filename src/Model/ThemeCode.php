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

namespace AnimeDb\PluginContracts\Model;

/**
 * The theme axis of MAL's 4-axis taxonomy (genres/explicit_genres/themes/demographics) —
 * genres and demographics are separate axes, see {@see GenreCode} and {@see Demographic}.
 *
 * A closed dictionary, not free-form input, so it is a contract-owned enum
 * rather than a plain string: values are kept 1:1 with the host
 * application's own theme enum, but this package does not depend on it, so
 * the host maps this enum to its internal one instead of sharing it. MAL's
 * theme list is periodically extended, so adding a new case here is a minor
 * (not breaking) version bump.
 */
enum ThemeCode: string
{
    case AdultCast = 'adult-cast';
    case Anthropomorphic = 'anthropomorphic';
    case CGDCT = 'cgdct';
    case Childcare = 'childcare';
    case CombatSports = 'combat-sports';
    case Crossdressing = 'crossdressing';
    case Delinquents = 'delinquents';
    case Detective = 'detective';
    case Educational = 'educational';
    case GagHumor = 'gag-humor';
    case Gore = 'gore';
    case Harem = 'harem';
    case HighStakesGame = 'high-stakes-game';
    case Historical = 'historical';
    case IdolsFemale = 'idols-female';
    case IdolsMale = 'idols-male';
    case Isekai = 'isekai';
    case Iyashikei = 'iyashikei';
    case LovePolygon = 'love-polygon';
    case LoveStatusQuo = 'love-status-quo';
    case MagicalSexShift = 'magical-sex-shift';
    case MahouShoujo = 'mahou-shoujo';
    case MartialArts = 'martial-arts';
    case Mecha = 'mecha';
    case Medical = 'medical';
    case Military = 'military';
    case Music = 'music';
    case Mythology = 'mythology';
    case OrganizedCrime = 'organized-crime';
    case OtakuCulture = 'otaku-culture';
    case Parody = 'parody';
    case PerformingArts = 'performing-arts';
    case Pets = 'pets';
    case Psychological = 'psychological';
    case Racing = 'racing';
    case Reincarnation = 'reincarnation';
    case ReverseHarem = 'reverse-harem';
    case Samurai = 'samurai';
    case School = 'school';
    case Showbiz = 'showbiz';
    case Space = 'space';
    case StrategyGame = 'strategy-game';
    case SuperPower = 'super-power';
    case Survival = 'survival';
    case TeamSports = 'team-sports';
    case TimeTravel = 'time-travel';
    case UrbanFantasy = 'urban-fantasy';
    case Vampire = 'vampire';
    case VideoGame = 'video-game';
    case Villainess = 'villainess';
    case VisualArts = 'visual-arts';
    case Workplace = 'workplace';
}
