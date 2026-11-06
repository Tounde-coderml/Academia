# Plan d’implémentation — M4 Épreuves et fichiers

> Version 1.5 — 6 novembre 2026  
> Statut : M4 implémenté et validé à 100 % sur l’environnement local ; 20 Mo vérifiés par requête HTTP réelle.  
> Projet : Academia — Module 4 de l’architecture de la Bibliothèque des Épreuves ESGC VAK.

## 1. Identification

| Élément | Valeur |
| --- | --- |
| Module | M4 — Épreuves et fichiers |
| Auteur | Équipe Academia |
| Validateur métier | Demandeur / responsable métier |
| Date | 6 novembre 2026 |
| Version | 1.5 |
| Statut | Validé à 100 % le 6 novembre 2026 sur l’environnement local |

M4 possède les métadonnées d’épreuve, le fichier privé, les statuts de publication et la file de validation des dépôts étudiants. M6 lira les épreuves publiées et demandera l’aperçu ou le téléchargement autorisé. M3 reste propriétaire des filières, niveaux, matières, associations et semestres.

## 2. Contexte et objectifs

M4 permet aux utilisateurs autorisés de déposer une épreuve avec ses métadonnées et son document. Il doit conserver le fichier hors de l’espace public, contrôler son format et sa taille, et exposer les données uniquement selon leur statut et les permissions du demandeur.

Objectifs :

- Enregistrer titre, filière, niveau, matière, année scolaire, semestre, type, auteur, date et état.
- Accepter PDF, DOC, JPG et PNG jusqu’à 20 Mo ; DOCX est exclu selon la décision du demandeur.
- Mettre en attente les dépôts étudiants pour revue par un responsable de classe ou un administrateur ; publier directement les dépôts des rôles supérieurs autorisés, sauf doublon non confirmé.
- Proposer une file de revue pour publier un dépôt ou le refuser avec motif obligatoire.
- Conserver les épreuves refusées avec leur motif et contrôler toute modification ou suppression par le serveur.
- Fournir un contrôle serveur aux consommateurs M5 et M6 sans exposer le chemin physique du fichier.

## 3. Périmètre

### Inclus dans M4

- Dépôt, stockage privé et gestion des métadonnées d’épreuve.
- Statuts `pending`, `published` et `rejected`, auteur et dates associées.
- Liste personnelle des dépôts et suivi de leur état.
- Publication directe selon le rôle, après validation serveur et contrôles de sécurité.
- Gestion administrative des épreuves enregistrées, réservée à l’administrateur.
- Protection d’accès au fichier : auteur, personnes habilitées à examiner un dépôt en attente et utilisateurs autorisés pour les fichiers publiés.
- Calcul ou fourniture de l’année scolaire, sans écran d’administration, selon la règle R10.
- Détection des correspondances potentielles selon matière, année scolaire, type et empreinte du fichier.

### Hors périmètre de M4

- Gestion des filières, niveaux, matières et semestres : M3.
- Affectation d’un responsable de classe à ses étudiants : non requise pour la file globale approuvée par le demandeur.
- Catalogue de recherche, pages de résultat, aperçu et téléchargement destinés aux utilisateurs : M6, qui s’appuiera sur le service M4.
- Favoris et historique : M7.
- Rattachement des comptes à une classe : hors périmètre actuel, puisque le demandeur a choisi une file globale.

## 4. Existant, stack et contraintes

