<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: sans-serif;
            margin: 0;
            padding: 1em;
        }

        body, p, td {
            font-size: 1.0em;
        }

        h1 {
            font-family: sans-serif;
            font-size: 2em;
            margin: 0;
            font-weight: normal;
        }

        div.footer,
        small {
            font-size: 0.8em;
        }

        code {
            font-family: monospace;
            font-size: 1.2em;
        }

        div.invoice-description {
            padding: 0;
            margin: 1em 0;
        }

        table {
            border-collapse: collapse;
        }

        table td {
            text-align: left;
            margin: 0;
            padding: 0.4em 0;
        }

        table.invoice-items {
            min-width: 20em;
            max-width: 42em;
        }

        table.invoice-items tfoot td {
            border-top: 1px solid black;
            font-weight: bold;
        }

        div.payment-method p {
            font-weight: bold;
        }
    </style>
</head>
<body>

    <h1>Faktura från scouterna</h1>

    <div class="invoice-description invoice-description-1">
        Den här fakturan gäller <?= join(' ', [$reference_person->first_name, $reference_person->sur_name]) ?>
        och skickades <?= date('Y-m-d', $ready_date) ?>.
    </div>

    <?= !empty(trim($invoice->text_1)) ? sprintf('<p>%s</p>', $invoice->text_1) : '' ?>

    <table class="invoice-items">
        <tfoot>
        <tr>
            <td></td>
            <td style="text-align: right">
                Summa: <?= invoices_from_cents($sum) ?>&nbsp;kr
            </td>
        </tr>
        </tfoot>
        <tbody>
        <?php foreach ($invoice->items as $item) { ?>
            <tr>
                <td>
                    <?= $item->text . ($item->unit_count > 1 ? sprintf(' (%d st)', $item->unit_count) : '') ?>
                </td>
                <td style="text-align: right">
                    <?= invoices_from_cents($item->unit_count * $item->unit_price) ?>&nbsp;kr
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    <?php if ($sum > 0) { ?>
        <p>Du kan betala via Bankgiro eller Swish.</p>
        <table>
            <tbody>
            <tr>
                <td style="padding-right: 1em">Swish-nummer:</td>
                <td><code><?= $config['swish']['number'] ?></code></td>
            </tr>
            <tr>
                <td style="padding-right: 1em">Bankgiro-nummer:</td>
                <td><code><?= $config['bankgiro']['number'] ?></code></td>
            </tr>
            <tr>
                <td style="padding-right: 1em">Meddelande:</td>
                <td><code><?= $invoice->external_invoice_id ?></code></td>
            </tr>
            <tr>
                <td style="padding-right: 1em">Belopp:</td>
                <td><code><?= invoices_from_cents($sum) ?></code></td>
            </tr>
            <tr>
                <td style="padding-right: 1em">Förfallodatum:</td>
                <td><code><?= date('Ymd', $due_date) ?></code></td>
            </tr>
        </table>
    <?php } else { ?>
        <p>Vi kommer återbetala <?= invoices_from_cents(abs($sum)) ?> kr.</p>
        <p>Pengarna återbetalas till tidigare inrapporterat bankkonto.</p>
    <?php } ?>
    <p class="footer">
        <p>Tips: Swish-appen, och vissa bank-appar, kan läsa av QR-koderna i bifogad PDF. Då slipper du knappa in betalningsuppgifterna själv. Mycket smidigt!</p>
        <p>Tips: Du hittar också denna faktura på <a href="<?= $public_html_url ?>"><?= $public_html_url ?></a>.</p>
        <?php if ($sum > 50000) { ?>
            <p>Meddela oss om ni önskar delbetala över flera månader.</p>
        <?php } ?>
    </div>
</body>
</html>
