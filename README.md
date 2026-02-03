# Vault.gg - Gestionnaire de Bibliothèque de Jeux Vidéo

**Vault.gg** est une application web complète permettant aux joueurs de gérer leur bibliothèque de jeux vidéo personnelle. L'application offre un suivi détaillé des jeux (backlog, en cours, terminés), un système de favoris, et des statistiques avancées.

## Table des matières

- [À propos du projet](#à-propos-du-projet)
- [Fonctionnalités](#fonctionnalités)
- [Technologies utilisées](#technologies-utilisées)
- [Prérequis](#prérequis)
- [Installation](#installation)
- [Configuration](#configuration)
- [Utilisation](#utilisation)
- [Tests](#tests)
- [Screenshots](#screenshots)
- [Architecture](#architecture)
- [Sécurité](#sécurité)
- [Conformité DWWM](#conformité-dwwm)
- [Auteur](#auteur)
- [Licence](#licence)

---

## À propos du projet

Vault.gg est développé dans le cadre de la certification **Titre Professionnel Développeur Web et Web Mobile (DWWM)** niveau 5.

### Objectifs du projet

- Permettre aux joueurs de centraliser leur bibliothèque de jeux vidéo
- Suivre l'avancement de chaque jeu (a commencer, en cours, terminé)
- Découvrir de nouveaux jeux via l'intégration de l'API RAWG
- Visualiser des statistiques détaillées sur sa collection
- Gérer ses favoris et organiser sa blibliothèque

## Fonctionnalités

### Authentification et Sécurité
- Inscription et connexion sécurisées
- Hashage des mots de passe (bcrypt)
- Protection CSRF
- Gestion des rôles utilisateurs (USER, ADMIN)
- Session "Remember Me"

### Gestion de Bibliothèque
- Recherche de jeux via l'API RAWG
- Ajout de jeux à sa bibliothèque personnelle
- Gestion des statuts : A commencer, En cours, Terminé
- Système de favoris
- Filtrage par statut et par genre
- Suppression de jeux

### Profil Utilisateur
- Avatar personnalisable
- Statistiques personnelles
- Historique des jeux ajoutés
- Taux de complétion
- Progression par statut

### Statistiques Globales
- Nombre total de jeux
- Répartition par statut
- Top 5 genres préférés
- Top 3 jeux les mieux notés
- Graphiques d'évolution (6 derniers mois)
- Taux de complétion global

### Administration (ROLE_ADMIN)
- Dashboard administrateur
- Gestion des utilisateurs
- Statistiques globales
- Top 10 jeux les plus populaires

### Système de Reviews
- Notation des jeux
- Commentaires personnels
- Stockage MongoDB

---

## Technologies utilisées

### Back-end
- **PHP** 8.3.1
- **Symfony** 7.4 LTS
- **Doctrine ORM** (MySQL)
- **Twig** (moteur de templates)
- **PHPUnit** 12.5.1 (tests)

### Front-end
- **HTML5 / CSS3**
- **Tailwind CSS** (via CDN)
- **JavaScript** (Vanilla)
- **Lucide Icons**

### Base de données
- **MySQL** 8.0 (données principales)
- **MongoDB** 7.0 (système de reviews - à venir)

### API externe
- **RAWG API** (recherche et informations sur les jeux)

### DevOps
- **Docker** & **Docker Compose**
- **Nginx** (serveur web)
- **PhpMyAdmin** (gestion MySQL)
- **Mongo Express** (gestion MongoDB)

### Outils de développement
- **Git** (versionning)
- **Composer** (dépendances PHP)
- **Symfony CLI**

---

## Prérequis

- **Docker Desktop** installé et démarré
- **Docker Compose** installé
- **Git** (optionnel, pour le clonage)
- **Ports disponibles** : 8080, 8081, 8082, 3307, 27017

---

## Installation

### 1. Cloner le projet

```bash
git clone https://github.com/votre-username/vault-gg.git
cd vault-gg
```

### 2. Copier les variables d'environnement

```bash
cp .env.docker .env
```

### 3. Construire et démarrer les conteneurs Docker

```bash
docker-compose up -d --build
```

### 4. Installer les dépendances PHP

```bash
docker-compose exec php composer install
```

### 5. Créer la base de données

```bash
docker-compose exec php php bin/console doctrine:database:create
docker-compose exec php php bin/console doctrine:migrations:migrate
```

### 6. (Optionnel) Charger des données de test

```bash
docker-compose exec php php bin/console doctrine:fixtures:load
```

---

## Configuration

### Variables d'environnement

Le fichier `.env` contient les configurations principales :

```env
# Base de données MySQL
DATABASE_URL="mysql://root:root@mysql:3306/vault_gg"

# MongoDB (système de reviews)
MONGODB_URL="mongodb://root:root@mongodb:27017"
MONGODB_DB="vault_gg_reviews"

# API RAWG
RAWG_API_KEY=votre_cle_api_rawg

# Application
APP_ENV=dev
APP_SECRET=votre_secret_symfony
```

### Accès aux services

- **Application** : http://localhost:8080
- **PhpMyAdmin** : http://localhost:8081
  - Serveur : `mysql`
  - Utilisateur : `root`
  - Mot de passe : `root`
- **Mongo Express** : http://localhost:8082
  - Utilisateur : `admin`
  - Mot de passe : `pass`

---

## Utilisation

### Créer un compte utilisateur

1. Accédez à http://localhost:8080
2. Cliquez sur "S'inscrire"
3. Remplissez le formulaire (email, username, mot de passe)
4. Connectez-vous avec vos identifiants

### Ajouter des jeux à sa bibliothèque

1. Cliquez sur "Rechercher" dans le menu
2. Tapez le nom d'un jeu (ex: "Elden Ring", "GTA V")
3. Cliquez sur "Ajouter à ma bibliothèque"
4. Le jeu est ajouté avec le statut "Backlog" par défaut

### Gérer ses jeux

1. Dans "Ma Bibliothèque", visualisez tous vos jeux
2. Changez le statut via le menu déroulant
3. Ajoutez aux favoris en cliquant sur le cœur
4. Filtrez par statut ou par genre
5. Supprimez un jeu avec le bouton "Supprimer"

### Consulter ses statistiques

1. Cliquez sur "Statistiques" dans le menu
2. Visualisez :
   - Nombre total de jeux
   - Répartition par statut
   - Top genres préférés
   - Jeux les mieux notés
   - Évolution sur 6 mois

---

## Tests

### Lancer tous les tests

```bash
docker-compose exec php ./vendor/bin/phpunit
```

### Tests unitaires uniquement

```bash
docker-compose exec php ./vendor/bin/phpunit tests/Unit
```

### Tests fonctionnels uniquement

```bash
docker-compose exec php ./vendor/bin/phpunit tests/Functional
```

### Couverture de code

```bash
docker-compose exec php ./vendor/bin/phpunit --coverage-html coverage
```

Le rapport de couverture sera disponible dans `coverage/index.html`

### Résultats des tests

**20 tests, 47 assertions - 100% de réussite**

Voir le fichier [RESULTATS_TESTS.md](RESULTATS_TESTS.md) pour le détail complet.

---

## Architecture

### Structure des dossiers

```
vault-gg/
├── .phpunit.cache/ 
├── assets/ 
├── bin/ 
├── config/              # Configuration Symfony
├── docker/              # Configuration Docker (Nginx)
├── migrations/          # Migrations Doctrine
├── public/              # Point d'entrée + assets
│   ├── css/            # Styles personnalisés
│   ├── js/             # Scripts JavaScript
│   ├── uploads/        # Fichiers uploadés (avatars)
│   └── index.php
├── src/
│   ├── Controller/     # Contrôleurs Symfony
│   ├── DataFixtures/
│   ├── Document/
│   ├── Entity/         # Entités Doctrine (User, Game, UserGame)
│   ├── Form/           # Formulaires Symfony
│   ├── Repository/     # Repositories Doctrine
│   ├── Security/       # Authenticator personnalisé
│   ├── Service/        # Services (RawgApiService)
│   └── Kernel.php
├── templates/          # Templates Twig
├── tests/              # Tests PHPUnit
│   ├── Functional/    # Tests fonctionnels (Controllers)
│   ├── Unit/          # Tests unitaires (Entities)
│   └── bootstrap.php
├── translations/
├── var/                # Cache, logs, sessions
├── vendor/             # Dépendances Composer
├── .dockerignore
├── .editignore
├── .env                # Variables d'environnement
├── .env.dev
├── .env.docker
├── .env.test
├── .gitignore
├── composer.json       # Dépendances PHP
├── composer.lock
├── docker-compose.yml  # Configuration Docker Compose
├── Dockerfile          # Image Docker PHP
├── importmap.php
├── phpunit.dist.xml    # Configuration PHPUnit
├── phpunit.xml.dist
├── README-DOCKER.md
├── RESULTAT_TEST.md
├── README.md           # Ce fichier
├── symfony.lock
└── vault_gg_backup.sql
```

### Diagramme de la base de données

```
┌─────────────────┐         ┌──────────────────┐         ┌─────────────────┐
│      USER       │         │    USER_GAME     │         │      GAME       │
├─────────────────┤         ├──────────────────┤         ├─────────────────┤
│ id (PK)         │────┐    │ id (PK)          │    ┌────│ id (PK)         │
│ email (unique)  │    └───→│ user_id (FK)     │    │    │ rawg_id         │
│ username        │         │ game_id (FK)     │←───┘    │ name            │
│ password (hash) │         │ status           │         │ background_image│
│ roles (JSON)    │         │ is_favorite      │         │ released        │
│ avatar          │         │ added_at         │         │ rating          │
└─────────────────┘         └──────────────────┘         │ genres (JSON)   │
                                                         └─────────────────┘
```

### Design System - Digital Fortress

- **Couleurs** : Indigo (#6366F1) à Violet (#8B5CF6)
- **Glassmorphism** : Effets de transparence et flou
- **Responsive** : Mobile-first avec Tailwind CSS
- **Thème** : Mode sombre par défaut + mode clair
- **Icônes** : Lucide Icons

---

## Sécurité

### Mesures de sécurité implémentées

#### Authentification
- Hashage des mots de passe avec **bcrypt**
- Protection contre les attaques par force brute
- Gestion des sessions sécurisées
- Option "Remember Me" sécurisée

#### Protection CSRF
- Tokens CSRF sur tous les formulaires
- Validation systématique des tokens

#### Validation des données
- Validation côté serveur avec Symfony Validator
- Contraintes sur les entités (NotBlank, Email, Length)
- Sanitisation des entrées utilisateur

#### Gestion des rôles
- Système de rôles Symfony (ROLE_USER, ROLE_ADMIN)
- Access Control dans `security.yaml`
- Protection des routes administrateur

#### Protection des uploads
- Validation des types de fichiers (avatars)
- Limitation de la taille (2 Mo max)
- Renommage sécurisé des fichiers

#### Base de données
- Requêtes préparées (protection injection SQL)
- Gestion des relations Doctrine
- Transactions pour l'intégrité des données

#### Bonnes pratiques
- Pas de données sensibles dans le code
- Variables d'environnement pour les secrets
- HTTPS en production (recommandé)
- Headers de sécurité configurés

---

## Conformité DWWM

Ce projet répond aux exigences du **Titre Professionnel Développeur Web et Web Mobile (DWWM)** niveau 5.

### CCP1 - Développer la partie front-end d'une application web sécurisée

- **Maquetter des interfaces utilisateur web** : Design system Digital Fortress
- **Réaliser des interfaces utilisateur statiques** : HTML5, CSS3, Tailwind
- **Développer la partie dynamique des interfaces** : JavaScript, interactions utilisateur
- **Installer et configurer son environnement** : Docker, outils de développement

### CCP2 - Développer la partie back-end d'une application web sécurisée

- **Mettre en place une base de données relationnelle** : MySQL, Doctrine ORM
- **Développer des composants d'accès aux données** : Repositories Doctrine
- **Développer des composants métier** : Controllers, Services, Entities
- **Documenter le déploiement** : README-DOCKER.md, docker-compose.yml

### Compétences transversales

- **Tests** : PHPUnit (20 tests, 47 assertions, 88% de couverture)
- **Sécurité** : Authentification, CSRF, validation, hashage
- **Versionning** : Git, commits structurés
- **Documentation** : README.md, commentaires de code
- **API** : Intégration RAWG API
- **Responsive** : Design adaptatif mobile/desktop

### Livrables pour la certification

-  Code source complet
-  Base de données (schémas et migrations)
-  Tests unitaires et fonctionnels
-  Documentation technique
-  Environnement Docker reproductible
-  Résultats des tests (RESULTATS_TESTS.md)

---

## Commandes Docker utiles

### Gestion des conteneurs

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

### Accès aux conteneurs

```bash
# Accéder au conteneur PHP
docker-compose exec php bash

# Accéder à MySQL
docker-compose exec mysql mysql -uroot -proot vault_gg

# Accéder à MongoDB
docker-compose exec mongodb mongosh -u root -p root
```

### Commandes Symfony

```bash
# Vider le cache
docker-compose exec php php bin/console cache:clear

# Créer une migration
docker-compose exec php php bin/console make:migration

# Lancer les migrations
docker-compose exec php php bin/console doctrine:migrations:migrate
```

---

## Améliorations futures

### Court terme
- [ ] Export de la bibliothèque (PDF, CSV)
- [ ] Partage de bibliothèque entre utilisateurs
- [ ] Notifications (nouveaux jeux, mises à jour)

### Moyen terme
- [ ] API REST publique
- [ ] Application mobile (React Native)
- [ ] Intégration Steam, Epic Games, PlayStation Network
- [ ] Système de recommandations IA

### Long terme
- [ ] Mode multijoueur / social
- [ ] Achievements / trophées personnalisés
- [ ] Marketplace d'échange de jeux
- [ ] Intégration Twitch / streaming
- [ ] Intégration comme hub discord

---

## Problèmes connus
- Couverture de code à améliorer sur RawgApiService (80%)
- Tests E2E à ajouter

---

## Contribuer

Ce projet est développé dans le cadre d'une certification professionnelle. Les contributions ne sont pas acceptées actuellement.

---

## Auteur

**Arnaud**  
Développeur Web et Web Mobile en formation  
Certification DWWM - 2026

- Portfolio : (à compléter)
- LinkedIn : (à compléter)
- GitHub : (à compléter)

---

## Licence

Ce projet est développé dans un cadre pédagogique. Tous droits réservés.

---

## Remerciements

- **Ecole Europeene Du Numerique** - Organisme de formation
- **RAWG** - API de jeux vidéo
- **Maheva DESSART** - Formatrice
- **Bertrand DETRE** - Formatreur
- **Communauté Docker** - Containerisation

---

**Créé le** : janvier 2026  
**Dernière mise à jour** : 13 janvier 2026  
**Version** : 1.0.0  
**Projet de certification** : Titre Professionnel DWWM Niveau 5
