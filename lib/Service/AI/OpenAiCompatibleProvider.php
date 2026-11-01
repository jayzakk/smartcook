<?php

declare(strict_types=1);

namespace OCA\SmartCook\Service\AI;

use OCA\SmartCook\Exception\ImportException;

final class OpenAiCompatibleProvider implements AiProviderInterface {
    private const PROVIDERS = ['openai', 'openrouter', 'ollama', 'localai', 'mistral', 'custom'];

    public function __construct(
        private AiHttpClient $http,
        private AiPromptFactory $prompts,
        private AiJsonParser $json,
        private HttpResponseDecoder $responses,
    ) {
    }

    public function supports(string $provider): bool {
        return in_array($provider, self::PROVIDERS, true);
    }

    public function extractRecipe(string $text, string $language, array $config): array {
        $provider = (string)($config['provider'] ?? 'openai');
        $endpoint = rtrim((string)($config['endpoint'] ?? ''), '/');
        if ($endpoint === '') {
            $endpoint = match ($provider) {
                'openai' => 'https://api.openai.com/v1',
                'openrouter' => 'https://openrouter.ai/api/v1',
                'ollama' => 'http://127.0.0.1:11434/v1',
                'localai' => 'http://127.0.0.1:8080/v1',
                'mistral' => 'https://api.mistral.ai/v1',
                default => throw new ImportException('An AI endpoint is required'),
            };
        }
        $nativeOllama = $provider === 'ollama' && preg_match('~/api/generate$~i', $endpoint) === 1;
        if ($provider === 'ollama' && preg_match('~^https?://ollama\.com$~i', $endpoint) === 1) {
            $endpoint .= '/api/generate';
            $nativeOllama = true;
        }
        $model = trim((string)($config['model'] ?? ''));
        if ($model === '') {
            throw new ImportException('An AI model is required');
        }
        $headers = ['Content-Type' => 'application/json', 'Accept' => 'application/json'];
        $key = trim((string)($config['apiKey'] ?? ''));
        if ($key !== '') {
            $headers['Authorization'] = 'Bearer ' . $key;
        }
        if ($provider === 'openrouter') {
            $headers['X-Title'] = 'SmartCook for Nextcloud';
        }
        $temperature = (float)str_replace(',', '.', (string)($config['temperature'] ?? 0.1));
        $prompt = isset($config['assistant']) && is_array($config['assistant'])
            ? $this->prompts->assistant((string)($config['assistant']['question'] ?? ''), (array)($config['assistant']['recipes'] ?? []), $language)
            : (isset($config['planner']) && is_array($config['planner'])
            ? $this->prompts->mealPlan($config['planner']['recipes'] ?? [], (string)($config['planner']['from'] ?? ''), (string)($config['planner']['to'] ?? ''), (array)($config['planner']['preferences'] ?? []))
            : (isset($config['refinement']) && is_array($config['refinement']) ? $this->prompts->refinement($config['refinement'], $language, (array)($config['taxonomy'] ?? [])) : $this->prompts->recipe($text, $language, (array)($config['taxonomy'] ?? []))));
        $payload = $nativeOllama
            ? [
                'model' => $model,
                'prompt' => "You are a precise culinary data extraction engine.\n\n" . $prompt,
                'stream' => false,
                'options' => ['temperature' => $temperature],
            ]
            : [
                'model' => $model,
                'temperature' => $temperature,
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a precise culinary data extraction engine.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
            ];
        if (!$nativeOllama && in_array($provider, ['openai', 'openrouter', 'mistral'], true)) {
            $payload['response_format'] = ['type' => 'json_object'];
        }
        $response = $this->http->post($nativeOllama ? $endpoint : $endpoint . '/chat/completions', [
            'headers' => $headers,
            'body' => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'timeout' => (int)($config['timeout'] ?? 90),
        ], $provider);
        $body = $this->responses->json($response, 'The AI endpoint');
        $content = $nativeOllama
            ? ($body['response'] ?? null)
            : ($body['choices'][0]['message']['content'] ?? null);
        if (!is_string($content)) {
            throw new ImportException('The AI endpoint returned an unexpected response');
        }
        return $this->json->parse($content);
    }
}
