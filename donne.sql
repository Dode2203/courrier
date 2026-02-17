
INSERT INTO role (id, name) VALUES (1, 'Admin');
INSERT INTO role (id, name) VALUES (2, 'Utilisateur');

INSERT INTO type_event (id, name) VALUES (1, 'Evenement');
INSERT INTO type_event (id, name) VALUES (2, 'Actualite');

INSERT INTO Status (id, name) VALUES (1, 'Actif');
INSERT INTO Status (id, name) VALUES (2, 'Inactif');


INSERT INTO Utilisateur (id, email, password, prenom, nom, is_active, is_admin)
VALUES (
    4,
    'admin@gmail.com',
    '$2y$10$Djns8FgsL.xk2GBACEtJh.Hs1civTyvdGQ9s6gqbSgDN81QkOHvTi',
    'admin',
    'admin',
    1,
    1
);

UPDATE utilisateur SET status_id = 2;


