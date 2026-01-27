#!/bin/bash

################################################################################
# Script de déploiement - Vault.gg
# Auteur: Arnaud (Certification DWWM)
# Description: Déploie automatiquement l'application en production
# Version: 1.0
# Date: Janvier 2026
################################################################################

set -e

echo "========================================"
echo "DEPLOIEMENT DE VAULT.GG - VERSION 1.0"
echo "========================================"
echo ""

# Vérification: Est-on dans le bon dossier ?
if [ ! -f "composer.json" ]; then
    echo "ERREUR: Vous n'etes pas dans le dossier du projet."
    echo "Veuillez vous positionner dans le dossier vault-gg."
    exit 1
fi

# Variables
TIMESTAMP=$(date +"%Y-%m-%d %H:%M:%S")
LOG_FILE="var/log/deploy.log"

# Fonction de log
log() {
    echo "[$TIMESTAMP] $1" | tee -a "$LOG_FILE"
}

log "Debut du deploiement"
echo ""

# Etape 1: Récupération du code
echo "Etape 1/6 : Recuperation du code source..."
git pull origin main
if [ $? -ne 0 ]; then
    log "ERREUR: Echec lors du git pull"
    exit 1
fi
log "Code source recupere avec succes"
echo ""

# Etape 2: Installation des dépendances
echo "Etape 2/6 : Installation des dependances PHP..."
composer install --no-dev --optimize-autoloader --no-interaction
if [ $? -ne 0 ]; then
    log "ERREUR: Echec lors de composer install"
    exit 1
fi
log "Dependances installees avec succes"
echo ""

# Etape 3: Mise à jour de la base de données
echo "Etape 3/6 : Mise a jour de la base de donnees..."
php bin/console doctrine:migrations:migrate --no-interaction
if [ $? -ne 0 ]; then
    log "ERREUR: Echec lors des migrations"
    exit 1
fi
log "Base de donnees mise a jour avec succes"
echo ""

# Etape 4: Nettoyage du cache
echo "Etape 4/6 : Nettoyage et optimisation du cache..."
php bin/console cache:clear --no-warmup --env=prod
php bin/console cache:warmup --env=prod
if [ $? -ne 0 ]; then
    log "ERREUR: Echec lors du nettoyage du cache"
    exit 1
fi
log "Cache nettoye et optimise avec succes"
echo ""

# Etape 5: Permissions des fichiers
echo "Etape 5/6 : Application des permissions..."
chmod -R 775 var/
chmod -R 775 public/uploads/
chown -R www-data:www-data var/ 2>/dev/null || true
chown -R www-data:www-data public/uploads/ 2>/dev/null || true
log "Permissions appliquees avec succes"
echo ""

# Etape 6: Test de l'application
echo "Etape 6/6 : Verification de l'application..."
php bin/console about --env=prod > /dev/null 2>&1
if [ $? -ne 0 ]; then
    log "ATTENTION: L'application presente des anomalies"
    echo "Verifiez les logs dans var/log/"
else
    log "Application fonctionnelle"
fi
echo ""

log "Deploiement termine avec succes"
echo "========================================"
echo "DEPLOIEMENT TERMINE"
echo "========================================"
echo ""
echo "Application disponible sur : https://votre-domaine.com"
echo "Logs de deploiement : $LOG_FILE"
echo "Logs applicatifs : var/log/prod.log"
echo ""