<?php

declare(strict_types=1);

namespace OCA\SmartCook\Service\AI;

use OCA\SmartCook\Exception\ValidationException;
use OCA\SmartCook\Db\TaxonomyRepository;
use OCA\SmartCook\Service\CoverImageSearchService;
use OCA\SmartCook\Service\RecipeAccessService;
use OCA\SmartCook\Service\RecipeService;
use OCA\SmartCook\Service\UserContext;

final class RecipeRefinementService {
    private const FIELDS = ['title', 'subtitle', 'description', 'author', 'sourceName', 'sourceUrl', 'difficulty', 'cuisine', 'mealType', 'cookingMethod', 'season', 'calories', 'nutrition', 'steps', 'tools', 'tags', 'categories'];
    private const COLLECTION_FIELDS = ['tools', 'tags', 'categories'];
    private const CLEARABLE_FIELDS = ['cuisine', 'mealType', 'cookingMethod', 'season', 'tools', 'tags', 'categories'];

    public function __construct(
        private AiProviderRegistry $ai,
        private RecipeAccessService $access,
        private RecipeService $recipes,
        private CoverImageSearchService $covers,
        private TaxonomyRepository $taxonomy,
        private UserContext $userContext,
    ) {
    }

    /** @param list<int> $recipeIds @return list<array<string, mixed>> */
    public function analyze(array $recipeIds): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $recipeIds), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            throw new ValidationException('Select at least one recipe');
        }
        $results = [];
        foreach ($ids as $id) {
            $results[] = $this->analyzeOne($id);
        }
        return $results;
    }

    /** @return array<string, mixed> */
    public function analyzeOne(int $id): array {
        $recipe = $this->access->editable($id);
        // The person who asks uses their own AI settings and vocabulary, also for the recipe of a household member.
        $userId = $this->userContext->userId();
        $taxonomy = $this->taxonomy->listForUser($userId);
        $analysis = $this->ai->refine($userId, $recipe, (string)($recipe['language'] ?? 'it'), $taxonomy);
        $analysis = $this->taxonomy->restrictRecipeClassifications($analysis, $taxonomy);
        $clearFields = array_values(array_intersect(self::CLEARABLE_FIELDS, array_filter(
            (array)($analysis['clearFields'] ?? []),
            static fn (mixed $field): bool => is_string($field),
        )));
        $proposal = [];
        foreach (self::FIELDS as $field) {
            if ($field === 'steps' && (!$this->hasNoSteps($recipe['steps'] ?? null) || !$this->hasSteps($analysis['steps'] ?? null))) {
                continue;
            }
            if (in_array($field, $clearFields, true)) {
                $clearedValue = in_array($field, self::COLLECTION_FIELDS, true) ? [] : null;
                if ($this->valuesDiffer($recipe[$field] ?? null, $clearedValue, $field)) {
                    $proposal[$field] = $clearedValue;
                }
                continue;
            }
            if (array_key_exists($field, $analysis) && $analysis[$field] !== null && $this->valuesDiffer($recipe[$field] ?? null, $analysis[$field], $field)) {
                $proposal[$field] = $analysis[$field];
            }
        }
        $current = array_intersect_key($recipe, array_flip(self::FIELDS));
        return ['recipeId' => $id, 'title' => $recipe['title'], 'current' => $current, 'proposal' => $proposal, 'addCover' => (bool)($analysis['addCover'] ?? false) && empty($recipe['imagePath']), 'coverSuggestion' => $analysis['coverSuggestion'] ?? null];
    }

    private function valuesDiffer(mixed $current, mixed $proposed, string $field): bool {
        return $this->normalizedValue($current, $field) !== $this->normalizedValue($proposed, $field);
    }

    private function normalizedValue(mixed $value, string $field): mixed {
        if ($field === 'calories') {
            return $value === null || $value === '' ? null : (int)$value;
        }
        if (in_array($field, ['tools', 'tags', 'categories'], true)) {
            $names = [];
            foreach (is_array($value) ? $value : [] as $item) {
                $name = is_array($item) ? ($item['name'] ?? '') : $item;
                $name = $this->normalizedText($name);
                if ($name !== null) {
                    $names[] = mb_strtolower($name);
                }
            }
            $names = array_values(array_unique($names));
            sort($names, SORT_STRING);
            return $names;
        }
        if ($field === 'nutrition') {
            return $this->normalizedStructure($value);
        }
        if ($field === 'steps') {
            return $this->normalizedStructure($value);
        }
        return $this->normalizedText($value);
    }

    private function hasNoSteps(mixed $steps): bool {
        return !$this->hasSteps($steps);
    }

    private function hasSteps(mixed $steps): bool {
        if (!is_array($steps)) {
            return false;
        }
        foreach ($steps as $step) {
            $text = is_array($step) ? ($step['text'] ?? null) : $step;
            if ($this->normalizedText($text) !== null) {
                return true;
            }
        }
        return false;
    }

    private function normalizedStructure(mixed $value): mixed {
        if (!is_array($value)) {
            return is_string($value) ? $this->normalizedText($value) : $value;
        }
        $normalized = [];
        foreach ($value as $key => $item) {
            $normalized[$key] = $this->normalizedStructure($item);
        }
        if (!array_is_list($normalized)) {
            ksort($normalized, SORT_STRING);
        }
        return $normalized;
    }

    private function normalizedText(mixed $value): ?string {
        if ($value === null) {
            return null;
        }
        $text = preg_replace('/\\s+/u', ' ', trim((string)$value));
        return $text === '' ? null : $text;
    }

    /** @param list<array<string, mixed>> $proposals */
    public function apply(array $proposals): int {
        $changed = 0;
        foreach ($proposals as $proposal) {
            if (!is_array($proposal)) {
                continue;
            }
            $id = (int)($proposal['recipeId'] ?? 0);
            if ($id < 1) {
                continue;
            }
            $this->access->editable($id);
            $updated = false;
            $fields = is_array($proposal['fields'] ?? null) ? $proposal['fields'] : [];
            $safe = array_intersect_key($fields, array_flip(self::FIELDS));
            if ($safe !== []) {
                $this->recipes->update($id, $safe);
                $updated = true;
            }
            if (!empty($proposal['addCover'])) {
                $this->covers->findAndStore($id);
                $updated = true;
            }
            if ($updated) {
                $changed++;
            }
        }
        return $changed;
    }
}
