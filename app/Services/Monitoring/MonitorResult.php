<?php

namespace App\Services\Monitoring;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;

class MonitorResult implements Arrayable
{
    public function __construct(
        public string $status,
        public ?int $statusCode = null,
        public ?int $responseTimeMs = null,
        public ?string $error = null,
        public array $meta = [],
        public ?array $payload = null,
        public ?Carbon $sslValidFrom = null,
        public ?Carbon $sslExpiresAt = null,
        public ?string $sslIssuer = null,
        public ?Carbon $checkedAt = null,
        public bool $recordHistory = true,
    ) {
        $this->status = in_array($status, ['up', 'down', 'unknown'], true) ? $status : 'unknown';
    }

    public static function success(array $meta = [], ?int $statusCode = null, ?int $responseTimeMs = null): self
    {
        return new self('up', $statusCode, $responseTimeMs, null, $meta);
    }

    public static function failure(string $message, array $meta = [], ?int $statusCode = null, ?int $responseTimeMs = null): self
    {
        return new self('down', $statusCode, $responseTimeMs, $message, $meta);
    }

    public static function skip(string $reason = 'skipped'): self
    {
        return new self('unknown', null, null, $reason, recordHistory: false);
    }

    public function withSsl(?Carbon $validFrom, ?Carbon $expiresAt, ?string $issuer): self
    {
        $clone = clone $this;
        $clone->sslValidFrom = $validFrom;
        $clone->sslExpiresAt = $expiresAt;
        $clone->sslIssuer = $issuer;
        return $clone;
    }

    public function withPayload(?array $payload): self
    {
        $clone = clone $this;
        $clone->payload = $payload;
        return $clone;
    }

    public function withCheckedAt(?Carbon $checkedAt): self
    {
        $clone = clone $this;
        $clone->checkedAt = $checkedAt;
        return $clone;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'status_code' => $this->statusCode,
            'response_time_ms' => $this->responseTimeMs,
            'error' => $this->error,
            'meta' => $this->meta,
            'payload' => $this->payload,
            'ssl_valid_from' => $this->sslValidFrom?->toIso8601String(),
            'ssl_expires_at' => $this->sslExpiresAt?->toIso8601String(),
            'ssl_issuer' => $this->sslIssuer,
            'checked_at' => $this->checkedAt?->toIso8601String(),
            'record_history' => $this->recordHistory,
        ];
    }
}
