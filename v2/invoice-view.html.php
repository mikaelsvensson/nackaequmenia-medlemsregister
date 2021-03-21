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
            font-size: 3em;
            margin: 0;
            font-weight: normal;
        }

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
            padding: 0.4em 1em 0.4em 0;
        }

        table.invoice-items {
            width: 100%;
        }

        table.invoice-items td:last-child {
            text-align: right;
            padding: 0.4em 0 0.4em 0;
        }

        table.invoice-items tfoot td {
            border-top: 1px solid black;
            font-weight: bold;
        }

        div.code-container {
            /*width: 100%;*/
            display: flex;
            justify-content: center;
        }

        div.code-description {
            margin: 2em 0 0 0;
            font-size: 0.9em;
        }

        img.code-image {
            width: 70vw;
            max-width: 200px;
            margin: 3em 0;
            /*padding: 1em;*/
            /*border: 1px solid rgba(0, 0, 0, 0.1);*/

            /* offset-x | offset-y | blur-radius | spread-radius | color */
            /*box-shadow: 5px 5px 20px 0px rgba(0, 0, 0, 0.05);*/
            /*border-radius: 10px;*/
        }

        div.payment-methods {
            display: flex;
            flex-wrap: wrap;
            /*justify-content: space-around;*/

        }

        div.payment-method {
            flex: 1;
        }

        div.payment-method p {
            font-weight: bold;
        }

        @media screen and (max-width: 375px) {

        }

        @media screen and (min-width: 376px) {

            div.page-container {
                max-width: 50em;
                margin: 0 auto;
            }

            div.payment-method {
                max-width: 20em;
                margin-left: 2em;
            }

            div.payment-methods-container {
                margin: 0 0 0 -2em
            }

        }
    </style>
</head>
<body>
<div class="page-container">

    <h1>Faktura</h1>

    <div class="invoice-description invoice-description-1">
        Den här fakturan gäller <?= join(' ', [$reference_person->first_name, $reference_person->sur_name]) ?>
        och skickades <?= date('Y-m-d', $ready_date) ?>.
    </div>

    <?= !empty(trim($invoice->text_1)) ? sprintf('<p>%s</p>', $invoice->text_1) : '' ?>

    <table class="invoice-items">
        <tfoot>
        <tr>
            <td></td>
            <td>
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
                <td>
                    <?= invoices_from_cents($item->unit_count * $item->unit_price) ?>&nbsp;kr
                </td>
            </tr>
        <?php } ?>
        </tbody>
    </table>

    <?php if ($sum > 50000) { ?>
        <div class="invoice-description invoice-description-2">
            Meddela oss om ni önskar delbetala över flera månader.
            Ange i så fall samma meddelande på samtliga delbetalningar.
        </div>
    <?php } ?>

    <?php if ($sum > 0) { ?>
        <p>Du kan betala via Bankgiro eller Swish.</p>
        <div class="payment-methods-container">
            <div class="payment-methods">
                <div class="payment-method">
                    <p>Betala via Swish:</p>
                    <table>
                        <tbody>
                        <tr>
                            <td>Mottagare:</td>
                            <td><code><?= $config['swish']['number'] ?></code></td>
                        </tr>
                        <tr>
                            <td>Meddelande:</td>
                            <td><code><?= $invoice->external_invoice_id ?></code></td>
                        </tr>
                        <tr>
                            <td>Belopp:</td>
                            <td><code><?= invoices_from_cents($sum) ?></code></td>
                        </tr>
                        </tbody>
                    </table>
                    <?php if (!empty($swish_qr_code_url)) { ?>
                        <div class="code-description">
                            I Swish-appen kan du skanna QR-koden för att slippa knappa in siffrorna själv:
                        </div>
                        <div class="code-container">
                            <img class="code-image" src="<?= $swish_qr_code_url ?>">
                        </div>
                    <?php } ?>
                </div>
                <div class="payment-method">
                    <p>Betala via Bankgiro:</p>
                    <table>
                        <tbody>
                        <tr>
                            <td>Bankgiro-nummer:</td>
                            <td><code><?= $config['bankgiro']['number'] ?></code></td>
                        </tr>
                        <tr>
                            <td>Meddelande:</td>
                            <td><code><?= $invoice->external_invoice_id ?></code></td>
                        </tr>
                        <tr>
                            <td>Belopp:</td>
                            <td><code><?= invoices_from_cents($sum) ?></code></td>
                        </tr>
                        <tr>
                            <td>Förfallodatum:</td>
                            <td><code><?= date('Ymd', $due_date) ?></code></td>
                        </tr>
                        </tbody>
                    </table>
                    <?php if (!empty($bankgiro_qr_code_url)) { ?>
                        <div class="code-description">
                            I Nordeas eller Swedbanks appar kan du skanna QR-koden för att slippa knappa in siffrorna
                            själv:
                        </div>
                        <div class="code-container">
                            <img class="code-image" src="<?= $bankgiro_qr_code_url ?>">
                        </div>
                    <?php } ?>
                </div>
            </div>
        </div>
    <?php } else { ?>
        <p>Vi kommer återbetala <?= invoices_from_cents(abs($sum)) ?> kr.</p>
        <p>Pengarna återbetalas till tidigare inrapporterat bankkonto.</p>
    <?php } ?>
</div>
</body>
</html>
