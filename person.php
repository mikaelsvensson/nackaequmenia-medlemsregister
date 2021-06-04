<?php
require_once __DIR__ . '/util/db.php';
require_once __DIR__ . '/util/invoices.php';

$dbh = db_connect();

$person = db_get_person($dbh, $_GET['id']);

$guardian_1 = isset($person->guardian_1_person_id) ? db_get_person($dbh, $person->guardian_1_person_id) : null;

$guardian_2 = isset($person->guardian_2_person_id) ? db_get_person($dbh, $person->guardian_2_person_id) : null;

$children = db_get_children($dbh, $person->person_id);

switch (@$_POST['action']) {
    case 'invoices_create':
        invoices_create_for_person($dbh, $person->person_id);
        break;
}

$invoices = invoices_get_for_person($dbh, $person->person_id);

$dbh = null;

include './person.html.php';