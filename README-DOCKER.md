# 🐳 Docker - Vault.gg

## Prérequis
- Docker Desktop installé
- Docker Compose installé

## Structure Docker
- **PHP 8.3.1** avec Symfony 7.4
- **MySQL 8.0** (port 3307)
- **PhpMyAdmin** (port 8081)
- **MongoDB 7.0** (port 27017) - pour système de reviews
- **Mongo Express** (port 8082) - interface MongoDB
- **Nginx** (port 8080)

## Lancement de l'application

### Première utilisation
```bash
# Construire et démarrer les conteneurs
docker-compose up -d --build

# Vérifier que tout tourne
docker-compose ps

# Accéder au conteneur PHP
docker-compose exec php bash

# Dans le conteneur, vérifier la connexion MySQL
php bin/console doctrine:database:create --if-not-exists

# Lancer les migrations
php bin/console doctrine:migrations:migrate --no-interaction

# Quitter le conteneur
exit
```

### Utilisation quotidienne
```bash
# Démarrer les conteneurs
docker-compose up -d

# Arrêter les conteneurs
docker-compose down

# Voir les logs
docker-compose logs -f

# Redémarrer un service
docker-compose restart php
```

## Accès aux services

- 🎮 **Vault.gg** : http://localhost:8080
- 🗄️ **PhpMyAdmin** : http://localhost:8081
  - Serveur : `mysql`
  - Utilisateur : `root`
  - Mot de passe : `root`
- 🍃 **Mongo Express** : http://localhost:8082
  - Utilisateur : `admin`
  - Mot de passe : `pass`

## Commandes utiles
```bash
# Accéder au conteneur PHP
docker-compose exec php bash

# Voir les conteneurs en cours
docker-compose ps

# Reconstruire un conteneur
docker-compose up -d --build php

# Supprimer tous les conteneurs et volumes
docker-compose down -v

# Voir les logs d'un service
docker-compose logs -f php
docker-compose logs -f nginx
docker-compose logs -f mysql

# Vider le cache Symfony
docker-compose exec php php bin/console cache:clear

# Créer un contrôleur
docker-compose exec php php bin/console make:controller
```

## Base de données

### MySQL
- **Host depuis container** : `mysql`
- **Host depuis machine** : `localhost:3307`
- **Database** : `vault_gg`
- **User** : `root`
- **Password** : `root`

### MongoDB (pour plus tard)
- **Host depuis container** : `mongodb`
- **Host depuis machine** : `localhost:27017`
- **Database** : `vault_gg_reviews`
- **User** : `root`
- **Password** : `root`

## Troubleshooting

### Les conteneurs ne démarrent pas
```bash
docker-compose down -v
docker-compose up -d --build
```

### Problème de permissions
```bash
docker-compose exec php chown -R www-data:www-data /var/www/html/var
docker-compose exec php chown -R www-data:www-data /var/www/html/public/uploads
```

### Voir les erreurs
```bash
docker-compose logs -f php
docker-compose logs -f nginx
```