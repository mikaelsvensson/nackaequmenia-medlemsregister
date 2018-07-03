<?php
//const URL_SPREADSHEET = "https://docs.google.com/spreadsheets/d/1Kr2X17DX5N9MQvNFXLjrfuVTAh6Q7HcxALxkfmBwZJ4/pub?gid=0&single=true&output=tsv";

//const name = 0;
//const troupe = 1;
//const ssn = 2;
//const age = 3;
//const grade = 4;
//const quit = 5;
//const email = 6;
//const phone_mobile = 7;
//const phone = 8;
//const address_street = 9;
//const address_postal = 10;
//const allergies = 11;
//const note = 12;
//const guardian_1_name = 13;
//const guardian_1_email = 14;
//const guardian_1_phone = 15;
//const guardian_1_address_street = 16;
//const guardian_1_address_postal = 17;
//const guardian_2_name = 18;
//const guardian_2_email = 19;
//const guardian_2_phone = 20;
//const guardian_2_address_street = 21;
//const guardian_2_address_postal = 22;
//const model_release_nacka_equmenia = 23;
//const model_release_scout_material = 24;
//const model_release_photographer = 25;
//const model_release_internet = 26;
//const model_release_name = 27;
//const id = 28;

//const name_given = 29; // This column does not exists in the Google Spreadsheet, it is added by this application.
//const guardian_1_name_given = 30; // This column does not exists in the Google Spreadsheet, it is added by this application.
//const guardian_2_name_given = 31; // This column does not exists in the Google Spreadsheet, it is added by this application.
//const name_surname_initial = 32; // This column does not exists in the Google Spreadsheet, it is added by this application.

const CACHE_EXPIRATION_SECONDS = 30;

const LINK_PATTERN = "https://docs.google.com/forms/d/e/1FAIpQLSffGvyxRxcWw90DXj5z7SNX1DZzekakMDGCmAy1FPj6WT1nLg/viewform?" .
    "entry.645072851={id}&" .
    "entry.772550625={name}&" .
    "entry.758769741={ssn}&" .
    "entry.1260081139={email}&" .
    "entry.1408734283={phone_mobile}&" .
    "entry.160546750={address_street}&" .
    "entry.560064279={address_postal}&" .
    "entry.466376640={allergies}&" .
    "entry.2076160580={guardian_1_name}&" .
    "entry.647263911={guardian_1_email}&" .
    "entry.878250299={guardian_1_phone}&" .
    "entry.232776408={guardian_1_phone_mobile}&" .
    "entry.1275442685={guardian_1_address_street}&" .
    "entry.1768413410={guardian_1_address_postal}&" .
    "entry.1205032770={guardian_2_name}&" .
    "entry.794787331={guardian_2_email}&" .
    "entry.2025099321={guardian_2_phone}&" .
    "entry.1090313649={guardian_2_phone_mobile}&" .
    "entry.1879882957={guardian_2_address_street}&" .
    "entry.261864377={guardian_2_address_postal}&" .
    "entry.1254440465={model_release_nacka_equmenia}&" .
    "entry.332365457={model_release_scout_material}&" .
    "entry.314863396={model_release_photographer}&" .
    "entry.1650081280={model_release_internet}&" .
    "entry.2083345974={model_release_name}";

/**
 * Substitutes {field} placeholders in the specified pattern with the corresponding values from the supplied contact entry.
 */
function formatString($pattern, $entry, $urlEncode = false)
{
    $props = array();
    foreach (get_object_vars($entry) as $name => $value) {
        $props['{' . $name . '}'] = $urlEncode ? urlencode($value) : $value;
    }
    return str_replace(array_keys($props), array_values($props), $pattern);
}

function tristate_form_value($value)
{
    if ($value == "J" || $value == "j") {
        return "Ja";
    } else if ($value == "N" || $value == "n") {
        return "Nej";
    } else {
        return "Vet inte";
    }
}


function get_selected_contacts_form_field() {
    return implode("", array_map(function ($value) {
        return sprintf('<input type="hidden" name="%s" value="%s">', 'select_contacts[]', $value);
    }, $_POST['select_contacts']));
}


/*
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

    $filter_only_active = function ($entry) {
        return strlen(trim($entry->t)) == 0;
    };
    $entries = array_filter($entries, $filter_only_active);

    usort($entries, "sort_by_name");

    return $entries;
}
*/
?>
