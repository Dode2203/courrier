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
  "message": "Message d'information",
  "data": { ... },
  "metadata_key": "metadata_value"
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
      "prenom": "Jean",
      "adresse": "Lot II M 45 Isoraka"
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
      "adresse": "Lot II M 45 Isoraka",
      "role": "Admin"
    },
    {
      "id": 2,
      "email": "user@espa.mg",
      "nom": "Rabe",
      "prenom": "Marie",
      "adresse": "Lot IV G 12 Antanimena",
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
  "adresse": "Lot III F 22 Ankorondrano",
  "mdp": "motdepasse",
  "role": "Utilisateur"
}
```

| Champ    | Type     | Requis | Description                        |
|----------|----------|--------|------------------------------------|
| `email`  | `string` | ✅     | Adresse email unique               |
| `nom`    | `string` | ✅     | Nom de famille                     |
| `prenom` | `string` | ✅     | Prénom                             |
| `adresse`| `string` | ❌     | Adresse (nullable)                 |
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
    "prenom": "Paul",
    "adresse": "Lot III F 22 Ankorondrano"
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
    "adresse": "Lot IV G 12 Antanimena",
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
  "adresse": "Nouvelle Adresse",
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
    "adresse": "Nouvelle Adresse",
    "role": "Admin"
  }
}
```

---

## 5. Courriers

> **Header requis pour tous les endpoints :** `Authorization: Bearer <token>`

---

### `GET /api/courriers`

Liste les courriers enregistrés avec pagination et tri chronologique (plus récent au plus ancien).

- **Paramètres de requête (Query String) :**
    - `page` (integer, optionnel, défaut : 1) : Numéro de la page.
    - `limit` (integer, optionnel, défaut : 20) : Nombre d'éléments par page.
- **Accès :** Tout utilisateur authentifié

#### Réponse — `200 OK`

```json
  "status": "success",
  "message": "Liste des courriers récupérée avec succès.",
  "data": [
    {
      "id": 42,
      ...
    }
  ],
  "total": 125,
  "page": 1,
  "lastPage": 7,
  "limit": 20
}
```

---

### `GET /api/courriers/getAllbyUser`

Récupère la liste paginée de tous les courriers qui impliquent l'utilisateur connecté (créateur du courrier, expéditeur ou destinataire d'un message lié).

- **Paramètres de requête (Query String) :**
    - `page` (int, défaut 1) : Numéro de la page.
    - `limit` (int, défaut 20) : Nombre d'éléments par page.
- **Accès :** Tout utilisateur authentifié

#### Réponse — `200 OK`

```json
{
  "status": "success",
  "message": "Liste de vos courriers récupérée avec succès.",
  "data": [
    {
      "id": 42,
      "reference": "21022026/REF1",
      "object": "Demande de bourse",
      "nom": "RAKOTO",
      "prenom": "Jean",
      ...
    }
  ],
  "total": 12,
  "page": 1,
  "lastPage": 1,
  "limit": 20
}
```

---

### `POST /api/courriers/creer`

Crée un nouveau courrier, avec upload optionnel d'un fichier joint.

- **Content-Type :** `multipart/form-data` ⚠️ (obligatoire pour le champ `fichiers[]`)
- **Accès :** Tout utilisateur authentifié

#### Champs du formulaire

| Champ         | Type              | Requis | Contraintes              |
|---------------|-------------------|--------|--------------------------|
| `mail`        | `string`          | ✅     | Email de l'expéditeur    |
| `nom`         | `string`          | ✅     | Nom du déposant          |
| `prenom`      | `string`          | ❌     | Prénom du déposant       |
| `telephone`   | `string`          | ❌     | Téléphone du déposant    |
| `object`      | `string`          | ✅     | Objet du courrier        |
| `description` | `string`          | ✅     | Description du courrier  |

#### Réponse — `201 Created`

