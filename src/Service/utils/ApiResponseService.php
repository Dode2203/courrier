<?php

namespace App\Service\utils;

class ApiResponseService
{
    /**
     * Formate une réponse API standardisée
     */
    public function format(bool $success, string $message, $data = null, array $extras = []): array
    {
        $response = [
            'status' => $success ? 'success' : 'error',
            'message' => $message,
            'data' => $data
        ];

        if (!empty($extras)) {
            $response = array_merge($response, $extras);
        }

        return $response;
    }
}
