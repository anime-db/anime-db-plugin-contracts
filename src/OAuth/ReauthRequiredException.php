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
 * Thrown by a sync plugin from {@see \AnimeDb\PluginContracts\Sync\SyncInterface::push()}
 * or {@see \AnimeDb\PluginContracts\Sync\SyncInterface::pull()} (or from the plugin's
 * OAuth wrapper around them) when the OAuth session is dead and cannot be recovered
 * without the user going through interactive reauthorization again.
 *
 * A terminal signal, unlike transient failures ({@see \Psr\Http\Client\ClientExceptionInterface},
 * HTTP 5xx, rate limiting) that a retry can resolve: the host must not retry this one, and
 * must surface the need to reauthorize to the user instead of silently retrying into a
 * dead letter queue.
 *
 * Deliberately distinct from {@see OAuthTokenExchangeException}: that one is the raw,
 * low-level failure of a token exchange/refresh call. This one is the plugin's already
 * interpreted conclusion drawn from such failures (or from their absence, e.g. a
 * confirmed `invalid_grant` on a legitimate refresh, not a rotation race) that the token
 * is dead and a new user consent is required. When to throw this instead of
 * {@see OAuthTokenExchangeException} is a policy decision left to the plugin; the
 * contract only defines the type.
 */
final class ReauthRequiredException extends \RuntimeException
{
}
