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

use AnimeDb\PluginContracts\Media\AudioTrack;
use AnimeDb\PluginContracts\Media\MediaInfo;
use AnimeDb\PluginContracts\Media\OtherTrack;
use AnimeDb\PluginContracts\Media\SubtitleTrack;
use AnimeDb\PluginContracts\Media\VideoTrack;
use PHPUnit\Framework\TestCase;

class MediaInfoTest extends TestCase
{
    public function testPropertiesReturnConstructorValues(): void
    {
        $video = new VideoTrack(0, 'h264', 'High', 1920, 1080, 'yuv420p', 23.976, 4500000);
        $audio = new AudioTrack(1, 'aac', 2, 'stereo', 48000, 192000, 'jpn', 'Original', true);
        $subtitle = new SubtitleTrack(2, 'ass', 'eng', 'English', true, false);
        $other = new OtherTrack(3, 'attachment', 'ttf');

        $info = new MediaInfo(
            containerFormat: 'matroska,webm',
            durationSeconds: 1440.5,
            sizeBytes: 734003200,
            bitRate: 4700000,
            trackCount: 4,
            video: [$video],
            audio: [$audio],
            subtitles: [$subtitle],
            otherTracks: [$other],
            probeIdentity: 'ffmpeg-6.1',
        );

        self::assertSame('matroska,webm', $info->containerFormat);
        self::assertSame(1440.5, $info->durationSeconds);
        self::assertSame(734003200, $info->sizeBytes);
        self::assertSame(4700000, $info->bitRate);
        self::assertSame(4, $info->trackCount);
        self::assertSame([$video], $info->video);
        self::assertSame([$audio], $info->audio);
        self::assertSame([$subtitle], $info->subtitles);
        self::assertSame([$other], $info->otherTracks);
        self::assertSame('ffmpeg-6.1', $info->probeIdentity);
    }

    public function testUnreportedFieldsAreNull(): void
    {
        $info = new MediaInfo(
            containerFormat: 'mpegts',
            durationSeconds: null,
            sizeBytes: 0,
            bitRate: null,
            trackCount: 0,
            video: [],
            audio: [],
            subtitles: [],
            otherTracks: [],
            probeIdentity: 'ffmpeg-6.1',
        );

        self::assertNull($info->durationSeconds);
        self::assertNull($info->bitRate);
    }

    /**
     * @return iterable<string, array{0: VideoTrack[], 1: AudioTrack[], 2: SubtitleTrack[], 3: OtherTrack[]}>
     */
    public static function trackLists(): iterable
    {
        yield 'no tracks at all' => [[], [], [], []];
        yield 'video and audio only' => [
            [new VideoTrack(0, 'h264', null, 1920, 1080, null, null, null)],
            [new AudioTrack(1, 'aac', 2, null, 48000, null, null, null, true)],
            [],
            [],
        ];
        yield 'every list populated, including otherTracks' => [
            [new VideoTrack(0, 'h264', null, 1920, 1080, null, null, null)],
            [
                new AudioTrack(1, 'aac', 2, null, 48000, null, 'jpn', null, true),
                new AudioTrack(2, 'aac', 2, null, 48000, null, 'eng', null, false),
            ],
            [new SubtitleTrack(3, 'ass', 'eng', null, true, false)],
            [new OtherTrack(4, 'attachment', 'ttf')],
        ];
    }

    /**
     * @param VideoTrack[]    $video
     * @param AudioTrack[]    $audio
     * @param SubtitleTrack[] $subtitles
     * @param OtherTrack[]    $otherTracks
     *
     * @dataProvider trackLists
     */
    public function testTrackCountEqualsSumOfAllFourTrackLists(
        array $video,
        array $audio,
        array $subtitles,
        array $otherTracks,
    ): void {
        $trackCount = \count($video) + \count($audio) + \count($subtitles) + \count($otherTracks);

        $info = new MediaInfo(
            containerFormat: 'matroska,webm',
            durationSeconds: 1440.5,
            sizeBytes: 734003200,
            bitRate: 4700000,
            trackCount: $trackCount,
            video: $video,
            audio: $audio,
            subtitles: $subtitles,
            otherTracks: $otherTracks,
            probeIdentity: 'ffmpeg-6.1',
        );

        self::assertSame(
            $info->trackCount,
            \count($info->video) + \count($info->audio) + \count($info->subtitles) + \count($info->otherTracks),
        );
    }
}
