<?php
require_once 'koneksi.php';

if (!isset($_SESSION['user']) || ($_SESSION['role'] ?? '') !== 'admin') {
    header("Location: login.php");
    exit();
}

$admin_name = $_SESSION['user'];
$msg_success = '';
$msg_error = '';

// Fungsi helper mencatat log aktivitas
function recordLog($conn, $admin, $aktivitas) {
    $stmt = $conn->prepare("INSERT INTO activity_logs (admin_name, aktivitas) VALUES (?, ?)");
    $stmt->bind_param("ss", $admin, $aktivitas);
    $stmt->execute();
    $stmt->close();
}

// 1. PROSES TAMBAH KATEGORI BARU
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_add_category'])) {
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');
    if (!empty($nama_kategori)) {
        $query_cat = "INSERT INTO categories (nama_kategori) VALUES ('$nama_kategori')";
        if (mysqli_query($conn, $query_cat)) {
            $msg_success = "Kategori baru berhasil ditambahkan!";
            recordLog($conn, $admin_name, "Menambahkan kategori baru: $nama_kategori");
        } else {
            $msg_error = "Gagal menambah kategori: " . mysqli_error($conn);
        }
    } else {
        $msg_error = "Nama kategori tidak boleh kosong!";
    }
}

// 2. PROSES TAMBAH ATAU EDIT LAYANAN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_service'])) {
    $service_id   = intval($_POST['service_id'] ?? 0);
    $nama_layanan = trim($_POST['nama_layanan'] ?? '');
    $kategori_id  = intval($_POST['kategori_id'] ?? 1);
    $harga        = floatval($_POST['harga'] ?? 0);
    $deskripsi    = trim($_POST['deskripsi'] ?? '');
    $gambar_path  = trim($_POST['old_gambar'] ?? '');

    if (isset($_FILES['gambar_file']) && $_FILES['gambar_file']['error'] === UPLOAD_ERR_OK) {
        $file_tmp   = $_FILES['gambar_file']['tmp_name'];
        $file_name  = time() . '_' . basename($_FILES['gambar_file']['name']);
        $upload_dir = 'uploads/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $target_file = $upload_dir . $file_name;
        if (move_uploaded_file($file_tmp, $target_file)) {
            if (!empty($gambar_path) && file_exists($gambar_path)) {
                @unlink($gambar_path);
            }
            $gambar_path = $target_file;
        }
    }

    if (!empty($nama_layanan) && $harga > 0) {
        if ($service_id > 0) {
            $query_update = "UPDATE services SET nama_layanan='$nama_layanan', kategori_id='$kategori_id', harga='$harga', deskripsi='$deskripsi', gambar='$gambar_path' WHERE id=$service_id";
            if (mysqli_query($conn, $query_update)) {
                $msg_success = "Layanan berhasil diperbarui!";
                recordLog($conn, $admin_name, "Memperbarui layanan: $nama_layanan");
            } else {
                $msg_error = "Gagal memperbarui layanan: " . mysqli_error($conn);
            }
        } else {
            $query_insert = "INSERT INTO services (nama_layanan, kategori_id, harga, deskripsi, gambar) VALUES ('$nama_layanan', '$kategori_id', '$harga', '$deskripsi', '$gambar_path')";
            if (mysqli_query($conn, $query_insert)) {
                $msg_success = "Layanan baru berhasil ditambahkan!";
                recordLog($conn, $admin_name, "Menambah layanan baru: $nama_layanan");
            } else {
                $msg_error = "Gagal menambah layanan: " . mysqli_error($conn);
            }
        }
    } else {
        $msg_error = "Nama layanan dan harga wajib diisi dengan benar!";
    }
}

// 3. PROSES UPDATE STATUS PESANAN
if (isset($_GET['update_order_status']) && isset($_GET['order_id'])) {
    $o_id = intval($_GET['order_id']);
    $o_status = $_GET['update_order_status'];
    mysqli_query($conn, "UPDATE orders SET status='$o_status' WHERE id=$o_id");
    recordLog($conn, $admin_name, "Mengubah status pesanan #$o_id menjadi $o_status");
    header("Location: admin-dashboard.php");
    exit();
}

// 4. PROSES HAPUS LAYANAN
if (isset($_GET['delete_service'])) {
    $id_del = intval($_GET['delete_service']);
    $get_img = mysqli_query($conn, "SELECT nama_layanan, gambar FROM services WHERE id = $id_del");
    if ($row_img = mysqli_fetch_assoc($get_img)) {
        if (!empty($row_img['gambar']) && file_exists($row_img['gambar'])) {
            @unlink($row_img['gambar']);
        }
        recordLog($conn, $admin_name, "Menghapus layanan: " . $row_img['nama_layanan']);
    }
    mysqli_query($conn, "DELETE FROM services WHERE id = $id_del");
    header("Location: admin-dashboard.php");
    exit();
}

// 5. PROSES UPDATE PROFIL ADMIN
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_admin'])) {
    $new_name = trim($_POST['admin_name'] ?? '');
    if (!empty($new_name)) {
        $_SESSION['user'] = $new_name;
        $admin_name = $new_name;
        $msg_success = "Profil administrator berhasil diperbarui!";
        recordLog($conn, $admin_name, "Memperbarui nama profil admin.");
    } else {
        $msg_error = "Nama administrator tidak boleh kosong!";
    }
}

