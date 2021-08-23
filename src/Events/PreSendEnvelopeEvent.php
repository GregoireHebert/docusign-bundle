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

namespace DocusignBundle\Events;

use DocusignBundle\EnvelopeBuilderInterface;
use Symfony\Contracts\EventDispatcher\Event;

class PreSendEnvelopeEvent extends Event
{
    private $envelopeBuilder;

    public function __construct(EnvelopeBuilderInterface $envelopeBuilder)
    {
        $this->envelopeBuilder = $envelopeBuilder;
    }

    public function setEnvelopeBuilder(EnvelopeBuilderInterface $envelopeBuilder): void
    {
        $this->envelopeBuilder = $envelopeBuilder;
    }

    public function getEnvelopeBuilder(): EnvelopeBuilderInterface
    {
        return $this->envelopeBuilder;
    }
}
