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

namespace AnimeDb\PluginContracts\Widget;

/**
 * The single definition of the shape a widget passes, one instance per list
 * entry, to the host's core `plugin/_widget_list.html.twig` partial. That
 * partial's own docblock currently describes this shape in prose; it should
 * be replaced with a reference to this class instead of restating the fields.
 *
 * Only the shape is defined here: no filesystem or other I/O, and no
 * validation of field content (URL well-formedness, HTML escaping) — that
 * stays with the host, in `PluginHtmlSanitizer` and the Twig template
 * itself, the same division of responsibility as {@see \AnimeDb\PluginContracts\Manifest\PluginUi}.
 */
final class WidgetListItem
{
    public function __construct(
        /**
         * Absolute URL or `app-media://` URL of the item's thumbnail image.
         *
         * `null` (or empty) makes the host render a placeholder instead.
         */
        public readonly ?string $thumbnail,
        /**
         * The item's title, shown as plain text.
         */
        public readonly string $title,
        /**
         * Short metadata line shown under the title, or `null` when the
         * plugin has nothing to show there.
         */
        public readonly ?string $subtitle,
        /**
         * Link target for the whole card.
         */
        public readonly string $url,
    ) {
    }
}
