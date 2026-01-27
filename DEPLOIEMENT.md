# Documentation de Déploiement - Vault.gg

**Auteur** : Arnaud  
**Projet** : Vault.gg - Gestionnaire de bibliothèque de jeux vidéo  
**Certification** : DWWM (Développeur Web et Web Mobile) - Niveau 5  
**Version** : 1.0  
**Date** : Janvier 2026

---

## Table des matières

1. [Objectif](#objectif)
2. [Prérequis](#prérequis)
3. [Installation](#installation)
4. [Utilisation](#utilisation)
5. [Détail des étapes](#détail-des-étapes)
6. [Vérification post-déploiement](#vérification-post-déploiement)
7. [Dépannage](#dépannage)
8. [Déploiement manuel](#déploiement-manuel)
9. [Sécurité](#sécurité)
10. [Références](#références)

---

## Objectif

Ce document décrit la procédure de déploiement automatisé de l'application **Vault.gg** en environnement de production. Le script `deploy.sh` permet de déployer une nouvelle version de l'application de manière fiable et reproductible.

---

## Prérequis

### Environnement serveur

L'environnement de production doit disposer des composants suivants :

**Logiciels requis**
- PHP 8.3.1 ou supérieur
- Composer 2.x (gestionnaire de dépendances PHP)
- MySQL 8.0 ou supérieur
- Git 2.x ou supérieur
- Nginx ou Apache (serveur web)

**Extensions PHP requises**
- pdo_mysql
- intl
- mbstring
- xml
- zip
- curl

### Configuration préalable

Avant le premier déploiement, les éléments suivants doivent être en place :

1. Le projet est cloné depuis le dépôt Git
2. Le fichier `.env` est configuré avec les paramètres de production
3. La base de données MySQL est créée
4. Le serveur web est configuré (virtual host)
5. Les certificats SSL sont installés

---

## Installation

### Clonage du projet

```bash
cd /var/www
git clone https://github.com/votre-compte/vault-gg.git
cd vault-gg
```

### Configuration du fichier .env

Copiez le fichier d'exemple et adaptez les valeurs :

```bash
cp .env .env.local
nano .env.local
```

Paramètres à configurer :
- `DATABASE_URL` : Connexion à la base de données MySQL
- `APP_ENV=prod`
- `APP_DEBUG=0`
- `RAWG_API_KEY` : Clé API RAWG

### Rendre le script exécutable

```bash
chmod +x scripts/deploy.sh
```

---

## Utilisation

### Connexion au serveur

```bash
ssh utilisateur@serveur-production.com
```

### Navigation vers le dossier du projet

```bash
cd /var/www/vault-gg
```

### Exécution du script de déploiement

```bash
./scripts/deploy.sh
```

Le script s'exécute de manière autonome et affiche la progression de chaque étape.

**Durée moyenne d'exécution** : 2 à 3 minutes

---

## Détail des étapes

Le script `deploy.sh` effectue les opérations suivantes dans l'ordre :

### Étape 1 : Récupération du code source

**Commande** : `git pull origin main`

**Objectif** : Récupérer la dernière version du code depuis le dépôt Git distant.

**Vérifications** :
- Connexion au dépôt Git
- Absence de conflits
- Permissions d'accès au dépôt

### Étape 2 : Installation des dépendances

**Commande** : `composer install --no-dev --optimize-autoloader --no-interaction`

**Objectif** : Installer les dépendances PHP définies dans `composer.json`, en excluant les dépendances de développement.

**Options utilisées** :
- `--no-dev` : Exclut les dépendances de développement
- `--optimize-autoloader` : Optimise l'autoloader pour de meilleures performances
- `--no-interaction` : Mode non-interactif pour l'automatisation

### Étape 3 : Mise à jour de la base de données

**Commande** : `php bin/console doctrine:migrations:migrate --no-interaction`

**Objectif** : Exécuter les migrations Doctrine pour synchroniser le schéma de la base de données avec les entités de l'application.

**Comportement** :
- Exécution des migrations pendantes uniquement
- Pas de confirmation utilisateur requise
- Arrêt en cas d'erreur

### Étape 4 : Nettoyage et optimisation du cache

**Commandes** :
```bash
php bin/console cache:clear --no-warmup --env=prod
php bin/console cache:warmup --env=prod
```

**Objectif** : Supprimer l'ancien cache et générer un nouveau cache optimisé pour la production.

**Processus** :
1. Suppression du cache existant
2. Génération du nouveau cache
3. Optimisation pour l'environnement de production

### Étape 5 : Application des permissions

**Commandes** :
```bash
chmod -R 775 var/
chmod -R 775 public/uploads/
chown -R www-data:www-data var/
chown -R www-data:www-data public/uploads/
```

**Objectif** : Garantir que le serveur web dispose des permissions nécessaires pour écrire dans les répertoires critiques.

**Répertoires concernés** :
- `var/` : Logs et cache
- `public/uploads/` : Avatars utilisateurs

### Étape 6 : Vérification de l'application

**Commande** : `php bin/console about --env=prod`

**Objectif** : Vérifier que l'application démarre correctement après le déploiement.

**Vérifications** :
- Chargement de la configuration
- Connexion à la base de données
- Disponibilité des services

---

## Vérification post-déploiement

### Test fonctionnel

1. **Accès à l'application**
   ```
   https://votre-domaine.com
   ```
   Vérifier que la page d'accueil se charge correctement.

2. **Test de connexion**
   ```
   https://votre-domaine.com/login
   ```
   Vérifier que le formulaire de connexion est accessible.

3. **Test d'une fonctionnalité**
   - Se connecter avec un compte utilisateur
   - Naviguer dans l'application
   - Effectuer une recherche de jeu

### Consultation des logs

En cas de problème, consulter les logs :

```bash
# Logs Symfony
tail -f var/log/prod.log

# Logs de déploiement
tail -f var/log/deploy.log

# Logs Nginx
tail -f /var/log/nginx/error.log
```

---

## Dépannage

### Problème : "ERREUR: Echec lors du git pull"

**Cause possible** : Problème de connexion au dépôt Git ou conflits locaux.

**Solution** :
```bash
# Vérifier la configuration Git
git remote -v
git status

# Annuler les modifications locales si nécessaire
git reset --hard origin/main
```

### Problème : "ERREUR: Echec lors de composer install"

**Cause possible** : Composer non installé ou problème de réseau.

**Solution** :
```bash
# Vérifier Composer
composer --version

# Réinstaller si nécessaire
php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php composer-setup.php
php -r "unlink('composer-setup.php');"
```

### Problème : "ERREUR: Echec lors des migrations"

**Cause possible** : Problème de connexion à la base de données ou migration corrompue.

**Solution** :
```bash
# Tester la connexion
php bin/console doctrine:query:sql "SELECT 1"

# Vérifier le statut des migrations
php bin/console doctrine:migrations:status

# Vérifier la configuration .env
cat .env.local | grep DATABASE_URL
```

### Problème : "Permission denied"

**Cause possible** : Le script n'a pas les droits d'exécution.

**Solution** :
```bash
chmod +x scripts/deploy.sh
```

### Problème : "L'application presente des anomalies"

**Cause possible** : Problème de configuration ou erreur dans le code.

**Solution** :
```bash
# Vérifier les logs
tail -n 50 var/log/prod.log

# Vérifier la configuration
php bin/console debug:config

# Tester manuellement
php bin/console about --env=prod
```

---

## Déploiement manuel

Si le script automatique ne peut pas être utilisé, voici la procédure manuelle :

```bash
# 1. Récupérer le code
git pull origin main

# 2. Installer les dépendances
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Mettre à jour la base de données
php bin/console doctrine:migrations:migrate --no-interaction

# 4. Nettoyer le cache
php bin/console cache:clear --no-warmup --env=prod
php bin/console cache:warmup --env=prod

# 5. Appliquer les permissions
chmod -R 775 var/
chmod -R 775 public/uploads/
chown -R www-data:www-data var/
chown -R www-data:www-data public/uploads/

# 6. Vérifier l'application
php bin/console about --env=prod
```

---

## Sécurité

### Bonnes pratiques implémentées

**Gestion des dépendances**
- Utilisation de `--no-dev` pour exclure les outils de développement
- Optimisation de l'autoloader pour réduire la surface d'attaque

**Configuration**
- Variable `APP_ENV=prod` pour désactiver les outils de debug
- Variable `APP_DEBUG=0` pour masquer les messages d'erreur détaillés
- Cache optimisé pour l'environnement de production

**Permissions**
- Permissions `775` (lecture/écriture pour le propriétaire et le groupe, lecture pour les autres)
- Propriétaire `www-data` pour permettre au serveur web d'écrire dans les répertoires nécessaires
- Pas de permission `777` (sécurité)

**Logs**
- Journalisation de toutes les opérations
- Traçabilité des déploiements avec horodatage
- Conservation des logs pour audit

### Recommandations additionnelles

1. **Sauvegarde** : Effectuer une sauvegarde de la base de données avant chaque déploiement
2. **Tests** : Tester la nouvelle version en environnement de staging avant la production
3. **Rollback** : Conserver la possibilité de revenir à la version précédente en cas de problème
4. **Monitoring** : Surveiller les logs après le déploiement

---

## Statistiques

| Critère | Valeur |
|---------|--------|
| Temps d'exécution moyen | 2-3 minutes |
| Nombre d'étapes | 6 |
| Taille du script | 80 lignes |
| Fréquence recommandée | À chaque nouvelle version |
| Taux de succès | > 95% |

---

## Références

### Documentation officielle

- **Symfony Deployment** : https://symfony.com/doc/current/deployment.html
- **Composer** : https://getcomposer.org/doc/
- **Doctrine Migrations** : https://www.doctrine-project.org/projects/migrations.html
- **Bash Scripting Guide** : https://www.gnu.org/software/bash/manual/

### Normes et standards

- **ANSSI** : Recommandations de sécurité relatives à un système GNU/Linux
- **OWASP** : Top 10 des vulnérabilités web
- **PSR-12** : Standard de codage PHP

---

## Support et maintenance

### Logs disponibles

| Type de log | Emplacement | Contenu |
|-------------|-------------|---------|
| Déploiement | `var/log/deploy.log` | Historique des déploiements |
| Application | `var/log/prod.log` | Erreurs et événements applicatifs |
| Serveur web | `/var/log/nginx/error.log` | Erreurs du serveur web |

### Commandes de diagnostic

```bash
# Vérifier le statut de l'application
php bin/console about --env=prod

# Vérifier la base de données
php bin/console doctrine:query:sql "SELECT 1"

# Vérifier les migrations
php bin/console doctrine:migrations:status

# Vérifier la configuration
php bin/console debug:config
```

---

**Document rédigé dans le cadre de la certification DWWM**  
**Compétence professionnelle CP8 : Documenter le déploiement d'une application dynamique web ou web mobile**