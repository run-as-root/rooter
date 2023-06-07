#!/usr/bin/env bash
# Usage: install
# Summary: Main installation of rooter
# Help:
# Manages initialisation Certificates
#  - creating root certificate
#  - signing root certificate
#  - trusting root certificate
#  - certs for rooter.test
# 
# Initialises resolver for test domain
# :Help

[[ ! ${ROOTER_DIR} ]] && >&2 echo -e "\033[31mThis script is not intended to be run directly!\033[0m" && exit 1

mkdir -p "${ROOTER_HOME_DIR}/commands/"
touch  "${ROOTER_HOME_DIR}/commands/empty.sh"

#############################################################################
## Generate ROOT CA and trust ROOT CA
#############################################################################
if [[ ! -d "${ROOTER_SSL_DIR}/rootca" ]]; then
    mkdir -p "${ROOTER_SSL_DIR}/rootca"/{certs,crl,newcerts,private}

    touch "${ROOTER_SSL_DIR}/rootca/index.txt"
    echo 1000 > "${ROOTER_SSL_DIR}/rootca/serial"
fi

# create CA root certificate if none present
if [[ ! -f "${ROOTER_SSL_DIR}/rootca/private/ca.key.pem" ]]; then
  echo "==> Generating private key for local root certificate"
  openssl genrsa -out "${ROOTER_SSL_DIR}/rootca/private/ca.key.pem" 2048
fi

if [[ ! -f "${ROOTER_SSL_DIR}/rootca/certs/ca.cert.pem" ]]; then
  echo "==> Signing root certificate 'ROOTER Proxy Local CA ($(hostname -s))'"
  openssl req -new -x509 -days 7300 -sha256 -extensions v3_ca \
    -config "${ROOTER_DIR}/etc/openssl/rootca.conf"        \
    -key "${ROOTER_SSL_DIR}/rootca/private/ca.key.pem"        \
    -out "${ROOTER_SSL_DIR}/rootca/certs/ca.cert.pem"         \
    -subj "/C=US/O=rooter.run-as-root.sh/CN=ROOTER Proxy Local CA ($(hostname -s))"
fi

## trust root ca differently on Fedora, Ubuntu and macOS
if [[ "$OSTYPE" =~ ^linux ]] \
  && [[ -d /etc/pki/ca-trust/source/anchors ]] \
  && [[ ! -f /etc/pki/ca-trust/source/anchors/rooter-proxy-local-ca.cert.pem ]] \
  ## Fedora/CentOS
then
  echo "==> Trusting root certificate (requires sudo privileges)"  
  sudo cp "${ROOTER_SSL_DIR}/rootca/certs/ca.cert.pem" /etc/pki/ca-trust/source/anchors/rooter-proxy-local-ca.cert.pem
  sudo update-ca-trust
elif [[ "$OSTYPE" =~ ^linux ]] \
  && [[ -d /usr/local/share/ca-certificates ]] \
  && [[ ! -f /usr/local/share/ca-certificates/rooter-proxy-local-ca.crt ]] \
  ## Ubuntu/Debian
then
  echo "==> Trusting root certificate (requires sudo privileges)"
  sudo cp "${ROOTER_SSL_DIR}/rootca/certs/ca.cert.pem" /usr/local/share/ca-certificates/rooter-proxy-local-ca.crt
  sudo update-ca-certificates
elif [[ "$OSTYPE" == "darwin"* ]] \
  && ! security dump-trust-settings -d | grep 'ROOTER Proxy Local CA' >/dev/null \
  ## Apple macOS
then
  echo "==> Trusting root certificate (requires sudo privileges)"
  sudo security add-trusted-cert -d -r trustRoot \
    -k /Library/Keychains/System.keychain "${ROOTER_SSL_DIR}/rootca/certs/ca.cert.pem"
fi




