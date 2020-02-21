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

namespace DocusignBundle\Events;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\EventDispatcher\Event;

abstract class WebhookEvent extends Event
{
    protected $data;
    protected $request;

    public function __construct(\SimpleXMLElement $data, Request $request)
    {
        $this->data = $data;
        $this->request = $request;
    }

    public function getData(): \SimpleXMLElement
    {
        return $this->data;
    }

    public function getRequest(): Request
    {
        return $this->request;
    }
}
