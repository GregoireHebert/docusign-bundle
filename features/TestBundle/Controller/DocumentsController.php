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
     * @Route("/embedded", name="embedded", methods={"GET"}, defaults={"mode"="embedded", "route_name"="docusign_sign_default"})
     * @Route("/remote", name="remote", methods={"GET"}, defaults={"mode"="remote", "route_name"="docusign_sign_remote"})
     */
    public function __invoke(Request $request, Environment $twig): Response
    {
        return new Response($twig->render('list.html.twig', [
            'documents' => $this->getDocuments("$this->kernelProjectDir/var/storage"),
            'route_name' => $request->attributes->get('route_name'),
            'mode' => $request->attributes->get('mode'),
        ]));
    }

    /**
     * @Route("/embedded/callback", name="embedded_callback", methods={"GET"})
     */
    public function embeddedCallbackAction(RouterInterface $router, Session $session): RedirectResponse
    {
        $session->getFlashBag()->add('success', 'The document has been successfully signed!');

        return new RedirectResponse($router->generate('embedded'));
    }

    /**
     * @Route("/remote/callback", name="remote_callback", methods={"GET"})
     */
    public function remoteCallbackAction(RouterInterface $router, Session $session): RedirectResponse
    {
        $session->getFlashBag()->add('success', 'The document has been successfully sent to the signer!');

        return new RedirectResponse($router->generate('remote'));
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
