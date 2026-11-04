<?php
// 1. Mulai sesi untuk memeriksa apakah user atau admin sedang login
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once 'koneksi.php';

// PROSES SIMPAN FORM KONSULTASI JIKA DIKIRIM
$pesan_sukses = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_kirim_konsultasi'])) {
    $nama_umkm = trim($_POST['nama_umkm'] ?? '');
    $whatsapp  = trim($_POST['whatsapp'] ?? '');
    $layanan   = trim($_POST['kebutuhan_layanan'] ?? '');

    if (!empty($nama_umkm) && !empty($whatsapp)) {
        $stmt_konsul = $conn->prepare("INSERT INTO konsultasi_pesan (nama_umkm, whatsapp, kebutuhan_layanan) VALUES (?, ?, ?)");
        $stmt_konsul->bind_param("sss", $nama_umkm, $whatsapp, $layanan);
        $stmt_konsul->execute();
        $stmt_konsul->close();
        $pesan_sukses = "Pesan konsultasi Anda berhasil dikirim! Tim kami akan segera menghubungi via WhatsApp.";
    }
}

// 1. Ambil data Hero Slides dari database
$hero_slides_res = mysqli_query($conn, "SELECT * FROM hero_slides ORDER BY id ASC");
$hero_slides = [];
if ($hero_slides_res) {
    while ($row = mysqli_fetch_assoc($hero_slides_res)) {
        $hero_slides[] = $row;
    }
}

// 2. Ambil data Judul & Keunggulan dari database
$title_keunggulan = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM about_keunggulan_title WHERE id = 1"));
$keunggulan_list_res = mysqli_query($conn, "SELECT * FROM about_keunggulan_list ORDER BY id ASC");
$keunggulan_list = [];
if ($keunggulan_list_res) {
    while ($row = mysqli_fetch_assoc($keunggulan_list_res)) {
        $keunggulan_list[] = $row;
    }
}

// 3. Ambil data Paket Harga dari database
$paket_harga_res = mysqli_query($conn, "SELECT * FROM paket_harga ORDER BY id ASC");
$paket_harga_list = [];
if ($paket_harga_res) {
    while ($row = mysqli_fetch_assoc($paket_harga_res)) {
        $paket_harga_list[] = $row;
    }
}

// 4. Ambil data Banner CTA Checkout dari database
$cta_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM cta_banner WHERE id = 1"));

