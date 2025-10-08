<?php
require_once("includes/config.php");

if (!empty($_POST["bookid"])) {
    $bookid = $_POST["bookid"];

    $sql = "SELECT 
                MAX(tblbooks.BookName) AS BookName,
                MAX(tblcategory.CategoryName) AS CategoryName,
                MAX(tblauthors.AuthorName) AS AuthorName,
                MAX(tblbooks.ISBNNumber) AS ISBNNumber,
                MAX(tblbooks.BookPrice) AS BookPrice,
                MAX(tblbooks.id) AS bookid,
                MAX(tblbooks.bookImage) AS bookImage,
                MAX(tblbooks.isIssued) AS isIssued,
                MAX(tblbooks.bookQty) AS bookQty,
                COUNT(tblissuedbookdetails.id) AS issuedBooks,
                COUNT(tblissuedbookdetails.RetrunStatus) AS returnedbook
            FROM tblbooks
            LEFT JOIN tblissuedbookdetails 
                ON tblissuedbookdetails.BookId = tblbooks.id
            LEFT JOIN tblauthors 
                ON tblauthors.id = tblbooks.AuthorId
            LEFT JOIN tblcategory 
                ON tblcategory.id = tblbooks.CatId
            WHERE tblbooks.ISBNNumber = :bookid 
               OR tblbooks.BookName LIKE :bookname
            GROUP BY tblbooks.id";

    $query = $dbh->prepare($sql);
    $query->bindValue(':bookid', $bookid, PDO::PARAM_STR);
    $query->bindValue(':bookname', "%$bookid%", PDO::PARAM_STR); // for LIKE search
    $query->execute();
    $results = $query->fetchAll(PDO::FETCH_OBJ);

    if ($query->rowCount() > 0) {
        echo '<table border="1"><tr>';
        foreach ($results as $result) {
            $bqty = $result->bookQty;
            $aqty = $bqty - ($result->issuedBooks - $result->returnedbook);
            ?>
            <th style="padding-left:5%; width: 10%;">
                <img src="bookimg/<?php echo htmlentities($result->bookImage); ?>" width="120"><br />
                <?php echo htmlentities($result->BookName); ?><br />
                <?php echo htmlentities($result->AuthorName); ?><br />
                Book Quantity: <?php echo htmlentities($bqty); ?><br />
                Available Book Quantity: <?php echo htmlentities($aqty); ?><br />
                <?php if ($aqty == 0): ?>
                    <p style="color:red;">Book not available for issue.</p>
                <?php else: ?>
                    <input type="radio" name="bookid" value="<?php echo htmlentities($result->bookid); ?>" required>
                    <input type="hidden" name="aqty" value="<?php echo htmlentities($aqty); ?>" required>
                <?php endif; ?>
            </th>
            <?php
            echo "<script>$('#submit').prop('disabled',false);</script>";
        }
        echo '</tr></table>';
    } else {
        echo '<p>Record not found. Please try again.</p>';
        echo "<script>$('#submit').prop('disabled',true);</script>";
    }
}
?>
