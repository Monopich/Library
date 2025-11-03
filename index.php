<?php
session_start();
include('includes/config.php');

$debugFile = __DIR__ . '/sso_debug.log';
function log_debug($msg) {
    global $debugFile;
    file_put_contents($debugFile, "[" . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

log_debug("=== Library login page loaded ===");

function rtcAutoLogin($token, $dbh = null) {
    // Do not log the raw token. Log only a masked version for debugging.
    $masked = substr($token, 0, 6) . '...' . substr($token, -4);
    log_debug("rtcAutoLogin: attempting validation for token: {$masked}");

    $apiUrl = "https://api.rtc-bb.camai.kh/api/auth/get_detail_user";

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "Authorization: Bearer {$token}",
            "Accept: application/json"
        ],
        CURLOPT_TIMEOUT => 10,
        // In production, verify peer. Make sure your system has CA certs.
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_err = null;
    if ($response === false) {
        $curl_err = curl_error($ch);
        log_debug("rtcAutoLogin: cURL error: " . $curl_err);
    }
    curl_close($ch);

    if ($response === false) {
        return false;
    }

    // decode response
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        log_debug("rtcAutoLogin: JSON decode error: " . json_last_error_msg());
        return false;
    }

    // Typical shape: ['status' => ..., 'data' => ['user' => [...]] ] or ['user' => ...]
    $user = null;
    if (!empty($data['data']['user'])) {
        $user = $data['data']['user'];
    } elseif (!empty($data['user'])) {
        $user = $data['user'];
    } elseif (!empty($data['data'])) {
        // sometimes get_detail_user returns data directly
        $user = $data['data'];
    }

    if ($http_code === 200 && !empty($user) && !empty($user['id'])) {
        // Create local session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'] ?? '';
        $_SESSION['user_name'] = $user['name'] ?? ($user['fullname'] ?? '');
        $_SESSION['sso_provider'] = 'rtc';

        log_debug("rtcAutoLogin: success for user_id=" . $_SESSION['user_id']);

        // Optional: sync local DB user if you need it
        if (!empty($dbh)) {
            try {
                // example: upsert user into local users table - adapt to your schema
                $sth = $dbh->prepare("INSERT INTO users (id, email, name, updated_at) VALUES (:id, :email, :name, NOW())
                    ON DUPLICATE KEY UPDATE email = VALUES(email), name = VALUES(name), updated_at = NOW()");
                $sth->execute([
                    ':id' => $user['id'],
                    ':email' => $user['email'] ?? null,
                    ':name' => $_SESSION['user_name'],
                ]);
            } catch (Exception $e) {
                // log but don't fail the login
                log_debug("rtcAutoLogin: DB sync failed: " . $e->getMessage());
            }
        }

        return true;
    }

    // token invalid or unexpected response
    log_debug("rtcAutoLogin: validation failed (http_code={$http_code}). Response: " . substr(json_encode($data), 0, 400));
    return false;
}

// ---------------------------
// 1) If cookie 'auth_token' exists, attempt auto-login
// ---------------------------
$token = null;
if (!empty($_COOKIE['auth_token'])) {
    $token = $_COOKIE['auth_token'];
    // Attempt to login using cookie token
    if (rtcAutoLogin($token, $dbh ?? null)) {
        // success
        header("Location: dashboard.php");
        exit;
    } else {
        // Optionally clear cookie when invalid to avoid repeated failed attempts
        setcookie('auth_token', '', [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '.camai.kh',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'None'
        ]);
        log_debug("Cleared invalid auth_token cookie.");
        // do not exit; allow other login methods (GET/POST) and render login form
    }
}

// ---------------------------
// 2) Check GET/POST token (bridge from RTC portal)
// ---------------------------
if (empty($token) && (!empty($_GET['token']) || !empty($_POST['token']))) {
    $token = $_GET['token'] ?? $_POST['token'];

    // URL decoding, some bridges encode token in URL
    $token = urldecode($token);

    log_debug("Found bridge token via GET/POST (masked) - attempting rtcAutoLogin.");
    if (rtcAutoLogin($token, $dbh ?? null)) {
        // On success, optionally set cookie for future cross-subdomain auth
        setcookie('auth_token', $token, [
            'expires' => time() + 60*60*24*30,
            'path' => '/',
            'domain' => '.camai.kh',
            'secure' => true,
            'httponly' => true,
            'samesite' => 'None'
        ]);
        header("Location: dashboard.php");
        exit;
    } else {
        $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Login failed via SSO token.'];
    }
}

// ---------------------------
// 3) (Optional) Handle local/manual login form submission
//    Replace with actual local auth logic if you have one.
// ---------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['email'], $_POST['password']) && empty($_POST['token'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Example local auth - adapt to your DB structure and password hashing
    try {
        if (!empty($dbh)) {
            $sth = $dbh->prepare("SELECT id, password_hash, name FROM users WHERE email = :email LIMIT 1");
            $sth->execute([':email' => $email]);
            $row = $sth->fetch(PDO::FETCH_ASSOC);
            if ($row && password_verify($password, $row['password_hash'])) {
                $_SESSION['user_id'] = $row['id'];
                $_SESSION['user_email'] = $email;
                $_SESSION['user_name'] = $row['name'] ?? '';
                header("Location: dashboard.php");
                exit;
            } else {
                $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Invalid email or password.'];
            }
        } else {
            // If no DB, reject and log
            $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Local login not available.'];
            log_debug("Local login attempted but \$dbh not configured.");
        }
    } catch (Exception $e) {
        log_debug("Local login error: " . $e->getMessage());
        $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Login error.'];
    }
}
// ---------------------------
// If reached here, no auto-login succeeded: show login page
// ---------------------------
$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Library Management | Login</title>
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
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
    padding: 35px 30px;
    width: 100%;
    max-width: 400px;
    text-align: center;
}
.login-logo {
    width: 150px;
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
.btn-primary:hover { background: #1a3d7c; }
.small-link { font-size: 0.875rem; margin-top: 10px; display: block; }
</style>
</head>
<body>

<div class="login-card">
    <img src="assets/img/login-logo.png" alt="Library Logo" class="login-logo">
    <h3 class="login-title">Library Login</h3>

    <form method="post">
        <div class="mb-1 text-start">
            <label for="emailid" class="form-label">Email or Admin Username</label>
            <input type="text" class="form-control" name="emailid" id="emailid" required autocomplete="off">
        </div>

        <div class="mb-1 text-start">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <input type="password" class="form-control" name="password" id="password" required autocomplete="off">
                <button type="button" class="toggle-password" id="togglePassword"><i class="bi bi-eye"></i></button>
            </div>
            <a href="user-forgot-password.php" class="small-link">Forgot password?</a>
        </div>

        <button type="submit" name="login" class="btn btn-primary mt-2">Login</button>
        <span class="small-link mt-3">Don’t have an account? <a href="signup.php">Register here</a></span>
    </form>
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
if(isset($_SESSION['toast'])){
    $toast=$_SESSION['toast'];
    $bg = match($toast['type']){
        'success'=>'bg-success',
        'danger'=>'bg-danger',
        'warning'=>'bg-warning text-dark',
        'info'=>'bg-info text-dark',
        default=>'bg-secondary',
    };
    echo "document.addEventListener('DOMContentLoaded',()=>{
        const toastEl=document.getElementById('liveToast');
        const toastBody=document.getElementById('toast-message');
        toastEl.className='toast align-items-center border-0 $bg';
        toastBody.textContent='{$toast['message']}';
        new bootstrap.Toast(toastEl).show();
    });";
    unset($_SESSION['toast']);
}
?>
document.getElementById('togglePassword').addEventListener('click', function(){
    const pw = document.getElementById('password');
    const icon = this.querySelector('i');
    if(pw.type === 'password'){
        pw.type = 'text';
        icon.classList.replace('bi-eye', 'bi-eye-slash');
    }else{
        pw.type = 'password';
        icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
});
</script>
</body>
</html>
