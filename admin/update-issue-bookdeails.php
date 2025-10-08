<?php
session_start();
include('includes/config.php');

if (strlen($_SESSION['alogin']) == 0) {
    exit('Unauthorized');
}

$isAjax = isset($_GET['ajax']) && $_GET['ajax'] == 1;

if (isset($_POST['return'])) {
    $rid = intval($_POST['rid']);
    $fine = $_POST['fine'];
    $rstatus = 1;
    $bookid = $_POST['bookid'];

    // Update return and book status
    $sql = "UPDATE tblissuedbookdetails SET fine=:fine, RetrunStatus=:rstatus WHERE id=:rid;
            UPDATE tblbooks SET isIssued=0 WHERE id=:bookid";
    $query = $dbh->prepare($sql);
    $query->bindParam(':rid', $rid, PDO::PARAM_STR);
    $query->bindParam(':fine', $fine, PDO::PARAM_STR);
    $query->bindParam(':rstatus', $rstatus, PDO::PARAM_STR);
    $query->bindParam(':bookid', $bookid, PDO::PARAM_STR);
    $query->execute();

    $_SESSION['msg'] = "Book Returned successfully";

    if ($isAjax) {
        echo '<div class="alert alert-success">Book Returned successfully</div>';
        exit;
    } else {
        header('location:manage-issued-books.php');
        exit;
    }
}

$rid = intval($_GET['rid']);
$sql = "SELECT tblstudents.StudentId, tblstudents.FullName, tblstudents.EmailId, tblstudents.MobileNumber, 
        tblbooks.BookName, tblbooks.ISBNNumber, tblbooks.bookImage,
        tblissuedbookdetails.IssuesDate, tblissuedbookdetails.ReturnDate, tblissuedbookdetails.id as rid,
        tblissuedbookdetails.fine, tblissuedbookdetails.RetrunStatus, tblbooks.id as bid
        FROM tblissuedbookdetails
        JOIN tblstudents ON tblstudents.StudentId = tblissuedbookdetails.StudentId
        JOIN tblbooks ON tblbooks.id = tblissuedbookdetails.BookId
        WHERE tblissuedbookdetails.id=:rid";

$query = $dbh->prepare($sql);
$query->bindParam(':rid', $rid, PDO::PARAM_STR);
$query->execute();
$result = $query->fetch(PDO::FETCH_OBJ);

if (!$result) {
    echo '<p class="text-danger">Record not found.</p>';
    exit;
}
?>

<form method="post">
<input type="hidden" name="rid" value="<?= $result->rid ?>">
<input type="hidden" name="bookid" value="<?= $result->bid ?>">

<h4>Student Details</h4>
<hr />
<div class="row">
    <div class="col-md-6">
        <label>Student ID:</label> <?= htmlentities($result->StudentId) ?>
    </div>
    <div class="col-md-6">
        <label>Student Name:</label> <?= htmlentities($result->FullName) ?>
    </div>
    <div class="col-md-6">
        <label>Email:</label> <?= htmlentities($result->EmailId) ?>
    </div>
    <div class="col-md-6">
        <label>Mobile:</label> <?= htmlentities($result->MobileNumber) ?>
    </div>
</div>

<h4>Book Details</h4>
<hr />
<div class="row">
    <div class="col-md-6">
        <label>Book Image:</label><br>
        <img src="bookimg/<?= htmlentities($result->bookImage) ?>" width="120">
    </div>
    <div class="col-md-6">
        <label>Book Name:</label> <?= htmlentities($result->BookName) ?>
    </div>
    <div class="col-md-6">
        <label>ISBN:</label> <?= htmlentities($result->ISBNNumber) ?>
    </div>
    <div class="col-md-6">
        <label>Issued Date:</label> <?= htmlentities($result->IssuesDate) ?>
    </div>
    <div class="col-md-6">
        <label>Return Date:</label> <?= $result->ReturnDate == "" ? "Not Return Yet" : htmlentities($result->ReturnDate) ?>
    </div>
</div>

<div class="row mt-3">
    <div class="col-md-12">
        <label>Fine (USD):</label>
        <?php if ($result->fine == "") { ?>
            <input class="form-control" type="text" name="fine" required>
        <?php } else { ?>
            <?= htmlentities($result->fine) ?>
        <?php } ?>
    </div>
</div>

<?php if ($result->RetrunStatus == 0) { ?>
<div class="mt-3">
    <button type="submit" name="return" class="btn btn-info">Return Book</button>
</div>
<?php } ?>
</form>