| Élément | État observé | Conséquence |
| --- | --- | --- |
| Backend | Laravel 13, contrôleurs, Form Requests, Inertia et Vue 3 | Suivre les conventions existantes et valider toute entrée côté serveur. |
| Authentification | M1, rôles Spatie et compte vérifié requis pour les routes privées | Définir une permission serveur pour le dépôt, la revue, la gestion et la lecture publiée. |
| Référentiels | M3 expose filières, niveaux, matières, associations et semestres S1/S2 | Réutiliser ces valeurs et refuser les références inactives ou incohérentes. |
| Stockage | Disque `local` pointant vers `storage/app/private` ; disque `public` distinct | Garder les fichiers M4 sur le disque privé ; aucun lien direct public. |
| Base de données | MySQL, tests d’intégration disponibles | Indexer les requêtes par état, matière, année, type et date ; clés étrangères restrictives. |
| Configuration PHP | `.user.ini` configure PHP-FPM et la commande `serve:uploads` passe 20/25 Mo au processus serveur local | Un fichier HTTP de 20 Mo atteint la route M4 sans rejet de taille ; un proxy de production devra aussi autoriser 25 Mo. |
| État M4 | Modèle, migration, dépôt privé, formulaire, suivi, revue, gestion administrative, accès tableau de bord et gestion des uploads implémentés | Le catalogue M6 est un module séparé et consommera la route de fichier contrôlée. |
| Vérification au 6 novembre 2026 | Suite PHP complète : 79 tests / 684 assertions ; 17 tests M4 ciblés / 140 assertions ; Pint, typecheck, ESLint et build Vite réussis | Validé à 100 % sur l’environnement local ; la limite de requête du proxy de production est propre à son déploiement. |

## 5. Règles métier et accès

Règles explicitement présentes dans le cahier des charges :

| ID | Règle | Base de contrôle prévue |
| --- | --- | --- |
| R01 | Le dépôt contient titre, filière, niveau, matière, année scolaire, semestre, type et fichier. | Form Request, références actives M3 et contraintes de données. |
| R02 | Un étudiant peut déposer ; son dépôt reste en attente jusqu’à validation par un responsable de classe ou un administrateur. | Permission `exams.submit`, statut `pending`, droits `exams.review` et filtrage M6. |
| R03 | Responsable de classe, Administration et Administrateur publient directement après contrôles. | Permission `exams.submit`, statut `published` et date de publication atomique ; les doublons exigent confirmation. |
| R04 | Un refus requiert un motif communiqué au déposant. | Validation du motif par M4 et conservation dans l’épreuve. |
| R05 | Une matière liée à une épreuve ne peut pas être supprimée. | Clés étrangères restrictives et coordination avec M3. |
| R06 | Une épreuve se classe par titre, matière, année scolaire, semestre, type et fichier. | Colonnes dédiées et relations M3. |
| R07 | Un doublon exact correspond à la même matière, année, type et empreinte du fichier ; une confirmation est requise avant publication. | Empreinte SHA-256 et confirmation explicite à l’envoi direct ou à la validation. |
| R08 | Les formats admis sont PDF, DOC, JPG et PNG ; DOCX est exclu ; maximum 20 Mo. | Validation de type MIME et de taille, puis stockage privé. |
| R09 | L’année scolaire est prévue dans le système sans écran d’administration et commence en septembre. | Liste calculée selon l’année civile et le mois courant. |

### État et droits prévus

| Acteur | Dépôt | Lecture de son dépôt | Fichier en attente | Épreuve publiée | Modifier/supprimer une épreuve enregistrée |
| --- | --- | --- | --- | --- | --- |
| Étudiant | Oui ; dépôt en attente | Oui, ses propres dépôts | Oui, son propre dépôt | Oui, si permission de consultation publiée | Non |
| Responsable de classe | Oui selon D3 | Oui, ses propres dépôts | Oui via `exams.review` pour tous les dépôts étudiants | Oui | Non |
| Administration | Oui selon D3 | Oui, ses propres dépôts | Oui via `exams.review` | Oui | Non |
| Administrateur | Oui selon D3 | Oui | Oui via `exams.review` | Oui | Oui, permission dédiée `exams.manage` |

L’accès à un fichier ne dépend jamais d’un chemin fourni par le client. Le client transmet uniquement l’identifiant d’épreuve ; le serveur vérifie rôle, statut et relation avec l’auteur avant de lire le stockage privé.

## 6. Données et interfaces prévues

### Entité `Epreuve`

