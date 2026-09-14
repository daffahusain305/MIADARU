<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Miadaru Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8fafc;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            background: #ffffff;
            border-radius: 24px;
            border: none;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.05), 0 10px 10px -5px rgba(0, 0, 0, 0.02);
            padding: 40px;
        }
        .brand-logo {
            font-weight: 800;
            font-size: 24px;
            letter-spacing: -1px;
            color: #1e293b;
            margin-bottom: 8px;
        }
        .form-label {
            font-weight: 600;
            font-size: 13px;
            color: #64748b;
        }
        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
            background-color: #f8fafc;
            font-size: 14px;
            font-weight: 500;
        }
        .form-control:focus {
            background-color: #ffffff;
            border-color: #1e293b;
            box-shadow: none;
        }
        .btn-login {
            background-color: #1e293b;
            color: white;
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-weight: 700;
            font-size: 14px;
            width: 100%;
            margin-top: 10px;
            transition: all 0.2s;
        }
        .btn-login:hover {
            background-color: #0f172a;
            transform: translateY(-1px);
        }
        .alert {
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            border: none;
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-5">
        <div class="brand-logo">MIADARU</div>
        <p class="text-muted small">Kelola stok gudang dengan lebih mudah.</p>
    </div>

    <?php if(isset($_GET['pesan']) && $_GET['pesan'] == 'gagal'): ?>
        <div class="alert alert-danger text-center mb-4">
            <i class="bi bi-exclamation-circle me-2"></i> Username atau Password salah!
        </div>
    <?php endif; ?>

    <form action="keamanan/proses_login.php" method="POST">
        <div class="mb-3">
            <label class="form-label">Username</label>
            <div class="input-group">
                <input type="text" name="username" class="form-control" placeholder="Masukkan username" required autocomplete="off">
            </div>
        </div>
        
        <div class="mb-4">
            <label class="form-label">Password</label>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>

        <button type="submit" class="btn btn-login">
            Masuk Sekarang <i class="bi bi-arrow-right ms-2"></i>
        </button>
    </form>
    
    <div class="text-center mt-5">
        <p class="text-muted" style="font-size: 11px;">&copy; 2026 Miadaru Inventory System</p>
    </div>
</div>

</body>
</html>