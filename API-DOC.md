# API Documentation — Système de Gestion de Courriers

> **Version :** 1.0  
> **Base URL :** `http://<your-domain>`  
> **Authentification :** JWT Bearer Token (header `Authorization: Bearer <token>`)

---

## Sommaire

1. [Format des réponses](#1-format-des-réponses)
2. [Gestion des erreurs](#2-gestion-des-erreurs)
3. [Authentification](#3-authentification)
4. [Utilisateurs](#4-utilisateurs)
5. [Courriers](#5-courriers)
6. [Messages](#6-messages)

---

## 1. Format des réponses

Toutes les réponses JSON respectent la structure suivante, générée par `BaseApiController`.

### ✅ Succès
```json
{
  "status": "success",
  "data": { ... }
}
```

### ❌ Erreur
```json
{
  "status": "error",
  "message": "Description de l'erreur"
}
```

> **Note :** Le code HTTP de la réponse porte l'information d'état principal (`200`, `201`, `400`, `401`, `403`, `404`, etc.).

---

## 2. Gestion des erreurs

Tableau des codes d'erreur standards renvoyés par `BaseApiController` :

| Code HTTP | Signification                          | Cas d'usage typique                                              |
|-----------|----------------------------------------|------------------------------------------------------------------|
| `400`     | Validation échouée / Requête invalide  | Champs requis manquants, JSON mal formé, fichier > 5 Mo         |
| `401`     | Token invalide ou manquant             | Header `Authorization` absent, token expiré ou corrompu         |
| `403`     | Droits insuffisants                    | Endpoint réservé `Admin` mais appelé par un utilisateur standard |
| `404`     | Ressource non trouvée                  | Courrier, message ou utilisateur avec l'ID demandé inexistant    |

---

## 3. Authentification

### `POST /utilisateur/login`

Authentifie un utilisateur et retourne un token JWT.

- **Accès :** Public (aucun token requis)
- **Content-Type :** `application/json`

#### Corps de la requête

```json
{
  "email": "admin@espa.mg",
  "mdp": "motdepasse123"
}
```

| Champ   | Type     | Requis | Description         |
|---------|----------|--------|---------------------|
| `email` | `string` | ✅     | Email de connexion  |
| `mdp`   | `string` | ✅     | Mot de passe        |

#### Réponse — `200 OK`

```json
{
  "status": "success",
  "data": {
    "membre": {
      "email": "admin@espa.mg",
      "role": "Admin",
      "nom": "Rakoto",
      "prenom": "Jean"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9..."
  }
}
```

#### Erreurs

| Code | Message                    | Cause                               |
|------|----------------------------|-------------------------------------|
| `400` | `Champs requis manquants` | `email` ou `mdp` absent            |
| `404` | `Identifiants invalides`  | Email ou mot de passe incorrect     |

---

## 4. Utilisateurs

> **Accès :** Tous les endpoints de cette section sont réservés aux utilisateurs ayant le rôle **`Admin`**.  
> **Header requis :** `Authorization: Bearer <token>`

---

### `GET /utilisateur`

Liste tous les utilisateurs enregistrés.

- **Content-Type :** N/A
- **Accès :** `Admin` uniquement

#### Réponse — `200 OK`

```json
{
  "status": "success",
  "data": [
    {
      "id": 1,
      "email": "admin@espa.mg",
      "nom": "Rakoto",
      "prenom": "Jean",
      "role": "Admin"
    },
    {
      "id": 2,
      "email": "user@espa.mg",
      "nom": "Rabe",
      "prenom": "Marie",
      "role": "Utilisateur"
    }
  ]
}
```

---

### `POST /utilisateur`

Crée un nouvel utilisateur.

- **Content-Type :** `application/json`
- **Accès :** `Admin` uniquement

#### Corps de la requête

```json
{
  "email": "nouveau@espa.mg",
  "nom": "Ranaivo",
  "prenom": "Paul",
  "mdp": "motdepasse",
  "role": "Utilisateur"
}
```

| Champ    | Type     | Requis | Description                        |
|----------|----------|--------|------------------------------------|
| `email`  | `string` | ✅     | Adresse email unique               |
| `nom`    | `string` | ✅     | Nom de famille                     |
| `prenom` | `string` | ✅     | Prénom                             |
| `mdp`    | `string` | ✅     | Mot de passe en clair              |
| `role`   | `string` | ✅     | Rôle : `"Admin"` ou `"Utilisateur"` |

#### Réponse — `201 Created`

```json
{
  "status": "success",
  "user": {
    "id": 3,
    "email": "nouveau@espa.mg",
    "nom": "Ranaivo",
    "prenom": "Paul"
  }
}
```

---

### `GET /utilisateur/{id}`

Retourne les détails d'un utilisateur.

- **Contrainte d'URL :** `{id}` doit être un entier positif (`\d+`)
- **Accès :** `Admin` uniquement

#### Réponse — `200 OK`

```json
{
  "status": "success",
  "data": {
    "id": 2,
    "email": "user@espa.mg",
    "nom": "Rabe",
    "prenom": "Marie",
    "role": "Utilisateur"
  }
}
```

#### Erreurs

| Code  | Message                  | Cause                    |
|-------|--------------------------|--------------------------|
| `404` | `Utilisateur non trouvé` | Aucun utilisateur avec cet ID |

---

### `PUT /utilisateur/{id}`

Met à jour les informations d'un utilisateur existant.

- **Contrainte d'URL :** `{id}` doit être un entier positif (`\d+`)
- **Content-Type :** `application/json`
- **Accès :** `Admin` uniquement

#### Corps de la requête (partiel possible)

```json
{
  "nom": "Nouveau Nom",
  "prenom": "Nouveau Prénom",
  "email": "nouveau@espa.mg",
  "role": "Admin"
}
```

#### Réponse — `200 OK`

```json
{
  "status": "success",
  "data": {
    "id": 2,
    "nom": "Nouveau Nom",
    "prenom": "Nouveau Prénom",
    "email": "nouveau@espa.mg",
    "role": "Admin"
  }
}
```

---

## 5. Courriers

> **Header requis pour tous les endpoints :** `Authorization: Bearer <token>`

---

### `POST /api/courriers/creer`

Crée un nouveau courrier, avec upload optionnel d'un fichier joint.

- **Content-Type :** `multipart/form-data` ⚠️ (obligatoire pour le champ `fichier`)
- **Accès :** Tout utilisateur authentifié

#### Champs du formulaire

| Champ         | Type              | Requis | Contraintes              |
|---------------|-------------------|--------|--------------------------|
| `mail`        | `string`          | ✅     | Email de l'expéditeur    |
| `object`      | `string`          | ✅     | Objet du courrier        |
| `description` | `string`          | ✅     | Description du courrier  |
| `fichier`     | `file` (binaire)  | ❌     | Taille max : **5 Mo**    |

#### Réponse — `201 Created`

```json
{
  "status": "success",
  "data": {
    "id": 42,
    "reference": "REF-2026-00042"
  }
}
```

#### Erreurs

| Code  | Message                                     | Cause                       |
|-------|---------------------------------------------|-----------------------------|
| `400` | `Champs requis manquants : mail, object...` | Champs obligatoires absents |
| `400` | `Le fichier est trop volumineux (max 5 Mo)` | Fichier joint > 5 Mo        |

---

### `POST /api/courriers/creerTransferer`

Crée un courrier **et** le transfère immédiatement à un destinataire en une seule opération atomique.

- **Content-Type :** `multipart/form-data` ⚠️ (obligatoire pour le champ `fichier`)
- **Accès :** Tout utilisateur authentifié

#### Champs du formulaire

| Champ         | Type              | Requis | Contraintes              |
|---------------|-------------------|--------|--------------------------|
| `mail`        | `string`          | ✅     | Email de l'expéditeur    |
| `object`      | `string`          | ✅     | Objet du courrier        |
| `description` | `string`          | ✅     | Description du courrier  |
| `destId`      | `integer`         | ✅     | ID du destinataire       |
| `fichier`     | `file` (binaire)  | ❌     | Taille max : **5 Mo**    |

#### Réponse — `201 Created`

```json
{
  "status": "success",
  "data": {
    "id": 43,
    "reference": "REF-2026-00043"
  }
}
```

#### Erreurs

| Code  | Message                                                   | Cause                       |
|-------|-----------------------------------------------------------|-----------------------------|
| `400` | `Champs requis manquants : mail, object, destId...`       | Champs obligatoires absents |
| `400` | `Le fichier est trop volumineux (max 5 Mo)`               | Fichier joint > 5 Mo        |
| `404` | `Utilisateur introuvable pour l'ID <destId>`              | Destinataire inexistant     |

---

### `GET /api/courriers/{id}`

Retourne les détails d'un courrier par son ID.

- **Contrainte d'URL :** `{id}` doit être un entier positif (`requirements: ['id' => '\d+']`)
- **Accès :** Tout utilisateur authentifié

#### Réponse — `200 OK`

```json
{
  "status": "success",
  "data": {
    "id": 42,
    "reference": "REF-2026-00042",
    "object": "Demande de bourse",
    "description": "Dossier de candidature pour la bourse ESPA 2026",
    "mail": "etudiant@example.mg",
    "dateFin": null,
    "dateCreation": "2026-02-21 06:00:00"
  }
}
```

> **Note :** Le champ `fichier` (objet relation) est exclu de la réponse car renvoyé via un endpoint dédié (`GET /api/courriers/{id}/fichier`).

#### Erreurs

| Code  | Message                              | Cause                     |
|-------|--------------------------------------|---------------------------|
| `404` | `Courrier avec l'ID X introuvable.`  | Aucun courrier avec cet ID |

---

### `POST /api/courriers/{id}/cloturer`

Clôture un dossier courrier et notifie l'étudiant concerné par email.

- **Contrainte d'URL :** `{id}` doit être un entier positif (`requirements: ['id' => '\d+']`)
- **Content-Type :** N/A (pas de corps)
- **Accès :** Tout utilisateur authentifié

#### Réponse — `200 OK`

```json
{
  "status": "success",
  "data": {
    "message": "Le dossier a été clôturé et l'étudiant a été notifié par mail."
  }
}
```

#### Erreurs

| Code  | Message                | Cause                                    |
|-------|------------------------|------------------------------------------|
| `404` | `Courrier introuvable` | Aucun courrier avec cet ID               |
| `400` | `...`                  | Erreur lors de l'envoi du mail           |

---

### `GET /api/courriers/{id}/fichier`

Télécharge ou affiche le fichier binaire associé à un courrier (PDF, image, etc.).

- **Contrainte d'URL :** `{id}` doit être un entier positif (`requirements: ['id' => '\d+']`)
- **Accès :** Tout utilisateur authentifié

#### Réponse — Flux binaire

> ⚠️ Cet endpoint **ne retourne pas du JSON**. Il retourne directement le contenu binaire du fichier avec les headers HTTP suivants :

| Header                | Valeur dynamique                                              |
|-----------------------|---------------------------------------------------------------|
| `Content-Type`        | Type MIME du fichier (ex: `application/pdf`, `image/jpeg`)    |
| `Content-Disposition` | `inline; filename="<nom_du_fichier>"`                         |

Le navigateur (ou le client HTTP) peut ainsi afficher le fichier directement (`inline`) sans forcer le téléchargement.

#### Exemple de headers de réponse

```
HTTP/1.1 200 OK
Content-Type: application/pdf
Content-Disposition: inline; filename="courrier-espa-2026.pdf"
```

#### Erreurs (retournées en JSON)

| Code  | Message                                        | Cause                                |
|-------|------------------------------------------------|--------------------------------------|
| `404` | `Aucun fichier n'est associé à ce courrier.`   | Courrier sans pièce jointe           |
| `404` | `Courrier introuvable.`                        | Aucun courrier avec cet ID           |

---

## 6. Messages

> **Header requis pour tous les endpoints :** `Authorization: Bearer <token>`

---

### `GET /api/messages`

Liste tous les messages reçus par l'utilisateur connecté, avec pagination.

- **Accès :** Tout utilisateur authentifié

#### Paramètres de requête (Query String)

| Paramètre | Type      | Défaut | Description                        |
|-----------|-----------|--------|------------------------------------|
| `page`    | `integer` | `1`    | Numéro de la page à afficher       |
| `limit`   | `integer` | `10`   | Nombre de messages par page        |

**Exemple :** `GET /api/messages?page=2&limit=5`

#### Réponse — `200 OK`

```json
{
  "status": "success",
  "data": {
    "messages": [
      {
        "id": 10,
        "courrier": {
          "id": 42,
          "reference": "REF-2026-00042",
          "object": "Demande de bourse"
        },
        "expediteur": {
          "id": 1,
          "nom": "Rakoto",
          "prenom": "Jean"
        },
        "destinataire": {
          "id": 2,
          "nom": "Rabe",
          "prenom": "Marie"
        },
        "isReadAt": null,
        "dateCreation": "2026-02-21 06:30:00"
      }
    ],
    "page": 1,
    "limit": 10
  }
}
```

> **Note :** `isReadAt` est `null` si le message n'a pas encore été lu. La date est au format `Y-m-d H:i:s` si le message a été lu.

---

### `POST /api/messages/transferer`

Transfère un courrier existant vers un utilisateur destinataire (crée un message de transfert).

- **Content-Type :** `application/json` ✅
- **Accès :** Tout utilisateur authentifié

#### Corps de la requête

```json
{
  "destId": 3,
  "courrierId": 42
}
```

| Champ       | Type      | Requis | Description                       |
|-------------|-----------|--------|-----------------------------------|
| `destId`    | `integer` | ✅     | ID de l'utilisateur destinataire  |
| `courrierId`| `integer` | ✅     | ID du courrier à transférer       |

#### Réponse — `200 OK`

```json
{
  "status": "success",
  "data": {
    "message": "Courrier transféré avec succès."
  }
}
```

#### Erreurs

| Code  | Message                             | Cause                              |
|-------|-------------------------------------|------------------------------------|
| `400` | `Champs requis manquants : destId`  | Champs obligatoires absents        |
| `404` | `Utilisateur introuvable...`        | Destinataire ou expéditeur invalide|
| `404` | `Courrier introuvable...`           | Aucun courrier avec cet ID         |

---

### `PATCH /api/messages/{id}/lire`

Marque un message spécifique comme **lu** (enregistre la date et l'heure de lecture).

- **Contrainte d'URL :** `{id}` doit être un entier positif (`requirements: ['id' => '\d+']`)
- **Content-Type :** N/A (pas de corps)
- **Accès :** Tout utilisateur authentifié

#### Réponse — `200 OK`

```json
{
  "status": "success",
  "data": {
    "message": "Message marqué comme lu."
  }
}
```

#### Erreurs

| Code  | Message               | Cause                      |
|-------|-----------------------|----------------------------|
| `404` | `Message introuvable` | Aucun message avec cet ID  |

---

## Récapitulatif des endpoints

| Méthode  | URL                                   | Content-Type            | Auth         | Description                              |
|----------|---------------------------------------|-------------------------|--------------|------------------------------------------|
| `POST`   | `/utilisateur/login`                  | `application/json`      | ❌ Public    | Connexion et obtention du JWT            |
| `GET`    | `/utilisateur`                        | —                       | ✅ Admin     | Liste tous les utilisateurs              |
| `POST`   | `/utilisateur`                        | `application/json`      | ✅ Admin     | Crée un nouvel utilisateur               |
| `GET`    | `/utilisateur/{id}`                   | —                       | ✅ Admin     | Détails d'un utilisateur                 |
| `PUT`    | `/utilisateur/{id}`                   | `application/json`      | ✅ Admin     | Mise à jour d'un utilisateur             |
| `POST`   | `/api/courriers/creer`                | `multipart/form-data`   | ✅ Token     | Crée un courrier                         |
| `POST`   | `/api/courriers/creerTransferer`      | `multipart/form-data`   | ✅ Token     | Crée un courrier et le transfère         |
| `GET`    | `/api/courriers/{id}`                 | —                       | ✅ Token     | Détails d'un courrier                    |
| `POST`   | `/api/courriers/{id}/cloturer`        | —                       | ✅ Token     | Clôture un dossier et notifie par mail   |
| `GET`    | `/api/courriers/{id}/fichier`         | —                       | ✅ Token     | Retourne le fichier binaire joint        |
| `GET`    | `/api/messages`                       | —                       | ✅ Token     | Liste les messages reçus (paginés)       |
| `POST`   | `/api/messages/transferer`            | `application/json`      | ✅ Token     | Transfère un courrier à un utilisateur   |
| `PATCH`  | `/api/messages/{id}/lire`             | —                       | ✅ Token     | Marque un message comme lu              |