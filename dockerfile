# Utiliser une image PHP avec Apache
FROM php:8.2.28-apache-bullseye

# Copier les fichiers du projet dans le répertoire de travail du conteneur
COPY . /var/www/html/

# Donner les permissions nécessaires
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Activer le module Apache pour PHP
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Exposer le port 80 pour le service web
EXPOSE 80

# Commande par défaut pour démarrer Apache
CMD ["apache2-foreground"]