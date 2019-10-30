<?php

declare(strict_types=1);

namespace DocusignBundle\Events;

use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

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
