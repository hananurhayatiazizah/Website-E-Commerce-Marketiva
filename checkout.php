<?php
require_once 'koneksi.php';

$nama_layanan = $_GET['layanan'] ?? $_POST['nama_layanan'] ?? '';
$harga        = $_GET['harga'] ?? $_POST['harga'] ?? 0;
$kategori     = $_GET['kategori'] ?? $_POST['kategori'] ?? 'Digital Marketing';

$success_msg = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_process_payment'])) {
    $nama_lengkap    = trim($_POST['nama_lengkap'] ?? '');
    $nama_usaha      = trim($_POST['nama_usaha'] ?? '');
    $whatsapp        = trim($_POST['whatsapp'] ?? '');
    $layanan_dipesan = trim($_POST['nama_layanan'] ?? '');
    $harga_pesan     = floatval($_POST['harga'] ?? 0);
    $kategori_pesan  = trim($_POST['kategori'] ?? 'Digital Marketing');
    $metode_bayar    = trim($_POST['metode_bayar'] ?? '');
    
    $order_id        = 'ORD-' . strtoupper(uniqid());
    $status_awal     = 'Menunggu Konfirmasi';
    $user_id         = $_SESSION['user_id'] ?? 0;
    $user_email      = $_SESSION['email'] ?? ($whatsapp . '@marketiva.com');

    if (!empty($nama_lengkap) && !empty($whatsapp) && !empty($layanan_dipesan)) {
        $stmt = $conn->prepare("INSERT INTO orders (order_id, user_email, nama_layanan, kategori, harga, status, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssdsi", $order_id, $user_email, $layanan_dipesan, $kategori_pesan, $harga_pesan, $status_awal, $user_id);
        
        if ($stmt->execute()) {
            $success_msg = true;
        }
        $stmt->close();
    }
}

