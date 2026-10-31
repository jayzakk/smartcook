<?php

declare(strict_types=1);

namespace OCA\SmartCook\Db;

use OCA\SmartCook\Service\TextNormalizer;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;

final class TaxonomyRepository extends AbstractRepository {
    private const NAMED_KINDS = [
        'tags' => ['smartcook_tags', 'smartcook_r_tags', 'tag_id'],
        'categories' => ['smartcook_cats', 'smartcook_r_cats', 'category_id'],
        'tools' => ['smartcook_tools', 'smartcook_r_tools', 'tool_id'],
        'ingredients' => ['smartcook_ingr', 'smartcook_r_ingr', 'ingredient_id'],
    ];
    private const VALUE_KINDS = [
        'cuisine' => 'cuisine', 'mealType' => 'meal_type',
        'cookingMethod' => 'cook_method', 'season' => 'season',
    ];
    public function __construct(IDBConnection $db, private TextNormalizer $normalizer) {
        parent::__construct($db);
    }

    /** @param list<array<string, mixed>> $ingredients */
    public function syncIngredients(int $recipeId, string $userId, array $ingredients): void {
        $this->deleteBy('smartcook_r_ingr', 'recipe_id', $recipeId);
        $now = time();

        foreach (array_values($ingredients) as $index => $ingredient) {
            $name = trim((string)($ingredient['name'] ?? ''));
            $original = trim((string)($ingredient['originalText'] ?? $ingredient['original_text'] ?? ''));
            if ($name === '' && $original !== '') {
                $name = $original;
            }
            if ($name === '') {
                continue;
            }

            $ingredientId = $this->findOrCreateIngredient($userId, [
                'name' => $name,
                'category' => $ingredient['category'] ?? null,
                'allergens' => $ingredient['allergens'] ?? [],
                'substitutes' => $ingredient['substitutes'] ?? [],
                'created_at' => $now,
            ]);

            $quantity = isset($ingredient['quantity']) ? trim((string)$ingredient['quantity']) : null;
            $amount = $ingredient['amount'] ?? $this->normalizer->parseQuantity($quantity);
            $unit = $this->normalizer->normalizeUnit(isset($ingredient['unit']) ? (string)$ingredient['unit'] : null);

            $this->insert('smartcook_r_ingr', [
                'recipe_id' => $recipeId,
                'ingredient_id' => $ingredientId,
                'original_text' => $original !== '' ? $original : null,
                'quantity' => $quantity !== '' ? $quantity : null,
                'amount' => $amount !== null ? (string)$amount : null,
                'unit' => $unit,
                'notes' => $this->nullString($ingredient['notes'] ?? null),
                'optional' => (bool)($ingredient['optional'] ?? false),
				'alternatives' => $this->encode($ingredient['alternatives'] ?? []),
                'sort_order' => (int)($ingredient['sortOrder'] ?? $ingredient['sort_order'] ?? $index),
                'group_name' => $this->nullString($ingredient['group'] ?? $ingredient['groupName'] ?? null),
            ]);
        }
    }

