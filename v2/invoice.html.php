<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Hello Bulma!</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.1/css/bulma.min.css">
</head>
<body>
<section class="section">
    <div class="container">
        <nav class="breadcrumb" aria-label="breadcrumbs">
            <ul>
                <li><a href="index.php">Start</a></li>
                <li><a href="person.php?id=<?= $invoice->reference_person_id ?>"><?= $reference_person->first_name ?></a></li>
                <li class="is-active"><a href="#" aria-current="page">Faktura <?= $invoice->external_invoice_id ?></a></li>
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
                        sprintf('<input class="input" type="text" name="item__%s__text" value="%s" %s/>', $item->line_number, $item->text, $invoice->is_ready ? 'disabled="disabled"' : ''),
                        sprintf('<input class="input" type="text" name="item__%s__unit_count" value="%s" %s/>', $item->line_number, $item->unit_count, $invoice->is_ready ? 'disabled="disabled"' : ''),
                        sprintf('<input class="input" type="text" name="item__%s__unit_price" value="%s" %s/>', $item->line_number, invoices_from_cents($item->unit_price), $invoice->is_ready ? 'disabled="disabled"' : ''));
                }
                printf('<tr><td>%s</td><td>%s</td><td>%s</td><td>?</td></tr>',
                    sprintf('<input class="input" type="text" name="item__new__text" value="%s" %s/>', '', $invoice->is_ready ? 'disabled="disabled"' : ''),
                    sprintf('<input class="input" type="text" name="item__new__unit_count" value="%s" %s/>', 1, $invoice->is_ready ? 'disabled="disabled"' : ''),
                    sprintf('<input class="input" type="text" name="item__new__unit_price" value="%s" %s/>', invoices_from_cents(100), $invoice->is_ready ? 'disabled="disabled"' : ''));
                ?>
                </tbody>
            </table>

            <?php if ($invoice->is_ready) { ?>

            <?php } else { ?>
                <button class="button" name="action" value="invoices_save">Spara ändringar</button>
                <button class="button" name="action" value="invoices_set_ready">Lås</button>
            <?php } ?>
        </form>
    </div>
</section>
<section class="section">
    <div class="container">
        <p class="subtitle">
            Historik
        </p>
        <?php
        foreach ($invoice->log as $item) {
            $action_html = $item->action;
//            switch ($item->action) {
//                case INVOICE_ACTION_RENDERED:
//                    $action_html .= $item->action_data;
//                    break;
//            }
            printf('<p>%s: %s</p>', date("Y-m-d H:i", $item->created_at), $action_html);
        }
        ?>
    </div>
</section>
</body>
</html>
