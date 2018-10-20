#!/bin/sh

# Bootstrapping
php /root/bootstrap.php

# Start apache
exec apache2-foreground
