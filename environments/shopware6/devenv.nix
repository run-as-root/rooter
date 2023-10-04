{ pkgs, inputs, lib, config, ... }:

let
    rooterBin = if builtins.getEnv "ROOTER_BIN" != "" then builtins.getEnv "ROOTER_BIN" else "rooter";
in {
    dotenv.enable = true;
    env = {
        PROJECT_NAME = "${PROJECT_NAME}";
        PROJECT_HOST = "${PROJECT_HOST}";

        NGINX_PKG_ROOT = pkgs.nginx;
        DEVENV_STATE_NGINX = "${config.env.DEVENV_STATE}/nginx";

        DEVENV_PHPFPM_SOCKET = "${config.env.DEVENV_STATE}/php-fpm.sock";

        DEVENV_DB_NAME = "app";# shopware
        DEVENV_DB_USER = "app";# shopware
        DEVENV_DB_PASS = "app";# shopware

        DEVENV_AMQP_USER = builtins.getEnv "USER";
        DEVENV_AMQP_PASS = "guest";

        # Shopware env variables
        APP_URL="http://127.0.0.1:${config.env.DEVENV_HTTP_PORT}";
        STOREFRONT_PROXY_URL = "http://${config.env.PROJECT_HOST}";
        MAILER_DSN = lib.mkDefault "smtp://127.0.0.1:${config.env.DEVENV_MAIL_SMTP_PORT}";
        DATABASE_URL = lib.mkDefault "mysql://${config.env.DEVENV_DB_USER}:${config.env.DEVENV_DB_PASS}@127.0.0.1:${config.env.DEVENV_DB_PORT}/${config.env.DEVENV_DB_NAME}";
        OPENSEARCH_URL="http://127.0.0.1:${config.env.DEVENV_ELASTICSEARCH_PORT}";
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

    process.implementation="process-compose";
    process.process-compose={
        "port" = config.env.DEVENV_PROCESS_COMPOSE_PORT;
        "tui" = "false";
        "version" = "0.5";
    };

    # PHP
    languages.php = {
        enable = true;
        package = inputs.phps.packages.${builtins.currentSystem}.php82.buildEnv {
            extensions = { all, enabled }: with all; enabled ++ [ redis xdebug xsl ];
            extraConfig = ''
              memory_limit = -1
              display_errors = On
              display_startup_errors = On
              error_reporting=E_ALL
              xdebug.mode = coverage,debug
              sendmail_path = ${pkgs.mailpit}/bin/mailpit sendmail -S 127.0.0.1:${config.env.DEVENV_MAIL_SMTP_PORT}

              realpath_cache_ttl = 3600
              session.gc_probability = 0
              session.save_handler = redis
              session.save_path = "tcp://127.0.0.1:${config.env.DEVENV_REDIS_PORT}/0"
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
    };

    # nginx
    services.nginx = {
        enable = true;
        configFile = "${config.env.DEVENV_STATE_NGINX}/nginx.conf";
    };

    # DATABASE
    services.mysql = {
        enable = true;
        package = pkgs.mariadb_106;
        settings = {
            mysqld = {
                port = builtins.getEnv "DEVENV_DB_PORT"; # direct access to config.env is not working
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

    # mailpit
    services.mailpit = {
        enable = true;
        uiListenAddress   = "127.0.0.1:${config.env.DEVENV_MAIL_UI_PORT}";
        smtpListenAddress = "127.0.0.1:${config.env.DEVENV_MAIL_SMTP_PORT}";
    };

    # Redis
    services.redis = {
        enable = true;
        port = lib.strings.toInt ( config.env.DEVENV_REDIS_PORT );
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
