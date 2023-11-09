# ROOTER - configuration

## Domains and Subdomains

By default, rooter will use the project name as the domain.

The following subdomains are available by default if the project is named foobar: `*.foobar.rooter.test`

Using `DEVENV_HTTP_SUBDOMAINS` you can define a list of subdomains that should be used for the project.  
Add the following line to your .env file and adjust the subdomains to your needs.

```dotenv
DEVENV_HTTP_SUBDOMAINS=my-project,de-project
```

This will result in the following domains being available for the project:

- `my-project.rooter.test`
- `de-project.rooter.test`

## Custom nginx config

rooter supports a per environment nginx config, different from the default one from the environment templates.  
To use it, copy the nginx directory from the [environment templates](/environments) to `PROJECT_ROOT/.rooter/nginx` or
any other directory you prefer.  
Make sure you copy and keep all the files from the nxginx directory. Then customize the nginx config to your needs.  
Finally add the following to your `.env` file, to point rooter to your custom nginx config:

```dotenv
DEVENV_CONFIG_NGINX=.rooter/nginx
```

After you have done that, stop and start the environment again.

## Custom TLD

First Generate certificates for `your-domain-name.test`

```bash
rooter certs:generate your-domain-name.test
```

Copy the file [templates/nginx/tld/nginx-template.conf](`/templates/nginx/tld/nginx-template.conf`)
to `PROJECT_ROOT/.rooter/nginx/nginx-template.conf`.

Open the file and replace all occurrences of `${PROJECT_TLD}` with `your-domain-name.test`.

Finally, add the following to your `.env` file

```dotenv
PROJECT_TLD=your-domain-name.test
DEVENV_CONFIG_NGINX=.rooter/nginx
```

That's it. You can now start rooter and it will use your custom TLD.   
``bash
rooter env:start
``
Traefik config will be automatically registered. So you can open the traefik dashboard and check.  
You can also verify `PROJECT_ROOT/.devenv/state/nginx/nginx.conf`, it should now contain your TLD.
