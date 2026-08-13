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
 * Static description of a widget, returned by {@see CatalogWidgetInterface::metadata()}
 * and {@see EntryWidgetInterface::metadata()}.
 */
final class WidgetMetadata
{
    public function __construct(
        /**
         * Stable code name of the widget, matching `[a-z0-9-]+`.
         *
         * Used by the host as the DI tag / URL segment / `features` key
         * (`{pluginId}:{name}`) to identify the widget without instantiating
         * its class. Changing it after release breaks every host reference
         * to the widget and requires a migration.
         */
        public readonly string $name,
        /**
         * Translation key for the widget name shown on the host settings
         * page instead of the machine `$name`.
         *
         * Resolved by the host's Translator in the translation domain equal
         * to the plugin id, in the current UI locale. The plugin must ship
         * strings for this key in its `translations/`, at least for the
         * default locale.
         */
        public readonly string $titleKey,
        /**
         * Translation key for the widget description shown on the host
         * settings page.
         *
         * Resolved by the host's Translator in the translation domain equal
         * to the plugin id, in the current UI locale. The plugin must ship
         * strings for this key in its `translations/`, at least for the
         * default locale.
         */
        public readonly string $descriptionKey,
    ) {
    }
}
