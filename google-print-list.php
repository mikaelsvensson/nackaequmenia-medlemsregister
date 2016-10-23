<?php
$config = parse_ini_file('config.ini', true);

foreach ($_POST['entry'] as $key) {
    $entry = $entries[$key];
    ?>
    <input type="hidden" name="entry[]" value="<?= $key ?>">
    <div class="row">
        <div class="col-xs-12">
            <p><strong><?= $entry[name] ?></strong></p>

            <p>
                <?php printf('<a href="tel:%s">%s</a>', $entry[phone], $entry[phone]) ?>
                <?php printf('<a href="mailto:%s">%s</a>', $entry[email], $entry[email]) ?>
            </p>
        </div>
    </div>

    <?php if (!empty($entry[allergies])) { ?>
        <div class="row">
            <div class="col-xs-12">
                <p>Allergier:<br>
                    <?= $entry[allergies] ?>
                </p>
            </div>
        </div>
    <?php } ?>
    <?php if (!empty($entry[note])) { ?>
        <div class="row">
            <div class="col-xs-12">
                <p>Anteckningar:<br>
                    <?= $entry[note] ?>
                </p>
            </div>
        </div>
    <?php } ?>
    <?php if (!empty($entry[guardian_1_name])) { ?>
        <div class="row">
            <div class="col-xs-12">
                <p>
                    <?= $entry[guardian_1_name] ?>
                    <?php printf('<a href="tel:%s">%s</a>', $entry[guardian_1_phone], $entry[guardian_1_phone]) ?>
                    <?php printf('<a href="mailto:%s">%s</a>', $entry[guardian_1_email], $entry[guardian_1_email]) ?>
                </p>
            </div>
        </div>
    <?php } ?>
    <?php if (!empty($entry[guardian_2_name])) { ?>
        <div class="row">
            <div class="col-xs-12">
                <p>
                    <?= $entry[guardian_2_name] ?>
                    <?php printf('<a href="tel:%s">%s</a>', $entry[guardian_2_phone], $entry[guardian_2_phone]) ?>
                    <?php printf('<a href="mailto:%s">%s</a>', $entry[guardian_2_email], $entry[guardian_2_email]) ?>
                </p>
            </div>
        </div>
    <?php } ?>
<?php } ?>
<div class="hidden-print">
    <button type="submit" name="action" value="" class="btn btn-default btn-sm">Tillbaka</button>
    <a class="btn btn-primary" href="<?= $link ?>" target="_blank">Karta i ny flik</a>
</div>