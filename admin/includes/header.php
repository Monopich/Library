<!-- HEADER -->
<nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm" style="border-bottom: 4px solid #007bff;">
    <div class="container-fluid justify-content-center w-auto">
        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center me-4" href="dashboard.php">
            <img src="assets/img/logo.png" alt="Logo" height="60" class="me-2">
        </a>

        <!-- Toggler/collapse button -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar" aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Navbar links -->
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav align-items-lg-center">
                <li class="nav-item mx-1">
                    <a class="nav-link text-primary fw-medium px-3 py-2 rounded hover-bg" href="dashboard.php">Dashboard</a>
                </li>
                <li class="nav-item mx-1">
                    <a class="nav-link text-primary fw-medium px-3 py-2 rounded hover-bg" href="manage-categories.php">Categories</a>
                </li>
                <li class="nav-item mx-1">
                    <a class="nav-link text-primary fw-medium px-3 py-2 rounded hover-bg" href="manage-authors.php">Authors</a>
                </li>
                <li class="nav-item mx-1">
                    <a class="nav-link text-primary fw-medium px-3 py-2 rounded hover-bg" href="manage-books.php">Books</a>
                </li>
                <li class="nav-item mx-1">
                    <a class="nav-link text-primary fw-medium px-3 py-2 rounded hover-bg" href="manage-ebooks.php">EBooks</a>
                </li>
                <li class="nav-item mx-1">
                    <a class="nav-link text-primary fw-medium px-3 py-2 rounded hover-bg" href="manage-issued-books.php">Issued Books</a>
                </li>
                <li class="nav-item mx-1">
                    <a class="nav-link text-primary fw-medium px-3 py-2 rounded hover-bg" href="reg-students.php">Students</a>
                </li>
                <li class="nav-item mx-1">
                    <a class="nav-link text-primary fw-medium px-3 py-2 rounded hover-bg" href="change-password.php">Change Password</a>
                </li>
                <li class="nav-item mx-1">
                    <a class="btn btn-danger fw-bold px-4" href="logout.php">Log Out</a>
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

/* Adjust spacing for smaller screens */
@media (max-width: 992px) {
    .navbar-nav .nav-item {
        margin-bottom: 0.5rem;
    }
}
</style>
