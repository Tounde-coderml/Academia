# Plan d’implémentation — M3 Catalogue pédagogique

> Version 1.1 — 6 novembre 2026  
> Statut : validé à 100 % le 6 novembre 2026 — périmètre confirmé, tests réussis et recette navigateur effectuée.  
> Projet : Academia — Module 3 de l’architecture de la Bibliothèque des Épreuves ESGC VAK.

## 1. Identification

| Élément | Valeur |
| --- | --- |
| Module | M3 — Catalogue pédagogique |
| Auteur | Équipe Academia |
| Validateur métier | Demandeur |
| Date | 6 novembre 2026 |
| Version | 1.1 |
| Statut | Module validé à la demande du demandeur le 6 novembre 2026 |

Ce plan suit la trame des plans M1 et M2 et reprend la responsabilité attribuée à M3 dans l’architecture : gérer les filières, les niveaux, les matières et les critères de classement. Le demandeur a confirmé le 6 novembre 2026 que M3 doit aussi fournir les semestres S1 et S2. Ces deux valeurs sont fixes et exposées par l’endpoint des référentiels actifs ; elles n’ont pas d’écran d’administration. Le cahier des charges prévoit l’année scolaire dans l’application sans écran d’administration ; sa valeur relève des métadonnées d’épreuve (M4), et non d’un référentiel administrable M3. Les classes et rattachements de profils restent hors de ce périmètre.

## 2. Contexte, problème et objectifs

La Bibliothèque des Épreuves a besoin de valeurs pédagogiques cohérentes pour classer les épreuves et alimenter les filtres du catalogue. M3 est responsable des référentiels partagés par les autres modules. Il doit permettre aux personnes autorisées de tenir ces valeurs à jour sans casser les associations déjà utilisées.

Objectifs :

- Gérer les filières, les niveaux et les matières avec un code unique et un libellé.
- Fournir les deux semestres de classement S1 et S2 aux modules consommateurs.
- Associer une matière à une filière et à un niveau.
- Empêcher les doublons et préserver les références déjà utilisées.
- Exposer aux utilisateurs authentifiés les seules valeurs actives et les seules données nécessaires aux filtres.
- Réserver les opérations d’administration aux utilisateurs ayant la permission dédiée.

### Histoires utilisateur

- Comme administrateur autorisé, je veux créer, consulter, modifier et désactiver un référentiel afin de maintenir le classement pédagogique.
- Comme administrateur autorisé, je veux associer une matière à une filière et à un niveau afin de décrire les parcours proposés.
- Comme utilisateur connecté, je veux recevoir les référentiels actifs afin de filtrer le catalogue.
- Comme responsable métier, je veux que la désactivation ou la suppression d’une valeur ne rende pas incohérentes les épreuves qui la référencent.

## 3. Périmètre

### Inclus dans M3

- Consultation, création, modification, suppression ou désactivation des filières, niveaux et matières.
- Recherche, filtre d’état et pagination des listes d’administration.
- Gestion de l’ordre d’affichage des niveaux.
- Association et dissociation d’une matière avec un couple filière/niveau.
- Exposition des valeurs actives et des associations actives aux modules consommateurs.
- Exposition des semestres fixes S1 et S2 par le même contrat de référentiels.
- Autorisation serveur des actions de gestion et validation des entrées.

### Hors périmètre de M3 à ce stade

- Création, dépôt, stockage, publication ou validation d’épreuves : M4 et M5.
- Recherche et consultation des épreuves : M6.
- Affectation de profils pédagogiques aux comptes utilisateurs : à cadrer avec M2.
- Administration des semestres : les deux valeurs S1 et S2 sont fixes, confirmées par le demandeur et fournies par le code.
- Gestion des années académiques, classes, unités d’enseignement, crédits ou coefficients : hors M3 ; l’année scolaire est une métadonnée d’épreuve gérée par M4.
- Import/export en masse et synchronisation avec un système externe : non spécifiés.

## 4. Existant, stack et contraintes

L’état ci-dessous décrit le dépôt au 6 novembre 2026. Il ne vaut pas acceptation fonctionnelle.

