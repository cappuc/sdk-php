<?php

/**
 * This file is part of Temporal package.
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Temporal\Client\Internal\Transport;

use React\Promise\PromiseInterface;
use Temporal\Client\Worker\Command\RequestInterface;

class CapturedClient implements CapturedClientInterface
{
    /**
     * @var array<positive-int, RequestInterface>
     */
    private array $requests = [];

    /**
     * @var ClientInterface
     */
    protected ClientInterface $parent;

    /**
     * @param ClientInterface $parent
     */
    public function __construct(ClientInterface $parent)
    {
        $this->parent = $parent;
    }

    /**
     * @param RequestInterface $request
     * @return PromiseInterface
     */
    public function request(RequestInterface $request): PromiseInterface
    {
        $this->requests[$request->getId()] = $request;

        return $this->parent->request($request)
            ->then($this->onFulfilled($request), $this->onRejected($request))
        ;
    }

    /**
     * @param RequestInterface $request
     * @return \Closure
     */
    private function onFulfilled(RequestInterface $request): \Closure
    {
        return function ($response) use ($request) {
            unset($this->requests[$request->getId()]);

            return $response;
        };
    }

    /**
     * @param RequestInterface $request
     * @return \Closure
     */
    private function onRejected(RequestInterface $request): \Closure
    {
        return function (\Throwable $error) use ($request) {
            unset($this->requests[$request->getId()]);

            throw $error;
        };
    }

    /**
     * {@inheritDoc}
     */
    public function getUnresolvedRequests(): array
    {
        return $this->requests;
    }

    /**
     * {@inheritDoc}
     */
    public function fetchUnresolvedRequests(): array
    {
        try {
            return $this->getUnresolvedRequests();
        } finally {
            $this->requests = [];
        }
    }

    /**
     * @return \Traversable
     */
    public function getIterator(): \Traversable
    {
        return new \ArrayIterator($this->requests);
    }

    /**
     * @return int
     */
    public function count(): int
    {
        return \count($this->requests);
    }
}