| Champ prévu | Signification | Contraintes |
| --- | --- | --- |
| `id` | Identifiant d’épreuve | Clé primaire ; seul identifiant utilisé dans les routes de fichier. |
| `user_id` | Auteur du dépôt | Référence vers `users`, conservée avec l’auteur. |
| `filiere_id`, `niveau_id`, `matiere_id` | Classement pédagogique | Références actives M3 ; le triplet doit exister dans les associations. |
| `title` | Titre affiché | Obligatoire, texte borné. |
| `academic_year` | Année scolaire | Format `YYYY-YYYY`, deux années consécutives ; début en septembre. |
| `semester` | Semestre | S1 ou S2, fourni par M3. |
| `type` | Type de l’épreuve | `examen`, `rattrapage`, `devoir`, `controle_continu`. |
| `status` | État du cycle de vie | `pending`, `published` ou `rejected`. |
| `rejection_reason` | Motif de refus | Obligatoire quand `status=rejected`; le parcours de revue M4 le renseigne. |
| `submitted_at`, `published_at` | Dates du dépôt et de publication | Horodatage serveur ; publication directe journalisée. |
| `file_path` | Chemin relatif privé | Généré par le serveur, jamais fourni par le navigateur. |
| `original_filename` | Nom présenté au téléchargement | Donnée non fiable ; neutralisée et échappée à l’affichage. |
| `mime_type`, `file_size`, `file_sha256` | Métadonnées techniques du fichier | Contrôle MIME, taille 1–20 Mo, empreinte pour les doublons. |
| timestamps | Création et dernière mise à jour | Fournis par Eloquent. |

### Routes candidates

| Route | Accès |
| --- | --- |
| `GET /epreuves/deposer` | Utilisateur connecté et autorisé à déposer. |
| `POST /epreuves` | Dépôt, validation du fichier et application du statut selon le rôle. |
| `GET /mes-epreuves` | Liste de ses dépôts, quel que soit le statut. |
| `GET /admin/revue-epreuves` | Responsable de classe, Administration ou Administrateur avec `exams.review`. |
| `POST /admin/revue-epreuves/{epreuve}/publier`, `POST /admin/revue-epreuves/{epreuve}/refuser` | Décision de revue ; refus avec motif obligatoire ; doublon à confirmer avant publication. |
| `GET /admin/epreuves` | Administrateur avec `exams.manage`. |
| `GET /admin/epreuves/{epreuve}/modifier`, `PUT /admin/epreuves/{epreuve}`, `DELETE /admin/epreuves/{epreuve}` | Modification et suppression par l’administrateur uniquement. |
| `GET /epreuves/{epreuve}/fichier` | Lecture contrôlée pour l’auteur, le validateur ou les utilisateurs autorisés sur une épreuve publiée. Aucun chemin libre. |

La file M4 traite les décisions de publication et de refus des dépôts étudiants. M5 peut consommer les statuts et intégrer les règles de rattachement de classe lorsqu’elles seront définies.

## 7. Qualité, sécurité et risques

- Contrôler le MIME réel, l’extension attendue et la taille côté serveur ; ne jamais faire confiance au seul nom envoyé par le navigateur.
- Générer un nom aléatoire non devinable et stocker dans `storage/app/private`, jamais sous `public`.
- Ne pas journaliser les jetons de réinitialisation, contenu des fichiers ou chemins privés.
- Autoriser l’aperçu inline uniquement pour les types sûrs ; forcer le téléchargement pour les documents Word, avec `X-Content-Type-Options: nosniff` et réponses privées.
- Mettre à jour le fichier et les métadonnées de façon cohérente ; supprimer le fichier stocké si la transaction d’écriture échoue.
- Refuser les références M3 inactives ou les combinaisons filière/niveau/matière inexistantes.
- Limiter la fréquence et la taille des dépôts ; configurer le serveur web et PHP au-dessus de 20 Mo.
- Protéger la suppression des épreuves par confirmation et vérifier l’auteur / la permission au serveur.
- Le périmètre d’accès des responsables de classe reste ouvert dans CA-011-04 ; la file est actuellement globale.

## 8. Questions et arbitrages en attente

