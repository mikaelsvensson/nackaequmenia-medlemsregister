<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Alla fakturor</title>
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
                'is_invalidated' => 'Makulerad',
                'is_paid' => 'Betalad',
                'is_sent' => 'Skickad',
                'is_ready' => 'Klar för att skickas',
                'is_created' => 'Utkast'
            ][$group]);
            foreach ($invoices as $invoice) {
                $lines = join(', ', array_map(function ($invoice_line) { 
                    return $invoice_line->text; 
                }, $invoice->items));

                $reference_person = join(' ', array(
                    $invoice->reference_person->first_name, 
                    $invoice->reference_person->sur_name
                ));

                printf('<p><a href="invoice.php?id=%s">%s</a>: %s. %s.</p>', 
                    $invoice->invoice_id, 
                    $invoice->external_invoice_id, 
                    $reference_person,
                    $lines);
            }
            print '</div></section>';
        }
        ?>
</body>
</html>
