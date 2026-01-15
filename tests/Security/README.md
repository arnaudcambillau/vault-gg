# ��� Suite de Tests de Sécurité OWASP - Vault.gg

## ��� Vue d'ensemble

Suite complète de tests de sécurité couvrant les standards **OWASP Top 10 2021**.

**Résultat : 60 tests / 135 assertions - 100%**

## ��� Fichiers de tests

### 1. InputValidationSecurityTest.php (9 tests)
- Protection contre les injections SQL
- Protection contre XSS (Cross-Site Scripting)
- Validation des entrées utilisateur
- Gestion des caractères spéciaux
- Limitation de longueur des champs

### 2. AuthenticationSecurityTest.php (9 tests)
- Sécurité du système de connexion
- Protection CSRF sur les formulaires
- Fonctionnalité "Remember Me"
- Hashage des mots de passe
- Invalidation des sessions

### 3. AuthorizationSecurityTest.php (~14 tests)
- Contrôle d'accès basé sur les rôles (RBAC)
- Protection des routes administrateur
- Vérification des permissions utilisateur

### 4. SessionSecurityTest.php (~13 tests)
- Cookies sécurisés (HttpOnly, Secure, SameSite)
- Protection contre la fixation de session
- Régénération des identifiants de session
- Expiration automatique des sessions

### 5. SecurityHeadersTest.php (~15 tests)
- Content-Security-Policy (CSP)
- X-Frame-Options (protection clickjacking)
- X-Content-Type-Options
- Strict-Transport-Security (HSTS)
- Referrer-Policy

## ��� Standards OWASP couverts

| OWASP Top 10 2021 | Couverture |
|-------------------|------------|
| A01 - Broken Access Control | valide |
| A02 - Cryptographic Failures | valide |
| A03 - Injection | valide |
| A05 - Security Misconfiguration | valide |
| A07 - Authentication Failures | valide |

## ��� Lancement des tests
```bash
# Tous les tests de sécurité
php bin/phpunit tests/Security/

# Un fichier spécifique
php bin/phpunit tests/Security/AuthenticationSecurityTest.php
```

## ��� Dernière mise à jour
Janvier 2026 - Certification DWWM
