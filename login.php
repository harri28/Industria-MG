<?php
require_once __DIR__ . '/config/session.php';
startAppSession();
require_once __DIR__ . '/config/database.php';

// Si ya esta logueado, redirigir al inicio
if (!empty($_SESSION['usuario'])) {
    header('Location: index.php');
    exit;
}

$error = $_SESSION['login_error'] ?? '';
$email_value = $_SESSION['login_email'] ?? '';
unset($_SESSION['login_error'], $_SESSION['login_email']);

// Personalización del login (Configuración → Personalización)
$login_bg        = 'assets/iconos/fondo.png';
$login_logo      = 'assets/iconos/logo.jpeg';
$login_nombre    = APP_NAME;
$login_subtitulo = 'Sistema de Gestion Industrial';
$favicon         = '';
try {
    $__cfg = getDB()->query("SELECT clave, valor FROM app_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    if (!empty($__cfg['login_bg']))        $login_bg        = $__cfg['login_bg'];
    if (!empty($__cfg['login_logo']))      $login_logo      = $__cfg['login_logo'];
    if (!empty($__cfg['login_nombre']))    $login_nombre    = $__cfg['login_nombre'];
    if (!empty($__cfg['login_subtitulo'])) $login_subtitulo = $__cfg['login_subtitulo'];
    if (!empty($__cfg['favicon']))         $favicon         = $__cfg['favicon'];
} catch (\Throwable $e) {}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $_SESSION['login_email'] = $email;

    if ($email && $password) {
        try {
            $db   = getDB();
            $stmt = $db->prepare("
                SELECT u.*, r.nombre AS rol_nombre, r.permisos AS permisos_json
                FROM usuarios u
                LEFT JOIN roles r ON r.id = u.rol_id
                WHERE (u.email = :login OR split_part(u.email, '@', 1) = :login)
                  AND u.activo = TRUE
                ORDER BY CASE WHEN u.email = :login_exact THEN 0 ELSE 1 END, u.id ASC
                LIMIT 1");
            $stmt->execute([
                ':login' => $email,
                ':login_exact' => $email,
            ]);
            $user = $stmt->fetch();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['usuario'] = [
                    'id'       => $user['id'],
                    'nombre'   => $user['nombre'],
                    'email'    => $user['email'],
                    'rol'      => $user['rol_nombre'] ?? 'Administrador',
                    'rol_id'   => $user['rol_id'],
                    'area_id'  => $user['area_id'],
                    'permisos' => is_string($user['permisos_json'])
                                    ? json_decode($user['permisos_json'], true)
                                    : ($user['permisos_json'] ?? []),
                ];
                $db->prepare("UPDATE usuarios SET ultimo_acceso = NOW() WHERE id = :id")
                   ->execute([':id' => $user['id']]);

                unset($_SESSION['login_error'], $_SESSION['login_email']);
                header('Location: index.php');
                exit;
            } else {
                $_SESSION['login_error'] = 'Usuario o contraseña incorrectos.';
                header('Location: login.php');
                exit;
            }
        } catch (Exception $e) {
            $_SESSION['login_error'] = 'Error de conexión. Contacte al administrador.';
            header('Location: login.php');
            exit;
        }
    } else {
        $_SESSION['login_error'] = 'Ingrese usuario y contraseña.';
        header('Location: login.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar sesión â€” <?= APP_NAME ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <?php if ($favicon): ?>
    <link rel="icon" href="<?= htmlspecialchars($favicon, ENT_QUOTES) ?>">
    <?php endif; ?>
    <style>
        *, *::before, *::after { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: url('<?= htmlspecialchars($login_bg, ENT_QUOTES) ?>') center center / cover no-repeat fixed;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background: linear-gradient(160deg, rgba(10,22,60,.45) 0%, rgba(6,14,42,.82) 100%);
            z-index: 0;
        }

        .login-wrap {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 16px;
        }

        .login-card {
            background: #fff;
            border-radius: 14px;
            box-shadow: 0 24px 64px rgba(0,0,0,.45), 0 4px 16px rgba(0,0,0,.20);
            padding: 44px 40px;
        }

        .login-logo { text-align: center; margin-bottom: 32px; }
        .login-logo img { width: 52px; height: 52px; object-fit: contain; border-radius: 10px; margin-bottom: 12px; }
        .login-logo h1 { font-size: 1.3rem; font-weight: 700; color: #0f172a; margin: 0 0 3px; }
        .login-logo p  { font-size: .8rem; color: #64748b; margin: 0; }

        .login-section-label {
            font-size: .68rem;
            font-weight: 600;
            letter-spacing: .1em;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 20px;
        }

        .login-error { background:#fef2f2; border:1px solid #fecaca; color:#dc2626; border-radius:6px; padding:10px 14px; font-size:.82rem; margin-bottom:16px; display:flex; align-items:center; gap:8px; }
        .login-btn { width:100%; padding:11px; font-size:.92rem; font-weight:600; letter-spacing:.02em; }
        .login-foot { text-align:center; margin-top:20px; font-size:.74rem; color:rgba(255,255,255,.55); }
    </style>
</head>
<body>
<div class="login-wrap">
    <div class="login-card">

        <div class="login-logo">
            <img src="<?= htmlspecialchars($login_logo, ENT_QUOTES) ?>" alt="<?= htmlspecialchars($login_nombre, ENT_QUOTES) ?>"
                 onerror="this.style.display='none'">
            <h1><?= htmlspecialchars($login_nombre) ?></h1>
            <p><?= htmlspecialchars($login_subtitulo) ?></p>
        </div>

        <p class="login-section-label">Acceso al sistema</p>

        <?php if ($error): ?>
        <div class="login-error">
            <i class="fa fa-circle-exclamation"></i> <?= htmlspecialchars($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="form-group">
                <label class="form-label">Usuario</label>
                <input type="text" name="email" class="form-control"
                       placeholder="correo@empresa.com"
                       value="<?= htmlspecialchars($email_value) ?>"
                       autofocus required>
            </div>
            <div class="form-group" style="margin-top:14px">
                <label class="form-label">Contraseña</label>
                <div style="position:relative">
                    <input type="password" name="password" id="passwordInput" class="form-control"
                           placeholder="••••••••" required style="padding-right:40px">
                    <button type="button"
                            style="position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;color:#94a3b8;padding:0;line-height:1"
                            onclick="togglePasswordVisibility()">
                        <i id="toggleIcon" class="fa fa-eye"></i>
                    </button>
                </div>
            </div>
            <button type="submit" class="btn btn-primary login-btn" style="margin-top:24px">
                <i class="fa fa-right-to-bracket"></i> Ingresar
            </button>
        </form>

    </div>
    <div class="login-foot">
        <?= APP_NAME ?> v<?= APP_VERSION ?>
    </div>
</div>
<script>
function togglePasswordVisibility() {
    const input = document.getElementById('passwordInput');
    const icon  = document.getElementById('toggleIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>
</body>
</html>
