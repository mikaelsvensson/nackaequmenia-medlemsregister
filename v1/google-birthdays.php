<?php
require_once 'core.php';

require_once 'core-google-data.php';
require_once 'ContactsDataSource.php';

include 'core-page-start.php';

$contactsDataSoure = new \contacts\MergedContactDataSource();
$entries = $contactsDataSoure->getEntries();
?>
    <div class="row">
        <div class="col-xs-12">
            <h1>Födelsedagar</h1>

            <p>Vem fyller år snart? Vem har precis fyllt år?</p>
        </div>
    </div>
<?php

foreach ($entries as $key => $entry) {
    $matches = array();
    preg_match('/(\d{0,2}\d{2})(\d{2})(\d{2})(-\d{4})?/', $entry->ssn, $matches);

    $today = date_create('today');
    $birthday = date_create(join('-', array($matches[1], $matches[2], $matches[3])) . ' 00:00:00');

    $dates = [
        date_create(join('-', array(date('Y') - 1, $matches[2], $matches[3])) . ' 00:00:00'),
        date_create(join('-', array(date('Y'), $matches[2], $matches[3])) . ' 00:00:00'),
        date_create(join('-', array(date('Y') + 1, $matches[2], $matches[3])) . ' 00:00:00')
    ];
    $days = array_map(function ($date) use ($today) {
        $diff = date_diff($today, $date);
        return $diff->days;
    }, $dates);

    $closest_date = array_reduce($dates, function ($carry, $date) use ($today) {
        return $carry == null || date_diff($today, $date)->days < date_diff($today, $carry)->days ? $date : $carry;
    }, null);

    $age = date_diff($birthday, $closest_date);
    $diff = date_diff($today, $closest_date);

    if ($diff->days < 14) {
        switch ($diff->days) {
            case 0:
                $relative_today = sprintf('fyller %d år idag', $age->y);
                break;
            case 1:
                $relative_today = $diff->invert === 1
                    ?
                    sprintf('fyllde %d år igår', $age->y)
                    :
                    sprintf('fyller %d år imorgon', $age->y);
                break;
            default:
                $relative_today = $diff->invert === 1
                    ?
                    sprintf('fyllde %d år för %d dagar sedan', $age->y, $diff->days)
                    :
                    sprintf('fyller %d år om %d dagar', $age->y, $diff->days);
                break;
        };

        $formatted_birthdate = date_format($closest_date, 'Y-m-d')
        ?>
        <div class="row">
            <div class="col-xs-12">
                <p>
                    <strong><?= $entry->name ?></strong>
                    <?= $relative_today ?> (<?= $formatted_birthdate ?>)</p>
            </div>
        </div>
        <?php
    }
}
?>