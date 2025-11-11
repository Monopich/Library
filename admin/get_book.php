<?php
require_once("includes/config.php");

if (!empty($_POST["bookid"])) {
    $bookid = $_POST["bookid"];

    $sql = "SELECT 
                b.id AS bookid,
                b.BookName,
                b.ISBNNumber,
                b.bookImage,
                b.bookQty,
                a.AuthorName,
                c.CategoryName,
                COUNT(ibd.id) AS issuedBooks,
                SUM(CASE WHEN ibd.RetrunStatus=1 THEN 1 ELSE 0 END) AS returnedBooks
            FROM tblbooks b
            LEFT JOIN tblissuedbookdetails ibd ON ibd.BookId = b.id
            LEFT JOIN tblauthors a ON a.id = b.AuthorId
            LEFT JOIN tblcategory c ON c.id = b.CatId
            WHERE b.ISBNNumber LIKE :isbn OR b.BookName LIKE :bookname
            GROUP BY b.id
            ORDER BY b.BookName ASC";

    $query = $dbh->prepare($sql);
    $query->bindValue(':isbn', "%$bookid%", PDO::PARAM_STR);
    $query->bindValue(':bookname', "%$bookid%", PDO::PARAM_STR); 
    $query->execute();

    $results = $query->fetchAll(PDO::FETCH_OBJ);

    if ($query->rowCount() > 0) {
    echo '<div class="row">';
    foreach ($results as $result) {
        $bqty = $result->bookQty;
        $issued = $result->issuedBooks - $result->returnedBooks; // corrected property name
        $availableQty = $bqty - $issued;
        ?>
        <div class="col-md-6 mb-3">
            <div class="card p-2 h-100">
                <img src="bookimg/<?php echo htmlentities($result->bookImage); ?>" class="card-img- w-auto" style="height:200px; object-fit:cover;">
                <div class="card-body">
                    <h6 class="card-title"><?php echo htmlentities($result->BookName); ?></h6>
                    <p class="mb-1"><?php echo $lang['author']; ?>: <?php echo htmlentities($result->AuthorName); ?></p>
                    <p class="mb-1"><?php echo $lang['book_qty']; ?>: <?php echo htmlentities($bqty); ?></p>
                    <p class="mb-1"><?php echo $lang['available_qty']; ?>: <?php echo htmlentities($availableQty); ?></p>
                </div>
                <div class="card-footer bg-transparent border-0 mt-auto">
                <?php if ($availableQty > 0): ?>
                    <button type="button" class="btn btn-success btn-sm selectBookBtn w-100"
                            data-bookid="<?php echo htmlentities($result->bookid); ?>">
                        <?= $lang['select_book'] ?>
                    </button>
                <?php else: ?>
                    <span class="text-danger"><?= $lang['book_not_available'] ?></span>
                <?php endif; ?>
            </div>
            </div>
        </div>
        <?php
    }
    echo '</div>';
} else {
    echo '<p class="text-danger">'.$lang['not_found'].'</p>';
}
}
?>
