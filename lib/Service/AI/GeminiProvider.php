<?php

declare(strict_types=1);

namespace OCA\SmartCook\Service\AI;

use OCA\SmartCook\Exception\ImportException;

final class GeminiProvider implements AiProviderInterface {
    public function __construct(
        private AiHttpClient $http,
        private AiPromptFactory $prompts,
        private AiJsonParser $json,
        private HttpResponseDecoder $responses,
    ) {
    }

    public function supports(string $provider): bool {
        return $provider === 'gemini';
    }

    public function extractRecipe(string $text, string $language, array $config): array {
        $key = trim((string)($config['apiKey'] ?? ''));
        if ($key === '') {
            throw new ImportException('A Gemini API key is required');
        }
        $model = trim((string)($config['model'] ?? ''));
        if ($model === '') {
            throw new ImportException('A Gemini model is required');
        }
        $endpoint = rtrim((string)($config['endpoint'] ?? ''), '/') ?: 'https://generativelanguage.googleapis.com/v1beta';
        $url = $endpoint . '/models/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($key);
        $payload = [
            'contents' => [['parts' => [['text' => $this->prompts->forRequest($config, $text, $language)]]]],
            'generationConfig' => ['temperature' => (float)($config['temperature'] ?? 0.1), 'responseMimeType' => 'application/json'],
        ];
        $response = $this->http->post($url, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'timeout' => (int)($config['timeout'] ?? 90),
        ], 'gemini');
        $decoded = $this->responses->json($response, 'Gemini');
        $content = is_array($decoded) ? ($decoded['candidates'][0]['content']['parts'][0]['text'] ?? null) : null;
        if (!is_string($content)) {
            throw new ImportException('Gemini returned an unexpected response');
        }
        return $this->json->parse($content);
    }
}
