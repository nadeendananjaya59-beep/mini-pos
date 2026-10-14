<?php
/**
 * Login page.
 */
require_once __DIR__ . '/includes/auth.php';

// Already logged in? Go straight to the dashboard.
if (isLoggedIn()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $error = 'Please enter both username and password.';
    } else {
        $stmt = db()->prepare('SELECT id, name, username, password, role FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();   

        // password_verify compares against the stored bcrypt hash.
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);               // prevents session fixation
            $_SESSION['user_id']       = (int) $user['id'];
            $_SESSION['name']          = $user['name'];
            $_SESSION['role']          = $user['role'];
            $_SESSION['last_activity'] = time();
            redirect('dashboard.php');
        }
        $error = 'Invalid username or password.';
    }
}

$flash = takeFlash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#f4f6f3">
    <title>Sign in | Mini-POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<style>
    :root { --ink:#172923; --muted:#718079; --green:#236b50; --line:#e3e9e4; }
    * { box-sizing:border-box; }
    body { min-height:100vh; margin:0; color:var(--ink); background:#f4f6f3; font-family:Inter,ui-sans-serif,system-ui,-apple-system,"Segoe UI",sans-serif; }
    .login-shell { min-height:100vh; display:grid; grid-template-columns:minmax(0,1.08fr) minmax(420px,.92fr); padding:24px; gap:24px; }
    .brand-panel { position:relative; overflow:hidden; display:flex; flex-direction:column; justify-content:space-between; min-height:calc(100vh - 48px); padding:clamp(32px,5vw,72px); border-radius:28px; color:#f5f8f3; background:linear-gradient(145deg,#143c30 0%,#1d5843 58%,#287052 100%); }
    .brand-panel:before,.brand-panel:after { content:""; position:absolute; border:1px solid rgba(255,255,255,.12); border-radius:50%; pointer-events:none; }
    .brand-panel:before { width:540px;height:540px;right:-200px;top:14%; }
    .brand-panel:after { width:390px;height:390px;right:-120px;top:24%; }
    .brand-top,.brand-copy,.brand-foot { position:relative; z-index:1; }
    .brand-mark { display:flex; align-items:center; gap:12px; font-weight:750; letter-spacing:-.03em; font-size:1.18rem; }
    .brand-icon { display:grid; place-items:center; width:42px;height:42px; border:1px solid rgba(255,255,255,.24); border-radius:14px; background:rgba(255,255,255,.1); font-size:1.15rem; }
    .eyebrow { display:inline-flex; align-items:center; gap:9px; margin-bottom:20px; color:#b5d7c3; font-size:.76rem; font-weight:700; letter-spacing:.15em; text-transform:uppercase; }
    .eyebrow:before { content:""; width:22px;height:1px;background:#a6d0b7; }
    .brand-copy h1 { max-width:580px; margin:0 0 20px; font-size:clamp(2.6rem,5vw,4.7rem); line-height:1.04; letter-spacing:-.055em; font-weight:700; }
    .brand-copy p { max-width:440px; margin:0; color:rgba(245,248,243,.75); font-size:1.04rem; line-height:1.8; }
    .brand-foot { display:flex; align-items:center; gap:10px; color:rgba(245,248,243,.7); font-size:.85rem; }
    .brand-foot i { color:#b8dfc6; }
    .form-side { display:grid; place-items:center; padding:clamp(20px,5vw,72px); }
    .login-card { width:100%; max-width:430px; }
    .mobile-brand { display:none; }
    .form-heading { margin-bottom:34px; }
    .form-heading .welcome { margin:0 0 9px; color:var(--green); font-size:.77rem; font-weight:750; text-transform:uppercase; letter-spacing:.14em; }
    .form-heading h2 { margin:0 0 9px; font-size:2.1rem; font-weight:720; letter-spacing:-.045em; }
    .form-heading p { margin:0; color:var(--muted); font-size:.96rem; }
    .field-label { display:block; margin:0 0 9px; font-size:.88rem; font-weight:650; }
    .field-wrap { position:relative; margin-bottom:21px; }
    .field-wrap .field-icon { position:absolute; top:50%; left:16px; transform:translateY(-50%); color:#819087; font-size:1rem; pointer-events:none; }
    .field-control { display:block; width:100%; height:54px; padding:0 48px; border:1px solid var(--line); border-radius:12px; outline:none; color:var(--ink); background:#fff; font:inherit; transition:border-color .18s,box-shadow .18s; }
    .field-control::placeholder { color:#a0aaa4; }
    .field-control:focus { border-color:#4c9874; box-shadow:0 0 0 4px rgba(35,107,80,.11); }
    .password-toggle { position:absolute; top:50%; right:10px; transform:translateY(-50%); width:36px;height:36px; border:0; border-radius:9px; color:#75837b; background:transparent; }
    .password-toggle:hover { color:var(--green); background:#f1f6f2; }
    .submit-btn { display:flex; align-items:center; justify-content:center; gap:10px; width:100%; height:54px; margin-top:8px; border:0; border-radius:12px; color:white; background:var(--green); font:inherit; font-weight:700; box-shadow:0 8px 18px rgba(35,107,80,.18); transition:background .18s,transform .18s,box-shadow .18s; }
    .submit-btn:hover { background:#1b593f; transform:translateY(-1px); box-shadow:0 11px 22px rgba(35,107,80,.23); }
    .submit-btn:focus-visible,.password-toggle:focus-visible { outline:3px solid #92c7a8; outline-offset:3px; }
    .secure-note { display:flex; align-items:center; justify-content:center; gap:8px; margin:23px 0 0; color:var(--muted); font-size:.8rem; }
    .secure-note i { color:var(--green); }
    .alert { border-radius:11px; font-size:.9rem; }
    .form-alert { margin:-16px 0 24px; }
    @media(max-width:900px) { .login-shell { grid-template-columns:1fr; padding:16px; } .brand-panel { display:none; } .form-side { min-height:calc(100vh - 32px); padding:32px 20px; } .mobile-brand { display:flex; align-items:center; gap:10px; margin-bottom:42px; font-size:1.02rem; font-weight:750; } .mobile-brand .brand-icon { color:var(--green); border-color:#dce8de; background:#e8f1e9; } }
    @media(max-width:480px) { .login-shell { padding:0; } .form-side { padding:28px 24px; } .mobile-brand { margin-bottom:54px; } .form-heading h2 { font-size:1.85rem; } }
    @media(prefers-reduced-motion:reduce) { *,*:before,*:after { scroll-behavior:auto!important; transition:none!important; } }
</style>
<main class="login-shell">
    <aside class="brand-panel" aria-label="Mini-POS overview">
        <div class="brand-top"><div class="brand-mark"><span class="brand-icon"><i class="bi bi-shop" aria-hidden="true"></i></span><span>Mini-POS</span></div></div>
        <div class="brand-copy">
            <div class="eyebrow">Your store, in sync</div>
            <h1>Run your business with confidence.</h1>
            <p>Keep sales moving, stock in check, and your day running smoothly from one simple place.</p>
        </div>
        <div class="brand-foot"><i class="bi bi-shield-check" aria-hidden="true"></i><span>A smarter way to manage your store</span></div>
    </aside>
    <section class="form-side" aria-labelledby="login-title">
        <div class="login-card">
            <div class="mobile-brand"><span class="brand-icon"><i class="bi bi-shop" aria-hidden="true"></i></span><span>Mini-POS</span></div>
            <header class="form-heading">
                <p class="welcome">Welcome back</p>
                <h2 id="login-title">Sign in to your account</h2>
                <p>Enter your details to access your store dashboard.</p>
            </header>

            <?php if ($error): ?><div class="alert alert-danger form-alert" role="alert"><i class="bi bi-exclamation-circle me-2" aria-hidden="true"></i><?= e($error) ?></div><?php endif; ?>
            <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?> form-alert" role="status"><?= e($flash['message']) ?></div><?php endif; ?>

            <form method="post" autocomplete="on">
                <?= csrfField() ?>
                <label class="field-label" for="username">Username</label>
                <div class="field-wrap">
                    <i class="bi bi-person field-icon" aria-hidden="true"></i>
                    <input type="text" class="field-control" id="username" name="username" placeholder="Enter your username" required autofocus autocomplete="username"
                           value="<?= e($_POST['username'] ?? '') ?>">
                </div>
                <label class="field-label" for="password">Password</label>
                <div class="field-wrap">
                    <i class="bi bi-lock field-icon" aria-hidden="true"></i>
                    <input type="password" class="field-control" id="password" name="password" placeholder="Enter your password" required autocomplete="current-password">
                    <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" id="password-toggle"><i class="bi bi-eye" aria-hidden="true"></i></button>
                </div>
                <button class="submit-btn" type="submit"><span>Sign in</span><i class="bi bi-arrow-right" aria-hidden="true"></i></button>
            </form>
            <p class="secure-note"><i class="bi bi-shield-lock" aria-hidden="true"></i><span>Your sign-in is protected and secure</span></p>
        </div>
    </section>
</main>
<script>
    const passwordInput = document.getElementById('password');
    const passwordToggle = document.getElementById('password-toggle');
    passwordToggle.addEventListener('click', () => {
        const reveal = passwordInput.type === 'password';
        passwordInput.type = reveal ? 'text' : 'password';
        passwordToggle.setAttribute('aria-pressed', String(reveal));
        passwordToggle.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
        passwordToggle.innerHTML = `<i class="bi ${reveal ? 'bi-eye-slash' : 'bi-eye'}" aria-hidden="true"></i>`;
    });
</script>
</body>
</html>
