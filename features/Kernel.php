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

use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\FlysystemBundle\FlysystemBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Security\Core\User\User;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles()
    {
        $bundles = [
            new Symfony\Bundle\FrameworkBundle\FrameworkBundle(),
            new Symfony\Bundle\SecurityBundle\SecurityBundle(),
            new Symfony\Bundle\TwigBundle\TwigBundle(),
            new DocusignBundle\DocusignBundle(),
            new DocusignBundle\E2e\TestBundle\TestBundle(),
        ];

        if (class_exists(FlysystemBundle::class)) {
            $bundles[] = new FlysystemBundle();
        }

        if ($this->isDebug()) {
            $bundles[] = new Symfony\Bundle\MonologBundle\MonologBundle();
            $bundles[] = new Symfony\Bundle\WebProfilerBundle\WebProfilerBundle();
            $bundles[] = new Symfony\Bundle\WebServerBundle\WebServerBundle();
        }

        return $bundles;
    }

    public function getProjectDir()
    {
        return __DIR__;
    }

    public function getCacheDir()
    {
        return $this->rootDir.'/var/cache/'.$this->environment;
    }

    public function getLogDir()
    {
        return $this->rootDir.'/var/log';
    }

    protected function configureRoutes(RouteCollectionBuilder $routes): void
    {
        if ($this->isDebug()) {
            $routes->import('@WebProfilerBundle/Resources/config/routing/wdt.xml', '/_wdt');
            $routes->import('@WebProfilerBundle/Resources/config/routing/profiler.xml', '/_profiler');
            $routes->import('@TwigBundle/Resources/config/routing/errors.xml', '/_error');
        }

        $routes->import('.', null, 'docusign');
        $routes->import('@TestBundle/Controller', null, 'annotation');
        $routes->addRoute(new Route('/logout'), 'logout');
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $c->loadFromExtension('framework', [
            'secret' => 'DocusignBundle',
            'test' => true,
            'session' => [
                'storage_id' => 'session.storage.mock_file',
            ],
        ]);

        $c->loadFromExtension('twig', [
            'paths' => ['%kernel.project_dir%/TestBundle/Resources/views'],
        ]);

        $c->loadFromExtension('security', [
            'encoders' => [
                User::class => 'plaintext',
            ],
            'providers' => [
                'in_memory' => [
                    'memory' => [
                        'users' => [
                            'admin' => [
                                'password' => '4dm1n',
                                'roles' => 'ROLE_EMBEDDED',
                            ],
                            'john_doe' => [
                                'password' => 'j0hnd0E',
                                'roles' => 'ROLE_USER',
                            ],
                        ],
                    ],
                ],
            ],
            'role_hierarchy' => [
                'ROLE_EMBEDDED' => 'ROLE_USER',
            ],
            'firewalls' => [
                'dev' => [
                    'pattern' => '^/(_(profiler|wdt|error)|css|images|js)/',
                    'security' => false,
                ],
                'embedded' => [
                    'pattern' => '^/',
                    'form_login' => [
                        'login_path' => 'login',
                        'check_path' => 'login',
                    ],
                    'logout' => [
                        'path' => 'logout',
                        'target' => 'homepage',
                    ],
                    'anonymous' => null,
                ],
            ],
            'access_control' => [
                ['path' => '/embedded', 'roles' => 'ROLE_EMBEDDED'],
                ['path' => '/', 'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY'],
            ],
        ]);

        $c->loadFromExtension('docusign', [
            'demo' => true,
            'mode' => 'embedded',
            'auth_jwt' => [
                'private_key' => '%kernel.project_dir%/var/jwt/docusign.pem',
                'integration_key' => $_SERVER['DOCUSIGN_INTEGRATION_KEY'],
                'user_guid' => $_SERVER['DOCUSIGN_USER_GUID'],
                'grant_type' => 'authorization_code',
            ],
            'sign_path' => '/docusign/sign',
            'callback' => 'embedded_callback',
            'account_id' => (int) $_SERVER['DOCUSIGN_ACCOUNT_ID'],
            'default_signer_name' => 'Grégoire Hébert',
            'default_signer_email' => 'gregoire@les-tilleuls.coop',
            'signatures' => [
                [
                    'page' => 1,
                    'x_position' => 200,
                    'y_position' => 400,
                ],
            ],
            'storage' => 'docusign.storage',
        ]);

        if (class_exists(FlysystemBundle::class)) {
            $c->loadFromExtension('flysystem', [
                'storages' => [
                    'docusign.storage' => [
                        'adapter' => 'local',
                        'options' => [
                            'directory' => '%kernel.project_dir%/var/storage',
                        ],
                    ],
                ],
            ]);
        } else {
            $c->register('flysystem.adapter.local', Local::class)
                ->setAutowired(true)
                ->setPublic(false)
                ->setArguments([__DIR__.'/var/storage']);

            $c->register('docusign.storage', Filesystem::class)
                ->setAutowired(true)
                ->setPublic(false)
                ->setArgument('$adapter', new Reference('flysystem.adapter.local'));
        }

        if ($this->isDebug()) {
            $c->loadFromExtension('framework', [
                'profiler' => ['only_exceptions' => false],
            ]);

            $c->loadFromExtension('web_profiler', [
                'toolbar' => '%kernel.debug%',
            ]);

            $c->loadFromExtension('monolog', [
                'handlers' => [
                    'main' => [
                        'type' => 'stream',
                        'path' => '%kernel.logs_dir%/%kernel.environment%.log',
                        'level' => 'error',
                    ],
                ],
            ]);
        }
    }
}
