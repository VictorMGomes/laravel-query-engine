FROM php:8.5-cli

ARG UID=1000
ARG GID=1000
ARG USERNAME=app

SHELL ["/bin/bash", "-o", "pipefail", "-c"]

# hadolint ignore=DL3008
RUN apt-get update && apt-get install -y --no-install-recommends \
        unzip \
        libzip-dev \
        git \
        curl \
        gnupg \
        nodejs \
        npm \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Install Docker CLI and Sudo (for Docker-out-of-Docker)
# hadolint ignore=DL3008
RUN install -m 0755 -d /etc/apt/keyrings \
    && curl -fsSL https://download.docker.com/linux/debian/gpg -o /etc/apt/keyrings/docker.asc \
    && chmod a+r /etc/apt/keyrings/docker.asc \
    && echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/debian $(sed -n 's/VERSION_CODENAME=//p' /etc/os-release) stable" | tee /etc/apt/sources.list.d/docker.list > /dev/null \
    && apt-get update \
    && apt-get install -y --no-install-recommends docker-ce-cli docker-compose-plugin sudo \
    && rm -rf /var/lib/apt/lists/*

# Install Nektos Act
RUN curl -s https://raw.githubusercontent.com/nektos/act/master/install.sh | bash -s -- -b /usr/local/bin

RUN groupadd -g ${GID} ${USERNAME} \
    && useradd -l -u ${UID} -g ${GID} -m -s /bin/bash ${USERNAME} \
    && echo "${USERNAME} ALL=(ALL) NOPASSWD: /usr/local/bin/act, /usr/bin/docker" >> /etc/sudoers

# Configure Act default image so it doesn't ask interactively for both dev and root
RUN mkdir -p /home/${USERNAME}/.config/act /root/.config/act \
    && echo "-P ubuntu-latest=catthehacker/ubuntu:act-latest\n-P ubuntu-22.04=catthehacker/ubuntu:act-22.04\n-P ubuntu-20.04=catthehacker/ubuntu:act-20.04" > /home/${USERNAME}/.actrc \
    && cp /home/${USERNAME}/.actrc /root/.actrc \
    && cp /home/${USERNAME}/.actrc /root/.config/act/actrc \
    && chown -R ${UID}:${GID} /home/${USERNAME}/.config /home/${USERNAME}/.actrc

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

RUN curl -fsSL https://github.com/php/pie/releases/latest/download/pie.phar \
    -o /usr/local/bin/pie \
    && chmod +x /usr/local/bin/pie

USER root

RUN pie install pecl/pcov

USER ${USERNAME}

WORKDIR /app
