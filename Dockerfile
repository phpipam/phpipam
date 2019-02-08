# phpIPAM Dockerfile
# Pre-built Alpine Linux (AMD64) images are maintained at https://hub.docker.com/u/phpipam

FROM alpine:3.9

MAINTAINER Gary Allan <github@gallan.co.uk>

WORKDIR /phpipam

RUN apk add git \
    php php-cli php-pear php-pdo_mysql php-json php-session php-gmp php-gd php-sockets php-gettext php-mbstring \
    php-ctype php-ldap php-curl php-snmp php-openssl php-simplexml php-pcntl php-iconv php-opcache \
    iputils fping \
    apache2 php-apache2

# Setup apache, logging & php
RUN ln -sf /dev/stdout /var/log/apache2/access.log \
    && ln -sf /dev/stderr /var/log/apache2/error.log \
    && mkdir -p /run/apache2 \
    && sed -i 's/#LoadModule rewrite_module modules\/mod_rewrite.so/LoadModule rewrite_module modules\/mod_rewrite.so/' /etc/apache2/httpd.conf \
    && echo -e '<VirtualHost *:80>\n DocumentRoot /phpipam\n <Directory "/phpipam">\n  AllowOverride All\n  Require all granted\n </Directory>\n</VirtualHost>' >/etc/apache2/conf.d/apache.conf \
    && echo -e "max_execution_time=3600\nmemory_limit=768M\nmax_input_vars=10000\npost_max_size=32M\nopcache.revalidate_freq=60" >/etc/php7/conf.d/99_phpipam.ini

# Download phpIPAM
RUN git clone --recursive --depth 1 -b master https://github.com/phpipam/phpipam.git /phpipam \
    && cd /phpipam \
    && git config core.fileMode false \
    && ln -s /phpipam/config.docker.php /phpipam/config.php \
    && chmod -R u=rw,go=r /phpipam \
    && chmod -R a+X /phpipam \
    && find /phpipam -type d -name upload -exec chmod a=rwX {} \;

# Available environment variables
ENV IPAM_DATABASE_HOST "localhost"
ENV IPAM_DATABASE_USER "phpipam"
ENV IPAM_DATABASE_PASS "phpipamadmin"
ENV IPAM_DATABASE_NAME "phpipam"
ENV IPAM_DATABASE_PORT 3306
ENV IPAM_GMAPS_API_KEY ""

# Run Apache
EXPOSE 80
CMD /usr/sbin/httpd -DFOREGROUND
