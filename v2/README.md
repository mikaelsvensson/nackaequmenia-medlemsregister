https://hub.docker.com/_/php/

$ docker run \
    -p 80:80 \
    --name medlemsregister \
    -v "$PWD/v2":/var/www/html \
    php:7.2-apache

$ docker stop medlemsregister
$ docker rm medlemsregister
