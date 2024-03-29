## Start project locally

https://hub.docker.com/_/php/

    $ docker run \
        -p 80:80 \
        --name medlemsregister \
        -v "$PWD/src":/var/www/html \
        php:7.2-apache

Go to http://localhost/

Create a `src/config.ini` file based on `src/config.sample.ini`.

    $ cp src/config.sample.ini src/config.ini

## Stop and uninstall

Do this:

    $ docker stop medlemsregister
    $ docker rm medlemsregister


## Install dependencies

  mkdir -p src/lib/phpmailer
  wget https://github.com/PHPMailer/PHPMailer/archive/master.zip -O phpmailer.zip.temp
  unzip -j phpmailer.zip.temp PHPMailer-master/src/* -d src/lib/phpmailer
  rm phpmailer.zip.temp

## Deploy using SFTP

Full deploy:

    $ cd _deploy
    $ ./v2-full.sh user@server

Only deploy configuration file:

    $ cd _deploy
    $ ./v2-config.sh user@server
