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

namespace AnimeDb\PluginContracts\Network;

/**
 * Thrown by {@see DatagramChannelFactoryInterface::open()} (and by a channel
 * on a send/receive failure or use after close) when a datagram
 * channel cannot be provided — in particular when a proxy is configured in the
 * host, because UDP does not pass through an HTTP proxy or through SOCKS5
 * without `UDP ASSOCIATE`, and the host does not send datagrams around the
 * user's proxy.
 *
 * The message is a user-facing explanation of why UDP is unavailable; a plugin
 * may show it as is.
 */
final class DatagramUnavailableException extends \RuntimeException
{
}
