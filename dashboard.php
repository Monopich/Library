<?php
session_start();
error_reporting(0);
include('includes/config.php');

if (strlen($_SESSION['login']) == 0) {
    header('location:index.php');
    exit;
}

$role = $_SESSION['role'] ?? 'student';
$uid  = $_SESSION['stdid'] ?? $_SESSION['loginid'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Library Management System | <?php echo ucfirst($role); ?> Dashboard</title>

    <!-- BOOTSTRAP 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FONT AWESOME 6 -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <!-- CUSTOM STYLE -->
    <link href="assets/css/style.css" rel="stylesheet" />
</head>
<body>
    <?php include('includes/header.php'); ?>

    <div class="content-wrapper py-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <h4 class="fw-bold"><?php echo ucfirst($role); ?> Dashboard</h4>
                </div>
            </div>

            <div class="row g-3">
                <!-- Total Books -->
                <a href="listed-books.php" class="col-md-4 col-sm-6 text-decoration-none">
                    <div class="alert alert-success text-center py-4 rounded-3">
                        <i class="fa-solid fa-book fa-3x mb-2"></i>
                        <?php
                        $sql = "SELECT COUNT(id) as total FROM tblbooks WHERE bookQty > 0";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $totalBooks = $query->fetch(PDO::FETCH_OBJ)->total;
                        ?>
                        <h3><?php echo htmlentities($totalBooks); ?></h3>
                        Books Listed
                    </div>
                </a>

                <!-- Total Books -->
                <a href="ebooks.php" class="col-md-4 col-sm-6 text-decoration-none">
                    <div class="alert alert-danger text-center py-4 rounded-3">
                        <i class="fa-solid fa-book fa-3x mb-2"></i>
                        <?php
                        $sql = "SELECT COUNT(id) as total FROM tblbooks WHERE BookFile != ''";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $totalBooks = $query->fetch(PDO::FETCH_OBJ)->total;
                        ?>
                        <h3><?php echo htmlentities($totalBooks); ?></h3>
                        Books Listed
                    </div>
                </a>

                <!-- Books Not Returned -->
                <a href="issued-books.php" class="col-md-4 col-sm-6 text-decoration-none">
                    <div class="alert alert-warning text-center py-4 rounded-3">
                        <i class="fa-solid fa-recycle fa-3x mb-2"></i>
                        <?php
                        $sql = "SELECT COUNT(id) as notReturned FROM tblissuedbookdetails 
                                WHERE StudentID=:uid AND (RetrunStatus=0 OR RetrunStatus IS NULL OR RetrunStatus='')";
                        $query = $dbh->prepare($sql);
                        $query->bindParam(':uid', $uid, PDO::PARAM_STR);
                        $query->execute();
                        $notReturned = $query->fetch(PDO::FETCH_OBJ)->notReturned;
                        ?>
                        <h3><?php echo htmlentities($notReturned); ?></h3>
                        Books Not Returned Yet
                    </div>
                </a>

                <!-- Total Issued Books -->
                <a href="issued-books.php" class="col-md-4 col-sm-6 text-decoration-none">
                    <div class="alert alert-info text-center py-4 rounded-3">
                        <i class="fa-solid fa-book-open fa-3x mb-2"></i>
                        <?php
                        $sql = "SELECT COUNT(id) as totalIssued FROM tblissuedbookdetails WHERE StudentID=:uid";
                        $query = $dbh->prepare($sql);
                        $query->bindParam(':uid', $uid, PDO::PARAM_STR);
                        $query->execute();
                        $totalIssued = $query->fetch(PDO::FETCH_OBJ)->totalIssued;
                        ?>
                        <h3><?php echo htmlentities($totalIssued); ?></h3>
                        Total Issued Books
                    </div>
                </a>
            </div>
        </div>
    </div>

    <?php include('includes/footer.php'); ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/custom.js"></script>
</body>
</html>
