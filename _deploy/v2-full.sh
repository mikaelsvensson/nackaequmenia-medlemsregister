SFTP_CONNECTION=$1
TEMPDIR=./deploy-temp
rm -rf $TEMPDIR
mkdir -p $TEMPDIR
mkdir -p $TEMPDIR/lib/phpmailer

## Dependencies

mkdir -p $TEMPDIR/lib/phpmailer
wget --quiet https://github.com/PHPMailer/PHPMailer/archive/master.zip -O $TEMPDIR/phpmailer.zip.temp
unzip -q -j $TEMPDIR/phpmailer.zip.temp PHPMailer-master/src/* -d $TEMPDIR/lib/phpmailer
rm $TEMPDIR/phpmailer.zip.temp

## Copy files

cp -r ../ $TEMPDIR

## Remove files which shouldn't be copied

rm -rf $TEMPDIR/temp
rm $TEMPDIR/db.sqlite3
rm $TEMPDIR/config.sample.ini

## Fix config files

rm $TEMPDIR/config.ini
mv $TEMPDIR/config.prod.ini $TEMPDIR/config.ini
rm $TEMPDIR/.htaccess
mv $TEMPDIR/.htaccess.prod $TEMPDIR/.htaccess

## Copy

sftp ${SFTP_CONNECTION} <<EOF
mkdir medlemsregister
cd medlemsregister
put -r ${TEMPDIR}/*
exit
EOF
