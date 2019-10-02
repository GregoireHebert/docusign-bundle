<?php

declare(strict_types=1);

namespace DocusignBundle\Exception;

use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class MissingMandatoryParameterHttpException extends BadRequestHttpException
{
}
