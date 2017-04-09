<?php
const URL_SPREADSHEET = "https://docs.google.com/spreadsheets/d/1Kr2X17DX5N9MQvNFXLjrfuVTAh6Q7HcxALxkfmBwZJ4/pub?gid=0&single=true&output=tsv";

const name = 0;
const troupe = 1;
const ssn = 2;
const age = 3;
const grade = 4;
const quit = 5;
const email = 6;
const phone_mobile = 7;
const phone = 8;
const address_street = 9;
const address_postal = 10;
const allergies = 11;
const note = 12;
const guardian_1_name = 13;
const guardian_1_email = 14;
const guardian_1_phone = 15;
const guardian_1_address_street = 16;
const guardian_1_address_postal = 17;
const guardian_2_name = 18;
const guardian_2_email = 19;
const guardian_2_phone = 20;
const guardian_2_address_street = 21;
const guardian_2_address_postal = 22;
const model_release_nacka_equmenia = 23;
const model_release_scout_material = 24;
const model_release_photographer = 25;
const model_release_internet = 26;
const model_release_name = 27;

const name_given = 28; // This column does not exists in the Google Spreadsheet, it is added by this application.
const guardian_1_name_given = 29; // This column does not exists in the Google Spreadsheet, it is added by this application.
const guardian_2_name_given = 30; // This column does not exists in the Google Spreadsheet, it is added by this application.

const CACHE_EXPIRATION_SECONDS = 300;

function sort_by_name($a, $b)
{
    if ($a[name] == $b[name]) {
        return 0;
    }
    return ($a[name] < $b[name]) ? -1 : 1;
}

function filter_only_active($entry)
{
    return strlen(trim($entry[quit])) == 0;
}

function google_data_get() {
    $entries = array();
    $cacheFile = sys_get_temp_dir() . '/google-data-cache.txt';
//    print "Cache file $cacheFile.";
    if (!file_exists($cacheFile) || filemtime($cacheFile) < time() - CACHE_EXPIRATION_SECONDS) {
//        print "Cache missing or old.";
        file_put_contents($cacheFile, fopen(URL_SPREADSHEET, 'r'));
//        print "Fetched data.";
    }

    if (($handle = fopen($cacheFile, "r")) !== FALSE) {
        while (($data = fgetcsv($handle, 1000, "\t")) !== FALSE) {
            $entries[] = array_map(function ($v) { return mb_convert_encoding($v, mb_internal_encoding(), 'UTF-8'); }, $data);
        }
        fclose($handle);
    }

    $entries = array_filter($entries, "filter_only_active");

    usort($entries, "sort_by_name");

    return $entries;
}
?>
