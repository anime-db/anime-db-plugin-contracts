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
 * entry, to the host's public `plugin/_widget_list.html.twig` partial — the
 * core extension point a widget includes in its own template to render a
 * list of anime entries.
 *
 * Only the shape is defined here: no filesystem or other I/O, and no
 * validation of field content (URL well-formedness, HTML escaping) — the
 * host validates and escapes field content at render time, the same
 * division of responsibility as {@see \AnimeDb\PluginContracts\Manifest\PluginUi}.
 */
final class WidgetListItem
{
    public function __construct(
        /**
         * Absolute `http(s)://` URL or `app-media://` URL of the item's
         * thumbnail image, or `null`/an empty string when the item has no
         * thumbnail — the host then renders a placeholder.
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
         * Link target for the whole card, opened in the system browser:
         * an absolute `http(s)://` URL of the plugin's own external page
         * for the record. A path relative to the host's own site root is
         * not a valid value here — the host resolves such a link back to
         * itself and refuses to open it, so the card would appear
         * clickable but do nothing. Schemes other than `http`/`https`
         * (e.g. `javascript:`, `data:`) are outside this contract; the
         * host is free to reject the value or drop the link in that case.
         */
        public readonly string $url,
    ) {
    }
}
