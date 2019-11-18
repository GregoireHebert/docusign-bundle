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

use DocusignBundle\Exception\ConfigurationException;
use Lcobucci\JWT\Builder;
use Lcobucci\JWT\Signer\Key;
use Lcobucci\JWT\Signer\Rsa\Sha256;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Webmozart\Assert\Assert;

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
        string $accountApiUri,
        int $ttl,
        HttpClientInterface $client = null
    ) {
        $this->client = $client ?: HttpClient::create();
        $this->privateKey = $privateKey;
        $this->integrationKey = $integrationKey;
        $this->userGuid = $userGuid;
        $this->accountApiUri = $accountApiUri;
        $this->ttl = $ttl;

        $this->validateConfiguration();
    }

    public function __invoke(): string
    {
        $time = time();
        $token = (new Builder())->issuedBy($this->integrationKey) // iss
            ->relatedTo($this->userGuid) // sub
            ->issuedAt($time) // iat
            ->expiresAt($time + $this->ttl) // exp
            ->permittedFor(parse_url($this->accountApiUri, PHP_URL_HOST)) // aud
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

    private function validateConfiguration(): void
    {
        try {
            Assert::uuid($this->integrationKey);
        } catch (\Exception $e) {
            throw new ConfigurationException(sprintf(
                'Your integration key "%s" is invalid. To generate your integration key, follow this documentation: https://developers.docusign.com/esign-soap-api/reference/Introduction-Changes/Integration-Keys',
                $this->integrationKey
            ));
        }

        try {
            Assert::uuid($this->userGuid);
        } catch (\Exception $e) {
            throw new ConfigurationException(sprintf(
                'Your user guid "%s" is invalid. Obtain your user UID (also called API username) from DocuSign Admin > Users > User > Actions > Edit',
                $this->userGuid
            ));
        }
    }
}
