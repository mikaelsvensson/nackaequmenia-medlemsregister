<?php
require_once 'core.php';

require_once 'core-google-data.php';
require_once 'ContactsDataSource.php';

include 'core-page-start.php';

$contactsDataSoure = new \contacts\MergedContactDataSource();
$entries = $contactsDataSoure->getEntries();
//$entries = google_data_get();
?>

    <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
        <h1>Vem får synas på nätet?</h1>
        <p>Följande scouter får synas på internet "i information om, och reklam för, scoutkåren Nacka Equmenia":</p>
        <ul>
            <?php foreach ($entries as $key => $entry) { ?>
                <?php if ($entry->model_release_nacka_equmenia == "Ja" && $entry->model_release_internet == "Ja") { ?>
                    <li>
                        <?= $entry->name ?>
                    </li>
                <?php } ?>
            <?php } ?>
        </ul>

        <p>Utgå från att scout som inte finns på listan inte heller får synas på internet.</p>

        <p>Skriv inte ut namn på scout.</p>
    </div>
<?php
include 'core-page-end.php';
?>
