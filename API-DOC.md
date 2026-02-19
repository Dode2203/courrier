# API Documentation - Système de Gestion de Courriers

Ce document regroupe tous les endpoints nécessaires pour tester le workflow complet du système.

## 1. Authentification

Permet d'obtenir le token JWT nécessaire pour les appels sécurisés.

- **URL** : `POST /api/utilisateur/login`
- **Headers** : `Content-Type: application/json`
- **Body JSON** :
  ```json
  {
    "email": "admin@example.com",
    "mdp": "password123"
  }
  ```
- **Réponse (200 OK)** :
  ```json
  {
    "status": "success",
    "data": {
      "membre": { "email": "...", "role": "Admin", ... },
      "token": "eyJ0eXAi..."
    }
  }
  ```

---

## 2. Utilisateurs

### Liste des utilisateurs (Admin seul)

- **URL** : `GET /utilisateur`
- **Headers** : `Authorization: Bearer <token>`
- **Réponse (200 OK)** :
  ```json
  {
    "status": "success",
    "data": [
      { "id": 1, "email": "admin@example.com", "nom": "DOE", "prenom": "John", "role": "Admin" }
    ]
  }
  ```

---

## 3. Courriers

### Création d'un courrier (avec fichier)

- **URL** : `POST /api/courriers/creer`
- **Headers** : `Authorization: Bearer <token>` (Ne pas forcer `Content-Type`)
- **Body (form-data)** :
  - `mail` : etudiant@email.com (string)
  - `object` : Demande de relevé (string)
  - `description` : Détails de la demande... (string)
  - `fichier` : [Fichier PDF/Image] (File, max 5 Mo)
- **Comportement** : Génère automatiquement une référence (format `dmY/REFX`).
- **Réponse (201 Created)** :
  ```json
  {
    "status": "success",
    "data": { "id": 15, "reference": "19022026/REF1" }
  }
  ```

### Détails d'un courrier

- **URL** : `GET /api/courriers/{id}`
- **Headers** : `Authorization: Bearer <token>`
- **Réponse (200 OK)** : Renvoie l'objet complet (id, reference, object, description, mail, dateCreation, dateFin).

### Récupération du fichier

- **URL** : `GET /api/courriers/{id}/fichier`
- **Requirements** : `{id}` doit être un nombre entier.
- **Headers** : `Authorization: Bearer <token>`
- **Réponse** :
  - **Success** : Le flux binaire du fichier (affiche le PDF ou l'image directement).
  - **Error (404)** : Si le courrier n'a pas de fichier associé.

---

## 4. Messages

### Transférer un courrier

Envoie un message interne à un autre utilisateur.

- **URL** : `POST /api/messages/transferer`
- **Headers** : `Authorization: Bearer <token>`, `Content-Type: application/json`
- **Body JSON** :
  ```json
  {
    "destId": 2,
    "courrierId": 15
  }
  ```

### Créer et transférer (avec fichier)

- **URL** : `POST /api/courriers/creer-et-transferer`
- **Headers** : `Authorization: Bearer <token>`
- **Body (form-data)** :
  - `mail` : etudiant@email.com (string)
  - `object` : Demande de relevé (string)
  - `description` : Détails de la demande... (string)
  - `destId` : 5 (int)
  - `fichier` : [Fichier] (File, optionnel)

### Liste de réception

Récupère les messages reçus par l'utilisateur connecté avec support de la pagination.

- **URL** : `GET /api/messages?page=1&limit=10`
- **Headers** : `Authorization: Bearer <token>`
- **Réponse Succès** :
  ```json
  {
    "status": "success",
    "data": {
      "messages": [
        {
          "id": 1,
          "courrier": {
            "id": 15,
            "reference": "20240219/REFX",
            "object": "Demande de relevé de notes"
          },
          "expediteur": {
            "id": 1,
            "nom": "DOE",
            "prenom": "John"
          },
          "destinataire": {
            "id": 2,
            "nom": "SMITH",
            "prenom": "Jane"
          },
          "isReadAt": null,
          "dateCreation": "2024-02-19 10:05:00"
        }
      ],
      "page": 1,
      "limit": 10
    }
  }
  ```

### Liste des messages reçus

- **URL** : `GET /api/messages`
- **Headers** : `Authorization: Bearer <token>`
- **Réponse (200 OK)** : Liste des messages avec détails du courrier et de l'expéditeur.

### Marquer comme lu

- **URL** : `PATCH /api/messages/{id}/lire`
- **Headers** : `Authorization: Bearer <token>`

---

## 5. Clôture

### Clôturer et notifier

Ferme le dossier et envoie un mail automatique à l'étudiant.

- **URL** : `POST /api/courriers/{id}/cloturer`
- **Headers** : `Authorization: Bearer <token>`
- **Comportement** :
  1. Met à jour `dateFin` dans la base.
  2. Envoie un mail à l'adresse liée au courrier.
- **Réponse (200 OK)** :
  ```json
  {
    "status": "success",
    "data": { "message": "Le dossier a été clôturé..." }
  }
  ```
```

**Modifications apportées :**
- Structure hiérarchique cohérente avec des titres de niveaux 2 et 3
- Formatage correct des blocs de code avec les backticks
- Alignement uniforme des listes non ordonnées
- Suppression des commentaires superflus et du texte en double