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

use AnimeDb\PluginContracts\Catalog\CatalogReaderInterface;
use AnimeDb\PluginContracts\ExternalIdResolutionInterface;

/**
 * Widget rendered on a shared catalog page (e.g. "new releases"), without
 * any single-record context.
 *
 * A plugin may declare several widgets, one class per widget, each toggled
 * independently in the host UI. The rendered output is a raw HTML string
 * rather than structured data: this keeps the contract simple and covers
 * widgets that are not a list of catalog records. Visual consistency for
 * the common "list of records" case is a host-side optional helper, not a
 * rigid schema in this contract.
 *
 * `render()` takes no record context: for a per-record state, including the
 * already-resolved external id, a plugin uses {@see CatalogReaderInterface}
 * instead. A widget that needs its own external source id (to call its own
 * API) implements {@see ExternalIdResolutionInterface} additionally and
 * explicitly, as a declared capability rather than a side effect of
 * inheritance.
 */
interface CatalogWidgetInterface
{
    /**
     * Widget metadata: code name and title/description translation keys.
     *
     * Static so the host can read {@see WidgetMetadata::$name} (for its
     * DI tag / URL / `features` key) while compiling the container, without
     * instantiating the widget class. Must return a literal value object
     * with no heavy logic or side effects: it runs at container build time
     * on plugin install/activate.
     */
    public static function metadata(): WidgetMetadata;

    /**
     * Render the widget without any single-record context.
     *
     * @return string rendered widget markup as a raw HTML string
     */
    public function render(): string;
}
