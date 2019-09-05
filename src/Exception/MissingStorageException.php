<?php

declare(strict_types=1);

namespace DocusignBundle\Exception;

class MissingStorageException extends \LogicException
{
    protected $message = 'You must define a FlySystem Adapter docusign.storage';
}
