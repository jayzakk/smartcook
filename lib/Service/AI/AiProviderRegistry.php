<?php

declare(strict_types=1);

namespace OCA\SmartCook\Service\AI;

use OCA\SmartCook\Exception\ImportException;
use OCA\SmartCook\Service\SettingsService;

final class AiProviderRegistry {
    /** @var list<AiProviderInterface> */
    private array $providers;

    public function __construct(
        NextcloudTaskProvider $nextcloud,
        OpenAiCompatibleProvider $openAi,
        AnthropicProvider $anthropic,
        GeminiProvider $gemini,
        private SettingsService $settings,
    ) {
        $this->providers = [$nextcloud, $openAi, $anthropic, $gemini];
    }

    /** @return array<string, mixed> */
    public function extract(string $userId, string $text, string $language, ?string $providerOverride = null, array $taxonomy = []): array {
        $config = $this->settings->ai($userId);
        $providerId = $providerOverride !== null && $providerOverride !== '' ? $providerOverride : (string)$config['provider'];
        if ($providerId === 'disabled') {
            throw new ImportException('AI extraction is disabled');
        }
        $config['provider'] = $providerId;
        $config['userId'] = $userId;
        $config['taxonomy'] = $taxonomy;
        foreach ($this->providers as $provider) {
            if ($provider->supports($providerId)) {
                return $this->call($provider, $text, $language, $config);
            }
        }
        throw new ImportException('Unsupported AI provider: ' . $providerId);
    }

    /** @param list<array<string, mixed>> $recipes @param array<string, mixed> $preferences @return array<string, mixed> */
    public function plan(string $userId, array $recipes, string $from, string $to, array $preferences): array {
        $config = $this->settings->ai($userId);
        $providerId = (string)$config['provider'];
        if ($providerId === 'disabled') {
            throw new ImportException('AI meal planning is disabled');
        }
        $config['userId'] = $userId;
        foreach ($this->providers as $provider) {
            if ($provider->supports($providerId)) {
                $config['planner'] = ['recipes' => $recipes, 'from' => $from, 'to' => $to, 'preferences' => $preferences];
                return $this->call($provider, '', 'it', $config);
            }
        }
        throw new ImportException('Unsupported AI provider: ' . $providerId);
    }

    /** @param array<string, mixed> $content @return array<string, mixed> */
    public function translate(string $userId, array $content, string $language): array {
        $config = $this->settings->ai($userId);
        $providerId = (string)$config['provider'];
        if ($providerId === 'disabled') {
            throw new ImportException('AI translation is disabled');
        }
        $config['userId'] = $userId;
        $config['translation'] = $content;
        foreach ($this->providers as $provider) {
            if ($provider->supports($providerId)) {
                return $this->call($provider, '', $language, $config);
            }
        }
        throw new ImportException('Unsupported AI provider: ' . $providerId);
    }

    /** @param array<string, mixed> $recipe @return array<string, mixed> */
    public function refine(string $userId, array $recipe, string $language, array $taxonomy = []): array {
        $config = $this->settings->ai($userId);
        $providerId = (string)$config['provider'];
        if ($providerId === 'disabled') {
            throw new ImportException('AI refinement is disabled');
        }
        $config['userId'] = $userId;
        $config['refinement'] = $recipe;
        $config['taxonomy'] = $taxonomy;
        foreach ($this->providers as $provider) {
            if ($provider->supports($providerId)) {
                return $this->call($provider, '', $language, $config);
            }
        }
        throw new ImportException('Unsupported AI provider: ' . $providerId);
    }

    /** @param list<array<string, mixed>> $recipes @return array<string, mixed> */
    public function answer(string $userId, string $question, array $recipes, string $language): array {
        $config = $this->settings->ai($userId);
        $providerId = (string)$config['provider'];
        if ($providerId === 'disabled') {
            throw new ImportException('The cookbook assistant requires an enabled AI provider');
        }
        $config['userId'] = $userId;
        $config['assistant'] = ['question' => $question, 'recipes' => $recipes];
        foreach ($this->providers as $provider) {
            if ($provider->supports($providerId)) {
                return $this->call($provider, '', $language, $config);
            }
        }
        throw new ImportException('Unsupported AI provider: ' . $providerId);
    }

    /**
     * Calls the provider; an answer that is not valid JSON is a hiccup of the model, so it gets one more try.
     *
     * @param array<string, mixed> $config @return array<string, mixed>
     */
    private function call(AiProviderInterface $provider, string $text, string $language, array $config): array {
        try {
            return $provider->extractRecipe($text, $language, $config);
        } catch (ImportException $e) {
            if ($e->getMessage() !== 'The AI provider did not return valid JSON') {
                throw $e;
            }
            return $provider->extractRecipe($text, $language, $config);
        }
    }
}
