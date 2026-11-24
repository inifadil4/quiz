<?php
class Book extends BaseModel
{
    protected function tableName(): string
    {
        return 'books';
    }

    public function create(array $d): bool
    {
        if (trim($d['title'] ?? '') === '' || trim($d['author'] ?? '') === '') {
            $this->addError('Judul dan penulis wajib diisi.');
            return false;
        }
        $stock = (int) ($d['stock'] ?? 0);
        if ($stock < 1) {
            $this->addError('Stok minimal 1.');
            return false;
        }
        $st = $this->db->prepare('INSERT INTO books (title, author, stock) VALUES (?, ?, ?)');
        return $st->execute([trim($d['title']), trim($d['author']), $stock]);
    }

    public function update(int $id, array $d): bool
    {
        if (trim($d['title'] ?? '') === '' || trim($d['author'] ?? '') === '') {
            $this->addError('Judul dan penulis wajib diisi.');
            return false;
        }
        $stock = (int) ($d['stock'] ?? -1);
        if ($stock < 0) {
            $this->addError('Stok tidak boleh negatif.');
            return false;
        }
        $st = $this->db->prepare('UPDATE books SET title = ?, author = ?, stock = ? WHERE id = ?');
        return $st->execute([trim($d['title']), trim($d['author']), $stock, $id]);
    }

    public function changeStock(int $id, int $delta): void
    {
        $st = $this->db->prepare('UPDATE books SET stock = stock + ? WHERE id = ?');
        $st->execute([$delta, $id]);
    }

    public static function stockLabel(int $stock): string
    {
        return $stock > 0 ? "Tersedia ($stock)" : 'Habis';
    }
}
