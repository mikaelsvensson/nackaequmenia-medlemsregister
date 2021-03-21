<?php
require_once __DIR__.'/util/db.php';

$dbh = db_connect();

//try {
//} catch (PDOException $e) {
//    print "Error!: " . $e->getMessage() . "<br/>";
//    die();
//}

$people = db_get_people($dbh);

$dbh = null;

include './index.html.php';