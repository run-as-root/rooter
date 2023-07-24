# ROOTER - devenv.sh templates

## OpenSearch Magento > 2.4.6 required (Elastic v8 = OpenSearch v2)

```nix
    services.opensearch = {
        enable = true;
        settings = {
            "http.port" = elasticsearchPort;
            "transport.port" = 9300;
        };
    };
```
