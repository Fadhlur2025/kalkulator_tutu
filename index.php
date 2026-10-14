<?php
require 'koneksi.php';

$kalkulator = [
  'dasar' => ['nama'=>'Dasar', 'judul'=>'Hitung dua angka', 'field'=>[
      ['a','Angka pertama','number'],
      ['op','Operasi','select',['+'=>'Tambah (+)','-'=>'Kurang (−)','*'=>'Kali (×)','/'=>'Bagi (÷)','^'=>'Pangkat (^)','%'=>'Sisa bagi (mod)']],
      ['b','Angka kedua','number']]],
  'persen' => ['nama'=>'Persentase', 'judul'=>'Berapa persen dari sebuah angka', 'field'=>[
      ['a','Persen (%)','number'], ['b','Dari angka','number']]],
  'diskon' => ['nama'=>'Diskon', 'judul'=>'Harga setelah diskon', 'field'=>[
      ['a','Harga awal','number'], ['b','Diskon (%)','number']]],
  'bmi' => ['nama'=>'BMI', 'judul'=>'Indeks massa tubuh', 'field'=>[
      ['a','Berat (kg)','number'], ['b','Tinggi (cm)','number']]],
  'suhu' => ['nama'=>'Suhu', 'judul'=>'Konversi suhu', 'field'=>[
      ['a','Nilai','number'],
      ['dari','Dari','select',['C'=>'Celsius','F'=>'Fahrenheit','K'=>'Kelvin']],
      ['ke','Ke','select',['C'=>'Celsius','F'=>'Fahrenheit','K'=>'Kelvin']]]],
  'lingkaran' => ['nama'=>'Lingkaran', 'judul'=>'Luas dan keliling lingkaran', 'field'=>[
      ['a','Jari-jari','number']]],
];

function fmt($n) { return rtrim(rtrim(number_format($n, 4, ',', '.'), '0'), ','); }

function hitung($jenis, $in) {
  $a = (float)($in['a'] ?? 0); $b = (float)($in['b'] ?? 0);
  switch ($jenis) {
    case 'dasar':
      $op = $in['op'] ?? '+';
      if (($op === '/' || $op === '%') && $b == 0) throw new Exception('Pembagi tidak boleh nol.');
      $r = match($op) { '+'=>$a+$b, '-'=>$a-$b, '*'=>$a*$b, '/'=>$a/$b, '^'=>$a**$b, '%'=>fmod($a,$b) };
      $s = ['*'=>'×','/'=>'÷','%'=>'mod'][$op] ?? $op;
      return [fmt($a)." $s ".fmt($b), fmt($r)];
    case 'persen':
      return [fmt($a).'% dari '.fmt($b), fmt($a/100*$b)];
    case 'diskon':
      if ($b < 0 || $b > 100) throw new Exception('Diskon harus antara 0 dan 100.');
      $hemat = $a*$b/100;
      return ['Harga '.fmt($a).' diskon '.fmt($b).'%', 'Bayar '.fmt($a-$hemat).' (hemat '.fmt($hemat).')'];
    case 'bmi':
      if ($a <= 0 || $b <= 0) throw new Exception('Berat dan tinggi harus lebih dari nol.');
      $bmi = $a / (($b/100)**2);
      $k = $bmi < 18.5 ? 'Kurus' : ($bmi < 25 ? 'Normal' : ($bmi < 30 ? 'Gemuk' : 'Obesitas'));
      return [fmt($a).' kg, '.fmt($b).' cm', round($bmi,1)." ($k)"];
    case 'suhu':
      $d = $in['dari'] ?? 'C'; $k = $in['ke'] ?? 'C';
      $c = $d==='C' ? $a : ($d==='F' ? ($a-32)*5/9 : $a-273.15);
      $r = $k==='C' ? $c : ($k==='F' ? $c*9/5+32 : $c+273.15);
      return [fmt($a)." °$d ke °$k", fmt($r)." °$k"];
    case 'lingkaran':
      if ($a <= 0) throw new Exception('Jari-jari harus lebih dari nol.');
      return ['Jari-jari '.fmt($a), 'Luas '.fmt(M_PI*$a*$a).', keliling '.fmt(2*M_PI*$a)];
  }
  throw new Exception('Jenis kalkulator tidak dikenal.');
}