| ID | Question | Source du conflit / impact | État |
| --- | --- | --- | --- |
| Q01 | Les étudiants peuvent-ils déposer ? | CDC D1/US-009 dit « Tous » et la matrice M1 précédente excluait les étudiants. | Décidé : oui ; leurs dépôts restent en attente jusqu’à validation par un responsable de classe ou un administrateur. |
| Q02 | Quel mois commence l’année scolaire automatique ? | R10 interdit un écran de gestion ; l’année de dépôt doit être calculée et les options M6 doivent être cohérentes. | Décidé : septembre. |
| Q03 | Si un dépôt d’un rôle à publication directe est un doublon exact, faut-il demander confirmation ou l’envoyer en revue ? | CDC D3/US-013 exige la publication immédiate ; R8/US-010 exige un avertissement avant publication. | Décidé : afficher l’alerte et demander confirmation explicite avant publication. |
| Q04 | Le responsable de classe examine-t-il toutes les soumissions ou seulement celles de sa classe ? | CA-011-04 laissait cette règle à confirmer ; aucun rattachement à une classe n’existe dans le modèle actuel. | Décidé le 6 novembre 2026 : un responsable de classe voit tous les dépôts étudiants. |

## 9. Étapes d’implémentation et validation

### Étape 0 — Confirmer le périmètre

Fixer le périmètre et confirmer les permissions M4 à partir des règles R01–R09, de la matrice M1 et des contrats M3/M5/M6.

**État :** Q01 à Q04 ont été décidées le 6 novembre 2026.

### Étape 1 — Créer le modèle et le stockage privé

Créer la migration `epreuves`, les relations M3, le modèle, les enums de type/statut, un service d’année scolaire, le service de stockage privé et les factories. Ajouter la permission `exams.manage` au seul rôle administrateur. Les tests couvrent les contraintes, états, références, empreintes et suppression.

**État :** modèle, migration, relations, empreinte et fichier privé implémentés. `exams.manage` est réservé au rôle administrateur.

### Étape 2 — Implémenter le dépôt sécurisé

Créer Form Request et action de dépôt. Valider les champs, l’association active, le semestre, l’année, le MIME, l’extension et 20 Mo. Générer un nom opaque, stocker dans le disque privé et créer l’épreuve avec statut déterminé par le rôle selon D2/D3.

**État :** formulaire, suivi personnel et téléchargement privé implémentés. Les dépôts étudiants restent en attente ; les rôles supérieurs autorisés publient directement. Un doublon direct exige une confirmation explicite.

### Étape 3 — Ajouter les écrans de suivi et la gestion administrative

Construire le formulaire de dépôt, la liste personnelle, les états pending/published/rejected et le parcours administrateur de modification/suppression. Ne pas exposer les dépôts non publiés dans le catalogue M6.

**État :** formulaire Inertia/Vue, liste personnelle, file de revue et gestion administrative ajoutés. L’administrateur peut modifier ou supprimer une épreuve ; la suppression est confirmée dans l’interface.

### Étape 4 — Relier les consommateurs M5 et M6, recetter et accepter

Fournir un service d’accès aux fichiers contrôlé par statut, auteur et permission. Vérifier la disponibilité de la file de revue M5 et la lecture des seules épreuves publiées par M6. Tester les doublons, permissions, fichiers malveillants/invalides, tailles limites, intégrité des échecs et navigation sur navigateur.

**État :** tous les critères M4 sont recettés localement : suite complète de 78 tests / 666 assertions, 16 tests M4 / 122 assertions, Pint, typecheck, ESLint et build Vite réussis. Une requête HTTP réelle de 20 Mo atteint la route sans rejet de taille (419 CSRF attendu faute de session), confirmant la réception par PHP. M4 est validé à 100 % sur cet environnement. En production, le reverse proxy doit accepter un corps d’au moins 25 Mo.

## 10. Critères d’acceptation

