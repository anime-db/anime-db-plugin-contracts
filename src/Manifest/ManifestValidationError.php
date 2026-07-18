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

namespace AnimeDb\PluginContracts\Manifest;

/**
 * A single, structured `manifest.json` validation failure, as reported by {@see ManifestValidator}.
 *
 * Kept structured (field + message) rather than a plain string or a boolean result, so both
 * the market registry's CI and the host application's client installer can show the plugin
 * author a precise reason for rejection instead of a generic "invalid manifest".
 */
final class ManifestValidationError
{
    public function __construct(
        /**
         * Dot-path of the offending field, e.g. `require.core` or `features.filler`.
         */
        public readonly string $field,
        public readonly string $message,
    ) {
    }
}
