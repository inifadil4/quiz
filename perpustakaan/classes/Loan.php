<?php

class Loan extends BaseModel
{
    private const LOAN_DAYS  = 7;
    private const FEE_PER_DAY = 1000;

    private Member $member;
    private Book $book;

    public function __construct()
    {
        parent::__construct();
        $this->member = new Member();
        $this->book   = new Book();
    }

    protected function tableName(): string
    {
        return 'loans';
    }

    public function create(array $d): bool
    {
        $memberId = (int) ($d['member_id'] ?? 0);
        $bookId   = (int) ($d['book_id'] ?? 0);

        $m = $this->member->find($memberId);
        $b = $this->book->find($bookId);

        if (!$m || !$b) {
            $this->addError('Anggota atau buku tidak ditemukan.');
            return false;
        }
        if ($b['stock'] < 1) {
            $this->addError('Stok buku habis.');
            return false;
        }
        if ($this->member->activeLoans($memberId) >= $this->member->getMaxLoan()) {
            $this->addError('Batas pinjam ' . $this->member->getMaxLoan() . ' buku sudah tercapai.');
            return false;
        }

        $this->db->beginTransaction();
        $st = $this->db->prepare('INSERT INTO loans (member_id, book_id, borrowed_at, due_at) VALUES (?, ?, ?, ?)');
        $st->execute([
            $memberId,
            $bookId,
            date('Y-m-d'),
            date('Y-m-d', strtotime('+' . self::LOAN_DAYS . ' days')),
        ]);
        $this->book->changeStock($bookId, -1);
        $this->db->commit();
        return true;
    }

    // Update: perpanjang / ubah tanggal jatuh tempo (hanya jika belum kembali)
    public function update(int $id, array $d): bool
    {
        $loan = $this->find($id);
        $due  = $d['due_at'] ?? '';
        if (!$loan || $loan['returned_at'] !== null) {
            $this->addError('Peminjaman sudah selesai, tidak bisa diubah.');
            return false;
        }
        if (!strtotime($due) || $due < $loan['borrowed_at']) {
            $this->addError('Tanggal jatuh tempo tidak valid.');
            return false;
        }
        $st = $this->db->prepare('UPDATE loans SET due_at = ? WHERE id = ?');
        return $st->execute([$due, $id]);
    }

    // Delete: jika buku belum dikembalikan, stok dipulihkan dulu
    public function delete(int $id): bool
    {
        $loan = $this->find($id);
        if (!$loan) {
            return false;
        }
        $this->db->beginTransaction();
        if ($loan['returned_at'] === null) {
            $this->book->changeStock((int) $loan['book_id'], 1);
        }
        parent::delete($id);
        $this->db->commit();
        return true;
    }

    public function giveBack(int $loanId): bool
    {
        $loan = $this->find($loanId);
        if (!$loan || $loan['returned_at'] !== null) {
            $this->addError('Data peminjaman tidak valid.');
            return false;
        }
        $today = date('Y-m-d');
        $fee   = self::calcFee($loan['due_at'], $today);

        $this->db->beginTransaction();
        $st = $this->db->prepare('UPDATE loans SET returned_at = ?, fee = ? WHERE id = ?');
        $st->execute([$today, $fee, $loanId]);
        $this->book->changeStock((int) $loan['book_id'], 1);
        $this->db->commit();
        return true;
    }

    public static function calcFee(string $dueAt, string $returnedAt): int
    {
        $late = (strtotime($returnedAt) - strtotime($dueAt)) / 86400;
        return $late > 0 ? (int) $late * self::FEE_PER_DAY : 0;
    }

    // Relasi 3 tabel lewat JOIN
    public function report(): array
    {
        return $this->db->query('
            SELECT l.*, m.name AS member_name, b.title AS book_title
            FROM loans l
            JOIN members m ON m.id = l.member_id
            JOIN books b   ON b.id = l.book_id
            ORDER BY l.id DESC
        ')->fetchAll();
    }

    public function members(): Member { return $this->member; }
    public function books(): Book { return $this->book; }
    public function error(): string { return $this->firstError(); }
}