    /** @return list<array<string, mixed>> */
    public function getIngredients(int $recipeId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select(
            'ri.id', 'ri.ingredient_id', 'ri.original_text', 'ri.quantity', 'ri.amount', 'ri.unit',
			'ri.notes', 'ri.optional', 'ri.alternatives', 'ri.sort_order', 'ri.group_name',
            'i.name', 'i.norm_name', 'i.category', 'i.allergens', 'i.substitutes'
        )
            ->from('smartcook_r_ingr', 'ri')
            ->leftJoin('ri', 'smartcook_ingr', 'i', $qb->expr()->eq('ri.ingredient_id', 'i.id'))
            ->where($qb->expr()->eq('ri.recipe_id', $qb->createNamedParameter($recipeId, IQueryBuilder::PARAM_INT)))
            ->orderBy('ri.sort_order', 'ASC');

        return array_map(function (array $row): array {
            return [
                'id' => (int)$row['id'],
                'ingredientId' => $row['ingredient_id'] !== null ? (int)$row['ingredient_id'] : null,
                'name' => (string)($row['name'] ?? ''),
                'normalizedName' => (string)($row['norm_name'] ?? ''),
                'originalText' => $row['original_text'],
                'quantity' => $row['quantity'],
                'amount' => $row['amount'] !== null ? (float)$row['amount'] : null,
                'unit' => $row['unit'],
                'notes' => $row['notes'],
                'optional' => (bool)$row['optional'],
				'alternatives' => $this->decode($row['alternatives'], []),
                'sortOrder' => (int)$row['sort_order'],
                'group' => $row['group_name'],
                'category' => $row['category'],
                'allergens' => $this->decode($row['allergens'], []),
                'substitutes' => $this->decode($row['substitutes'], []),
            ];
        }, $this->fetchAll($qb));
    }

    /** @param list<string|array<string, mixed>> $tools */
    public function syncTools(int $recipeId, string $userId, array $tools): void {
        $this->syncNamedRelation('smartcook_tools', 'smartcook_r_tools', 'tool_id', $recipeId, $userId, $tools);
    }

    /** @param list<string|array<string, mixed>> $tags */
    public function syncTags(int $recipeId, string $userId, array $tags): void {
        $this->syncNamedRelation('smartcook_tags', 'smartcook_r_tags', 'tag_id', $recipeId, $userId, $tags);
    }

    /** @param list<string|array<string, mixed>> $categories */
    public function syncCategories(int $recipeId, string $userId, array $categories): void {
        $this->syncNamedRelation('smartcook_cats', 'smartcook_r_cats', 'category_id', $recipeId, $userId, $categories);
    }

    /** @return list<array<string, mixed>> */
    public function getTools(int $recipeId): array {
        return $this->getNamedRelation('smartcook_tools', 'smartcook_r_tools', 'tool_id', $recipeId);
    }

    /** @return list<array<string, mixed>> */
    public function getTags(int $recipeId): array {
        return $this->getNamedRelation('smartcook_tags', 'smartcook_r_tags', 'tag_id', $recipeId);
    }

    /** @return list<array<string, mixed>> */
    public function getCategories(int $recipeId): array {
        return $this->getNamedRelation('smartcook_cats', 'smartcook_r_cats', 'category_id', $recipeId);
    }

    /** @return array<string, list<array<string, mixed>>> */
    public function listForUser(string $userId): array {
        $items = [
            'ingredients' => $this->listNamedTable('smartcook_ingr', $userId),
            'tools' => $this->listNamedTable('smartcook_tools', $userId),
            'tags' => $this->listNamedTable('smartcook_tags', $userId),
            'categories' => $this->listNamedTable('smartcook_cats', $userId),
        ];
        foreach (self::VALUE_KINDS as $kind => $column) {
            $items[$kind] = $this->listValueKind($userId, $kind, $column);
        }
        return $items;
    }

    /**
     * Keeps only classifications already present in the user's vocabulary and
     * returns their stored spelling. This is intentionally strict: an AI
     * suggestion must be an existing label, while manual import review can
     * still add a genuinely missing value.
     *
     * @param array<string, mixed> $recipe
     * @param array<string, mixed> $taxonomy
     * @return array<string, mixed>
     */
    public function restrictRecipeClassifications(array $recipe, array $taxonomy): array {
        foreach (['tags', 'categories', 'cuisine', 'mealType', 'cookingMethod'] as $field) {
            $available = [];
            foreach ((array)($taxonomy[$field] ?? []) as $item) {
                $name = trim((string)(is_array($item) ? ($item['name'] ?? '') : $item));
                $normalized = $this->normalizer->normalizeName($name);
                if ($normalized !== '') {
                    $available[$normalized] = $name;
                }
            }
            if (in_array($field, ['tags', 'categories'], true)) {
                $items = [];
                foreach ((array)($recipe[$field] ?? []) as $item) {
                    $name = trim((string)(is_array($item) ? ($item['name'] ?? '') : $item));
                    $normalized = $this->normalizer->normalizeName($name);
                    if ($normalized !== '' && isset($available[$normalized])) {
                        $items[$normalized] = ['name' => $available[$normalized]];
                    }
                }
                $recipe[$field] = array_values($items);
                continue;
            }
            $name = trim((string)($recipe[$field] ?? ''));
            $normalized = $this->normalizer->normalizeName($name);
            $recipe[$field] = $normalized !== '' && isset($available[$normalized]) ? $available[$normalized] : null;
        }
        return $recipe;
    }

