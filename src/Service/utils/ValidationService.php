<?php

namespace App\Service\utils;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use InvalidArgumentException;

class ValidationService
{
    /**
     * Lance une NotFoundHttpException si la donnée est null
     */
    public function throwIfNull(mixed $data, string $message): void
    {
        if ($data === null) {
            throw new NotFoundHttpException($message);
        }
    }

    /**
     * Lance une InvalidArgumentException si la donnée est vide
     */
    public function throwIfEmpty(mixed $data, string $message): void
    {
        if (empty($data)) {
            throw new InvalidArgumentException($message);
        }
    }
}