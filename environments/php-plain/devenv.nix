{ pkgs, inputs, lib, config, ... }:
let

in {
    dotenv.enable = true;
    env = {
        PROJECT_NAME = "${PROJECT_NAME}";
        PROJECT_HOST = "${PROJECT_HOST}";
    };

    # PACKAGES
    packages = [
        pkgs.git
        pkgs.gnupatch
        pkgs.curl
        pkgs.yarn
        pkgs.gettext
    ];

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
    };

    # mailpit
    services.mailpit = {
        enable = true;
        uiListenAddress   = "127.0.0.1:${config.env.DEVENV_MAIL_UI_PORT}";
        smtpListenAddress = "127.0.0.1:${config.env.DEVENV_MAIL_SMTP_PORT}";
    };
}
