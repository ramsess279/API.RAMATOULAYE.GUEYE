# 🏦 API Gestion Bancaire - RAMATOULAYE GUEYE

API REST complète pour la gestion des comptes bancaires avec authentification OAuth2, archivage automatique et système de blocage programmé.

## 🚀 Démarrage Rapide

### Prérequis
- PHP 8.1+
- Composer
- PostgreSQL
- Node.js & NPM

### Installation

1. **Cloner le projet**
```bash
git clone https://github.com/ramsess279/API.RAMATOULAYE.GUEYE.git
cd API.RAMATOULAYE.GUEYE
```

2. **Installer les dépendances**
```bash
composer install
npm install
```

3. **Configuration de l'environnement**
```bash
cp .env.example .env
php artisan key:generate
```

4. **Configuration de la base de données**
```env
# Base de données principale (PostgreSQL)
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=ges_banque
DB_USERNAME=votre_username
DB_PASSWORD=votre_password

# Base d'archive Neon (PostgreSQL)
DB_ARCHIVE_CONNECTION=pgsql
DB_ARCHIVE_HOST=votre_host_neon
DB_ARCHIVE_PORT=5432
DB_ARCHIVE_DATABASE=votre_db_neon
DB_ARCHIVE_USERNAME=votre_username_neon
DB_ARCHIVE_PASSWORD=votre_password_neon
```

5. **Migration et seeding**
```bash
php artisan migrate
php artisan db:seed
```

6. **Installation Passport**
```bash
php artisan passport:install
php artisan passport:client --personal
```

## 🔐 Authentification OAuth2

### Comptes de test

| Rôle | Email | Mot de passe |
|------|-------|-------------|
| Admin | `admin@banque.com` | `admin123` |
| Client | `amadou.diallo@email.com` | `client123` |
| Client | `fatou.sarr@email.com` | `client123` |
| Client | `moussa.ndiaye@email.com` | `client123` |
| Client | `aminata.ba@email.com` | `client123` |
| Client | `ibrahima.gueye@email.com` | `client123` |

### Obtenir un token d'accès

```bash
curl -X POST http://localhost:8000/api/v1/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@banque.com",
    "password": "admin123"
  }'
```

**Réponse :**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "user": {
      "id": "uuid",
      "nom": "Admin",
      "prenom": "System",
      "email": "admin@banque.com",
      "role": "admin"
    },
    "access_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "refresh_token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...",
    "token_type": "Bearer",
    "expires_in": 3600
  }
}
```

### Utiliser le token

```bash
curl -X GET http://localhost:8000/api/v1/comptes \
  -H "Authorization: Bearer VOTRE_ACCESS_TOKEN" \
  -H "Accept: application/json"
```

## 📚 Documentation API

### Swagger UI
Accédez à la documentation interactive :
```
http://localhost:8000/api/documentation
```

### Endpoints Principaux

#### Authentification
- `POST /api/v1/auth/login` - Connexion
- `POST /api/v1/auth/refresh` - Rafraîchir token
- `POST /api/v1/auth/logout` - Déconnexion

#### Comptes Bancaires
- `GET /api/v1/comptes` - Lister les comptes
- `POST /api/v1/comptes` - Créer un compte *(Admin uniquement)*
- `GET /api/v1/comptes/{id}` - Détails d'un compte
- `PATCH /api/v1/comptes/{numero}` - Modifier un compte *(Admin uniquement)*
- `DELETE /api/v1/comptes/{id}` - Supprimer un compte *(Admin uniquement)*
- `POST /api/v1/comptes/{id}/bloquer` - Bloquer un compte *(Admin uniquement)*
- `POST /api/v1/comptes/{id}/debloquer` - Débloquer un compte *(Admin uniquement)*

## 🏗️ Architecture

### Technologies
- **Laravel 11** - Framework PHP
- **Laravel Passport** - Authentification OAuth2
- **PostgreSQL** - Base de données principale
- **Neon** - Base d'archive distante
- **Swagger/OpenAPI** - Documentation API

### Fonctionnalités Clés
- ✅ Authentification OAuth2 avec rôles (Admin/Client)
- ✅ Archivage automatique vers Neon lors du blocage
- ✅ Recherche hybride (DB principale + archive)
- ✅ Blocage programmé des comptes
- ✅ Calcul automatique des soldes
- ✅ Validation des données métier
- ✅ Logging des opérations
- ✅ API RESTful complète

### Sécurité
- Middleware d'authentification
- Contrôle des rôles et permissions
- Validation des données
- Sanitisation des entrées
- Logs d'audit

## 🧪 Tests

```bash
# Tests unitaires
php artisan test

# Tests avec couverture
php artisan test --coverage
```

## 📦 Déploiement

### Production
```bash
# Build des assets
npm run build

# Cache de configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Génération de la doc Swagger
php artisan l5-swagger:generate
```

### Docker
```bash
# Construction de l'image
docker build -t api-banque .

# Lancement
docker-compose up -d
```

## 🤝 Contribution

1. Fork le projet
2. Créer une branche feature (`git checkout -b feature/AmazingFeature`)
3. Commit les changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📄 Licence

Ce projet est sous licence MIT - voir le fichier [LICENSE](LICENSE) pour plus de détails.

## 👤 Auteur

**RAMATOULAYE GUEYE**
- GitHub: [@ramsess279](https://github.com/ramsess279)
- Email: contact@ramatoulaye.gueye.com

---

<p align="center">Fait avec ❤️ par RAMATOULAYE GUEYE</p>

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

You may also try the [Laravel Bootcamp](https://bootcamp.laravel.com), where you will be guided through building a modern Laravel application from scratch.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains thousands of video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the [Laravel Partners program](https://partners.laravel.com).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[WebReinvent](https://webreinvent.com/)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[DevSquad](https://devsquad.com/hire-laravel-developers)**
- **[Jump24](https://jump24.co.uk)**
- **[Redberry](https://redberry.international/laravel/)**
- **[Active Logic](https://activelogic.com)**
- **[byte5](https://byte5.de)**
- **[OP.GG](https://op.gg)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