// 6. PROSES UPDATE KONTEN "TENTANG KAMI"[cite: 3]
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_update_about'])) {
    $judul     = trim($_POST['about_judul'] ?? '');
    $deskripsi = trim($_POST['about_deskripsi'] ?? '');
    $visi      = trim($_POST['about_visi'] ?? '');
    $misi      = trim($_POST['about_misi'] ?? '');

    if (!empty($judul) && !empty($deskripsi)) {
        $check_about = mysqli_query($conn, "SELECT id FROM about_us WHERE id = 1");
        if ($check_about && mysqli_num_rows($check_about) > 0) {
            $stmt_ab = $conn->prepare("UPDATE about_us SET judul = ?, deskripsi = ?, visi = ?, misi = ? WHERE id = 1");
            $stmt_ab->bind_param("ssss", $judul, $deskripsi, $visi, $misi);
            $stmt_ab->execute();
            $stmt_ab->close();
        } else {
            $stmt_ab = $conn->prepare("INSERT INTO about_us (id, judul, deskripsi, visi, misi) VALUES (1, ?, ?, ?, ?)");
            $stmt_ab->bind_param("ssss", $judul, $deskripsi, $visi, $misi);
            $stmt_ab->execute();
            $stmt_ab->close();
        }
        $msg_success = "Konten 'Tentang Kami' berhasil diperbarui!";
        recordLog($conn, $admin_name, "Memperbarui halaman Tentang Kami.");
    } else {
        $msg_error = "Judul dan Deskripsi Tentang Kami tidak boleh kosong!";
    }
}

// 7. PROSES TAMBAH / EDIT HERO SLIDE[cite: 3, 6]
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_slide'])) {
    $slide_id  = intval($_POST['slide_id'] ?? 0);
    $badge     = trim($_POST['badge'] ?? '');
    $judul     = trim($_POST['judul'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    if (!empty($judul)) {
        if ($slide_id > 0) {
            $stmt = $conn->prepare("UPDATE hero_slides SET badge = ?, judul = ?, deskripsi = ? WHERE id = ?");
            $stmt->bind_param("sssi", $badge, $judul, $deskripsi, $slide_id);
            $stmt->execute();
            $stmt->close();
            $msg_success = "Banner Slide Hero berhasil diperbarui!";
        } else {
            $stmt = $conn->prepare("INSERT INTO hero_slides (badge, judul, deskripsi) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $badge, $judul, $deskripsi);
            $stmt->execute();
            $stmt->close();
            $msg_success = "Banner Slide Hero baru berhasil ditambahkan!";
        }
        recordLog($conn, $admin_name, "Memperbarui Hero Slides Beranda");
    } else {
        $msg_error = "Judul Banner Slide tidak boleh kosong!";
    }
}

// 8. PROSES HAPUS HERO SLIDE
if (isset($_GET['delete_slide'])) {
    $id_slide = intval($_GET['delete_slide']);
    mysqli_query($conn, "DELETE FROM hero_slides WHERE id = $id_slide");
    recordLog($conn, $admin_name, "Menghapus Hero Slide #$id_slide");
    header("Location: admin-dashboard.php");
    exit();
}

// 9. PROSES UPDATE PAKET HARGA (BARU)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_paket'])) {
    $paket_id    = intval($_POST['paket_id'] ?? 0);
    $nama_paket  = trim($_POST['nama_paket'] ?? '');
    $sub_judul   = trim($_POST['sub_judul'] ?? '');
    $harga       = floatval($_POST['harga'] ?? 0);
    $fitur       = trim($_POST['fitur'] ?? '');
    $rekomendasi = intval($_POST['rekomendasi'] ?? 0);

    if ($paket_id > 0 && !empty($nama_paket)) {
        $stmt_pkt = $conn->prepare("UPDATE paket_harga SET nama_paket = ?, sub_judul = ?, harga = ?, fitur = ?, rekomendasi = ? WHERE id = ?");
        $stmt_pkt->bind_param("ssdsii", $nama_paket, $sub_judul, $harga, $fitur, $rekomendasi, $paket_id);
        $stmt_pkt->execute();
        $stmt_pkt->close();
        $msg_success = "Paket harga berhasil diperbarui!";
        recordLog($conn, $admin_name, "Memperbarui paket harga: $nama_paket");
    }
}

// 10. PROSES UPDATE BANNER CTA (BARU)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_save_cta'])) {
    $badge_cta = trim($_POST['cta_badge'] ?? '');
    $judul_cta = trim($_POST['cta_judul'] ?? '');
    $desc_cta  = trim($_POST['cta_deskripsi'] ?? '');

    if (!empty($judul_cta)) {
        $check_cta = mysqli_query($conn, "SELECT id FROM cta_banner WHERE id = 1");
        if ($check_cta && mysqli_num_rows($check_cta) > 0) {
            $stmt_cta = $conn->prepare("UPDATE cta_banner SET badge = ?, judul = ?, deskripsi = ? WHERE id = 1");
            $stmt_cta->bind_param("sss", $badge_cta, $judul_cta, $desc_cta);
            $stmt_cta->execute();
            $stmt_cta->close();
        } else {
            $stmt_cta = $conn->prepare("INSERT INTO cta_banner (id, badge, judul, deskripsi) VALUES (1, ?, ?, ?)");
            $stmt_cta->bind_param("sss", $badge_cta, $judul_cta, $desc_cta);
            $stmt_cta->execute();
            $stmt_cta->close();
        }
        $msg_success = "Banner CTA berhasil diperbarui!";
        recordLog($conn, $admin_name, "Memperbarui Banner CTA.");
    }
}

$count_services = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM services"))['total'] ?? 0;
$count_users    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'user'"))['total'] ?? 0;
$count_orders   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM orders"))['total'] ?? 0;

$categories_res = mysqli_query($conn, "SELECT * FROM categories ORDER BY nama_kategori ASC");
$services_res   = mysqli_query($conn, "SELECT s.*, c.nama_kategori FROM services s LEFT JOIN categories c ON s.kategori_id = c.id ORDER BY s.id DESC");
$users_res      = mysqli_query($conn, "SELECT * FROM users WHERE role = 'user' ORDER BY id DESC");
$orders_res     = mysqli_query($conn, "SELECT * FROM orders ORDER BY id DESC");
$logs_res       = mysqli_query($conn, "SELECT * FROM activity_logs ORDER BY id DESC LIMIT 15");

