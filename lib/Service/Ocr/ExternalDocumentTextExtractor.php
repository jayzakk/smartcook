<?php

declare(strict_types=1);

namespace OCA\SmartCook\Service\Ocr;

use OCA\SmartCook\Exception\ImportException;
use OCP\Http\Client\IClientService;

final class ExternalDocumentTextExtractor implements DocumentTextExtractorInterface {
    public function __construct(private IClientService $clients) {
    }

    public function supports(string $provider): bool {
        return $provider === 'external';
    }

    public function extract(string $path, string $mimeType, string $originalName, array $config): string {
        $endpoint = trim((string)($config['endpoint'] ?? ''));
        if ($endpoint === '' || filter_var($endpoint, FILTER_VALIDATE_URL) === false) {
            throw new ImportException('A valid external OCR endpoint is required');
        }
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new ImportException('The uploaded document is not readable');
        }
        $headers = ['Accept' => 'application/json'];
        if (trim((string)($config['apiKey'] ?? '')) !== '') {
            $headers['Authorization'] = 'Bearer ' . trim((string)$config['apiKey']);
        }
        try {
            $response = $this->clients->newClient()->post($endpoint, [
                'headers' => $headers,
                'multipart' => [
                    ['name' => 'file', 'contents' => $handle, 'filename' => $originalName, 'headers' => ['Content-Type' => $mimeType]],
                    ['name' => 'mimeType', 'contents' => $mimeType],
                    ['name' => 'language', 'contents' => (string)($config['language'] ?? 'eng')],
                ],
                'timeout' => (int)($config['timeout'] ?? 90),
            ]);
        } finally {
            fclose($handle);
        }
        if ($response->getStatusCode() < 200 || $response->getStatusCode() >= 300) {
            throw new ImportException('The external OCR endpoint returned HTTP ' . $response->getStatusCode());
        }
        $body = $response->getBody();
        if (is_resource($body)) {
            $body = stream_get_contents($body);
        }
        try {
            $decoded = json_decode((string)$body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $e) {
            throw new ImportException('The external OCR endpoint returned invalid JSON', $e);
        }
        $text = is_array($decoded) ? ($decoded['text'] ?? null) : null;
        if (!is_string($text) || trim($text) === '') {
            throw new ImportException('The external OCR endpoint returned no text');
        }
        return $text;
    }
}
