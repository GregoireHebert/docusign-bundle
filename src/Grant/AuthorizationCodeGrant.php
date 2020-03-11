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

namespace DocusignBundle\Grant;

use DocusignBundle\AuthorizationCodeHandler\AuthorizationCodeHandlerInterface;
use DocusignBundle\EnvelopeBuilderInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class AuthorizationCodeGrant implements GrantInterface
{
    private $authorizationCodeHandler;
    private $envelopeBuilder;

    public function __construct(AuthorizationCodeHandlerInterface $authorizationCodeHandler, EnvelopeBuilderInterface $envelopeBuilder)
    {
        $this->authorizationCodeHandler = $authorizationCodeHandler;
        $this->envelopeBuilder = $envelopeBuilder;
    }

    public function __invoke(): string
    {
        return $this->authorizationCodeHandler->read(['signature_name' => $this->envelopeBuilder->getName()]);
    }
}
