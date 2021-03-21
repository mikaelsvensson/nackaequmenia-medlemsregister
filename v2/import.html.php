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
                <li class="is-active"><a href="#" aria-current="page">Importera</a></li>
            </ul>
        </nav>
        <h1 class="title">
            Import
        </h1>
    </div>
</section>
<section class="section">
    <div class="container">
        <form enctype="multipart/form-data" action="" method="post">
            <p class="subtitle">
                Importera från Repet
            </p>

            <div class="file">
                <label class="file-label">
                    <input class="file-input" type="file" name="file">
                    <span class="file-cta">
                        <span class="file-label">
                            Choose a file…
                        </span>
                    </span>
                </label>
            </div>

            <button class="button" name="action" value="import_repet">Importera</button>
        </form>

        <form enctype="multipart/form-data" action="" method="post">
            <p class="subtitle">
                Importera från CSV
            </p>

            <div class="file">
                <label class="file-label">
                    <input class="file-input" type="file" name="file">
                    <span class="file-cta">
                        <span class="file-label">
                            Choose a file…
                        </span>
                    </span>
                </label>
            </div>

            <button class="button" name="action" value="import_csv">Importera</button>
        </form>
    </div>
</section>
</body>
</html>