```json
{
  "status": "success",
  "message": "Courrier créé avec succès.",
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

- **Content-Type :** `multipart/form-data` ⚠️ (obligatoire pour le champ `fichiers[]`)
- **Accès :** Tout utilisateur authentifié

#### Champs du formulaire

| Champ         | Type              | Requis | Contraintes              |
|---------------|-------------------|--------|--------------------------|
| `mail`        | `string`          | ✅     | Email de l'expéditeur    |
| `nom`         | `string`          | ✅     | Nom du déposant          |
| `prenom`      | `string`          | ❌     | Prénom du déposant       |
| `object`      | `string`          | ✅     | Objet du courrier        |
| `description` | `string`          | ✅     | Description du courrier  |
| `destId`      | `integer`         | ✅     | ID du destinataire       |

#### Réponse — `201 Created`

```json
{
  "status": "success",
  "message": "Courrier créé et transféré avec succès.",
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

### `GET /api/fichiers/{id}/download`

Télécharge le binaire d'un fichier spécifique par son ID.

- **Contrainte d'URL :** `{id}` doit être un entier positif (`\d+`)
- **Accès :** Tout utilisateur authentifié

#### Réponse — `200 OK`

- **Content-Type :** Dynamique (ex: `application/pdf`, `image/png`)
- **Corps :** Contenu binaire du fichier
- **Header :** `Content-Disposition: attachment; filename="..."`

---

---

### `GET /api/courriers/recherche`

Recherche ciblée des courriers par nom et/ou prénom du déposant (**Insensible à la casse**).

- **Paramètres de requête (Query String) :**
    - `nom` (string, optionnel) : Nom du déposant (partiel ou complet).
    - `prenom` (string, optionnel) : Prénom du déposant (partiel ou complet).
- **Note :** Au moins un des deux paramètres doit être fourni.
- **Accès :** Tout utilisateur authentifié

#### Réponse — `200 OK`

```json
{
  "status": "success",
  "message": "Résultats de recherche récupérés.",
  "data": [
    {
      "id": 42,
      "reference": "21022026/REF1",
      "nom": "RAKOTO",
      "prenom": "Jean",
      "object": "Demande de bourse",
      "description": "...",
      "mail": "rakoto@example.mg",
      "dateCreation": "2026-02-21 06:00:00",
      "dateFin": null
    }
  ]
}
```

#### Erreurs

| Code  | Message                                                  | Cause                                    |
|-------|----------------------------------------------------------|------------------------------------------|
| `400` | `Veuillez fournir au moins un critère de recherche...`   | Paramètres `nom` et `prenom` absents      |

---

### `GET /api/courriers/{id}`

Retourne les détails d'un courrier par son ID.

- **Contrainte d'URL :** `{id}` doit être un entier positif (`requirements: ['id' => '\d+']`)
- **Accès :** Tout utilisateur authentifié

#### Réponse — `200 OK`

```json
{
  "status": "success",
  "message": "Détails du courrier récupérés.",
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

> **Note :** Les fichiers ne sont plus inclus directement dans l'objet Courrier. Ils sont accessibles via les messages associés ou via `GET /api/fichiers/{id}/download`.

#### Erreurs

| `404` | `Courrier avec l'ID X introuvable.`  | Aucun courrier avec cet ID ou courrier supprimé |

---

### `GET /api/courriers/{id}/messages`

Récupère la liste de tous les messages liés à un courrier spécifique.

- **Sécurité :** L'utilisateur doit être participant (expéditeur ou destinataire) d'un message, ou être le créateur du courrier.
- **Accès :** Tout utilisateur authentifié
- **Pagination :** Non paginé (retourne tous les messages chronologiquement).

#### Réponse — `200 OK`

```json
{
  "status": "success",
  "message": "Messages du courrier récupérés avec succès.",
  "data": [
    {
      "id": 16,
      "dateCreation": "2026-02-23 09:40:00",
      "expediteur": { "id": 1, "nom": "RAJAO", "prenom": "Emile" },
      "destinataire": { "id": 5, "nom": "RANDRIA", "prenom": "Mamy" },
      "observation": "<p>Veuillez traiter ce dossier...</p>",
      "fichiers": [
        { "id": 1, "nom": "PJ1.pdf", "type": "application/pdf" }
      ]
    }
  ]
}
```

---

### `DELETE /api/courriers/{id}`

Supprime logiquement un courrier (Soft Delete). Le courrier ne sera plus visible via `GET /api/courriers/{id}`.

- **Contrainte d'URL :** `{id}` doit être un entier positif
- **Accès :** Tout utilisateur authentifié

#### Réponse — `200 OK`

```json
{
  "status": "success",
  "message": "Courrier supprimé avec succès.",
  "data": null
}
```

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
  "message": "Le dossier a été clôturé et l'étudiant a été notifié par mail.",
  "data": null
}
```

#### Erreurs

| Code  | Message                | Cause                                    |
|-------|------------------------|------------------------------------------|
| `404` | `Courrier introuvable` | Aucun courrier avec cet ID               |
| `400` | `...`                  | Erreur lors de l'envoi du mail           |

---

---

### ✉️ Messages

> **Header requis pour tous les endpoints :** `Authorization: Bearer <token>`

---

### `GET /api/messages`

Liste les messages de l'utilisateur connecté, avec filtre de direction et pagination.

- **Accès :** Tout utilisateur authentifié

#### Paramètres de requête (Query String)

| Paramètre | Type      | Défaut       | Valeurs possibles              | Description                            |
|-----------|-----------|--------------|--------------------------------|----------------------------------------|
| `type`    | `string`  | `all`        | `received`, `sent`, `all`      | Filtre de direction des messages        |
| `page`    | `integer` | `1`          | —                              | Numéro de la page à afficher           |
| `limit`   | `integer` | `20`         | —                              | Nombre de messages par page            |

| Valeur `type` | Description                                         |
|---------------|-----------------------------------------------------|
| `received`    | Messages où l'utilisateur est le destinataire       |
| `sent`        | Messages où l'utilisateur est l'expéditeur         |
| `all`         | **(défaut)** Tous les messages liés à l'utilisateur |

**Exemples :**
- `GET /api/messages` → tous les messages, page 1
- `GET /api/messages?type=sent` → messages envoyés
- `GET /api/messages?type=received&page=2&limit=5` → messages reçus, page 2

#### Réponse — `200 OK`

Le résultat est trié par **ordre chronologique décroissant** (le message le plus récent est à l'index 0).

```json
{
  "status": "success",
  "message": "Messages récupérés avec succès.",
  "data": [
    {
      "id": 16,
      "dateCreation": "2026-02-23 09:40:00",
      "isReadAt": null,
      "expediteur": { "id": 1, "nom": "RAJAO", "prenom": "Emile" },
      "destinataire": { "id": 5, "nom": "RANDRIA", "prenom": "Mamy" },
      "courrier": { "id": 42, "reference": "21022026/REF1", "object": "Demande de bourse" },
      "observation": "<p>Veuillez traiter ce dossier...</p>",
      "partagerAt": "2026-02-23 09:41:00",
      "fichiers": [
        { "id": 1, "nom": "PJ1.pdf", "type": "application/pdf" }
      ]
    }
  ],
  "total": 54,
  "page": 1,
  "lastPage": 3,
  "limit": 20
}
```

> **Note :** `isReadAt` est `null` si le message n'a pas encore été lu. La date est au format `Y-m-d H:i:s` si le message a été lu.


---

### `GET /api/messages/{id}`

Récupère les détails d'un message spécifique, incluant son contenu complet et la liste de ses pièces jointes.

- **Contrainte d'URL :** `{id}` doit être un entier positif
- **Sécurité :** L'utilisateur doit être l'expéditeur ou le destinataire du message.
- **Accès :** Tout utilisateur authentifié

#### Réponse — `200 OK`

```json
{
  "status": "success",
  "message": "Détails du message récupérés.",
  "data": {
    "id": 16,
    "courrier": {
      "id": 42,
      "reference": "21022026/REF1",
      "object": "Demande de bourse"
    },
    "expediteur": {
      "id": 1,
      "nom": "RAJAO",
      "prenom": "Emile"
    },
    "destinataire": {
      "id": 5,
      "nom": "RANDRIA",
      "prenom": "Mamy"
    },
    "isReadAt": "2026-02-23 10:00:00",
    "partagerAt": "2026-02-23 09:40:00",
    "observation": "<p>Bonjour, voici le dossier complet...</p>",
    "dateCreation": "2026-02-23 09:40:00",
    "fichiers": [
      {
        "id": 1,
        "nom": "PJ1.pdf",
        "type": "application/pdf"
      }
    ]
  }
}
```

#### Erreurs

| Code  | Message                                           | Cause                       |
|-------|---------------------------------------------------|-----------------------------|
| `403` | `Vous n'êtes pas autorisé à consulter ce message.` | Utilisateur non Participant |
| `404` | `Message avec l'ID X introuvable.`                | ID inexistant ou supprimé   |

