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
 * A single name of an anime entry, carrying language and role as two
 * independent axes.
 *
 * `locale` is a free-form string rather than a closed enum, deliberately —
 * sources disagree on how they express language (e.g. `russian`/`english`,
 * `Japanese`/`German`, or values that are not language codes at all), and a
 * closed dictionary here would need a minor release, with every plugin
 * reissued, each time an unseen value shows up. The list above describes the
 * raw shapes a source may hand the plugin, not accepted values of this
 * field — the plugin is responsible for normalizing them before assigning
 * `locale`. The same shape is already used for
 * {@see \AnimeDb\PluginContracts\Filler\PluginAnimeData::$descriptions}
 * locale keys.
 *
 * Expected form: the primary ISO 639-1 subtag, lowercase — `ja`, `ru`, `en`,
 * `ko`. `null` means the source did not declare a language at all (e.g. for
 * an untyped list of synonyms) — a normal value, not an error marker. The
 * host lowercases the value and strips any regional subtag (`ru-RU` becomes
 * `ru`), and discards anything it does not recognize as a language subtag to
 * `null` — so raw source labels such as `russian` or `Japanese` will not
 * survive unchanged.
 */
final class AnimeName
{
    public function __construct(
        public readonly string $name,
        public readonly ?string $locale,
        public readonly NameRole $role,
    ) {
    }
}
