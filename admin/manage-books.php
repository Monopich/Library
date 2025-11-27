<?php
session_start();
error_reporting(0);
include('includes/config.php');

// Check if admin logged in
if (strlen($_SESSION['alogin']) == 0) {
    header('location:index.php');
    exit;
}

// Set current language texts
// config.php should include the proper file: en.php or kh.php
// $lang array is available from included file
$t = $lang; 

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
        $_SESSION['toast'] = ['msg' => $t['book_added'], 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['msg' => $t['book_add_failed'], 'type' => 'danger'];
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
            ISBNNumber=:isbn, BookPrice=:price, bookQty=:qty";

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
        $_SESSION['toast'] = ['msg' => $t['book_updated'], 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['msg' => $t['book_update_failed'], 'type' => 'danger'];
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
        $_SESSION['toast'] = ['msg' => $t['book_deleted'], 'type' => 'success'];
    } else {
        $_SESSION['toast'] = ['msg' => $t['book_delete_failed'], 'type' => 'danger'];
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
<title><?php echo $t['manage_books']; ?></title>

<!-- Bootstrap 5 CSS -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
<link href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<style>
body { background-color: #f8f9fa; }
.table thead th, .table tbody td { text-align: center; vertical-align: middle; }
.toast-container { z-index: 1100; }
.table thead th { background-color: #007bff; color: #fff; }
.table tbody td { vertical-align: middle; text-align: center; }
.table img { width: 100px; height: auto; object-fit: cover; }
.dataTables_wrapper .dataTables_filter, .dataTables_wrapper .dataTables_info {
    position: sticky; top: 0; background: white; z-index: 10; padding: 10px;
}
</style>
</head>
<body>

<?php include('includes/header.php'); ?>

<div class="container my-3" style="padding-bottom: 50px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold text-primary"><?php echo $t['manage_books']; ?></h2>
        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addBookModal">
            <i class="bi bi-plus-circle"></i> <?php echo $t['add_book']; ?>
        </button>
    </div>

    <div class="table-responsive shadow-sm rounded bg-white p-3">
        <table class="table table-striped table-hover table-bordered align-middle" id="booksTable">
            <thead>
                <tr>
                    <th>#</th>
                    <th><?php echo $t['cover']; ?></th>
                    <th><?php echo $t['book_name']; ?></th>
                    <th><?php echo $t['category']; ?></th>
                    <th><?php echo $t['author']; ?></th>
                    <th><?php echo $t['isbn']; ?></th>
                    <th><?php echo $t['price']; ?></th>
                    <th><?php echo $t['quantity']; ?></th>
                    <th><?php echo $t['delete_book']; ?></th>
                </tr>
            </thead>
            <tbody>
<?php
$sql = "SELECT b.id as bookid, b.BookName, b.ISBNNumber, b.BookPrice, b.bookImage, b.BookFile, b.bookQty,
        b.CatId, b.AuthorId, c.CategoryName, a.AuthorName 
        FROM tblbooks b 
        JOIN tblcategory c ON c.id=b.CatId 
        JOIN tblauthors a ON a.id=b.AuthorId
        WHERE  b.bookQty > 0 ";
$query = $dbh->prepare($sql);
$query->execute();
$results = $query->fetchAll(PDO::FETCH_OBJ);
$cnt = 1;
foreach ($results as $result):
?>
<tr>
    <td><?php echo htmlentities($cnt); ?></td>
    <td><img src="bookimg/<?php echo htmlentities($result->bookImage); ?>" alt="Book Cover"></td>
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
            <?php echo $t['edit_book']; ?> <i class="bi bi-pencil-square"></i>
        </button>
        <button class="btn btn-danger btn-sm deleteBookBtn" data-id="<?php echo $result->bookid; ?>" data-bs-toggle="modal" data-bs-target="#deleteBookModal"><?php echo $t['delete_book']; ?> <i class="bi bi-trash"></i></button>
    </td>
</tr>
<?php $cnt++; endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include('includes/footer.php'); ?>

<!-- Add Book Modal -->
<div class="modal fade" id="addBookModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" enctype="multipart/form-data">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title"><?php echo $t['add_book']; ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label"><?php echo $t['book_name']; ?></label>
                        <input type="text" name="bookName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo $t['category']; ?></label>
                        <select class="form-select select2" name="catId" required>
                            <option value=""><?php echo $t['category']; ?></option>
                            <?php
                            $catSql = "SELECT id, CategoryName FROM tblcategory ORDER BY CategoryName ASC";
                            $catQuery = $dbh->prepare($catSql);
                            $catQuery->execute();
                            $categories = $catQuery->fetchAll(PDO::FETCH_OBJ);
                            foreach ($categories as $cat) {
                                echo "<option value='{$cat->id}'>".htmlentities($cat->CategoryName)."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo $t['author']; ?></label>
                        <select class="form-select select2" name="authorId" required>
                            <option value=""><?php echo $t['author']; ?></option>
                            <?php
                            $authorSql = "SELECT id, AuthorName FROM tblauthors ORDER BY AuthorName ASC";
                            $authorQuery = $dbh->prepare($authorSql);
                            $authorQuery->execute();
                            $authors = $authorQuery->fetchAll(PDO::FETCH_OBJ);
                            foreach ($authors as $author) {
                                echo "<option value='{$author->id}'>".htmlentities($author->AuthorName)."</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo $t['isbn']; ?></label>
                        <input type="text" name="isbn" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo $t['price']; ?></label>
                        <input type="text" name="price" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo $t['quantity']; ?></label>
                        <input type="text" name="qty" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo $t['book_image']; ?></label>
                        <input type="file" name="bookImage" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="addBook" class="btn btn-success"><?php echo $t['add']; ?></button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $t['cancel']; ?></button>
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
                    <h5 class="modal-title"><?php echo $t['edit_book']; ?></h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="bookId" id="editBookId">
                    <div class="mb-3">
                        <label class="form-label"><?php echo $t['book_name']; ?></label>
                        <input type="text" name="bookName" id="editBookName" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo $t['category']; ?></label>
                        <select name="catId" id="editCatId" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo $t['author']; ?></label>
                        <select name="authorId" id="editAuthorId" class="form-select" required></select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo $t['isbn']; ?></label>
                        <input type="text" name="isbn" id="editISBN" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo $t['price']; ?></label>
                        <input type="text" name="price" id="editPrice" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo $t['quantity']; ?></label>
                        <input type="text" name="qty" id="editQty" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label"><?php echo $t['book_image']; ?> (Optional)</label>
                        <input type="file" name="bookImage" class="form-control">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" name="updateBook" class="btn btn-primary"><?php echo $t['update']; ?></button>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $t['cancel']; ?></button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Modal -->
<div class="modal fade" id="deleteBookModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><?php echo $t['delete_book']; ?></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <?php echo $t['confirm_delete']; ?>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal"><?php echo $t['cancel']; ?></button>
                <a href="#" class="btn btn-danger" id="confirmDeleteBtn"><?php echo $t['delete_book']; ?></a>
            </div>
        </div>
    </div>
</div>

<!-- Toast -->
<div class=" position-fixed top-0 end-0 p-3 toast-container">
<?php if($toast): ?>
<div class="toast align-items-center text-white bg-<?php echo $toast['type']; ?> border-0 show" role="alert" aria-live="assertive" aria-atomic="true">
    <div class="d-flex">
        <div class="toast-body"><?php echo htmlentities($toast['msg']); ?></div>
        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
    </div>
</div>
<?php endif; ?>
</div>

<!-- JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    $('#booksTable').DataTable({
    responsive: true,
    pageLength: 10,
    lengthMenu: [5, 10, 25, 50, 100],
    language: {
        search: "<?= $t['search'] ?? 'Search' ?>",
        lengthMenu: "<?= $t['show'] ?? 'Show' ?> _MENU_ entries",
        info: "<?= $t['showing'] ?? 'Showing' ?> _START_ to _END_ of _TOTAL_ entries",
        paginate: {
            previous: "<?= $t['previous'] ?? 'Previous' ?>",
            next: "<?= $t['next'] ?? 'Next' ?>"
        }
    }
});
    $('.select2').select2({ dropdownParent: $('.modal') });

    // Edit book
    $('.editBookBtn').on('click', function(){
        const id = $(this).data('id');
        $('#editBookId').val(id);
        $('#editBookName').val($(this).data('name'));
        $('#editISBN').val($(this).data('isbn'));
        $('#editPrice').val($(this).data('price'));
        $('#editQty').val($(this).data('qty'));

        // Load categories and authors into edit modal
        const catId = $(this).data('cat-id');
        const authorId = $(this).data('author-id');

        $.get('ajax-load-category.php', function(data){ $('#editCatId').html(data).val(catId); });
        $.get('ajax-load-author.php', function(data){ $('#editAuthorId').html(data).val(authorId); });
    });

    // Delete book
    $('.deleteBookBtn').on('click', function(){
        const id = $(this).data('id');
        $('#confirmDeleteBtn').attr('href', 'manage-books.php?del=' + id);
    });
});
</script>

</body>
</html>