- **CA-M4-01 — Dépôt complet :** les champs, références M3, semestre et fichier requis sont contrôlés serveur.
- **CA-M4-02 — Fichiers :** PDF, DOC, JPG et PNG admis jusqu’à 20 Mo ; DOCX, type interdit et fichier trop gros refusés ; fichier non public.
- **CA-M4-03 — Cycle étudiant :** un dépôt étudiant reste en attente jusqu’à validation et n’apparaît pas dans le catalogue publié.
- **CA-M4-04 — Publication directe :** les rôles D3 publiant directement obtiennent `published` après contrôles ; un doublon exact exige confirmation.
- **CA-M4-05 — Suivi :** l’auteur peut retrouver ses propres dépôts et statuts ; les autres utilisateurs ne peuvent pas lire les dépôts privés.
- **CA-M4-06 — Administration :** seul l’administrateur peut modifier ou supprimer une épreuve enregistrée ; suppression confirmée côté interface et vérifiée serveur.
- **CA-M4-07 — Fichiers :** le chemin privé n’est jamais fourni au client ; accès conditionné au rôle, à l’état et à l’auteur.
- **CA-M4-08 — Année scolaire :** valeur cohérente avec la convention du demandeur et sans écran de gestion.
- **CA-M4-09 — Doublons :** correspondances basées sur matière, année, type et empreinte ; avertissement conforme à Q03, sans remplacement automatique.
- **CA-M4-10 — Intégration :** la file M4 donne les dépôts en attente aux réviseurs autorisés et M6 ne reçoit que les épreuves publiées.

## 11. Journal des décisions

| Date | Décision / constat | Décideur | Preuve |
| --- | --- | --- | --- |
| 6 novembre 2026 | Cadrage M4 créé à partir du cahier des charges, des règles R5–R12 et des contrats M3/M6. Aucun code M4 n’existait dans le dépôt. | Équipe Academia | Inspection du dépôt et du cahier des charges ESGC VAK. |
| 6 novembre 2026 | Les formats PDF, DOC, JPG et PNG (sans DOCX) et la limite de 20 Mo sont retenus ; le CLI reste limité à 2 Mo, avec un réglage `.user.ini` prévu pour PHP-FPM. | Équipe Academia | Décision du demandeur, CA-009-03/04 et `php -i`. |
| 6 novembre 2026 | Le modèle, stockage privé, formulaire, suivi, revue, gestion administrative et téléchargement protégé ont été ajoutés. | Équipe Academia | Code M4 et tests Feature. |
| 6 novembre 2026 | Le demandeur autorise le dépôt étudiant sous validation, fixe septembre comme début de l’année scolaire, exclut DOCX et exige une confirmation explicite pour publier un doublon. | Demandeur | Réponses de cadrage M4 du 6 novembre 2026. |
| 6 novembre 2026 | File de revue, publication, refus avec motif, droits étudiants, confirmation de doublon et gestion admin implémentés. | Équipe Academia | Tests `EpreuveTest` et `AuthorizationTest` : 12 réussis, 71 assertions. |
| 6 novembre 2026 | Les responsables de classe peuvent voir et traiter tous les dépôts étudiants, sans filtre de classe. | Demandeur | Réponse de cadrage Q04 du 6 novembre 2026 ; test de portée globale réussi. |
| 6 novembre 2026 | Vérifications M4 : 16 tests ciblés (122 assertions), formatage Pint, typage Vue, lint ESLint, build Vite et test HTTP réel de 20 Mo réussis. | Équipe Academia | Tests, contrôles frontend et requête POST temporaire vers le serveur local. |
| 6 novembre 2026 | Suite PHP complète réussie : 79 tests et 684 assertions. | Équipe Academia | `php artisan test --compact`. |
| 6 novembre 2026 | M4 validé à 100 % sur l’environnement local. Le plafond du reverse proxy de production reste un paramètre d’exploitation. | Équipe Academia | Critères CA-M4-01 à CA-M4-10 et preuves de vérification ci-dessus. |
| 6 novembre 2026 | Corriger l’erreur 500 sur `/mes-epreuves` : appliquer les migrations M3 dépendantes et M4, puis synchroniser les permissions M4 dans la base `academia`. | Équipe Academia | `migrate:status`, `db:table epreuves` et requête du compte 12 réussis. |
| 6 novembre 2026 | Retirer « Choisir » des listes Filière et Niveau ; afficher les codes GI, GT, GC puis L1, L2, L3, et fournir ces valeurs si elles sont absentes d’une base neuve. | Demandeur | Réponses du 6 novembre 2026 et test `test_submission_form_displays_requested_filiere_and_niveau_codes`. |

