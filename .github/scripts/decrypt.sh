#!/bin/sh

PROJECT_PATH=/__w/docusign-bundle/docusign-bundle
JWT_PATH=$PROJECT_PATH/features/var/jwt
SCRIPTS_PATH=$PROJECT_PATH/.github/scripts

# Decrypt the file
mkdir $JWT_PATH

# --batch to prevent interactive command --yes to assume "yes" for questions
gpg --quiet --batch --yes --decrypt --passphrase="$DOCUSIGN_RSA_PASSPHRASE" \
--output $JWT_PATH/docusign.pem $SCRIPTS_PATH/docusign.pem.gpg

chmod 644 $JWT_PATH/docusign.pem
