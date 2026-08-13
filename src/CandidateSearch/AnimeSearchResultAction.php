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

namespace AnimeDb\PluginContracts\CandidateSearch;

/**
 * A single action offered by the plugin for an {@see AnimeSearchResultItem}
 * (e.g. "download"), shown to the user and, once picked, passed back to
 * {@see DownloadCandidateSearchInterface::runAction()} by `id`.
 */
final class AnimeSearchResultAction
{
    public function __construct(
        /**
         * Action id, opaque to the core, interpreted by the plugin that produced it.
         */
        public readonly string $id,
        /**
         * Human-readable label shown to the user for this action.
         */
        public readonly string $label,
    ) {
    }
}
