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

namespace AnimeDb\PluginContracts\Manifest;

/**
 * Flat DTO for the `ui` block of a plugin manifest: paths, relative to the plugin's own
 * archive root, of the stylesheet and script files the host itself inserts into the page
 * shell when it renders this plugin's UI. Every path must live under the plugin's `assets/`
 * directory and is only checked for form here — this package does not touch the file system,
 * so whether the file actually exists is verified elsewhere (the plugin monorepo's tooling
 * and the host's own installer).
 */
final class PluginUi
{
    /**
     * @param list<string> $css paths of stylesheets under `assets/`, e.g. `assets/carousel.css`
     * @param list<string> $js  paths of scripts under `assets/`, e.g. `assets/settings.js`
     */
    public function __construct(
        public readonly array $css,
        public readonly array $js,
    ) {
    }
}
