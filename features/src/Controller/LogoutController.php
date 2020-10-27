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

use Symfony\Component\Routing\Annotation\Route;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class LogoutController
{
    /**
     * @Route("/logout", name="logout", methods={"GET"})
     */
    public function __invoke(): void
    {
    }
}
