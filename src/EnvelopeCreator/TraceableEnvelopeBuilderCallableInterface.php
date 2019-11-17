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

namespace DocusignBundle\EnvelopeCreator;

use DocuSign\eSign\ApiException;

interface TraceableEnvelopeBuilderCallableInterface extends EnvelopeBuilderCallableInterface
{
    /**
     * @throws ApiException
     *
     * @return void|string when the function return a string, it will leave the chain
     */
    public function __invoke(array $context = []);
}
