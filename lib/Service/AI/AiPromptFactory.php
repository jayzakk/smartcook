<?php

declare(strict_types=1);

namespace OCA\SmartCook\Service\AI;

use OCP\IL10N;

final class AiPromptFactory {
    public function __construct(private ?IL10N $l10n = null) {
    }

    /** @param list<string|int> $parameters */
    private function t(string $text, array $parameters = []): string {
        return $this->l10n?->t($text, $parameters) ?? vsprintf($text, $parameters);
    }

    /** @param list<array<string, mixed>> $recipes */
    public function assistant(string $question, array $recipes, string $language): string {
        $catalog = json_encode($recipes, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $schema = '{"answer":"string","recommendations":[{"recipeId":1,"reason":"string"}]}';
        return "You are SmartCook's cookbook assistant. Answer the user's question using only the supplied recipe catalog. You may give practical cooking advice, but never claim that a recipe, ingredient, quantity, allergen, time, or dietary property exists unless it is present in the catalog. When ingredients are available, recommend recipes that use the greatest useful overlap; clearly mention important missing ingredients. Do not invent recipe IDs. Keep the answer concise, helpful, and in {$language}. Return only valid JSON without Markdown using exactly this shape: {$schema}. Include at most 8 recommendations and only recipeId values from the catalog.\n\nUSER QUESTION:\n{$question}\n\nRECIPE CATALOG:\n{$catalog}";
    }

    /** @param array<string, mixed> $recipe */
    public function refinement(array $recipe, string $language, array $taxonomy = []): string {
        $recipeJson = json_encode($recipe, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $schema = <<<'JSON'
{"title":"string","subtitle":"string|null","description":"string|null","author":"string|null","sourceName":"string|null","sourceUrl":"string|null","difficulty":"integer 1 through 5|null","cuisine":"string|null","mealType":"string|null","cookingMethod":"string|null","season":"string|null","calories":null,"nutrition":{},"steps":[{"text":"string","timerSeconds":null,"temperature":null,"temperatureUnit":null,"notes":null}]|null,"tools":[{"name":"string"}],"tags":[{"name":"string"}],"categories":[{"name":"string"}],"clearFields":["tags|categories|tools|cuisine|mealType|cookingMethod|season"],"addCover":false,"coverSuggestion":"string|null"}
JSON;
        return "Review this existing recipe and propose only high-confidence editorial and organizational improvements. Return only valid JSON, no Markdown or commentary. You may read ingredients and procedure to understand the recipe, but NEVER modify ingredients, their quantities, times, servings, yield, an existing procedure, media, notes, status, visibility, or planner settings. Only when the recipe has no non-empty procedure, you may propose steps: write a concise, practical procedure based solely on the listed ingredients and reliable common cooking methods. Do not introduce unlisted ingredients, exact times, temperatures, or equipment; use null for unavailable step metadata. Return steps as null when a safe, useful procedure cannot be inferred. Do not invent facts; leave a field unchanged by returning its current value or null when no useful improvement can be made. To remove a superfluous field, list its name in clearFields and leave its normal value unchanged. Use clearFields only when a current value adds no distinct way to find the recipe, such as a tag that duplicates a category, meal type, cuisine or cooking method. For difficulty, use only an integer from 1 to 5: 1 very easy, 2 easy, 3 medium, 4 difficult, 5 very difficult. " . $this->classificationInstruction($taxonomy) . " Estimate calories per serving only when it can be reasonably inferred. Set addCover true only when the recipe has no cover and a representative food photo is appropriate; coverSuggestion must be a short search description, never a URL. Output language: {$language}. Use exactly this shape:\n{$schema}\n\nRECIPE:\n{$recipeJson}";
    }

    public function recipe(string $text, string $language, array $taxonomy = []): string {
        $schema = <<<'JSON'
{
  "title": "string",
  "subtitle": "string|null",
  "description": "string|null",
  "language": "BCP-47 language code",
  "author": "string|null",
  "sourceName": "string|null",
  "sourceUrl": "string|null",
  "servings": 1,
  "yieldText": "string|null",
  "prepTime": 0,
  "restTime": 0,
  "cookTime": 0,
  "totalTime": 0,
  "difficulty": "integer 1 through 5|null",
  "cuisine": "string|null",
  "mealType": "string|null",
  "cookingMethod": "string|null",
  "season": "string|null",
  "calories": null,
  "nutrition": {},
  "coverSuggestion": "string|null",
  "notes": "string|null",
  "ingredients": [{"name":"string","originalText":"string","quantity":"string|null","amount":null,"unit":"string|null","notes":"string|null","optional":false,"alternatives":[{"name":"string","quantity":"string|null","amount":null,"unit":"string|null","notes":"string|null"}],"group":"string|null","category":"string|null","allergens":[],"substitutes":[]}],
  "steps": [{"text":"string","timerSeconds":null,"temperature":null,"temperatureUnit":null,"notes":null}],
  "tools": [{"name":"string"}],
  "tags": [{"name":"string"}],
  "categories": [{"name":"string"}]
}
JSON;
        return "Extract one cooking recipe from the source below. Return only one valid JSON object, with no Markdown and no commentary. Preserve facts for ingredients, quantities, times, source and tools; never invent those details. When the source explicitly offers an ingredient replacement, place it in that ingredient's alternatives array instead of making it a separate main ingredient. Also enrich the recipe with useful editorial suggestions when the source does not state them. " . $this->classificationInstruction($taxonomy) . " For difficulty, use only an integer from 1 to 5: 1 very easy, 2 easy, 3 medium, 4 difficult, 5 very difficult; return null when it cannot be inferred. Estimate calories per serving from the ingredients when possible. For coverSuggestion, provide a short, specific photo-search description of the finished dish (for example, 'overhead photo of creamy mushroom risotto in a white bowl'); do not provide a URL. These are suggestions to review, so prefer null only when a meaningful suggestion is impossible. The description must be a brief description of the finished recipe result, not a repetition or list of ingredients or procedure; return null if no suitable description can be derived. Times are integer minutes, timerSeconds are seconds. Split ingredients and procedure into ordered arrays. Normalize units while retaining originalText. Output language should be {$language}. Use this exact shape:\n{$schema}\n\nSOURCE:\n{$text}";
    }

    /** @param array<string, mixed> $taxonomy */
    private function classificationInstruction(array $taxonomy): string {
        $choices = [];
        foreach (['tags', 'categories', 'cuisine', 'mealType', 'cookingMethod'] as $field) {
            $names = array_values(array_filter(array_map(static fn (mixed $item): string => trim((string)(is_array($item) ? ($item['name'] ?? '') : $item)), (array)($taxonomy[$field] ?? []))));
            $choices[$field] = $names;
        }
        $catalog = json_encode($choices, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return "For tags, categories, cuisine, mealType and cookingMethod, select ONLY exact labels from this existing SmartCook vocabulary: {$catalog}. Never invent, translate, rephrase, or create a new classification. Keep the organization compact: prefer the broadest accurate existing label, use the fewest labels that still help users find the recipe, and when labels are near-duplicates or aliases choose one existing label consistently. Do not duplicate the same meaning across tags, categories, cuisine, mealType or cookingMethod: for example, if 'Main course' is already the category or meal type, do not also use it as a tag. Do not add a more specific label when an assigned broader label already describes the recipe. If no listed value is appropriate, return null for single-value fields and [] for tags/categories. ";
    }

    /** @param list<array<string, mixed>> $recipes @param array<string, mixed> $preferences */
    public function mealPlan(array $recipes, string $from, string $to, array $preferences): string {
        $schema = '{"meals":[{"date":"YYYY-MM-DD","slot":"breakfast|lunch|dinner|snack","recipeId":1,"servings":2,"notes":"string|null"}]}';
        $catalog = json_encode($recipes, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $custom = trim((string)($preferences['prompt'] ?? ''));
        // The instructions are sent in the user's language; the JSON shape and its keys stay English.
        $instruction = $custom !== '' ? $custom : $this->t('Plan a varied, realistic week using only the available recipes.');
        return $instruction . "\n" . $this->t('Create a meal plan from %1$s to %2$s. Use only recipeId values that exist in the catalog. Respect the dietary preferences, allergies, maximum time and servings given. Add breakfast, lunch, dinner or snack where appropriate; do not add meals if no suitable recipe exists. Avoid close repetitions where possible. Return only valid JSON without Markdown in this shape: %3$s', [$from, $to, $schema])
            . "\n\n" . $this->t('PREFERENCES:') . "\n" . (string)($preferences['dietary'] ?? '')
            . "\n" . $this->t('MAXIMUM COOKING TIME (MINUTES):') . ' ' . (int)($preferences['cookingTime'] ?? 60)
            . "\n" . $this->t('SERVINGS:') . ' ' . (int)($preferences['servings'] ?? 2)
            . "\n" . $this->t('WEEKLY INSTRUCTIONS:') . "\n" . (string)($preferences['instruction'] ?? '')
            . "\n\n" . $this->t('RECIPE CATALOG:') . "\n" . $catalog;
    }
}
