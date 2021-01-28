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

use DocuSign\eSign\Model;
use DocusignBundle\EnvelopeBuilder;
use DocusignBundle\EnvelopeBuilderInterface;
use DocusignBundle\Utils\CallbackRouteGenerator;
use Symfony\Component\Routing\RouterInterface;

/*final */ class GetViewUrl implements EnvelopeBuilderCallableInterface
{
    private $router;
    private $envelopeBuilder;

    public function __construct(EnvelopeBuilderInterface $envelopeBuilder, RouterInterface $router)
    {
        $this->envelopeBuilder = $envelopeBuilder;
        $this->router = $router;
    }

    /**
     * @return string|void
     */
    public function __invoke(array $context = [])
    {
        if ($context['signature_name'] !== $this->envelopeBuilder->getName()) {
            return;
        }

        if (EnvelopeBuilder::MODE_REMOTE === $this->envelopeBuilder->getMode()) {
            return CallbackRouteGenerator::getCallbackRoute($this->router, $this->envelopeBuilder);
        }

        $recipientViewRequest = new Model\RecipientViewRequest([
            'authentication_method' => EnvelopeBuilder::EMBEDDED_AUTHENTICATION_METHOD,
            'client_user_id' => $this->envelopeBuilder->getAccountId(),
            'recipient_id' => '1',
            'return_url' => CallbackRouteGenerator::getCallbackRoute($this->router, $this->envelopeBuilder),
            'user_name' => $this->envelopeBuilder->getSignerName(),
            'email' => $this->envelopeBuilder->getSignerEmail(),
        ]);

        return $this->envelopeBuilder->getViewUrl($recipientViewRequest);
    }
}
