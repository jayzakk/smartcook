<?php

declare(strict_types=1);

namespace OCA\SmartCook\Service\AI;

use OCA\SmartCook\Exception\ImportException;

final class AnthropicProvider implements AiProviderInterface {
    public function __construct(
        private AiHttpClient $http,
        private AiPromptFactory $prompts,
        private AiJsonParser $json,
        private HttpResponseDecoder $responses,
    ) {
    }

    public function supports(string $provider): bool {
        return $provider === 'anthropic';
    }

    public function extractRecipe(string $text, string $language, array $config): array {
        $key = trim((string)($config['apiKey'] ?? ''));
        if ($key === '') {
            throw new ImportException('An Anthropic API key is required');
        }
        $endpoint = rtrim((string)($config['endpoint'] ?? ''), '/') ?: 'https://api.anthropic.com/v1';
        $model = trim((string)($config['model'] ?? ''));
        if ($model === '') {
            throw new ImportException('An Anthropic model is required');
        }
        $payload = [
            'model' => $model,
            'max_tokens' => 8192,
            'temperature' => (float)($config['temperature'] ?? 0.1),
            'messages' => [['role' => 'user', 'content' => isset($config['assistant']) && is_array($config['assistant']) ? $this->prompts->assistant((string)($config['assistant']['question'] ?? ''), (array)($config['assistant']['recipes'] ?? []), $language) : (isset($config['planner']) && is_array($config['planner']) ? $this->prompts->mealPlan($config['planner']['recipes'] ?? [], (string)($config['planner']['from'] ?? ''), (string)($config['planner']['to'] ?? ''), (array)($config['planner']['preferences'] ?? [])) : (isset($config['refinement']) && is_array($config['refinement']) ? $this->prompts->refinement($config['refinement'], $language, (array)($config['taxonomy'] ?? [])) : $this->prompts->recipe($text, $language, (array)($config['taxonomy'] ?? []))))]],
        ];
        $response = $this->http->post($endpoint . '/messages', [
            'headers' => ['Content-Type' => 'application/json', 'x-api-key' => $key, 'anthropic-version' => '2023-06-01'],
            'body' => json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE),
            'timeout' => (int)($config['timeout'] ?? 90),
        ], 'anthropic');
        $decoded = $this->responses->json($response, 'Anthropic');
        $content = is_array($decoded) ? ($decoded['content'][0]['text'] ?? null) : null;
        if (!is_string($content)) {
            throw new ImportException('Anthropic returned an unexpected response');
        }
        return $this->json->parse($content);
    }
}
