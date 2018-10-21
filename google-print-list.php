<?php
$config = parse_ini_file('config.ini', true);

print get_selected_contacts_form_field();

if (empty($_POST['select_contacts'])) {
    $_POST['select_contacts'] = array_keys($entries);
}
?>
<h1>Kontaktlista</h1>
<?php
foreach ($_POST['select_contacts'] as $key) {
    $entry = $entries[$key];
    ?>
    <div class="row" style="page-break-inside: avoid; border-top: 0.05pt solid black; padding: 1em 0">
        <div class="col-xs-4">
            <p><strong><?= $entry->name ?></strong></p>
            <p>
                <?php printf('<a href="tel:%s">%s</a>', $entry->phone, $entry->phone) ?>
                <?php printf('<br/><a href="mailto:%s">%s</a>', $entry->email, $entry->email) ?>
            </p>
    <?php if (!empty($entry->note)) { ?>
                <p>Anteckningar: <?= $entry->note ?></p>
    <?php } ?>
    <?php if (!empty($entry->allergies)) { ?>
                <p>Allergier: <?= $entry->allergies ?></p>
    <?php } ?>
        </div>

    <?php if (!empty($entry->guardian_1_name)) { ?>
            <div class="col-xs-4">
                <p></p>
                <p>
                    <?= $entry->guardian_1_name ?>
                    <?php printf('<br/><a href="tel:%s">%s</a>', $entry->guardian_1_phone, $entry->guardian_1_phone) ?>
                    <?php printf('<br/><a href="mailto:%s">%s</a>', $entry->guardian_1_email, $entry->guardian_1_email) ?>
                </p>
            </div>
    <?php } ?>
    <?php if (!empty($entry->guardian_2_name)) { ?>
            <div class="col-xs-4">
                <p></p>
                <p>
                    <?= $entry->guardian_2_name ?>
                    <?php printf('<br/><a href="tel:%s">%s</a>', $entry->guardian_2_phone, $entry->guardian_2_phone) ?>
                    <?php printf('<br/><a href="mailto:%s">%s</a>', $entry->guardian_2_email, $entry->guardian_2_email) ?>
                </p>
            </div>
    <?php } ?>
    </div>
<?php } ?>
<div class="hidden-print">
    <button type="submit" name="action" value="" class="btn btn-default btn-sm">Tillbaka</button>
    <a class="btn btn-primary" href="<?= $link ?>" target="_blank">Karta i ny flik</a>
</div>