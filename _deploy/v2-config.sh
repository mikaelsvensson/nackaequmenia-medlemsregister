SFTP_CONNECTION=$1
sftp ${SFTP_CONNECTION} <<EOF
mkdir medlemsregister
cd medlemsregister
put -r ../config.prod.ini config.ini
put -r ../.htaccess.prod .htaccess
put -r ../.htpasswd .htpasswd
exit
EOF