| Élément | État observé | Conséquence |
| --- | --- | --- |
| Backend | Laravel 13, contrôleurs d’administration et Form Requests présents | Maintenir les conventions Laravel existantes et la validation côté serveur. |
| Interface | Inertia et Vue 3 ; listes, formulaires et associations présents | Vérifier les parcours clavier, erreurs et petits écrans en recette navigateur. |
| Données | Tables `filieres`, `niveaux`, `matieres` et `filiere_niveau_matiere` présentes | Les trois codes sont uniques ; le triplet filière/niveau/matière est unique. |
| Autorisations | Routes protégées par `auth`, `verified` et `academic_referentials.manage` | Les contrôles doivent rester côté serveur, y compris pour les requêtes directes. |
| Consommation | `GET /referentiels/actifs` expose les référentiels actifs et les semestres S1/S2 | Les modules consommateurs utilisent le même contrat sans recopier les référentiels. |
| Tests backend | 14 tests Feature dans `tests/Feature/ReferentielsTest.php` | 14 tests réussis, 63 assertions, le 6 novembre 2026 sur MySQL 8.4 éphémère isolé. |
| Vérifications frontend | 8 tests unitaires, lint, typage et build réussis le 6 novembre 2026 | Le contrôle global Prettier reste bloqué par `Auth/VerifyEmail.vue`, hors périmètre M3 ; les trois vues M3 passent le contrôle ciblé. |

### Principaux fichiers concernés

- `app/Models/Filiere.php`, `Niveau.php`, `Matiere.php` et `FiliereNiveauMatiere.php`
- `app/Http/Controllers/Admin/FiliereController.php`, `NiveauController.php`, `MatiereController.php`
- `app/Http/Controllers/ReferentielValuesController.php`
- `app/Http/Requests/ReferentielRequest.php` et les Form Requests spécialisées
- `database/migrations/2026_10_27_080139_create_filieres_table.php` à `2026_10_27_080142_create_filiere_niveau_matiere_table.php`
- `resources/js/pages/Referentiels/ReferentielsIndex.vue`, `ReferentielForm.vue` et `MatiereAssociations.vue`
- `routes/web.php` et `tests/Feature/ReferentielsTest.php`

## 5. Règles métier et autorisations

Les règles suivantes sont implémentées dans le code actuel. Elles doivent être confirmées par le responsable métier pendant le cadrage.

| ID | Règle observée | Contrôle présent |
| --- | --- | --- |
| R01 | Le code d’une filière, d’un niveau ou d’une matière est obligatoire et unique. | Normalisation par suppression des espaces en début/fin, validation et contrainte unique en base. |
| R02 | Un niveau possède un ordre de tri numérique, utilisé pour l’affichage. | Champ `sort_order`, entier non signé, valeur par défaut 0. |
| R03 | Une association relie exactement une filière, un niveau et une matière ; un même triplet ne peut exister deux fois. | Clés étrangères et contrainte unique en base ; contrôle applicatif avant insertion. |
| R04 | Une nouvelle association exige des référentiels actifs. | Form Request, puis vérification sous transaction côté contrôleur. |
| R05 | Une valeur utilisée ne doit pas être physiquement supprimée. | La suppression demandée désactive la valeur utilisée ; les clés étrangères restreignent les suppressions incohérentes. |
| R06 | Le code d’une valeur déjà utilisée ne peut pas changer. | Vérification sous transaction et verrouillage de la ligne. |
| R07 | Les listes d’administration peuvent afficher les valeurs actives et inactives ; les consommateurs ne reçoivent que les valeurs actives. | Filtre d’état en administration ; endpoint public filtré sur `is_active`. |
| R08 | Seul un utilisateur authentifié, vérifié et doté de `academic_referentials.manage` peut administrer les référentiels. | Middleware des routes et autorisation des Form Requests. |
| R09 | Les réponses destinées aux filtres n’exposent que les champs utiles : identifiant, code, nom et ordre de tri des niveaux. | Projection explicite dans le contrôleur de valeurs actives. |
| R10 | Le classement par semestre utilise deux valeurs stables : S1 (identifiant 1) et S2 (identifiant 2). | Enum `App\Semestre`, inclus dans `GET /referentiels/actifs` et testé. Pas de CRUD pour cette liste fixe. |

