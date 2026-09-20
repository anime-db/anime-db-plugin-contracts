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

use AnimeDb\PluginContracts\Media\MediaFile;
use AnimeDb\PluginContracts\Media\MediaInfo;
use AnimeDb\PluginContracts\Media\MediaProbeFailedException;
use AnimeDb\PluginContracts\Media\MediaProbeInterface;
use AnimeDb\PluginContracts\Media\MediaProbeUnavailableException;
use PHPUnit\Framework\TestCase;

class MediaProbeInterfaceTest extends TestCase
{
    public function testProbeReturnsMediaInfo(): void
    {
        $info = $this->createInfo('ffmpeg-6.1');
        $probe = $this->createProbe(['01.mkv' => $info], 'ffmpeg-6.1');

        self::assertSame($info, $probe->probe($this->createFile('01.mkv')));
    }

    public function testProbeThrowsWhenNoProberIsAvailable(): void
    {
        $probe = $this->createProbe([], 'ffmpeg-6.1', unavailable: true);

        $this->expectException(MediaProbeUnavailableException::class);

        $probe->probe($this->createFile('01.mkv'));
    }

    public function testProbeThrowsWhenTheFileCouldNotBeParsed(): void
    {
        $probe = $this->createProbe([], 'ffmpeg-6.1');

        $this->expectException(MediaProbeFailedException::class);

        $probe->probe($this->createFile('corrupt.mkv'));
    }

    public function testProbeAllReturnsSuccessfullyParsedFilesOnlyOnPartialFailure(): void
    {
        $info = $this->createInfo('ffmpeg-6.1');
        $probe = $this->createProbe(['01.mkv' => $info], 'ffmpeg-6.1');

        $result = $probe->probeAll([$this->createFile('01.mkv'), $this->createFile('corrupt.mkv')]);

        self::assertSame([$info], $result);
    }

    public function testProbeAllThrowsOnlyWhenEveryFileFailed(): void
    {
        $probe = $this->createProbe([], 'ffmpeg-6.1');

        $this->expectException(MediaProbeFailedException::class);

        $probe->probeAll([$this->createFile('corrupt.mkv')]);
    }

    public function testProbeIdentityReturnsCurrentValueWithoutProbingAFile(): void
    {
        $probe = $this->createProbe([], 'ffmpeg-6.1');

        self::assertSame('ffmpeg-6.1', $probe->probeIdentity());
    }

    private function createFile(string $name): MediaFile
    {
        return new MediaFile($name, $name, 1024, new \DateTimeImmutable('2026-08-11T12:00:00+00:00'));
    }

    private function createInfo(string $probeIdentity): MediaInfo
    {
        return new MediaInfo('matroska,webm', 1440.5, 734003200, 4700000, 0, [], [], [], [], $probeIdentity);
    }

    /**
     * @param array<string, MediaInfo> $resultsByFileName
     */
    private function createProbe(array $resultsByFileName, string $probeIdentity, bool $unavailable = false): MediaProbeInterface
    {
        return new class($resultsByFileName, $probeIdentity, $unavailable) implements MediaProbeInterface {
            /**
             * @param array<string, MediaInfo> $resultsByFileName
             */
            public function __construct(
                private readonly array $resultsByFileName,
                private readonly string $probeIdentity,
                private readonly bool $unavailable,
            ) {
            }

            public function probe(MediaFile $file): MediaInfo
            {
                if ($this->unavailable) {
                    throw new MediaProbeUnavailableException('no prober installed');
                }

                return $this->resultsByFileName[$file->name] ?? throw new MediaProbeFailedException(\sprintf('could not parse "%s"', $file->name));
            }

            public function probeAll(array $files): array
            {
                if ($this->unavailable) {
                    throw new MediaProbeUnavailableException('no prober installed');
                }

                $results = [];
                foreach ($files as $file) {
                    if (isset($this->resultsByFileName[$file->name])) {
                        $results[] = $this->resultsByFileName[$file->name];
                    }
                }

                if ($results === [] && $files !== []) {
                    throw new MediaProbeFailedException('none of the given files could be parsed');
                }

                return $results;
            }

            public function probeIdentity(): string
            {
                return $this->probeIdentity;
            }
        };
    }
}
