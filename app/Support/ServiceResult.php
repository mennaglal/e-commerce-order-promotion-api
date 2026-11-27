<?php

namespace App\Support;

use App\Http\Responses\ApiResponse;
use Illuminate\Http\JsonResponse;

class ServiceResult
{
    private function __construct(
        public readonly bool $success,
        public readonly mixed $data = null,
        public readonly string $message = '',
        public readonly string $code = '',
        public readonly int $status = 422,
        public readonly array $extra = [],
    ) {
    }

    public static function ok(mixed $data = null): self
    {
        return new self(true, $data);
    }

    public static function fail(string $message, string $code, int $status = 422, array $extra = []): self
    {
        return new self(false, null, $message, $code, $status, $extra);
    }

    public function failed(): bool
    {
        return ! $this->success;
    }

    public function errorResponse(): JsonResponse
    {
        return ApiResponse::error(
            $this->message,
            $this->status,
            array_merge(['code' => $this->code], $this->extra)
        );
    }
}