---

### `POST /api/messages/transferer`

Transfère un courrier existant vers un utilisateur destinataire (crée un message de transfert).

- **Content-Type :** `multipart/form-data` ⚠️ (pour le champ `fichiers[]`)
- **Accès :** Tout utilisateur authentifié

#### Corps de la requête

```json
{
  "destId": 3,
  "courrierId": 42,
  "observation": "<p>Veuillez traiter ce dossier en priorité. Merci.</p>",
  "fichiers[]": "[Fichiers binaires]"
}
```

| Champ       | Type      | Requis | Description                       |
|-------------|-----------|--------|-----------------------------------|
| `destId`    | `integer` | ✅     | ID de l'utilisateur destinataire  |
| `courrierId`| `integer` | ✅     | ID du courrier à transférer       |
| `observation`| `string` | ❌     | Note/Instruction (Rich Text / HTML)|
| `fichiers[]` | `file[]`  | ❌     | Pièces jointes (max 5 Mo / fichier)|

#### Réponse — `200 OK`

```json
{
  "status": "success",
  "message": "Courrier transféré avec succès.",
  "data": null
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
  "message": "Message marqué comme lu.",
  "data": null
}
```

#### Erreurs

| `404` | `Message introuvable` | Aucun message avec cet ID  |

---

### `PATCH /api/messages/{id}/non-lu`

