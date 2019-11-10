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

use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Twig\Environment;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class SecurityController
{
    /**
     * @Route("/login", name="login", methods={"GET", "POST"})
     */
    public function __invoke(AuthenticationUtils $authenticationUtils, Environment $twig): Response
    {
        $error = $authenticationUtils->getLastAuthenticationError();

        return new Response($twig->render('login.html.twig', [
            'last_username' => $authenticationUtils->getLastUsername(),
            'error' => $error,
        ]), $error ? Response::HTTP_BAD_REQUEST : Response::HTTP_OK);
    }
}
