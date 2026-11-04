<?php
require_once 'koneksi.php';

$message = '';
$error = '';
$step = 1; // Step 1: Input Email, Step 2: Input Password Baru

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['check_email'])) {
        $email = trim($_POST['email'] ?? '');
        
        $stmt = mysqli_prepare($conn, "SELECT id, email FROM users WHERE email = ?");
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($res)) {
            $step = 2;
            $verified_email = $email;
        } else {
            $error = "Email tidak ditemukan di database kami.";
        }
    } elseif (isset($_POST['update_new_password'])) {
        $email = trim($_POST['email_target'] ?? '');
        $new_pass = trim($_POST['new_password'] ?? '');
        $confirm_pass = trim($_POST['confirm_password'] ?? '');

        if (!empty($new_pass) && $new_pass === $confirm_pass) {
            $stmt = mysqli_prepare($conn, "UPDATE users SET password = ? WHERE email = ?");
            mysqli_stmt_bind_param($stmt, "ss", $new_pass, $email);
            
            if (mysqli_stmt_execute($stmt)) {
                $message = "Kata sandi berhasil diubah! Silakan masuk kembali.";
                $step = 3; 
            } else {
                $error = "Gagal memperbarui kata sandi di database.";
                $step = 2;
                $verified_email = $email;
            }
        } else {
            $error = "Konfirmasi kata sandi baru tidak cocok!";
            $step = 2;
            $verified_email = $email;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Lupa Kata Sandi - Marketiva</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
  <style>
    body { font-family: 'Plus Jakarta Sans', sans-serif; background-color: #F8FAFC; }
    .card-studio { background-color: #FFFFFF; border-radius: 1.25rem; border: 1px solid #E2E8F0; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02); }
    .input-studio { height: 46px; border-radius: 0.75rem; border: 1.5px solid #CBD5E1; padding: 0 1rem; width: 100%; font-size: 0.875rem; background-color: #FFFFFF; color: #1E293B; transition: all 0.2s; }
    .input-studio:focus { outline: none; border-color: #E11D48; box-shadow: 0 0 0 3px rgba(225,29,72,0.1); }
  </style>
</head>
<body class="min-h-screen flex items-center justify-center p-6">

  <div class="max-w-md w-full card-studio p-8 space-y-6">
    <div class="text-center space-y-2">
      <a href="index.php" class="font-extrabold text-2xl tracking-tight inline-block">
        <span class="text-[#F43F5E]">Mar</span><span class="text-[#3B82F6]">ke</span><span class="text-[#10B981]">ti</span><span class="text-[#F59E0B]">va</span>
      </a>
      <h1 class="text-xl font-extrabold text-slate-900">Pemulihan Kata Sandi</h1>
      <p class="text-xs text-slate-500">Atur ulang akses akun dashboard Anda dengan mudah.</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="p-3.5 rounded-xl bg-rose-50 border border-rose-200 text-rose-700 text-xs font-bold">
        <?= htmlspecialchars($error); ?>
      </div>
    <?php endif; ?>

    <?php if ($step === 1): ?>
      <form method="POST" action="forgot-password.php" class="space-y-4">
        <input type="hidden" name="check_email" value="1">
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Email Terdaftar</label>
          <input type="email" name="email" class="input-studio" placeholder="nama@email.com" required>
        </div>
        <button type="submit" class="w-full py-3.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs shadow-md transition-all">
          Verifikasi Email
        </button>
      </form>
    <?php elseif ($step === 2): ?>
      <form method="POST" action="forgot-password.php" class="space-y-4">
        <input type="hidden" name="update_new_password" value="1">
        <input type="hidden" name="email_target" value="<?= htmlspecialchars($verified_email); ?>">
        
        <div class="p-3 rounded-xl bg-slate-50 border border-slate-200 text-xs text-slate-600">
          Email terverifikasi: <span class="font-bold text-slate-900"><?= htmlspecialchars($verified_email); ?></span>
        </div>

        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Kata Sandi Baru</label>
          <input type="password" name="new_password" class="input-studio" placeholder="••••••••" required>
        </div>
        <div>
          <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Konfirmasi Kata Sandi Baru</label>
          <input type="password" name="confirm_password" class="input-studio" placeholder="••••••••" required>
        </div>
        <button type="submit" class="w-full py-3.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md transition-all">
          Simpan Kata Sandi Baru
        </button>
      </form>
    <?php elseif ($step === 3): ?>
      <div class="text-center space-y-4">
        <div class="w-14 h-14 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center mx-auto text-2xl font-extrabold">✓</div>
        <p class="text-xs font-bold text-slate-700"><?= htmlspecialchars($message); ?></p>
        <a href="login.php" class="block w-full py-3.5 rounded-xl bg-slate-900 text-white font-bold text-xs text-center shadow-md">
          Masuk ke Halaman Login
        </a>
      </div>
    <?php endif; ?>

    <?php if ($step !== 3): ?>
      <div class="text-center pt-2">
        <a href="login.php" class="text-xs font-bold text-slate-500 hover:text-slate-800">← Kembali ke Halaman Login</a>
      </div>
    <?php endif; ?>
  </div>

</body>
</html>