# feel free to change base image, or rework the docker build steps as desired...
FROM ubuntu:14.04

RUN apt-get update -y
RUN apt-get install -y php5-cli
# shorter composer install
#RUN curl -O https://getcomposer.org/composer.phar && chmod +x composer.phar && mv composer.phar /usr/local/bin/composer
# or the longer steps alternative...
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php -r "if (hash_file('SHA384', 'composer-setup.php') === 'e115a8dc7871f15d853148a7fbac7da27d6c0030b848d9b3dc09e2a0388afed865e6a3d6b3c0fad45c48e2b5fc1196ae') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
RUN php composer-setup.php --install-dir=/usr/local/bin --filename=composer
RUN chmod +x /usr/local/bin/composer
RUN php -r "unlink('composer-setup.php');"

# update cache again to get correct PHP-related packages for install?
#RUN apt-get update

# install RF related stuff for acceptance testing
RUN apt-get install -y python2.7 python-pip
RUN pip install robotframework

# set up build files and paths
RUN mkdir -p /opt/phrrs/
WORKDIR /opt/phrrs/
ENV HOME /opt/phrrs/
COPY composer.json /opt/phrrs/

#RUN composer require jplambert/phrrs
RUN composer install

COPY . /opt/phrrs/

# Clean up APT when done.
#RUN apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*
RUN rm -rf /tmp/* /var/tmp/*

# Define default command
CMD ["bash"]

# change remote library server port if not using default
EXPOSE 8270

# might have to set proper timezone in docker image during build (or at runtime when starting up container)
# it appears /etc/localtime and /etc/timezone in the image when started up as container
# do not have the correct timezone format to be parsed by src/TimeZoneManager.php

# examples of building & using docker image
# building (from root of phrrs):
#   docker build -t phrrs -f vm-image-configs/Dockerfile .
# starting container of built image:
#   docker run --rm -it -p 8270:8270 [-v /localPath/OnYourHost:/aCustomPath/OnDockerImage] phrrs bash
# where bracket argument are optional (port map between host and container)
# then in the bash shell:
#   php src/StartRobotRemoteServer.php tests/test-libraries/ExampleLibrary.php 8270
# and then can run RF tests against server outside of the docker container, on host or elsewhere,
# assuming server in container is accessible over network outside by others
# and can also run the unit & acceptance tests from the container bash shell:
#   vendor/phpunit/phpunit/phpunit tests/
