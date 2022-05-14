<?php
require_once __DIR__ . '/util/db.php';
require_once __DIR__ . '/util/import.php';

$dbh = db_connect();

$person_id = isset($_GET['id']) ? $_GET['id'] : false;

$person = isset($person_id) ? db_get_person($dbh, $person_id) : false;

$is_update_mode = !empty($person);

switch (@$_POST['action']) {
    case 'person_upsert':

        $first_name = htmlspecialchars($_POST['first_name']);
        $sur_name = htmlspecialchars($_POST['sur_name']);
        $phone = filter_var($_POST['phone'], FILTER_SANITIZE_NUMBER_INT);
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $pno = filter_var($_POST['pno'], FILTER_SANITIZE_NUMBER_INT);

        if (!$is_update_mode && !empty($pno)) {
            $person = import_find_person_by_pno($dbh, $pno);
        }

        if (empty($person)) {
            $person_id = db_create_person($dbh, $pno);
            error_log("✨ Creating new person ${person_id}");
        } else {
            $person_id = $person->person_id;
            error_log("😐 Using person ${person_id}");
        }
        if (empty($person_id)) {
            die('No person id');
        }

        db_set_person_props($dbh, $person_id, [
            'first_name' => $first_name,
            'sur_name' => $sur_name,
            'phone' => $phone,
            'email' => $email,
        ]);

        break;
}

$person = isset($person_id) ? db_get_person($dbh, $person_id) : false;

$page_title = $is_update_mode ? 'Redigera person' : 'Lägg till person';

$dbh = null;

include './person-upsert.html.php';