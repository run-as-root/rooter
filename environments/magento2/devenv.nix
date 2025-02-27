{ pkgs, inputs, lib, config, ... }:

let
    rooterBin = if builtins.getEnv "ROOTER_BIN" != "" then builtins.getEnv "ROOTER_BIN" else "rooter";
    composerPhar = builtins.fetchurl{
        url = "https://github.com/composer/composer/releases/download/2.2.22/composer.phar";
        sha256 = "1lmibmdlk2rsrf4zr7xk4yi5rhlmmi8f2g8h2izb8x4sik600dbx";
    };
    magerun2Phar = builtins.fetchurl{
        url = "https://github.com/netz98/n98-magerun2/releases/download/7.5.0/n98-magerun2.phar";
        sha256 = "0xcc7hag2wbr41ikk21ayqwc1jfpn4mz5z72w5ba9zqz8pa7q9q3";
    };
in {
    dotenv.enable = true;
    env = {
        PROJECT_NAME = "${PROJECT_NAME}";
        PROJECT_HOST = "${PROJECT_HOST}";

        PC_SOCKET_PATH = "${config.devenv.runtime}/pc.sock";

        NGINX_PKG_ROOT = pkgs.nginx;
        DEVENV_STATE_NGINX = "${config.env.DEVENV_STATE}/nginx";

        DEVENV_PHPFPM_SOCKET = "${config.env.DEVENV_STATE}/php-fpm.sock";

        DEVENV_DB_NAME = "app";
        DEVENV_DB_USER = "app";
        DEVENV_DB_PASS = "app";

        DEVENV_AMQP_USER = "guest";
        DEVENV_AMQP_PASS = "guest";
    };

    # PACKAGES
    packages = [
        pkgs.git
        pkgs.gnupatch
        pkgs.curl
        pkgs.yarn
        pkgs.gettext
    ];

    scripts.composer2.exec = ''php ${composerPhar} $@''; # Composer 2.2.x required by Magento2 <=2.4.6
    scripts.magerun2.exec = ''php ${magerun2Phar} $@''; # magerun2 without hardlock to php8.2

    # process-compose
    process.manager.implementation="process-compose";

    # PHP
    languages.php = {
        enable = true;
        version= "8.3";
        extensions = [ "redis" "xdebug" "xsl" ];
        ini= ''
            memory_limit = -1
            display_errors = On
            display_startup_errors = On
            error_reporting=E_ALL
            xdebug.mode = coverage,debug
            sendmail_path = ${pkgs.mailpit}/bin/mailpit sendmail -S 127.0.0.1:${config.env.DEVENV_MAIL_SMTP_PORT}
        '';
        fpm.phpOptions =''
            memory_limit = -1
            error_reporting=E_ALL
            xdebug.mode = debug
            sendmail_path = ${pkgs.mailpit}/bin/mailpit sendmail -S 127.0.0.1:${config.env.DEVENV_MAIL_SMTP_PORT}
            display_errors = On
            display_startup_errors = On
        '';
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
        package = pkgs.nodejs_20;
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
                port = config.env.DEVENV_DB_PORT;
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

    # OpenSearch
    services.opensearch = {
        enable = true;
        settings = {
            "http.port" = lib.strings.toInt ( config.env.DEVENV_OPENSEARCH_PORT);
            "transport.port" = lib.strings.toInt ( config.env.DEVENV_OPENSEARCH_TCP_PORT );
        };
    };

    # RabbitMQ
    services.rabbitmq = {
        enable = true;
        port = lib.strings.toInt ( config.env.DEVENV_AMQP_PORT );
        managementPlugin = {
            enable = true;
            port = lib.strings.toInt ( config.env.DEVENV_AMQP_MANAGEMENT_PORT );
        };
    };
}