// 5. Ambil data Footer & Kontak dari database
$settings = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM website_settings WHERE id = 1"));
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Marketiva - Partner Pertumbuhan Digital UMKM #1 Indonesia</title>
  
  <!-- 1. Google Fonts & Material Symbols Icons -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

  <!-- 2. Tailwind CSS CDN -->
  <script src="https://cdn.tailwindcss.com"></script>
  
  <!-- 3. Internal CSS (Playful Modern Studio Theme) -->
  <style>
    :root {
      --canvas-warm: #FAF9F6;
      --primary-blue: #2563EB;
      --secondary-crimson: #E11D48;
      --brand-pink: #F43F5E;
      --bubblegum-pink: #EC4899;
      --fresh-green: #10B981;
      --amber-sun: #FBBF24;
      --on-surface: #131b2e;
    }

    body {
      font-family: 'Plus Jakarta Sans', sans-serif;
      background-color: var(--canvas-warm);
      color: var(--on-surface);
    }

    ::-webkit-scrollbar {
      display: none;
    }

    .ambient-glow-pink {
      position: absolute;
      width: 500px;
      height: 400px;
      background: radial-gradient(circle, rgba(244, 63, 94, 0.15) 0%, rgba(253, 242, 248, 0) 70%);
      filter: blur(60px);
      pointer-events: none;
    }

    .ambient-glow-blue {
      position: absolute;
      width: 550px;
      height: 450px;
      background: radial-gradient(circle, rgba(59, 130, 246, 0.15) 0%, rgba(239, 246, 255, 0) 70%);
      filter: blur(60px);
      pointer-events: none;
    }

    .btn-primary-hero {
      background-color: var(--secondary-crimson);
      color: #FFFFFF;
      font-weight: 700;
      padding: 0.875rem 1.75rem;
      border-radius: 1rem;
      box-shadow: 0 8px 20px -4px rgba(225, 29, 72, 0.3);
      transition: all 0.2s ease;
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      text-decoration: none;
    }

    .btn-primary-hero:hover {
      background-color: #BE123C;
      transform: translateY(-2px);
    }

    .btn-outline-playful {
      background-color: #FFFFFF;
      border: 2px solid #E2E8F0;
      color: var(--on-surface);
      font-weight: 700;
      padding: 0.875rem 1.75rem;
      border-radius: 1rem;
      transition: all 0.2s ease;
      text-decoration: none;
    }

    .btn-outline-playful:hover {
      border-color: #3B82F6;
      color: var(--primary-blue);
    }

    .badge-roi {
      background-color: #ECFDF5;
      color: #15803D;
      border: 1px solid #A7F3D0;
      border-radius: 9999px;
      padding: 0.35rem 0.85rem;
      font-size: 0.75rem;
      font-weight: 700;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
    }

    .badge-creative {
      background-color: #FDF2F8;
      color: #BE185D;
      border: 1px solid #FBCFE8;
      border-radius: 9999px;
      padding: 0.35rem 0.85rem;
      font-size: 0.75rem;
      font-weight: 700;
    }

    .badge-star {
      background-color: #FEFCE8;
      color: #B45309;
      border: 1px solid #FDE68A;
      border-radius: 9999px;
      padding: 0.35rem 0.85rem;
      font-size: 0.75rem;
      font-weight: 700;
    }

    .card-studio {
      background-color: #FFFFFF;
      border-radius: 1.5rem;
      border: 1.5px solid #F1F5F9;
      padding: 1.5rem;
      box-shadow: 0 8px 24px -4px rgba(15, 23, 42, 0.04);
      transition: all 0.3s ease;
    }

    .card-studio:hover {
      transform: translateY(-4px);
      box-shadow: 0 16px 32px -8px rgba(37, 99, 235, 0.15);
    }

    .card-top-seller {
      position: relative;
      overflow: hidden;
      border: 2px solid var(--bubblegum-pink);
    }

    .card-top-seller::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      height: 6px;
      background: linear-gradient(90deg, var(--secondary-crimson), var(--bubblegum-pink));
    }

    .input-studio {
      height: 52px;
      border-radius: 0.5rem;
      border: 1.5px solid #E2E8F0;
      background-color: #FFFFFF;
      color: var(--on-surface);
      padding: 0 1rem;
      font-size: 0.875rem;
      width: 100%;
      box-sizing: border-box;
      transition: all 0.2s ease;
    }

    .input-studio:focus {
      outline: none;
      border-color: var(--primary-blue);
      box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.15);
    }

    .wa-float-pod {
      position: fixed;
      bottom: 2rem;
      right: 2rem;
      width: 60px;
      height: 60px;
      border-radius: 9999px;
      background-color: var(--fresh-green);
      color: #FFFFFF;
      display: flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 20px 30px -6px rgba(16, 185, 129, 0.3);
      z-index: 100;
      text-decoration: none;
      transition: transform 0.2s ease;
    }

    .wa-float-pod:hover {
      transform: scale(1.08);
    }
  </style>
