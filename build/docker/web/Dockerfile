FROM "nginx:1.15.7-alpine"
MAINTAINER Zan Baldwin "hello@zanbaldwin.com"

# https://engineeringblog.yelp.com/2016/01/dumb-init-an-init-for-docker.html
RUN    apk add --no-cache --virtual .build-deps bash gcc git libc-dev make \
    && git clone git://github.com/Yelp/dumb-init.git /tmp/dumb-init \
    && (cd /tmp/dumb-init; git checkout $(git describe --tags); make) \
    && mv /tmp/dumb-init/dumb-init /sbin/dumb-init \
    && rm -rf /tmp/dumb-init \
    && apk del .build-deps \
    && chmod +x /sbin/dumb-init
ENTRYPOINT [ "/sbin/dumb-init", "--" ]

WORKDIR /srv
EXPOSE 80

RUN    rm -rf /etc/nginx/conf.d \
    && mkdir -p /etc/nginx/conf.d

COPY server.conf /etc/nginx/conf.d/server.conf
COPY upstream.conf /etc/nginx/conf.d/upstream.conf

ARG DEVELOPMENT=0
RUN if [ ${DEVELOPMENT} -ne 0 ]; then \
        sed -i -e "s@autoindex off@autoindex on@g" /etc/nginx/conf.d/server.conf; \
    fi

CMD [ "nginx", "-g", "daemon off;" ]
