<?php
class Member extends Person
{
    protected function tableName(): string
    {
        return 'members';
    }

    public function role(): string
    {
        return 'Anggota';
    }

    public function create(array $d): bool
    {
        if (!$this->validPerson($d)) {
            return false;
        }
        try {
            $st = $this->db->prepare('INSERT INTO members (name, email) VALUES (?, ?)');
            return $st->execute([trim($d['name']), trim($d['email'])]);
        } catch (PDOException $e) {
            $this->addError('Email sudah terdaftar.');
            return false;
        }
    }

    public function update(int $id, array $d): bool
    {
        if (!$this->validPerson($d)) {
            return false;
        }
        try {
            $st = $this->db->prepare('UPDATE members SET name = ?, email = ? WHERE id = ?');
            return $st->execute([trim($d['name']), trim($d['email']), $id]);
        } catch (PDOException $e) {
            $this->addError('Email sudah dipakai anggota lain.');
            return false;
        }
    }

    public function activeLoans(int $memberId): int
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM loans WHERE member_id = ? AND returned_at IS NULL');
        $st->execute([$memberId]);
        return (int) $st->fetchColumn();
    }
}
