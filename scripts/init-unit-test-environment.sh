#! /bin/bash
# Let's begin...
echo ".........................................."
echo
echo "Initializing local unit test environment"
echo
echo ".........................................."
echo

echo "The unit tests will run on your localhost. You need to have a running PHP server and MySQL database."

read -p 'Please enter the MySQL host [localhost]: ' mysql_host
mysql_host=${mysql_host:-localhost}

read -p 'Please enter the MySQL user [root]: ' mysql_user
mysql_user=${mysql_user:-root}

read -sp 'Please enter the MySQL password []: ' mysql_password
echo

read -p 'Please enter the WordPress version which should be used. (Versions can be found here: https://develop.svn.wordpress.org/tags/) [latest]: ' wordpress_version
wordpress_version=${wordpress_version:-latest}

scripts/install-wp-tests.sh wordpress_test $mysql_user "$mysql_password" $mysql_host $wordpress_version
