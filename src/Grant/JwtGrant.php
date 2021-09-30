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

namespace DocusignBundle\Grant;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Lcobucci\JWT\Token\Builder as TokenBuilder;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class JwtGrant implements GrantInterface
{
    public const DEMO_ACCOUNT_API_URI = 'https://account-d.docusign.com/oauth/token';
    public const ACCOUNT_API_URI = 'https://account.docusign.com/oauth/token';

    private $client;
    private $privateKey;
    private $integrationKey;
    private $userGuid;
    private $accountApiUri;
    private $ttl;

    public function __construct(
        string $privateKey,
        string $integrationKey,
        string $userGuid,
        bool $demo,
        int $ttl,
        HttpClientInterface $client = null
    ) {
        $this->client = $client ?: HttpClient::create();
        $this->privateKey = $privateKey;
        $this->integrationKey = $integrationKey;
        $this->userGuid = $userGuid;
        $this->accountApiUri = $demo ? self::DEMO_ACCOUNT_API_URI : self::ACCOUNT_API_URI;
        $this->ttl = $ttl;
    }

    public function __invoke(): string
    {
        try {
            $response = $this->client->request('POST', $this->accountApiUri, [
                'body' => sprintf('grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer&assertion=%s', $this->createToken()),
            ]);

            return $response->toArray()['access_token'] ?? '';
        } catch (ExceptionInterface $exception) {
            return '';
        }
    }

    /**
     * Creates a valid JWT for DocuSign.
     *
     * @see https://developers.docusign.com/platform/auth/jwt/jwt-get-token/
     */
    private function createToken(): string
    {
        // Ensure compatibility with lcobucci/jwt v3 and v4
        if (class_exists(TokenBuilder::class)) {
            // lcobucci/jwt v4
            $time = new \DateTimeImmutable();
            // Need for force seconds to 0, otherwise DocuSign will consider this token as invalid
            $time = $time->setTime((int) $time->format('H'), (int) $time->format('i'), 0, 0);
            $config = Configuration::forSymmetricSigner(new Sha256(), InMemory::file("file://$this->privateKey"));

            return $config
                ->builder()
                ->issuedBy($this->integrationKey) // iss
                ->relatedTo($this->userGuid) // sub
                ->issuedAt($time) // iat
                ->expiresAt($time->modify("$this->ttl sec")) // exp
                ->permittedFor(parse_url($this->accountApiUri, \PHP_URL_HOST)) // aud
                ->withClaim('scope', 'signature impersonation') // scope
                ->getToken($config->signer(), $config->signingKey())
                ->toString();
        }

        $time = time();

        return (string) (new Builder())
            ->issuedBy($this->integrationKey) // iss
            ->relatedTo($this->userGuid) // sub
            ->issuedAt($time) // iat
            ->expiresAt($time + $this->ttl) // exp
            ->permittedFor(parse_url($this->accountApiUri, \PHP_URL_HOST)) // aud
            ->withClaim('scope', 'signature impersonation') // scope
            ->getToken(new Sha256(), new Key("file://$this->privateKey"));
    }
}