### Matrice d’accès observée

| Action | Visiteur | Utilisateur authentifié sans permission | Utilisateur avec `academic_referentials.manage` |
| --- | --- | --- | --- |
| Lire les valeurs actives | Refusé / redirection vers connexion | Autorisé si compte vérifié | Autorisé si compte vérifié |
| Consulter l’administration | Refusé / redirection vers connexion | Refusé (403) | Autorisé si compte vérifié |
| Créer, modifier, désactiver, supprimer ou associer | Refusé / redirection vers connexion | Refusé (403) | Autorisé si compte vérifié |

## 6. Données, interfaces et états

### Données

| Entité | Champs observés | Contraintes observées |
| --- | --- | --- |
| Filière | `id`, `code`, `name`, `is_active`, timestamps | Code unique ; active par défaut. |
| Niveau | `id`, `code`, `name`, `sort_order`, `is_active`, timestamps | Code unique ; ordre par défaut 0 ; actif par défaut. |
| Matière | `id`, `code`, `name`, `is_active`, timestamps | Code unique ; active par défaut. |
| Association | `filiere_id`, `niveau_id`, `matiere_id`, timestamps | Clés étrangères restrictives ; triplet unique. |
| Semestre de classement | `id` (1 ou 2), `code` (S1 ou S2), `name` | Valeurs fixes fournies par `App\Semestre` ; aucune table ni écran CRUD. |

Les champs `code` et `name` des référentiels persistés sont des chaînes obligatoires (255 caractères maximum dans les requêtes). Les codes sont rognés des espaces en début et fin et doivent être uniques. Les semestres sont des valeurs fixes. L’année académique n’est pas administrée par M3.

### Routes observées

Les routes d’administration sont sous le préfixe `/admin`, le middleware `auth`, `verified`, `can:academic_referentials.manage` et les noms `admin.*`.

| Fonction | Routes nommées |
| --- | --- |
| CRUD filières | `admin.filieres.index`, `.create`, `.store`, `.edit`, `.update`, `.destroy` |
| Activer / désactiver une filière | `admin.filieres.reactivate`, `.deactivate` |
| CRUD niveaux | `admin.niveaux.index`, `.create`, `.store`, `.edit`, `.update`, `.destroy` |
| Activer / désactiver un niveau | `admin.niveaux.reactivate`, `.deactivate` |
| CRUD matières | `admin.matieres.index`, `.create`, `.store`, `.edit`, `.update`, `.destroy` |
| Activer / désactiver une matière | `admin.matieres.reactivate`, `.deactivate` |
| Associations d’une matière | `admin.matieres.associations`, `.associations.store`, `.associations.destroy` |
| Valeurs actives et semestres pour les consommateurs | `GET /referentiels/actifs`, nom `referentiels.actifs` |

Les routes de ressources excluent `show`. La route de valeurs actives exige `auth` et `verified`, sans permission d’administration.

## 7. Qualité, sécurité et risques

- Garder les autorisations sur le serveur ; masquer un bouton dans Vue ne constitue pas un contrôle d’accès.
- Valider les identifiants et états côté serveur et s’appuyer sur les contraintes de base contre les doublons concurrents.
- Garder la transaction et les verrous lors du changement de code ou de la suppression logique par désactivation.
- Ne pas retourner de référentiel inactif aux modules consommateurs.
- Éviter de supprimer une association ou une valeur encore référencée par une épreuve ; M4 devra définir les dépendances et règles de conservation avant sa livraison.
- Les valeurs inactives restent conservées ; elles et leurs associations sont exclues de l’endpoint des valeurs actives. M4 devra empêcher la dissociation d’une association référencée par une épreuve.

## 8. Questions ouvertes et décisions

