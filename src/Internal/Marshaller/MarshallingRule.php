<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Temporal\Internal\Marshaller;

use Temporal\Internal\Marshaller\Type\TypeInterface;

/**
 * @internal
 */
class MarshallingRule
{
    /**
     * @param string|null $name
     * @param class-string<TypeInterface>|null $type
     * @param self|class-string<TypeInterface>|string|null $of
     */
    public function __construct(
        public ?string $name = null,
        public ?string $type = null,
        public self|string|null $of = null,
    ) {
    }
}
