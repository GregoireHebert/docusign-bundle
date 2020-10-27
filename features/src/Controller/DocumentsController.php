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

namespace DocusignBundle\E2e\Controller;

use Symfony\Component\Finder\Finder;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class DocumentsController
{
    private $kernelProjectDir;

    public function __construct(string $kernelProjectDir)
    {
        $this->kernelProjectDir = $kernelProjectDir;
    }

    /**
     * @Route("/embedded_auth_code", name="embedded_auth_code", methods={"GET"}, defaults={"mode"="embedded", "auth"="AuthorizationCode", "route_name"="docusign_sign_embedded_auth_code"})
     * @Route("/remote_auth_code", name="remote_auth_code", methods={"GET"}, defaults={"mode"="remote", "auth"="AuthorizationCode", "route_name"="docusign_sign_remote_auth_code"})
     * @Route("/embedded", name="embedded", methods={"GET"}, defaults={"mode"="embedded", "auth"="JWT", "route_name"="docusign_sign_default"})
     * @Route("/remote", name="remote", methods={"GET"}, defaults={"mode"="remote", "auth"="JWT", "route_name"="docusign_sign_remote"})
     */
    public function __invoke(Request $request, Environment $twig): Response
    {
        return new Response($twig->render('list.html.twig', [
            'documents' => $this->getDocuments("$this->kernelProjectDir/var/storage"),
            'route_name' => $request->attributes->get('route_name'),
            'mode' => $request->attributes->get('mode'),
            'auth' => $request->attributes->get('auth'),
        ]));
    }

    /**
     * @Route("/embedded/callback", name="embedded_callback", methods={"GET"}, defaults={"redirect"="embedded"})
     * @Route("/embedded_auth_code/callback", name="embedded_auth_code_callback", methods={"GET"}, defaults={"redirect"="embedded_auth_code"})
     */
    public function embeddedCallbackAction(Request $request, RouterInterface $router, Session $session): RedirectResponse
    {
        $session->getFlashBag()->add('success', 'The document has been successfully signed!');

        return new RedirectResponse($router->generate($request->attributes->get('redirect')));
    }

    /**
     * @Route("/remote/callback", name="remote_callback", methods={"GET"}, defaults={"redirect"="remote"})
     * @Route("/remote_auth_code/callback", name="remote_auth_code_callback", methods={"GET"}, defaults={"redirect"="remote_auth_code"})
     */
    public function remoteCallbackAction(Request $request, RouterInterface $router, Session $session): RedirectResponse
    {
        $session->getFlashBag()->add('success', 'The document has been successfully sent to the signer!');

        return new RedirectResponse($router->generate($request->attributes->get('redirect')));
    }

    private function getDocuments($path): array
    {
        $documents = [];
        /** @var \SplFileInfo[] $files */
        $files = Finder::create()->files()->in($path);
        foreach ($files as $file) {
            $documents[$file->getFilename()] = substr($file->getRealPath(), \strlen($path));
        }

        return $documents;
    }
}
