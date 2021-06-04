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
                <li class="is-active"><a href="#" aria-current="page">Alla fakturor</a></li>
            </ul>
        </nav>
        <h1 class="title">
            Alla fakturor
        </h1>
    </div>
</section>
        <?php
        foreach (array_reverse($invoice_groups) as $group => $invoices) {
            print '<section class="section"><div class="container">';
            printf('<p class="subtitle">%s</p>', [
                'is_invalidated' => 'Ogiltig',
                'is_paid' => 'Betalad',
                'is_sent' => 'Skickad',
                'is_ready' => 'Klar för att skickas',
                'is_created' => 'Utkast'
            ][$group]);
            foreach ($invoices as $invoice) {
                printf('<p><a href="invoice.php?id=%s">%s</a></p>', $invoice->invoice_id, $invoice->external_invoice_id);
            }
            print '</div></section>';
        }
        ?>
</body>
</html>
