<?php
error_reporting(E_WARNING);
//error_reporting(E_ALL);
ini_set("display_errors", 1);
require_once 'core-google-data.php';
require_once 'ContactsDataSource.php';

$contactsDataSource = new \contacts\MergedContactDataSource();
$entries = $contactsDataSource->getEntries();
//$entries = google_data_get();
$scoutId = $_SERVER['QUERY_STRING'];

$matches = array_filter($entries, function ($entry) use ($scoutId) {
    return $entry->id == $scoutId;
});

if (count($matches) == 1) {

    $entry = $matches[array_keys($matches)[0]];

//    $entry->model_release_nacka_equmenia = tristate_form_value($entry->model_release_nacka_equmenia);
//    $entry->model_release_scout_material = tristate_form_value($entry->model_release_scout_material);
//    $entry->model_release_photographer = tristate_form_value($entry->model_release_photographer);
//    $entry->model_release_internet = tristate_form_value($entry->model_release_internet);
//    $entry->model_release_name = tristate_form_value($entry->model_release_name);

    $editLinkUrl = str_replace("=,", "=", formatString(LINK_PATTERN, $entry, true));

    header("Location: $editLinkUrl", true, 303);
} else {
    echo "Kunde hitta scout $scoutId";
}
