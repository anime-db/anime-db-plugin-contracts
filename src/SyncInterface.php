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
 * Sync user lists between the host application and an external source.
 *
 * Unlike the interactive filler/widget/search features, sync is configured once and
 * runs in the background. It covers two independent directions: push (local state to
 * the external source) and pull (external source to local state).
 */
interface SyncInterface extends PluginInterface
{
    /**
     * Push a local list item change to the external source.
     */
    public function push(SyncItem $item): void;

    /**
     * Pull the user's list from the external source.
     *
     * @return iterable<SyncItem>
     */
    public function pull(): iterable;
}
