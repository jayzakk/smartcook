<?php

declare(strict_types=1);

namespace OCA\SmartCook\Db;

use OCP\DB\QueryBuilder\IQueryBuilder;
use OCA\SmartCook\Service\HouseholdService;
use OCP\IDBConnection;

final class ImportRepository extends AbstractRepository {
    public function __construct(IDBConnection $db, HouseholdService $household) {
        parent::__construct($db, $household);
    }

    public function createJob(string $userId, string $kind, ?string $sourceRef, bool $useAi, ?string $provider, array $payload): array {
        $now = time();
        $id = $this->insert('smartcook_imports', [
            'user_id' => $userId,
            'kind' => $kind,
            'source_ref' => $sourceRef,
            'status' => 'queued',
            'use_ai' => $useAi,
            'provider' => $provider,
            'payload' => $this->encode($payload),
            'result' => null,
            'error' => null,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
        return $this->getJob($id, $userId) ?? throw new \RuntimeException('Import job could not be reloaded');
    }

    /** @return list<array<string, mixed>> */
    public function listForUser(string $userId, int $limit = 50): array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('smartcook_imports')
            ->where($this->ownedBy($qb, 'user_id', $userId))
            ->orderBy('created_at', 'DESC')
            ->setMaxResults(max(1, min(200, $limit)));
        return array_map(fn (array $row): array => $this->map($row), $this->fetchAll($qb));
    }

    public function getJob(int $id, ?string $userId = null): ?array {
        $qb = $this->db->getQueryBuilder();
        $qb->select('*')->from('smartcook_imports')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)));
        if ($userId !== null) {
            $qb->andWhere($this->ownedBy($qb, 'user_id', $userId));
        }
        $qb->setMaxResults(1);
        $row = $this->fetchOne($qb);
        return $row === null ? null : $this->map($row);
    }

    public function markRunning(int $id): void {
        $this->update('smartcook_imports', $id, ['status' => 'running', 'updated_at' => time(), 'error' => null]);
    }

    public function markComplete(int $id, array $result): void {
        $this->update('smartcook_imports', $id, [
            'status' => 'complete',
            'result' => $this->encode($result),
            'error' => null,
            'updated_at' => time(),
        ]);
    }

    public function markFailed(int $id, string $error): void {
        $this->update('smartcook_imports', $id, [
            'status' => 'failed',
            'error' => mb_substr($error, 0, 4000),
            'updated_at' => time(),
        ]);
    }

    public function deleteForUser(int $id, string $userId): bool {
        $qb = $this->db->getQueryBuilder();
        return $qb->delete('smartcook_imports')
            ->where($qb->expr()->eq('id', $qb->createNamedParameter($id, IQueryBuilder::PARAM_INT)))
            ->andWhere($this->ownedBy($qb, 'user_id', $userId))
            ->executeStatement() > 0;
    }

    public function deleteOlderThan(int $timestamp): int {
        $qb = $this->db->getQueryBuilder();
        return $qb->delete('smartcook_imports')
            ->where($qb->expr()->lt('updated_at', $qb->createNamedParameter($timestamp, IQueryBuilder::PARAM_INT)))
            ->executeStatement();
    }

    /** @return array<string, mixed> */
    private function map(array $row): array {
        return [
            'id' => (int)$row['id'],
            'userId' => (string)$row['user_id'],
            'kind' => (string)$row['kind'],
            'sourceRef' => $row['source_ref'],
            'status' => (string)$row['status'],
            'useAi' => (bool)$row['use_ai'],
            'provider' => $row['provider'],
            'payload' => $this->decode($row['payload'], []),
            'result' => $this->decode($row['result'], null),
            'error' => $row['error'],
            'createdAt' => (int)$row['created_at'],
            'updatedAt' => (int)$row['updated_at'],
        ];
    }
}
