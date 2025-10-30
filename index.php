<?php
// ------------------------
// Library index.php - Token-based auto-login (no cookies)
// ------------------------

session_start();

include('includes/config.php');

// ------------------------
// Debug logging function
// ------------------------
$logFile = __DIR__ . '/sso_debug.log';
function log_debug($msg){
    global $logFile;
    file_put_contents($logFile, "[".date('Y-m-d H:i:s')."] $msg\n", FILE_APPEND);
}

// Enable PHP errors for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

log_debug("=== Page load started ===");

// ------------------------
// 1️⃣ Check if already logged in locally
// ------------------------
if (!empty($_SESSION['login'])) {
    log_debug("User already logged in locally: " . $_SESSION['username']);
    header('Location: dashboard.php');
    exit;
}

// ------------------------
// 2️⃣ Auto-login using token from URL
// ------------------------
if (!empty($_GET['access_token'])) {
    $token = $_GET['access_token'];
    log_debug("Found access_token via URL: $token");

    try {
        $ch = curl_init("https://rtc-bb.camai.kh/api/auth/get_detail_user");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $token"],
            CURLOPT_SSL_VERIFYPEER => false
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        log_debug("RTC API HTTP Code: $httpCode");
        log_debug("RTC API Response: " . $response);

        if ($httpCode === 200) {
            $user = json_decode($response, true);
            log_debug("Decoded user data: " . print_r($user, true));

            if (!empty($user['user']['id'])) {
                // Set Library session
                $_SESSION['login'] = true;
                $_SESSION['stdid'] = $user['user']['user_detail']['id_card'] ?? null;
                $_SESSION['username'] = $user['user']['name'] ?? 'Unknown';
                $_SESSION['roles'] = $user['user']['roles'] ?? ['Student'];

                log_debug("Token-based login successful. Redirecting to dashboard.");
                header('Location: dashboard.php');
                exit;
            } else {
                log_debug("RTC API returned invalid user data.");
            }
        } else {
            log_debug("RTC API call failed or returned non-200 HTTP code.");
        }
    } catch (Exception $e) {
        log_debug("Token-based auto-login exception: " . $e->getMessage());
    }
} else {
    log_debug("No access_token in URL. User will see login page.");
}

// ------------------------
// 3️⃣ Local login (admin/Library user)
// ------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['emailid']);
    $password = $_POST['password'];
    log_debug("Attempting local login for: $email");

    // Admin login
    $sqlAdmin = "SELECT UserName, Password FROM admin WHERE UserName = :email";
    $queryAdmin = $dbh->prepare($sqlAdmin);
    $queryAdmin->bindParam(':email', $email, PDO::PARAM_STR);
    $queryAdmin->execute();
    $adminResult = $queryAdmin->fetch(PDO::FETCH_OBJ);

    if ($adminResult && $adminResult->Password === md5($password)) {
        $_SESSION['alogin'] = $email;
        log_debug("Local admin login successful: $email");
        header("Location: admin/dashboard.php");
        exit;
    } else {
        log_debug("Local login failed for: $email");
        $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Invalid credentials or not logged in via RTC.'];
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
<title>Library Management | Login</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
body { background: #f0f2f5; min-height: 100vh; display: flex; justify-content: center; align-items: center; font-family: 'Segoe UI', sans-serif; }
.login-card { background: #fff; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); padding: 35px 30px; width: 100%; max-width: 400px; text-align: center; }
.login-logo { width: 150px; margin-bottom: 20px; }
.login-title { font-weight: 600; margin-bottom: 25px; color: #1a3d7c; }
.input-group .form-control { border-radius: 8px !important; padding-right: 40px; }
.input-group .toggle-password { position: absolute; top: 50%; right: 10px; transform: translateY(-50%); border: none; background: none; cursor: pointer; font-size: 1.1rem; color: #6c757d; }
.btn-primary { width: 100%; border-radius: 8px; padding: 10px; font-weight: 500; }
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
                <button type="button" class="toggle-password"><i class="bi bi-eye"></i></button>
            </div>
            <a href="user-forgot-password.php" class="small-link">Forgot password?</a>
        </div>

        <button type="submit" name="login" class="btn btn-primary mt-2">Login</button>
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
// Toggle password visibility
document.querySelector('.toggle-password').addEventListener('click', function(){
    const pw = document.getElementById('password');
    const icon = this.querySelector('i');
    if(pw.type === 'password'){ pw.type='text'; icon.classList.replace('bi-eye','bi-eye-slash'); }
    else{ pw.type='password'; icon.classList.replace('bi-eye-slash','bi-eye'); }
});

// Toast messages
<?php if(isset($_SESSION['toast'])): 
    $toast=$_SESSION['toast'];
    $bg=match($toast['type']){
        'success'=>'bg-success',
        'danger'=>'bg-danger',
        'warning'=>'bg-warning text-dark',
        'info'=>'bg-info text-dark',
        default=>'bg-secondary',
    };
?>
document.addEventListener('DOMContentLoaded', ()=>{
    const toastEl=document.getElementById('liveToast');
    const toastBody=document.getElementById('toast-message');
    toastEl.className='toast align-items-center border-0 <?= $bg ?>';
    toastBody.textContent='<?= $toast['message'] ?>';
    new bootstrap.Toast(toastEl).show();
});
<?php unset($_SESSION['toast']); endif; ?>
</script>
</body>
</html>
