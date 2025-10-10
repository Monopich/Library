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
<title>E-Books | Online Library Management System</title>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/font-awesome.css" rel="stylesheet" />
<link href="assets/css/style.css" rel="stylesheet" />

<style>
body {
    background-color: #f8f9fa;
}

/* Card Styling */
.card-ebook {
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    flex-direction: column;
    height: 100%;
}
.card-ebook:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.15);
}
.card-img-container {
    width: 100%;
    height: 220px;
    display: flex;
    justify-content: center;
    align-items: center;
    background-color: #fff;
    border-radius: 15px 15px 0 0;
    overflow: hidden;
}
.card-img-top {
    width: auto;
    height: 100%;
    object-fit: contain;
}
.card-body {
    padding: 1rem 1.25rem;
}
.card-title {
    font-size: 1.1rem;
    font-weight: 600;
    min-height: 45px;
}

/* Button style */
.btn-view, .btn-download {
    font-size: 0.9rem;
}

/* Search box */
#searchInput {
    max-width: 400px;
    border-radius: 30px;
    padding: 0.6rem 1.5rem;
    border: 1px solid #007bff;
}
</style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="content-wrapper">
    <div class="container py-3">
        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
            <h4 class="text-primary fw-bold mb-0">Available E-Books</h4>
            <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search by title, author, or category">
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
            <div class="col-lg-4 col-md-6 col-sm-12 ebook-item">
                <div class="card card-ebook">
                    <div class="card-img-container">
                        <img src="admin/bookimg/<?php echo htmlentities($result->bookImage ?: 'default-book.png'); ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlentities($result->BookName); ?>">
                    </div>
                    <div class="card-body text-center">
                        <h5 class="card-title text-truncate" title="<?php echo htmlentities($result->BookName); ?>">
                            <?php echo htmlentities($result->BookName); ?>
                        </h5>
                        <p class="mb-1"><strong>Author:</strong> <?php echo htmlentities($result->AuthorName); ?></p>
                        <p class="mb-2"><strong>Category:</strong> <?php echo htmlentities($result->CategoryName); ?></p>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="admin/assets/pdf/<?php echo htmlentities($result->BookFile); ?>" target="_blank" class="btn btn-primary btn-view px-3">
                                <i class="fa fa-eye me-1"></i> View
                            </a>
                            <a href="admin/assets/pdf/<?php echo htmlentities($result->BookFile); ?>" download class="btn btn-success btn-download px-3">
                                <i class="fa fa-download me-1"></i> Download
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
                    <div class="alert alert-info text-center">No e-books found.</div>
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
