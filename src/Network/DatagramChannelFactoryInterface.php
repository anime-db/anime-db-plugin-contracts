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
 * Core-provided factory of datagram (UDP) channels to a remote host.
 *
 * This is an optional capability: an additive extension that does not change
 * any existing signature. A plugin that uses it requires `^0.26.2`.
 *
 * A plugin obtains this service via constructor injection, type-hinting this
 * interface; the host implements it. This is the only way for a plugin to
 * send a UDP datagram: raw sockets in plugin code stay forbidden (see
 * `NoDangerousPrimitivesRule`) and are not relaxed by this interface.
 *
 * It is a network capability: a plugin with manifest `type: local` must not
 * declare it (checked by `NoNetworkAccessInLocalPluginsRule`).
 *
 * A proxy configured in the host does not carry UDP: an HTTP proxy cannot
 * relay datagrams, and a SOCKS5 proxy can only do so with `UDP ASSOCIATE`.
 * The host must not send a datagram around the user's proxy, so while a proxy
 * is configured {@see self::open()} throws {@see DatagramUnavailableException}.
 */
interface DatagramChannelFactoryInterface
{
    /**
     * Opens a channel to `$host:$port`.
     *
     * The channel lives for exactly one `open()` … `close()` and holds ONE
     * local socket (one local address and port) for that whole time; see
     * {@see DatagramChannelInterface}.
     *
     * @throws DatagramUnavailableException when a datagram channel cannot be
     *                                      provided, in particular when a proxy is configured in the host; the
     *                                      message explains to the user why UDP is unavailable
     */
    public function open(string $host, int $port): DatagramChannelInterface;
}
