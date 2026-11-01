<?php

declare(strict_types=1);

namespace OCA\SmartCook\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCA\SmartCook\Service\HouseholdService;
use OCP\IDBConnection;

final class ShoppingRepository extends AbstractRepository {
    public function __construct(IDBConnection $db, HouseholdService $household) {
        parent::__construct($db, $household);
    }

    /** @param list<array<string, mixed>> $items @param array<string, mixed> $source */
    public function createList(string $userId, string $name, array $items, array $source = []): array {
        $this->db->beginTransaction();
        try {
            $now = time();
            $listId = $this->insert('smartcook_shop_lists', [
                'user_id' => $userId,
                'name' => trim($name) ?: 'Shopping list',
                'status' => 'active',
                'source' => $this->encode($source),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
            foreach (array_values($items) as $index => $item) {
                $this->insert('smartcook_shop_items', [
                    'list_id' => $listId,
                    'name' => trim((string)$item['name']),
                    'norm_name' => trim((string)($item['normalizedName'] ?? mb_strtolower((string)$item['name']))),
                    'quantity' => isset($item['quantity']) ? (string)$item['quantity'] : null,
                    'amount' => isset($item['amount']) && $item['amount'] !== null ? (string)$item['amount'] : null,
                    'unit' => $this->nullString($item['unit'] ?? null),
                    'category' => $this->nullString($item['category'] ?? null),
                    'checked' => (bool)($item['checked'] ?? false),
                    'notes' => $this->nullString($item['notes'] ?? null),
                    'sort_order' => (int)($item['sortOrder'] ?? $index),
                ]);
            }
            $list = $this->getList($listId, $userId) ?? throw new \RuntimeException('List could not be reloaded');
            $this->db->commit();
            return $list;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** @return list<array<string, mixed>> */
    public function listLists(string $userId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('smartcook_shop_lists')
            ->where($this->ownedBy($qb, 'user_id', $userId))
            ->orderBy('updated_at', 'DESC');
        $rows = $this->fetchAll($qb);
        $counts = $this->itemCounts(array_map(fn (array $row): int => (int)$row['id'], $rows));
        return array_map(function (array $row) use ($counts): array {
            $list = $this->mapList($row, false);
            $summary = $counts[$list['id']] ?? ['total' => 0, 'checked' => 0];
            $list['itemCount'] = $summary['total'];
            $list['checkedCount'] = $summary['checked'];
            return $list;
        }, $rows);
    }

    /** @param list<int> $listIds @return array<int, array{total:int, checked:int}> */
    private function itemCounts(array $listIds): array {
        if ($listIds === []) {
            return [];
        }
        $qb = $this->db->getQueryBuilder();
        $qb->select('list_id', 'checked')->from('smartcook_shop_items')
            ->where($qb->expr()->in('list_id', $qb->createNamedParameter($listIds, IQueryBuilder::PARAM_INT_ARRAY)));
        $counts = [];
        foreach ($this->fetchAll($qb) as $row) {
            $listId = (int)$row['list_id'];
            $counts[$listId] ??= ['total' => 0, 'checked' => 0];
            $counts[$listId]['total']++;
            if ((bool)$row['checked']) {
                $counts[$listId]['checked']++;
            }
        }
        return $counts;
    }

    public function getList(int $id, string $userId): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('smartcook_shop_lists')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->andWhere($this->ownedBy($qb, 'user_id', $userId))
            ->setMaxResults(1);
        $row = $this->fetchOne($qb);
        if ($row === null) {
            return null;
        }
        $list = $this->mapList($row, true);
        $list['items'] = $this->getItems($id);
        return $list;
    }

    /** @param array<string, mixed> $data */
    public function updateItem(int $listId, int $itemId, string $userId, array $data): array {
        if ($this->getList($listId, $userId) === null) {
            throw new \RuntimeException('Shopping list not found');
        }
        $existing = $this->findItem($listId, $itemId) ?? throw new \RuntimeException('Shopping item not found');
        $this->update('smartcook_shop_items', $itemId, [
            'name' => trim((string)($data['name'] ?? $existing['name'])),
            'norm_name' => trim((string)($data['normalizedName'] ?? $existing['normalizedName'])),
            'quantity' => $this->nullString($data['quantity'] ?? $existing['quantity']),
            'amount' => isset($data['amount']) ? ($data['amount'] !== null ? (string)$data['amount'] : null) : ($existing['amount'] !== null ? (string)$existing['amount'] : null),
            'unit' => $this->nullString($data['unit'] ?? $existing['unit']),
            'category' => $this->nullString($data['category'] ?? $existing['category']),
            'checked' => (bool)($data['checked'] ?? $existing['checked']),
            'notes' => $this->nullString($data['notes'] ?? $existing['notes']),
            'sort_order' => (int)($data['sortOrder'] ?? $existing['sortOrder']),
        ]);
        $this->update('smartcook_shop_lists', $listId, ['updated_at' => time()]);
        return $this->findItem($listId, $itemId) ?? throw new \RuntimeException('Shopping item could not be reloaded');
    }

    public function addItem(int $listId, string $userId, array $data): array {
        $list = $this->getList($listId, $userId) ?? throw new \RuntimeException('Shopping list not found');
        $id = $this->insert('smartcook_shop_items', [
            'list_id' => $listId,
            'name' => trim((string)($data['name'] ?? 'New item')),
            'norm_name' => trim((string)($data['normalizedName'] ?? mb_strtolower((string)($data['name'] ?? 'New item')))),
            'quantity' => $this->nullString($data['quantity'] ?? null),
            'amount' => isset($data['amount']) && $data['amount'] !== null ? (string)$data['amount'] : null,
            'unit' => $this->nullString($data['unit'] ?? null),
            'category' => $this->nullString($data['category'] ?? null),
            'checked' => false,
            'notes' => $this->nullString($data['notes'] ?? null),
            'sort_order' => count($list['items']),
        ]);
        $this->update('smartcook_shop_lists', $listId, ['updated_at' => time()]);
        return $this->findItem($listId, $id) ?? throw new \RuntimeException('Shopping item could not be reloaded');
    }

    /** @param list<array<string, mixed>> $items */
    public function addItems(int $listId, string $userId, array $items): array {
        $list = $this->getList($listId, $userId) ?? throw new \RuntimeException('Shopping list not found');
        $this->db->beginTransaction();
        try {
            $existingByKey = [];
            foreach ($list['items'] as $existing) {
                $key = $existing['normalizedName'] . '|' . ($existing['unit'] ?? '');
                $existingByKey[$key][] = $existing;
            }
            foreach (array_values($items) as $index => $item) {
                $key = trim((string)($item['normalizedName'] ?? $item['name'])) . '|' . ($item['unit'] ?? '');
                $matches = $existingByKey[$key] ?? [];
                if ($matches !== []) {
                    $merged = array_shift($matches);
                    foreach ($matches as $duplicate) {
                        $merged = $this->mergeItemQuantities($merged, $duplicate);
                        $this->deleteBy('smartcook_shop_items', 'id', (int)$duplicate['id']);
                    }
                    $merged = $this->mergeItemQuantities($merged, $item);
                    $this->update('smartcook_shop_items', (int)$merged['id'], [
                        'quantity' => $merged['quantity'],
                        'amount' => $merged['amount'],
                        'checked' => false,
                    ]);
                    $existingByKey[$key] = [$merged];
                    continue;
                }
                $this->insert('smartcook_shop_items', [
                    'list_id' => $listId,
                    'name' => trim((string)$item['name']),
                    'norm_name' => trim((string)($item['normalizedName'] ?? mb_strtolower((string)$item['name']))),
                    'quantity' => $this->nullString($item['quantity'] ?? null),
                    'amount' => isset($item['amount']) && $item['amount'] !== null ? (string)$item['amount'] : null,
                    'unit' => $this->nullString($item['unit'] ?? null),
                    'category' => $this->nullString($item['category'] ?? null),
                    'checked' => false,
                    'notes' => $this->nullString($item['notes'] ?? null),
                    'sort_order' => count($list['items']) + $index,
                ]);
            }
            $this->update('smartcook_shop_lists', $listId, ['updated_at' => time()]);
            $updated = $this->getList($listId, $userId) ?? throw new \RuntimeException('Shopping list could not be reloaded');
            $this->db->commit();
            return $updated;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** @param array<string, mixed> $existing @param array<string, mixed> $incoming @return array<string, mixed> */
    private function mergeItemQuantities(array $existing, array $incoming): array {
        $existingAmount = $existing['amount'] ?? null;
        $incomingAmount = $incoming['amount'] ?? null;
        if ($existingAmount !== null && $incomingAmount !== null) {
            $amount = round((float)$existingAmount + (float)$incomingAmount, 4);
            $existing['amount'] = $amount;
            $existing['quantity'] = rtrim(rtrim(number_format($amount, 4, '.', ''), '0'), '.');
            return $existing;
        }
        $existing['amount'] = null;
        $existing['quantity'] = $this->nullString(trim((string)($existing['quantity'] ?? '') . ' + ' . (string)($incoming['quantity'] ?? ''), ' +'));
        return $existing;
    }

    public function deleteList(int $id, string $userId): void {
        if ($this->getList($id, $userId) === null) {
            return;
        }
        $this->db->beginTransaction();
        try {
            $this->deleteBy('smartcook_shop_items', 'list_id', $id);
            $this->deleteBy('smartcook_shop_lists', 'id', $id);
            $this->db->commit();
        } catch (\Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    /** @return list<array<string, mixed>> */
    private function getItems(int $listId): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('smartcook_shop_items')
            ->where($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)))
            ->orderBy('sort_order', 'ASC');
        return array_map(fn (array $row): array => $this->mapItem($row), $this->fetchAll($qb));
    }

    private function findItem(int $listId, int $itemId): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('smartcook_shop_items')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($itemId, IQueryBuilder::PARAM_INT)))
            ->andWhere($qb->expr()->eq('list_id', $qb->createNamedParameter($listId, IQueryBuilder::PARAM_INT)))
            ->setMaxResults(1);
        $row = $this->fetchOne($qb);
        return $row === null ? null : $this->mapItem($row);
    }

    /** @return array<string, mixed> */
    private function mapList(array $row, bool $decodeSource): array {
        return [
            'id' => (int)$row['id'],
            'name' => (string)$row['name'],
            'status' => (string)$row['status'],
            'source' => $decodeSource ? $this->decode($row['source'], []) : null,
            'createdAt' => (int)$row['created_at'],
            'updatedAt' => (int)$row['updated_at'],
        ];
    }

    /** @return array<string, mixed> */
    private function mapItem(array $row): array {
        return [
            'id' => (int)$row['id'],
            'listId' => (int)$row['list_id'],
            'name' => (string)$row['name'],
            'normalizedName' => (string)$row['norm_name'],
            'quantity' => $row['quantity'],
            'amount' => $row['amount'] !== null ? (float)$row['amount'] : null,
            'unit' => $row['unit'],
            'category' => $row['category'],
            'checked' => (bool)$row['checked'],
            'notes' => $row['notes'],
            'sortOrder' => (int)$row['sort_order'],
        ];
    }

    private function nullString(mixed $value): ?string {
        if ($value === null) {
            return null;
        }
        $value = trim((string)$value);
        return $value === '' ? null : $value;
    }
}
