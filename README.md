# Deployment

## Install PHP and Composer

## Download Dependencies

    $ composer install

## Generate TCPDF Font Files

    $ php.exe vendor\tecnickcom\tcpdf\tools\tcpdf_addfont.php -b -i templates-resources\Cagliostro-Regular.ttf,templates-resources\FrederickatheGreat-Regular.ttf

    >>> Converting fonts for TCPDF:
    *** Output dir set to medlemsregister\vendor\tecnickcom\tcpdf/fonts/
    +++ OK   : templates-resources\Cagliostro-Regular.ttf added as cagliostro
    +++ OK   : templates-resources\FrederickatheGreat-Regular.ttf added as frederickathegreat
    >>> Process successfully completed!
    
The fonts can be referenced as "cagliostro" and "frederickathegreat" in CSS code.

## Create config.ini

Configure mail settings in config.ini (copy config.ini.sample to the web server and name it config.ini).

## Upload the Vendor Folder

...even though it will upload way more files than are actually used.

## Create Writable Folder

Create a folder called "archive", in which PHP scripts can write files, on the web server.