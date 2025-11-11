<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
include_once('includes/config.php');

// Check if student logged in
if (empty($_SESSION['login'])) {
    header('location:index.php');
    exit;
}

$role = $_SESSION['role'] ?? 'student';
$uid  = $_SESSION['stdid'] ?? $_SESSION['loginid'] ?? 0;
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= $lang['dashboard'] ?? ucfirst($role) . ' Dashboard' ?> | Library Management System</title>

    <!-- BOOTSTRAP 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FONT AWESOME 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- CUSTOM STYLE -->
    <link href="assets/css/style.css" rel="stylesheet" />
    <!-- GOOGLE FONT -->
    <link href="https://fonts.googleapis.com/css?family=Open+Sans" rel="stylesheet">
</head>
<body>
    <!-- HEADER -->
    <?php include('includes/header.php'); ?>
    <!-- HEADER END -->

    <div class="content-wrapper py-5" style="padding-top: 100px; padding-bottom: 100px;">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <h4 class="fw-bold"><?= $lang['dashboard'] ?? ucfirst($role) . ' Dashboard' ?></h4>
                </div>
            </div>

            <div class="row g-3">
                <!-- Total Books -->
                <a href="listed-books.php" class="col-md-3 col-sm-6 text-decoration-none">
                    <div class="alert alert-success text-center py-4 rounded-3">
                        <i class="fa-solid fa-book fa-3x mb-2"></i>
                        <?php
                        $sql = "SELECT COUNT(id) AS total FROM tblbooks WHERE bookQty > 0";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $totalBooks = $query->fetch(PDO::FETCH_OBJ)->total;
                        ?>
                        <h3><?= htmlentities($totalBooks) ?></h3>
                        <?= $lang['books_list'] ?? 'Books List' ?>
                    </div>
                </a>

                <!-- EBooks -->
                <a href="ebooks.php" class="col-md-3 col-sm-6 text-decoration-none">
                    <div class="alert alert-info text-center py-4 rounded-3">
                        <i class="fa-solid fa-book fa-3x mb-2"></i>
                        <?php
                        $sql = "SELECT COUNT(id) AS total FROM tblbooks WHERE BookFile != ''";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $totalEbooks = $query->fetch(PDO::FETCH_OBJ)->total;
                        ?>
                        <h3><?= htmlentities($totalEbooks) ?></h3>
                        <?= $lang['ebooks_list'] ?? 'EBooks List' ?>
                    </div>
                </a>

                <!-- Books Not Returned -->
                <a href="issued-books.php" class="col-md-3 col-sm-6 text-decoration-none">
                    <div class="alert alert-warning text-center py-4 rounded-3">
                        <i class="fa-solid fa-recycle fa-3x mb-2"></i>
                        <?php
                        $sql = "SELECT COUNT(id) AS notReturned FROM tblissuedbookdetails 
                                WHERE StudentID = :uid AND (RetrunStatus = 0 OR RetrunStatus IS NULL OR RetrunStatus = '')";
                        $query = $dbh->prepare($sql);
                        $query->bindParam(':uid', $uid, PDO::PARAM_STR);
                        $query->execute();
                        $notReturned = $query->fetch(PDO::FETCH_OBJ)->notReturned;
                        ?>
                        <h3><?= htmlentities($notReturned) ?></h3>
                        <?= $lang['books_not_returned'] ?? 'Books Not Returned Yet' ?>
                    </div>
                </a>

                <!-- Total Issued Books -->
                <a href="issued-books.php" class="col-md-3 col-sm-6 text-decoration-none">
                    <div class="alert alert-danger text-center py-4 rounded-3">
                        <i class="fa-solid fa-book-open fa-3x mb-2"></i>
                        <?php
                        $sql = "SELECT COUNT(id) AS totalIssued FROM tblissuedbookdetails WHERE StudentID = :uid";
                        $query = $dbh->prepare($sql);
                        $query->bindParam(':uid', $uid, PDO::PARAM_STR);
                        $query->execute();
                        $totalIssued = $query->fetch(PDO::FETCH_OBJ)->totalIssued;
                        ?>
                        <h3><?= htmlentities($totalIssued) ?></h3>
                        <?= $lang['total_issued_books'] ?? 'Total Issued Books' ?>
                    </div>
                </a>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>

    <!-- BOOTSTRAP 5 JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- CUSTOM JS -->
    <script src="assets/js/custom.js"></script>
</body>
</html>
