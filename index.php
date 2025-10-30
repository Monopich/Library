<?php
session_start();
include('includes/config.php');

// 1️⃣ Check if already logged in to Library
if (!empty($_SESSION['login'])) {
    header('Location: dashboard.php');
    exit;
}

// 2️⃣ Auto-login from RTC token
$rtcToken = $_COOKIE['access_token'] ?? null;

if ($rtcToken && !isset($_POST['login'])) {
    try {
        // Verify token with RTC API
        $ch = curl_init("https://api.rtc-bb.camai.kh/api/auth/get_detail_user");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $rtcToken"],
            CURLOPT_SSL_VERIFYPEER => true, // Changed to true for security
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FAILONERROR => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($httpCode === 200 && !empty($response)) {
            $userData = json_decode($response, true);
            
            if (isset($userData['user']['id'])) {
                $user = $userData['user'];
                
                // Extract user information
                $studentId = $user['user_detail']['id_card'] ?? null;
                $fullName = $user['user_detail']['latin_name'] ?? $user['name'] ?? 'Unknown';
                $phoneNumber = $user['user_detail']['phone_number'] ?? '';
                $emailId = $user['email'] ?? '';
                $roles = $user['roles'] ?? ['Student'];

                // Validate required fields
                if (empty($emailId)) {
                    throw new Exception("Email not found in user data");
                }

                // Sync with local database
                if ($emailId) {
                    $checkUser = $dbh->prepare("SELECT StudentId FROM tblstudents WHERE EmailId = :email");
                    $checkUser->bindParam(':email', $emailId);
                    $checkUser->execute();

                    if ($checkUser->rowCount() > 0) {
                        // Update existing user
                        $updateUser = $dbh->prepare("UPDATE tblstudents SET FullName = :name, MobileNumber = :mobile, Status = 1 WHERE EmailId = :email");
                        $updateUser->bindParam(':name', $fullName);
                        $updateUser->bindParam(':mobile', $phoneNumber);
                        $updateUser->bindParam(':email', $emailId);
                        $updateUser->execute();
                    } else {
                        // Insert new user with proper validation
                        if (empty($studentId)) {
                            $studentId = 'LIB_' . uniqid(); // Generate library ID if no student ID
                        }
                        
                        $insertUser = $dbh->prepare("
                            INSERT INTO tblstudents (StudentId, FullName, EmailId, MobileNumber, Password, Status) 
                            VALUES (:id, :name, :email, :mobile, :password, 1)
                        ");
                        $insertUser->bindParam(':id', $studentId);
                        $insertUser->bindParam(':name', $fullName);
                        $insertUser->bindParam(':email', $emailId);
                        $insertUser->bindParam(':mobile', $phoneNumber);
                        $insertUser->bindValue(':password', md5(uniqid() . time())); // Random password
                        $insertUser->execute();
                    }

                    // Create Library session
                    $_SESSION['login'] = true;
                    $_SESSION['stdid'] = $studentId;
                    $_SESSION['username'] = $fullName;
                    $_SESSION['email'] = $emailId;
                    $_SESSION['roles'] = $roles;
                    $_SESSION['token_external'] = $rtcToken;

                    // Redirect to dashboard immediately
                    header('Location: dashboard.php');
                    exit;
                }
            } else {
                throw new Exception("Invalid user data structure");
            }
        } else {
            // Token is invalid, clear it
            setcookie('access_token', '', time() - 3600, '/', '.rtc-bb.camai.kh'); // Added domain for cross-subdomain
            error_log("RTC token verification failed: HTTP $httpCode - $curlError");
        }
    } catch (Exception $e) {
        error_log("RTC auto-login error: " . $e->getMessage());
        // Clear invalid token
        setcookie('access_token', '', time() - 3600, '/', '.rtc-bb.camai.kh');
    }
}

// 3️⃣ Manual login processing (only shown if no valid RTC token)
if (isset($_POST['login'])) {
    $email = trim($_POST['emailid']);
    $password = $_POST['password'];

    // Validate input
    if (empty($email) || empty($password)) {
        $_SESSION['toast'] = ['type' => 'danger', 'message' => "Email and password are required"];
        header("Location: index.php");
        exit;
    }

    // Local admin login
    $sqlAdmin = "SELECT UserName, Password FROM admin WHERE UserName = :email";
    $queryAdmin = $dbh->prepare($sqlAdmin);
    $queryAdmin->bindParam(':email', $email, PDO::PARAM_STR);
    $queryAdmin->execute();
    $adminResult = $queryAdmin->fetch(PDO::FETCH_OBJ);

    if ($adminResult && $adminResult->Password === md5($password)) {
        $_SESSION['alogin'] = $email;
        header("Location: admin/dashboard.php");
        exit;
    }

    // External API login
    $apiLoginUrl = "https://api.rtc-bb.camai.kh/api/auth/login";
    $payload = json_encode(['email' => $email, 'password' => $password]);

    $ch = curl_init($apiLoginUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
        CURLOPT_SSL_VERIFYPEER => true, // Changed to true for security
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FAILONERROR => true
    ]);
    
    $response = curl_exec($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($curlError) {
        $_SESSION['toast'] = ['type' => 'danger', 'message' => "Connection error: Please try again later"];
        header("Location: index.php");
        exit;
    }

    $result = json_decode($response, true);
    $token = $result['token'] ?? $result['access_token'] ?? ($result['data']['token'] ?? null);

    if ($statusCode === 200 && $token) {
        // Set RTC token cookie for future auto-login (cross-subdomain)
        setcookie('access_token', $token, [
            'expires' => time() + (30 * 24 * 60 * 60),
            'path' => '/',
            'domain' => '.rtc-bb.camai.kh', // Important for cross-subdomain
            'secure' => true,
            'httponly' => true,
            'samesite' => 'Lax'
        ]);

        // Get user details and create session
        $ch = curl_init("https://api.rtc-bb.camai.kh/api/auth/get_detail_user");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $token"],
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FAILONERROR => true
        ]);
        
        $userResponse = curl_exec($ch);
        $userHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($userHttpCode === 200 && !empty($userResponse)) {
            $userData = json_decode($userResponse, true);
            
            if (isset($userData['user']['id'])) {
                $user = $userData['user'];
                $studentId = $user['user_detail']['id_card'] ?? null;
                $fullName = $user['user_detail']['latin_name'] ?? $user['name'] ?? 'Unknown';
                $phoneNumber = $user['user_detail']['phone_number'] ?? '';
                $emailId = $user['email'] ?? $email;
                $roles = $user['roles'] ?? ['Student'];

                // Generate library ID if no student ID
                if (empty($studentId)) {
                    $studentId = 'LIB_' . uniqid();
                }

                // Sync with local database
                if ($emailId) {
                    $checkUser = $dbh->prepare("SELECT StudentId FROM tblstudents WHERE EmailId = :email");
                    $checkUser->bindParam(':email', $emailId);
                    $checkUser->execute();

                    if ($checkUser->rowCount() > 0) {
                        $updateUser = $dbh->prepare("UPDATE tblstudents SET FullName = :name, MobileNumber = :mobile, Status = 1 WHERE EmailId = :email");
                        $updateUser->bindParam(':name', $fullName);
                        $updateUser->bindParam(':mobile', $phoneNumber);
                        $updateUser->bindParam(':email', $emailId);
                        $updateUser->execute();
                    } else {
                        $insertUser = $dbh->prepare("
                            INSERT INTO tblstudents (StudentId, FullName, EmailId, MobileNumber, Password, Status) 
                            VALUES (:id, :name, :email, :mobile, :password, 1)
                        ");
                        $insertUser->bindParam(':id', $studentId);
                        $insertUser->bindParam(':name', $fullName);
                        $insertUser->bindParam(':email', $emailId);
                        $insertUser->bindParam(':mobile', $phoneNumber);
                        $insertUser->bindValue(':password', md5(uniqid() . time()));
                        $insertUser->execute();
                    }

                    // Create Library session
                    $_SESSION['login'] = true;
                    $_SESSION['stdid'] = $studentId;
                    $_SESSION['username'] = $fullName;
                    $_SESSION['email'] = $emailId;
                    $_SESSION['roles'] = $roles;
                    $_SESSION['token_external'] = $token;

                    header("Location: dashboard.php");
                    exit;
                }
            }
        }
        
        // If user details fetch failed but login was successful
        $_SESSION['toast'] = ['type' => 'warning', 'message' => "Login successful but user details incomplete"];
        header("Location: index.php");
        exit;
        
    } else {
        $msg = $result['message'] ?? "Invalid email or password";
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
    padding: 0;
    margin: 0;
}
.btn-primary {
    width: 100%;
    border-radius: 8px;
    padding: 10px;
    font-weight: 500;
}
.btn-primary:hover { background: #1a3d7c; }
.small-link { font-size: 0.875rem; margin-top: 10px; display: block; }
.auto-login-notice {
    background: #e7f3ff;
    border: 1px solid #b3d9ff;
    border-radius: 8px;
    padding: 15px;
    margin-bottom: 20px;
    font-size: 0.9rem;
    color: #0066cc;
}
</style>
</head>
<body>

<div class="login-card">
    <img src="assets/img/login-logo.png" alt="Library Logo" class="login-logo">
    <h3 class="login-title">Library Login</h3>

    <!-- Show loading message if auto-login is in progress -->
    <?php if ($rtcToken && !isset($_POST['login'])): ?>
    <div class="auto-login-notice">
        <div class="spinner-border spinner-border-sm me-2" role="status"></div>
        Checking RTC session... Redirecting automatically
    </div>
    <script>
        // Small delay to show the message, then redirect
        setTimeout(() => {
            window.location.reload();
        }, 1000);
    </script>
    <?php endif; ?>

    <form method="post">
        <div class="mb-3 text-start">
            <label for="emailid" class="form-label">Email or Admin Username</label>
            <input type="text" class="form-control" name="emailid" id="emailid" required autocomplete="off" 
                   value="<?php echo isset($_POST['emailid']) ? htmlspecialchars($_POST['emailid']) : ''; ?>">
        </div>

        <div class="mb-3 text-start">
            <label for="password" class="form-label">Password</label>
            <div class="input-group">
                <input type="password" class="form-control" name="password" id="password" required autocomplete="off">
                <button type="button" class="toggle-password" id="togglePassword">
                    <i class="bi bi-eye"></i>
                </button>
            </div>
            <a href="user-forgot-password.php" class="small-link">Forgot password?</a>
        </div>

        <button type="submit" name="login" class="btn btn-primary mt-3">Login</button>
        <span class="small-link mt-3">Don't have an account? <a href="signup.php">Register here</a></span>
    </form>
</div>

<!-- Toast notifications -->
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
    $bg=match($toast['type']){
        'success'=>'bg-success',
        'danger'=>'bg-danger',
        'warning'=>'bg-warning text-dark',
        'info'=>'bg-info text-dark',
        default=>'bg-secondary',
    };
    $message = addslashes($toast['message']);
    echo "document.addEventListener('DOMContentLoaded',()=>{const toastEl=document.getElementById('liveToast');const toastBody=document.getElementById('toast-message');toastEl.className='toast align-items-center border-0 $bg';toastBody.textContent='{$message}';new bootstrap.Toast(toastEl).show();});";
    unset($_SESSION['toast']);
}
?>

// Toggle password visibility
document.getElementById('togglePassword').addEventListener('click', function(){
    const pw = document.getElementById('password');
    const icon = this.querySelector('i');
    if(pw.type === 'password'){
        pw.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    }else{
        pw.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
});
</script>
</body>
</html>