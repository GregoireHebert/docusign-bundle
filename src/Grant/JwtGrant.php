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

use Lcobucci\Jose\Parsing\Parser;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
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
        // Ensure compatibility with lcobucci/jwt v3 and v4
        $v4 = class_exists(TokenBuilder::class);
        $time = $v4 ? new \DateTimeImmutable() : time();
        /** @var Builder $builder */
        $builder = $v4 ? new TokenBuilder(new Parser()) : new Builder();
        $token = $builder->issuedBy($this->integrationKey) // iss
            ->relatedTo($this->userGuid) // sub
            ->issuedAt($time) // iat
            ->expiresAt($v4 ? $time->modify("$this->ttl sec") : $time + $this->ttl) // exp
            ->permittedFor(parse_url($this->accountApiUri, PHP_URL_HOST)) // aud
            ->withClaim('scope', 'signature impersonation') // scope
            ->getToken(new Sha256(), new Key("file://$this->privateKey"));
        $token = method_exists($token, 'toString') ? $token->toString() : (string) $token;

        try {
            $response = $this->client->request('POST', $this->accountApiUri, [
                'body' => "grant_type=urn:ietf:params:oauth:grant-type:jwt-bearer&assertion=$token",
            ]);

            return $response->toArray()['access_token'] ?? '';
        } catch (ExceptionInterface $exception) {
            return '';
        }
    }
}
