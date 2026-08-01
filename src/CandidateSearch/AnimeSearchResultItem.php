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

namespace AnimeDb\PluginContracts\CandidateSearch;

use AnimeDb\PluginContracts\Search\SearchByPluginCandidate;

/**
 * A single item of an {@see AnimeSearchResult}, rich enough to be shown directly to
 * the user and acted upon — unlike the lightweight {@see SearchByPluginCandidate}
 * used for bulk title recognition during a scan.
 *
 * Neutral, anime-typed shape without any source-specific semantics: what `fields`
 * mean and what an action actually does is entirely up to the plugin that produced
 * this item.
 */
final class AnimeSearchResultItem
{
    /**
     * @param array<string, string>     $fields  extra fields to show the user, label => value
     * @param AnimeSearchResultAction[] $actions actions available for this item
     */
    public function __construct(
        public readonly string $title,
        /**
         * Id of this candidate on the source, used by the core to match it to an
         * existing catalog record or create a new one when a download is enqueued
         * for it. When the source has no canonical id for the candidate, the
         * plugin falls back to a stable hash of the underlying download reference
         * / source-specific identifier, so the same candidate always resolves to
         * the same id.
         */
        public readonly string $externalId,
        /**
         * Base64-encoded preview cover image, already fetched and downsized by the
         * plugin — the core/client never reaches out to the external source itself.
         */
        public readonly ?string $image,
        public readonly array $fields,
        public readonly array $actions,
        /**
         * Opaque to the core: carried as-is to the client and back to
         * {@see DownloadCandidateSearchInterface::runAction()} unchanged. Only the
         * plugin that produced this item knows how to interpret it.
         *
         * The core wraps this value in a signed envelope (HMAC over an
         * application secret) before it reaches the client, and verifies it on
         * the way back, to prevent client-side tampering — that is an
         * application-side concern, invisible at this contract's level.
         */
        public readonly string $meta,
    ) {
    }
}
