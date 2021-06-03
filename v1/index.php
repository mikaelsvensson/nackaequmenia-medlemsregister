<html>
    <head>
        <link rel="stylesheet" type="text/css" href="core.css" />
        <title>Medlemsregister - Nacka SMU</title>
        <meta content="width=device-width; initial-scale=1.0; maximum-scale=1.0; user-scalable=0;" name="viewport" />
    </head>
    <body>
        <h1>Medlemsregister</h1>
        <!--
        <?php
        define("NODE_TYPE_PROCESSING_INSTRUCTION", 7);
        $formats = array();
        foreach (scandir('.') as $name) {
            if ($name != '.' && $name != '..') {

                if (substr($name, -4) == '.xsl') {
                    $title = $name;

                    $doc = new DOMDocument();
                    $doc -> load($name);
                    foreach ($doc->childNodes as $node) {
                        if ($node -> nodeType == NODE_TYPE_PROCESSING_INSTRUCTION) {
                            $target = $node -> target;
                            $data = $node -> data;
                            if ($target == "nackasmu-title") {
                                $title = substr($data, 1, -1);
                            }
                        }
                    }

                    $formats[$name] = $title;
                }
            }
        }
        asort($formats);
        foreach ($formats as $name => $title) {
            printf('<p><a href="playground/show.php?format=%s">%s</a></p>', $name, $title);
        }
        ?>
        -->

    <p><a href="google.php">Standardlistan</a></p>
    <p><a href="google-map-config.php">Karta</a></p>
    <p><a href="google-modelrelease.php">Vem får synas på nätet?</a></p>
    <p><a href="google-data-check.php">Saknar vi viktig information för några scouter?</a></p>
    <p><a href="google-birthdays.php">Födelsedagar</a></p>
    </body>
</html>