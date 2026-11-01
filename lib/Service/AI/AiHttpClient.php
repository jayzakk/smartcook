<?php

declare(strict_types=1);

namespace OCA\SmartCook\Service\AI;

use GuzzleHttp\Exception\RequestException;
use OCA\SmartCook\AppInfo\Application;
use OCA\SmartCook\Exception\ImportException;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use Psr\Log\LoggerInterface;

/**
 * POST helper for the AI providers: retries short-lived overload errors and turns HTTP failures into
 * ImportException messages that are safe to show (the Guzzle message contains the full URL, which for
 * Gemini includes the API key).
 */
final class AiHttpClient {
    private const MAX_ATTEMPTS = 3;
    private const MAX_WAIT_SECONDS = 10;
    private const RETRY_STATUSES = [429, 502, 503, 504];

    public function __construct(
        private IClientService $clients,
        private LoggerInterface $logger,
    ) {
    }

    /** @param array<string, mixed> $options */
    public function post(string $url, array $options, string $provider): IResponse {
        for ($attempt = 1;; $attempt++) {
            $status = 0;
            $retryAfter = '';
            $detail = '';
            try {
                $response = $this->clients->newClient()->post($url, $options);
                $status = $response->getStatusCode();
                if ($status < 400) {
                    return $response;
                }
                $retryAfter = (string)$response->getHeader('Retry-After');
                $detail = $this->excerpt($response->getBody());
            } catch (RequestException $e) {
                $failed = $e->getResponse();
                $status = $failed?->getStatusCode() ?? 0;
                $retryAfter = $failed?->getHeaderLine('Retry-After') ?? '';
                $detail = $failed !== null ? $this->excerpt($failed->getBody()) : get_class($e);
            } catch (\Throwable $e) {
                $detail = get_class($e);
            }

            $this->logger->warning('SmartCook AI request failed', [
                'app' => Application::APP_ID,
                'provider' => $provider,
                'host' => parse_url($url, PHP_URL_HOST),
                'status' => $status,
                'attempt' => $attempt,
                'detail' => $detail,
            ]);
            if ($attempt < self::MAX_ATTEMPTS && in_array($status, self::RETRY_STATUSES, true)) {
                sleep(ctype_digit($retryAfter) ? min((int)$retryAfter, self::MAX_WAIT_SECONDS) : 2 * $attempt);
                continue;
            }
            throw new ImportException($this->message($status));
        }
    }

    private function message(int $status): string {
        return match (true) {
            $status === 0 => 'The AI service could not be reached or did not answer in time',
            $status === 429, $status === 503 => 'The AI service is overloaded or rate-limited. Please try again in a few minutes or choose another model.',
            $status === 401, $status === 403 => 'The AI service rejected the API key or the access. Check the API key and model in the settings.',
            default => 'The AI service returned HTTP ' . $status,
        };
    }

    private function excerpt(mixed $body): string {
        if (is_resource($body)) {
            $body = stream_get_contents($body);
        }
        return mb_substr(trim((string)$body), 0, 300);
    }
}
