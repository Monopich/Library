<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('includes/config.php'); // ✅ Load language and other configs

$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!-- STUDENT HEADER -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm sticky-top" style="border-bottom: 4px solid #007bff; z-index: 1030;">
    <div class="container-fluid justify-content-between w-auto">

        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center me-4" href="dashboard.php">
            <img src="assets/img/logo.png" alt="Logo" height="60" class="me-2">
        </a>

        <!-- Toggler for mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#studentNavbar" aria-controls="studentNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar Links -->
        <div class="collapse navbar-collapse justify-content-center" id="studentNavbar">
            <ul class="navbar-nav align-items-lg-center mb-2 mb-lg-0">

                <li class="nav-item mx-1">
                    <a class="nav-link text-primary fw-medium px-3 py-2 rounded hover-bg <?= $currentPage == 'dashboard.php' ? 'active' : ''; ?>" href="dashboard.php">
                        <?= $lang['dashboard'] ?? 'Dashboard' ?>
                    </a>
                </li>

                <li class="nav-item mx-1">
                    <a class="nav-link text-primary fw-medium px-3 py-2 rounded hover-bg <?= $currentPage == 'issued-books.php' ? 'active' : ''; ?>" href="issued-books.php">
                        <?= $lang['issued_books'] ?? 'Issued Books' ?>
                    </a>
                </li>

                <li class="nav-item mx-1">
                    <a class="nav-link text-primary fw-medium px-3 py-2 rounded hover-bg <?= $currentPage == 'ebooks.php' ? 'active' : ''; ?>" href="ebooks.php">
                        <?= $lang['ebooks'] ?? 'E-Books' ?>
                    </a>
                </li>

                <li class="nav-item mx-1">
                    <a class="nav-link text-primary fw-medium px-3 py-2 rounded hover-bg <?= $currentPage == 'my-profile.php' ? 'active' : ''; ?>" href="my-profile.php">
                        <?= $lang['my_profile'] ?? 'My Profile' ?>
                    </a>
                </li>

                <li class="nav-item mx-1">
                    <a class="nav-link text-primary fw-medium px-3 py-2 rounded hover-bg <?= $currentPage == 'change-password.php' ? 'active' : ''; ?>" href="change-password.php">
                        <?= $lang['change_password'] ?? 'Change Password' ?>
                    </a>
                </li>

                <li class="nav-item mx-1">
                    <a class="nav-link text-primary fw-medium px-3 py-2 rounded hover-bg <?= $currentPage == 'help.php' ? 'active' : ''; ?>" href="help.php">
                        <?= $lang['help'] ?? 'Help' ?>
                    </a>
                </li>

                <!-- 🌐 Language Switcher -->
                <div class="d-flex align-items-center mx-1 mr-3">
                    <form method="get" class="m-0">
                        <select name="lang" onchange="this.form.submit()" class="form-select form-select-sm border-primary text-primary">
                            <option value="en" <?= $_SESSION['lang'] === 'en' ? 'selected' : '' ?>>🇬🇧 English</option>
                            <option value="kh" <?= $_SESSION['lang'] === 'kh' ? 'selected' : '' ?>>🇰🇭 ភាសាខ្មែរ</option>
                        </select>
                    </form>
                </div>

                <!-- 🔴 Logout -->
                <li class="nav-item mx-1">
                    <a class="btn btn-danger fw-bold px-4 btn-sm" href="logout.php">
                        <?= $lang['logout'] ?? 'Log Out' ?>
                    </a>
                </li>

            </ul>
        </div>
    </div>
</nav>

<style>
/* Hover effect for navbar links */
.hover-bg:hover {
    background-color: rgba(0, 123, 255, 0.1);
    transition: 0.3s;
}

/* Active link style */
.nav-link.active {
    background-color: rgba(0, 123, 255, 0.2);
    border-radius: 0.25rem;
}

/* Smaller text size for navbar links */
.navbar-nav .nav-link {
    font-size: 0.85rem;
}

/* Adjust spacing for smaller screens */
@media (max-width: 992px) {
    .navbar-nav .nav-item {
        margin-bottom: 0.5rem;
    }
}
</style>
