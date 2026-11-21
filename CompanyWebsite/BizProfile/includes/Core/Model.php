<?php

namespace BizProfile\Core;

abstract class Model
{
    protected string $table;
    protected array $fillable = [];
    
    public function all(): array
    {
        return Database::query("SELECT * FROM {$this->table}");
    }
    
    public function find(int $id): ?array
    {
        $result = Database::query("SELECT * FROM {$this->table} WHERE id = ?", [$id]);
        return $result[0] ?? null;
    }
    
    public function findBy(string $column, $value): ?array
    {
        $result = Database::query("SELECT * FROM {$this->table} WHERE {$column} = ?", [$value]);
        return $result[0] ?? null;
    }
    
    public function create(array $data): bool
    {
        $filteredData = array_intersect_key($data, array_flip($this->fillable));
        $columns = implode(',', array_keys($filteredData));
        $placeholders = implode(',', array_fill(0, count($filteredData), '?'));
        $values = array_values($filteredData);
        
        return Database::execute(
            "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})",
            $values
        );
    }
    
    public function update(int $id, array $data): bool
    {
        $filteredData = array_intersect_key($data, array_flip($this->fillable));
        $setClause = implode(' = ?, ', array_keys($filteredData)) . ' = ?';
        $values = array_values($filteredData);
        $values[] = $id;
        
        return Database::execute(
            "UPDATE {$this->table} SET {$setClause} WHERE id = ?",
            $values
        );
    }
    
    public function delete(int $id): bool
    {
        return Database::execute("DELETE FROM {$this->table} WHERE id = ?", [$id]);
    }
    
    public function where(string $column, $value): array
    {
        return Database::query("SELECT * FROM {$this->table} WHERE {$column} = ?", [$value]);
    }
    
    public function paginate(int $perPage = 10, int $page = 1): array
    {
        $offset = ($page - 1) * $perPage;
        return Database::query("SELECT * FROM {$this->table} LIMIT {$perPage} OFFSET {$offset}");
    }
}