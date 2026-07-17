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

use AnimeDb\PluginContracts\PHPStan\Rules\NoDangerousPrimitivesRule;
use PHPStan\Rules\Rule;
use PHPStan\Testing\RuleTestCase;

/**
 * @extends RuleTestCase<NoDangerousPrimitivesRule>
 */
class NoDangerousPrimitivesRuleTest extends RuleTestCase
{
    protected function getRule(): Rule
    {
        return new NoDangerousPrimitivesRule();
    }

    public function testDetectsDangerousPrimitivesAndAllowsSafeUsage(): void
    {
        $this->analyse([__DIR__.'/data/dangerous-primitives.php'], [
            ['Calling exec() directly is forbidden, use the abstraction provided by the host application instead.', 22],
            ['Calling shell_exec() directly is forbidden, use the abstraction provided by the host application instead.', 27],
            ['Using the shell exec operator (backticks) is forbidden, use the abstraction provided by the host application instead.', 32],
            ['Using eval() is forbidden, use the abstraction provided by the host application instead.', 37],
            ['Calling proc_open() directly is forbidden, use the abstraction provided by the host application instead.', 42],
            ['Calling curl_init() directly is forbidden, use the abstraction provided by the host application instead.', 47],
            ['Calling curl_setopt() directly is forbidden, use the abstraction provided by the host application instead.', 48],
            ['Calling file_get_contents() directly is forbidden, use the abstraction provided by the host application instead.', 53],
            ['Dynamic function calls through a variable are forbidden, use the abstraction provided by the host application instead.', 59],
            ['Using variable variables is forbidden, use the abstraction provided by the host application instead.', 65],
        ]);
    }
}
