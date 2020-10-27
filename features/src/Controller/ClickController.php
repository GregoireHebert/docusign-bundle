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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ClickController
{
    private $kernelProjectDir;

    public function __construct(string $kernelProjectDir)
    {
        $this->kernelProjectDir = $kernelProjectDir;
    }

    /**
     * @Route("/clickwrap", name="clickwrap", methods={"GET"})
     */
    public function __invoke(Request $request, Environment $twig): Response
    {
        return new Response($twig->render('clickwrap.html.twig'));
    }
}
