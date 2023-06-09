{ pkgs, inputs, lib, config, ... }:

let
    user = builtins.getEnv "USER";
    # variables from .env are not available in config.env atm
    httpPort = builtins.getEnv "DEVENV_HTTP_PORT";
    mailhogSmtpPort = builtins.getEnv "DEVENV_MAILHOG_SMTP_PORT";
    mailhogUiPort = builtins.getEnv "DEVENV_MAILHOG_UI_PORT";
    mysqlPort = builtins.getEnv "DEVENV_DB_PORT";
    redisPort = lib.strings.toInt ( builtins.getEnv "DEVENV_REDIS_PORT" );
    redisSocket = "tcp://127.0.0.1:" + builtins.getEnv "DEVENV_REDIS_PORT" ;
    elasticsearchPort = builtins.getEnv "DEVENV_ELASTICSEARCH_PORT";
    rooterBin = builtins.getEnv "ROOTER_BIN";
in {
    env = {
        PROJECT_NAME = "shopware-tmp";
        PROJECT_HOST = "shopware-tmp.rooter.test";

        DEVENV_DB_NAME = "app";# shopware
        DEVENV_DB_USER = "app";# shopware
        DEVENV_DB_PASS = "app";# shopware

        DEVENV_AMQP_USER = user;
        DEVENV_AMQP_PASS = "guest";

        # Shopware env variables
        APP_URL="http://127.0.0.1:${httpPort}";
        STOREFRONT_PROXY_URL = "http://${config.env.PROJECT_HOST}";
        MAILER_DSN = lib.mkDefault "smtp://127.0.0.1:${mailhogSmtpPort}";
        DATABASE_URL = lib.mkDefault "mysql://${config.env.DEVENV_DB_USER}:${config.env.DEVENV_DB_PASS}@127.0.0.1:${mysqlPort}/${config.env.DEVENV_DB_NAME}";
        OPENSEARCH_URL="http://127.0.0.1:${elasticsearchPort}";
    };

    # PACKAGES
    packages = [
        pkgs.git
        pkgs.jq
        pkgs.gnupatch
        pkgs.curl
        pkgs.yarn
        pkgs.gettext
    ];

    # Shell welcome message
    enterShell = ''
        ${rooterBin} info
    '';

    # PHP
    languages.php = {
        enable = true;
        package = inputs.phps.packages.${builtins.currentSystem}.php82.buildEnv {
            extensions = { all, enabled }: with all; enabled ++ [ redis xdebug xsl ];
            extraConfig = ''
              memory_limit = -1
              xdebug.mode = coverage,debug
              sendmail_path = ${pkgs.mailhog}/bin/Mailhog sendmail --smtp-addr 127.0.0.1:${mailhogSmtpPort}

              realpath_cache_ttl = 3600
              session.gc_probability = 0
              session.save_handler = redis
              session.save_path = "${redisSocket}/0"
              display_errors = On
              error_reporting = E_ALL
              assert.active = 0
              opcache.memory_consumption = 256M
              opcache.interned_strings_buffer = 20
              zend.assertions = 0
              short_open_tag = 0
              zend.detect_unicode = 0
              realpath_cache_ttl = 3600
            '';
        };
        fpm.pools.web = {
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
    };

    services.caddy = {
        enable = lib.mkDefault true;
        virtualHosts.":${httpPort}" = lib.mkDefault {
            extraConfig = ''
                root * public
                php_fastcgi unix/${config.languages.php.fpm.pools.web.socket}
                encode zstd gzip
                file_server
                log {
                  output stderr
                  format console
                  level ERROR
                }
            '';
        };
    };


    # DATABASE
    services.mysql = {
        enable = true;
        package = pkgs.mariadb_104;
        settings = {
            mysqld = {
                port = "${mysqlPort}";
                log_bin_trust_function_creators = 1;
            };
        };
        initialDatabases = [{ name = "${config.env.DEVENV_DB_NAME}"; }];
        ensureUsers = [
            {
                name = "${config.env.DEVENV_DB_USER}";
                password = "${config.env.DEVENV_DB_PASS}";
                ensurePermissions = {
                    "${config.env.DEVENV_DB_NAME}.*" = "ALL PRIVILEGES";
                    "${config.env.DEVENV_DB_NAME}_test.*" = "ALL PRIVILEGES";
                };
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

    # Shopware 6 related scripts
    scripts.build-js.exec = lib.mkDefault "bin/build-js.sh";
    scripts.build-storefront.exec = lib.mkDefault "bin/build-storefront.sh";
    scripts.watch-storefront.exec = lib.mkDefault "bin/watch-storefront.sh";
    scripts.build-administration.exec = lib.mkDefault "bin/build-administration.sh";
    scripts.watch-administration.exec = lib.mkDefault "bin/watch-administration.sh";
    scripts.theme-refresh.exec = lib.mkDefault "bin/console theme-refresh";
    scripts.theme-compile.exec = lib.mkDefault "bin/console theme-compile";

    # Symfony related scripts
    scripts.cc.exec = lib.mkDefault "bin/console cache:clear";
}
