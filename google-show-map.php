<?php
//require_once 'lib/phpmailer/PHPMailerAutoload.php';

require_once 'core-google-data.php';

$config = parse_ini_file('config.ini', true);

?>
<div class="row">
    <div class="col-xs-12">
        <h1>Visa på karta</h1>

        <p>Visa scouternas adresser på en karta.</p>
    </div>
</div>
<?php

$link = 'google-map-view.html?' . implode('|', array_map(function ($key) use ($entries) {
        $entry = $entries[$key];
        return rawurlencode($entry->name . ', ' . $entry->address_street . ';' . $entry->address_street . ',' . $entry->address_postal . ",SWEDEN;;");
    }, $_POST['select_contacts']));

print get_selected_contacts_form_field();

foreach ($_POST['select_contacts'] as $key) {
    $entry = $entries[$key];
    ?>
    <div class="row">
        <div class="col-xs-12">
            <p><strong><?= $entry->name ?></strong>, <?= $entry->address_street ?>, <?= $entry->address_postal ?></p>
        </div>
    </div>
<?php } ?>
<div>
    <button type="submit" name="action" value="" class="btn btn-default btn-sm">Tillbaka</button>
    <a class="btn btn-primary" href="<?= $link ?>" target="_blank">Karta i ny flik</a>
</div>