</head>
<body class="antialiased relative min-h-screen">

  <!-- HEADER / NAVBAR -->
  <header class="fixed top-0 left-0 right-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-200/60 shadow-[0_1px_8px_rgba(0,0,0,0.04)]">
    <div class="h-20 max-w-[1240px] mx-auto px-6 flex items-center justify-between gap-4">
      
      <!-- LOGO MARKETIVA -->
      <a class="flex items-center gap-2.5" href="index.php#beranda">
        <span class="font-extrabold text-xl tracking-tight">
          <span class="text-[#F43F5E]">Mar</span><span class="text-[#3B82F6]">ke</span><span class="text-[#10B981]">ti</span><span class="text-[#F59E0B]">va</span>
        </span>
      </a>

      <!-- MENU NAVBAR -->
      <nav class="hidden md:flex items-center gap-8 text-sm font-semibold text-slate-700">
        <a class="hover:text-[#E11D48] transition-colors flex items-center gap-1.5" href="index.php#beranda">
          <span class="material-symbols-outlined text-[18px]">home</span>
          <span>Home</span>
        </a>
        
        <a class="hover:text-[#E11D48] transition-colors flex items-center gap-1.5" href="about.php">
          <span class="material-symbols-outlined text-[18px]">info</span>
          <span>Tentang Kami</span>
        </a>

        <a class="hover:text-[#E11D48] transition-colors flex items-center gap-1.5" href="index.php#keunggulan">
          <span class="material-symbols-outlined text-[18px]">verified</span>
          <span>Keunggulan</span>
        </a>
        
        <a class="hover:text-[#E11D48] transition-colors flex items-center gap-1.5" href="catalog.php">
          <span class="material-symbols-outlined text-[18px]">grid_view</span>
          <span>Catalog Layanan</span>
        </a>
      </nav>

      <!-- DINAMIS: TOMBOL MASUK ATAU STATUS AKUN AKTIF -->
      <div class="flex items-center gap-3">
        <?php if (isset($_SESSION['user'])): ?>
          <?php $dashboard_url = ($_SESSION['role'] === 'admin') ? 'admin-dashboard.php' : 'user-dashboard.php'; ?>
          <a href="<?= $dashboard_url ?>" class="btn-primary-hero text-xs py-2 px-4 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[18px]">dashboard</span>
            <span>Halo, <?= htmlspecialchars($_SESSION['user']); ?></span>
          </a>
        <?php else: ?>
          <a href="login.php" class="btn-outline-playful text-xs py-2 px-4 flex items-center gap-1.5 border-slate-200 hover:border-rose-300">
            <span class="material-symbols-outlined text-[18px] text-[#E11D48]">account_circle</span>
            <span class="hidden sm:inline">Masuk</span>
          </a>
          <a href="login.php" class="btn-primary-hero text-xs py-2 px-4 flex items-center gap-1.5">
            <span class="material-symbols-outlined text-[18px]">person_add</span>
            <span>Daftar</span>
          </a>
        <?php endif; ?>
      </div>

    </div>
  </header>

  <main class="pt-20">
    
    <!-- HERO SECTION (DINAMIS DARI DATABASE) -->
    <section class="relative overflow-hidden py-16 lg:py-24" id="beranda">
      <div class="ambient-glow-pink -top-20 left-10"></div>
      <div class="ambient-glow-blue top-10 right-10"></div>
      
      <div class="max-w-[1240px] mx-auto px-6 relative z-10 text-center">
        <?php if (!empty($hero_slides)): ?>
          <?php foreach ($hero_slides as $index => $slide): ?>
            <div class="<?= $index === 0 ? '' : 'hidden'; ?>">
              <div class="badge-roi mb-6">
                <span class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></span>
                <span><?= htmlspecialchars($slide['badge'] ?: '🚀 Partner Pertumbuhan Digital UMKM #1 Indonesia'); ?></span>
              </div>

              <h1 class="text-4xl md:text-6xl font-extrabold tracking-tight text-slate-900 max-w-4xl mx-auto leading-tight mb-6">
                <?= htmlspecialchars($slide['judul']); ?>
              </h1>

              <p class="text-slate-600 text-lg max-w-2xl mx-auto mb-8 leading-relaxed">
                <?= htmlspecialchars($slide['deskripsi']); ?>
              </p>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

        <div class="flex flex-col sm:flex-row items-center justify-center gap-4 mb-10">
          <a class="btn-primary-hero w-full sm:w-auto" href="#kontak">
            <span>Konsultasi Gratis</span>
            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
          </a>
          <a class="btn-outline-playful w-full sm:w-auto inline-flex items-center justify-center gap-2" href="catalog.php">
            <span>Lihat Layanan</span>
            <span class="material-symbols-outlined text-[18px]">expand_more</span>
          </a>
        </div>

        <div class="space-y-3">
          <p class="text-xs uppercase font-bold text-slate-400 tracking-wider">Dipercaya 50+ UMKM di Seluruh Nusantara</p>
          <div class="flex flex-wrap items-center justify-center gap-2">
            <span class="px-3 py-1 rounded-full bg-amber-50 text-amber-800 border border-amber-200 text-xs font-semibold">🍜 Kuliner Lokal</span>
            <span class="px-3 py-1 rounded-full bg-sky-50 text-sky-700 border border-sky-200 text-xs font-semibold">☕ Kopi Nusantara</span>
            <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-xs font-semibold">🪵 Kriya Kreatif</span>
            <span class="px-3 py-1 rounded-full bg-pink-50 text-pink-700 border border-pink-200 text-xs font-semibold">🌿 Skincare Herbal</span>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION 1: KEUNGGULAN (DINAMIS DARI DATABASE) -->
    <section class="py-20 bg-slate-50/70 border-y border-slate-200/60" id="keunggulan">
      <div class="max-w-[1240px] mx-auto px-6">
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
          
          <div class="lg:col-span-5 flex flex-col justify-between space-y-6">
            <div>
              <?php if ($title_keunggulan): ?>
                <span class="px-3 py-1 rounded-full bg-emerald-100/80 text-emerald-800 text-xs font-bold uppercase tracking-wider mb-4 inline-block">
                  <?= htmlspecialchars($title_keunggulan['badge']); ?>
                </span>
                <h2 class="text-3xl lg:text-4xl font-extrabold text-slate-900 leading-tight tracking-tight mb-4">
                  <?= htmlspecialchars($title_keunggulan['judul']); ?>
                </h2>
                <p class="text-slate-600 text-sm leading-relaxed">
                  <?= htmlspecialchars($title_keunggulan['deskripsi']); ?>
                </p>
              <?php endif; ?>
            </div>

            <div class="p-6 rounded-2xl bg-white border border-emerald-300/80 shadow-sm border-l-4 border-l-emerald-500">
              <div class="flex items-center gap-2.5 mb-2 text-emerald-700 font-bold text-base">
                <span class="material-symbols-outlined text-[22px]">verified</span>
                <h3>Jaminan Komunikasi Humanis</h3>
              </div>
              <p class="text-slate-600 text-xs leading-relaxed">
                Setiap klien didampingi Account Strategist khusus yang mudah dihubungi kapan saja via grup WhatsApp tanpa sistem tiket yang berbelit.
              </p>
            </div>
          </div>

          <div class="lg:col-span-7 grid grid-cols-1 sm:grid-cols-2 gap-6">
            <?php if (!empty($keunggulan_list)): ?>
              <?php foreach ($keunggulan_list as $kg): ?>
                <div class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-sm border-t-4 border-t-<?= htmlspecialchars($kg['warna']); ?>-400 flex flex-col justify-between">
                  <div>
                    <div class="w-10 h-10 rounded-xl bg-<?= htmlspecialchars($kg['warna']); ?>-50 text-<?= htmlspecialchars($kg['warna']); ?>-600 flex items-center justify-center mb-4">
                      <span class="material-symbols-outlined text-[20px]"><?= htmlspecialchars($kg['ikon']); ?></span>
                    </div>
                    <h3 class="font-extrabold text-slate-900 text-lg mb-2"><?= htmlspecialchars($kg['judul']); ?></h3>
                    <p class="text-slate-600 text-xs leading-relaxed"><?= htmlspecialchars($kg['deskripsi']); ?></p>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>

        </div>
      </div>
    </section>

    <!-- CATALOG LAYANAN -->
    <section class="py-24" id="layanan">
      <div class="max-w-[1240px] mx-auto px-6">
        <div class="max-w-3xl mx-auto text-center space-y-3 mb-16">
          <span class="badge-creative inline-block">Catalog Layanan</span>
          <h2 class="text-3xl font-extrabold text-slate-900">Layanan Digital Marketing untuk UMKM</h2>
          <p class="text-slate-600 text-base">Pilihan paket fleksibel, transparan, dan terukur yang dirancang khusus untuk meningkatkan omset bisnis.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="card-studio flex flex-col justify-between">
            <div class="space-y-4">
              <div class="w-12 h-12 rounded-xl bg-sky-50 text-sky-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-[28px]">share</span>
              </div>
              <h3 class="font-bold text-xl text-slate-900">Social Media Management</h3>
              <p class="text-slate-600 text-sm leading-relaxed">Mengelola akun Instagram & TikTok agar selalu aktif, interaktif, dan terlihat profesional.</p>
            </div>
            <a class="mt-6 font-bold text-sm text-sky-600 hover:underline flex items-center gap-1" href="catalog.php">
              <span>Konsultasi Paket Ini</span>
              <span class="material-symbols-outlined text-[16px]">chevron_right</span>
            </a>
          </div>

          <div class="card-studio card-top-seller flex flex-col justify-between">
            <div class="space-y-4">
              <div class="flex items-center justify-between">
                <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-500 flex items-center justify-center">
                  <span class="material-symbols-outlined text-[28px]">movie</span>
                </div>
                <span class="badge-creative">Paling Populer</span>
              </div>
              <h3 class="font-bold text-xl text-slate-900">Content Creation & Reels</h3>
              <p class="text-slate-600 text-sm leading-relaxed">Memproduksi foto produk, video pendek reels, dan copywriting persuasif bernilai jual.</p>
            </div>
            <a class="mt-6 font-bold text-sm text-rose-600 hover:underline flex items-center gap-1" href="catalog.php">
              <span>Konsultasi Paket Ini</span>
              <span class="material-symbols-outlined text-[16px]">chevron_right</span>
            </a>
          </div>

          <div class="card-studio flex flex-col justify-between">
            <div class="space-y-4">
              <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-[28px]">ads_click</span>
              </div>
              <h3 class="font-bold text-xl text-slate-900">Digital Advertising (Ads)</h3>
              <p class="text-slate-600 text-sm leading-relaxed">Iklan berbayar Meta & TikTok Ads untuk menjangkau pembeli potensial secara instan.</p>
            </div>
            <a class="mt-6 font-bold text-sm text-amber-600 hover:underline flex items-center gap-1" href="catalog.php">
              <span>Konsultasi Paket Ini</span>
              <span class="material-symbols-outlined text-[16px]">chevron_right</span>
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- SECTION 2: 4 LANGKAH PROSES KERJA -->
    <section class="py-20 bg-slate-50/70 border-y border-slate-200/60" id="proses">
      <div class="max-w-[1240px] mx-auto px-6">
        
        <div class="max-w-3xl mx-auto text-center space-y-3 mb-16">
          <h2 class="text-3xl lg:text-4xl font-extrabold text-slate-900 tracking-tight">
            4 Langkah Mudah Memulai Pertumbuhan Digital
          </h2>
          <p class="text-slate-600 text-sm sm:text-base">
            Transparan dari sesi pertama hingga laporan evaluasi berkala bulanan.
          </p>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
          
          <div class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-6">
            <div class="flex items-center justify-between">
              <span class="text-4xl font-black text-sky-300 font-display">01</span>
              <div class="w-9 h-9 rounded-full bg-sky-50 text-sky-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-[18px]">forum</span>
              </div>
            </div>
            <div>
              <h3 class="font-extrabold text-slate-900 text-lg mb-2">Konsultasi</h3>
              <p class="text-slate-600 text-xs leading-relaxed">
                Mendengarkan kisah produk Anda, mengenali target audiens, dan mengidentifikasi kendala utama dalam penjualan saat ini.
              </p>
            </div>
          </div>

          <div class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-6">
            <div class="flex items-center justify-between">
              <span class="text-4xl font-black text-amber-300 font-display">02</span>
              <div class="w-9 h-9 rounded-full bg-amber-50 text-amber-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-[18px]">analytics</span>
              </div>
            </div>
            <div>
              <h3 class="font-extrabold text-slate-900 text-lg mb-2">Analisis Data</h3>
              <p class="text-slate-600 text-xs leading-relaxed">
                Menganalisis profil kompetitor sejenis, celah kata kunci pencarian, serta audit akun sosial media yang sudah berjalan.
              </p>
            </div>
          </div>

          <div class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-6">
            <div class="flex items-center justify-between">
              <span class="text-4xl font-black text-pink-300 font-display">03</span>
              <div class="w-9 h-9 rounded-full bg-pink-50 text-pink-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-[18px]">rocket_launch</span>
              </div>
            </div>
            <div>
              <h3 class="font-extrabold text-slate-900 text-lg mb-2">Strategi & Eksekusi</h3>
              <p class="text-slate-600 text-xs leading-relaxed">
                Membuat konten kreatif, menjadwalkan postingan, mengoptimasi akun, dan menyalakan kampanye iklan tertarget.
              </p>
            </div>
          </div>

          <div class="p-6 rounded-2xl bg-white border border-slate-200/80 shadow-sm flex flex-col justify-between space-y-6">
            <div class="flex items-center justify-between">
              <span class="text-4xl font-black text-emerald-300 font-display">04</span>
              <div class="w-9 h-9 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center">
                <span class="material-symbols-outlined text-[18px]">show_chart</span>
              </div>
            </div>
            <div>
              <h3 class="font-extrabold text-slate-900 text-lg mb-2">Evaluasi & Scale</h3>
              <p class="text-slate-600 text-xs leading-relaxed">
                Mengirimkan laporan mingguan & bulanan yang mudah dimengerti, mengevaluasi angka konversi, dan melipatgandakan strategi paling efektif.
              </p>
            </div>
          </div>

        </div>

      </div>
    </section>

    <!-- PAKET HARGA (DINAMIS DARI DATABASE) -->
    <section class="py-20 bg-white" id="harga">
      <div class="max-w-[1240px] mx-auto px-6">
        <div class="max-w-3xl mx-auto text-center space-y-3 mb-16">
          <span class="badge-roi inline-block">Paket Spesial</span>
          <h2 class="text-3xl font-extrabold text-slate-900">PAKET LAYANAN UNTUK UMKM</h2>
          <p class="text-slate-600 text-base">Pilih paket yang sesuai dengan skala dan target bisnis Anda.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 items-stretch">
          <?php if (!empty($paket_harga_list)): ?>
            <?php foreach ($paket_harga_list as $pkt): 
              $is_rekom = ($pkt['rekomendasi'] == 1);
              $fitur_arr = explode(',', $pkt['fitur']);
            ?>
              <div class="card-studio flex flex-col justify-between <?= $is_rekom ? 'card-top-seller scale-[1.02]' : ''; ?>">
                <div>
                  <?php if ($is_rekom): ?>
                    <div class="flex items-center justify-between mb-1">
                      <h3 class="font-bold text-lg text-slate-900"><?= htmlspecialchars($pkt['nama_paket']); ?></h3>
                      <span class="badge-star">⭐ Rekomendasi</span>
                    </div>
                  <?php else: ?>
                    <h3 class="font-bold text-lg text-slate-900"><?= htmlspecialchars($pkt['nama_paket']); ?></h3>
                  <?php endif; ?>

                  <p class="text-rose-600 text-xs font-semibold mt-1"><?= htmlspecialchars($pkt['sub_judul']); ?></p>
                  <div class="my-4">
                    <span class="text-3xl font-extrabold <?= $is_rekom ? 'text-rose-600' : 'text-slate-900'; ?>">
                      Rp<?= number_format($pkt['harga'], 0, ',', '.'); ?>
                    </span>
                    <span class="text-slate-400 text-xs">/ bulan</span>
                  </div>
                  <ul class="space-y-2 text-xs text-slate-600 pt-3 border-t border-slate-100">
                    <?php foreach ($fitur_arr as $ft): ?>
                      <li class="flex items-center gap-2">✓ <?= htmlspecialchars(trim($ft)); ?></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
                <a href="<?= htmlspecialchars($pkt['link_tombol']); ?>" class="<?= $is_rekom ? 'btn-primary-hero text-xs text-center justify-center mt-6' : 'btn-outline-playful text-xs text-center mt-6 block'; ?>">
                  Pilih <?= htmlspecialchars($pkt['nama_paket']); ?>
                </a>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>
        </div>
      </div>
    </section>

    <!-- SECTION 3: BANNER CTA CHECKOUT (DINAMIS DARI DATABASE) -->
    <section class="py-16">
      <div class="max-w-[1240px] mx-auto px-6">
        <div class="rounded-3xl bg-gradient-to-br from-[#0a1b33] via-[#111f3d] to-[#2a132e] text-white p-8 lg:p-14 shadow-2xl relative overflow-hidden flex flex-col lg:flex-row lg:items-center justify-between gap-8 border border-slate-800">
          
          <div class="max-w-xl space-y-4 relative z-10">
            <span class="px-3.5 py-1.5 rounded-full bg-white/10 text-rose-300 text-xs font-bold border border-white/10 backdrop-blur-md inline-block">
              <?= htmlspecialchars($cta_data['badge'] ?? 'Aktivasi Instan Hari Ini'); ?>
            </span>
            <h2 class="text-3xl lg:text-4xl font-extrabold tracking-tight leading-tight">
              <?= htmlspecialchars($cta_data['judul'] ?? 'Siap Melipatgandakan Omset Penjualan Brand Anda?'); ?>
            </h2>
            <p class="text-slate-300 text-xs sm:text-sm leading-relaxed">
              <?= htmlspecialchars($cta_data['deskripsi'] ?? 'Pilih paket favorit Anda sekarang.'); ?>
            </p>
          </div>

          <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-4 relative z-10 shrink-0">
            <a href="<?= htmlspecialchars($cta_data['link_checkout'] ?? 'catalog.php'); ?>" class="px-6 py-4 rounded-xl bg-gradient-to-r from-rose-500 to-pink-600 hover:from-rose-600 hover:to-pink-700 text-white font-bold text-sm shadow-lg shadow-rose-500/30 transition-all text-center flex items-center justify-center gap-2">
              <span class="material-symbols-outlined text-[18px]">shopping_cart</span>
              <span>Mulai Checkout Sekarang</span>
            </a>
            <a href="<?= htmlspecialchars($cta_data['link_wa'] ?? 'https://wa.me/6281234567890'); ?>" target="_blank" class="px-6 py-4 rounded-xl bg-white/10 hover:bg-white/15 text-white font-bold text-sm border border-white/20 backdrop-blur-md transition-all text-center flex items-center justify-center gap-2">
              <span class="material-symbols-outlined text-[18px]">chat</span>
              <span>Tanya CS Dulu</span>
            </a>
          </div>

        </div>
      </div>
    </section>

    <!-- FORM KONSULTASI (TERHUBUNG KE DATABASE) -->
    <section class="py-16" id="kontak">
      <div class="max-w-[800px] mx-auto px-6 text-center">
        <span class="badge-creative mb-3 inline-block">Hubungi Kami</span>
        <h2 class="text-3xl font-extrabold text-slate-900 mb-2">Mulai Konsultasi Strategi Brand Anda</h2>
        <p class="text-slate-600 text-sm mb-8">Tim Marketiva siap mendampingi operasional pemasaran bisnis Anda.</p>

        <?php if (!empty($pesan_sukses)): ?>
          <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center justify-center gap-2">
            <span class="material-symbols-outlined text-[20px]">check_circle</span> <?= htmlspecialchars($pesan_sukses); ?>
          </div>
        <?php endif; ?>

        <form method="POST" action="index.php#kontak" class="card-studio text-left space-y-4">
          <input type="hidden" name="action_kirim_konsultasi" value="1">
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Lengkap / Nama UMKM</label>
            <input type="text" name="nama_umkm" required placeholder="Contoh: Hanna - Kebaya Wisuda" class="input-studio">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nomor WhatsApp</label>
            <input type="tel" name="whatsapp" required placeholder="081234567890" class="input-studio">
          </div>
          <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kebutuhan Layanan</label>
            <select name="kebutuhan_layanan" class="input-studio">
              <option>Social Media Management</option>
              <option>Content Creation & Video Reels</option>
              <option>Digital Advertising (Meta/TikTok Ads)</option>
              <option>Konsultasi Branding UMKM</option>
            </select>
          </div>
          <button type="submit" class="btn-primary-hero w-full justify-center text-sm py-3.5">
            <span>Kirim Pesan Konsultasi</span>
            <span class="material-symbols-outlined text-[18px]">send</span>
          </button>
        </form>
      </div>
    </section>

  </main>

  <!-- FLOATING WHATSAPP -->
  <a href="https://wa.me/6281234567890" target="_blank" class="wa-float-pod" title="Konsultasi Langsung via WhatsApp">
    <span class="material-symbols-outlined text-[32px]">chat</span>
  </a>

  <!-- FOOTER (DINAMIS DARI DATABASE) -->
  <footer class="bg-slate-50/80 border-t border-slate-200/80 pt-16 pb-12 text-slate-600 text-xs">
    <div class="max-w-[1240px] mx-auto px-6">
      
      <div class="grid grid-cols-1 md:grid-cols-12 gap-10 pb-12 border-b border-slate-200">
        
        <div class="md:col-span-5 space-y-4">
          <a class="flex items-center gap-2.5" href="index.php#beranda">
            <span class="font-extrabold text-xl tracking-tight">
              <span class="text-[#F43F5E]">Mar</span><span class="text-[#3B82F6]">ke</span><span class="text-[#10B981]">ti</span><span class="text-[#F59E0B]">va</span>
            </span>
          </a>
          <p class="font-bold text-slate-800 text-sm">
            Partner Digital Marketing untuk Pertumbuhan UMKM.
          </p>
          <p class="text-slate-500 leading-relaxed max-w-sm">
            <?= htmlspecialchars($settings['deskripsi_footer'] ?? 'Membantu bisnis lokal dan pelaku UMKM Indonesia.'); ?>
          </p>
        </div>

        <div class="md:col-span-3 space-y-3">
          <h4 class="font-extrabold text-slate-900 text-sm">Tautan Cepat</h4>
          <ul class="space-y-2 text-slate-600">
            <li><a href="#beranda" class="hover:text-rose-600 transition-colors">Beranda</a></li>
            <li><a href="about.php" class="hover:text-rose-600 transition-colors">Tentang Kami</a></li>
            <li><a href="catalog.php" class="hover:text-rose-600 transition-colors">Layanan</a></li>
            <li><a href="#keunggulan" class="hover:text-rose-600 transition-colors">Keunggulan</a></li>
            <li><a href="#kontak" class="hover:text-rose-600 transition-colors">Kontak</a></li>
          </ul>
        </div>

        <div class="md:col-span-4 space-y-4">
          <h4 class="font-extrabold text-slate-900 text-sm">Hubungi Kami</h4>
          <ul class="space-y-2.5 text-slate-600">
            <li class="flex items-center gap-2.5">
              <span class="material-symbols-outlined text-[18px] text-rose-500">mail</span>
              <span><?= htmlspecialchars($settings['email'] ?? 'halo@marketiva.co.id'); ?></span>
            </li>
            <li class="flex items-center gap-2.5">
              <span class="material-symbols-outlined text-[18px] text-sky-500">call</span>
              <span><?= htmlspecialchars($settings['telepon'] ?? '+62 812-3456-7890'); ?></span>
            </li>
            <li class="flex items-center gap-2.5">
              <span class="material-symbols-outlined text-[18px] text-emerald-500">location_on</span>
              <span><?= htmlspecialchars($settings['alamat'] ?? 'Makassar, Indonesia'); ?></span>
            </li>
          </ul>
        </div>

      </div>

      <div class="pt-8 flex flex-col sm:flex-row items-center justify-between gap-4 text-slate-400">
        <p><?= htmlspecialchars($settings['copyright_text'] ?? '© 2026 Marketiva. All Rights Reserved.'); ?></p>
        <div class="flex items-center gap-6">
          <a href="#" class="hover:text-slate-600 transition-colors">Kebijakan Privasi</a>
          <a href="#" class="hover:text-slate-600 transition-colors">Syarat & Ketentuan</a>
        </div>
      </div>

    </div>
  </footer>

</body>
</html>