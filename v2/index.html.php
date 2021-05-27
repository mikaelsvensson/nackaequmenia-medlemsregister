<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nacka Equmenia - Administration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.1/css/bulma.min.css">
</head>
<body>
<section class="section">
    <div class="container">
        <nav class="breadcrumb" aria-label="breadcrumbs">
            <ul>
                <li class="is-active"><a href="#" aria-current="page">Start</a></li>:
            </ul>
        </nav>
        <div class="columns">
            <div class="column">
                <p class="subtitle">Personer</p>
                <?php
                foreach ($people as $person) {
                    printf('<p><a href="person.php?id=%s"><strong>%s</strong> %s</a>%s</p>', $person->person_id, $person->first_name ?? $person->pno ?? $person->person_id, $person->sur_name,
                    isset($person->guardian_1_person_id) || isset($person->guardian_1_person_id) ? ' <span class="tag is-light">Scout</span>' : '');
                }
                ?>
                <p><a href="import.php">Importera...</a></p>
            </div>
            <div class="column">
                <p class="subtitle">Fakturor</p>
                <p><a href="invoices.php">Alla fakturor...</a></p>
            </div>
        </div>
    </div>
</section>
</body>
</html>
