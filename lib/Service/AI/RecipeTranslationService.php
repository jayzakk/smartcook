<?php

declare(strict_types=1);

namespace OCA\SmartCook\Service\AI;

use OCA\SmartCook\Exception\ImportException;
use OCA\SmartCook\Exception\ValidationException;
use OCA\SmartCook\Service\UserContext;

/**
 * Translates the texts of a recipe with the AI the person configured. It works on the content it is given (a
 * saved recipe, an unsaved edit or an import preview) and only returns the translated texts; the caller decides
 * whether to show them or to put them into the recipe.
 */
final class RecipeTranslationService {
    private const MAX_CHARACTERS = 60000;
    private const TEXT_FIELDS = ['title', 'subtitle', 'description', 'cuisine', 'mealType', 'cookingMethod', 'season'];
    private const NAME_LISTS = ['categories', 'tags', 'tools'];

    public function __construct(
        private AiProviderRegistry $ai,
        private UserContext $userContext,
    ) {
    }

    /**
     * @param array<string, mixed> $recipe
     * @return array<string, mixed> the translated texts, same shape as the request, only for what could be translated
     */
    public function translate(array $recipe, string $language): array {
        if (preg_match('/^[A-Za-z]{2,3}([-_][A-Za-z0-9]{2,8})*$/', $language) !== 1) {
            throw new ValidationException('The target language is invalid', ['language' => 'Invalid language']);
        }
        $content = $this->content($recipe);
        if ($content === []) {
            throw new ValidationException('There is nothing to translate');
        }
        if (mb_strlen(json_encode($content, JSON_UNESCAPED_UNICODE) ?: '') > self::MAX_CHARACTERS) {
            throw new ValidationException('The recipe is too long to translate');
        }
        $result = $this->ai->translate($this->userContext->userId(), $content, $language);
        $translation = $this->accept($content, $result);
        if ($translation === []) {
            throw new ImportException('The AI returned an unusable translation');
        }
        return $translation;
    }

    /** @param array<string, mixed> $recipe @return array<string, mixed> */
    private function content(array $recipe): array {
        $content = [];
        foreach (self::TEXT_FIELDS as $field) {
            $value = trim((string)($recipe[$field] ?? ''));
            if ($value !== '') {
                $content[$field] = $value;
            }
        }
        foreach (self::NAME_LISTS as $field) {
            $names = array_values(array_filter(array_map(
                static fn (mixed $item): string => trim((string)(is_array($item) ? ($item['name'] ?? '') : $item)),
                is_array($recipe[$field] ?? null) ? $recipe[$field] : [],
            ), static fn (string $name): bool => $name !== ''));
            if ($names !== []) {
                $content[$field] = $names;
            }
        }
        $ingredients = [];
        foreach (is_array($recipe['ingredients'] ?? null) ? $recipe['ingredients'] : [] as $item) {
            $entry = [];
            foreach (['name', 'notes', 'group'] as $field) {
                $value = trim((string)(is_array($item) ? ($item[$field] ?? $item[$field === 'group' ? 'groupName' : $field] ?? '') : ($field === 'name' ? $item : '')));
                if ($value !== '') {
                    $entry[$field] = $value;
                }
            }
            $ingredients[] = $entry === [] ? new \stdClass() : $entry;
        }
        if (array_filter($ingredients, static fn ($entry): bool => $entry instanceof \stdClass ? false : true) !== []) {
            $content['ingredients'] = $ingredients;
        }
        $steps = [];
        foreach (is_array($recipe['steps'] ?? null) ? $recipe['steps'] : [] as $step) {
            $text = trim((string)(is_array($step) ? ($step['text'] ?? '') : $step));
            $steps[] = $text === '' ? new \stdClass() : ['text' => $text];
        }
        if (array_filter($steps, static fn ($entry): bool => !($entry instanceof \stdClass)) !== []) {
            $content['steps'] = $steps;
        }
        return $content;
    }

    /**
     * Keeps what matches the request: the same fields, and lists with the same number of items.
     *
     * @param array<string, mixed> $content @param array<string, mixed> $result @return array<string, mixed>
     */
    private function accept(array $content, array $result): array {
        $out = [];
        foreach (self::TEXT_FIELDS as $field) {
            if (isset($content[$field]) && is_string($result[$field] ?? null) && trim($result[$field]) !== '') {
                $out[$field] = trim($result[$field]);
            }
        }
        foreach (self::NAME_LISTS as $field) {
            if (isset($content[$field]) && is_array($result[$field] ?? null) && count($result[$field]) === count($content[$field])) {
                $out[$field] = array_map(static fn (mixed $value): string => trim((string)$value), array_values($result[$field]));
            }
        }
        foreach (['ingredients' => ['name', 'notes', 'group'], 'steps' => ['text']] as $list => $fields) {
            if (!isset($content[$list]) || !is_array($result[$list] ?? null) || count($result[$list]) !== count($content[$list])) {
                continue;
            }
            $items = [];
            foreach (array_values($result[$list]) as $index => $item) {
                $original = (array)$content[$list][$index];
                $entry = [];
                foreach ($fields as $field) {
                    if (isset($original[$field]) && is_array($item) && is_string($item[$field] ?? null) && trim($item[$field]) !== '') {
                        $entry[$field] = trim($item[$field]);
                    }
                }
                $items[] = $entry;
            }
            $out[$list] = $items;
        }
        return $out;
    }
}