Marque un message spécifique comme **non lu** (réinitialise `isReadAt` à `null`).

- **Contrainte d'URL :** `{id}` doit être un entier positif
- **Accès :** Tout utilisateur authentifié

#### Réponse — `200 OK`

```json
{
  "status": "success",
  "message": "Message marqué comme non lu.",
  "data": null
}
```

---

### `DELETE /api/messages/{id}`

Supprime logiquement un message (Soft Delete). Le message n'apparaîtra plus dans les listes (`received`, `sent`, `all`).

- **Contrainte d'URL :** `{id}` doit être un entier positif
- **Accès :** Tout utilisateur authentifié

#### Réponse — `200 OK`

```json
{
  "status": "success",
  "message": "Message supprimé avec succès.",
  "data": null
}
```

---

## Récapitulatif des endpoints

| Méthode  | URL                                   | Content-Type            | Auth         | Description                              |
|----------|---------------------------------------|-------------------------|--------------|------------------------------------------|
| `POST`   | `/utilisateur/login`                  | `application/json`      | ❌ Public    | Connexion et obtention du JWT            |
| `GET`    | `/utilisateur`                        | —                       | ✅ Admin     | Liste tous les utilisateurs              |
| `POST`   | `/utilisateur`                        | `application/json`      | ✅ Admin     | Crée un nouvel utilisateur               |
| `GET`    | `/utilisateur/{id}`                   | —                       | ✅ Admin     | Détails d'un utilisateur                 |
| `POST`   | `/utilisateur/{id}`                   | `application/json`      | ✅ Admin     | Mise à jour d'un utilisateur             |
| `GET`    | `/api/courriers`                      | —                       | ✅ Token     | Liste paginée des courriers (Admin)      |
| `GET`    | `/api/courriers/getAllbyUser`         | —                       | ✅ Token     | Liste les courriers impliquant l'USER    |
| `POST`   | `/api/courriers/creer`                | `multipart/form-data`   | ✅ Token     | Crée un courrier                         |
| `POST`   | `/api/courriers/creerTransferer`      | `multipart/form-data`   | ✅ Token     | Crée un courrier et le transfère         |
| `GET`    | `/api/courriers/recherche`            | —                       | ✅ Token     | Recherche par nom ou prénom              |
| `GET`    | `/api/courriers/{id}`                 | —                       | ✅ Token     | Détails d'un courrier                    |
| `GET`    | `/api/courriers/{id}/messages`        | —                       | ✅ Token     | Liste les messages d'un courrier         |
| `POST`   | `/api/courriers/{id}/cloturer`        | —                       | ✅ Token     | Clôture un dossier et notifie par mail   |
| `GET`    | `/api/fichiers/{id}/download`         | —                       | ✅ Token     | Télécharge un fichier par son ID         |
| `DELETE` | `/api/courriers/{id}`                 | —                       | ✅ Token     | Supprime logiquement un courrier         |
| `GET`    | `/api/messages`                       | —                       | ✅ Token     | Liste les messages reçus (paginés)       |
| `GET`    | `/api/messages/{id}`                  | —                       | ✅ Token     | Détails d'un message (avec pièces jointes)|
| `POST`   | `/api/messages/transferer`            | `application/json`      | ✅ Token     | Transfère un courrier à un utilisateur   |
| `PATCH`  | `/api/messages/{id}/lire`             | —                       | ✅ Token     | Marque un message comme lu              |
| `PATCH`  | `/api/messages/{id}/non-lu`           | —                       | ✅ Token     | Marque un message comme non lu          |
| `DELETE` | `/api/messages/{id}`                 | —                       | ✅ Token     | Supprime logiquement un message (Soft Delete) |