SFTP_CONNECTION=$1
sftp ${SFTP_CONNECTION} <<EOF
mkdir medlemsregister
cd medlemsregister
put -r ../v2/config.prod.ini config.ini
put -r ../v2/.htaccess.prod .htaccess
put -r ../v2/.htpasswd .htpasswd
exit
EOF