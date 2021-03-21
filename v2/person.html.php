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
                <li class="is-active"><a href="#" aria-current="page"><?= $person->first_name ?></a></li>
            </ul>
        </nav>
        <h1 class="title">
            <?= $person->first_name ?>
        </h1>
        <p class="subtitle">
            <?= $person->sur_name ?>
        </p>
        <?php
        foreach ([1, 2] as $guardian_num) {
            $guardian = ${"guardian_$guardian_num"};
            if (isset($guardian)) {
                printf('<p><a href="person.php?id=%s">Kontakt: %s</a></p>', $guardian->person_id, $guardian->first_name);
            }
        }
        foreach ($children as $child) {
            printf('<p><a href="person.php?id=%s">Barn: %s</a></p>', $child->person_id, $child->first_name);
        }
        ?>

        <table class="table">
            <tbody>
            <?php
            foreach (get_object_vars($person) as $prop => $value) {
                printf('<tr><td>%s</td><td>%s</td></tr>', $prop, $value);
            }
            ?>
            </tbody>
        </table>
    </div>
</section>
<section class="section">
    <div class="container">
        <p class="subtitle">
            Fakturor
        </p>
        <?php
        foreach ($invoices as $invoice) {
            $status = $invoice->is_invalidated
                ? 'Makulerad'
                : ($invoice->is_paid
                    ? 'Betalad'
                    : ($invoice->is_sent
                        ? 'Skickad'
                        : 'Ny'));
            $status_style = $invoice->is_invalidated
                ? 'is-warning'
                : ($invoice->is_paid
                    ? 'is-success'
                    : ($invoice->is_sent
                        ? 'is-info'
                        : 'is-light'));
            printf('<p><a href="%s">%s</a> <span class="tag %s is-light">%s</span></p>',
                'invoice.php?id=' . $invoice->invoice_id,
                $invoice->external_invoice_id,
                $status_style,
                $status);
        }
        ?>

        <form action="" method="post">
            <button class="button" name="action" value="invoices_create">Skapa ny</button>
        </form>
    </div>
</section>
</body>
</html>
