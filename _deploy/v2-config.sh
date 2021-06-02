SFTP_CONNECTION=$1
sftp ${SFTP_CONNECTION} <<EOF
pwd
cd medlemsregister
mkdir v2
cd v2
put -r ../v2/config.prod.ini config.ini
exit
EOF