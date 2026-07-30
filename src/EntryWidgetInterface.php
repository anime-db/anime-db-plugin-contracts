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

namespace AnimeDb\PluginContracts;

/**
 * Widget rendered on a single catalog record's page.
 *
 * A plugin may declare several widgets, one class per widget, each toggled
 * independently in the host UI. The rendered output is a raw HTML string
 * rather than structured data: this keeps the contract simple and covers
 * widgets that are not a list of catalog records. Visual consistency for
 * the common "list of records" case is a host-side optional helper, not a
 * rigid schema in this contract.
 *
 * The host resolves the external source id via `resolveExternalId()`
 * (inherited from ExternalIdResolutionInterface) and passes it into `render()`;
 * the widget never has access to host-internal record ids.
 */
interface EntryWidgetInterface extends ExternalIdResolutionInterface
{
    /**
     * Render the widget for a single catalog record.
     *
     * @param string|null $externalId external source id resolved by the host via
     *                                resolveExternalId() (null when the record has no
     *                                link to this plugin's source)
     *
     * @return string rendered widget markup as a raw HTML string
     */
    public function render(?string $externalId): string;
}
