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

namespace DocusignBundle\E2e\TestBundle\Controller;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class CallbackController
{
    /**
     * @Route("/embedded/callback", name="embedded_callback", methods={"GET"})
     */
    public function __invoke(RouterInterface $router, Session $session): RedirectResponse
    {
        $session->getFlashBag()->add('success', 'The document has been successfully signed!');

        return new RedirectResponse($router->generate('embedded'));
    }
}
