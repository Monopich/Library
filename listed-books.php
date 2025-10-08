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
<title>Issued Books | Online Library Management System</title>

<!-- Bootstrap 5 -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="assets/css/font-awesome.css" rel="stylesheet" />
<link href="assets/css/style.css" rel="stylesheet" />

<style>
body {
    background-color: #f8f9fa;
}

/* Card Styling */
.card-book {
    border-radius: 15px;
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transition: transform 0.2s, box-shadow 0.2s;
    display: flex;
    flex-direction: column;
    height: 100%;
}
.card-book:hover {
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
.badge-available {
    font-size: 0.85rem;
    padding: 5px 10px;
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
            <h4 class="text-primary fw-bold mb-0">Issued Books</h4>
            <input type="text" id="searchInput" class="form-control" placeholder="🔍 Search by title, author, category, or ISBN">
        </div>

        <div class="row g-4" id="bookList">
            <?php 
            $sql = "SELECT tblbooks.BookName, tblcategory.CategoryName, tblauthors.AuthorName,
                    tblbooks.ISBNNumber, tblbooks.BookPrice, tblbooks.id as bookid, tblbooks.bookImage,
                    tblbooks.isIssued, tblbooks.bookQty,
                    COUNT(tblissuedbookdetails.id) AS issuedBooks,
                    COUNT(tblissuedbookdetails.RetrunStatus) AS returnedbook
                    FROM tblbooks
                    LEFT JOIN tblissuedbookdetails ON tblissuedbookdetails.BookId = tblbooks.id
                    LEFT JOIN tblauthors ON tblauthors.id = tblbooks.AuthorId
                    LEFT JOIN tblcategory ON tblcategory.id = tblbooks.CatId
                    GROUP BY tblbooks.id";

            $query = $dbh->prepare($sql);
            $query->execute();
            $results = $query->fetchAll(PDO::FETCH_OBJ);

            if ($query->rowCount() > 0) {
                foreach ($results as $result) {
                    $availableQty = $result->bookQty;
                    if ($result->issuedBooks > 0) {
                        $availableQty -= ($result->issuedBooks - $result->returnedbook);
                    }
            ?>
            <div class="col-lg-4 col-md-6 col-sm-12 book-item">
                <div class="card card-book">
                    <div class="card-img-container">
                        <img src="admin/bookimg/<?php echo htmlentities($result->bookImage); ?>" 
                             class="card-img-top" 
                             alt="<?php echo htmlentities($result->BookName); ?>">
                    </div>
                    <div class="card-body text-center">
                        <h5 class="card-title text-truncate" title="<?php echo htmlentities($result->BookName); ?>">
                            <?php echo htmlentities($result->BookName); ?>
                        </h5>
                        <p class="mb-1"><strong>Author:</strong> <?php echo htmlentities($result->AuthorName); ?></p>
                        <p class="mb-1"><strong>Category:</strong> <?php echo htmlentities($result->CategoryName); ?></p>
                        <p class="mb-1"><strong>ISBN:</strong> <?php echo htmlentities($result->ISBNNumber); ?></p>
                        <p class="mb-1"><strong>Total:</strong> <?php echo htmlentities($result->bookQty); ?></p>
                        <p class="mb-0">
                            <strong>Available:</strong>
                            <?php if($availableQty > 0) { ?>
                                <span class="badge bg-success badge-available"><?php echo $availableQty; ?></span>
                            <?php } else { ?>
                                <span class="badge bg-danger badge-available">0</span>
                            <?php } ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php 
                }
            } else { 
            ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">No books found.</div>
                </div>
            <?php } ?>
        </div>
    </div>
</div>

<?php include('includes/footer.php'); ?>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
// 🔍 Live Search Filter (includes ISBN)
$(document).ready(function() {
    $('#searchInput').on('keyup', function() {
        var value = $(this).val().toLowerCase();
        $('#bookList .book-item').filter(function() {
            // Search text includes Book Name, Author, Category, and ISBN
            $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
        });
    });
});
</script>

</body>
</html>
