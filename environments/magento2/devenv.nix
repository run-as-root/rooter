{ pkgs, inputs, lib, config, ... }:

let
    user = builtins.getEnv "USER";
    # variables from .env are not available in config.env atm
    mailhogSmtpPort = builtins.getEnv "DEVENV_MAILHOG_SMTP_PORT";
    mailhogUiPort = builtins.getEnv "DEVENV_MAILHOG_UI_PORT";
    mysqlPort = builtins.getEnv "DEVENV_DB_PORT";
    redisPort = lib.strings.toInt ( builtins.getEnv "DEVENV_REDIS_PORT" );
    amqpPort = lib.strings.toInt ( builtins.getEnv "DEVENV_AMQP_PORT" );
    amqpManagementPort = lib.strings.toInt ( builtins.getEnv "DEVENV_AMQP_MANAGEMENT_PORT" );
    elasticsearchPort = lib.strings.toInt ( builtins.getEnv "DEVENV_ELASTICSEARCH_PORT" );
    rooterBin = builtins.getEnv "ROOTER_BIN";
in {
    env = {
        PROJECT_NAME = "run-as-root-shop";
        PROJECT_HOST = "run-as-root-shop.rooter.test";

        NGINX_PKG_ROOT = pkgs.nginx;
        DEVENV_STATE_NGINX = "${config.env.DEVENV_STATE}/nginx";

        DEVENV_PHPFPM_SOCKET = "${config.env.DEVENV_STATE}/php-fpm.sock";

        DEVENV_DB_NAME = "app";
        DEVENV_DB_USER = "app";
        DEVENV_DB_PASS = "app";

        DEVENV_AMQP_USER = lib.mkDefault user;
        DEVENV_AMQP_PASS = "guest";
    };

    # PACKAGES
    packages = [
        pkgs.git
        pkgs.gnupatch
        pkgs.curl
        pkgs.yarn
        pkgs.gettext
        pkgs.n98-magerun2
    ];

    # Shell welcome message
    enterShell = ''
        ${rooterBin} info
        ${rooterBin} magento2:nginx-init
    '';

    # PHP
    languages.php = {
        enable = true;
        package = inputs.phps.packages.${builtins.currentSystem}.php81.buildEnv {
            extensions = { all, enabled }: with all; enabled ++ [ redis xdebug xsl ];
            extraConfig = ''
              memory_limit = -1
              xdebug.mode = coverage,debug
              sendmail_path = ${pkgs.mailhog}/bin/Mailhog sendmail --smtp-addr 127.0.0.1:${mailhogSmtpPort}
            '';
        };
        fpm.pools.web = {
            listen = "${config.env.DEVENV_PHPFPM_SOCKET}";
            settings = {
                "clear_env" = "no";
                "pm" = "dynamic";
                "pm.max_children" = 20;
                "pm.start_servers" = 6;
                "pm.min_spare_servers" = 1;
                "pm.max_spare_servers" = 10;
            };
        };
    };

    # JS
    languages.javascript = {
        enable = true;
        package = pkgs.nodejs-16_x;
    };

    # nginx
    services.nginx = {
        enable = true;
        configFile = "${config.env.DEVENV_STATE_NGINX}/nginx.conf";
    };

    # DATABASE
    services.mysql = {
        enable = true;
        package = pkgs.mariadb_104;
        settings = {
            mysqld = {
                port = "${mysqlPort}";
                innodb_buffer_pool_size = "2G";
                table_open_cache = "2048";
                sort_buffer_size = "8M";
                join_buffer_size = "8M";
                query_cache_size = "256M";
                query_cache_limit = "2M";
            };
        };
        initialDatabases = [{ name = "${config.env.DEVENV_DB_NAME}"; }];
        ensureUsers = [
            {
                name = "${config.env.DEVENV_DB_USER}";
                password = "${config.env.DEVENV_DB_PASS}";
                ensurePermissions = { "${config.env.DEVENV_DB_NAME}.*" = "ALL PRIVILEGES"; };
            }
        ];
    };

    # Mailhog
    services.mailhog = {
        enable = true;
        uiListenAddress   = "127.0.0.1:${mailhogUiPort}";
        apiListenAddress  = "127.0.0.1:${mailhogUiPort}";
        smtpListenAddress = "127.0.0.1:${mailhogSmtpPort}";
    };

    # Redis
    services.redis = {
        enable = true;
        port = redisPort;
    };

    # ElasticSearch
    services.elasticsearch = {
        enable = true;
        port = elasticsearchPort;
    };

    # RabbitMQ
    services.rabbitmq = {
        enable = true;
        port = amqpPort;
        managementPlugin = {
            enable = true;
            port = amqpManagementPort;
        };
    };
}
