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

use Symfony\Component\Translation\IdentityTranslator;
use Symfony\Component\Translation\TranslatorInterface;

trait TranslatorAwareTrait
{
    private $translator;

    public function setTranslator(TranslatorInterface $translator): void
    {
        $this->translator = $translator;
    }

    public function getTranslator(): TranslatorInterface
    {
        if (null === $this->translator ?? null) {
            $this->translator = new IdentityTranslator();
            // Force the locale to be 'en' when no translator is provided rather than relying on the Intl default locale
            // This avoids depending on Intl or the stub implementation being available. It also ensures that Docusign
            // messages are pluralized properly even when the default locale gets changed because they are in English.
            $this->translator->setLocale('en');
        }

        return $this->translator;
    }
}
