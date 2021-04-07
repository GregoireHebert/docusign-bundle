<?php

/*
 * This file is part of the DocusignBundle.
 *
 * (c) Grégoire Hébert <gregoire@les-tilleuls.coop>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace DocusignBundle\EnvelopeCreator;

use DocuSign\eSign\ApiException;
use Symfony\Component\Stopwatch\Stopwatch;

class TraceableEnvelopeBuilderCallable implements TraceableEnvelopeBuilderCallableInterface
{
    private $stopwatch;
    private $inner;

    public function __construct(EnvelopeBuilderCallableInterface $inner, Stopwatch $stopwatch)
    {
        $this->inner = $inner;
        $this->stopwatch = $stopwatch;
    }

    /**
     * @throws ApiException
     *
     * @return void|string when the function return a string, it will leave the chain
     */
    public function __invoke(array $context = [])
    {
        $name = sprintf('[DOCUSIGN] Action called "%s"', \get_class($this->inner));
        try {
            $this->stopwatch->start($name);

            return ($this->inner)($context);
        } finally {
            $this->stopwatch->stop($name);
        }
    }
}
