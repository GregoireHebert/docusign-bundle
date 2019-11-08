<?php

declare(strict_types=1);

namespace DocusignBundle\Grant;

use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class JwtGrant implements GrantInterface
{
    private $client;
    private $privateKey;
    private $integrationKey;
    private $userGuid;
    private $apiUri;
    private $accountApiUri;

    public function __construct(
        string $privateKey,
        string $integrationKey,
        string $userGuid,
        string $apiURI,
        string $accountApiUri,
        HttpClientInterface $client = null
    ) {
        $this->client = $client ?: HttpClient::create();
        $this->privateKey = $privateKey;
        $this->integrationKey = $integrationKey;
        $this->userGuid = $userGuid;
        $this->apiUri = $apiURI;
        $this->accountApiUri = $accountApiUri;
    }

    public function __invoke(): string
    {
        $time = time();
        $token = (new Builder())->issuedBy($this->integrationKey) // iss
            ->relatedTo($this->userGuid) // sub
            ->issuedAt($time) // iat
            ->expiresAt($time + 3600) // exp
            ->permittedFor('account-d.docusign.com') // aud
            ->withClaim('scope', 'signature impersonation') // scope
            ->getToken(new Sha256(), new Key("file://$this->privateKey"));

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