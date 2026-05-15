<?php
namespace LEX360\Core;

use LEX360\Models\Dao\Db\Database;
use PDO;

abstract class BaseDao implements DaoInterface
{
    protected PDO $db;
    protected string $table;
    protected string $pk = 'id';

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->pk} = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    public function findAll(array $filters = []): array
    {
        // Filtro base seguro. Se extenderá por cada DAO específico
        $sql = "SELECT * FROM {$this->table} ORDER BY {$this->pk} DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders}) RETURNING {$this->pk}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($data);
        return (int) $stmt->fetchColumn();
    }

    public function update(int $id, array $data): bool
    {
        if (isset($data[$this->pk])) unset($data[$this->pk]); // Evitar sobrescribir PK
        
        $set = [];
        foreach (array_keys($data) as $key) {
            $set[] = "{$key} = :{$key}";
        }
        
        $sql = "UPDATE {$this->table} SET " . implode(', ', $set) . " WHERE {$this->pk} = :id";
        $data['id'] = $id;
        
        return $this->db->prepare($sql)->execute($data);
    }

    public function delete(int $id): bool
    {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->pk} = :id");
        return $stmt->execute(['id' => $id]);
    }
}