<?php
require_once 'koneksi.php';

$error = '';
$success = '';

// ==========================================
// 1. PENANGANAN LOGIN / REGISTER DENGAN GOOGLE
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['google_credential'])) {
    $credential = $_POST['google_credential'];
    
    $token_parts = explode('.', $credential);
    if (count($token_parts) === 3) {
        $payload = json_decode(base64_decode(str_replace(['-', '_'], ['+', '/'], $token_parts[1])), true);
        
        if ($payload && isset($payload['email'])) {
            $google_email = trim($payload['email']);
            $google_name = trim($payload['name'] ?? 'Pengguna Google');

            // Atur email admin khusus
            $admin_email_khusus = "marketivaadmin2710@gmail.com";
            $assigned_role = ($google_email === $admin_email_khusus) ? 'admin' : 'user';

            $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "s", $google_email);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($res)) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user'] = $row['nama_lengkap'];
                $_SESSION['role'] = $assigned_role;
                $_SESSION['email'] = $row['email'];

                if ($assigned_role === 'admin' && $row['role'] !== 'admin') {
                    mysqli_query($conn, "UPDATE users SET role = 'admin' WHERE email = '$google_email'");
                }

                if ($assigned_role === 'admin') {
                    header("Location: admin-dashboard.php");
                } else {
                    header("Location: user-dashboard.php");
                }
                exit();
            } else {
                $stmt_ins = mysqli_prepare($conn, "INSERT INTO users (nama_lengkap, email, role) VALUES (?, ?, ?)");
                mysqli_stmt_bind_param($stmt_ins, "sss", $google_name, $google_email, $assigned_role);
                if (mysqli_stmt_execute($stmt_ins)) {
                    $_SESSION['user_id'] = mysqli_insert_id($conn);
                    $_SESSION['user'] = $google_name;
                    $_SESSION['role'] = $assigned_role;
                    $_SESSION['email'] = $google_email;

                    if ($assigned_role === 'admin') {
                        header("Location: admin-dashboard.php");
                    } else {
                        header("Location: user-dashboard.php");
                    }
                    exit();
                } else {
                    $error = "Gagal mendaftarkan akun Google ke database.";
                }
            }
        }
    }
}

