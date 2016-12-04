FROM alpine:3.3

WORKDIR /phpipam

RUN apk add --no-cache php \
  php-pdo php-json php-sockets \
  php-openssl php-gmp php-ldap \
  php-gettext php-pcntl php-cli \
  php-pdo_mysql php-mcrypt php-pear \
  php-ctype php-xml apache2 php-apache2

ADD . .

ENV IPAM_HOST "localhost"
ENV IPAM_USER "phpipam"
ENV IPAM_PASS "phpipamadmin"
ENV IPAM_NAME "phpipam"
ENV IPAM_PORT 3306

# Setup apache and logging for it
RUN ln -s /phpipam/apache.conf /etc/apache2/conf.d/apache.conf \
  && ln -s /phpipam/config.dist.php /phpipam/config.php \
  && ln -sf /dev/stdout /var/log/apache2/access.log \
  && ln -sf /dev/stderr /var/log/apache2/error.log \
  && mkdir -p /run/apache2 \
  && sed -i 's/#LoadModule rewrite_module modules\/mod_rewrite.so/LoadModule rewrite_module modules\/mod_rewrite.so/' /etc/apache2/httpd.conf

EXPOSE 80 443
CMD /usr/sbin/httpd -DFOREGROUND
