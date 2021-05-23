## Start project locally

https://hub.docker.com/_/php/

    $ docker run \
        -p 80:80 \
        --name medlemsregister \
        -v "$PWD/v2":/var/www/html \
        php:7.2-apache

Go to http://localhost/

Create a `config.ini` file based on `config.sample.ini`.

    $ cp config.sample.ini config.ini

## Stop and uninstall

Do this:

    $ docker stop medlemsregister
    $ docker rm medlemsregister


## Install dependencies

  mkdir -p lib/phpmailer
  wget https://github.com/PHPMailer/PHPMailer/archive/master.zip -O phpmailer.zip.temp
  unzip -j phpmailer.zip.temp PHPMailer-master/src/* -d lib/phpmailer
  rm phpmailer.zip.temp