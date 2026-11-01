<?php

declare(strict_types=1);

namespace OCA\SmartCook\Service\AI;

use OCA\SmartCook\AppInfo\Application;
use OCA\SmartCook\Exception\ImportException;
use OCP\TaskProcessing\IManager;
use OCP\TaskProcessing\Task;
use OCP\TaskProcessing\TaskTypes\TextToText;
use OCP\TaskProcessing\TaskTypes\TextToTextChat;

final class NextcloudTaskProvider implements AiProviderInterface {
    public function __construct(
        private IManager $manager,
        private AiPromptFactory $prompts,
        private AiJsonParser $json,
    ) {
    }

    public function supports(string $provider): bool {
        return $provider === 'nextcloud';
    }

    public function extractRecipe(string $text, string $language, array $config): array {
        $taskTypes = $this->manager->getAvailableTaskTypes();
        if (!$this->manager->hasProviders() || (!array_key_exists(TextToText::ID, $taskTypes) && !array_key_exists(TextToTextChat::ID, $taskTypes))) {
            throw new ImportException('No compatible Nextcloud Assistant provider is available');
        }
        $prompt = $this->prompts->forRequest($config, $text, $language);
        $task = array_key_exists(TextToText::ID, $taskTypes)
            ? new Task(TextToText::ID, ['input' => $prompt], Application::APP_ID, (string)($config['userId'] ?? ''))
            : new Task(TextToTextChat::ID, ['system_prompt' => 'Follow the user request exactly. Return only the requested JSON object, with no Markdown or commentary.', 'input' => $prompt, 'history' => []], Application::APP_ID, (string)($config['userId'] ?? ''));
        $result = $this->manager->runTask($task)->getOutput();
        $output = is_array($result) ? ($result['output'] ?? null) : null;
        if (!is_string($output) || trim($output) === '') {
            throw new ImportException('Nextcloud Assistant returned an empty response');
        }
        return $this->json->parse($output);
    }
}
