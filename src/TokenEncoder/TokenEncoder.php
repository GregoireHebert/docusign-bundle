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

namespace DocusignBundle\TokenEncoder;

/**
 * @author Vincent Chalamon <vincentchalamon@gmail.com>
 */
final class TokenEncoder implements TokenEncoderInterface
{
    private $integrationKey;
    private $userGuid;

    public function __construct(string $integrationKey, string $userGuid)
    {
        $this->integrationKey = $integrationKey;
        $this->userGuid = $userGuid;
    }

    public function encode(array $parameters): string
    {
        return password_hash(http_build_query($parameters + [
            'integration_key' => $this->integrationKey,
            'user_guid' => $this->userGuid,
        ]), PASSWORD_DEFAULT);
    }

    public function isTokenValid(array $parameters, ?string $token): bool
    {
        unset($parameters['_token']);

        return !empty($token) && password_verify(http_build_query($parameters + [
            'integration_key' => $this->integrationKey,
            'user_guid' => $this->userGuid,
        ]), $token);
    }
}