$about_data = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM about_us WHERE id = 1")) ?? ['judul' => '', 'deskripsi' => '', 'visi' => '', 'misi' => ''];
$slides_res  = mysqli_query($conn, "SELECT * FROM hero_slides ORDER BY id DESC");
$paket_res   = mysqli_query($conn, "SELECT * FROM paket_harga ORDER BY id ASC");
$cta_data    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM cta_banner WHERE id = 1")) ?? ['badge' => '', 'judul' => '', 'deskripsi' => ''];
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Control Panel - Marketiva</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; }
    .card-studio { background-color: #FFFFFF; border-radius: 1rem; border: 1px solid #E2E8F0; padding: 1.5rem; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02); }
    .input-studio { height: 46px; border-radius: 0.75rem; border: 1.5px solid #CBD5E1; padding: 0 1rem; width: 100%; font-size: 0.875rem; background-color: #FFFFFF; color: #1E293B; transition: all 0.2s; }
    .input-studio:focus { outline: none; border-color: #4F46E5; box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1); }
    textarea.input-studio { height: auto; padding-top: 0.75rem; padding-bottom: 0.75rem; }
  </style>
</head>
<body class="min-h-screen flex flex-col justify-between">

  <!-- TOPBAR -->
  <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-md border-b border-slate-200">
    <div class="max-w-[1360px] mx-auto px-6 h-20 flex items-center justify-between">
      <a href="admin-dashboard.php" class="flex items-center gap-3">
        <span class="font-extrabold text-2xl tracking-tight">
          <span class="text-[#F43F5E]">Mar</span><span class="text-[#3B82F6]">ke</span><span class="text-[#10B981]">ti</span><span class="text-[#F59E0B]">va</span>
        </span>
        <span class="px-2.5 py-1 rounded-md bg-indigo-50 text-indigo-700 text-[10px] font-extrabold uppercase border border-indigo-200">Admin Panel</span>
      </a>
      <div class="flex items-center gap-4">
        <div class="flex items-center gap-3 bg-slate-50 border border-slate-200 px-4 py-2 rounded-2xl">
          <div class="w-8 h-8 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-bold text-xs"><?= strtoupper(substr($admin_name, 0, 1)); ?></div>
          <p class="text-xs font-extrabold text-slate-800"><?= htmlspecialchars($admin_name); ?></p>
        </div>
        <a href="login.php?action=logout" class="px-4 py-2.5 rounded-xl bg-rose-50 text-rose-600 hover:bg-rose-100 font-bold text-xs transition-all flex items-center gap-1.5">
          <span class="material-symbols-outlined text-[18px]">logout</span> Keluar
        </a>
      </div>
    </div>
  </header>

  <!-- MAIN CONTAINER -->
  <main class="max-w-[1360px] mx-auto px-6 py-8 w-full flex-1">
    
    <?php if (!empty($msg_success)): ?>
      <div class="mb-6 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-800 text-xs font-bold flex items-center gap-2">
        <span class="material-symbols-outlined text-[20px]">check_circle</span> <?= htmlspecialchars($msg_success); ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($msg_error)): ?>
      <div class="mb-6 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold flex items-center gap-2">
        <span class="material-symbols-outlined text-[20px]">error</span> <?= htmlspecialchars($msg_error); ?>
      </div>
    <?php endif; ?>

    <!-- STATS -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-8">
      <div class="card-studio flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center"><span class="material-symbols-outlined text-[26px]">design_services</span></div>
        <div><p class="text-[11px] font-extrabold text-slate-400 uppercase">Total Layanan</p><p class="text-2xl font-extrabold text-slate-900"><?= $count_services; ?></p></div>
      </div>
      <div class="card-studio flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center"><span class="material-symbols-outlined text-[26px]">shopping_bag</span></div>
        <div><p class="text-[11px] font-extrabold text-slate-400 uppercase">Pesanan Masuk</p><p class="text-2xl font-extrabold text-slate-900"><?= $count_orders; ?></p></div>
      </div>
      <div class="card-studio flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center"><span class="material-symbols-outlined text-[26px]">groups</span></div>
        <div><p class="text-[11px] font-extrabold text-slate-400 uppercase">Pelanggan UMKM</p><p class="text-2xl font-extrabold text-slate-900"><?= $count_users; ?></p></div>
      </div>
    </div>

    <!-- GRID LAYOUT -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
      
      <!-- SIDEBAR -->
      <div class="lg:col-span-3 card-studio space-y-4 p-4">
        <div class="p-4 rounded-2xl bg-gradient-to-br from-slate-900 to-indigo-950 text-white space-y-3 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-indigo-600 text-white flex items-center justify-center font-extrabold text-base shadow-md">
              <?= strtoupper(substr($admin_name, 0, 1)); ?>
            </div>
            <div class="overflow-hidden">
              <p class="text-xs font-extrabold truncate"><?= htmlspecialchars($admin_name); ?></p>
              <p class="text-[10px] text-indigo-300 font-semibold flex items-center gap-1 mt-0.5">
                <span class="w-2 h-2 rounded-full bg-emerald-400 inline-block animate-pulse"></span> Super Administrator
              </p>
            </div>
          </div>
        </div>

        <div class="space-y-1">
          <p class="px-3 py-2 text-[10px] font-extrabold text-slate-400 uppercase">Navigasi Utama</p>
          <button onclick="switchAdminTab('services')" id="btnTabServices" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 transition-all">
            <span class="material-symbols-outlined text-[20px]">inventory_2</span> Katalog Layanan
          </button>
          <button onclick="openAddForm()" id="btnTabAddService" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all">
            <span class="material-symbols-outlined text-[20px]">add_circle</span> Tambah Layanan Baru
          </button>
          <button onclick="switchAdminTab('orders')" id="btnTabOrders" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all">
            <span class="material-symbols-outlined text-[20px]">shopping_cart</span> Pesanan Masuk
          </button>
          <button onclick="switchAdminTab('users')" id="btnTabUsers" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all">
            <span class="material-symbols-outlined text-[20px]">group</span> Data Pelanggan
          </button>

          <!-- TAB KELOLA SLIDES BERANDA -->
          <button onclick="switchAdminTab('slides')" id="btnTabSlides" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all">
            <span class="material-symbols-outlined text-[20px]">view_carousel</span> Hero Slides Beranda
          </button>

          <!-- TAB KELOLA PAKET HARGA (BARU) -->
          <button onclick="switchAdminTab('paket')" id="btnTabPaket" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all">
            <span class="material-symbols-outlined text-[20px]">payments</span> Kelola Paket Harga
          </button>

          <!-- TAB KELOLA BANNER CTA (BARU) -->
          <button onclick="switchAdminTab('cta')" id="btnTabCta" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all">
            <span class="material-symbols-outlined text-[20px]">campaign</span> Kelola Banner CTA
          </button>

          <!-- TAB KELOLA TENTANG KAMI -->
          <button onclick="switchAdminTab('about')" id="btnTabAbout" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all">
            <span class="material-symbols-outlined text-[20px]">info</span> Kelola Tentang Kami
          </button>

          <button onclick="switchAdminTab('profile')" id="btnTabProfile" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all">
            <span class="material-symbols-outlined text-[20px]">manage_accounts</span> Pengaturan Profil
          </button>
        </div>

        <div class="pt-3 space-y-1 border-t border-slate-100">
          <p class="px-3 py-2 text-[10px] font-extrabold text-slate-400 uppercase">Pintasan Website</p>
          <a href="index.php" target="_blank" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-700 hover:bg-slate-50 transition-all">
            <span class="material-symbols-outlined text-[20px]">home</span> Lihat Beranda
          </a>
          <a href="catalog.php" target="_blank" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-blue-600 hover:bg-blue-50 transition-all">
            <span class="material-symbols-outlined text-[20px]">open_in_new</span> Pratinjau Katalog
          </a>
          <a href="about.php" target="_blank" class="w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-indigo-600 hover:bg-indigo-50 transition-all">
            <span class="material-symbols-outlined text-[20px]">visibility</span> Pratinjau Tentang Kami
          </a>
        </div>
      </div>

      <!-- KONTEN KANAN -->
      <div class="lg:col-span-9 space-y-6">
        
        <!-- TAB 1: KATALOG & PENCARIAN -->
        <div id="adminTabServices" class="card-studio space-y-6">
          <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 border-b border-slate-100 pb-4">
            <div>
              <h2 class="text-xl font-extrabold text-slate-900">Kelola Katalog Layanan</h2>
              <p class="text-slate-500 text-xs mt-0.5">Daftar paket & jasa digital marketing.</p>
            </div>
            <div class="flex items-center gap-3">
              <input type="text" id="searchCatalog" onkeyup="filterCatalog()" placeholder="Cari layanan..." class="h-10 px-3 text-xs border border-slate-300 rounded-xl outline-none focus:border-indigo-500 w-48">
              <button onclick="openAddForm()" class="px-4 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs flex items-center gap-2 shadow-md whitespace-nowrap">
                <span class="material-symbols-outlined text-[18px]">add</span> Tambah
              </button>
            </div>
          </div>

          <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full text-left text-xs" id="catalogTable">
              <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 uppercase font-extrabold text-[10px]">
                <tr>
                  <th class="py-3.5 px-4">Gambar</th>
                  <th class="py-3.5 px-4">Layanan</th>
                  <th class="py-3.5 px-4">Kategori</th>
                  <th class="py-3.5 px-4">Harga</th>
                  <th class="py-3.5 px-4 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                <?php if ($services_res && mysqli_num_rows($services_res) > 0): ?>
                  <?php while ($s = mysqli_fetch_assoc($services_res)): ?>
                    <tr class="hover:bg-slate-50 transition-colors service-row">
                      <td class="py-3 px-4">
                        <div class="w-12 h-10 rounded-lg bg-slate-100 overflow-hidden border border-slate-200 flex-shrink-0">
                          <?php if (!empty($s['gambar'])): ?>
                            <img src="<?= htmlspecialchars($s['gambar']); ?>" class="w-full h-full object-cover">
                          <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center text-slate-400"><span class="material-symbols-outlined text-[16px]">image</span></div>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td class="py-3 px-4 font-bold text-slate-900 service-name"><?= htmlspecialchars($s['nama_layanan']); ?></td>
                      <td class="py-3 px-4"><span class="px-2.5 py-1 rounded-md bg-slate-100 text-slate-700 font-extrabold text-[10px]"><?= htmlspecialchars($s['nama_kategori'] ?? 'Umum'); ?></span></td>
                      <td class="py-3 px-4 font-bold text-emerald-600">Rp <?= number_format($s['harga'], 0, ',', '.'); ?></td>
                      <td class="py-3 px-4 text-center flex items-center justify-center gap-1.5">
                        <button onclick='openEditForm(<?= json_encode($s); ?>)' class="p-2 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 inline-flex items-center" title="Edit">
                          <span class="material-symbols-outlined text-[16px]">edit</span>
                        </button>
                        <a href="admin-dashboard.php?delete_service=<?= $s['id']; ?>" onclick="return confirm('Yakin ingin menghapus layanan ini?')" class="p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 inline-flex items-center" title="Hapus">
                          <span class="material-symbols-outlined text-[16px]">delete</span>
                        </a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="5" class="py-12 text-center text-slate-400 font-bold">Belum ada data layanan di database.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 2: FORM TAMBAH / EDIT LAYANAN -->
        <div id="adminTabAddService" class="card-studio space-y-6 hidden">
          <div class="border-b border-slate-100 pb-4">
            <h2 id="formTitle" class="text-xl font-extrabold text-slate-900">Input Layanan Baru</h2>
            <p id="formSubtitle" class="text-slate-500 text-xs mt-0.5">Tambah kategori baru atau unggah paket jasa digital dari perangkat Anda.</p>
          </div>

          <div class="p-4 rounded-2xl bg-indigo-50/70 border border-indigo-100 space-y-3">
            <p class="text-xs font-extrabold text-indigo-900 uppercase">Tambah Kategori Baru</p>
            <form method="POST" action="admin-dashboard.php" class="flex gap-3">
              <input type="hidden" name="action_add_category" value="1">
              <input type="text" name="nama_kategori" class="input-studio flex-1 bg-white" placeholder="Nama Kategori (misal: Bundling Spesial)" required>
              <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs whitespace-nowrap shadow-md">
                + Tambah Kategori
              </button>
            </form>
          </div>

          <form method="POST" action="admin-dashboard.php" enctype="multipart/form-data" class="space-y-4 pt-2">
            <input type="hidden" name="action_save_service" value="1">
            <input type="hidden" name="service_id" id="service_id" value="">
            <input type="hidden" name="old_gambar" id="old_gambar" value="">
            
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Nama Layanan / Paket</label>
              <input type="text" name="nama_layanan" id="nama_layanan" class="input-studio" placeholder="Contoh: Paket Bundling Instagram UMKM" required>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Pilih Kategori</label>
                <select name="kategori_id" id="kategori_id" class="input-studio" required>
                  <option value="">-- Pilih Kategori --</option>
                  <?php 
                  mysqli_data_seek($categories_res, 0);
                  while ($cat = mysqli_fetch_assoc($categories_res)): 
                  ?>
                    <option value="<?= $cat['id']; ?>"><?= htmlspecialchars($cat['nama_kategori']); ?></option>
                  <?php endwhile; ?>
                </select>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Harga (Rp)</label>
                <input type="number" name="harga" id="harga" class="input-studio" placeholder="500000" min="0" required>
              </div>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Pilih File Gambar</label>
              <input type="file" name="gambar_file" accept="image/*" class="input-studio py-2.5 file:mr-4 file:py-1 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-indigo-50 file:text-indigo-700">
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Deskripsi Lengkap</label>
              <textarea name="deskripsi" id="deskripsi" rows="4" class="input-studio" placeholder="Jelaskan detail layanan..."></textarea>
            </div>

            <div class="pt-2 flex gap-3">
              <button type="submit" id="submitBtnText" class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md">Simpan Layanan</button>
              <button type="button" onclick="switchAdminTab('services')" class="px-5 py-3 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs">Batal</button>
            </div>
          </form>
        </div>

        <!-- TAB 3: PESANAN MASUK -->
        <div id="adminTabOrders" class="card-studio space-y-6 hidden">
          <div class="border-b border-slate-100 pb-4">
            <h2 class="text-xl font-extrabold text-slate-900">Daftar Pesanan Masuk</h2>
            <p class="text-slate-500 text-xs mt-0.5">Kelola pesanan jasa digital dari calon klien website.</p>
          </div>
          <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 uppercase font-extrabold text-[10px]">
                <tr>
                  <th class="py-3.5 px-4">Klien / Usaha</th>
                  <th class="py-3.5 px-4">Layanan Dipesan</th>
                  <th class="py-3.5 px-4">WhatsApp</th>
                  <th class="py-3.5 px-4">Catatan</th>
                  <th class="py-3.5 px-4">Status</th>
                  <th class="py-3.5 px-4 text-center">Aksi / Update</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                <?php if (mysqli_num_rows($orders_res) > 0): ?>
                  <?php while ($ord = mysqli_fetch_assoc($orders_res)): ?>
                    <tr class="hover:bg-slate-50">
                      <td class="py-3 px-4">
                        <p class="font-bold text-slate-900"><?= htmlspecialchars($ord['nama_lengkap'] ?? ''); ?></p>
                        <p class="text-[10px] text-slate-400"><?= htmlspecialchars($ord['nama_usaha'] ?? ''); ?></p>
                      </td>
                      <td class="py-3 px-4 font-bold text-indigo-600"><?= htmlspecialchars($ord['nama_layanan']); ?></td>
                      <td class="py-3 px-4"><?= htmlspecialchars($ord['user_email'] ?? '-'); ?></td>
                      <td class="py-3 px-4 text-slate-500 italic"><?= htmlspecialchars($ord['kategori'] ?? '-'); ?></td>
                      <td class="py-3 px-4">
                        <span class="px-2.5 py-1 rounded-md font-extrabold text-[10px] 
                          <?= $ord['status'] === 'Selesai' ? 'bg-emerald-50 text-emerald-700' : ($ord['status'] === 'Diproses' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700'); ?>">
                          <?= htmlspecialchars($ord['status']); ?>
                        </span>
                      </td>
                      <td class="py-3 px-4 text-center">
                        <select onchange="location.href='admin-dashboard.php?order_id=<?= $ord['id']; ?>&update_order_status='+this.value" class="text-[11px] font-bold border border-slate-300 rounded-lg p-1 bg-white">
                          <option value="">-- Ubah Status --</option>
                          <option value="Menunggu Konfirmasi">Menunggu</option>
                          <option value="Diproses">Diproses</option>
                          <option value="Selesai">Selesai</option>
                        </select>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="6" class="py-8 text-center text-slate-400 font-bold">Belum ada pesanan masuk dari katalog.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB 4: USERS -->
        <div id="adminTabUsers" class="card-studio space-y-6 hidden">
          <div class="border-b border-slate-100 pb-4"><h2 class="text-xl font-extrabold text-slate-900">Data Pelanggan UMKM</h2></div>
          <div class="overflow-x-auto rounded-2xl border border-slate-200">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 uppercase font-extrabold text-[10px]">
                <tr><th class="py-3.5 px-4">Nama Pelanggan</th><th class="py-3.5 px-4">Email</th><th class="py-3.5 px-4">WhatsApp</th></tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                <?php if (mysqli_num_rows($users_res) > 0): ?>
                  <?php while ($u = mysqli_fetch_assoc($users_res)): ?>
                    <tr class="hover:bg-slate-50">
                      <td class="py-3 px-4 font-bold text-slate-900"><?= htmlspecialchars($u['nama_lengkap']); ?></td>
                      <td class="py-3 px-4"><?= htmlspecialchars($u['email']); ?></td>
                      <td class="py-3 px-4"><?= htmlspecialchars($u['whatsapp'] ?? '-'); ?></td>
                    </tr>
                  <?php endwhile; ?>
                <?php else: ?>
                  <tr><td colspan="3" class="py-8 text-center text-slate-400 font-bold">Belum ada pelanggan terdaftar.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB KELOLA HERO SLIDES BERANDA[cite: 3, 6] -->
        <div id="adminTabSlides" class="card-studio space-y-6 hidden">
          <div class="border-b border-slate-100 pb-4">
            <h2 class="text-xl font-extrabold text-slate-900">Kelola Hero Slide Banner (Beranda)</h2>
            <p class="text-slate-500 text-xs mt-0.5">Atur teks banner promosi utama yang tampil pada halaman depan index.php.</p>
          </div>

          <form method="POST" action="admin-dashboard.php" class="space-y-4">
            <input type="hidden" name="action_save_slide" value="1">
            <input type="hidden" name="slide_id" id="slide_id" value="">

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Badge Teks</label>
              <input type="text" name="badge" id="slide_badge" class="input-studio" placeholder="Contoh: 🚀 Partner Pertumbuhan Digital UMKM">
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Judul Utama Banner Slide</label>
              <input type="text" name="judul" id="slide_judul" class="input-studio" placeholder="Contoh: Bantu UMKM Tumbuh Lebih Besar di Dunia Digital." required>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Deskripsi Singkat Banner</label>
              <textarea name="deskripsi" id="slide_deskripsi" rows="3" class="input-studio" placeholder="Penjelasan promo/layanan..." required></textarea>
            </div>

            <div class="pt-2 flex gap-3">
              <button type="submit" id="slideSubmitBtn" class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md">Simpan Banner Slide</button>
              <button type="button" onclick="resetSlideForm()" class="px-4 py-3 rounded-xl bg-slate-100 text-slate-700 font-bold text-xs">Reset Form</button>
            </div>
          </form>

          <div class="overflow-x-auto rounded-2xl border border-slate-200 mt-6">
            <table class="w-full text-left text-xs">
              <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 uppercase font-extrabold text-[10px]">
                <tr>
                  <th class="py-3.5 px-4">Badge</th>
                  <th class="py-3.5 px-4">Judul Banner</th>
                  <th class="py-3.5 px-4">Deskripsi</th>
                  <th class="py-3.5 px-4 text-center">Aksi</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                <?php if ($slides_res && mysqli_num_rows($slides_res) > 0): ?>
                  <?php while ($sl = mysqli_fetch_assoc($slides_res)): ?>
                    <tr class="hover:bg-slate-50">
                      <td class="py-3 px-4 font-bold text-rose-600"><?= htmlspecialchars($sl['badge']); ?></td>
                      <td class="py-3 px-4 font-bold text-slate-900"><?= htmlspecialchars($sl['judul']); ?></td>
                      <td class="py-3 px-4 text-slate-500 max-w-xs truncate"><?= htmlspecialchars($sl['deskripsi']); ?></td>
                      <td class="py-3 px-4 text-center flex items-center justify-center gap-1.5">
                        <button onclick='editSlide(<?= json_encode($sl); ?>)' class="p-2 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 inline-flex items-center" title="Edit">
                          <span class="material-symbols-outlined text-[16px]">edit</span>
                        </button>
                        <a href="admin-dashboard.php?delete_slide=<?= $sl['id']; ?>" onclick="return confirm('Hapus banner slide ini?')" class="p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 inline-flex items-center" title="Hapus">
                          <span class="material-symbols-outlined text-[16px]">delete</span>
                        </a>
                      </td>
                    </tr>
                  <?php endwhile; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

        <!-- TAB KELOLA PAKET HARGA (BARU) -->
        <div id="adminTabPaket" class="card-studio space-y-6 hidden">
          <div class="border-b border-slate-100 pb-4">
            <h2 class="text-xl font-extrabold text-slate-900">Kelola Paket Harga (Beranda)</h2>
            <p class="text-slate-500 text-xs mt-0.5">Ubah nama paket, harga, atau daftar fitur layanan.</p>
          </div>

          <div class="space-y-6">
            <?php 
            mysqli_data_seek($paket_res, 0);
            while ($pkt = mysqli_fetch_assoc($paket_res)): 
            ?>
              <form method="POST" action="admin-dashboard.php" class="p-5 rounded-2xl border border-slate-200 bg-slate-50/50 space-y-4">
                <input type="hidden" name="action_save_paket" value="1">
                <input type="hidden" name="paket_id" value="<?= $pkt['id']; ?>">
                
                <div class="flex items-center justify-between">
                  <h3 class="text-sm font-extrabold text-slate-800">Edit Paket ID #<?= $pkt['id']; ?>: <?= htmlspecialchars($pkt['nama_paket']); ?></h3>
                  <label class="flex items-center gap-2 text-xs font-bold text-slate-700 cursor-pointer">
                    <input type="checkbox" name="rekomendasi" value="1" <?= $pkt['rekomendasi'] == 1 ? 'checked' : ''; ?> class="rounded accent-rose-600 w-4 h-4">
                    Jadikan Rekomendasi (⭐ Populer)
                  </label>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                  <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Nama Paket</label>
                    <input type="text" name="nama_paket" value="<?= htmlspecialchars($pkt['nama_paket']); ?>" class="input-studio bg-white" required>
                  </div>
                  <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Sub Judul</label>
                    <input type="text" name="sub_judul" value="<?= htmlspecialchars($pkt['sub_judul']); ?>" class="input-studio bg-white" required>
                  </div>
                  <div>
                    <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Harga (Rp)</label>
                    <input type="number" name="harga" value="<?= $pkt['harga']; ?>" class="input-studio bg-white" min="0" required>
                  </div>
                </div>

                <div>
                  <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Daftar Fitur (Pisahkan dengan koma ,)</label>
                  <input type="text" name="fitur" value="<?= htmlspecialchars($pkt['fitur']); ?>" class="input-studio bg-white" required>
                </div>

                <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md">
                  Simpan Perubahan Paket
                </button>
              </form>
            <?php endwhile; ?>
          </div>
        </div>

        <!-- TAB KELOLA BANNER CTA (BARU) -->
        <div id="adminTabCta" class="card-studio space-y-6 hidden">
          <div class="border-b border-slate-100 pb-4">
            <h2 class="text-xl font-extrabold text-slate-900">Kelola Banner CTA Checkout</h2>
            <p class="text-slate-500 text-xs mt-0.5">Ubah teks ajakan (Call to Action) bagian bawah beranda.</p>
          </div>

          <form method="POST" action="admin-dashboard.php" class="space-y-4">
            <input type="hidden" name="action_save_cta" value="1">
            
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Badge Teks</label>
              <input type="text" name="cta_badge" value="<?= htmlspecialchars($cta_data['badge'] ?? ''); ?>" class="input-studio" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Judul Utama Banner</label>
              <input type="text" name="cta_judul" value="<?= htmlspecialchars($cta_data['judul'] ?? ''); ?>" class="input-studio" required>
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Deskripsi Singkat</label>
              <textarea name="cta_deskripsi" rows="3" class="input-studio" required><= htmlspecialchars($cta_data['deskripsi'] ?? ''); ?></textarea>
            </div>

            <button type="submit" class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md">
              Simpan Banner CTA
            </button>
          </form>
        </div>

        <!-- TAB KELOLA TENTANG KAMI[cite: 3] -->
        <div id="adminTabAbout" class="card-studio space-y-6 hidden">
          <div class="border-b border-slate-100 pb-4">
            <h2 class="text-xl font-extrabold text-slate-900">Kelola Artikel "Tentang Kami"</h2>
            <p class="text-slate-500 text-xs mt-0.5">Perbarui profil agensi, visi, dan misi yang akan tampil secara dinamis di halaman about.php.</p>
          </div>

          <form method="POST" action="admin-dashboard.php" class="space-y-4">
            <input type="hidden" name="action_update_about" value="1">
            
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Judul Utama Halaman</label>
              <input type="text" name="about_judul" value="<?= htmlspecialchars($about_data['judul']); ?>" class="input-studio" required>
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Deskripsi Profil Utama</label>
              <textarea name="about_deskripsi" rows="5" class="input-studio" required><= htmlspecialchars($about_data['deskripsi']); ?></textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Visi Perusahaan</label>
                <textarea name="about_visi" rows="3" class="input-studio"><?= htmlspecialchars($about_data['visi'] ?? ''); ?></textarea>
              </div>
              <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Misi Perusahaan</label>
                <textarea name="about_misi" rows="3" class="input-studio"><?= htmlspecialchars($about_data['misi'] ?? ''); ?></textarea>
              </div>
            </div>

            <div class="pt-2">
              <button type="submit" class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md">Simpan Perubahan Tentang Kami</button>
            </div>
          </form>
        </div>

        <!-- TAB 5: PENGATURAN PROFIL & LOG AKTIVITAS -->
        <div id="adminTabProfile" class="card-studio space-y-8 hidden">
          <div class="border-b border-slate-100 pb-4">
            <h2 class="text-xl font-extrabold text-slate-900">Pengaturan Profil & Log Aktivitas</h2>
            <p class="text-slate-500 text-xs mt-0.5">Kelola identitas akun dan pantau rekam jejak aktivitas administrator.</p>
          </div>

          <form method="POST" action="admin-dashboard.php" class="space-y-4">
            <input type="hidden" name="action_update_admin" value="1">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Nama Administrator</label>
              <input type="text" name="admin_name" value="<?= htmlspecialchars($admin_name); ?>" class="input-studio" required>
            </div>
            <button type="submit" class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs shadow-md">Simpan Perubahan</button>
          </form>

          <div class="pt-6 border-t border-slate-100 space-y-4">
            <h3 class="text-sm font-extrabold text-slate-900 uppercase">Riwayat Log Aktivitas Admin</h3>
            <div class="space-y-2 max-h-60 overflow-y-auto pr-2">
              <?php while ($log = mysqli_fetch_assoc($logs_res)): ?>
                <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 flex items-center justify-between text-xs">
                  <span class="font-bold text-slate-800"><?= htmlspecialchars($log['aktivitas']); ?></span>
                  <span class="text-[10px] text-slate-400"><?= $log['waktu']; ?></span>
                </div>
              <?php endwhile; ?>
            </div>
          </div>
        </div>

      </div>
    </div>
  </main>

  <footer class="bg-white border-t border-slate-200 py-6 text-center text-xs text-slate-400">
    © 2026 Marketiva Digital Agency. Control Panel Administrator.
  </footer>

  <script>
    function switchAdminTab(tabName) {
      document.getElementById('adminTabServices').classList.add('hidden');
      document.getElementById('adminTabAddService').classList.add('hidden');
      document.getElementById('adminTabOrders').classList.add('hidden');
      document.getElementById('adminTabUsers').classList.add('hidden');
      document.getElementById('adminTabSlides').classList.add('hidden');
      document.getElementById('adminTabPaket').classList.add('hidden');
      document.getElementById('adminTabCta').classList.add('hidden');
      document.getElementById('adminTabAbout').classList.add('hidden');
      document.getElementById('adminTabProfile').classList.add('hidden');

      document.getElementById('btnTabServices').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all";
      document.getElementById('btnTabAddService').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all";
      document.getElementById('btnTabOrders').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all";
      document.getElementById('btnTabUsers').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all";
      document.getElementById('btnTabSlides').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all";
      document.getElementById('btnTabPaket').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all";
      document.getElementById('btnTabCta').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all";
      document.getElementById('btnTabAbout').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all";
      document.getElementById('btnTabProfile').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold text-slate-600 hover:bg-slate-50 transition-all";

      if (tabName === 'services') {
        document.getElementById('adminTabServices').classList.remove('hidden');
        document.getElementById('btnTabServices').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 transition-all";
      } else if (tabName === 'add-service') {
        document.getElementById('adminTabAddService').classList.remove('hidden');
        document.getElementById('btnTabAddService').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 transition-all";
      } else if (tabName === 'orders') {
        document.getElementById('adminTabOrders').classList.remove('hidden');
        document.getElementById('btnTabOrders').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 transition-all";
      } else if (tabName === 'users') {
        document.getElementById('adminTabUsers').classList.remove('hidden');
        document.getElementById('btnTabUsers').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 transition-all";
      } else if (tabName === 'slides') {
        document.getElementById('adminTabSlides').classList.remove('hidden');
        document.getElementById('btnTabSlides').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 transition-all";
      } else if (tabName === 'paket') {
        document.getElementById('adminTabPaket').classList.remove('hidden');
        document.getElementById('btnTabPaket').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 transition-all";
      } else if (tabName === 'cta') {
        document.getElementById('adminTabCta').classList.remove('hidden');
        document.getElementById('btnTabCta').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 transition-all";
      } else if (tabName === 'about') {
        document.getElementById('adminTabAbout').classList.remove('hidden');
        document.getElementById('btnTabAbout').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 transition-all";
      } else if (tabName === 'profile') {
        document.getElementById('adminTabProfile').classList.remove('hidden');
        document.getElementById('btnTabProfile').className = "w-full flex items-center gap-3 px-4 py-3 rounded-xl text-xs font-bold bg-indigo-50 text-indigo-700 transition-all";
      }
    }

    function openAddForm() {
      document.getElementById('service_id').value = '';
      document.getElementById('old_gambar').value = '';
      document.getElementById('nama_layanan').value = '';
      document.getElementById('kategori_id').value = '';
      document.getElementById('harga').value = '';
      document.getElementById('deskripsi').value = '';
      document.getElementById('formTitle').innerText = 'Input Layanan Baru';
      document.getElementById('formSubtitle').innerText = 'Tambah kategori baru atau unggah paket jasa digital dari perangkat Anda.';
      document.getElementById('submitBtnText').innerText = 'Simpan Layanan';
      switchAdminTab('add-service');
    }

    function openEditForm(data) {
      document.getElementById('service_id').value = data.id;
      document.getElementById('old_gambar').value = data.gambar;
      document.getElementById('nama_layanan').value = data.nama_layanan;
      document.getElementById('kategori_id').value = data.kategori_id;
      document.getElementById('harga').value = data.harga;
      document.getElementById('deskripsi').value = data.deskripsi;
      document.getElementById('formTitle').innerText = 'Edit Layanan: ' + data.nama_layanan;
      document.getElementById('formSubtitle').innerText = 'Perbarui informasi atau ubah file gambar layanan.';
      document.getElementById('submitBtnText').innerText = 'Simpan Perubahan';
      switchAdminTab('add-service');
    }

    function editSlide(data) {
      document.getElementById('slide_id').value = data.id;
      document.getElementById('slide_badge').value = data.badge;
      document.getElementById('slide_judul').value = data.judul;
      document.getElementById('slide_deskripsi').value = data.deskripsi;
      document.getElementById('slideSubmitBtn').innerText = 'Simpan Perubahan Slide';
    }

    function resetSlideForm() {
      document.getElementById('slide_id').value = '';
      document.getElementById('slide_badge').value = '';
      document.getElementById('slide_judul').value = '';
      document.getElementById('slide_deskripsi').value = '';
      document.getElementById('slideSubmitBtn').innerText = 'Simpan Banner Slide';
    }

    function filterCatalog() {
      let input = document.getElementById('searchCatalog').value.toLowerCase();
      let rows = document.getElementsByClassName('service-row');
      for (let i = 0; i < rows.length; i++) {
        let name = rows[i].getElementsByClassName('service-name')[0].innerText.toLowerCase();
        if (name.includes(input)) {
          rows[i].style.display = "";
        } else {
          rows[i].style.display = "none";
        }
      }
    }
  </script>
</body>
</html>