    /** @param array<string, mixed> $recipe @param array<string, mixed> $taxonomy @return array<string, mixed> */
    public function canonicalizeRecipeClassifications(array $recipe, array $taxonomy): array {
        foreach (['tags', 'categories', 'cuisine', 'mealType', 'cookingMethod'] as $field) {
            $available = [];
            foreach ((array)($taxonomy[$field] ?? []) as $item) {
                $name = trim((string)(is_array($item) ? ($item['name'] ?? '') : $item));
                $normalized = $this->normalizer->normalizeName($name);
                if ($normalized !== '') {
                    $available[$normalized] = $name;
                    $available[$this->classificationKey($normalized)] = $name;
                }
            }
            $canonical = fn (string $name): string => $available[$this->normalizer->normalizeName($name)] ?? $available[$this->classificationKey($this->normalizer->normalizeName($name))] ?? $name;
            if (in_array($field, ['tags', 'categories'], true)) {
                $recipe[$field] = array_map(static fn (mixed $item): array => ['name' => $canonical(trim((string)(is_array($item) ? ($item['name'] ?? '') : $item)))], (array)($recipe[$field] ?? []));
            } else {
                $value = trim((string)($recipe[$field] ?? ''));
                $recipe[$field] = $value === '' ? null : $canonical($value);
            }
        }
        return $recipe;
    }

    private function classificationKey(string $normalized): string {
        return match ($normalized) {
            'forno', 'al forno', 'baked', 'oven baked' => 'al forno',
            'gluten free', 'senza glutine', 'celiaci', 'celiaco' => 'senza glutine',
            default => $normalized,
        };
    }

    /** @return array<string, list<array<string, mixed>>> */
    public function listManagedForUser(string $userId): array {
        $items = $this->listForUser($userId);
        foreach (self::NAMED_KINDS as $kind => $_) {
            $items[$kind] = array_map(function (array $item) use ($kind, $userId): array {
                $item['usageCount'] = $this->namedUsageCount($kind, (int)$item['id'], $userId);
                return $item;
            }, $items[$kind]);
        }
        return $items;
    }

