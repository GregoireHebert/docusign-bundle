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

namespace DocusignBundle\Twig\Extension;

use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class ClickwrapExtension extends AbstractExtension
{
    private $config = [];

    public function addConfig(string $docusignName, bool $demo, array $config): void
    {
        if (true === $demo) {
            $config['environment'] = 'https://demo.docusign.net';
        }

        $this->config[$docusignName] = $config;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('renderClickwrap', [$this, 'renderClickwrap'], ['needs_environment' => true, 'is_safe' => ['html']]),
        ];
    }

    public function renderClickwrap(Environment $env, string $docusignName): string
    {
        if (empty($this->config[$docusignName])) {
            throw new \InvalidArgumentException("DocuSign configuration \"$docusignName\" does not exist.");
        }

        $config = $this->config[$docusignName];

        return $env->render('@Docusign/clickwrap.html.twig', [
            'environment' => $config['environment'],
            'accountId' => $config['accountId'],
            'clientUserId' => $config['clientUserId'],
            'clickwrapId' => $config['clickwrapId'],
        ]);
    }
}