| ID | Question | Impact | Décideur | État |
| --- | --- | --- | --- | --- |
| Q01 | Le semestre fait-il partie des critères gérés par M3 ? | Contrat M3/M4/M6 | Demandeur | Décidé : oui, S1 et S2 fixes, 6 novembre 2026. |
| Q02 | Les classes et rattachements de profils sont-ils inclus dans le premier incrément ? | Modèle M2/M3 | Responsable métier | Hors périmètre du premier incrément M3 ; aucun besoin ferme ne les impose. |
| Q03 | L’année scolaire est-elle un référentiel administrable ? | Contrat M3/M4 | Cahier des charges | Non : elle est prévue sans écran de gestion et reste une métadonnée d’épreuve M4. |
| Q04 | Quels sont les formats et règles de normalisation des codes ? | Validation et unicité | Équipe Academia, règle appliquée | Chaîne requise, maximum 255 caractères, espaces extérieurs supprimés, unicité. |
| Q05 | Une matière peut-elle être partagée entre plusieurs filières et niveaux ? | Relation et règles d’association | Cahier des charges | Oui : association par triplet filière/niveau/matière, avec triplets uniques. |
| Q06 | Qui reçoit `academic_referentials.manage` ? | Administration des rôles M2 | Équipe Academia, matrice existante | Rôle `administrateur` selon le seeder M2. |

## 9. Étapes d’implémentation et de validation

### Étape 0 — Confirmer le cadrage métier

**Statut :** périmètre principal confirmé le 6 novembre 2026.

Le demandeur a confirmé les référentiels filière/niveau/matière et l’ajout des semestres fixes S1/S2. Le cahier des charges place l’année scolaire dans le système sans gestion par écran ; les classes et rattachements de profils ne sont pas requis dans le premier incrément M3.

**Critères de sortie :** périmètre M3 accepté ; semestres S1/S2 définis ; année scolaire et responsabilités M2/M4/M6 séparées de M3.

### Étape 1 — Vérifier le modèle, les contraintes et les autorisations

**Statut :** tests exécutés avec succès le 6 novembre 2026.

Vérifier les migrations, relations, unicité, clés étrangères, permission dédiée, protection des routes et règles de suppression. Exécuter les scénarios Feature sur MySQL, notamment les doublons applicatifs et en base, les utilisateurs non autorisés, les valeurs utilisées et les associations invalides.

**Critères de sortie :** migrations applicables sur une base MySQL isolée ; tests backend M3 réussis ; refus d’accès vérifiés ; données cohérentes après échec d’une transaction.

### Étape 2 — Vérifier les parcours serveur et l’API des valeurs actives

**Statut :** vérifié par tests Feature le 6 novembre 2026.

Contrôler création, recherche, édition, filtres d’état, pagination, réactivation, suppression/désactivation, création et retrait d’association. Vérifier que `GET /referentiels/actifs` ne retourne aucune valeur inactive ni champ privé, fournit S1 puis S2 et conserve un tri stable.

**Critères de sortie :** tests Feature réussis pour les parcours et cas limites ; contrat de l’endpoint confirmé avec les modules consommateurs.

### Étape 3 — Recetter l’interface

**Statut :** recette navigateur effectuée le 6 novembre 2026.

Parcourir l’administration des trois référentiels et la gestion des associations. La recette Chrome a vérifié les pages de liste, les formulaires de création, les messages de succès, le choix des valeurs actives et la création d’une association. L’endpoint a retourné S1/S2 et l’association créée. Les pages ont été contrôlées à 1280 px et 320 px ; le tableau conserve son défilement dans son conteneur sur petit écran. Les refus d’accès et cas limites sont couverts par les tests Feature.

**Critères de sortie :** recette navigateur documentée ; aucun défaut bloquant observé ; affichage contenu au format bureau et petit écran.

### Étape 4 — Recette globale et acceptation

**Statut :** validée à 100 % le 6 novembre 2026.

Exécuter les contrôles backend sur MySQL, les commandes frontend et une recette de bout en bout avec un administrateur autorisé. Le demandeur a demandé la validation complète de M3 et confirmé explicitement l’inclusion de S1 et S2.

**Critères de sortie :** toutes les vérifications requises réussies ; questions métier bloquantes résolues ; validation explicite enregistrée ci-dessous.

#### État des contrôles au 6 novembre 2026

