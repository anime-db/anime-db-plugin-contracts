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
            ['Calling exec() directly is forbidden, use the abstraction provided by the host application instead.', 63],
            ['Calling shell_exec() directly is forbidden, use the abstraction provided by the host application instead.', 68],
            ['Using the shell exec operator (backticks) is forbidden, use the abstraction provided by the host application instead.', 73],
            ['Using eval() is forbidden, use the abstraction provided by the host application instead.', 78],
            ['Calling proc_open() directly is forbidden, use the abstraction provided by the host application instead.', 83],
            ['Calling fsockopen() directly is forbidden, use the abstraction provided by the host application instead.', 88],
            ['Calling pfsockopen() directly is forbidden, use the abstraction provided by the host application instead.', 93],
            ['Calling stream_socket_client() directly is forbidden, use the abstraction provided by the host application instead.', 98],
            ['Calling stream_socket_server() directly is forbidden, use the abstraction provided by the host application instead.', 103],
            ['Calling curl_init() directly is forbidden, use the abstraction provided by the host application instead.', 108],
            ['Calling curl_setopt() directly is forbidden, use the abstraction provided by the host application instead.', 109],
            ['Calling file_get_contents() with a URL is forbidden, use the abstraction provided by the host application instead.', 114],
            ['Calling fopen() with a URL is forbidden, use the abstraction provided by the host application instead.', 119],
            ['Calling copy() with a URL is forbidden, use the abstraction provided by the host application instead.', 124],
            ['Calling file() with a URL is forbidden, use the abstraction provided by the host application instead.', 129],
            ['Calling readfile() with a URL is forbidden, use the abstraction provided by the host application instead.', 134],
            ['Using require with a URL is forbidden, use the abstraction provided by the host application instead.', 139],
            ['Dynamic function calls through a variable are forbidden, use the abstraction provided by the host application instead.', 145],
            ['Using variable variables is forbidden, use the abstraction provided by the host application instead.', 151],
            ['Calling simplexml_load_file() with a URL is forbidden, use the abstraction provided by the host application instead.', 156],
            ['Calling getimagesize() with a URL is forbidden, use the abstraction provided by the host application instead.', 161],
            ['Calling file_put_contents() with a URL is forbidden, use the abstraction provided by the host application instead.', 166],
            ['Calling dns_get_record() directly is forbidden, use the abstraction provided by the host application instead.', 171],
            ['Calling gethostbyname() directly is forbidden, use the abstraction provided by the host application instead.', 176],
            ['Calling DOMDocument::load() with a URL is forbidden, use the abstraction provided by the host application instead.', 182],
            ['Calling file_get_contents() with a URL is forbidden, use the abstraction provided by the host application instead.', 187],
            ['Calling Symfony\Component\HttpClient\HttpClient::create() is forbidden, use the abstraction provided by the host application instead.', 192],
            ['Calling Http\Discovery\Psr18ClientDiscovery::find() is forbidden, use the abstraction provided by the host application instead.', 197],
            ['Instantiating Symfony\Component\Process\Process is forbidden, use the abstraction provided by the host application instead.', 202],
            ['Instantiating SoapClient is forbidden, use the abstraction provided by the host application instead.', 208],
            ['Instantiating AnimeDb\PluginContracts\Tests\PHPStan\Rules\Fixtures\SoapClientSubclass is forbidden, use the abstraction provided by the host application instead.', 213],
            ['Instantiating AnimeDb\PluginContracts\Tests\PHPStan\Rules\Fixtures\ProcessSubclass is forbidden, use the abstraction provided by the host application instead.', 218],
            ['Instantiating Symfony\Component\HttpClient\CurlHttpClient is forbidden, use the abstraction provided by the host application instead.', 224],
            ['Instantiating Symfony\Component\HttpClient\NativeHttpClient is forbidden, use the abstraction provided by the host application instead.', 229],
            ['Instantiating Symfony\Component\HttpClient\Psr18Client is forbidden, use the abstraction provided by the host application instead.', 234],
        ]);
    }
}
