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
 * The role a name plays for an anime entry, independent of its language.
 *
 * Orthogonal to {@see AnimeName::$locale}: a name is classified by what it
 * is (an official title, a synonym, a short form), not by what language it
 * is in — the two axes vary independently, e.g. an official title and a
 * synonym can both exist in the same language. The primary title itself is
 * not a role in this enum — it lives in a dedicated scalar field of its own
 * (e.g. {@see \AnimeDb\PluginContracts\Filler\PluginAnimeData::$title}),
 * not in the alternative-names collection.
 */
enum NameRole: string
{
    case Official = 'official';
    case Synonym = 'synonym';
    case Short = 'short';
}