$jenis = $_GET['jenis'] ?? 'dasar';
if (!isset($kalkulator[$jenis])) $jenis = 'dasar';
$hasil = $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (($_POST['aksi'] ?? '') === 'hapus') {
    $st = $conn->prepare('DELETE FROM riwayat WHERE id = ?');
    $id = (int)$_POST['id']; $st->bind_param('i', $id); $st->execute();
    header("Location: ?jenis=$jenis"); exit;
  }
  if (($_POST['aksi'] ?? '') === 'hapus_semua') {
    $conn->query('DELETE FROM riwayat'); header("Location: ?jenis=$jenis"); exit;
  }
  try {
    [$ekspresi, $nilai] = hitung($jenis, $_POST);
    $st = $conn->prepare('INSERT INTO riwayat (jenis_id, ekspresi, hasil) SELECT id, ?, ? FROM jenis_kalkulator WHERE kode = ?');
    $st->bind_param('sss', $ekspresi, $nilai, $jenis); $st->execute();
    $hasil = [$ekspresi, $nilai];
  } catch (Throwable $e) { $error = $e->getMessage() ?: 'Input tidak valid.'; }
}

$riwayat = $conn->query('SELECT r.id, j.nama, r.ekspresi, r.hasil, r.dibuat_pada FROM riwayat r JOIN jenis_kalkulator j ON j.id = r.jenis_id ORDER BY r.id DESC LIMIT 20');
$cfg = $kalkulator[$jenis];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Kalkulator Tutu</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<header><h1>Kalkulator Tutu</h1><p>Hitung apa saja, hasilnya tersimpan di database.</p></header>
<main>
  <nav aria-label="Jenis kalkulator">
    <?php foreach ($kalkulator as $k => $c): ?>
      <a href="?jenis=<?= $k ?>" class="<?= $k === $jenis ? 'aktif' : '' ?>"><?= $c['nama'] ?></a>
    <?php endforeach; ?>
  </nav>

  <section class="panel">
    <h2><?= htmlspecialchars($cfg['judul']) ?></h2>
    <form method="post">
      <?php foreach ($cfg['field'] as $f): $val = $_POST[$f[0]] ?? ''; ?>
        <label><?= $f[1] ?>
        <?php if ($f[2] === 'select'): ?>
          <select name="<?= $f[0] ?>">
            <?php foreach ($f[3] as $v => $t): ?>
              <option value="<?= $v ?>" <?= $val === (string)$v ? 'selected' : '' ?>><?= $t ?></option>
            <?php endforeach; ?>
          </select>
        <?php else: ?>
          <input type="number" step="any" name="<?= $f[0] ?>" value="<?= htmlspecialchars($val) ?>" required>
        <?php endif; ?>
        </label>
      <?php endforeach; ?>
      <button type="submit">Hitung dan simpan</button>
    </form>
    <?php if ($error): ?><p class="error" role="alert"><?= htmlspecialchars($error) ?></p><?php endif; ?>
    <?php if ($hasil): ?>
      <div class="hasil" role="status"><small><?= htmlspecialchars($hasil[0]) ?></small><strong><?= htmlspecialchars($hasil[1]) ?></strong></div>
    <?php endif; ?>
  </section>

  <section class="panel">
    <div class="judul-riwayat">
      <h2>Riwayat perhitungan</h2>
      <?php if ($riwayat->num_rows): ?>
      <form method="post" onsubmit="return confirm('Hapus semua riwayat?')">
        <input type="hidden" name="aksi" value="hapus_semua"><button class="kecil" type="submit">Hapus semua</button>
      </form>
      <?php endif; ?>
    </div>
    <?php if (!$riwayat->num_rows): ?>
      <p class="kosong">Belum ada riwayat. Lakukan perhitungan pertama Anda di atas.</p>
    <?php else: ?>
    <div class="tabel"><table>
      <tr><th>Jenis</th><th>Perhitungan</th><th>Hasil</th><th>Waktu</th><th></th></tr>
      <?php while ($r = $riwayat->fetch_assoc()): ?>
      <tr>
        <td><?= htmlspecialchars($r['nama']) ?></td>
        <td><?= htmlspecialchars($r['ekspresi']) ?></td>
        <td><b><?= htmlspecialchars($r['hasil']) ?></b></td>
        <td><?= date('d M H:i', strtotime($r['dibuat_pada'])) ?></td>
        <td><form method="post"><input type="hidden" name="aksi" value="hapus"><input type="hidden" name="id" value="<?= $r['id'] ?>"><button class="kecil" type="submit">Hapus</button></form></td>
      </tr>
      <?php endwhile; ?>
    </table></div>
    <?php endif; ?>
  </section>
</main>
</body>
</html>
