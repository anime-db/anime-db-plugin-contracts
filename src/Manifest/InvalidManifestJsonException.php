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
 * Thrown by {@see ManifestParser} when raw manifest content is not syntactically valid
 * JSON, or its top-level value is not a JSON object.
 *
 * Deliberately not used for manifest *content* problems (missing fields, wrong types,
 * bad version constraints) — those are reported as a structured list of
 * {@see ManifestValidationError} by {@see ManifestValidator} instead of an exception.
 */
final class InvalidManifestJsonException extends \RuntimeException
{
}
