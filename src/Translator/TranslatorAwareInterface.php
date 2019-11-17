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

namespace DocusignBundle\Translator;

use Symfony\Component\Translation\TranslatorInterface;

interface TranslatorAwareInterface
{
    public function setTranslator(TranslatorInterface $translator): void;
}
