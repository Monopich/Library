<?php
session_start();
include('includes/config.php');
$debugFile = __DIR__ . '/sso_debug.log';

// ✅ 1️⃣ Already logged in? Redirect to dashboard immediately
if (!empty($_SESSION['login']) || !empty($_SESSION['alogin'])) {
    header('Location: dashboard.php');
    exit;
}

// ✅ 2️⃣ RTC token exists? Try auto-login using SSO
if (!empty($_COOKIE['access_token'])) {
    $token = $_COOKIE['access_token'];
    error_log("🪪 Library SSO Token: " . $token);
    file_put_contents($debugFile, "[".date('Y-m-d H:i:s')."] 🪪 Token: ".$_COOKIE['access_token']."\n", FILE_APPEND);

    try {
        $apiUrl = "https://api.rtc-bb.camai.kh/api/auth/get_detail_user";

        $ch = curl_init($apiUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                "Authorization: Bearer $token",
                "Accept: application/json"
            ],
            CURLOPT_SSL_VERIFYPEER => false, // ⚠️ Disable only for testing; enable on production!
            CURLOPT_TIMEOUT => 10
        ]);
        $response = curl_exec($ch);

        if ($response === false) {
            throw new Exception('cURL Error: ' . curl_error($ch));
        }

        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            throw new Exception("Invalid or expired token (HTTP $httpCode)");
        }

        $userData = json_decode($response, true);
        if (empty($userData['user']['email'])) {
            throw new Exception("User data missing from RTC response");
        }

        // ✅ Valid RTC token → create Library session
        $user = $userData['user'];
        $detail = $user['user_detail'] ?? [];

        $email = $user['email'];
        $studentId = $detail['id_card'] ?? $user['id'];
        $fullName = $user['name'] ?? 'RTC User';
        $phone = $detail['phone_number'] ?? '';

        // ✅ Check or create local record
        $stmt = $dbh->prepare("SELECT StudentId FROM tblstudents WHERE EmailId = :email");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $update = $dbh->prepare("
                UPDATE tblstudents 
                SET FullName = :name, MobileNumber = :mobile, Status = 1 
                WHERE EmailId = :email
            ");
            $update->bindParam(':name', $fullName);
            $update->bindParam(':mobile', $phone);
            $update->bindParam(':email', $email);
            $update->execute();
        } else {
            $insert = $dbh->prepare("
                INSERT INTO tblstudents (StudentId, FullName, EmailId, MobileNumber, Password, Status)
                VALUES (:id, :name, :email, :mobile, '', 1)
            ");
            $insert->bindParam(':id', $studentId);
            $insert->bindParam(':name', $fullName);
            $insert->bindParam(':email', $email);
            $insert->bindParam(':mobile', $phone);
            $insert->execute();
        }

        // ✅ Auto-login user in Library
        $_SESSION['login'] = $email;
        $_SESSION['stdid'] = $studentId;
        $_SESSION['username'] = $fullName;
        $_SESSION['token_external'] = $token;

        header('Location: dashboard.php');
        exit;

    } catch (Exception $e) {
        error_log("RTC auto-login error: " . $e->getMessage());
        $_SESSION['toast'] = [
            'type' => 'danger',
            'message' => 'Auto-login failed: ' . $e->getMessage()
        ];
    }
}

// ✅ 3️⃣ Manual login (local or RTC)
if (isset($_POST['login'])) {
    $email = trim($_POST['emailid']);
    $password = $_POST['password'];

    // --- Local Admin Login ---
    $sqlAdmin = "SELECT UserName, Password FROM admin WHERE UserName = :email";
    $queryAdmin = $dbh->prepare($sqlAdmin);
    $queryAdmin->bindParam(':email', $email, PDO::PARAM_STR);
    $queryAdmin->execute();
    $admin = $queryAdmin->fetch(PDO::FETCH_OBJ);

    if ($admin && $admin->Password === md5($password)) {
        $_SESSION['alogin'] = $email;
        header("Location: admin/dashboard.php");
        exit;
    }

    // --- RTC API Login ---
    $loginUrl = "https://api.rtc-bb.camai.kh/api/auth/login";
    $payload = json_encode(['email' => $email, 'password' => $password]);

    $ch = curl_init($loginUrl);
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
        $_SESSION['toast'] = ['type' => 'danger', 'message' => "Network Error: $error"];
        header("Location: index.php");
        exit;
    }

    $result = json_decode($response, true);
    $token = $result['token'] ?? $result['access_token'] ?? ($result['data']['token'] ?? null);

    if ($status === 200 && $token) {
        // Fetch RTC user details
        $ch = curl_init("https://api.rtc-bb.camai.kh/api/auth/get_detail_user");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $token,
                'Accept: application/json'
            ],
            CURLOPT_SSL_VERIFYPEER => false,
        ]);
        $userResponse = curl_exec($ch);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($code === 200) {
            $userData = json_decode($userResponse, true);
            $user = $userData['user'] ?? null;
            if ($user) {
                $detail = $user['user_detail'] ?? [];
                $studentId = $detail['id_card'] ?? $user['id'];
                $fullName = $user['name'] ?? 'RTC User';
                $phone = $detail['phone_number'] ?? '';
                $emailId = $user['email'] ?? $email;

                // ✅ Upsert student
                $check = $dbh->prepare("SELECT StudentId FROM tblstudents WHERE EmailId = :email");
                $check->bindParam(':email', $emailId);
                $check->execute();

                if ($check->rowCount() > 0) {
                    $update = $dbh->prepare("
                        UPDATE tblstudents 
                        SET FullName = :name, MobileNumber = :mobile, Status = 1 
                        WHERE EmailId = :email
                    ");
                    $update->bindParam(':name', $fullName);
                    $update->bindParam(':mobile', $phone);
                    $update->bindParam(':email', $emailId);
                    $update->execute();
                } else {
                    $insert = $dbh->prepare("
                        INSERT INTO tblstudents (StudentId, FullName, EmailId, MobileNumber, Password, Status)
                        VALUES (:id, :name, :email, :mobile, :password, 1)
                    ");
                    $insert->bindParam(':id', $studentId);
                    $insert->bindParam(':name', $fullName);
                    $insert->bindParam(':email', $emailId);
                    $insert->bindParam(':mobile', $phone);
                    $insert->bindValue(':password', md5($password));
                    $insert->execute();
                }

                // ✅ Set session & cookie
                $_SESSION['stdid'] = $studentId;
                $_SESSION['login'] = $emailId;
                $_SESSION['token_external'] = $token;
                $_SESSION['username'] = $fullName;

                setcookie('access_token', $token, time() + 3600, '/', '.camai.kh', true, true);

                header("Location: dashboard.php");
                exit;
            }
        }
        $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Failed to fetch user info from RTC.'];
    } else {
        $_SESSION['toast'] = ['type' => 'danger', 'message' => 'Invalid credentials or API error.'];
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
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
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
        <span class="small-link mt-3">Don't have an account? <a href="signup.php">Register here</a></span>
    </form>
</div>

<!-- Toast -->
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
    echo "document.addEventListener('DOMContentLoaded',()=>{const toastEl=document.getElementById('liveToast');const toastBody=document.getElementById('toast-message');toastEl.className='toast align-items-center border-0 $bg';toastBody.textContent='".addslashes($toast['message'])."';new bootstrap.Toast(toastEl).show();});";
    unset($_SESSION['toast']);
}
?>

document.getElementById('togglePassword').addEventListener('click', function(){
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
</script>
</body>
</html>