// Status Sesi Login
$is_logged_in = isset($_SESSION['user']);
$user_name = $_SESSION['user'] ?? '';
$user_role = $_SESSION['role'] ?? 'user';
$dashboard_link = ($user_role === 'admin') ? 'admin-dashboard.php' : 'user-dashboard.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Checkout & Pembayaran - Marketiva</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; }
    .card-studio { background-color: #FFFFFF; border-radius: 1.25rem; border: 1px solid #E2E8F0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02); }
    .input-studio { height: 46px; border-radius: 0.75rem; border: 1.5px solid #CBD5E1; padding: 0 1rem; width: 100%; font-size: 0.875rem; background-color: #FFFFFF; color: #1E293B; transition: all 0.2s; }
    .input-studio:focus { outline: none; border-color: #4F46E5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
  </style>
</head>
<body class="min-h-screen flex flex-col justify-between">

  <!-- HEADER NAVBAR KONSISTEN & DINAMIS -->
  <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-200">
    <div class="max-w-[1320px] mx-auto px-6 h-20 flex items-center justify-between">
      <a href="index.php" class="font-extrabold text-2xl tracking-tight">
        <span class="text-[#F43F5E]">Mar</span><span class="text-[#3B82F6]">ke</span><span class="text-[#10B981]">ti</span><span class="text-[#F59E0B]">va</span>
      </a>

      <nav class="hidden md:flex items-center gap-8 text-xs font-bold text-slate-600">
        <a href="catalog.php" class="hover:text-[#E11D48] flex items-center gap-1 transition-colors">
          <span class="material-symbols-outlined text-[16px]">arrow_back</span> Kembali ke Katalog
        </a>
      </nav>

      <div class="flex items-center gap-4">
        <?php if ($is_logged_in): ?>
          <a href="<?= $dashboard_link; ?>" class="flex items-center gap-3 bg-slate-50 border border-slate-200 px-4 py-2 rounded-2xl hover:bg-slate-100 transition-all">
            <div class="w-8 h-8 rounded-xl bg-rose-100 text-[#E11D48] flex items-center justify-center font-bold text-xs">
              <?= strtoupper(substr($user_name, 0, 1)); ?>
            </div>
            <div class="text-left hidden sm:block">
              <p class="text-[10px] text-slate-400 font-bold uppercase"><?= ($user_role === 'admin') ? 'Administrator' : 'Pelanggan'; ?></p>
              <p class="text-xs font-extrabold text-slate-800"><?= htmlspecialchars($user_name); ?></p>
            </div>
          </a>
          <a href="login.php?action=logout" class="p-2.5 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 transition-all" title="Keluar">
            <span class="material-symbols-outlined text-[18px]">logout</span>
          </a>
        <?php else: ?>
          <a href="login.php" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-md transition-all flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">login</span>
            <span>Masuk / Daftar</span>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <main class="max-w-[1000px] mx-auto px-6 py-10 w-full flex-1">
    <?php if ($success_msg): ?>
      <div class="card-studio p-8 text-center space-y-6 max-w-lg mx-auto">
        <div class="w-16 h-16 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto text-3xl font-extrabold">✓</div>
        <div class="space-y-2">
          <h2 class="text-xl font-extrabold text-slate-900">Pesanan & Pembayaran Tercatat!</h2>
          <p class="text-xs text-slate-500 leading-relaxed">Terima kasih telah memesan jasa di Marketiva. Tim kami akan segera memverifikasi pesanan Anda.</p>
        </div>
        <a href="user-dashboard.php" class="block w-full py-3.5 rounded-xl bg-slate-900 text-white font-bold text-xs">Lihat di Dashboard Saya</a>
      </div>
    <?php else: ?>
      <div class="grid grid-cols-1 md:grid-cols-12 gap-8 items-start">
        <div class="md:col-span-7 card-studio p-8 space-y-6">
          <div>
            <h2 class="text-xl font-extrabold text-slate-900">Checkout Pesanan Jasa</h2>
            <p class="text-xs text-slate-500 mt-0.5">Lengkapi data diri dan pilih metode pembayaran.</p>
          </div>

          <form method="POST" action="checkout.php" class="space-y-4">
            <input type="hidden" name="action_process_payment" value="1">
            <input type="hidden" name="nama_layanan" value="<?= htmlspecialchars($nama_layanan); ?>">
            <input type="hidden" name="harga" value="<?= htmlspecialchars($harga); ?>">
            <input type="hidden" name="kategori" value="<?= htmlspecialchars($kategori); ?>">

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Lengkap</label>
              <input type="text" name="nama_lengkap" class="input-studio" placeholder="Nama Anda" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Usaha / UMKM</label>
              <input type="text" name="nama_usaha" class="input-studio" placeholder="Contoh: Kopi Senja" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nomor WhatsApp Aktif</label>
              <input type="text" name="whatsapp" class="input-studio" placeholder="08123456789" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Pilih Metode Pembayaran</label>
              <select name="metode_bayar" class="input-studio" required>
                <option value="">-- Pilih Bank / E-Wallet --</option>
                <option value="Transfer BCA - 8830123456">Transfer BCA (8830123456)</option>
                <option value="Transfer Mandiri - 1310098765432">Transfer Mandiri (1310098765432)</option>
                <option value="QRIS All Payment">QRIS (Scan & Bayar Instan)</option>
              </select>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Catatan Tambahan (Opsional)</label>
              <textarea name="catatan" rows="3" class="input-studio" placeholder="Tuliskan detail permintaan khusus..."></textarea>
            </div>

            <button type="submit" name="action_process_payment" class="w-full py-4 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-lg transition-all">
              Konfirmasi & Selesaikan Pembayaran
            </button>
          </form>
        </div>

        <div class="md:col-span-5 card-studio p-6 space-y-6 bg-slate-50/50">
          <h3 class="text-sm font-extrabold text-slate-900 uppercase tracking-wide">Ringkasan Pesanan</h3>
          <div class="space-y-4 pb-4 border-b border-slate-200">
            <div>
              <p class="text-[10px] font-extrabold text-slate-400 uppercase">Layanan Pilihan</p>
              <p class="text-sm font-extrabold text-slate-800 mt-0.5"><?= htmlspecialchars($nama_layanan ?: 'Belum ada layanan'); ?></p>
            </div>
            <div>
              <p class="text-[10px] font-extrabold text-slate-400 uppercase">Estimasi Biaya</p>
              <p class="text-xl font-extrabold text-emerald-600 mt-0.5">Rp <?= is_numeric($harga) ? number_format($harga, 0, ',', '.') : '0'; ?></p>
            </div>
          </div>
        </div>
      </div>
    <?php endif; ?>
  </main>
  
  <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-400 mt-12">
    © 2026 Marketiva Digital Agency. Secure Checkout.
  </footer>
</body>
</html>