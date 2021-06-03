<?php
require_once 'core.php';

require_once 'core-google-data.php';
require_once 'ContactsDataSource.php';

include 'core-page-start.php';

$contactsDataSoure = new \contacts\MergedContactDataSource();
$entries = $contactsDataSoure->getEntries();
?>

<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
    <h1>Saknar vi data för några scouter?</h1>
    <p>Här ser du om vi saknar viktig information för någon scout:</p>
    <ul>
        <?php
        foreach ($entries as $entry) {
            $test_results = array(
                "har id" => !empty($entry->id),
                "har meddelat hur bilder får användas" => $entry->model_release_nacka_equmenia == "Ja" || $entry->model_release_nacka_equmenia == "Nej",
                "har två kontaktpersoner" => !empty($entry->guardian_1_name) && !empty($entry->guardian_2_name),
                "har två e-postadresser" => !empty($entry->guardian_1_email) && !empty($entry->guardian_2_email),
                "har två telefonnummer" => (int)!empty($entry->guardian_1_phone) + (int)!empty($entry->guardian_1_phone_mobile) + (int)!empty($entry->guardian_2_phone) + (int)!empty($entry->guardian_2_phone_mobile) >= 2
            );

            ?>
            <?php $failed_tests = array_filter($test_results, function ($test_result) {
                return !$test_result;
            });
            if (count($failed_tests) > 0) { ?>
                <li>
                    <?= $entry->name ?>
                    <small>
                        <?= join(array_map(function ($failed_test_name) {
                            return "<br>Kontrollera data eftersom personen inte $failed_test_name";
                        }, array_keys($failed_tests))) ?>
                    </small>
                </li>
            <?php } ?>
        <?php } ?>
    </ul>

    <p>Observera att data bara testas för scouter vi har <em>någon</em> information om. Scouter <em>inte har någon
            information alls om</em> syns inte i listan.</p>
</div>
<?php
include 'core-page-end.php';
?>
