#!/usr/bin/env bash

# To use an OpenStack cloud you need to authenticate against the Identity
# service named keystone, which returns a **Token** and **Service Catalog**.
# The catalog contains the endpoints for all services the user/tenant has
# access to - such as Compute, Image Service, Identity, Object Storage, Block
# Storage, and Networking (code-named nova, glance, keystone, swift,
# cinder, and neutron).
export OS_AUTH_URL="https://identity.api.ams.fuga.cloud:443/v3"

# In addition to the owning entity (tenant), OpenStack stores the entity
# performing the action as the **user**.
export OS_APPLICATION_CREDENTIAL_ID="9607764f925e4e56ae7ff8800969cda3"

# With Keystone you pass the keystone application_credential_secret.
echo "Enter application_credential_secret with ID $OS_APPLICATION_CREDENTIAL_ID: "
read -sr OS_APPLICATION_CREDENTIAL_SECRET_INPUT
export OS_APPLICATION_CREDENTIAL_SECRET=$OS_APPLICATION_CREDENTIAL_SECRET_INPUT

# If your configuration has multiple regions, we set that information here.
# OS_REGION_NAME is optional and only valid in certain environments.
export OS_REGION_NAME="ams"
# Don't leave a blank variable, unset it if it was empty
if [ -z "$OS_REGION_NAME" ]; then unset OS_REGION_NAME; fi

export OS_AUTH_TYPE="v3applicationcredential"
export OS_IDENTITY_API_VERSION=3