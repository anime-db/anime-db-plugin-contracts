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

namespace AnimeDb\PluginContracts;

use Psr\Http\Client\ClientExceptionInterface;

/**
 * Core-provided access to a local LLM, for plugins that need to parse free-form
 * human text (e.g. a loosely-formatted textual description coming from a source) into
 * structured data.
 *
 * The model itself is a core concern, not something every plugin should bundle or
 * call out to independently: a plugin builds a prompt (including an explicit
 * instruction to return JSON) and gets back a decoded associative array. The core
 * implementation is free to reinforce JSON-mode with its own system prompt and to
 * pick/host whichever local model it runs — none of that is visible to the plugin.
 *
 * A plugin obtains this service the same way it obtains a PSR-18 HTTP client: via
 * constructor injection, type-hinting this interface. The plugin depends only on
 * this contracts package; the implementation lives in the host application.
 *
 * The host implementation reaches the model over HTTP through a PSR-18 client, so
 * transport failures surface to the plugin as {@see ClientExceptionInterface}.
 */
interface LlmServiceInterface
{
    /**
     * Run the local LLM on the given prompt and decode its JSON response.
     *
     * @return array<string, mixed> JSON decoded from the model's response
     *
     * @throws LlmDisabledException     if the local LLM is disabled in the host application's settings
     * @throws ClientExceptionInterface if the underlying PSR-18 HTTP transport fails (network
     *                                  error, timeout, non-2xx response from the backend); covers its subtypes
     *                                  {@see \Psr\Http\Client\NetworkExceptionInterface} and
     *                                  {@see \Psr\Http\Client\RequestExceptionInterface}
     * @throws \JsonException           if the model's response cannot be decoded as valid JSON
     */
    public function parse(string $prompt): array;
}
