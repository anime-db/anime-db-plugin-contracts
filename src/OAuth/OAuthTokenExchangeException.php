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

namespace AnimeDb\PluginContracts\OAuth;

/**
 * Thrown by {@see AbstractOAuthClient} when the vendor's token endpoint
 * responds to a code exchange or refresh request with a non-2xx status, or
 * with a body that is not the JSON token response the OAuth 2.0 token
 * endpoint contract requires.
 *
 * Deliberately distinct from {@see \Psr\Http\Client\ClientExceptionInterface}:
 * that one means the request never got a response (network/timeout), this
 * one means it did, and the vendor rejected it or answered with something
 * the class cannot use.
 */
final class OAuthTokenExchangeException extends \RuntimeException
{
}
