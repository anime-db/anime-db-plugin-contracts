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

namespace AnimeDb\PluginContracts\OAuth;

/**
 * Thrown by {@see AbstractOAuthClient::handleCallback()} when the `state` the
 * callback carries does not match the one persisted by
 * {@see AbstractOAuthClient::buildAuthorizeUrl()}.
 *
 * The unauthenticated loopback callback endpoint is reachable by anything
 * running on the same machine, not only the vendor's redirect — this check
 * is what tells a genuine callback apart from a forged one (CSRF against the
 * OAuth flow) or a stale callback replayed after the session moved on.
 */
final class OAuthStateMismatchException extends \RuntimeException
{
}
