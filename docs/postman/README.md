# Collection Postman - Gestion Bancaire API

## Vue d'ensemble

Cette collection Postman contient tous les tests nécessaires pour valider les endpoints US 2.2 : "Créer un nouveau compte" et US 2.0 : "Lister tous les comptes".

## Installation

1. **Importer la collection** :
   - Ouvrez Postman
   - Cliquez sur "Import" en haut à gauche
   - Sélectionnez "File"
   - Choisissez le fichier `Gestion_Bancaire_API.postman_collection.json`

2. **Configurer les variables d'environnement** :
   - Dans Postman, allez dans "Environments"
   - Créez un nouvel environnement nommé "Gestion Bancaire"
   - Ajoutez la variable :
     - `base_url` = `http://127.0.0.1:8001` (port utilisé par le serveur Laravel)

## Structure de la collection

### 📁 Créer un compte
Contient tous les tests pour l'endpoint `POST /api/v1/comptes`

#### Tests disponibles :

1. **Créer un nouveau compte - Client existant (Épargne)**
   - Création d'un compte épargne avec toutes les données client

2. **Créer un nouveau compte - Client existant (Chèque)**
   - Création d'un compte chèque avec toutes les données client

3. **Créer un nouveau compte - Données minimales**
   - Création avec uniquement les champs obligatoires

4. **Créer un nouveau compte - Erreur validation (Email dupliqué)**
   - Test d'erreur avec email déjà utilisé

5. **Créer un nouveau compte - Erreur validation (Téléphone dupliqué)**
   - Test d'erreur avec téléphone déjà utilisé

6. **Créer un nouveau compte - Erreur validation (Données manquantes)**
   - Test d'erreur avec champs obligatoires manquants

### 📁 Comptes
Contient tous les tests pour les endpoints `GET /api/v1/comptes`

#### Tests disponibles :

1. **Lister tous les comptes - Par défaut**
    - Test basique avec paramètres par défaut

2. **Lister tous les comptes - Page 2**
    - Test de pagination (page 2, 5 éléments par page)

3. **Lister comptes - Filtre par type**
    - Test filtre par type `epargne`
    - Test filtre par type `cheque`

4. **Lister comptes - Filtre par statut**
    - Test filtre par statut `actif`
    - Test filtre par statut `bloque`

5. **Lister comptes - Recherche**
    - Recherche par numéro de compte
    - Recherche par nom du titulaire

6. **Lister comptes - Tri**
    - Tri par date de création (DESC)
    - Tri par titulaire (ASC)

7. **Lister comptes - Combinaison de filtres**
    - Test avec tous les paramètres combinés

8. **Récupérer un compte spécifique**
    - Test de récupération par ID UUID

9. **Récupérer un compte - Erreur (ID inexistant)**
    - Test d'erreur avec ID inexistant

10. **Lister comptes - Paramètres invalides**
    - Test des erreurs de validation

## Utilisation

### Exécution des tests

1. **Test individuel** :
   - Sélectionnez une requête
   - Cliquez sur "Send"

2. **Test de collection** :
   - Cliquez droit sur la collection
   - Sélectionnez "Run collection"
   - Configurez les paramètres d'exécution

### Headers requis

#### Pour les requêtes GET :
Tous les tests incluent automatiquement :
- `Accept: application/json`

#### Pour les requêtes POST :
Tous les tests incluent automatiquement :
- `Accept: application/json`
- `Content-Type: application/json`

### Paramètres de requête

Chaque test démontre l'utilisation des différents paramètres :

| Paramètre | Type | Défaut | Description |
|-----------|------|--------|-------------|
| `page` | integer | 1 | Numéro de page |
| `limit` | integer | 10 | Nombre d'éléments par page (max: 100) |
| `type` | string | null | `epargne` ou `cheque` |
| `statut` | string | null | `actif`, `bloque`, `ferme` |
| `search` | string | null | Recherche textuelle |
| `sort` | string | `dateCreation` | `dateCreation`, `solde`, `titulaire` |
| `order` | string | `desc` | `asc` ou `desc` |

