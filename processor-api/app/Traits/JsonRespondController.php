<?php

namespace App\Traits;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\JsonResponse;

trait JsonRespondController
{
    protected int $httpStatusCode = 200;
    protected ?int $errorCode = null;

    public function setHTTPStatusCode(int $statusCode): self
    {
        $this->httpStatusCode = $statusCode;
        return $this;
    }

    public function setErrorCode(int $errorCode): self
    {
        $this->errorCode = $errorCode;
        return $this;
    }

    public function respond(array $data, array $headers = []): JsonResponse
    {
        return response()->json($data, $this->httpStatusCode, $headers);
    }

    /**
     * Sends a successful creation response (201)
     */
    public function respondCreated(array $data): JsonResponse
    {
        return $this->setHTTPStatusCode(201)->respond([
            'success' => true,
            'data' => $data
        ]);
    }

    /**
     * Sends validation error response (422)
     */
    public function respondValidatorFailed(Validator $validator): JsonResponse
    {
        return $this->setHTTPStatusCode(422)
            ->respond([
                'success' => false,
                'error' => [
                    'message' => 'Validation failed',
                    'errors' => $validator->errors()
                ]
            ]);
    }

    /**
     * Sends service error response
     */
    public function respondServiceError(string $message, int $code = 500): JsonResponse
    {
        return $this->setHTTPStatusCode($code)
            ->respond([
                'success' => false,
                'error' => [
                    'message' => $message
                ]
            ]);
    }
}