#############################################################################
## LOCAL RESOLVER
## configure resolver for .test domains on Mac OS only as Linux lacks support
## for BSD like per-TLD configuration as is done at /etc/resolver/test on Mac
#############################################################################
if [[ "$OSTYPE" == "darwin"* ]]; then
  if [[ ! -f /etc/resolver/test ]]; then
    echo "==> Configuring resolver for .test domains (requires sudo privileges)"
    if [[ ! -d /etc/resolver ]]; then
        sudo mkdir /etc/resolver
    fi
    echo "nameserver 127.0.0.1" | sudo tee /etc/resolver/test >/dev/null
  fi
else
  warning "Manual configuration required for Automatic DNS resolution"
fi




#############################################################################
## CERTS
#############################################################################
mkdir -p "${ROOTER_SSL_DIR}/certs"

if [[ ! -f "${ROOTER_SSL_DIR}/rootca/certs/ca.cert.pem" ]]; then
  fatal "Missing the root CA file. Please run 'install' and try again."
fi


declare ROOTER_PARAMS=()
ROOTER_PARAMS+=("rooter.test")

CERTIFICATE_SAN_LIST=
for (( i = 0; i < ${#ROOTER_PARAMS[@]} * 2; i+=2 )); do
  [[ ${CERTIFICATE_SAN_LIST} ]] && CERTIFICATE_SAN_LIST+=","
  CERTIFICATE_SAN_LIST+="DNS.$(expr $i + 1):${ROOTER_PARAMS[i/2]}"
  CERTIFICATE_SAN_LIST+=",DNS.$(expr $i + 2):*.${ROOTER_PARAMS[i/2]}"
done

CERTIFICATE_NAME="${ROOTER_PARAMS[0]}"

if [[ -f "${ROOTER_SSL_DIR}/certs/${CERTIFICATE_NAME}.key.pem" ]]; then
    >&2 echo -e "\033[33mWarning: Certificate for ${CERTIFICATE_NAME} already exists! Overwriting...\033[0m\n"
fi

echo "==> Generating private key ${CERTIFICATE_NAME}.key.pem"
openssl genrsa -out "${ROOTER_SSL_DIR}/certs/${CERTIFICATE_NAME}.key.pem" 2048

echo "==> Generating signing req ${CERTIFICATE_NAME}.crt.pem"
openssl req -new -sha256 -config <(cat                            \
    "${ROOTER_DIR}/etc/openssl/certificate.conf"               \
    <(printf "extendedKeyUsage = serverAuth,clientAuth \n         \
      subjectAltName = %s" "${CERTIFICATE_SAN_LIST}")             \
  )                                                               \
  -key "${ROOTER_SSL_DIR}/certs/${CERTIFICATE_NAME}.key.pem"      \
  -out "${ROOTER_SSL_DIR}/certs/${CERTIFICATE_NAME}.csr.pem"      \
  -subj "/C=US/O=rooter.run-as-root.sh/CN=${CERTIFICATE_NAME}"

echo "==> Generating certificate ${CERTIFICATE_NAME}.crt.pem"
openssl x509 -req -days 365 -sha256 -extensions v3_req            \
  -extfile <(cat                                                  \
    "${ROOTER_DIR}/etc/openssl/certificate.conf"               \
    <(printf "extendedKeyUsage = serverAuth,clientAuth \n         \
      subjectAltName = %s" "${CERTIFICATE_SAN_LIST}")             \
  )                                                               \
  -CA "${ROOTER_SSL_DIR}/rootca/certs/ca.cert.pem"                \
  -CAkey "${ROOTER_SSL_DIR}/rootca/private/ca.key.pem"            \
  -CAserial "${ROOTER_SSL_DIR}/rootca/serial"                     \
  -in "${ROOTER_SSL_DIR}/certs/${CERTIFICATE_NAME}.csr.pem"       \
  -out "${ROOTER_SSL_DIR}/certs/${CERTIFICATE_NAME}.crt.pem"

