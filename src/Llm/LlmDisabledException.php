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

namespace AnimeDb\PluginContracts\Llm;

/**
 * Thrown by a {@see LlmServiceInterface::parse()} implementation when the local LLM is
 * disabled in the host application's settings.
 *
 * Deliberately not a transport failure: a plugin catches this separately from
 * {@see \Psr\Http\Client\ClientExceptionInterface} and gracefully degrades — continues
 * without LLM-assisted enrichment — rather than treating it as a hard failure.
 */
final class LlmDisabledException extends \RuntimeException
{
}