// ==========================================
// 2. PROSES FORM MANUAL (LOGIN & REGISTER)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['google_credential'])) {
    $action = $_POST['action'] ?? 'login';

    if ($action === 'register') {
        $nama_lengkap = trim($_POST['fullNameInput'] ?? '');
        $email = trim($_POST['emailInput'] ?? '');
        $whatsapp = trim($_POST['phoneInput'] ?? '');
        $password = trim($_POST['regPasswordInput'] ?? '');

        if (!empty($nama_lengkap) && !empty($email) && !empty($password)) {
            $check = mysqli_query($conn, "SELECT * FROM users WHERE email = '$email'");
            if (mysqli_num_rows($check) > 0) {
                $error = "Email sudah terdaftar. Silakan gunakan email lain.";
            } else {
                $query = "INSERT INTO users (nama_lengkap, email, whatsapp, password, role) VALUES ('$nama_lengkap', '$email', '$whatsapp', '$password', 'user')";
                
                if (mysqli_query($conn, $query)) {
                    $_SESSION['user_id'] = mysqli_insert_id($conn);
                    $_SESSION['user'] = $nama_lengkap;
                    $_SESSION['role'] = 'user';
                    $_SESSION['email'] = $email;
                    header("Location: user-dashboard.php");
                    exit();
                } else {
                    $error = "Terjadi kesalahan database: " . mysqli_error($conn);
                }
            }
        } else {
            $error = "Semua kolom pendaftaran wajib diisi!";
        }
    }

    if ($action === 'login') {
        $username = trim($_POST['usernameInput'] ?? '');
        $password = trim($_POST['passwordInput'] ?? '');

        if (!empty($username) && !empty($password)) {
            $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE nama_lengkap = ? OR email = ?");
            mysqli_stmt_bind_param($stmt, "ss", $username, $username);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);

            if ($row = mysqli_fetch_assoc($result)) {
                if ($row['password'] === $password) {
                    $_SESSION['user_id'] = $row['id'];
                    $_SESSION['user'] = $row['nama_lengkap'];
                    $_SESSION['role'] = $row['role'];
                    $_SESSION['email'] = $row['email'];

                    if ($row['role'] === 'admin') {
                        header("Location: admin-dashboard.php");
                        exit();
                    } else {
                        header("Location: user-dashboard.php");
                        exit();
                    }
                } else {
                    $error = "Kata sandi salah!";
                }
            } else {
                $error = "Akun tidak ditemukan! Periksa kembali nama/email Anda.";
            }
        } else {
            $error = "Silakan isi Username/Email dan Kata Sandi!";
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Marketiva - Masuk & Pendaftaran Akun</title>
  
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Library Resmi Google Identity Services -->
  <script src="https://accounts.google.com/gsi/client" async defer></script>
  
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">

  <style>
    body { 
      font-family: 'Plus Jakarta Sans', sans-serif; 
      background: radial-gradient(100% 100% at 50% 0%, #FFF1F2 0%, #FAF9F6 100%);
    }
    .card-modern {
      background: rgba(255, 255, 255, 0.95);
      backdrop-filter: blur(12px);
      border: 1px solid rgba(226, 232, 240, 0.8);
      box-shadow: 0 20px 40px -15px rgba(15, 23, 42, 0.07);
    }
    .btn-primary-hero { 
      background-color: #E11D48; 
      color: white; 
      font-weight: 700; 
      padding: 0.875rem 1.75rem; 
      border-radius: 0.875rem; 
      transition: all 0.2s ease-in-out; 
    }
    .btn-primary-hero:hover { background-color: #BE123C; transform: translateY(-1px); }
    .btn-secondary-blue { 
      background-color: #4F46E5; 
      color: white; 
      font-weight: 700; 
      padding: 0.875rem 1.75rem; 
      border-radius: 0.875rem; 
      transition: all 0.2s ease-in-out; 
    }
    .input-studio { 
      height: 48px; 
      border-radius: 0.75rem; 
      border: 1.5px solid #E2E8F0; 
      padding: 0 1rem; 
      width: 100%; 
      font-size: 0.875rem; 
      background-color: #FFFFFF; 
      transition: all 0.2s;
    }
    .input-studio:focus { outline: none; border-color: #E11D48; box-shadow: 0 0 0 4px rgba(225,29,72,0.1); }
  </style>
</head>
<body class="min-h-screen flex flex-col justify-between relative antialiased">

  <!-- HEADER NAVBAR -->
  <header class="py-6 px-8 flex items-center justify-between max-w-[1240px] mx-auto w-full">
    <a href="index.php" class="flex items-center gap-2.5 group">
      <span class="font-extrabold text-2xl tracking-tight">
        <span class="text-[#F43F5E]">Mar</span><span class="text-[#3B82F6]">ke</span><span class="text-[#10B981]">ti</span><span class="text-[#F59E0B]">va</span>
      </span>
    </a>
    <a href="index.php" class="text-xs font-bold text-slate-600 hover:text-[#E11D48] transition-colors flex items-center gap-1.5 px-3 py-2 rounded-xl hover:bg-rose-50">
      <span class="material-symbols-outlined text-[18px]">arrow_back</span>
      <span>Kembali ke Beranda</span>
    </a>
  </header>

  <!-- KONTEN UTAMA LOGIN -->
  <main class="flex-1 flex items-center justify-center px-4 py-6 relative z-10">
    <div class="w-full max-w-md">
      
      <?php if (!empty($error)): ?>
        <div class="mb-4 p-3.5 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold text-center flex items-center justify-center gap-2 shadow-sm">
          <span class="material-symbols-outlined text-[18px]">error</span>
          <span><?= htmlspecialchars($error); ?></span>
        </div>
      <?php endif; ?>

      <!-- TAB LOGIN / REGISTER -->
      <div class="flex rounded-2xl bg-slate-200/60 p-1.5 mb-6 backdrop-blur-sm">
        <button id="tabLoginBtn" type="button" onclick="switchTab('login')" class="flex-1 py-2.5 rounded-xl text-xs font-bold bg-white text-slate-900 shadow-sm transition-all">
          Masuk (Login)
        </button>
        <button id="tabRegisterBtn" type="button" onclick="switchTab('register')" class="flex-1 py-2.5 rounded-xl text-xs font-bold text-slate-600 transition-all">
          Daftar (Register)
        </button>
      </div>

      <!-- KARTU FORM UTAMA -->
      <div class="card-modern p-8 rounded-3xl">
        
        <!-- INDIKATOR ROLE DINAMIK -->
        <div id="role-indicator" class="mb-6 p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between transition-all">
          <div class="flex items-center gap-3">
            <div id="role-icon-bg" class="w-9 h-9 rounded-xl bg-rose-100 text-[#E11D48] flex items-center justify-center transition-colors">
              <span id="role-icon" class="material-symbols-outlined text-[20px]">person</span>
            </div>
            <div>
              <p class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">Akses Portal</p>
              <p id="role-title" class="text-xs font-bold text-slate-800">Pelanggan Marketiva</p>
            </div>
          </div>
          <span id="role-chip" class="text-[10px] font-bold px-3 py-1 rounded-full bg-rose-100 text-rose-700 transition-colors">Pelanggan Mode</span>
        </div>

        <!-- TOMBOL GOOGLE SIGN-IN -->
        <div id="googleAuthSection" class="mb-5 space-y-3">
          <div id="g_id_onload"
              data-client_id="324356913944-umo33un2tvbmsm6rbsbndgm3h33urubo.apps.googleusercontent.com"
              data-callback="handleCredentialResponse"
              data-auto_prompt="false">
          </div>
          <div class="g_id_signin w-full flex justify-center"
              data-type="standard"
              data-size="large"
              data-theme="outline"
              data-text="sign_in_with"
              data-shape="rectangular"
              data-logo_alignment="left"
              data-width="380">
          </div>

          <div class="relative flex py-2 items-center">
            <div class="flex-grow border-t border-slate-200"></div>
            <span class="flex-shrink mx-4 text-[11px] font-bold text-slate-400 uppercase tracking-wider">atau masuk manual</span>
            <div class="flex-grow border-t border-slate-200"></div>
          </div>
        </div>

        <!-- FORM INPUT MANUAL -->
        <form id="authForm" method="POST" action="login.php" class="space-y-4">
          <input type="hidden" name="action" id="formAction" value="login">
          <input type="hidden" name="google_credential" id="googleCredentialInput">

          <div id="registerFields" class="space-y-3.5 hidden">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Lengkap / UMKM</label>
              <input name="fullNameInput" id="fullNameInput" type="text" placeholder="Masukkan Nama Lengkap Anda" class="input-studio">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Alamat Email</label>
              <input name="emailInput" id="emailInput" type="email" placeholder="nama@email.com" class="input-studio">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nomor WhatsApp</label>
              <input name="phoneInput" id="phoneInput" type="tel" placeholder="08123456789" class="input-studio">
            </div>
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kata Sandi Baru</label>
              <input name="regPasswordInput" id="regPasswordInput" type="password" placeholder="••••••••" class="input-studio">
            </div>
          </div>

          <div id="loginFields" class="space-y-3.5">
            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Nama Pengguna / Email</label>
              <input 
                name="usernameInput"
                id="usernameInput" 
                type="text" 
                oninput="detectSmartRole(this.value)"
                placeholder="Masukkan Nama atau Email" 
                class="input-studio"
              >
            </div>

            <div>
              <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kata Sandi</label>
              <input name="passwordInput" id="passwordInput" type="password" placeholder="••••••••" class="input-studio">
            </div>

            <!-- TAUTAN LUPA KATA SANDI (SUDAH TERPASANG) -->
            <div class="flex items-center justify-end pt-1">
              <a href="forgot-password.php" class="text-xs font-bold text-rose-600 hover:text-rose-700 hover:underline transition-colors">
                Lupa kata sandi?
              </a>
            </div>
          </div>

          <button type="submit" id="submitBtn" class="btn-primary-hero w-full justify-center mt-2 flex items-center gap-2 shadow-lg shadow-rose-600/20">
            <span id="submitBtnText">Masuk Sekarang</span>
            <span class="material-symbols-outlined text-[18px]">arrow_forward</span>
          </button>

        </form>

      </div>
    </div>
  </main>

  <footer class="py-4 text-center text-xs text-slate-400">
    © 2026 Marketiva Digital Agency.
  </footer>

  <script>
    function handleCredentialResponse(response) {
      if (response.credential) {
        document.getElementById('googleCredentialInput').value = response.credential;
        document.getElementById('authForm').submit();
      }
    }

    function switchTab(mode) {
      const tabLoginBtn = document.getElementById('tabLoginBtn');
      const tabRegisterBtn = document.getElementById('tabRegisterBtn');
      const registerFields = document.getElementById('registerFields');
      const loginFields = document.getElementById('loginFields');
      const submitBtnText = document.getElementById('submitBtnText');
      const formAction = document.getElementById('formAction');
      const roleIndicator = document.getElementById('role-indicator');
      const googleAuthSection = document.getElementById('googleAuthSection');

      googleAuthSection.classList.remove('hidden');

      if (mode === 'register') {
        tabRegisterBtn.className = "flex-1 py-2.5 rounded-xl text-xs font-bold bg-white text-slate-900 shadow-sm transition-all";
        tabLoginBtn.className = "flex-1 py-2.5 rounded-xl text-xs font-bold text-slate-600 transition-all";
        registerFields.classList.remove('hidden');
        loginFields.classList.add('hidden');
        submitBtnText.textContent = "Daftar Akun Baru";
        formAction.value = "register";
        roleIndicator.classList.add('hidden');
      } else {
        tabLoginBtn.className = "flex-1 py-2.5 rounded-xl text-xs font-bold bg-white text-slate-900 shadow-sm transition-all";
        tabRegisterBtn.className = "flex-1 py-2.5 rounded-xl text-xs font-bold text-slate-600 transition-all";
        registerFields.classList.add('hidden');
        loginFields.classList.remove('hidden');
        submitBtnText.textContent = "Masuk Sekarang";
        formAction.value = "login";
        roleIndicator.classList.remove('hidden');
      }
    }

    function detectSmartRole(value) {
      const val = value.toLowerCase().trim();
      const roleIndicator = document.getElementById('role-indicator');
      const roleIconBg = document.getElementById('role-icon-bg');
      const roleIcon = document.getElementById('role-icon');
      const roleTitle = document.getElementById('role-title');
      const roleChip = document.getElementById('role-chip');
      const submitBtn = document.getElementById('submitBtn');

      if (val.includes('admin')) {
        roleIndicator.className = "mb-6 p-3.5 rounded-2xl bg-indigo-50 border border-indigo-200 flex items-center justify-between transition-all";
        roleIconBg.className = "w-9 h-9 rounded-xl bg-indigo-600 text-white flex items-center justify-center transition-colors";
        roleIcon.textContent = "admin_panel_settings";
        roleTitle.textContent = "Control Panel Admin (" + value + ")";
        roleChip.className = "text-[10px] font-bold px-3 py-1 rounded-full bg-indigo-600 text-white transition-colors";
        roleChip.textContent = "Admin Mode";
        
        submitBtn.className = "btn-secondary-blue w-full justify-center mt-2 shadow-lg shadow-indigo-600/20 flex items-center gap-2";
      } else {
        roleIndicator.className = "mb-6 p-3.5 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-between transition-all";
        roleIconBg.className = "w-9 h-9 rounded-xl bg-rose-100 text-[#E11D48] flex items-center justify-center transition-colors";
        roleIcon.textContent = "person";
        roleTitle.textContent = "Pelanggan Marketiva";
        roleChip.className = "text-[10px] font-bold px-3 py-1 rounded-full bg-rose-100 text-rose-700 transition-colors";
        roleChip.textContent = "Pelanggan Mode";

        submitBtn.className = "btn-primary-hero w-full justify-center mt-2 flex items-center gap-2 shadow-lg shadow-rose-600/20";
      }
    }
  </script>
</body>
</html>