- `DB_USERNAME=root DB_PASSWORD=<temporaire> php artisan test --compact tests/Feature/ReferentielsTest.php` : **14 tests réussis, 63 assertions** sur un conteneur MySQL 8.4 éphémère dédié, le 6 novembre 2026.
- `DB_USERNAME=root DB_PASSWORD=<temporaire> php artisan test --compact` : **65 tests réussis, 552 assertions**. Le seeder des permissions a été corrigé pour vider son cache avant et après l’ajout des permissions, y compris lorsque le seeder parent neutralise les événements de modèles.
- `npm run test:unit` : **8 tests réussis**.
- `npm run lint` : réussi.
- `npm run typecheck` : réussi.
- Prettier ciblé sur les trois vues M3 : réussi. Le contrôle global signale également `Auth/VerifyEmail.vue`, hors périmètre M3.
- `npm run build` : réussi.
- Vérification de syntaxe PHP des trois contrôleurs M3 et du test Feature : réussie.
- Le demandeur a confirmé l’inclusion de S1 et S2 dans M3 le 6 novembre 2026 et demandé sa validation à 100 %.
- Recette Chrome : authentification administrateur temporaire, création filière/niveau/matière, association, message de succès, endpoint S1/S2 et association, vues à 1280 px et 320 px. La base et le compte de recette étaient éphémères.

## 10. Recette globale et critères d’acceptation

- **CA01 — Autorisation :** seuls les comptes autorisés administrent les référentiels ; les autres reçoivent un refus côté serveur.
- **CA02 — Unicité :** aucun code de filière, niveau ou matière n’est dupliqué, y compris après normalisation convenue.
- **CA03 — Associations :** un triplet filière/niveau/matière valide ne peut être enregistré qu’une seule fois ; les valeurs inactives sont refusées pour une nouvelle association.
- **CA04 — Conservation :** une valeur déjà utilisée n’est pas supprimée physiquement et son code reste stable, conformément aux règles métier acceptées.
- **CA05 — Consommation :** l’endpoint fournit uniquement les valeurs actives et les champs convenus, dans un ordre stable.
- **CA06 — Parcours d’administration :** liste, recherche, filtres, pagination, création, modification, désactivation/réactivation et associations sont utilisables.
- **CA07 — Intégrité :** les tests backend réussissent sur MySQL et aucune opération refusée ne laisse de données partielles.
- **CA08 — Semestres :** l’endpoint renvoie exactement S1 et S2 dans l’ordre, avec leurs identifiants 1 et 2.
- **CA09 — Acceptation métier :** le demandeur a demandé la validation complète de M3 et confirmé S1/S2 ; les autres règles livrées suivent le cahier des charges et le périmètre existant des M1/M2.

## 11. Limites connues et consignes de livraison

- Les tests backend, la suite complète et la recette navigateur sont consignés ; aucune réserve bloquante M3 ne reste ouverte.
- Les classes et rattachements utilisateurs restent hors du premier incrément M3 ; l’année scolaire est une métadonnée M4 selon le cahier des charges.
- L’intégration avec M4 doit définir les références aux référentiels avant d’autoriser une suppression ou dissociation susceptible d’altérer l’historique des épreuves.
- La construction frontend réussie ne remplace pas la recette fonctionnelle dans le navigateur.

## 12. Journal des décisions

| Date | Décision / constat | Décideur | Preuve |
| --- | --- | --- | --- |
| 6 novembre 2026 | M3 inclut les valeurs fixes S1 et S2 ; l’année scolaire reste une métadonnée d’épreuve sans écran de gestion. | Demandeur, pour S1/S2 ; cahier des charges, pour l’année | Réponse du demandeur et cahier des charges ESGC VAK v0.3. |
| 6 novembre 2026 | 14 tests Feature M3 et 63 assertions réussis sur MySQL 8.4 temporaire ; contrôles frontend disponibles réussis. | Équipe Academia | Résultats de tests et build consignés à l’étape 4. |
| 6 novembre 2026 | Validation complète de M3 demandée par le demandeur ; recette serveur, tests, build et parcours navigateur réussis. | Demandeur / équipe Academia | 65 tests PHP, 552 assertions, contrôles frontend réussis et recette Chrome aux formats 1280 px et 320 px. |
| 6 novembre 2026 | Valeurs initiales demandées pour le formulaire M4 : filières GI, GT, GC et niveaux L1, L2, L3, créés uniquement s’ils n’existent pas déjà. | Demandeur | Réponses sur les listes Filière et Niveau ; `AcademicReferentialsSeeder`. |

