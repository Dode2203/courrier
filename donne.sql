
INSERT INTO roles (id, name,date_creation) VALUES (1, 'Admin', NOW());
INSERT INTO roles (id, name,date_creation) VALUES (2, 'Utilisateur',NOW());


INSERT INTO utilisateurs
(id,role_id, date_creation, deleted_at, email, mdp, nom, prenom)
VALUES
(1,1, NOW(), NULL, 'test@gmail.com', '$2y$10$Djns8FgsL.xk2GBACEtJh.Hs1civTyvdGQ9s6gqbSgDN81QkOHvTi', 'Rakoto', 'Jean');


UPDATE utilisateur SET status_id = 2;


