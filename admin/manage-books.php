<?php
session_start();
error_reporting(0);
include('includes/config.php');

if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit;
}

// Add Book
if (isset($_POST['addBook'])) {
    $bookName = $_POST['bookName'];
    $catId = $_POST['catId'];
    $authorId = $_POST['authorId'];
    $isbn = $_POST['isbn'];
    $price = $_POST['price'];
    $qty = $_POST['qty'];
    $bookImage = $_FILES['bookImage']['name'];

    move_uploaded_file($_FILES['bookImage']['tmp_name'], "bookimg/".$bookImage);

    $sql = "INSERT INTO tblbooks(BookName, CatId, AuthorId, ISBNNumber, BookPrice, bookImage, bookQty) 
            VALUES(:bookName, :catId, :authorId, :isbn, :price, :bookImage, :qty)";
    $query = $dbh->prepare($sql);
    $query->bindParam(':bookName', $bookName, PDO::PARAM_STR);
    $query->bindParam(':catId', $catId, PDO::PARAM_INT);
    $query->bindParam(':authorId', $authorId, PDO::PARAM_INT);
    $query->bindParam(':isbn', $isbn, PDO::PARAM_STR);
    $query->bindParam(':price', $price, PDO::PARAM_STR);
    $query->bindParam(':bookImage', $bookImage, PDO::PARAM_STR);
    $query->bindParam(':qty', $qty, PDO::PARAM_STR);

    if($query->execute()){
        $_SESSION['toast'] = ['msg' => 'Book added successfully!', 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['msg' => 'Failed to add book!', 'type' => 'danger'];
    }
    header('location:manage-books.php');
    exit;
}

// Update Book
if (isset($_POST['updateBook'])) {
    $bookId = $_POST['bookId'];
    $bookName = $_POST['bookName'];
    $catId = $_POST['catId'];
    $authorId = $_POST['authorId'];
    $isbn = $_POST['isbn'];
    $price = $_POST['price'];
    $qty = $_POST['qty'];

    $sql = "UPDATE tblbooks SET BookName=:bookName, CatId=:catId, AuthorId=:authorId, 
            ISBNNumber=:isbn, BookPrice=:price, bookqty=:qty";

    // Update image if uploaded
    if(!empty($_FILES['bookImage']['name'])){
        $bookImage = $_FILES['bookImage']['name'];
        move_uploaded_file($_FILES['bookImage']['tmp_name'], "bookimg/".$bookImage);
        $sql .= ", bookImage=:bookImage";
    }

    $sql .= " WHERE id=:id";

    $query = $dbh->prepare($sql);
    $query->bindParam(':bookName', $bookName, PDO::PARAM_STR);
    $query->bindParam(':catId', $catId, PDO::PARAM_INT);
    $query->bindParam(':authorId', $authorId, PDO::PARAM_INT);
    $query->bindParam(':isbn', $isbn, PDO::PARAM_STR);
    $query->bindParam(':price', $price, PDO::PARAM_STR);
    $query->bindParam(':qty', $qty, PDO::PARAM_STR);
    if(!empty($_FILES['bookImage']['name'])){
        $query->bindParam(':bookImage', $bookImage, PDO::PARAM_STR);
    }
    $query->bindParam(':id', $bookId, PDO::PARAM_INT);

    if($query->execute()){
        $_SESSION['toast'] = ['msg' => 'Book updated successfully!', 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['msg' => 'Failed to update book!', 'type' => 'danger'];
    }
    header('location:manage-books.php');
    exit;
}

// Delete Book
if (isset($_GET['del'])) {
    $id = $_GET['del'];
    $sql = "DELETE FROM tblbooks WHERE id=:id";
    $query = $dbh->prepare($sql);
    $query->bindParam(':id', $id, PDO::PARAM_INT);
    if($query->execute()){
        $_SESSION['toast'] = ['msg' => 'Book deleted successfully!', 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['msg' => 'Failed to delete book!', 'type' => 'danger'];
    }
    header('location:manage-books.php');
    exit;
}

$toast = $_SESSION['toast'] ?? null;
unset($_SESSION['toast']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Library Management System | Manage Books</title>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
body { background-color: #f8f9fa; }
.table thead th, .table tbody td { text-align: center; vertical-align: middle; }
.toast-container { z-index: 1100; }
.table thead th {
    background-color: #007bff;
    color: #fff;
    text-align: center;
}
.table tbody td {
    vertical-align: middle;
    text-align: center;
}
</style>
</head>
<body>

<?php include('includes/header.php'); ?>


<div class="container my-3">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold text-primary">Manage Books</h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBookModal">
            <i class="bi bi-plus-circle"></i> Add Book
        </button>
    </div>

    <div class="table-responsive shadow-sm rounded bg-white p-3">
        <table class="table table-striped table-hover table-bordered align-middle" id="booksTable">
            <thead class="table-primary text-white">
                <tr>
                    <th>#</th>
                    <th>Cover</th>
                    <th>Book Name</th>
                    <th>Category</th>
                    <th>Author</th>
                    <th>ISBN</th>
                    <th>Price</th>
                    <th>Quantity</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
<?php
$sql = "SELECT b.id as bookid, b.BookName, b.ISBNNumber, b.BookPrice, b.bookImage, b.bookQty,
        b.CatId, b.AuthorId, c.CategoryName, a.AuthorName 
        FROM tblbooks b 
        JOIN tblcategory c ON c.id=b.CatId 
        JOIN tblauthors a ON a.id=b.AuthorId";
$query = $dbh->prepare($sql);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_OBJ);
$cnt = 1;
foreach ($results as $result):
?>
<tr>
    <td><?php echo htmlentities($cnt); ?></td>
    <td><img src="bookimg/<?php echo htmlentities($result->bookImage); ?>" width="50" height="70"></td>
    <td class="text-start" style="width: 300px;"><?php echo htmlentities($result->BookName); ?></td>
    <td><?php echo htmlentities($result->CategoryName); ?></td>
    <td><?php echo htmlentities($result->AuthorName); ?></td>
    <td><?php echo htmlentities($result->ISBNNumber); ?></td>
    <td><?php echo htmlentities($result->BookPrice); ?></td>
    <td><?php echo htmlentities($result->bookQty); ?></td>
    <td>
        <button 
            class="btn btn-primary btn-sm editBookBtn"
            data-id="<?php echo $result->bookid; ?>"
            data-name="<?php echo htmlentities($result->BookName); ?>"
            data-cat-id="<?php echo $result->CatId; ?>"
            data-author-id="<?php echo $result->AuthorId; ?>"
            data-isbn="<?php echo htmlentities($result->ISBNNumber); ?>"
            data-price="<?php echo htmlentities($result->BookPrice); ?>"
            data-qty="<?php echo htmlentities($result->bookQty); ?>"
            data-bs-toggle="modal" data-bs-target="#editBookModal">
            Edit 
            <i class="bi bi-pencil-square"></i>
        </button>
        <button class="btn btn-danger btn-sm deleteBookBtn" data-id="<?php echo $result->bookid; ?>" data-bs-toggle="modal" data-bs-target="#deleteBookModal">Delete <i class="bi bi-trash"></i></button>
    </td>
</tr>
<?php $cnt++; endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('includes/footer.php'); ?>

<?php
// Fetch categories alphabetically
$catSql = "SELECT id, CategoryName FROM tblcategory ORDER BY CategoryName ASC";
$catQuery = $dbh->prepare($catSql);
$catQuery->execute();
$categories = $catQuery->fetchAll(PDO::FETCH_OBJ);

// Fetch authors alphabetically
$authorSql = "SELECT id, AuthorName FROM tblauthors ORDER BY AuthorName ASC";
$authorQuery = $dbh->prepare($authorSql);
$authorQuery->execute();
$authors = $authorQuery->fetchAll(PDO::FETCH_OBJ);
?>

<!-- Add Book Modal -->
<div class="modal fade" id="addBookModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">Add Book</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Book Name</label>
                        <input type="text" name="bookName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label for="catId" class="form-label">Category</label>
                        <select class="form-select select2" name="catId" id="catId" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat->id; ?>"><?php echo htmlentities($cat->CategoryName); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="authorId" class="form-label">Author</label>
                        <select class="form-select select2" name="authorId" id="authorId" required>
                            <option value="">Select Author</option>
                            <?php foreach ($authors as $auth): ?>
                                <option value="<?php echo $auth->id; ?>"><?php echo htmlentities($auth->AuthorName); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">ISBN</label>
                        <input type="text" name="isbn" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="text" name="price" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="text" name="qty" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Book Image</label>
                        <input type="file" name="bookImage" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="addBook" class="btn btn-success">Add</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Book Modal -->
<div class="modal fade" id="editBookModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data" id="editBookForm">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Edit Book</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="bookId" id="editBookId">
                    <div class="mb-3">
                        <label class="form-label">Book Name</label>
                        <input type="text" name="bookName" id="editBookName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="catId" id="editCatId" class="form-select" required>
                            <option value="">Select Category</option>
                            <?php
                            // Fetch categories alphabetically
                            $catSql = "SELECT id, CategoryName FROM tblcategory ORDER BY CategoryName ASC";
                            $catQuery = $dbh->prepare($catSql);
                            $catQuery->execute();
                            $cats = $catQuery->fetchAll(PDO::FETCH_OBJ);

                            foreach($cats as $cat) {
                                // If this is the current book's category, mark as selected
                                $selected = (isset($result->CatId) && $result->CatId == $cat->id) ? "selected" : "";
                                echo "<option value='{$cat->id}' $selected>{$cat->CategoryName}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Author</label>
                        <select name="authorId" id="editAuthorId" class="form-select" required>
                            <option value="">Select Author</option>
                            <?php
                            // Fetch authors alphabetically
                            $authorSql = "SELECT id, AuthorName FROM tblauthors ORDER BY AuthorName ASC";
                            $authorQuery = $dbh->prepare($authorSql);
                            $authorQuery->execute();
                            $authors = $authorQuery->fetchAll(PDO::FETCH_OBJ);

                            foreach($authors as $author) {
                                // If this is the current book's author, mark as selected
                                $selected = (isset($result->AuthorId) && $result->AuthorId == $author->id) ? "selected" : "";
                                echo "<option value='{$author->id}' $selected>{$author->AuthorName}</option>";
                            }
                            ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ISBN</label>
                        <input type="text" name="isbn" id="editISBN" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Price</label>
                        <input type="text" name="price" id="editPrice" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity</label>
                        <input type="text" name="qty" id="editQty" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Book Image (Optional)</label>
                        <input type="file" name="bookImage" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="updateBook" class="btn btn-primary">Update</button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteBookModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Confirm Delete</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this book?
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <a href="#" class="btn btn-danger" id="confirmDeleteBtn">Delete</a>
            </div>
        </div>
    </div>
</div>


<!-- Toast -->
<div class="position-fixed top-0 end-0 p-3 toast-container">
<?php if($toast): ?>
    <div id="liveToast" class="toast align-items-center text-bg-<?php echo $toast['type']; ?> border-0" role="alert">
        <div class="d-flex">
            <div class="toast-body"><?php echo htmlentities($toast['msg']); ?></div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
        </div>
    </div>
<?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

<script>
$(document).ready(function() {
    $('#booksTable').DataTable({
        "columnDefs": [{ "orderable": false, "targets": 8 }]
    });

    // Toast
    <?php if($toast): ?>
    var toastEl = document.getElementById('liveToast');
    var toast = new bootstrap.Toast(toastEl, { delay: 4000 });
    toast.show();
    <?php endif; ?>

    // Edit Book delegated handler
    $(document).on('click', '.editBookBtn', function() {
        $('#editBookId').val($(this).data('id'));
        $('#editBookName').val($(this).data('name'));
        $('#editCatId').val($(this).data('cat-id')).trigger('change');
        $('#editAuthorId').val($(this).data('author-id')).trigger('change');
        $('#editISBN').val($(this).data('isbn'));
        $('#editPrice').val($(this).data('price'));
        $('#editQty').val($(this).data('qty'));
    });

    // Delete Book delegated handler
    $(document).on('click', '.deleteBookBtn', function() {
        const bookId = $(this).data('id');
        $('#confirmDeleteBtn').attr('href', 'manage-books.php?del=' + bookId);
    });
});
</script>

</body>
</html>
