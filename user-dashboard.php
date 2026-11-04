<?php
require_once 'koneksi.php';

// Proteksi: Pastikan user sudah login
if (!isset($_SESSION['user']) || $_SESSION['role'] === 'admin') {
    header("Location: login.php");
    exit();
}

$current_user = $_SESSION['user'];
$user_id = $_SESSION['user_id'] ?? null;
$current_email = $_SESSION['email'] ?? '';

$update_message = '';
$update_error = '';
$pass_message = '';
$pass_error = '';

// 1. KETIKA FORM SIMPAN PROFIL DI-SUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $nama_baru = trim($_POST['inputProfileName'] ?? '');
    $umkm_baru = trim($_POST['inputProfileUmkm'] ?? '');
    $phone_baru = trim($_POST['inputProfilePhone'] ?? '');
    $email_baru = trim($_POST['inputProfileEmail'] ?? '');
    $address_baru = trim($_POST['inputProfileAddress'] ?? '');

    if (!empty($nama_baru) && !empty($email_baru)) {
        if ($user_id) {
            $update_stmt = mysqli_prepare($conn, "UPDATE users SET nama_lengkap = ?, nama_umkm = ?, whatsapp = ?, email = ?, alamat = ? WHERE id = ?");
            mysqli_stmt_bind_param($update_stmt, "sssssi", $nama_baru, $umkm_baru, $phone_baru, $email_baru, $address_baru, $user_id);
        } else {
            $update_stmt = mysqli_prepare($conn, "UPDATE users SET nama_lengkap = ?, nama_umkm = ?, whatsapp = ?, email = ?, alamat = ? WHERE email = ?");
            mysqli_stmt_bind_param($update_stmt, "ssssss", $nama_baru, $umkm_baru, $phone_baru, $email_baru, $address_baru, $_SESSION['email']);
        }

        if (mysqli_stmt_execute($update_stmt)) {
            $_SESSION['user'] = $nama_baru;
            $_SESSION['email'] = $email_baru;
            $update_message = "Profil dan kelengkapan data usaha Anda berhasil diperbarui ke database!";
        } else {
            $update_error = "Gagal memperbarui database: " . mysqli_error($conn);
        }
    } else {
        $update_error = "Nama Lengkap dan Email tidak boleh kosong.";
    }
}

// 2. KETIKA FORM GANTI SANDI DI-SUBMIT
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    $sandi_lama = trim($_POST['currentPassword'] ?? '');
    $sandi_baru = trim($_POST['newPassword'] ?? '');
    $konfirmasi_baru = trim($_POST['confirmPassword'] ?? '');

    if (!empty($sandi_lama) && !empty($sandi_baru) && !empty($konfirmasi_baru)) {
        if ($sandi_baru === $konfirmasi_baru) {
            if ($user_id) {
                $check_stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE id = ?");
                mysqli_stmt_bind_param($check_stmt, "i", $user_id);
            } else {
                $check_stmt = mysqli_prepare($conn, "SELECT password FROM users WHERE email = ?");
                mysqli_stmt_bind_param($check_stmt, "s", $_SESSION['email']);
            }
            mysqli_stmt_execute($check_stmt);
            $check_res = mysqli_stmt_get_result($check_stmt);
            $user_row = mysqli_fetch_assoc($check_res);

            if ($user_row && $user_row['password'] === $sandi_lama) {
                if ($user_id) {
                    $up_pass = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE id = ?");
                    mysqli_stmt_bind_param($up_pass, "si", $sandi_baru, $user_id);
                } else {
                    $up_pass = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE email = ?");
                    mysqli_stmt_bind_param($up_pass, "ss", $sandi_baru, $_SESSION['email']);
                }

                if (mysqli_stmt_execute($up_pass)) {
                    $pass_message = "Kata sandi Anda berhasil diperbarui!";
                } else {
                    $pass_error = "Gagal memperbarui kata sandi di database.";
                }
            } else {
                $pass_error = "Kata sandi lama yang Anda masukkan salah!";
            }
        } else {
            $pass_error = "Konfirmasi kata sandi baru tidak cocok!";
        }
    } else {
        $pass_error = "Semua kolom form sandi wajib diisi!";
    }
}

