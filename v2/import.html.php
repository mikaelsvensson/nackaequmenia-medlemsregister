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

            <div class="content">
                <p>Gör så här för att skapa importfilen:</p>
                <ul>
                    <li>Logga in i Repet.</li>
                    <li>Se till så att du har rollen <em>E-LokalAdmin</em> (andra roller kan inte exportera fullständigt personnummer).</li>
                    <li>
                        Skapa rapporten <em>Närvarokort inkl persnr</em> med följande inställningar:
                        <ul>
                            <li>Huvudgrupp: Scout</li>
                            <li>Undergrupp: Alla undergrupper</li>
                            <li>Grupp: Alla grupper</li>
                            <li>År och Terminen: (Senaste terminen)</li>
                        </ul>
                    </li>
                    <li>Kör rapport.</li>
                    <li>Exportera som <em>MHTML (web archive)</em>.</li>
                    <li>Välj filen här nedan.</li>
                </ul>
            </div>

            <div class="file">
                <label class="file-label">
                    <input class="file-input" type="file" name="file">
                    <span class="file-cta">
                        <span class="file-label">
                            Välj fil…
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

            <div class="content">
                <p>Importfilen ska vara en "kommaseparerad CSV-fil" med exakt dessa kolumner:</p>
                <ul>
                    <li>Deltagare, Personnummer</li>
                    <li>Deltagare, Telefonnummer</li>
                    <li>Deltagare, E-postadress</li>
                    <li>Deltagare, Gatuadress</li>
                    <li>Deltagare, Postort</li>
                    <li>Deltagare, Medicinsk notering</li>
                    <li>Vårdnadshavare 1, Förnamn</li>
                    <li>Vårdnadshavare 1, Efternamn</li>
                    <li>Vårdnadshavare 1, E-postadress</li>
                    <li>Vårdnadshavare 1, Telefon</li>
                    <li>Vårdnadshavare 2, Förnamn</li>
                    <li>Vårdnadshavare 2, Efternamn</li>
                    <li>Vårdnadshavare 2, E-postadress</li>
                    <li>Vårdnadshavare 2, Telefon</li>
                </ul>
            </div>

            <div class="file">
                <label class="file-label">
                    <input class="file-input" type="file" name="file">
                    <span class="file-cta">
                        <span class="file-label">
                            Välj fil…
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
