<?php

declare(strict_types=1);

namespace DocusignBundle\Exception;

final class MissingStorageException extends \LogicException
{
    protected $message = 'You must define a docusign.storage.';
}
