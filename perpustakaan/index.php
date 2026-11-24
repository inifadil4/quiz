<?php
spl_autoload_register(fn($c) => require __DIR__ . "/classes/$c.php");

$loan    = new Loan();
$members = $loan->members();
$books   = $loan->books();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $ok = match ($_POST['action'] ?? '') {
        'member_add'    => $members->create($_POST),
        'member_update' => $members->update($id, $_POST),
        'member_delete' => $members->delete($id),
        'book_add'      => $books->create($_POST),
        'book_update'   => $books->update($id, $_POST),
        'book_delete'   => $books->delete($id),
        'loan_add'      => $loan->create($_POST),
        'loan_update'   => $loan->update($id, $_POST),
        'loan_return'   => $loan->giveBack($id),
        'loan_delete'   => $loan->delete($id),
        default         => false,
    };
    $err = $members->firstError() ?: $books->firstError() ?: $loan->error();
    header('Location: index.php?' . http_build_query($ok ? ['ok' => 1] : ['err' => $err ?: 'Gagal.']));
    exit;
}

$h = fn($s) => htmlspecialchars((string) $s);
$editMember = isset($_GET['em']) ? $members->find((int) $_GET['em']) : null;
$editBook   = isset($_GET['eb']) ? $books->find((int) $_GET['eb']) : null;
$editLoan   = isset($_GET['el']) ? $loan->find((int) $_GET['el']) : null;

