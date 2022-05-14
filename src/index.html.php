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
                <?php
                foreach ($people_groups as $group => $people) {
                    if (count($people) > 0) {
                        printf('<p class="subtitle">Personer %s år</p>', $group);
                        foreach ($people as $person) {
                            printf('<p><a href="person.php?id=%s"><strong>%s</strong> %s</a></p>', 
                                $person->person_id, 
                                $person->first_name ?? $person->pno ?? $person->person_id, 
                                $person->sur_name
                            );
                        }
                        print str_repeat('<br>', 3);
                    }
                }        
                ?>
            </div>
            <div class="column">
                <p class="subtitle">Fakturor</p>
                <p><a href="invoices.php">Alla fakturor</a></p>
                
                <br>
                <br>
                <br>
                <br>
                
                <p class="subtitle">Administration av administrationen</p>
                <p><a href="backup.php">Ladda ner data</a></p>
                <p><a href="person-upsert.php">Lägg till person manuellt</a></p>
                <p><a href="import.php">Importera</a></p>
            </div>
        </div>
    </div>
</section>
</body>
</html>
