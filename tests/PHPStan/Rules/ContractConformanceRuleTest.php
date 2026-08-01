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

namespace AnimeDb\PluginContracts\Tests\PHPStan\Rules;

use AnimeDb\PluginContracts\PHPStan\Rules\ContractConformanceRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<ContractConformanceRule>
 */
class ContractConformanceRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new ContractConformanceRule(self::createReflectionProvider());
    }

    public function testConformingImplementationAndRenamedParameterAreAccepted(): void
    {
        $this->analyse([__DIR__.'/data/contract-conformance.php'], [
            [
                'Method AnimeDb\PluginContracts\Tests\PHPStan\Rules\Data\WrongParameterTypePlugin::push() does not match the contract declared by AnimeDb\PluginContracts\Sync\SyncInterface for the installed version of anime-db/plugin-contracts: expected `push(AnimeDb\PluginContracts\Sync\SyncItem): void`, got `push(object): void`.',
                92,
            ],
            [
                'Method AnimeDb\PluginContracts\Tests\PHPStan\Rules\Data\WrongReturnTypePlugin::pull() does not match the contract declared by AnimeDb\PluginContracts\Sync\SyncInterface for the installed version of anime-db/plugin-contracts: expected `pull(): iterable<AnimeDb\PluginContracts\Sync\SyncItem>`, got `pull(): array`.',
                128,
            ],
        ]);
    }
}
