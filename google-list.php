<div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
    <?php foreach ($entries as $key => $entry) { ?>
        <div class="panel panel-default">
            <div class="panel-heading" role="tab" id="heading-<?= $key ?>">
                <?php
                $checked = isset($_POST['select_contacts']) && in_array($key, $_POST['select_contacts']);
                printf('<input type="checkbox" name="select_contacts[]" value="%s" id="checkbox-%s" %s style="float: left; margin: 0.1em 1.0em 0em 0em">', $key, $key, $checked ? 'checked="checked"' : "");
                ?>
                <h4 class="panel-title" role="button" data-toggle="collapse" data-parent="#accordion"
                    data-target="#person-<?= $key ?>" aria-expanded="false" aria-controls="person-<?= $key ?>">
                    <?= $entry->name ?>
                    <small style="white-space: nowrap" title="<?= $entry->ssn ?>"> <?= $entry->age ?> år</small>
                </h4>
            </div>
            <div id="person-<?= $key ?>" class="panel-collapse collapse" role="tabpanel"
                 aria-labelledby="heading-<?= $key ?>">
                <div class="panel-body">
                    <div class="row">
                        <div class="col-xs-12">
                            <?php printf('<a href="mailto:%s">%s</a>', $entry->email, $entry->email) ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            <?php printf('<a href="tel:%s">%s</a>', $entry->phone, $entry->phone) ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            <?php printf('<a href="tel:%s">%s</a>', $entry->phone_mobile, $entry->phone_mobile) ?>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-xs-12">
                            <?php printf('<a href="http://kartor.eniro.se/?q=%s">%s, %s</a>',
                                urlencode($entry->address_street . ',' . $entry->address_postal),
                                $entry->address_street,
                                $entry->address_postal) ?>
                            <!--
                <?php printf('<a href="https://www.google.se/maps/place/%s">%s, %s</a>',
                                urlencode($entry->address_street . ',' . $entry->address_postal),
                                $entry->address_street,
                                $entry->address_postal) ?>
                    -->
                        </div>
                    </div>
                    <?php if (!empty($entry->allergies)) { ?>
                        <div class="row">
                            <div class="col-xs-12 col-sm-3"><strong>Allergier</strong></div>
                            <div class="col-xs-12 col-sm-9"><?= $entry->allergies ?></div>
                        </div>
                    <?php } ?>
                    <?php if (!empty($entry->note)) { ?>
                        <div class="row">
                            <div class="col-xs-12 col-sm-3"><strong>Anteckningar</strong></div>
                            <div class="col-xs-12 col-sm-9"><?= $entry->note ?></div>
                        </div>
                    <?php } ?>
                    <?php if (!empty($entry->guardian_1_name)) { ?>
                        <div class="row">
                            <div class="col-xs-12 col-sm-3">
                                <strong><?= $entry->guardian_1_name ?></strong>
                            </div>
                            <div class="col-xs-12 col-sm-3">
                                <?php printf('<a href="tel:%s">%s</a>', $entry->guardian_1_phone, $entry->guardian_1_phone) ?>
                            </div>
                            <div class="col-xs-12 col-sm-6">
                                <?php printf('<a href="mailto:%s">%s</a>', $entry->guardian_1_email, $entry->guardian_1_email) ?>
                            </div>
                        </div>
                    <?php } ?>
                    <?php if (!empty($entry->guardian_2_name)) { ?>
                        <div class="row">
                            <div class="col-xs-12 col-sm-3">
                                <strong><?= $entry->guardian_2_name ?></strong>
                            </div>
                            <div class="col-xs-12 col-sm-3">
                                <?php printf('<a href="tel:%s">%s</a>', $entry->guardian_2_phone, $entry->guardian_2_phone) ?>
                            </div>
                            <div class="col-xs-12 col-sm-6">
                                <?php printf('<a href="mailto:%s">%s</a>', $entry->guardian_2_email, $entry->guardian_2_email) ?>
                            </div>
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php } ?>
</div>
<div>
    <button type="submit" name="action" value="send-links" class="btn btn-default">Skicka länk för att uppdatera kontaktinformation</button>
    <button type="submit" name="action" value="show-map" class="btn btn-default">Visa på karta</button>
    <button type="submit" name="action" value="print-list" class="btn btn-default">Telefonlista</button>
    <button type="submit" name="action" value="send-pdf" class="btn btn-default">Skicka PDF</button>
    <button type="submit" name="action" value="sync-contacts" class="btn btn-default">Synka Google-kontakter</button>
</div>
