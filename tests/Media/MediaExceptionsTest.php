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

namespace AnimeDb\PluginContracts\Tests\Media;

use AnimeDb\PluginContracts\Media\MediaProbeFailedException;
use AnimeDb\PluginContracts\Media\MediaProbeUnavailableException;
use AnimeDb\PluginContracts\Media\StorageUnavailableException;
use PHPUnit\Framework\TestCase;

class MediaExceptionsTest extends TestCase
{
    /**
     * @return iterable<string, array{0: class-string<\RuntimeException>}>
     */
    public static function exceptionClasses(): iterable
    {
        yield StorageUnavailableException::class => [StorageUnavailableException::class];
        yield MediaProbeUnavailableException::class => [MediaProbeUnavailableException::class];
        yield MediaProbeFailedException::class => [MediaProbeFailedException::class];
    }

    /**
     * @param class-string<\RuntimeException> $exceptionClass
     *
     * @dataProvider exceptionClasses
     */
    public function testEachExceptionIsCatchableAsRuntimeException(string $exceptionClass): void
    {
        $this->expectException(\RuntimeException::class);

        throw new $exceptionClass('failure');
    }

    public function testTheThreeExceptionsShareNoCommonBaseBeyondRuntimeException(): void
    {
        $storageUnavailable = new StorageUnavailableException('storage is offline');
        $probeUnavailable = new MediaProbeUnavailableException('no prober installed');
        $probeFailed = new MediaProbeFailedException('could not parse file');

        self::assertNotInstanceOf(MediaProbeUnavailableException::class, $storageUnavailable);
        self::assertNotInstanceOf(MediaProbeFailedException::class, $storageUnavailable);

        self::assertNotInstanceOf(StorageUnavailableException::class, $probeUnavailable);
        self::assertNotInstanceOf(MediaProbeFailedException::class, $probeUnavailable);

        self::assertNotInstanceOf(StorageUnavailableException::class, $probeFailed);
        self::assertNotInstanceOf(MediaProbeUnavailableException::class, $probeFailed);
    }

    public function testStorageUnavailableExceptionIsNotCaughtByAProbeSpecificCatchBlock(): void
    {
        try {
            throw new StorageUnavailableException('storage is offline');
        } catch (MediaProbeUnavailableException|MediaProbeFailedException $exception) {
            self::fail('StorageUnavailableException must not be caught as a probe-specific exception.');
        } catch (StorageUnavailableException) {
            $this->addToAssertionCount(1);
        }
    }
}