    /** @return array<string, mixed> */
    public function updateIngredient(string $userId, int $id, ?string $category, array $substitutes): array {
        $qb = $this->db->getQueryBuilder();
        $qb->update('smartcook_ingr')
            ->set('category', $qb->createNamedParameter($this->nullString($category)))
            ->set('substitutes', $qb->createNamedParameter($this->encode($substitutes)))
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id)))
            ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
        $qb->executeStatement();
        $row = $this->requireNamedForUser('smartcook_ingr', $userId, $id);
        return $this->mapNamed($row);
    }

    /** @return array<string, mixed> */
    public function addManaged(string $userId, string $kind, string $name): array {
        $name = $this->normalizer->capitalizeLabel($name);
        $normalized = $this->normalizer->normalizeName($name);
        if ($name === '' || $normalized === '') {
            throw new \InvalidArgumentException('A name is required');
        }
        if (isset(self::NAMED_KINDS[$kind])) {
            [$table] = self::NAMED_KINDS[$kind];
            $row = $this->findNamed($table, $userId, $normalized);
            if ($row === null) {
                $id = $this->insert($table, $this->namedEntityData($table, $userId, $name, $normalized, []));
                $row = ['id' => $id, 'name' => $name, 'norm_name' => $normalized];
            }
            return $this->mapNamed($row);
        }
        if (!isset(self::VALUE_KINDS[$kind])) {
            throw new \InvalidArgumentException('Unknown taxonomy type');
        }
        $row = $this->findValue($userId, $kind, $normalized);
        if ($row === null) {
            $id = $this->insert('smartcook_taxonomy', ['user_id' => $userId, 'kind' => $kind, 'name' => $name, 'norm_name' => $normalized, 'created_at' => time()]);
            $row = ['id' => $id, 'name' => $name, 'norm_name' => $normalized];
        }
        return ['id' => (int)$row['id'], 'name' => (string)$row['name'], 'normalizedName' => (string)$row['norm_name']];
    }

    /** @param list<int> $recipeIds */
    public function applyManagedToRecipes(string $userId, string $kind, int $id, array $recipeIds): int {
        $recipeIds = $this->selectedRecipeIdsForUser($userId, $recipeIds);
        if (isset(self::NAMED_KINDS[$kind])) {
            [$table, $relationTable, $relationColumn] = self::NAMED_KINDS[$kind];
            $this->requireNamedForUser($table, $userId, $id);
            $added = 0;
            foreach ($recipeIds as $recipeId) {
                if (!$this->relationExists($relationTable, $relationColumn, $recipeId, $id)) {
                    $this->insert($relationTable, ['recipe_id' => $recipeId, $relationColumn => $id]);
                    ++$added;
                }
            }
            return $added;
        }
        $column = self::VALUE_KINDS[$kind] ?? null;
        if ($column === null) {
            throw new \InvalidArgumentException('Unknown taxonomy type');
        }
        $item = $this->requireValueForUser($userId, $kind, $id);
        return $this->setRecipeValueForRecipes($recipeIds, $column, (string)$item['name']);
    }

    /** @param list<int> $recipeIds */
    public function removeManagedFromRecipes(string $userId, string $kind, int $id, array $recipeIds): int {
        $recipeIds = $this->selectedRecipeIdsForUser($userId, $recipeIds);
        if (isset(self::NAMED_KINDS[$kind])) {
            [$table, $relationTable, $relationColumn] = self::NAMED_KINDS[$kind];
            $this->requireNamedForUser($table, $userId, $id);
            return $this->deleteNamedRelationsForRecipes($recipeIds, $relationTable, $relationColumn, $id);
        }
        $column = self::VALUE_KINDS[$kind] ?? null;
        if ($column === null) {
            throw new \InvalidArgumentException('Unknown taxonomy type');
        }
        $item = $this->requireValueForUser($userId, $kind, $id);
        return $this->clearRecipeValueForRecipes($recipeIds, $column, (string)$item['norm_name']);
    }

    public function deleteManaged(string $userId, string $kind, int $id): int {
        $removed = $this->removeManagedFromRecipes($userId, $kind, $id, $this->recipeIdsForUser($userId));
        if (isset(self::NAMED_KINDS[$kind])) {
            [$table] = self::NAMED_KINDS[$kind];
            $this->deleteBy($table, 'id', $id);
        } else {
            $this->deleteBy('smartcook_taxonomy', 'id', $id);
        }
        return $removed;
    }

    public function deleteRecipeRelations(int $recipeId): void {
        foreach (['smartcook_r_ingr', 'smartcook_r_tools', 'smartcook_r_tags', 'smartcook_r_cats'] as $table) {
            $this->deleteBy($table, 'recipe_id', $recipeId);
        }
    }

    /** @param array<string, mixed> $ingredient */
    private function findOrCreateIngredient(string $userId, array $ingredient): int {
        $name = trim((string)$ingredient['name']);
        $normalized = $this->normalizer->normalizeName($name);
        $existing = $this->findNamed('smartcook_ingr', $userId, $normalized);
        if ($existing !== null) {
            return (int)$existing['id'];
        }

        return $this->insert('smartcook_ingr', [
            'user_id' => $userId,
            'name' => $name,
            'norm_name' => $normalized,
            'category' => $this->nullString($ingredient['category'] ?? null),
            'allergens' => $this->encode($ingredient['allergens'] ?? []),
            'substitutes' => $this->encode($ingredient['substitutes'] ?? []),
            'created_at' => (int)($ingredient['created_at'] ?? time()),
        ]);
    }

    /** @param list<string|array<string, mixed>> $items */
    private function syncNamedRelation(string $entityTable, string $relationTable, string $relationColumn, int $recipeId, string $userId, array $items): void {
        $this->deleteBy($relationTable, 'recipe_id', $recipeId);
        $seen = [];
        foreach ($items as $item) {
            $data = is_array($item) ? $item : ['name' => $item];
            $name = $this->normalizer->capitalizeLabel((string)($data['name'] ?? ''));
            $normalized = $this->normalizer->normalizeName($name);
            if ($name === '' || $normalized === '' || isset($seen[$normalized])) {
                continue;
            }
            $seen[$normalized] = true;
            $entity = $this->findNamed($entityTable, $userId, $normalized);
            $entityId = $entity !== null ? (int)$entity['id'] : $this->insert($entityTable, $this->namedEntityData($entityTable, $userId, $name, $normalized, $data));
            $this->insert($relationTable, [
                'recipe_id' => $recipeId,
                $relationColumn => $entityId,
            ]);
        }
    }

    /** @return array<string, mixed> */
    private function namedEntityData(string $table, string $userId, string $name, string $normalized, array $data): array {
        $row = [
            'user_id' => $userId,
            'name' => $name,
            'norm_name' => $normalized,
            'created_at' => time(),
        ];
        if ($table === 'smartcook_ingr') {
            $row['category'] = $this->nullString($data['category'] ?? null);
            $row['allergens'] = $this->encode($data['allergens'] ?? []);
            $row['substitutes'] = $this->encode($data['substitutes'] ?? []);
        } elseif ($table === 'smartcook_tools') {
            $row['category'] = $this->nullString($data['category'] ?? null);
        } else {
            $row['color'] = $this->nullString($data['color'] ?? null);
            $row['parent_id'] = isset($data['parentId']) ? (int)$data['parentId'] : null;
        }
        return $row;
    }

    private function findNamed(string $table, string $userId, string $normalized): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from($table)
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('norm_name', $qb->createNamedParameter($normalized)))
            ->setMaxResults(1);
        return $this->fetchOne($qb);
    }

    /** @return list<array<string, mixed>> */
    private function listValueKind(string $userId, string $kind, string $column): array {
        $values = [];
        $qb = $this->db->getQueryBuilder();
        $qb->select('id', 'name', 'norm_name')->from('smartcook_taxonomy')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('kind', $qb->createNamedParameter($kind)))
            ->orderBy('name', 'ASC');
        foreach ($this->fetchAll($qb) as $row) {
            $values[(string)$row['norm_name']] = ['id' => (int)$row['id'], 'name' => (string)$row['name'], 'normalizedName' => (string)$row['norm_name'], 'usageCount' => 0];
        }
        $qb = $this->db->getQueryBuilder();
        $qb->select($column)->from('smartcook_recipes')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->isNotNull($column));
        foreach ($this->fetchAll($qb) as $row) {
            $name = trim((string)$row[$column]);
            $normalized = $this->normalizer->normalizeName($name);
            if ($normalized === '') {
                continue;
            }
            if (!isset($values[$normalized])) {
                $item = $this->addManaged($userId, $kind, $name);
                $values[$normalized] = $item + ['usageCount' => 0];
            }
            ++$values[$normalized]['usageCount'];
        }
        uasort($values, static fn (array $left, array $right): int => strcasecmp((string)$left['name'], (string)$right['name']));
        return array_values($values);
    }

    private function namedUsageCount(string $kind, int $id, string $userId): int {
        [, $relationTable, $relationColumn] = self::NAMED_KINDS[$kind];
        $qb = $this->db->getQueryBuilder();
        $qb->select($qb->func()->count('*', 'count'))->from($relationTable, 'rel')
            ->innerJoin('rel', 'smartcook_recipes', 'recipe', $qb->expr()->eq('rel.recipe_id', 'recipe.id'))
            ->where($qb->expr()->eq('rel.' . $relationColumn, $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('recipe.user_id', $qb->createNamedParameter($userId)));
        return (int)($this->fetchOne($qb)['count'] ?? 0);
    }

    /** @return list<int> */
    private function recipeIdsForUser(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')->from('smartcook_recipes')->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)));
        return array_map(static fn (array $row): int => (int)$row['id'], $this->fetchAll($qb));
    }

    /** @return list<array<string, mixed>> */
    public function listRecipeChoicesForUser(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id', 'title', 'cuisine', 'meal_type', 'cook_method', 'season')->from('smartcook_recipes')
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->orderBy('title', 'ASC');
        return array_map(fn (array $row): array => [
            'id' => (int)$row['id'],
            'title' => (string)$row['title'],
            'cuisine' => $row['cuisine'],
            'mealType' => $row['meal_type'],
            'cookingMethod' => $row['cook_method'],
            'season' => $row['season'],
            'tools' => $this->getTools((int)$row['id']),
            'tags' => $this->getTags((int)$row['id']),
            'categories' => $this->getCategories((int)$row['id']),
        ], $this->fetchAll($qb));
    }

    /** @param list<int> $recipeIds @return list<int> */
    private function selectedRecipeIdsForUser(string $userId, array $recipeIds): array {
        $ids = array_values(array_unique(array_filter(array_map('intval', $recipeIds), static fn (int $id): bool => $id > 0)));
        if ($ids === []) {
            throw new \InvalidArgumentException('Select at least one recipe');
        }
        $owned = array_flip($this->recipeIdsForUser($userId));
        foreach ($ids as $id) {
            if (!isset($owned[$id])) {
                throw new \InvalidArgumentException('A selected recipe is not owned by the current user');
            }
        }
        return $ids;
    }

    private function relationExists(string $table, string $column, int $recipeId, int $entityId): bool {
        $qb = $this->db->getQueryBuilder();
        $qb->select('id')->from($table)
            ->where($qb->expr()->eq('recipe_id', $qb->createNamedParameter($recipeId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq($column, $qb->createNamedParameter($entityId, IQueryBuilder::PARAM_INT)))
            ->setMaxResults(1);
        return $this->fetchOne($qb) !== null;
    }

    /** @param list<int> $recipeIds */
    private function deleteNamedRelationsForRecipes(array $recipeIds, string $relationTable, string $relationColumn, int $entityId): int {
        $removed = 0;
        foreach ($recipeIds as $recipeId) {
            $qb = $this->db->getQueryBuilder();
            $removed += $qb->delete($relationTable)
                ->where($qb->expr()->eq('recipe_id', $qb->createNamedParameter($recipeId, IQueryBuilder::PARAM_INT)))
                ->andWhere($qb->expr()->eq($relationColumn, $qb->createNamedParameter($entityId, IQueryBuilder::PARAM_INT)))
                ->executeStatement();
        }
        return $removed;
    }

    /** @return array<string, mixed> */
    private function requireNamedForUser(string $table, string $userId, int $id): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from($table)->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))->setMaxResults(1);
        return $this->fetchOne($qb) ?? throw new \InvalidArgumentException('Taxonomy item not found');
    }

    /** @return array<string, mixed>|null */
    private function findValue(string $userId, string $kind, string $normalized): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('smartcook_taxonomy')->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('kind', $qb->createNamedParameter($kind)))
            ->andWhere($qb->expr()->eq('norm_name', $qb->createNamedParameter($normalized)))->setMaxResults(1);
        return $this->fetchOne($qb);
    }

    /** @return array<string, mixed> */
    private function requireValueForUser(string $userId, string $kind, int $id): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('smartcook_taxonomy')->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->andWhere($qb->expr()->eq('kind', $qb->createNamedParameter($kind)))->setMaxResults(1);
        return $this->fetchOne($qb) ?? throw new \InvalidArgumentException('Taxonomy item not found');
    }

    /** @param list<int> $recipeIds */
    private function setRecipeValueForRecipes(array $recipeIds, string $column, string $name): int {
        $changed = 0;
        foreach ($recipeIds as $recipeId) {
            $qb = $this->db->getQueryBuilder();
            $changed += $qb->update('smartcook_recipes')->set($column, $qb->createNamedParameter($name))
                ->set('updated_at', $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT))
                ->where($qb->expr()->eq('id', $qb->createNamedParameter($recipeId, IQueryBuilder::PARAM_INT)))->executeStatement();
        }
        return $changed;
    }

    /** @param list<int> $recipeIds */
    private function clearRecipeValueForRecipes(array $recipeIds, string $column, string $normalizedName): int {
        $removed = 0;
        foreach ($recipeIds as $recipeId) {
            $qb = $this->db->getQueryBuilder();
            $qb->select($column)->from('smartcook_recipes')->where($qb->expr()->eq('id', $qb->createNamedParameter($recipeId, IQueryBuilder::PARAM_INT)))->setMaxResults(1);
            $row = $this->fetchOne($qb);
            if ($row === null || $this->normalizer->normalizeName((string)$row[$column]) !== $normalizedName) {
                continue;
            }
            $qb = $this->db->getQueryBuilder();
            $removed += $qb->update('smartcook_recipes')->set($column, $qb->createNamedParameter(null, IQueryBuilder::PARAM_NULL))
                ->set('updated_at', $qb->createNamedParameter(time(), IQueryBuilder::PARAM_INT))
                ->where($qb->expr()->eq('id', $qb->createNamedParameter($recipeId, IQueryBuilder::PARAM_INT)))->executeStatement();
        }
        return $removed;
    }

    /** @return list<array<string, mixed>> */
    private function getNamedRelation(string $entityTable, string $relationTable, string $relationColumn, int $recipeId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('e.*')->from($relationTable, 'r')
            ->innerJoin('r', $entityTable, 'e', $qb->expr()->eq('r.' . $relationColumn, 'e.id'))
            ->where($qb->expr()->eq('r.recipe_id', $qb->createNamedParameter($recipeId, IQueryBuilder::PARAM_INT)))
            ->orderBy('e.name', 'ASC');
        return array_map(fn (array $row): array => $this->mapNamed($row), $this->fetchAll($qb));
    }

    /** @return list<array<string, mixed>> */
    private function listNamedTable(string $table, string $userId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from($table)
            ->where($qb->expr()->eq('user_id', $qb->createNamedParameter($userId)))
            ->orderBy('name', 'ASC');
        return array_map(fn (array $row): array => $this->mapNamed($row), $this->fetchAll($qb));
    }

    /** @return array<string, mixed> */
    private function mapNamed(array $row): array {
        $mapped = [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'normalizedName' => (string)$row['norm_name'],
        ];
        foreach (['category', 'color', 'parent_id'] as $key) {
            if (array_key_exists($key, $row)) {
                $target = $key === 'parent_id' ? 'parentId' : $key;
                $mapped[$target] = $key === 'parent_id' && $row[$key] !== null ? (int)$row[$key] : $row[$key];
            }
        }
        if (array_key_exists('allergens', $row)) {
            $mapped['allergens'] = $this->decode($row['allergens'], []);
            $mapped['substitutes'] = $this->decode($row['substitutes'], []);
        }
        return $mapped;
    }

    private function nullString(mixed $value): ?string {
        if ($value === null) {
            return null;
        }
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }
}
