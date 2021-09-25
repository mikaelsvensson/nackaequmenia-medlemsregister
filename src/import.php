<?php
require_once __DIR__.'/util/db.php';
require_once __DIR__.'/util/import.php';

$dbh = db_connect();

//try {
//} catch (PDOException $e) {
//    print "Error!: " . $e->getMessage() . "<br/>";
//    die();
//}

switch (@$_POST['action']) {
    case 'import_repet':
        import_repet($dbh, $_FILES['file']['tmp_name']);
        break;
    case 'import_csv':
        import_csv($dbh, $_FILES['file']['tmp_name']);
        break;
}

$dbh = null;

include './import.html.php';