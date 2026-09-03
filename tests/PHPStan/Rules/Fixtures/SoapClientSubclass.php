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

namespace AnimeDb\PluginContracts\Tests\PHPStan\Rules\Fixtures;

/**
 * A subclass of a NoDangerousPrimitivesRule-forbidden class, used to verify the rule
 * catches instantiation through a subclass, not just the exact class.
 *
 * Declared in its own PSR-4-autoloadable file (unlike the fixtures under
 * tests/PHPStan/Rules/data/, which are plain scripts fed to RuleTestCase and not meant to
 * be autoloadable): PHPStan\Testing\RuleTestCase resolves a *referenced* class's ancestors
 * through the same reflection sources PHP's own autoloader would use, so a subclass
 * declared inside a non-autoloadable, multi-class fixture script can't be looked up by
 * name and the `isSuperTypeOf()` type check this rule relies on degrades to "maybe"
 * instead of "yes".
 */
final class SoapClientSubclass extends \SoapClient
{
}
