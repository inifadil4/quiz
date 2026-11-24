<?php

abstract class BaseModel
{
    protected PDO $db;
    protected string $table;
    private array $errors = [];

    public function __construct()
    {
        $this->db    = Database::connect();
        $this->table = $this->tableName();
    }

    abstract protected function tableName(): string;
    abstract public function create(array $data): bool;
    abstract public function update(int $id, array $data): bool;

    public function all(): array
    {
        return $this->db->query("SELECT * FROM {$this->table} ORDER BY id DESC")->fetchAll();
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch() ?: null;
    }

    public function delete(int $id): bool
    {
        $st = $this->db->prepare("DELETE FROM {$this->table} WHERE id = ?");
        return $st->execute([$id]);
    }

    protected function addError(string $message): void
    {
        $this->errors[] = $message;
    }

    public function firstError(): string
    {
        return $this->errors[0] ?? '';
    }
}