// Ambil data profil terbaru dari database MySQL
if ($user_id) {
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
    mysqli_stmt_bind_param($stmt, "i", $user_id);
} else {
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE nama_lengkap = ? OR email = ?");
    mysqli_stmt_bind_param($stmt, "ss", $current_user, $current_user);
}
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$user_data = mysqli_fetch_assoc($result);

// 3. AMBIL DATA PESANAN DENGAN AMAN (Mencegah Fatal Error jika ada kolom/tabel bermasalah)
$count_progress = 0;
$count_history = 0;
$result_progress = false;
$result_history = false;

$check_table = mysqli_query($conn, "SHOW TABLES LIKE 'orders'");
if ($check_table && mysqli_num_rows($check_table) > 0) {
    // Ambil data berdasarkan user_id atau user_email
    $safe_email = mysqli_real_escape_string($conn, $current_email);
    $safe_id = intval($user_id);

    $query_progress = "SELECT * FROM orders WHERE (user_id = $safe_id OR user_email = '$safe_email') AND status != 'Selesai' ORDER BY id DESC";
    $result_progress = mysqli_query($conn, $query_progress);
    if ($result_progress) {
        $count_progress = mysqli_num_rows($result_progress);
    }

    $query_history = "SELECT * FROM orders WHERE (user_id = $safe_id OR user_email = '$safe_email') AND status = 'Selesai' ORDER BY id DESC";
    $result_history = mysqli_query($conn, $query_history);
    if ($result_history) {
        $count_history = mysqli_num_rows($result_history);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Pelanggan - Marketiva</title>

  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; }
    .card-studio { background-color: #FFFFFF; border-radius: 1rem; border: 1px solid #E2E8F0; padding: 1.75rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02); }
    .input-studio { height: 48px; border-radius: 0.75rem; border: 1.5px solid #CBD5E1; padding: 0 1rem; width: 100%; font-size: 0.875rem; background-color: #FFFFFF; color: #1E293B; transition: all 0.2s; }
    .input-studio:focus { outline: none; border-color: #E11D48; box-shadow: 0 0 0 3px rgba(225,29,72,0.1); }
    textarea.input-studio { height: auto; padding-top: 0.75rem; padding-bottom: 0.75rem; }
  </style>
</head>
<body class="min-h-screen flex flex-col justify-between">

  <!-- TOP NAVBAR -->
  <header class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-200">
    <div class="max-w-[1320px] mx-auto px-6 h-20 flex items-center justify-between">
      <a href="index.php" class="flex items-center gap-2.5">
        <span class="font-extrabold text-2xl tracking-tight">
          <span class="text-[#F43F5E]">Mar</span><span class="text-[#3B82F6]">ke</span><span class="text-[#10B981]">ti</span><span class="text-[#F59E0B]">va</span>
        </span>
      </a>

      <div class="flex items-center gap-4">
        <div class="flex items-center gap-3 bg-slate-50 border border-slate-200 px-4 py-2 rounded-2xl">
          <div class="w-8 h-8 rounded-xl bg-rose-100 text-[#E11D48] flex items-center justify-center font-bold text-xs">
            <?= strtoupper(substr($user_data['nama_lengkap'] ?? 'U', 0, 1)); ?>
          </div>
          <div>
            <p class="text-[10px] text-slate-400 font-bold uppercase">Masuk Sebagai</p>
            <p class="text-xs font-extrabold text-slate-800"><?= htmlspecialchars($user_data['nama_lengkap'] ?? $_SESSION['user']); ?></p>
          </div>
        </div>

        <a href="login.php?action=logout" class="flex items-center gap-1.5 px-4 py-2.5 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 font-bold text-xs transition-all">
          <span class="material-symbols-outlined text-[18px]">logout</span>
          <span>Keluar</span>
        </a>
      </div>
    </div>
  </header>

  <!-- DASHBOARD CONTAINER -->
  <main class="max-w-[1320px] mx-auto px-6 py-10 w-full flex-1">
    
    <?php if (!empty($update_message)): ?>
      <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2 shadow-sm">
        <span class="material-symbols-outlined text-[20px]">check_circle</span>
        <span><?= htmlspecialchars($update_message); ?></span>
      </div>
    <?php endif; ?>

    <?php if (!empty($pass_message)): ?>
      <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2 shadow-sm">
        <span class="material-symbols-outlined text-[20px]">check_circle</span>
        <span><?= htmlspecialchars($pass_message); ?></span>
      </div>
    <?php endif; ?>

    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
      
      <!-- SIDEBAR KIRI -->
      <div class="lg:col-span-3 card-studio space-y-4 p-4 flex flex-col justify-between">
        <div class="space-y-1.5">
          <div class="px-3 py-2">
            <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Menu Utama</p>
          </div>

          <button onclick="switchTab('profile')" id="btnTabProfile" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold bg-rose-50 text-[#E11D48] transition-all">
            <span class="material-symbols-outlined text-[20px]">badge</span>
            <span>Profil & Data Usaha</span>
          </button>

          <button onclick="switchTab('onprogress')" id="btnTabOnProgress" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all">
            <span class="material-symbols-outlined text-[20px]">hourglass_top</span>
            <span>Pesanan On Progress</span>
            <span class="ml-auto bg-slate-200 text-slate-700 text-[10px] px-2 py-0.5 rounded-full font-bold"><?= $count_progress; ?></span>
          </button>

          <button onclick="switchTab('history')" id="btnTabHistory" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all">
            <span class="material-symbols-outlined text-[20px]">history</span>
            <span>Riwayat Pesanan</span>
            <span class="ml-auto bg-slate-200 text-slate-700 text-[10px] px-2 py-0.5 rounded-full font-bold"><?= $count_history; ?></span>
          </button>

          <div class="pt-4 mt-4 border-t border-slate-100 space-y-1.5">
            <p class="px-3 pb-2 text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Navigasi & Layanan</p>
            
            <a href="catalog.php" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-blue-600 hover:bg-blue-50 transition-all">
              <span class="material-symbols-outlined text-[20px]">storefront</span>
              <span>Katalog Layanan</span>
            </a>

            <a href="https://wa.me/6281234567890" target="_blank" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-emerald-600 hover:bg-emerald-50 transition-all">
              <span class="material-symbols-outlined text-[20px]">support_agent</span>
              <span>Bantuan WhatsApp</span>
            </a>

            <button onclick="switchTab('security')" id="btnTabSecurity" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all">
              <span class="material-symbols-outlined text-[20px]">lock_reset</span>
              <span>Keamanan & Sandi</span>
            </button>
          </div>
        </div>

        <div class="p-3.5 rounded-2xl bg-slate-50 border border-slate-200 space-y-2 mt-6">
          <div class="flex items-center gap-2 text-slate-800">
            <span class="material-symbols-outlined text-rose-600 text-[18px]">verified</span>
            <p class="text-[11px] font-extrabold">Akun Terverifikasi</p>
          </div>
          <p class="text-[10px] text-slate-500 leading-relaxed">Nikmati layanan prioritas digital marketing bersama Marketiva.</p>
        </div>
      </div>

      <!-- KONTEN KANAN -->
      <div class="lg:col-span-9 space-y-6">
        
        <!-- TAB 1: PROFIL & DATA PRIBADI -->
        <div id="tabContentProfile" class="card-studio space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 pb-4 gap-2">
            <div>
              <h2 class="text-xl font-extrabold text-slate-900">Kelola Profil & Informasi UMKM</h2>
              <p class="text-slate-500 text-xs mt-0.5">Perbarui informasi identitas pribadi dan detail bisnis Anda.</p>
            </div>
            <span class="self-start sm:self-auto px-3 py-1.5 rounded-full bg-emerald-50 text-emerald-700 border border-emerald-200 text-[11px] font-bold flex items-center gap-1.5">
              <span class="w-2 h-2 rounded-full bg-emerald-500"></span> Terhubung ke Database
            </span>
          </div>

          <form method="POST" action="user-dashboard.php" class="space-y-5">
            <input type="hidden" name="update_profile" value="1">
            
            <div>
              <p class="text-xs font-extrabold text-slate-400 uppercase tracking-wider mb-3">1. Informasi Personal</p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Nama Lengkap</label>
                  <input name="inputProfileName" type="text" class="input-studio" value="<?= htmlspecialchars($user_data['nama_lengkap'] ?? ''); ?>" required>
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Alamat Email Aktif</label>
                  <input name="inputProfileEmail" type="email" class="input-studio" value="<?= htmlspecialchars($user_data['email'] ?? ''); ?>" required>
                </div>
              </div>
            </div>

            <div class="pt-2">
              <p class="text-xs font-extrabold text-slate-400 uppercase tracking-wider mb-3">2. Informasi Bisnis & Kontak Usaha</p>
              <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Nama Usaha / Brand / UMKM</label>
                  <input name="inputProfileUmkm" type="text" class="input-studio" value="<?= htmlspecialchars($user_data['nama_umkm'] ?? ''); ?>">
                </div>
                <div>
                  <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Nomor WhatsApp</label>
                  <input name="inputProfilePhone" type="tel" class="input-studio" value="<?= htmlspecialchars($user_data['whatsapp'] ?? ''); ?>">
                </div>
              </div>
            </div>

            <div class="pt-2">
              <p class="text-xs font-extrabold text-slate-400 uppercase tracking-wider mb-3">3. Alamat Lokasi Usaha</p>
              <div>
                <textarea name="inputProfileAddress" rows="3" class="input-studio"><?= htmlspecialchars($user_data['alamat'] ?? ''); ?></textarea>
              </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end">
              <button type="submit" class="px-6 py-3 rounded-xl bg-slate-900 text-white font-bold text-xs shadow-md">Simpan Perubahan Profil</button>
            </div>
          </form>
        </div>

        <!-- TAB 2: PESANAN ON PROGRESS -->
        <div id="tabContentOnProgress" class="card-studio space-y-6 hidden">
          <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <h2 class="text-xl font-extrabold text-slate-900">Pesanan Sedang Berjalan (On Progress)</h2>
            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-600 text-xs font-bold"><?= $count_progress; ?> Aktif</span>
          </div>

          <?php if ($count_progress > 0 && $result_progress): ?>
            <div class="space-y-4">
              <?php while ($row = mysqli_fetch_assoc($result_progress)): ?>
                <div class="p-5 rounded-2xl bg-slate-50 border border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                  <div class="space-y-1">
                    <span class="px-2.5 py-1 rounded-md bg-amber-100 text-amber-800 font-extrabold text-[10px] uppercase"><?= htmlspecialchars($row['status']); ?></span>
                    <h3 class="text-base font-extrabold text-slate-900 mt-1"><?= htmlspecialchars($row['nama_layanan']); ?></h3>
                    <p class="text-xs text-slate-500">Kategori: <?= htmlspecialchars($row['kategori'] ?? '-'); ?> | Harga: Rp <?= number_format($row['harga'] ?? 0, 0, ',', '.'); ?></p>
                  </div>
                  <a href="https://wa.me/6281234567890" target="_blank" class="px-4 py-2.5 rounded-xl bg-emerald-600 text-white font-bold text-xs text-center">Hubungi Tim</a>
                </div>
              <?php endwhile; ?>
            </div>
          <?php else: ?>
            <div class="text-center py-12 px-4 space-y-3 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
              <span class="material-symbols-outlined text-[48px] text-slate-300">hourglass_empty</span>
              <p class="text-sm font-bold text-slate-700">Belum ada pesanan aktif saat ini.</p>
              <a href="catalog.php" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#E11D48] text-white font-bold text-xs">Lihat Katalog Layanan</a>
            </div>
          <?php endif; ?>
        </div>

        <!-- TAB 3: RIWAYAT PESANAN -->
        <div id="tabContentHistory" class="card-studio space-y-6 hidden">
          <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <h2 class="text-xl font-extrabold text-slate-900">Riwayat Pesanan</h2>
            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-700 text-xs font-bold">Total: <?= $count_history; ?></span>
          </div>

          <?php if ($count_history > 0 && $result_history): ?>
            <div class="space-y-4">
              <?php while ($h_row = mysqli_fetch_assoc($result_history)): ?>
                <div class="p-5 rounded-2xl bg-emerald-50/50 border border-emerald-200 flex justify-between items-center">
                  <div>
                    <span class="text-[10px] font-extrabold text-emerald-800 uppercase bg-emerald-100 px-2 py-0.5 rounded">Selesai</span>
                    <h3 class="text-base font-extrabold text-slate-900 mt-1"><?= htmlspecialchars($h_row['nama_layanan']); ?></h3>
                  </div>
                  <span class="text-xs font-bold text-emerald-700">✓ Selesai</span>
                </div>
              <?php endwhile; ?>
            </div>
          <?php else: ?>
            <div class="text-center py-12 px-4 space-y-2 bg-slate-50/50 rounded-2xl border border-dashed border-slate-200">
              <p class="text-xs font-bold text-slate-600">Belum ada riwayat transaksi yang selesai.</p>
            </div>
          <?php endif; ?>
        </div>

        <!-- TAB 4: KEAMANAN & SANDI -->
        <div id="tabContentSecurity" class="card-studio space-y-6 hidden">
          <div class="border-b border-slate-100 pb-4">
            <h2 class="text-xl font-extrabold text-slate-900">Keamanan & Ubah Kata Sandi</h2>
          </div>
          <form method="POST" action="user-dashboard.php" class="space-y-4 max-w-xl">
            <input type="hidden" name="update_password" value="1">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Kata Sandi Lama</label>
              <input type="password" name="currentPassword" class="input-studio" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Kata Sandi Baru</label>
              <input type="password" name="newPassword" class="input-studio" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Konfirmasi Sandi Baru</label>
              <input type="password" name="confirmPassword" class="input-studio" required>
            </div>
            <button type="submit" class="px-6 py-3 rounded-xl bg-indigo-600 text-white font-bold text-xs">Perbarui Kata Sandi</button>
          </form>
        </div>

      </div>
    </div>
  </main>

  <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-400">
    © 2026 Marketiva Digital Agency. Member Portal Dashboard.
  </footer>

  <script>
    function switchTab(tabName) {
      document.getElementById('tabContentProfile').classList.add('hidden');
      document.getElementById('tabContentOnProgress').classList.add('hidden');
      document.getElementById('tabContentHistory').classList.add('hidden');
      document.getElementById('tabContentSecurity').classList.add('hidden');

      document.getElementById('btnTabProfile').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all";
      document.getElementById('btnTabOnProgress').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all";
      document.getElementById('btnTabHistory').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all";
      document.getElementById('btnTabSecurity').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all";

      if (tabName === 'profile') {
        document.getElementById('tabContentProfile').classList.remove('hidden');
        document.getElementById('btnTabProfile').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold bg-rose-50 text-[#E11D48] transition-all";
      } else if (tabName === 'onprogress') {
        document.getElementById('tabContentOnProgress').classList.remove('hidden');
        document.getElementById('btnTabOnProgress').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold bg-amber-50 text-amber-800 transition-all";
      } else if (tabName === 'history') {
        document.getElementById('tabContentHistory').classList.remove('hidden');
        document.getElementById('btnTabHistory').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold bg-slate-100 text-slate-900 transition-all";
      } else if (tabName === 'security') {
        document.getElementById('tabContentSecurity').classList.remove('hidden');
        document.getElementById('btnTabSecurity').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 transition-all";
      }
    }
  </script>
</body>
</html>