<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Faktura <?= $invoice->external_invoice_id ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.1/css/bulma.min.css">
</head>
<body>
<section class="section">
    <div class="container">
        <nav class="breadcrumb" aria-label="breadcrumbs">
            <ul>
                <li><a href="index.php">Start</a></li>
                <li>
                    <a href="person.php?id=<?= $invoice->reference_person_id ?>"><?= $reference_person->first_name ?></a>
                </li>
                <li class="is-active"><a href="#" aria-current="page">Faktura <?= $invoice->external_invoice_id ?></a>
                </li>
            </ul>
        </nav>
        <h1 class="title">
            Faktura <?= $invoice->external_invoice_id ?>
        </h1>
        <?php if ($invoice->is_ready) { ?>
            <?php if (!empty($public_html_url)) { ?>
                <p><a href="<?= $public_html_url ?>" class="href">HTML</a></p>
            <?php } ?>
            <?php if (!empty($public_pdf_url)) { ?>
                <p><a href="<?= $public_pdf_url ?>" class="href">PDF</a></p>
            <?php } ?>
        <?php } else { ?>
            <p><a href="invoice-view.php?id=<?= $invoice->invoice_id ?>" class="href">Visa</a></p>
        <?php } ?>
    </div>
</section>
<section class="section">
    <div class="container">
        <form action="" method="post">
            <p class="subtitle">
                Rader
            </p>
            <table class="table is-narrow">
                <thead>
                <tr>
                    <td>Text</td>
                    <td>Antal</td>
                    <td>Pris</td>
                    <td>Summa</td>
                </tr>
                </thead>
                <tbody>
                <?php
                foreach ($invoice->items as $item) {
                    printf('<tr><td>%s</td><td>%s</td><td>%s</td><td>?</td></tr>',
                        sprintf('<input class="input" type="text" name="item__%s__text" value="%s" %s/>', $item->line_number, $item->text, $invoice->is_readonly ? 'disabled="disabled"' : ''),
                        sprintf('<input class="input" type="text" name="item__%s__unit_count" value="%s" %s/>', $item->line_number, $item->unit_count, $invoice->is_readonly ? 'disabled="disabled"' : ''),
                        sprintf('<input class="input" type="text" name="item__%s__unit_price" value="%s" %s/>', $item->line_number, invoices_from_cents($item->unit_price), $invoice->is_readonly ? 'disabled="disabled"' : ''));
                }
                printf('<tr><td>%s</td><td>%s</td><td>%s</td><td>?</td></tr>',
                    sprintf('<input class="input" type="text" name="item__new__text" value="%s" %s/>', '', $invoice->is_readonly ? 'disabled="disabled"' : ''),
                    sprintf('<input class="input" type="text" name="item__new__unit_count" value="%s" %s/>', 1, $invoice->is_readonly ? 'disabled="disabled"' : ''),
                    sprintf('<input class="input" type="text" name="item__new__unit_price" value="%s" %s/>', invoices_from_cents(100), $invoice->is_readonly ? 'disabled="disabled"' : ''));
                ?>
                </tbody>
            </table>

            <?php if (!$invoice->is_readonly) { ?>
                <button class="button" name="action" value="invoices_save">Spara ändringar</button>
                <button class="button" name="action" value="invoices_set_ready">Lås</button>
            <?php } ?>
        </form>
    </div>
</section>
<section class="section">
    <div class="container">
        <form action="" method="post">
            <p class="subtitle">
                Skicka faktura som PDF
            </p>
            <?php if ($invoice->is_ready && !$invoice->is_invalidated) { ?>
                <div class="field">
                    <!--                <label class="label">Mottagare</label>-->
                    <?php
                    foreach ([$reference_person, $guardian_1, $guardian_2] as $person) {
                        if (isset($person)) {
                            if (!empty($person->email)) {
                                $id = uniqid();
                                printf('<div class="control"><label class="checkbox"><input type="checkbox" name="email_recipient[]" value="%s"> %s (%s)</label></div>',
                                    $person->email,
                                    $person->email,
                                    join(' ', [$person->first_name, $person->sur_name]));
                            }
                        }
                    }
                    ?>
                    <div class="buttons">
                        <button class="button" name="action" value="invoices_send">Skicka</button>
                    </div>
                </div>
            <?php } else { ?>
                <p>Fakturan kan skickas i detta läge.</p>
            <?php } ?>
        </form>
    </div>
</section>
<?php if (!$invoice->is_paid) { ?>
    <section class="section">
        <div class="container">
            <form action="" method="post">
                <p class="subtitle">
                    Markera som betalad
                </p>
                <div>
                    <?php
                    printf('<input class="input" type="date" name="invoices_set_paid__payment_date" value="%s" />', (new DateTime())->format('Y-m-d'));
                    ?>
                </div>
                <button class="button" name="action" value="invoices_set_paid">Markera som betalad</button>
            </form>
        </div>
    </section>
<?php } ?>
<?php if (!$invoice->is_invalidated) { ?>
    <section class="section">
        <div class="container">
            <form action="" method="post">
                <p class="subtitle">
                    Makulera
                </p>
                <div>
                    <input class="input" type="text" name="invoices_set_invalidated__note" placeholder="Varför makuleras fakturan?"/>
                </div>
                <button class="button" name="action" value="invoices_set_invalidated">Makulera</button>
            </form>
        </div>
    </section>
<?php } ?>
<section class="section">
    <div class="container">
        <p class="subtitle">
            Historik
        </p>
        <?php
        foreach ($invoice->log as $item) {
            $data = json_decode($item->action_data, true);
            switch ($item->action) {
                case INVOICE_ACTION_INVALIDATED:
                    $action_html = sprintf('%s. Anteckning: %s.', $item->action, $data['note'] ?? 'Saknas');
                    break;
                case INVOICE_ACTION_PAID:
                    $payment_date = $data['payment_date'];
                    $action_html = sprintf(
                        '%s. Datum: %s.',
                        $item->action,
                        isset($payment_date) 
                            ? (new DateTime("@${payment_date}"))->format('Y-m-d')
                            : 'Okänt'
                    );
                    break;
                default:
                    $action_html = $item->action;
                    break;
            }
            printf('<p>%s: %s</p>', date("Y-m-d H:i", $item->created_at), $action_html);
        }
        ?>
    </div>
</section>
</body>
</html>
