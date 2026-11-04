<?php
require_once 'koneksi.php';

// Ambil semua data layanan digabung dengan kategori
$query = "SELECT s.*, c.nama_kategori FROM services s LEFT JOIN categories c ON s.kategori_id = c.id ORDER BY s.id DESC";
$result = mysqli_query($conn, $query);

$services_all = [];
$bundling_items = [];

while ($row = mysqli_fetch_assoc($result)) {
    $services_all[] = $row;
    $nama_kat = strtolower($row['nama_kategori'] ?? '');
    if (strpos($nama_kat, 'bundling') !== false || strpos($nama_kat, 'paket') !== false) {
        $bundling_items[] = $row;
    }
}

// Jika kategori bundling belum ada, ambil 3 data teratas sebagai cadangan
if (empty($bundling_items)) {
    $bundling_items = array_slice($services_all, 0, 3);
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
  <title>Katalog Layanan Digital - Marketiva Agency</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; }
    .card-studio { background-color: #FFFFFF; border-radius: 1.25rem; border: 1px solid #E2E8F0; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02); transition: all 0.3s ease; }
    .card-studio:hover { transform: translateY(-3px); box-shadow: 0 12px 20px -5px rgba(225, 29, 72, 0.08); border-color: #FDA4AF; }
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
        <a href="index.php" class="hover:text-[#E11D48] transition-colors">Beranda</a>
        <a href="catalog.php" class="text-[#E11D48]">Katalog Layanan</a>
        <a href="about.php" class="hover:text-[#E11D48] transition-colors">Tentang Kami</a>
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

  <!-- MAIN CONTENT -->
  <main class="max-w-[1320px] mx-auto px-6 py-12 w-full flex-1 space-y-16">
    
    <!-- HERO SECTION: BUNDLING -->
    <section class="space-y-6">
      <div class="flex flex-col items-center text-center max-w-2xl mx-auto space-y-3">
        <span class="px-3.5 py-1.5 rounded-full bg-rose-50 text-[#E11D48] text-xs font-extrabold uppercase border border-rose-200 flex items-center gap-1.5">
          <span class="material-symbols-outlined text-[16px]">verified</span> Paket Bundling & Unggulan
        </span>
        <h1 class="text-3xl sm:text-4xl font-extrabold text-slate-900">Solusi Terbaik untuk Pertumbuhan UMKM</h1>
        <p class="text-xs text-slate-500">Pilihan paket spesial paling direkomendasikan untuk mendongkrak omset dan visibilitas digital Anda.</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php foreach ($bundling_items as $row): ?>
          <div class="card-studio flex flex-col justify-between">
            <div>
              <div class="h-48 w-full bg-slate-100 relative overflow-hidden">
                <?php if (!empty($row['gambar'])): ?>
                  <img src="<?= htmlspecialchars($row['gambar']); ?>" class="w-full h-full object-cover">
                <?php else: ?>
                  <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-rose-50 to-indigo-50 text-slate-400 gap-1">
                    <span class="material-symbols-outlined text-[36px]">image</span>
                    <span class="text-[10px] font-bold uppercase">Marketiva Bundling</span>
                  </div>
                <?php endif; ?>
                <div class="absolute top-3 left-3">
                  <span class="px-3 py-1 rounded-lg bg-rose-600 text-white text-[10px] font-extrabold uppercase shadow-sm">
                    <?= htmlspecialchars($row['nama_kategori'] ?? 'Bundling'); ?>
                  </span>
                </div>
              </div>
              <div class="p-6 space-y-3">
                <h3 class="text-lg font-extrabold text-slate-900"><?= htmlspecialchars($row['nama_layanan']); ?></h3>
                <p class="text-xs text-slate-500 leading-relaxed"><?= htmlspecialchars($row['deskripsi'] ?: 'Layanan profesional berstandar agensi.'); ?></p>
              </div>
            </div>
            <div class="px-6 pb-6 pt-2 flex items-center justify-between border-t border-slate-100 mt-auto">
              <div>
                <p class="text-[10px] font-extrabold text-slate-400 uppercase">Mulai Dari</p>
                <p class="text-base font-extrabold text-emerald-600">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></p>
              </div>
              <a href="checkout.php?layanan=<?= urlencode($row['nama_layanan']); ?>&harga=<?= $row['harga']; ?>" class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center gap-1.5 transition-all">
                <span>Pesan Jasa</span>
                <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
              </a>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    </section>

    <!-- KATALOG LENGKAP -->
    <section class="space-y-8 pt-6 border-t border-slate-200">
      <div class="flex items-center justify-between">
        <div>
          <h2 class="text-2xl font-extrabold text-slate-900">Semua Katalog Layanan</h2>
          <p class="text-xs text-slate-500 mt-0.5">Dikelompokkan rapi berdasarkan lini kategori digital.</p>
        </div>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
        <?php if (!empty($services_all)): ?>
          <?php foreach ($services_all as $row): ?>
            <div class="card-studio flex flex-col justify-between">
              <div>
                <div class="h-48 w-full bg-slate-100 relative overflow-hidden">
                  <?php if (!empty($row['gambar'])): ?>
                    <img src="<?= htmlspecialchars($row['gambar']); ?>" class="w-full h-full object-cover">
                  <?php else: ?>
                    <div class="w-full h-full flex flex-col items-center justify-center bg-gradient-to-br from-indigo-50 to-slate-50 text-slate-400 gap-1">
                      <span class="material-symbols-outlined text-[36px]">image</span>
                    </div>
                  <?php endif; ?>
                  <div class="absolute top-3 left-3">
                    <span class="px-3 py-1 rounded-lg bg-white/90 backdrop-blur-md text-indigo-700 text-[10px] font-extrabold uppercase shadow-sm">
                      <?= htmlspecialchars($row['nama_kategori'] ?? 'Digital'); ?>
                    </span>
                  </div>
                </div>
                <div class="p-6 space-y-3">
                  <h3 class="text-lg font-extrabold text-slate-900"><?= htmlspecialchars($row['nama_layanan']); ?></h3>
                  <p class="text-xs text-slate-500 leading-relaxed"><?= htmlspecialchars($row['deskripsi'] ?: 'Layanan profesional pilihan agensi.'); ?></p>
                </div>
              </div>
              <div class="px-6 pb-6 pt-2 flex items-center justify-between border-t border-slate-100 mt-auto">
                <div>
                  <p class="text-[10px] font-extrabold text-slate-400 uppercase">Mulai Dari</p>
                  <p class="text-base font-extrabold text-emerald-600">Rp <?= number_format($row['harga'], 0, ',', '.'); ?></p>
                </div>
                <a href="checkout.php?layanan=<?= urlencode($row['nama_layanan']); ?>&harga=<?= $row['harga']; ?>" class="px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs flex items-center gap-1.5 transition-all">
                  <span>Pesan Jasa</span>
                  <span class="material-symbols-outlined text-[16px]">arrow_forward</span>
                </a>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-span-3 text-center py-16 card-studio space-y-3 p-8">
            <span class="material-symbols-outlined text-[48px] text-slate-300">inventory_2</span>
            <p class="text-sm font-bold text-slate-700">Belum ada layanan yang tersedia di katalog.</p>
          </div>
        <?php endif; ?>
      </div>
    </section>

  </main>

  <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-400 mt-12">
    © 2026 Marketiva Digital Agency.
  </footer>

</body>
</html>