function del(string $action, int $id): string {
    return '<form method="post" onsubmit="return confirm(\'Hapus data ini?\')" style="display:inline">'
         . '<input type="hidden" name="action" value="' . $action . '">'
         . '<input type="hidden" name="id" value="' . $id . '"><button>Hapus</button></form>';
}
?>
<!DOCTYPE html>
<html lang="id"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Perpustakaan Mini</title>
<style>
*{box-sizing:border-box}
body{font-family:"Courier New",monospace;background:#fff;color:#000;max-width:980px;margin:24px auto;padding:0 16px}
h1{border-bottom:4px solid #000;padding-bottom:8px;margin-bottom:16px}
h3{margin:0 0 12px;text-transform:uppercase;letter-spacing:1px}
section{border:2px solid #000;padding:16px;margin:20px 0;overflow-x:auto}
input,select{border:2px solid #000;background:#fff;color:#000;padding:7px;margin:3px 3px 3px 0;font-family:inherit}
button,a.btn{border:2px solid #000;background:#000;color:#fff;padding:6px 12px;margin:2px;cursor:pointer;font-family:inherit;font-size:14px;text-decoration:none;display:inline-block}
button:hover,a.btn:hover{background:#fff;color:#000}
a.btn.light{background:#fff;color:#000}a.btn.light:hover{background:#000;color:#fff}
table{width:100%;border-collapse:collapse}
th{background:#000;color:#fff;text-align:left;padding:8px}
td{border-bottom:1px solid #000;padding:8px}
.notice{border:2px solid #000;padding:10px;margin:12px 0;font-weight:bold}
.notice.err{background:#000;color:#fff}
</style></head><body>
<h1>PERPUSTAKAAN MINI</h1>
<?php if (isset($_GET['ok'])): ?><div class="notice">Berhasil.</div><?php endif; ?>
<?php if (isset($_GET['err'])): ?><div class="notice err"><?= $h($_GET['err']) ?></div><?php endif; ?>

<!-- ANGGOTA -->
<section><h3>Anggota</h3>
<form method="post">
<input type="hidden" name="action" value="<?= $editMember ? 'member_update' : 'member_add' ?>">
<input type="hidden" name="id" value="<?= $editMember['id'] ?? 0 ?>">
<input name="name" placeholder="Nama" value="<?= $h($editMember['name'] ?? '') ?>">
<input name="email" placeholder="Email" value="<?= $h($editMember['email'] ?? '') ?>">
<button><?= $editMember ? 'Update' : 'Tambah' ?></button>
<?php if ($editMember): ?><a class="btn light" href="index.php">Batal</a><?php endif; ?>
</form><br>
<table><tr><th>ID</th><th>Nama</th><th>Email</th><th>Pinjam Aktif</th><th>Aksi</th></tr>
<?php foreach ($members->all() as $m): ?>
<tr><td><?= $m['id'] ?></td><td><?= $h($m['name']) ?></td><td><?= $h($m['email']) ?></td>
<td><?= $members->activeLoans((int) $m['id']) ?> / <?= $members->getMaxLoan() ?></td>
<td><a class="btn light" href="?em=<?= $m['id'] ?>">Edit</a><?= del('member_delete', (int) $m['id']) ?></td></tr>
<?php endforeach; ?></table></section>

<!-- BUKU -->
<section><h3>Buku</h3>
<form method="post">
<input type="hidden" name="action" value="<?= $editBook ? 'book_update' : 'book_add' ?>">
<input type="hidden" name="id" value="<?= $editBook['id'] ?? 0 ?>">
<input name="title" placeholder="Judul" value="<?= $h($editBook['title'] ?? '') ?>">
<input name="author" placeholder="Penulis" value="<?= $h($editBook['author'] ?? '') ?>">
<input name="stock" type="number" min="0" style="width:80px" value="<?= $h($editBook['stock'] ?? 1) ?>">
<button><?= $editBook ? 'Update' : 'Tambah' ?></button>
<?php if ($editBook): ?><a class="btn light" href="index.php">Batal</a><?php endif; ?>
</form><br>
<table><tr><th>ID</th><th>Judul</th><th>Penulis</th><th>Stok</th><th>Aksi</th></tr>
<?php foreach ($books->all() as $b): ?>
<tr><td><?= $b['id'] ?></td><td><?= $h($b['title']) ?></td><td><?= $h($b['author']) ?></td>
<td><?= Book::stockLabel((int) $b['stock']) ?></td>
<td><a class="btn light" href="?eb=<?= $b['id'] ?>">Edit</a><?= del('book_delete', (int) $b['id']) ?></td></tr>
<?php endforeach; ?></table></section>

<!-- PEMINJAMAN -->
<section><h3>Peminjaman</h3>
<?php if ($editLoan): ?>
<form method="post">
<input type="hidden" name="action" value="loan_update">
<input type="hidden" name="id" value="<?= $editLoan['id'] ?>">
Jatuh tempo: <input type="date" name="due_at" value="<?= $h($editLoan['due_at']) ?>">
<button>Update</button><a class="btn light" href="index.php">Batal</a>
</form>
<?php else: ?>
<form method="post">
<input type="hidden" name="action" value="loan_add">
<select name="member_id"><?php foreach ($members->all() as $m): ?>
<option value="<?= $m['id'] ?>"><?= $h($m['name']) ?></option><?php endforeach; ?></select>
<select name="book_id"><?php foreach ($books->all() as $b): ?>
<option value="<?= $b['id'] ?>"><?= $h($b['title']) ?> (<?= Book::stockLabel((int) $b['stock']) ?>)</option><?php endforeach; ?></select>
<button>Pinjam</button>
</form>
<?php endif; ?><br>
<table><tr><th>ID</th><th>Anggota</th><th>Buku</th><th>Jatuh Tempo</th><th>Status</th><th>Denda</th><th>Aksi</th></tr>
<?php foreach ($loan->report() as $r): ?>
<tr><td><?= $r['id'] ?></td><td><?= $h($r['member_name']) ?></td><td><?= $h($r['book_title']) ?></td>
<td><?= $h($r['due_at']) ?></td>
<td><?= $r['returned_at'] ? 'Kembali ' . $h($r['returned_at']) : 'Dipinjam' ?></td>
<td>Rp <?= number_format((int) $r['fee'], 0, ',', '.') ?></td>
<td>
<?php if (!$r['returned_at']): ?>
<form method="post" style="display:inline"><input type="hidden" name="action" value="loan_return">
<input type="hidden" name="id" value="<?= $r['id'] ?>"><button>Kembalikan</button></form>
<a class="btn light" href="?el=<?= $r['id'] ?>">Edit</a>
<?php endif; ?>
<?= del('loan_delete', (int) $r['id']) ?></td></tr>
<?php endforeach; ?></table></section>
</body></html>
