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

namespace AnimeDb\PluginContracts\Tests;

use AnimeDb\PluginContracts\Llm\LlmDisabledException;
use AnimeDb\PluginContracts\Llm\LlmServiceInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Client\ClientExceptionInterface;

class LlmServiceInterfaceTest extends TestCase
{
    public function testImplementationMayThrowLlmDisabledException(): void
    {
        $service = $this->createServiceThrowing(new LlmDisabledException('LLM is disabled'));

        $this->expectException(LlmDisabledException::class);

        $service->parse('prompt');
    }

    public function testImplementationMayThrowClientExceptionInterface(): void
    {
        $transportError = new class('transport failure') extends \RuntimeException implements ClientExceptionInterface {
        };
        $service = $this->createServiceThrowing($transportError);

        $this->expectException(ClientExceptionInterface::class);

        $service->parse('prompt');
    }

    public function testImplementationMayThrowJsonException(): void
    {
        $service = $this->createServiceThrowing(new \JsonException('malformed response'));

        $this->expectException(\JsonException::class);

        $service->parse('prompt');
    }

    private function createServiceThrowing(\Throwable $exception): LlmServiceInterface
    {
        return new class($exception) implements LlmServiceInterface {
            public function __construct(private readonly \Throwable $exception)
            {
            }

            public function parse(string $prompt): array
            {
                throw $this->exception;
            }
        };
    }
}
