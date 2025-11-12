<?php
session_start();
error_reporting(0);
include('includes/config.php');

if (strlen($_SESSION['login']) == 0) {
    header('location:index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $lang['ebooks_title'] ?> | Online Library Management System</title>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/font-awesome.css" rel="stylesheet" />
<link href="assets/css/style.css" rel="stylesheet" />

<style>
body {
    background-color: #f5f7fa;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Search Bar */
.search-wrapper {
    position: relative;
    width: 100%;
    max-width: 420px;
}
.search-wrapper input {
    width: 100%;
    padding: 0.7rem 1rem 0.7rem 2.8rem;
    border-radius: 30px;
    border: 1px solid #d0d7de;
    background-color: #fff;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    transition: all 0.2s ease-in-out;
}
.search-wrapper input:focus {
    outline: none;
    border-color: #007bff;
    box-shadow: 0 0 0 3px rgba(0,123,255,0.1);
}
.search-wrapper i {
    position: absolute;
    top: 50%;
    left: 12px;
    transform: translateY(-50%);
    color: #6c757d;
    font-size: 1.1rem;
}

/* Card Styling */
.card-ebook {
    border: none;
    border-radius: 15px;
    box-shadow: 0 3px 8px rgba(0,0,0,0.08);
    transition: all 0.3s ease;
    overflow: hidden;
    background-color: #fff;
}
.card-ebook:hover {
    transform: translateY(-6px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.12);
}
.card-img-container {
    background: #f8f9fa;
    height: 220px;
    display: flex;
    justify-content: center;
    align-items: center;
}
.card-img-top {
    max-height: 200px;
    object-fit: contain;
}
.card-body {
    padding: 1.25rem;
}
.card-title {
    font-size: 1.1rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 0.6rem;
}
.card-text {
    font-size: 0.95rem;
    color: #555;
}

/* Buttons */
.btn-view, .btn-download {
    font-size: 0.9rem;
    border-radius: 25px;
    padding: 0.4rem 1rem;
    transition: all 0.2s ease-in-out;
}
.btn-view:hover {
    background-color: #0056b3;
}
.btn-download:hover {
    background-color: #198754cc;
}

/* Empty state */
.alert-info {
    border-radius: 10px;
    background: #e9f5ff;
    color: #055160;
    border: 1px solid #b6e0fe;
}
</style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="content-wrapper py-4">
    <div class="container">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4">
            <h2 class="fw-bold text-primary mb-0">
                <i class="fa fa-book me-2"></i><?= $lang['available_ebooks'] ?>
            </h2>
            <div class="search-wrapper">
                <i class="fa fa-search"></i>
                <input type="text" id="searchInput" class="form-control" placeholder="<?= $lang['search_placeholder'] ?>">
            </div>
        </div>

        <div class="row g-4" id="ebookList">
            <?php 
            // Fetch only eBooks (those with BookPdf file)
            $sql = "SELECT tblbooks.id, tblbooks.BookName, tblbooks.BookFile, tblbooks.bookImage, 
                           tblauthors.AuthorName, tblcategory.CategoryName
                    FROM tblbooks
                    LEFT JOIN tblauthors ON tblauthors.id = tblbooks.AuthorId
                    LEFT JOIN tblcategory ON tblcategory.id = tblbooks.CatId
                    WHERE tblbooks.BookFile IS NOT NULL AND tblbooks.BookFile != ''";

            $query = $dbh->prepare($sql);
            $query->execute();
            $results = $query->fetchAll(PDO::FETCH_OBJ);

            if ($query->rowCount() > 0) {
                foreach ($results as $result) {
            ?>
            <div class="col-lg-3 col-md-4 col-sm-6 ebook-item">
                <div class="card card-ebook h-100">
                    <div class="card-img-container">
                        <img src="admin/bookimg/<?php echo htmlentities($result->bookImage ?: 'default-book.png'); ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlentities($result->BookName); ?>">
                    </div>
                    <div class="card-body text-center d-flex flex-column">
                        <h5 class="card-title text-truncate" title="<?php echo htmlentities($result->BookName); ?>">
                            <?php echo htmlentities($result->BookName); ?>
                        </h5>
                        <p class="card-text mb-1"><strong><?= $lang['author'] ?>:</strong> <?php echo htmlentities($result->AuthorName); ?></p>
                        <p class="card-text mb-3"><strong><?= $lang['category'] ?>:</strong> <?php echo htmlentities($result->CategoryName); ?></p>
                        <div class="mt-auto d-flex justify-content-center gap-2">
                            <a href="admin/assets/pdf/<?php echo htmlentities($result->BookFile); ?>" target="_blank" class="btn btn-primary btn-view">
                                <i class="fa fa-eye me-1"></i><?= $lang['view'] ?>
                            </a>
                            <a href="admin/assets/pdf/<?php echo htmlentities($result->BookFile); ?>" download class="btn btn-success btn-download">
                                <i class="fa fa-download me-1"></i><?= $lang['download'] ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php 
                }
            } else { 
            ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="fa fa-info-circle me-2"></i><?= $lang['no_ebooks_found'] ?>
                    </div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// 🔍 Live Search Filter
$(document).ready(function() {
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#ebookList .ebook-item').filter(function() {
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
});
</script>

</body>
</html>
