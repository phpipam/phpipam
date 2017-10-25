FROM alpine:3.5

WORKDIR /phpipam

RUN apk add --no-cache php5 \
  php5-pdo php5-json php5-sockets \
  php5-openssl php5-gmp php5-ldap \
  php5-gettext php5-pcntl php5-cli \
  php5-pdo_mysql php5-mcrypt php5-pear \
  php5-ctype php5-xml \
  php5-gd php5-snmp php5-curl \
  iputils \
  apache2 php5-apache2

ADD . .

ENV IPAM_DATABASE_HOST "localhost"
ENV IPAM_DATABASE_USER "phpipam"
ENV IPAM_DATABASE_PASS "phpipamadmin"
ENV IPAM_DATABASE_NAME "phpipam"
ENV IPAM_DATABASE_PORT 3306

# Setup apache and logging for it
RUN ln -s /phpipam/apache.conf /etc/apache2/conf.d/apache.conf \
  && ln -s /phpipam/config.dist.php /phpipam/config.php \
  && ln -sf /dev/stdout /var/log/apache2/access.log \
  && ln -sf /dev/stderr /var/log/apache2/error.log \
  && mkdir -p /run/apache2 \
  && sed -i 's/#LoadModule rewrite_module modules\/mod_rewrite.so/LoadModule rewrite_module modules\/mod_rewrite.so/' /etc/apache2/httpd.conf

EXPOSE 80
CMD /usr/sbin/httpd -DFOREGROUND
