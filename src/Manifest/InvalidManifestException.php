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

namespace AnimeDb\PluginContracts\Manifest;

/**
 * Thrown by {@see ManifestParser::parse()} when raw manifest content is syntactically valid
 * JSON but fails {@see ManifestValidator} content checks (missing fields, wrong types, bad
 * version constraints, etc.).
 *
 * Deliberately not used for JSON syntax problems — those are {@see InvalidManifestJsonException}.
 * Carries the full structured {@see ManifestValidationError} list, so a caller who ends up
 * catching this exception doesn't lose the detail a manual {@see ManifestValidator::validate()}
 * call would have given them.
 */
final class InvalidManifestException extends \RuntimeException
{
    /**
     * @param ManifestValidationError[] $errors
     */
    public function __construct(public readonly array $errors)
    {
        parent::__construct(\sprintf(
            'manifest.json is invalid: %s',
            implode('; ', array_map(
                static fn (ManifestValidationError $error): string => \sprintf('%s: %s', $error->field, $error->message),
                $errors,
            )),
        ));
    }
}
