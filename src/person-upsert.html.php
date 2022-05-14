<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $page_title ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bulma@0.9.1/css/bulma.min.css">
</head>
<body>
<section class="section">
    <div class="container">
        <nav class="breadcrumb" aria-label="breadcrumbs">
            <ul>
                <li><a href="index.php">Start</a></li>
                <li class="is-active"><a href="#" aria-current="page"><?= $page_title ?></a></li>
            </ul>
        </nav>
        <h1 class="title">
            <?= $page_title ?>
        </h1>
        <!--
        <p class="subtitle">
            Personuppgifter
        </p>
        -->

        <form method="post">
            <div class="field">
                <div class="control">
                    <input class="input" type="text" value="<?= !empty($person) ? $person->first_name : '' ?>" name="first_name" placeholder="Förnamn">
                </div>
            </div>
            <div class="field">
                <div class="control">
                    <input class="input" type="text" value="<?= !empty($person) ? $person->sur_name : '' ?>" name="sur_name" placeholder="Efternamn">
                </div>
            </div>
            <div class="field">
                <div class="control">
                    <input class="input" type="text" value="<?= !empty($person) ? $person->phone : '' ?>" name="phone" placeholder="Telefonnummer">
                </div>
            </div>
            <div class="field">
                <div class="control">
                    <input class="input" type="text" value="<?= !empty($person) ? $person->email : '' ?>" name="email" placeholder="E-postadress">
                </div>
            </div>
            <div class="field">
                <div class="control">
                    <input class="input" type="text" value="<?= !empty($person) ? $person->pno : '' ?>" name="pno" placeholder="Personnummer" <?= $is_update_mode ? 'readonly' : '' ?>>
                </div>
            </div>

            <div class="buttons">
                <button class="button" name="action" value="person_upsert" type="submit">Spara</button>
            </div>
        </form>
    </div>
</section>
</body>
</html>
