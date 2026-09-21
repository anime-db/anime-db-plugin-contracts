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

namespace AnimeDb\PluginContracts\Media;

/**
 * Thrown by {@see MediaProbeInterface::probe()}/`probeAll()` when no prober
 * is available at all — disabled in the host application's settings, or not
 * installed on the host.
 *
 * A plugin catches this and degrades gracefully — reports "no technical data
 * available" rather than treating it as a hard failure — the same way
 * {@see \AnimeDb\PluginContracts\Llm\LlmDisabledException} is handled.
 *
 * Deliberately distinct from {@see MediaProbeFailedException}: this one
 * means the capability itself is absent, that one means the capability
 * exists but a specific file could not be parsed. Deliberately has no base
 * class in common with either that exception or
 * {@see StorageUnavailableException} — see {@see StorageUnavailableException}
 * for why.
 */
final class MediaProbeUnavailableException extends \RuntimeException
{
}
