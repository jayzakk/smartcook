<?php

declare(strict_types=1);

namespace OCA\SmartCook\Service;

use OCA\SmartCook\AppInfo\Application;
use OCP\IConfig;
use OCP\Security\ICrypto;

final class SettingsService {
    /** Former built-in default of aiPlannerPrompt; it was stored verbatim whenever settings were saved. */
    private const LEGACY_PLANNER_PROMPT = 'Organizza una settimana varia e realistica usando esclusivamente le ricette disponibili.';

    private const DEFAULTS = [
        'language' => 'auto',
        'measurementSystem' => 'metric',
        'attachmentsFolder' => 'SmartCook',
        'aiProvider' => 'disabled',
        'aiEndpoint' => '',
        'aiModel' => '',
        'aiTemperature' => '0.1',
        'aiTimeout' => '90',
        'aiPlannerPrompt' => '',
        'plannerPreferences' => '',
        'plannerCookingTime' => '60',
        'plannerServings' => '2',
        'ocrProvider' => 'disabled',
        'ocrEndpoint' => '',
        'ocrLanguage' => 'ita+eng',
        'tesseractPath' => 'tesseract',
        'pdfToTextPath' => 'pdftotext',
        'maxImportBytes' => '3000000',
        'googleImageSearchEngineId' => '',
        'coverImageProvider' => 'google',
    ];

    public function __construct(private IConfig $config, private ICrypto $crypto) {
    }

    /** @return array<string, mixed> */
    public function get(string $userId, bool $includeSecrets = false): array {
        $result = [];
        foreach (self::DEFAULTS as $key => $default) {
            $result[$key] = $this->config->getUserValue($userId, Application::APP_ID, $key, $default);
        }
        // An empty prompt means "use the built-in default in the user's language" (see AiPromptFactory).
        if (trim((string)$result['aiPlannerPrompt']) === self::LEGACY_PLANNER_PROMPT) {
            $result['aiPlannerPrompt'] = '';
        }
        $result['aiTemperature'] = (float)$result['aiTemperature'];
        $result['aiTimeout'] = (int)$result['aiTimeout'];
        $result['plannerCookingTime'] = (int)$result['plannerCookingTime'];
        $result['plannerServings'] = (int)$result['plannerServings'];
        $result['maxImportBytes'] = (int)$result['maxImportBytes'];
        $result['hasAiApiKey'] = $this->secret($userId, 'aiApiKey') !== '';
        $result['hasOcrApiKey'] = $this->secret($userId, 'ocrApiKey') !== '';
        $result['hasGoogleImageSearchApiKey'] = $this->secret($userId, 'googleImageSearchApiKey') !== '';
        $result['hasPexelsApiKey'] = $this->secret($userId, 'pexelsApiKey') !== '';
        $result['hasUnsplashAccessKey'] = $this->secret($userId, 'unsplashAccessKey') !== '';
        if ($includeSecrets) {
            $result['aiApiKey'] = $this->secret($userId, 'aiApiKey');
            $result['ocrApiKey'] = $this->secret($userId, 'ocrApiKey');
            $result['googleImageSearchApiKey'] = $this->secret($userId, 'googleImageSearchApiKey');
            $result['pexelsApiKey'] = $this->secret($userId, 'pexelsApiKey');
            $result['unsplashAccessKey'] = $this->secret($userId, 'unsplashAccessKey');
        }
        return $result;
    }

    /** @param array<string, mixed> $values @return array<string, mixed> */
    public function save(string $userId, array $values): array {
        foreach (self::DEFAULTS as $key => $default) {
            if (!array_key_exists($key, $values)) {
                continue;
            }
            $value = $values[$key];
            if ($key === 'measurementSystem' && !in_array($value, ['metric', 'imperial'], true)) {
                continue;
            }
            if ($key === 'aiProvider' && !in_array($value, ['disabled', 'nextcloud', 'openai', 'openrouter', 'ollama', 'localai', 'mistral', 'anthropic', 'gemini', 'custom'], true)) {
                continue;
            }
            if ($key === 'ocrProvider' && !in_array($value, ['disabled', 'local', 'external'], true)) {
                continue;
            }
            if ($key === 'coverImageProvider' && !in_array($value, ['google', 'pexels', 'unsplash'], true)) {
                continue;
            }
            if ($key === 'aiTemperature') {
                $value = (string)max(0.0, min(2.0, (float)$value));
            } elseif ($key === 'aiTimeout') {
                $value = (string)max(10, min(300, (int)$value));
            } elseif ($key === 'maxImportBytes') {
                $value = (string)max(100000, min(20000000, (int)$value));
            } elseif ($key === 'plannerCookingTime') {
                $value = (string)max(5, min(600, (int)$value));
            } elseif ($key === 'plannerServings') {
                $value = (string)max(1, min(30, (int)$value));
            } else {
                $value = trim((string)$value);
            }
            $this->config->setUserValue($userId, Application::APP_ID, $key, $value);
        }
        foreach (['aiApiKey', 'ocrApiKey', 'googleImageSearchApiKey', 'pexelsApiKey', 'unsplashAccessKey'] as $key) {
            $clearKey = 'clear' . ucfirst($key);
            if (($values[$clearKey] ?? false) === true || ($values[$clearKey] ?? '') === '1') {
                $this->config->deleteUserValue($userId, Application::APP_ID, $key);
                continue;
            }
            if (!array_key_exists($key, $values)) {
                continue;
            }
            $plain = trim((string)$values[$key]);
            // Empty fields deliberately preserve the already encrypted secret.
            if ($plain !== '') {
                $this->config->setUserValue($userId, Application::APP_ID, $key, $this->crypto->encrypt($plain));
            }
        }
        return $this->get($userId);
    }

    /** @return array<string, mixed> */
    public function ai(string $userId): array {
        $settings = $this->get($userId, true);
        return [
            'provider' => $settings['aiProvider'],
            'endpoint' => $settings['aiEndpoint'],
            'model' => $settings['aiModel'],
            'apiKey' => $settings['aiApiKey'],
            'temperature' => $settings['aiTemperature'],
            'timeout' => $settings['aiTimeout'],
            'plannerPrompt' => $settings['aiPlannerPrompt'],
            'plannerPreferences' => $settings['plannerPreferences'],
            'plannerCookingTime' => $settings['plannerCookingTime'],
            'plannerServings' => $settings['plannerServings'],
        ];
    }

    /** @return array<string, mixed> */
    public function ocr(string $userId): array {
        $settings = $this->get($userId, true);
        return [
            'provider' => $settings['ocrProvider'],
            'endpoint' => $settings['ocrEndpoint'],
            'apiKey' => $settings['ocrApiKey'],
            'language' => $settings['ocrLanguage'],
            'tesseractPath' => $settings['tesseractPath'],
            'pdfToTextPath' => $settings['pdfToTextPath'],
            'timeout' => $settings['aiTimeout'],
        ];
    }

    private function secret(string $userId, string $key): string {
        $encrypted = $this->config->getUserValue($userId, Application::APP_ID, $key, '');
        if ($encrypted === '') {
            return '';
        }
        try {
            return $this->crypto->decrypt($encrypted);
        } catch (\Throwable) {
            return '';
        }
    }
}
