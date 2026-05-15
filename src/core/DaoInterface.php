<?php
namespace LEX360\Core;

interface DaoInterface
{
    public function findById(int $id): ?array;
    public function findAll(array $filters = []): array;
    public function insert(array $data): int;
    public function update(int $id, array $data): bool;
    public function delete(int $id): bool;
}