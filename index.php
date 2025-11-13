<?php
session_start();
include('includes/config.php');

$debugFile = __DIR__ . '/sso_debug.log';
function log_debug($msg)
{
    global $debugFile;
    file_put_contents($debugFile, "[" . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

log_debug("=== Library login page loaded ===");

// ✅ Auto-login using RTC token
function rtcAutoLogin($token, $dbh)
{
    log_debug("⚡ Starting RTC auto-login with token: $token");

    try {
        $ch = curl_init("https://api.rtc-bb.camai.kh/api/auth/get_detail_user");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $token"],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        log_debug("RTC API response code: $httpCode");
        log_debug("RTC API response: $response");

        if ($httpCode !== 200 || !$response) {
            throw new Exception("Invalid token or failed to reach RTC API");
        }

        $data = json_decode($response, true);
        if (!isset($data['user'])) {
            throw new Exception("Invalid response: missing user data");
        }

        $user = $data['user'];
        $email = $user['email'];
        $studentId = $user['user_detail']['id_card'] ?? uniqid('rtc_');
        $fullName = $user['user_detail']['latin_name'] ?? ($user['name'] ?? 'Unknown');
        $phone = $user['user_detail']['phone_number'] ?? '';

        // ✅ Check existing user
        $checkUser = $dbh->prepare("SELECT StudentId FROM tblstudents WHERE EmailId = :email");
        $checkUser->bindParam(':email', $email);
        $checkUser->execute();

        if ($checkUser->rowCount() > 0) {
            // Update existing user
            $update = $dbh->prepare("
                UPDATE tblstudents SET FullName = :name, MobileNumber = :mobile, Status = 1 WHERE EmailId = :email
            ");
            $update->execute([':name' => $fullName, ':mobile' => $phone, ':email' => $email]);
        } else {
            // Create new user
            $insert = $dbh->prepare("
                INSERT INTO tblstudents (StudentId, FullName, EmailId, MobileNumber, Password, Status)
                VALUES (:id, :name, :email, :mobile, :password, 1)
            ");
            $defaultPassword = md5(uniqid());
            $insert->execute([
                ':id' => $studentId,
                ':name' => $fullName,
                ':email' => $email,
                ':mobile' => $phone,
                ':password' => $defaultPassword,
            ]);
        }

        // ✅ Set library session
        $_SESSION['login'] = true;
        $_SESSION['stdid'] = $studentId;
        $_SESSION['username'] = $fullName;
        $_SESSION['email'] = $email;
        $_SESSION['roles'] = $user['roles'] ?? ['Student'];
        $_SESSION['rtc_token'] = $token;

        log_debug("✅ Auto-login success for user: $fullName ($email)");
        return true;

    } catch (Exception $e) {
        log_debug("⚠️ Auto-login failed: " . $e->getMessage());
        $_SESSION['toast'] = ['type' => 'danger', 'message_key' => 'invalid_credentials'];
        return false;
    }
}

// ✅ 1. If already logged in, validate token sync
if (!empty($_SESSION['login'])) {
    log_debug("Already logged in, checking token validity.");

    // Check if session token matches cookie token (for cross-domain sync)
    $cookieToken = $_COOKIE['auth_token'] ?? null;
    $sessionToken = $_SESSION['rtc_token'] ?? null;

    if ($cookieToken && $sessionToken && $cookieToken !== $sessionToken) {
        // Token changed - need to re-authenticate
        log_debug("Token mismatch detected - cookie: " . substr($cookieToken, 0, 20) . "... vs session: " . substr($sessionToken, 0, 20) . "...");

        // Clear old session
        session_unset();
        session_destroy();
        session_start();

        // Try to re-authenticate with new token
        if (rtcAutoLogin($cookieToken, $dbh)) {
            log_debug("Re-authentication successful with new token");
            header("Location: dashboard.php");
            exit;
        }

        log_debug("Re-authentication failed, showing login page");
    } else if (!$cookieToken && $sessionToken) {
        // Cookie removed (main server logged out)
        log_debug("Cookie removed but session exists - main server logged out");
        session_unset();
        session_destroy();
        session_start();
        $_SESSION['toast'] = ['type' => 'info', 'message_key' => 'login_session_fail'];
    } else {
        // Valid session
        log_debug("Session valid, redirecting to dashboard.");
        header("Location: dashboard.php");
        exit;
    }
}

// ✅ 2. Check if token passed from bridge page (GET/POST) - FIXED TO HANDLE URL DECODING
if (!empty($_GET['token']) || !empty($_POST['token'])) {
    $token = $_GET['token'] ?? $_POST['token'];

    // ✅ DECODE URL-ENCODED TOKEN
    $token = urldecode($token);
    log_debug("Token received for auto-login: $token");

    if (rtcAutoLogin($token, $dbh)) {
        log_debug("Auto-login successful, redirecting to dashboard");
        header("Location: dashboard.php");
        exit;
    } else {
        $_SESSION['toast'] = ['type' => 'danger', 'message_key' => 'invalid_credentials'];
        log_debug("Auto-login failed, showing manual login");
    }
}


// ✅ 3. Normal manual login (student/admin)
if (isset($_POST['login'])) {
    $email = trim($_POST['emailid']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $_SESSION['toast'] = ['type' => 'warning', 'message_key' => 'enter_email_password'];
        header("Location: index.php");
        exit;
    }

    // --- Admin login ---
    $sqlAdmin = "SELECT UserName, Password FROM admin WHERE UserName = :email";
    $query = $dbh->prepare($sqlAdmin);
    $query->bindParam(':email', $email);
    $query->execute();
    $admin = $query->fetch(PDO::FETCH_OBJ);

    if ($admin) {
        if ($admin->Password === md5($password)) {
            $_SESSION['alogin'] = $email;
            header("Location: admin/dashboard.php");
            exit;
        } else {
            $_SESSION['toast'] = ['type' => 'danger', 'message_key' => 'admin_incorrect_password'];
            header("Location: index.php");
            exit;
        }
    }

     // --- Student login via RTC API ---
    $checkUser = $dbh->prepare("SELECT StudentId, FullName FROM tblstudents WHERE EmailId = :email");
    $checkUser->bindParam(':email', $email);
    $checkUser->execute();
    $student = $checkUser->fetch(PDO::FETCH_OBJ);

    if (!$student) {
        $_SESSION['toast'] = ['type' => 'danger', 'message_key' => 'email_not_found'];
        header("Location: index.php");
        exit;
    }

    // --- RTC API login ---
    $apiLoginUrl = "https://api.rtc-bb.camai.kh/api/auth/login";
    $payload = json_encode(['email' => $email, 'password' => $password]);

    $ch = curl_init($apiLoginUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        $_SESSION['toast'] = ['type' => 'danger', 'message_key' => 'network_error'];
        header("Location: index.php");
        exit;
    }

    $result = json_decode($response, true);
    log_debug("RTC login response: " . print_r($result, true));

    $token = $result['token'] ?? $result['access_token'] ?? ($result['data']['token'] ?? null);

    if ($status === 200 && $token) {
        if (rtcAutoLogin($token, $dbh)) {
            header("Location: dashboard.php");
            exit;
        } else {
            $_SESSION['toast'] = ['type' => 'danger', 'message_key' => 'login_session_fail'];
            header("Location: index.php");
            exit;
        }
    } else {
        $msg = $result['message'] ?? "Incorrect password.";
        $_SESSION['toast'] = ['type' => 'danger', 'message_key' => 'invalid_credentials'];
        header("Location: index.php");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['library_login'] ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <style>
        body {
            background: #f0f2f5;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            font-family: 'Segoe UI', sans-serif;
        }

        .login-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
            padding: 35px 30px;
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-logo {
            width: 250px;
            margin-bottom: 20px;
        }

        .login-title {
            font-weight: 600;
            margin-bottom: 25px;
            color: #1a3d7c;
        }

        .input-group {
            position: relative;
        }

        .input-group .form-control {
            border-radius: 8px !important;
            padding-right: 40px;
        }

        .input-group .toggle-password {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            border: none;
            background: none;
            cursor: pointer;
            font-size: 1.1rem;
            color: #6c757d;
        }

        .btn-primary {
            width: 100%;
            border-radius: 8px;
            padding: 10px;
            font-weight: 500;
        }

        .btn-primary:hover {
            background: #1a3d7c;
        }

        .small-link {
            font-size: 0.875rem;
            margin-top: 10px;
            display: block;
        }

        .language-switch select {
            font-weight: 500;
            color: #1a3d7c;
        }

        .language-switch select:focus {
            border-color: #1a3d7c;
            box-shadow: 0 0 0 0.2rem rgba(26, 61, 124, 0.25);
        }

    </style>
</head>

<body>

<div class="login-card position-relative">
    

    <img src="assets/img/login-logo.png" alt="Library Logo" class="login-logo">
    <h3 class="login-title"><?= $lang['library_login'] ?></h3>

    <form method="post">
        <div class="mb-1 text-start">
            <label for="emailid" class="form-label"><?= $lang['email_or_username'] ?></label>
            <input type="text" class="form-control" name="emailid" id="emailid" required autocomplete="off">
        </div>

        <div class="mb-1 text-start">
            <label for="password" class="form-label"><?= $lang['password'] ?></label>
            <div class="input-group">
                <input type="password" class="form-control" name="password" id="password" required autocomplete="off">
                <button type="button" class="toggle-password" id="togglePassword"><i class="bi bi-eye"></i></button>
            </div>
            <a href="user-forgot-password.php" class="small-link"><?= $lang['forgot_password'] ?></a>
        </div>

        <button type="submit" name="login" class="btn btn-primary mt-2"><?= $lang['login'] ?></button>
        <span class="small-link mt-3"><?= $lang['no_account'] ?> <a href="signup.php"><?= $lang['register_here'] ?></a></span>
    </form>

    <!-- 🌐 Language Switcher -->
    <div class="language-switch d-flex justify-content-center mt-3">
        <form method="get" action="">
            <select name="lang" onchange="this.form.submit()" class="form-select form-select-sm fw-bold text-primary" style="width: 160px; cursor: pointer;">
                <option value="en" <?= ($_SESSION['lang'] ?? 'en') === 'en' ? 'selected' : '' ?>>🇬🇧 English</option>
                <option value="kh" <?= ($_SESSION['lang'] ?? 'en') === 'kh' ? 'selected' : '' ?>>🇰🇭 ភាសាខ្មែរ</option>
            </select>
        </form>
    </div>

</div>

<div class="position-fixed top-0 end-0 p-3" style="z-index:1055">
  <div id="liveToast" class="toast align-items-center text-white border-0" role="alert">
    <div class="d-flex">
      <div class="toast-body" id="toast-message"></div>
      <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    <?php
    if (isset($_SESSION['toast'])) {
    $toast = $_SESSION['toast'];
    $bg = match ($toast['type']) {
        'success' => 'bg-success',
        'danger' => 'bg-danger',
        'warning' => 'bg-warning text-dark',
        'info' => 'bg-info text-dark',
        default => 'bg-secondary',
    };

    // Translate message if a key exists in $lang
    $msgKey = $toast['message_key'] ?? null; // optional: use 'message_key' instead of direct message
    $message = $msgKey && isset($lang[$msgKey]) ? $lang[$msgKey] : ($toast['message'] ?? '');

    echo "document.addEventListener('DOMContentLoaded',()=>{
        const toastEl=document.getElementById('liveToast');
        const toastBody=document.getElementById('toast-message');
        toastEl.className='toast align-items-center border-0 $bg';
        toastBody.textContent='".addslashes($message)."';
        new bootstrap.Toast(toastEl).show();
    });";

    unset($_SESSION['toast']);
}
    ?>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const pw = document.getElementById('password');
        const icon = this.querySelector('i');
        if (pw.type === 'password') {
            pw.type = 'text';
            icon.classList.replace('bi-eye', 'bi-eye-slash');
        } else {
            pw.type = 'password';
            icon.classList.replace('bi-eye-slash', 'bi-eye');
        }
    });

    // ✅ Cross-domain auth synchronization for library subdomain
    (function () {
        'use strict';

        console.log('🔄 Library Auth Sync initialized');

        const CHECK_INTERVAL = 5000; // 5 seconds
        const COOKIE_NAME = 'auth_token';

        function getCookie(name) {
            const nameEQ = name + "=";
            const cookies = document.cookie.split(';');
            for (let i = 0; i < cookies.length; i++) {
                let c = cookies[i];
                while (c.charAt(0) === ' ') c = c.substring(1, c.length);
                if (c.indexOf(nameEQ) === 0) {
                    return c.substring(nameEQ.length, c.length);
                }
            }
            return null;
        }

        let lastKnownToken = getCookie(COOKIE_NAME);
        console.log('🔑 Initial token:', lastKnownToken ? lastKnownToken.substring(0, 20) + '...' : 'none');

        function checkTokenSync() {
            const currentToken = getCookie(COOKIE_NAME);

            if (currentToken !== lastKnownToken) {
                console.log('🔄 Token change detected!');
                console.log('🔄 Old:', lastKnownToken ? lastKnownToken.substring(0, 20) + '...' : 'none');
                console.log('🔄 New:', currentToken ? currentToken.substring(0, 20) + '...' : 'none');

                if (!currentToken) {
                    console.log('🚪 Main server logged out - redirecting...');
                    window.location.href = 'index.php?reason=logged_out';
                } else if (lastKnownToken && currentToken !== lastKnownToken) {
                    console.log('🔄 Account switched - re-authenticating...');
                    window.location.href = 'index.php?token=' + encodeURIComponent(currentToken) + '&reason=account_switched';
                }

                lastKnownToken = currentToken;
            }
        }

        // Listen for storage events from main server
        window.addEventListener('storage', function (event) {
            if (event.key === 'auth_sync' && event.newValue) {
                try {
                    const authState = JSON.parse(event.newValue);
                    console.log('🔄 Storage event from main:', authState.action);

                    if (authState.action === 'logout') {
                        console.log('🚪 Main logout detected - redirecting...');
                        window.location.href = 'index.php?reason=main_logout';
                    } else if (authState.action === 'login') {
                        console.log('✅ Main login detected - checking token...');
                        setTimeout(checkTokenSync, 1000);
                    }
                } catch (err) {
                    console.error('Error processing storage event:', err);
                }
            }
        });

        // Poll for changes
        setInterval(checkTokenSync, CHECK_INTERVAL);
        console.log('🔄 Token sync polling started (every 5s)');

        // Check on window focus
        window.addEventListener('focus', checkTokenSync);

        console.log('✅ Library Auth Sync ready');
    })();
</script>
</body>

</html>