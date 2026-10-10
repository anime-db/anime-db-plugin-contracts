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
 * A datagram (UDP) channel to one remote `host:port`, obtained from
 * {@see DatagramChannelFactoryInterface::open()}.
 *
 * The channel lives for exactly one `open()` … `close()` and holds ONE local
 * socket (one local port) for that whole time: every `send()` of the channel
 * leaves from the same local address and port, and `receive()` reads from that
 * same socket. An implementation must not open a new socket per `send()`.
 * Protocols with a session bind the session to the client's address and port,
 * so a packet sent from another port is not matched to the session and is
 * rejected.
 *
 * Keeping the port longer than one `open()` … `close()` (across calls, for the
 * lifetime of the process) is not required.
 */
interface DatagramChannelInterface
{
    /**
     * Sends one datagram with the given payload (at most 65507 bytes).
     *
     * @throws DatagramUnavailableException when the payload is too large, the
     *                                      send fails, or the channel is closed
     */
    public function send(string $payload): void;

    /**
     * Waits up to `$timeoutSeconds` for one datagram and returns its payload.
     *
     * Only datagrams from the `host:port` given to `open()` are returned
     * (the socket is connected to that peer); others are discarded. A datagram
     * is returned whole, up to 65507 bytes, never silently truncated. `0`
     * polls without blocking; a negative value is treated as `0`.
     *
     * Returns `null` on timeout.
     *
     * @throws DatagramUnavailableException when the receive fails (for example
     *                                      ICMP port unreachable) or the channel is closed
     */
    public function receive(float $timeoutSeconds): ?string;

    /**
     * Closes the channel and releases its local socket. The channel must not
     * be used afterwards.
     */
    public function close(): void;
}
