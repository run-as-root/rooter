{ pkgs, inputs, lib, config, ... }:
let

in {
    dotenv.enable = true;
    env = {
        PROJECT_NAME = "${PROJECT_NAME}";
        PROJECT_HOST = "${PROJECT_HOST}";

        PC_SOCKET_PATH = "${config.devenv.runtime}/pc.sock";
    };

    # PACKAGES
    packages = [
        pkgs.git
        pkgs.gnupatch
        pkgs.curl
        pkgs.yarn
        pkgs.gettext
    ];

    # process-compose
    process.manager.implementation="process-compose";

    # JS
    languages.javascript = {
        enable = true;
        package = pkgs.nodejs_20;
    };

    # mailpit
    services.mailpit = {
        enable = true;
        uiListenAddress   = "127.0.0.1:${config.env.DEVENV_MAIL_UI_PORT}";
        smtpListenAddress = "127.0.0.1:${config.env.DEVENV_MAIL_SMTP_PORT}";
    };
}