## Exemples de réponses

### Réponse de succès
```json
{
  "success": true,
  "message": "Comptes récupérés avec succès",
  "data": [
    {
      "id": "uuid",
      "numeroCompte": "C00123456",
      "titulaire": "Amadou Diallo",
      "type": "epargne",
      "solde": 1250000,
      "devise": "FCFA",
      "dateCreation": "2023-03-15T00:00:00Z",
      "statut": "bloque",
      "motifBlocage": "Inactivité de 30+ jours",
      "metadata": {
        "derniereModification": "2023-06-10T14:30:00Z",
        "version": 1
      }
    }
  ],
  "pagination": {
    "currentPage": 1,
    "totalPages": 3,
    "totalItems": 25,
    "itemsPerPage": 10,
    "hasNext": true,
    "hasPrevious": false
  },
  "links": {
    "self": "/api/v1/comptes?page=1&limit=10",
    "next": "/api/v1/comptes?page=2&limit=10",
    "first": "/api/v1/comptes?page=1&limit=10",
    "last": "/api/v1/comptes?page=3&limit=10"
  }
}
```

### Réponse de succès - Création de compte
```json
{
  "success": true,
  "message": "Compte créé avec succès",
  "data": {
    "id": "550e8400-e29b-41d4-a716-446655440000",
    "numeroCompte": "C00123456",
    "titulaire": "Amadou Diallo",
    "type": "epargne",
    "solde": 0,
    "devise": "FCFA",
    "dateCreation": "2023-03-15T00:00:00Z",
    "statut": "actif",
    "motifBlocage": null,
    "metadata": {
      "derniereModification": "2023-03-15T00:00:00Z",
      "version": 1
    }
  },
  "clientCreated": true,
  "notificationsSent": {
    "email": true,
    "sms": true
  }
}
```

### Réponse d'erreur de validation
```json
{
  "success": false,
  "message": "Les données fournies sont invalides.",
  "errors": {
    "email": ["Cet email est déjà utilisé."],
    "telephone": ["Ce numéro de téléphone est déjà utilisé."]
  }
}
```

## Tests automatisés

Pour ajouter des tests automatisés dans Postman :

1. Dans chaque requête, allez dans l'onglet "Tests"
2. Ajoutez des scripts de test comme :

```javascript
// Test du statut de succès
pm.test("Status code is 200", function () {
    pm.response.to.have.status(200);
});

// Test de la structure de réponse
pm.test("Response has success structure", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData).to.have.property('success');
    pm.expect(jsonData).to.have.property('data');
    pm.expect(jsonData).to.have.property('pagination');
});

// Test de la pagination
pm.test("Pagination structure is correct", function () {
    var jsonData = pm.response.json();
    pm.expect(jsonData.pagination).to.have.property('currentPage');
    pm.expect(jsonData.pagination).to.have.property('totalPages');
});
```

## Notes importantes

### Pour la création de compte :
- Si le client n'existe pas, il est créé automatiquement avec génération de mot de passe et code d'authentification
- Un email d'authentification est envoyé avec le mot de passe généré
- Un SMS est envoyé avec le code d'authentification (valide 24h)
- Le numéro de compte est généré automatiquement
- Le solde initial est de 0 (calculé à partir des transactions futures)

### Pour la consultation des comptes :
- L'authentification n'est pas encore activée (TODO pour plus tard)
- Tous les comptes retournés ont un statut ≠ 'ferme' (scope global)
- Les soldes sont calculés dynamiquement à partir des transactions
- La recherche fonctionne sur numéro de compte, nom et prénom du titulaire
- Le tri par solde utilise la date de création comme proxy

## Support

Pour toute question concernant l'utilisation de cette collection, consultez la documentation API complète dans `docs/api.md`.