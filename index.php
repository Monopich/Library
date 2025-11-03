<?php
session_start();
include('includes/config.php');

$debugFile = __DIR__ . '/sso_debug.log';
function log_debug($msg) {
    global $debugFile;
    file_put_contents($debugFile, "[" . date('Y-m-d H:i:s') . "] $msg\n", FILE_APPEND);
}

log_debug("=== Library login page loaded ===");

// ✅ 1. If already logged in, redirect
if (!empty($_SESSION['login'])) {
    log_debug("Already logged in, redirecting to dashboard.");
    header("Location: dashboard.php");
    exit;
}

// ✅ 2. Check if token passed from RTC system (auto-login)
if (!empty($_GET['token'])) {
    $token = $_GET['token'];
    $token = urldecode($token);
    log_debug("Token received from RTC: " . substr($token, 0, 20) . "...");

    if (rtcAutoLogin($token, $dbh)) {
        log_debug("✅ Auto-login successful, redirecting to dashboard");
        header("Location: dashboard.php");
        exit;
    } else {
        $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Auto-login failed. Please login manually.'];
        log_debug("❌ Auto-login failed");
    }
}

// ✅ 3. If no token in URL, show JavaScript checker
if (empty($_GET['token']) && empty($_SESSION['login'])) {
    echo "
    <script>
    console.log('🔍 Checking for RTC session...');
    const urlParams = new URLSearchParams(window.location.search);
    const tokenFromUrl = urlParams.get('token');
    
    if (tokenFromUrl) {
        console.log('✅ Token found in URL, auto-login should happen');
    } else {
        console.log('❌ No token in URL, showing login form');
        // Check localStorage for RTC session (same domain)
        const rtcToken = localStorage.getItem('rtc_auth_token');
        if (rtcToken) {
            console.log('🔄 RTC session found in localStorage, redirecting...');
            window.location.href = 'index.php?token=' + encodeURIComponent(rtcToken);
        }
    }
    </script>
    ";
}

// ✅ 3. Normal manual login (student/admin)
if (isset($_POST['login'])) {
    $email = trim($_POST['emailid']);
    $password = $_POST['password'];

    // --- Admin login ---
    $sqlAdmin = "SELECT UserName, Password FROM admin WHERE UserName = :email";
    $query = $dbh->prepare($sqlAdmin);
    $query->bindParam(':email', $email);
    $query->execute();
    $admin = $query->fetch(PDO::FETCH_OBJ);

    if ($admin && $admin->Password === md5($password)) {
        $_SESSION['alogin'] = $email;
        header("Location: admin/dashboard.php");
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
        $_SESSION['toast'] = ['type' => 'danger', 'message' => "Network error: $error"];
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
        }
        $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Login successful but failed to start session.'];
    } else {
        $msg = $result['message'] ?? "Login failed (HTTP $status)";
        $_SESSION['toast'] = ['type' => 'danger', 'message' => $msg];
    }
}
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
