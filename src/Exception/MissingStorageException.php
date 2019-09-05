<?php

declare(strict_types=1);

namespace DocusignBundle\Exception;

class MissingStorageException extends \LogicException
{
    protected $message = 'You must define a docusign.storage.';
}
