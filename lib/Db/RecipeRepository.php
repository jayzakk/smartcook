<?php

declare(strict_types=1);

namespace OCA\SmartCook\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCA\SmartCook\Service\HouseholdService;
use OCP\IDBConnection;

final class RecipeRepository extends AbstractRepository {
    public function __construct(IDBConnection $db, HouseholdService $household, private TaxonomyRepository $taxonomy) {
        parent::__construct($db, $household);
    }

    /**
     * @param list<int> $sharedRecipeIds
     * @param array<string, mixed> $filters
     * @return list<array<string, mixed>>
     */
    public function listAccessible(string $userId, array $sharedRecipeIds = [], array $filters = [], int $limit = 200): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('r.*')->from('smartcook_recipes', 'r');

        $ownerExpression = $this->ownedBy($qb, 'r.user_id', $userId);
        if ($sharedRecipeIds !== []) {
            $params = array_map(
                fn (int $id): mixed => $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT),
                array_values(array_unique($sharedRecipeIds)),
            );
            $qb->where($qb->expr()->orX($ownerExpression, $qb->expr()->in('r.id', $params)));
        } else {
            $qb->where($ownerExpression);
        }

        $search = trim((string)($filters['search'] ?? ''));
        if ($search !== '') {
            $pattern = '%' . $this->db->escapeLikeParameter($search) . '%';
            $qb->andWhere($qb->expr()->orX(
                $qb->expr()->iLike('r.title', $qb->createNamedParameter($pattern)),
                $qb->expr()->iLike('r.description', $qb->createNamedParameter($pattern)),
                $qb->expr()->iLike('r.cuisine', $qb->createNamedParameter($pattern)),
            ));
        }
        if (isset($filters['favorite'])) {
            $qb->andWhere($qb->expr()->eq('r.favorite', $qb->createNamedParameter((bool)$filters['favorite'], IQueryBuilder::PARAM_BOOL)));
        }
        if (!empty($filters['status'])) {
            $qb->andWhere($qb->expr()->eq('r.status', $qb->createNamedParameter((string)$filters['status'])));
        }
        if (!empty($filters['difficulty'])) {
            $qb->andWhere($qb->expr()->eq('r.difficulty', $qb->createNamedParameter((string)$filters['difficulty'])));
        }
        if (isset($filters['maxTime']) && (int)$filters['maxTime'] > 0) {
            $qb->andWhere($qb->expr()->lte('r.total_time', $qb->createNamedParameter((int)$filters['maxTime'], IQueryBuilder::PARAM_INT)));
        }
        if (isset($filters['maxCalories']) && (int)$filters['maxCalories'] > 0) {
            $qb->andWhere($qb->expr()->lte('r.calories', $qb->createNamedParameter((int)$filters['maxCalories'], IQueryBuilder::PARAM_INT)));
        }

        $allowedSort = ['updated_at', 'created_at', 'title', 'total_time', 'cook_count'];
        $sort = in_array($filters['sort'] ?? '', $allowedSort, true) ? (string)$filters['sort'] : 'updated_at';
        $direction = strtoupper((string)($filters['direction'] ?? 'DESC')) === 'ASC' ? 'ASC' : 'DESC';
        $qb->orderBy('r.' . $sort, $direction)->setMaxResults(max(1, min(500, $limit)));

        $recipes = array_map(fn (array $row): array => $this->mapRecipeRow($row), $this->fetchAll($qb));
        foreach ($recipes as &$recipe) {
            $recipe['categories'] = $this->taxonomy->getCategories((int)$recipe['id']);
        }
        unset($recipe);

        $tagNames = $this->normalizeFilterList($filters['tags'] ?? []);
        $categoryNames = $this->normalizeFilterList($filters['categories'] ?? []);
        $toolNames = $this->normalizeFilterList($filters['tools'] ?? []);
        $ingredientNames = $this->normalizeFilterList($filters['ingredients'] ?? []);
        $excludedAllergens = $this->normalizeFilterList($filters['excludeAllergens'] ?? []);

        if ($tagNames === [] && $categoryNames === [] && $toolNames === [] && $ingredientNames === [] && $excludedAllergens === []) {
            return $recipes;
        }

        return array_values(array_filter($recipes, function (array $recipe) use ($tagNames, $categoryNames, $toolNames, $ingredientNames, $excludedAllergens): bool {
            $id = (int)$recipe['id'];
            if ($tagNames !== [] && !$this->containsAny($this->taxonomy->getTags($id), $tagNames)) {
                return false;
            }
            if ($categoryNames !== [] && !$this->containsAny((array)$recipe['categories'], $categoryNames)) {
                return false;
            }
            if ($toolNames !== [] && !$this->containsAll($this->taxonomy->getTools($id), $toolNames)) {
                return false;
            }
            $ingredients = $this->taxonomy->getIngredients($id);
            if ($ingredientNames !== [] && !$this->containsAll($ingredients, $ingredientNames)) {
                return false;
            }
            if ($excludedAllergens !== []) {
                foreach ($ingredients as $ingredient) {
                    $allergens = array_map(fn ($v): string => mb_strtolower((string)$v), (array)($ingredient['allergens'] ?? []));
                    if (array_intersect($excludedAllergens, $allergens) !== []) {
                        return false;
                    }
                }
            }
            return true;
        }));
    }

    public function find(int $id): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('smartcook_recipes')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->setMaxResults(1);
        $row = $this->fetchOne($qb);
        return $row === null ? null : $this->mapRecipeRow($row);
    }

    /** @return list<int> */
    public function listMissingCoverIds(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')->from('smartcook_recipes')
            ->where($this->ownedBy($qb, 'user_id', $userId))
            ->andWhere($qb->expr()->orX(
                $qb->expr()->isNull('image_path'),
                $qb->expr()->eq('image_path', $qb->createNamedParameter('')),
            ))
            ->orderBy('id', 'ASC');
        return array_map(static fn (array $row): int => (int)$row['id'], $this->fetchAll($qb));
    }

    /** @return array<string, mixed>|null */
    public function findMedia(int $mediaId): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('smartcook_media')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($mediaId, IQueryBuilder::PARAM_INT)))
            ->setMaxResults(1);
        $row = $this->fetchOne($qb);
        if ($row === null) {
            return null;
        }
        return [
            'id' => (int)$row['id'],
            'recipeId' => (int)$row['recipe_id'],
            'stepId' => $row['step_id'] !== null ? (int)$row['step_id'] : null,
            'kind' => (string)$row['kind'],
            'path' => (string)$row['path'],
            'mime' => $row['mime'],
            'altText' => $row['alt_text'],
            'sortOrder' => (int)$row['sort_order'],
        ];
    }

    public function findDetailed(int $id): ?array {
        $recipe = $this->find($id);
        if ($recipe === null) {
            return null;
        }
        $recipe['ingredients'] = $this->taxonomy->getIngredients($id);
        $recipe['steps'] = $this->getSteps($id);
        $recipe['tools'] = $this->taxonomy->getTools($id);
        $recipe['tags'] = $this->taxonomy->getTags($id);
        $recipe['categories'] = $this->taxonomy->getCategories($id);
        $recipe['media'] = $this->getMedia($id);
        return $recipe;
    }

    /** @param array<string, mixed> $data */
    public function createRecipe(string $userId, array $data): array {
        $this->db->beginTransaction();
        try {
            $now = time();
            $id = $this->insert('smartcook_recipes', $this->recipeRow($data, $userId, $now, true));
            $this->syncChildren($id, $userId, $data, $now);
            $recipe = $this->findDetailed($id) ?? throw new \RuntimeException('Recipe could not be reloaded after creation');
            $this->saveVersion($id, 1, $userId, $recipe, $now);
            $this->db->commit();
            return $recipe;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** @param array<string, mixed> $data */
    public function updateRecipe(int $id, string $actingUserId, array $data): array {
        $existing = $this->findDetailed($id) ?? throw new \RuntimeException('Recipe not found');
        $ownerId = (string)$existing['ownerId'];
        $now = time();
        $revision = (int)$existing['revision'] + 1;

        $this->db->beginTransaction();
        try {
            $row = $this->recipeRow(array_merge($existing, $data, ['revision' => $revision]), $ownerId, $now, false);
            unset($row['uuid'], $row['user_id'], $row['created_at']);
            $this->update('smartcook_recipes', $id, $row);
            $this->syncChildren($id, $ownerId, array_merge($existing, $data), $now);
            $recipe = $this->findDetailed($id) ?? throw new \RuntimeException('Recipe could not be reloaded after update');
            $this->saveVersion($id, $revision, $actingUserId, $recipe, $now);
            $this->db->commit();
            return $recipe;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function deleteRecipe(int $id): void {
        $this->deleteRecipes([$id]);
    }

    /** @param list<int> $ids */
    public function deleteRecipes(array $ids): void {
        if ($ids === []) {
            return;
        }
        $this->db->beginTransaction();
        try {
            foreach ($ids as $id) {
                $this->taxonomy->deleteRecipeRelations($id);
                foreach (['smartcook_steps', 'smartcook_media', 'smartcook_versions', 'smartcook_shares', 'smartcook_meals'] as $table) {
                    $this->deleteBy($table, 'recipe_id', $id);
                }
                $this->deleteBy('smartcook_recipes', 'id', $id);
            }
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function setFavorite(int $id, bool $favorite): void {
        $this->update('smartcook_recipes', $id, ['favorite' => $favorite, 'updated_at' => time()]);
    }

    public function markCooked(int $id): void {
        $recipe = $this->find($id) ?? throw new \RuntimeException('Recipe not found');
        $this->update('smartcook_recipes', $id, [
            'cook_count' => (int)$recipe['cookCount'] + 1,
            'last_cooked' => time(),
            'updated_at' => time(),
        ]);
    }

    /** @return list<array<string, mixed>> */
    public function listVersions(int $recipeId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id', 'revision', 'user_id', 'created_at')->from('smartcook_versions')
            ->where($qb->expr()->eq('recipe_id', $qb->createNamedParameter($recipeId, IQueryBuilder::PARAM_INT)))
            ->orderBy('revision', 'DESC');
        return array_map(static fn (array $row): array => [
            'id' => (int)$row['id'],
            'revision' => (int)$row['revision'],
            'userId' => (string)$row['user_id'],
            'createdAt' => (int)$row['created_at'],
        ], $this->fetchAll($qb));
    }

    public function getVersion(int $recipeId, int $revision): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('snapshot')->from('smartcook_versions')
            ->where($qb->expr()->eq('recipe_id', $qb->createNamedParameter($recipeId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('revision', $qb->createNamedParameter($revision, IQueryBuilder::PARAM_INT)))
            ->setMaxResults(1);
        $row = $this->fetchOne($qb);
        return $row === null ? null : $this->decode($row['snapshot'], null);
    }

    /** @return array<string, int|float> */
    public function stats(string $userId, array $sharedIds = []): array {
        $recipes = $this->listAccessible($userId, $sharedIds, [], 500);
        $count = count($recipes);
        $favorites = 0;
        $cooked = 0;
        $totalTime = 0;
        foreach ($recipes as $recipe) {
            $favorites += $recipe['favorite'] ? 1 : 0;
            $cooked += (int)$recipe['cookCount'];
            $totalTime += (int)$recipe['totalTime'];
        }
        return [
            'recipes' => $count,
            'favorites' => $favorites,
            'timesCooked' => $cooked,
            'averageTotalTime' => $count > 0 ? round($totalTime / $count, 1) : 0,
        ];
    }

    /** @return list<array<string, mixed>> */
    public function searchOwned(string $userId, string $term, int $limit = 20): array {
        return $this->listAccessible($userId, [], ['search' => $term], $limit);
    }

    /** @param array<string, mixed> $data */
    private function recipeRow(array $data, string $userId, int $now, bool $isNew): array {
        $prep = max(0, (int)($data['prepTime'] ?? $data['prep_time'] ?? 0));
        $rest = max(0, (int)($data['restTime'] ?? $data['rest_time'] ?? 0));
        $cook = max(0, (int)($data['cookTime'] ?? $data['cook_time'] ?? 0));
        $total = max(0, (int)($data['totalTime'] ?? $data['total_time'] ?? ($prep + $rest + $cook)));
        if ($total === 0) {
            $total = $prep + $rest + $cook;
        }

        return [
            'uuid' => (string)($data['uuid'] ?? $this->uuid()),
            'user_id' => $userId,
            'title' => trim((string)($data['title'] ?? 'Untitled recipe')),
            'subtitle' => $this->nullString($data['subtitle'] ?? null),
            'description' => $this->nullString($data['description'] ?? null),
            'language' => trim((string)($data['language'] ?? 'en')) ?: 'en',
            'author' => $this->nullString($data['author'] ?? null),
            'source_name' => $this->nullString($data['sourceName'] ?? $data['source_name'] ?? null),
            'source_url' => $this->nullString($data['sourceUrl'] ?? $data['source_url'] ?? null),
            'license' => $this->nullString($data['license'] ?? null),
            'status' => in_array(($data['status'] ?? 'draft'), ['draft', 'published', 'archived'], true) ? (string)$data['status'] : 'draft',
            'visibility' => in_array(($data['visibility'] ?? 'private'), ['private', 'shared', 'public'], true) ? (string)$data['visibility'] : 'private',
            'favorite' => (bool)($data['favorite'] ?? false),
            'exclude_from_planner' => (bool)($data['excludeFromPlanner'] ?? $data['exclude_from_planner'] ?? false),
            'servings' => max(1, (int)($data['servings'] ?? 1)),
            'yield_text' => $this->nullString($data['yieldText'] ?? $data['yield_text'] ?? null),
            'prep_time' => $prep,
            'rest_time' => $rest,
            'cook_time' => $cook,
            'total_time' => $total,
            'difficulty' => $this->nullString($data['difficulty'] ?? null),
            'cost_cents' => $this->nullableInt($data['costCents'] ?? $data['cost_cents'] ?? null),
            'currency' => $this->nullString($data['currency'] ?? null),
            'cuisine' => $this->nullString($data['cuisine'] ?? null),
            'meal_type' => $this->nullString($data['mealType'] ?? $data['meal_type'] ?? null),
            'cook_method' => $this->nullString($data['cookingMethod'] ?? $data['cook_method'] ?? null),
            'season' => $this->nullString($data['season'] ?? null),
            'calories' => $this->nullableInt($data['calories'] ?? null),
            'nutrition' => $this->encode($data['nutrition'] ?? []),
            'notes' => $this->nullString($data['notes'] ?? null),
            'image_path' => $this->nullString($data['imagePath'] ?? $data['image_path'] ?? null),
            'folder_path' => $this->nullString($data['folderPath'] ?? $data['folder_path'] ?? null),
            'cook_count' => max(0, (int)($data['cookCount'] ?? $data['cook_count'] ?? 0)),
            'last_cooked' => $this->nullableInt($data['lastCooked'] ?? $data['last_cooked'] ?? null),
            'revision' => max(1, (int)($data['revision'] ?? 1)),
            'created_at' => $isNew ? $now : (int)($data['createdAt'] ?? $data['created_at'] ?? $now),
            'updated_at' => $now,
        ];
    }

    /** @param array<string, mixed> $data */
    private function syncChildren(int $recipeId, string $userId, array $data, int $now): void {
        $this->taxonomy->syncIngredients($recipeId, $userId, is_array($data['ingredients'] ?? null) ? $data['ingredients'] : []);
        $this->taxonomy->syncTools($recipeId, $userId, is_array($data['tools'] ?? null) ? $data['tools'] : []);
        $this->taxonomy->syncTags($recipeId, $userId, is_array($data['tags'] ?? null) ? $data['tags'] : []);
        $this->taxonomy->syncCategories($recipeId, $userId, is_array($data['categories'] ?? null) ? $data['categories'] : []);
        $this->saveSteps($recipeId, is_array($data['steps'] ?? null) ? $data['steps'] : [], $now);
        $this->saveMedia($recipeId, is_array($data['media'] ?? null) ? $data['media'] : [], $now);
    }

    /** @param list<array<string, mixed>> $steps */
    private function saveSteps(int $recipeId, array $steps, int $now): void {
        $this->deleteBy('smartcook_steps', 'recipe_id', $recipeId);
        foreach (array_values($steps) as $index => $step) {
            $text = trim((string)($step['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $this->insert('smartcook_steps', [
                'recipe_id' => $recipeId,
                'sort_order' => (int)($step['sortOrder'] ?? $index),
                'text' => $text,
                'timer_secs' => $this->nullableInt($step['timerSeconds'] ?? $step['timer_secs'] ?? null),
                'temperature' => isset($step['temperature']) && $step['temperature'] !== '' ? (string)$step['temperature'] : null,
                'temp_unit' => $this->nullString($step['temperatureUnit'] ?? $step['temp_unit'] ?? null),
                'image_path' => $this->nullString($step['imagePath'] ?? $step['image_path'] ?? null),
                'notes' => $this->nullString($step['notes'] ?? null),
                'ingredient_ids' => $this->encode($step['ingredientIds'] ?? []),
                'tool_ids' => $this->encode($step['toolIds'] ?? []),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    /** @return list<array<string, mixed>> */
    private function getSteps(int $recipeId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('smartcook_steps')
            ->where($qb->expr()->eq('recipe_id', $qb->createNamedParameter($recipeId, IQueryBuilder::PARAM_INT)))
            ->orderBy('sort_order', 'ASC');
        return array_map(fn (array $row): array => [
            'id' => (int)$row['id'],
            'sortOrder' => (int)$row['sort_order'],
            'text' => (string)$row['text'],
            'timerSeconds' => $row['timer_secs'] !== null ? (int)$row['timer_secs'] : null,
            'temperature' => $row['temperature'] !== null ? (float)$row['temperature'] : null,
            'temperatureUnit' => $row['temp_unit'],
            'imagePath' => $row['image_path'],
            'notes' => $row['notes'],
            'ingredientIds' => $this->decode($row['ingredient_ids'], []),
            'toolIds' => $this->decode($row['tool_ids'], []),
        ], $this->fetchAll($qb));
    }

    /** @param list<array<string, mixed>> $media */
    private function saveMedia(int $recipeId, array $media, int $now): void {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')->from('smartcook_media')
            ->where($qb->expr()->eq('recipe_id', $qb->createNamedParameter($recipeId, IQueryBuilder::PARAM_INT)));
        $existingIds = array_map(static fn (array $row): int => (int)$row['id'], $this->fetchAll($qb));
        $existing = array_fill_keys($existingIds, true);
        $kept = [];

        foreach (array_values($media) as $index => $item) {
            $path = trim((string)($item['path'] ?? ''));
            if ($path === '') {
                continue;
            }
            $row = [
                'step_id' => $this->nullableInt($item['stepId'] ?? null),
                'kind' => in_array(($item['kind'] ?? 'attachment'), ['image', 'video', 'pdf', 'attachment'], true) ? (string)$item['kind'] : 'attachment',
                'path' => $path,
                'mime' => $this->nullString($item['mime'] ?? null),
                'alt_text' => $this->nullString($item['altText'] ?? null),
                'file_size' => $this->nullableInt($item['fileSize'] ?? null),
                'sort_order' => (int)($item['sortOrder'] ?? $index),
            ];
            $mediaId = (int)($item['id'] ?? 0);
            if ($mediaId > 0 && isset($existing[$mediaId])) {
                $this->update('smartcook_media', $mediaId, $row);
                $kept[$mediaId] = true;
                continue;
            }
            $row['recipe_id'] = $recipeId;
            $row['created_at'] = $now;
            $kept[$this->insert('smartcook_media', $row)] = true;
        }

        foreach ($existingIds as $mediaId) {
            if (!isset($kept[$mediaId])) {
                $this->deleteBy('smartcook_media', 'id', $mediaId);
            }
        }
    }

    /** @return list<array<string, mixed>> */
    private function getMedia(int $recipeId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('smartcook_media')
            ->where($qb->expr()->eq('recipe_id', $qb->createNamedParameter($recipeId, IQueryBuilder::PARAM_INT)))
            ->orderBy('sort_order', 'ASC');
        return array_map(static fn (array $row): array => [
            'id' => (int)$row['id'],
            'stepId' => $row['step_id'] !== null ? (int)$row['step_id'] : null,
            'kind' => (string)$row['kind'],
            'path' => (string)$row['path'],
            'mime' => $row['mime'],
            'altText' => $row['alt_text'],
            'fileSize' => $row['file_size'] !== null ? (int)$row['file_size'] : null,
            'createdAt' => (int)$row['created_at'],
            'sortOrder' => (int)$row['sort_order'],
        ], $this->fetchAll($qb));
    }

    private function saveVersion(int $recipeId, int $revision, string $userId, array $recipe, int $now): void {
        $this->insert('smartcook_versions', [
            'recipe_id' => $recipeId,
            'revision' => $revision,
            'user_id' => $userId,
            'snapshot' => $this->encode($recipe),
            'created_at' => $now,
        ]);
    }

    /** @return array<string, mixed> */
    private function mapRecipeRow(array $row): array {
        return [
            'id' => (int)$row['id'],
            'uuid' => (string)$row['uuid'],
            'ownerId' => (string)$row['user_id'],
            'title' => (string)$row['title'],
            'subtitle' => $row['subtitle'],
            'description' => $row['description'],
            'language' => (string)$row['language'],
            'author' => $row['author'],
            'sourceName' => $row['source_name'],
            'sourceUrl' => $row['source_url'],
            'license' => $row['license'],
            'status' => (string)$row['status'],
            'visibility' => (string)$row['visibility'],
            'favorite' => (bool)$row['favorite'],
            'excludeFromPlanner' => (bool)$row['exclude_from_planner'],
            'servings' => (int)$row['servings'],
            'yieldText' => $row['yield_text'],
            'prepTime' => (int)$row['prep_time'],
            'restTime' => (int)$row['rest_time'],
            'cookTime' => (int)$row['cook_time'],
            'totalTime' => (int)$row['total_time'],
            'difficulty' => $row['difficulty'],
            'costCents' => $row['cost_cents'] !== null ? (int)$row['cost_cents'] : null,
            'currency' => $row['currency'],
            'cuisine' => $row['cuisine'],
            'mealType' => $row['meal_type'],
            'cookingMethod' => $row['cook_method'],
            'season' => $row['season'],
            'calories' => $row['calories'] !== null ? (int)$row['calories'] : null,
            'nutrition' => $this->decode($row['nutrition'], []),
            'notes' => $row['notes'],
            'imagePath' => $row['image_path'],
            'folderPath' => $row['folder_path'],
            'cookCount' => (int)$row['cook_count'],
            'lastCooked' => $row['last_cooked'] !== null ? (int)$row['last_cooked'] : null,
            'revision' => (int)$row['revision'],
            'createdAt' => (int)$row['created_at'],
            'updatedAt' => (int)$row['updated_at'],
        ];
    }

    /** @return list<string> */
    private function normalizeFilterList(mixed $values): array {
        if (is_string($values)) {
            $values = preg_split('/[,;]+/', $values) ?: [];
        }
        if (!is_array($values)) {
            return [];
        }
        return array_values(array_unique(array_filter(array_map(
            static fn ($value): string => mb_strtolower(trim((string)(is_array($value) ? ($value['name'] ?? '') : $value))),
            $values,
        ))));
    }

    /** @param list<array<string, mixed>> $entities @param list<string> $needles */
    private function containsAll(array $entities, array $needles): bool {
        $names = array_map(static fn (array $entity): string => mb_strtolower((string)($entity['name'] ?? '')), $entities);
        foreach ($needles as $needle) {
            $found = false;
            foreach ($names as $name) {
                if (str_contains($name, $needle)) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                return false;
            }
        }
        return true;
    }

    /** @param list<array<string, mixed>> $entities @param list<string> $needles */
    private function containsAny(array $entities, array $needles): bool {
        $names = array_map(static fn (array $entity): string => mb_strtolower((string)($entity['name'] ?? '')), $entities);
        foreach ($needles as $needle) {
            foreach ($names as $name) {
                if (str_contains($name, $needle)) {
                    return true;
                }
            }
        }
        return false;
    }

    private function uuid(): string {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }

    private function nullString(mixed $value): ?string {
        if ($value === null) {
            return null;
        }
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }

    private function nullableInt(mixed $value): ?int {
        return $value === null || $value === '' ? null : (int)$value;
    }
}
