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
 *
 * Origin of the dictionary: a mirror of the Themes section of the MyAnimeList
 * taxonomy (https://myanimelist.net/anime.php). The 52 values match that section
 * by name (the case values are lowercase slugs of the MAL names, see the mapping rule below). The axis is MAL, not Shikimori: Shikimori inherited the MAL
 * taxonomy but diverged from it (`Award Winning` is a theme there, while in MAL
 * and in this contract it is a genre; it also keeps the legacy `Yaoi` and `Yuri`
 * apart from `Boys Love` and `Girls Love`).
 *
 * The fourth MAL section, Explicit Genres (`Ecchi`, `Erotica`, `Hentai`), is
 * excluded on purpose: this is a decision, not a gap in the dictionary.
 *
 * The values of {@see GenreCode}, {@see ThemeCode} and {@see Demographic} do not
 * overlap. A plugin maps a source term by normalizing its English name to a slug
 * (lowercase; every run of characters outside `[a-z0-9]` collapses into a single `-`;
 * leading and trailing `-` are trimmed, so `Idols (Female)` becomes `idols-female`,
 * not `idols-(female)`), calling `tryFrom()` on all three enums, routing by the match, and dropping the term
 * (with a log entry) when none of them matches.
 *
 * The dictionaries reflect MAL after its 2022 reorganization. A source that
 * speaks the pre-reorganization vocabulary cannot be resolved by direct name
 * matching, for example: `Thriller` became `Suspense`, `Shoujo-ai` became `Girls Love`,
 * `Shounen-ai` became `Boys Love`, `Dementia` became `Avant Garde`, and `Magic`
 * was removed altogether. This list is not exhaustive (other terms, e.g. `Cars`, `Game`,
 * `Demons`, `Police`, were also renamed, removed or moved between axes). A plugin for such a source needs its own table of
 * exceptions.
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
