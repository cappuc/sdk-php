<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Spiral\Goridge;

interface ResponderInterface
{
    /**
     * @param string $body
     * @param int $flags
     */
    public function send(string $body, int $flags): void;
}
