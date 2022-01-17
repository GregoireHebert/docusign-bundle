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

namespace DocusignBundle\E2e;

use DocusignBundle\DocusignBundle;
use DocusignBundle\E2e\AuthorizationHandler\SessionAuthorizationHandler;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;
use League\FlysystemBundle\FlysystemBundle;
use Symfony\Bundle\FrameworkBundle\FrameworkBundle;
use Symfony\Bundle\FrameworkBundle\Kernel\MicroKernelTrait;
use Symfony\Bundle\MonologBundle\MonologBundle;
use Symfony\Bundle\SecurityBundle\SecurityBundle;
use Symfony\Bundle\TwigBundle\TwigBundle;
use Symfony\Bundle\WebProfilerBundle\WebProfilerBundle;
use Symfony\Component\Config\Loader\LoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Kernel as BaseKernel;
use Symfony\Component\HttpKernel\Kernel as HttpKernel;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;
use Symfony\Component\Routing\RouteCollectionBuilder;
use Symfony\Component\Security\Core\User\InMemoryUser;
use Symfony\Component\Security\Core\User\User;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class Kernel extends BaseKernel
{
    use MicroKernelTrait;

    public function registerBundles(): iterable
    {
        $bundles = [
            new FrameworkBundle(),
            new SecurityBundle(),
            new TwigBundle(),
            new DocusignBundle(),
        ];

        if (class_exists(FlysystemBundle::class)) {
            $bundles[] = new FlysystemBundle();
        }

        if ($this->isDebug()) {
            $bundles[] = new MonologBundle();
            $bundles[] = new WebProfilerBundle();
        }

        return $bundles;
    }

    public function getProjectDir(): string
    {
        return __DIR__.'/..';
    }

    public function getCacheDir(): string
    {
        return $this->getProjectDir().'/var/cache/'.$this->environment;
    }

    public function getLogDir(): string
    {
        return $this->getProjectDir().'/var/log';
    }

    /**
     * @param RouteCollectionBuilder|RoutingConfigurator $routes
     */
    protected function configureRoutes($routes): void
    {
        $routes->import(__DIR__.'/../config/routes/annotations.yaml');
        $routes->import(__DIR__.'/../config/routes/docusign.yaml');

        if ($this->isDebug()) {
            $routes->import(__DIR__.'/../config/routes/debug.yaml');
        }
    }

    protected function configureContainer(ContainerBuilder $c, LoaderInterface $loader): void
    {
        $loader->load(__DIR__.'/../config/services.yaml');
        $loader->load(__DIR__.'/../config/override.yaml');

        $c->loadFromExtension('framework', [
            'secret' => 'DocusignBundle',
            'test' => true,
            'router' => [
                'utf8' => true,
            ],
        ]);

        $c->loadFromExtension('twig', [
            'paths' => ['%kernel.project_dir%/templates'],
        ]);

        $c->loadFromExtension('security', [
            'providers' => [
                'in_memory' => [
                    'memory' => [
                        'users' => [
                            'admin' => [
                                'password' => '4dm1n',
                                'roles' => 'ROLE_USER',
                            ],
                        ],
                    ],
                ],
            ],
            'firewalls' => [
                'dev' => [
                    'pattern' => '^/(_(profiler|wdt|error)|css|images|js)/',
                    'security' => false,
                ],
                'test' => [
                    'pattern' => '^/',
                    'form_login' => [
                        'login_path' => 'login',
                        'check_path' => 'login',
                    ],
                    'logout' => [
                        'path' => 'logout',
                        'target' => 'homepage',
                    ],
                ],
            ],
        ]);

        $c->loadFromExtension('docusign', [
            'default' => [
                'demo' => true,
                'mode' => 'embedded',
                'auth_jwt' => [
                    'private_key' => '%kernel.project_dir%/var/jwt/docusign.pem',
                    'integration_key' => $_SERVER['DOCUSIGN_INTEGRATION_KEY'],
                    'user_guid' => $_SERVER['DOCUSIGN_USER_GUID'],
                ],
                'sign_path' => '/embedded/sign',
                'callback' => 'embedded_callback',
                'account_id' => (int) $_SERVER['DOCUSIGN_ACCOUNT_ID'],
                'default_signer_name' => $_SERVER['DOCUSIGN_DEFAULT_SIGNER_NAME'],
                'default_signer_email' => $_SERVER['DOCUSIGN_DEFAULT_SIGNER_EMAIL'],
                'signatures' => [
                    [
                        'page' => 1,
                        'x_position' => 200,
                        'y_position' => 400,
                    ],
                ],
                'storage' => 'docusign.storage',
            ],
            'embedded_auth_code' => [
                'demo' => true,
                'mode' => 'embedded',
                'auth_code' => [
                    'integration_key' => $_SERVER['DOCUSIGN_INTEGRATION_KEY'],
                    'secret' => $_SERVER['DOCUSIGN_SECRET'],
                    'strategy' => SessionAuthorizationHandler::class,
                ],
                'sign_path' => '/embedded_auth_code/sign',
                'callback' => 'embedded_auth_code_callback',
                'account_id' => (int) $_SERVER['DOCUSIGN_ACCOUNT_ID'],
                'default_signer_name' => $_SERVER['DOCUSIGN_DEFAULT_SIGNER_NAME'],
                'default_signer_email' => $_SERVER['DOCUSIGN_DEFAULT_SIGNER_EMAIL'],
                'signatures' => [
                    [
                        'page' => 1,
                        'x_position' => 200,
                        'y_position' => 400,
                    ],
                ],
                'storage' => 'docusign.storage',
            ],
            'remote' => [
                'demo' => true,
                'mode' => 'remote',
                'auth_jwt' => [
                    'private_key' => '%kernel.project_dir%/var/jwt/docusign.pem',
                    'integration_key' => $_SERVER['DOCUSIGN_INTEGRATION_KEY'],
                    'user_guid' => $_SERVER['DOCUSIGN_USER_GUID'],
                ],
                'sign_path' => '/remote/sign',
                'callback' => 'remote_callback',
                'account_id' => (int) $_SERVER['DOCUSIGN_ACCOUNT_ID'],
                'default_signer_name' => $_SERVER['DOCUSIGN_DEFAULT_SIGNER_NAME'],
                'default_signer_email' => $_SERVER['DOCUSIGN_DEFAULT_SIGNER_EMAIL'],
                'signatures' => [
                    [
                        'page' => 1,
                        'x_position' => 200,
                        'y_position' => 400,
                    ],
                ],
                'storage' => 'docusign.storage',
            ],
            'remote_auth_code' => [
                'demo' => true,
                'mode' => 'remote',
                'auth_code' => [
                    'integration_key' => $_SERVER['DOCUSIGN_INTEGRATION_KEY'],
                    'secret' => $_SERVER['DOCUSIGN_SECRET'],
                    'strategy' => SessionAuthorizationHandler::class,
                ],
                'sign_path' => '/remote_auth_code/sign',
                'callback' => 'remote_auth_code_callback',
                'account_id' => (int) $_SERVER['DOCUSIGN_ACCOUNT_ID'],
                'default_signer_name' => $_SERVER['DOCUSIGN_DEFAULT_SIGNER_NAME'],
                'default_signer_email' => $_SERVER['DOCUSIGN_DEFAULT_SIGNER_EMAIL'],
                'signatures' => [
                    [
                        'page' => 1,
                        'x_position' => 200,
                        'y_position' => 400,
                    ],
                ],
                'storage' => 'docusign.storage',
            ],
            'terms' => [
                'demo' => true,
                'mode' => 'clickwrap',
                'auth_clickwrap' => [
                    'api_account_id' => $_SERVER['DOCUSIGN_API_ACCOUNT_ID'],
                    'clickwrap_id' => $_SERVER['DOCUSIGN_CLICKWRAP_ID'],
                    'user_guid' => $_SERVER['DOCUSIGN_USER_GUID'],
                ],
            ],
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
                'toolbar' => true,
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

        if (6 === HttpKernel::MAJOR_VERSION) {
            $c->loadFromExtension('framework', [
                'session' => [
                    'enabled' => true,
                    'storage_factory_id' => 'session.storage.factory.mock_file',
                ],
            ]);

            $c->loadFromExtension('security', [
                'enable_authenticator_manager' => true,
                'password_hashers' => [
                    InMemoryUser::class => [
                        'algorithm' => 'plaintext',
                    ],
                ],
                'access_control' => [
                    ['path' => '^/docusign/webhook/', 'roles' => 'PUBLIC_ACCESS'],
                    ['path' => '^/docusign/authorization_code/', 'roles' => 'PUBLIC_ACCESS'],
                    ['path' => '^/login$', 'roles' => 'PUBLIC_ACCESS'],
                    ['path' => '^/$', 'roles' => 'PUBLIC_ACCESS'],
                    ['path' => '^/', 'roles' => 'IS_AUTHENTICATED_FULLY'],
                ],
            ]);

            return;
        }

        $c->loadFromExtension('framework', [
            'session' => [
                'enabled' => true,
                'storage_id' => 'session.storage.mock_file',
            ],
        ]);

        $c->loadFromExtension('security', [
            'encoders' => [
                User::class => 'plaintext',
            ],
            'firewalls' => [
                'test' => [
                    'anonymous' => true,
                ],
            ],
            'access_control' => [
                ['path' => '^/docusign/webhook/', 'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY'],
                ['path' => '^/docusign/authorization_code/', 'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY'],
                ['path' => '^/login$', 'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY'],
                ['path' => '^/$', 'roles' => 'IS_AUTHENTICATED_ANONYMOUSLY'],
                ['path' => '^/', 'roles' => 'IS_AUTHENTICATED_FULLY'],
            ],
        ]);
    }
}
