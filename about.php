<?php
require_once 'koneksi.php';

// Ambil data tentang kami dari database (ID = 1)
$query = "SELECT * FROM about_us WHERE id = 1";
$result = mysqli_query($conn, $query);
$about = mysqli_fetch_assoc($result);

// Cek status login pengguna
$is_logged_in = isset($_SESSION['user']);
$user_name = $_SESSION['user'] ?? '';
$user_role = $_SESSION['role'] ?? 'user';
// Tentukan arah dashboard berdasarkan role
$dashboard_link = ($user_role === 'admin') ? 'admin-dashboard.php' : 'user-dashboard.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Tentang Kami - Marketiva Digital Agency</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; }
    .card-studio { background-color: #FFFFFF; border-radius: 1.25rem; border: 1px solid #E2E8F0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02); }
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
        <a href="catalog.php" class="hover:text-[#E11D48] transition-colors">Katalog Layanan</a>
        <a href="about.php" class="text-[#E11D48]">Tentang Kami</a>
      </nav>

      <div class="flex items-center gap-4">
        <?php if ($is_logged_in): ?>
          <!-- Jika Sudah Login: Tampilkan Profil / Nama Akun -->
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
          <!-- Jika Belum Login: Tampilkan Tombol Masuk -->
          <a href="login.php" class="px-5 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-md transition-all flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[16px]">login</span>
            <span>Masuk / Daftar</span>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </header>

  <!-- KONTEN UTAMA TENTANG KAMI -->
  <main class="max-w-[1000px] mx-auto px-6 py-12 w-full flex-1 space-y-8">
    <div class="text-center space-y-3 max-w-2xl mx-auto">
      <span class="px-3 py-1 rounded-full bg-rose-50 text-[#E11D48] text-xs font-bold uppercase tracking-wider">Profil Agensi</span>
      <h1 class="text-3xl font-extrabold text-slate-900"><?= htmlspecialchars($about['judul'] ?? 'Tentang Marketiva'); ?></h1>
    </div>

    <div class="card-studio p-8 md:p-10 space-y-6">
      <div>
        <h3 class="text-sm font-extrabold text-slate-400 uppercase tracking-wider mb-2">Deskripsi Utama</h3>
        <p class="text-slate-700 text-sm md:text-base leading-relaxed whitespace-pre-line"><?= htmlspecialchars($about['deskripsi'] ?? ''); ?></p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-6 border-t border-slate-100">
        <div class="p-6 rounded-2xl bg-slate-50 border border-slate-200 space-y-2">
          <h3 class="text-xs font-extrabold text-indigo-600 uppercase tracking-wider">Visi Kami</h3>
          <p class="text-xs md:text-sm text-slate-700 leading-relaxed"><?= htmlspecialchars($about['visi'] ?? ''); ?></p>
        </div>

        <div class="p-6 rounded-2xl bg-rose-50/50 border border-rose-200 space-y-2">
          <h3 class="text-xs font-extrabold text-[#E11D48] uppercase tracking-wider">Misi Kami</h3>
          <p class="text-xs md:text-sm text-slate-700 leading-relaxed whitespace-pre-line"><?= htmlspecialchars($about['misi'] ?? ''); ?></p>
        </div>
      </div>
    </div>
  </main>

  <!-- FOOTER -->
  <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-400">
    © 2026 Marketiva Digital Agency. All rights reserved.
  </footer>
</body>
</html>