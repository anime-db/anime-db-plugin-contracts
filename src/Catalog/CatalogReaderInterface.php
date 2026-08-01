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

namespace AnimeDb\PluginContracts\Catalog;

use AnimeDb\PluginContracts\Download\DownloadServiceInterface;
use AnimeDb\PluginContracts\Llm\LlmServiceInterface;
use AnimeDb\PluginContracts\Model\AnimeId;

/**
 * Core-provided read-only access to a catalog record's current state, for
 * plugins that need shared fields beyond their own slice — a widget
 * rendering a record's card, or post-download enrichment reading the
 * episode/file list.
 *
 * A plugin obtains this service the same way it obtains
 * {@see LlmServiceInterface} or {@see DownloadServiceInterface}: via
 * constructor injection, type-hinting this interface. The host implementation
 * resolves {@see AnimeView::$externalId} for the specific plugin instance it
 * was injected into.
 *
 * Read-only: this contract has no method to persist changes to the shared
 * record. A plugin's own slice is read and written through a dedicated
 * per-plugin data store, not through this interface.
 */
interface CatalogReaderInterface
{
    /**
     * Read the current state of a catalog record.
     *
     * @return AnimeView|null current state of the record, or null if no record exists for `$anime`
     */
    public function read(AnimeId $anime): ?AnimeView;
}
