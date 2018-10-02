# Copyright 2017-2018 HowToCompute. All Rights Reserved
# Any Distribution Of Any Kind Or Usage With Any Other System Than A Copy Of TwitchWorks Bought From HowToCompute Or Sublicensees Is Strictly Prohibited.

# NOTE: This has been built and tested for the x86_64 architecture. We are currently unable to support any ARM varients or 

# The base image - the official/trusted PHP7 with apache
FROM php:7.0-apache

# Ensure we're running the latest set of package releases
RUN apt-get update -y

# mysql server setup START

# Allows for a headless install - default password shouldn't matter as the port won't be exposed
RUN echo "mysql-server mysql-server/root_password password root" | debconf-set-selections
RUN echo "mysql-server mysql-server/root_password_again password root" | debconf-set-selections

# Avoids a few strange issues
RUN sed -i "s/^exit 101$/exit 0/" /usr/sbin/policy-rc.d

RUN apt-get --assume-yes install mysql-server

# Run this or using localhost'll cause connectivity issues
RUN ln -s /var/run/mysqld/mysqld.sock /tmp/mysql.sock

#mysql server setup END

# Set up MYSQL database & it's tables
RUN /etc/init.d/mysql start && sleep 10s && mysql -uroot -proot -e 'CREATE DATABASE `twitchworks`; USE `twitchworks`; CREATE TABLE TwitchUsers( `ID` int NOT NULL AUTO_INCREMENT, `Token` varchar(255) NOT NULL, `Key` varchar(255) NOT NULL, `Username` varchar(255), `OAuthToken` varchar(255), UNIQUE (`ID`), UNIQUE (`Token`));'


# Enable the apache2 rewrite mod so we can restrict access in given folders/etc.
RUN a2enmod rewrite

# Install the php MySQLi extention so we can access the mysql database
RUN docker-php-ext-install mysqli

# Copy over the TwitchWorks files
COPY src/ /var/www/html/

# Copy over the default config containing the default database's credentials/etc.
COPY DockerResources/config.ini /var/www/html/private/config.ini

# Copy the run script (ensures both apache & the mysql server are started)
COPY DockerResources/runall.sh runall.sh

# Ensure the start script can be executed
RUN chmod +x ./runall.sh

# Expose HTTP and HTTPS ports
EXPOSE 80
EXPOSE 443

# Set the start command to run the start script.
CMD ./runall.sh
