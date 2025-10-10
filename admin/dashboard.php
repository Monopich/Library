<?php
session_start();
error_reporting(0);
include('includes/config.php');
if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
} else { ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Library Management System | Admin Dashboard</title>

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
    <!-- MENU SECTION START-->
    <?php include('includes/header.php'); ?>
    <!-- MENU SECTION END-->

    <div class="content-wrapper py-4">
        <div class="container">
            <div class="row mb-4">
                <div class="col-12">
                    <h4 class="fw-bold">ADMIN DASHBOARD</h4>
                </div>
            </div>

            <div class="row g-3">
                <!-- Books Listed -->
                <a href="manage-books.php" class="col-md-3 col-sm-6 text-decoration-none">
                    <div class="alert alert-success text-center py-4 rounded-3">
                        <i class="fa-solid fa-book fa-3x mb-2"></i>
                        <?php
                        $sql = "SELECT id FROM tblbooks WHERE bookQty > 0 ";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $listdbooks = $query->rowCount();
                        ?>
                        <h3><?php echo htmlentities($listdbooks); ?></h3>
                        Books Listed
                    </div>
                </a>

                <!-- Books Listed -->
                <a href="manage-ebooks.php" class="col-md-3 col-sm-6 text-decoration-none">
                    <div class="alert alert-info text-center py-4 rounded-3">
                        <i class="fa-solid fa-book fa-3x mb-2"></i>
                        <?php
                        $sql = "SELECT id FROM tblbooks WHERE BookFile != ''";
                        $query = $dbh->prepare($sql);
                        $query->execute();
                        $listdbooks = $query->rowCount();
                        ?>
                        <h3><?php echo htmlentities($listdbooks); ?></h3>
                        EBooks Listed
                    </div>
                </a>

                <!-- Books Not Returned -->
                <a href="manage-issued-books.php" class="col-md-3 col-sm-6 text-decoration-none">
                    <div class="alert alert-warning text-center py-4 rounded-3">
                        <i class="fa-solid fa-recycle fa-3x mb-2"></i>
                        <?php
                        $sql2 = "SELECT id FROM tblissuedbookdetails WHERE (RetrunStatus='' OR RetrunStatus IS NULL)";
                        $query2 = $dbh->prepare($sql2);
                        $query2->execute();
                        $returnedbooks = $query2->rowCount();
                        ?>
                        <h3><?php echo htmlentities($returnedbooks); ?></h3>
                        Books Not Returned Yet
                    </div>
                </a>

                <!-- Registered Users -->
                <a href="reg-students.php" class="col-md-3 col-sm-6 text-decoration-none">
                    <div class="alert alert-danger text-center py-4 rounded-3">
                        <i class="fa-solid fa-users fa-3x mb-2"></i>
                        <?php
                        $sql3 = "SELECT id FROM tblstudents";
                        $query3 = $dbh->prepare($sql3);
                        $query3->execute();
                        $regstds = $query3->rowCount();
                        ?>
                        <h3><?php echo htmlentities($regstds); ?></h3>
                        Registered Users
                    </div>
                </a>

                <!-- Authors Listed -->
                <a href="manage-authors.php" class="col-md-3 col-sm-6 text-decoration-none">
                    <div class="alert alert-success text-center py-4 rounded-3">
                        <i class="fa-solid fa-user fa-3x mb-2"></i>
                        <?php
                        $sq4 = "SELECT id FROM tblauthors";
                        $query4 = $dbh->prepare($sq4);
                        $query4->execute();
                        $listdathrs = $query4->rowCount();
                        ?>
                        <h3><?php echo htmlentities($listdathrs); ?></h3>
                        Authors Listed
                    </div>
                </a>

                <!-- Categories Listed -->
                <a href="manage-categories.php" class="col-md-3 col-sm-6 text-decoration-none">
                    <div class="alert alert-info text-center py-4 rounded-3">
                        <i class="fa-solid fa-file-archive fa-3x mb-2"></i>
                        <?php
                        $sql5 = "SELECT id FROM tblcategory";
                        $query5 = $dbh->prepare($sql5);
                        $query5->execute();
                        $listdcats = $query5->rowCount();
                        ?>
                        <h3><?php echo htmlentities($listdcats); ?></h3>
                        Listed Categories
                    </div>
                </a>
            </div>
        </div>
    </div>
    <!-- CONTENT-WRAPPER SECTION END-->

    <?php include('includes/footer.php'); ?>

    <!-- BOOTSTRAP 5 JS BUNDLE -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- CUSTOM JS -->
    <script src="assets/js/custom.js"></script>
</body>
</html>
<?php } ?>
