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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class EmbeddedController
{
    private $kernelProjectDir;

    public function __construct(string $kernelProjectDir)
    {
        $this->kernelProjectDir = $kernelProjectDir;
    }

    /**
     * @Route("/embedded", name="embedded", methods={"GET"})
     */
    public function __invoke(Environment $twig): Response
    {
        return new Response($twig->render('embedded.html.twig', [
            'documents' => $this->getDocuments("$this->kernelProjectDir/var/storage"),
        ]));
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
