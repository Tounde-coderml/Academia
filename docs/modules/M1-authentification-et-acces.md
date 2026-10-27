# Plan d’implémentation — M1 Authentification et accès

> Version 1.11 — 24 octobre 2026
> Statut : étapes 0 à 4 validées techniquement ; e-mail SMTP d’essai reçu et confirmé le 24 octobre 2026.
> Projet : Academia — Module 1 de l’architecture de la Bibliothèque des Épreuves ESGC VAK.

## 1. Identification

| Élément | Valeur |
| --- | --- |
| Module | M1 — Authentification et accès |
| Auteur | Équipe Academia, à confirmer |
| Validateur métier | Demandeur, approbation reçue le 22 octobre 2026 ; nom et fonction à compléter si requis |
| Date | 22 octobre 2026 |
| Version | 1.11 |
| Statut | Étapes 0 à 4 validées techniquement ; e-mail SMTP d’essai reçu et confirmé le 24 octobre 2026 |

Ce plan reprend le module M1 de l’architecture logicielle et la trame du guide « Avant de coder un module ». Les décisions D01 à D10 et la matrice d’accès ont été approuvées par le demandeur le 22 octobre 2026 ; les validations d’exécution sont consignées séparément pour chaque étape.

## 2. Contexte, problème et objectifs

La plateforme doit réserver ses espaces privés aux utilisateurs authentifiés et appliquer les droits associés à leur rôle. Un utilisateur doit pouvoir ouvrir une session, la fermer et récupérer son compte sans exposer ses identifiants ni révéler à un tiers si une adresse e-mail est enregistrée.

Parcours principal : un utilisateur autorisé ouvre la page de connexion, s’authentifie, accède à une page protégée, puis se déconnecte. En cas d’oubli de mot de passe, il demande un lien et définit un nouveau mot de passe à partir de ce lien.

Objectifs :

- Fournir une authentification web par session et protéger les pages privées côté serveur.
- Appliquer les rôles et permissions aux actions selon une matrice métier validée.
- Protéger les sessions, limiter les tentatives abusives et permettre une récupération de compte maîtrisée.
- Donner des retours compréhensibles sans divulguer l’existence d’un compte ou des données sensibles.

### Histoires utilisateur à confirmer

- Comme utilisateur autorisé, je veux me connecter afin d’accéder aux fonctions permises par mon rôle.
- Comme utilisateur authentifié, je veux me déconnecter afin que ma session ne puisse plus être réutilisée.
- Comme utilisateur ayant oublié son mot de passe, je veux recevoir un lien de réinitialisation sans qu’un tiers puisse déduire si mon compte existe.
- Comme responsable de la plateforme, je veux que les actions sensibles soient refusées lorsque le rôle ou la permission nécessaire manque.

## 3. Périmètre

### Inclus dans M1

- Connexion et déconnexion par session web.
- Protection des routes privées et vérification serveur des rôles/permissions utilisés par les modules.
- Limitation des tentatives de connexion et de récupération de mot de passe, selon des seuils validés.
- Demande et exécution de réinitialisation du mot de passe.
- Messages d’erreur, retours de formulaire et protections contre la divulgation de données sensibles.
- Tests des règles d’authentification et d’accès.

### Hors périmètre de M1

- Création, modification, désactivation et suppression des comptes : responsabilité du M2 « Comptes utilisateurs ».
- Affectation et administration des rôles et permissions : à coordonner avec M2 et l’administration ; M1 applique les droits, mais ne définit pas seul la politique métier.
- Vérification obligatoire d’adresse e-mail, authentification multifacteur, connexion sociale et API de jetons : non demandées à ce stade. L’inscription publique des étudiants est réintégrée par la décision D11.
- Autorisations propres aux épreuves et aux fichiers : chaque module concerné devra fournir ses règles et policies, en s’appuyant sur le socle M1.

## 4. Existant, stack et contraintes

Les éléments ci-dessous ont été observés dans le dépôt. « Présent » décrit le code ou la configuration, pas une validation de conformité ni une recette complète.

| Élément | État observé | Conséquence pour le plan |
| --- | --- | --- |
| Backend | Laravel `^13.17` et PHP `^8.3` déclarés ; Laravel 13.36.0 et PHP CLI 8.4.22 installés | Vérifier les versions et la configuration de déploiement sur chaque environnement avant la livraison. |
| Interface | Inertia, Vue 3 et TypeScript déclarés dans `package.json` ; pages d’authentification sous `resources/js/pages/Auth/` | Réutiliser les pages et conventions présentes ; ne pas remplacer la stack par Blade/Bootstrap. |
| Comptes | Modèle `User` et migration `users` présents ; e-mail unique, mot de passe, `remember_token` et `email_verified_at`. M2 a depuis ajouté `is_active` et la suppression logique. | M1 fournit l’authentification ; le statut actif du compte est contrôlé par le middleware livré avec M2. |
| Rôles | Dépendance Spatie Permission, tables et intégration `HasRoles` du modèle `User` présentes depuis M1/M2 | Les autorisations sont appliquées côté serveur ; M2 gère l’affectation des rôles et ne modifie pas leur matrice. |
| Authentification | Routes web `guest`/`auth`, Actions, Form Requests et contrôleurs présents | Auditer puis compléter ce qui manque ; éviter de créer un deuxième mécanisme d’authentification. |
| Sessions | Table `sessions` créée par la migration initiale ; middleware `AuthenticateSession` configuré dans `bootstrap/app.php` | Vérifier le driver réellement utilisé par environnement et le comportement après changement de mot de passe. |
| Réinitialisation | Table `password_reset_tokens`, Actions, notification et pages présentes | Vérifier expiration, usage unique, limitations, configuration e-mail et retours utilisateur. |
| Tests | PHPUnit et `tests/Feature/AuthenticationTest.php` couvrent plusieurs parcours ; Vitest est déclaré côté interface | Étendre les tests existants avec les règles validées, sans supposer qu’un test de composant prouve le contrôle serveur. |
| Base et exploitation | MySQL, cache database et sessions database. Après un premier échec SMTP, un e-mail d’essai a été accepté et reçu le 24 octobre 2026. | La configuration d’envoi doit être maintenue dans les environnements de recette et de production. |

### Principaux fichiers repérés

- `routes/web.php`
- `app/Models/User.php`
- `app/Actions/Auth/`
- `app/Http/Controllers/Auth/`
- `app/Http/Requests/Auth/`
- `app/Notifications/Auth/`
- `resources/js/pages/Auth/`
- `database/migrations/`
- `tests/Feature/AuthenticationTest.php`

Adapter ces chemins si la structure évolue. Examiner les migrations et les tests avant toute modification ; ne pas dupliquer les tables `users`, `sessions` ou `password_reset_tokens`.

## 5. Règles métier et autorisations

Les règles suivantes sont les critères de conception proposés. Les seuils et politiques marqués « à valider » ne deviennent pas des décisions tant que le responsable désigné ne les a pas approuvés.

| ID | Règle vérifiable | État / décision |
| --- | --- | --- |
| R01 | Un visiteur non authentifié ne peut pas consulter une route privée ; le serveur le redirige vers la connexion ou refuse l’accès selon le contrat de la route. | Partiellement en place ; compléter les tests par route protégée. |
| R02 | Une connexion réussie régénère l’identifiant de session avant d’accorder l’accès privé. | Présent et testé dans `AuthenticationTest`. |
| R03 | Une connexion échouée renvoie un message générique identique pour un compte connu et inconnu ; le mot de passe n’est pas conservé dans les anciennes valeurs du formulaire. | Message générique présent et testé ; vérifier le rendu de l’interface. |
| R04 | Les tentatives sont limitées côté serveur par identité et adresse IP ; les clés de limitation ne contiennent ni mot de passe ni identifiant de session en clair. | Implémenté avec des seuils actuels ; seuils, fenêtres et comportement en production à valider. |
| R05 | Une session authentifiée ne donne accès qu’aux actions permises ; chaque action sensible vérifie côté serveur les permissions métier nécessaires. Le masquage d’un bouton ne constitue pas une autorisation. | Matrice de rôles et de permissions à confirmer ; l’authentification seule ne satisfait pas cette règle. |
| R06 | La déconnexion est une mutation authentifiée en `POST`, invalide la session et régénère le jeton CSRF ; une ancienne session ne permet plus d’ouvrir une route privée. | Présent et couvert par des tests ciblés. |
| R07 | Une demande de réinitialisation renvoie le même message public que le compte existe ou non ; les demandes sont limitées côté serveur. | Message générique présent et testé ; limites à valider. |
| R08 | Un jeton de réinitialisation expiré, invalide ou déjà utilisé ne change pas le mot de passe. Le nouveau mot de passe est validé et stocké via le mécanisme de hachage Laravel ; il ne peut pas être identique à l’adresse e-mail du compte, sans tenir compte de la casse. | Validations serveur à l’inscription, à la réinitialisation, à la connexion et à la création initiale de l’administrateur ; couverture Feature ajoutée. |
| R09 | Une réinitialisation réussie ne laisse pas réutiliser les sessions précédentes selon la politique de session retenue ; elle ne connecte pas automatiquement l’utilisateur sans décision explicite. | Comportement après changement de mot de passe couvert par un test ; faire confirmer la politique attendue. |
| R10 | Les journaux et les données envoyées à l’interface n’exposent jamais de mot de passe, jeton de réinitialisation, cookie ou identifiant de session. | Empreintes HMAC utilisées dans certaines traces ; revue complète des logs et props à effectuer. |

### Matrice d’accès proposée

La matrice ci-dessous transforme les responsabilités macro de l’architecture en proposition MVP de moindre privilège. Elle sert au cadrage de M1 ; les actions métier seront appliquées par les policies de leurs modules. « Administration » et « Administrateur » sont traités comme deux acteurs distincts. Le demandeur a approuvé cette matrice le 22 octobre 2026 ; son nom et sa fonction peuvent être ajoutés si la procédure de l’établissement l’exige.

| Action | Étudiant | Responsable de classe | Administration | Administrateur |
| --- | --- | --- | --- | --- |
| Se connecter, se déconnecter et réinitialiser son propre mot de passe | Oui, compte provisionné | Oui, compte provisionné | Oui, compte provisionné | Oui, compte provisionné |
| Rechercher et consulter les épreuves publiées | Oui | Oui | Oui | Oui |
| Déposer une épreuve à soumettre | Non dans le MVP proposé | Oui | Oui | Oui |
| Valider ou refuser une épreuve | Non | Non | Oui | Oui |
| Gérer les comptes et attribuer les rôles | Non | Non | Non dans le MVP proposé | Oui, via M2 |
| Gérer les référentiels pédagogiques | Non | Non | Non dans le MVP proposé | Oui |
| Consulter les indicateurs d’administration | Non | Périmètre de sa classe uniquement, à confirmer | Oui | Oui |

Règles associées : l’inscription publique crée un compte étudiant ; seul un administrateur peut ensuite modifier le rôle par M2. Tous les rôles ci-dessus sont authentifiés et un refus d’accès est contrôlé côté serveur. L’accès aux épreuves non publiées reste défini par M4/M5, pas par cette matrice générale.

## 6. Données, routes web et interface

### Données

| Donnée | Source / responsabilité | Contraintes à vérifier |
| --- | --- | --- |
| Utilisateur (`users`) | Modèle et migration existants ; cycle de vie détaillé dans M2 | E-mail unique, hachage du mot de passe, champs autorisés à la sérialisation ; politique d’activation et de vérification à décider. |
| Rôle, permission et pivots | Tables créées par Spatie Permission | Utiliser le guard web et les noms/permissions validés ; confirmer l’intégration au modèle `User`. Pas de rôle implicite fondé sur une valeur client. |
| Session | Driver Laravel et table existante si le driver database est utilisé | Cookies `HttpOnly`, `Secure` en HTTPS et `SameSite` approprié ; durée et invalidation à documenter par environnement. |
| Jeton de réinitialisation | Broker Laravel et table existante | Expiration, stockage, limitation, usage unique et invalidation après succès à vérifier avec la configuration retenue. |
| Tentatives | Rate limiter/cache Laravel | Seuils, fenêtres, cache de production partagé et réponse en cas d’indisponibilité à décider. Ne pas créer de compteur permanent dans `users` sans besoin validé. |

### Contrat web observé à maintenir ou faire évoluer

Les routes ci-dessous existent dans `routes/web.php`. Elles utilisent les sessions web et Inertia ; ce module ne définit pas d’API JSON.

| Méthode et route | Accès | Résultat attendu |
| --- | --- | --- |
| `GET /login` | Visiteur | Page de connexion. |
| `POST /login` | Visiteur | Authentification, erreur générique ou refus par limitation. |
| `POST /logout` | Authentifié | Invalidation de la session puis retour à la connexion. |
| `GET /forgot-password` | Visiteur | Formulaire de demande de lien. |
| `POST /forgot-password` | Visiteur | Confirmation générique, avec limitation côté serveur. |
| `GET /reset-password/{token}` | Visiteur | Formulaire de nouveau mot de passe. |
| `POST /reset-password` | Visiteur | Réinitialisation réussie ou erreur sûre pour jeton invalide/expiré. |
| `GET /dashboard` | Authentifié | Exemple de route privée ; le contenu définitif relève du périmètre fonctionnel à préciser. |

Ne pas journaliser les corps de ces requêtes. Conserver la protection CSRF du groupe `web`. Les détails des codes et redirections pour une limitation ou une erreur de service sont à arrêter avant la recette.

### Interface et états à vérifier

Les pages observées sont `LoginPage.vue`, `ForgotPassword.vue` et `ResetPassword.vue`. Pour chaque formulaire, vérifier les états chargement, erreur de validation, échec générique, succès, lien expiré et indisponibilité réseau ; labels visibles, navigation clavier, focus, annonces d’erreur et adaptation mobile. Le serveur reste l’autorité pour l’authentification et les permissions.

## 7. Qualité, sécurité et risques

- Tester les refus d’accès au serveur, pas seulement l’affichage conditionnel de l’interface.
- Ne jamais stocker ou journaliser les mots de passe en clair ; utiliser le hasher Laravel et ne pas renvoyer les secrets dans les props Inertia.
- Vérifier la configuration HTTPS, cookies, session, cache/rate limiter, `APP_DEBUG` et transport e-mail dans chaque environnement réel.
- Tester les échecs de cache, session et e-mail afin qu’ils ne créent jamais un accès accordé par défaut.
- Tester l’expiration et le rejeu des jetons, les sessions après changement de mot de passe et les variations d’identifiant e-mail.
- Vérifier les pages au clavier, à fort zoom et sur petit écran ; compléter les tests automatiques par une vérification humaine.
- Risque principal : appliquer un rôle ou un droit supposé peut exposer des comptes ou bloquer les acteurs légitimes. La matrice validée est donc un prérequis avant l’activation des autorisations métier.

## 8. Décisions de cadrage proposées

Les propositions ci-dessous rendent le plan directement exploitable. Elles s’appuient sur le code et la configuration observés, ou sur le principe de moindre privilège lorsque l’architecture ne précise pas la règle. Le demandeur a approuvé la matrice et les décisions D01 à D10 le 22 octobre 2026. Son nom et sa fonction peuvent être ajoutés au tableau si la procédure de l’établissement l’exige.

| ID | Sujet et décision proposée pour le MVP | Justification / impact | Responsable de validation | Décision / date |
| --- | --- | --- | --- | --- |
| D01 | Conserver les rôles Étudiant, Responsable de classe, Administration et Administrateur ; appliquer la matrice proposée en section 5. | Reprend les acteurs de l’architecture et sépare dépôt, validation et administration des comptes. | Responsable métier ESGC VAK | Approuvé par le demandeur, 22 octobre 2026 |
| D02 | Aucun compte ne peut s’inscrire publiquement. L’établissement crée les comptes ; seul l’Administrateur peut attribuer ou retirer les rôles, dans M2. | Le cahier d’architecture ne demande pas d’inscription ; réduit l’exposition aux comptes non autorisés. | Responsable projet / établissement | Approuvé par le demandeur, 22 octobre 2026 |
| D03 | Ne pas exiger la vérification d’e-mail pour le MVP, faute de besoin confirmé. Si M2 introduit un état inactif, le compte inactif est refusé à la connexion ; ne pas ajouter le champ dans M1. | La migration actuelle ne contient pas de statut actif et le mailer local est `log`. Toute activation requiert une décision et le service e-mail de M2. | Responsable métier | Approuvé par le demandeur, 22 octobre 2026 |
| D04 | Conserver provisoirement les limites implémentées : connexion 5 échecs par identité+IP sur 60 s et 30 par IP sur 60 s ; demande de reset 3 par identité+IP et 20 par IP sur 900 s ; soumission du reset 5 par identité+IP sur 900 s et 30 par IP sur 60 s. Message public générique dans tous les cas. | Reprend `AuthenticateUser`, `RequestPasswordReset` et `ResetUserPassword` ; changer ces seuils exige des tests et une mesure des faux positifs. | Responsable sécurité / projet | Approuvé par le demandeur, 22 octobre 2026 |
| D05 | Garder le minimum actuel de 15 caractères pour un nouveau mot de passe ; ne pas ajouter de règles de composition arbitraires. Conserver la limite technique calculée selon le hasher configuré. | Aligné sur `NewPasswordRequest` ; le mot de passe est hashé par Laravel. | Responsable sécurité / projet | Approuvé par le demandeur, 22 octobre 2026 |
| D06 | Garder le jeton de réinitialisation valable 60 min et l’intervalle minimal de création à 60 s ; après succès, ne pas connecter automatiquement l’utilisateur et invalider les sessions précédentes. | Valeurs effectives du broker vérifiées ; le middleware `AuthenticateSession` et le changement du hash invalident les sessions existantes. | Responsable sécurité / établissement | Approuvé par le demandeur, 22 octobre 2026 |
| D07 | Garder une durée d’inactivité de session de 120 min, sans expiration à la fermeture du navigateur ; cookie HttpOnly, SameSite=Lax et Secure en production HTTPS. | Valeurs effectives vérifiées ; Secure est désactivé en environnement local et activé en production par défaut. | Responsable technique / sécurité | Approuvé par le demandeur, 22 octobre 2026 |
| D08 | Garder le cache database comme choix initial partagé ; utiliser `array`/`log` en tests isolés et configurer un transport e-mail réel avant recette de la réinitialisation. | Configuration locale vérifiée : cache database et mailer `log`. Aucun fournisseur de production n’est choisi ici. | Responsable technique | Approuvé par le demandeur, 22 octobre 2026 |
| D09 | Les rôles et permissions restent dans Spatie Permission, guard `web`. La gestion des comptes et l’affectation des rôles relèvent de M2 ; M1 vérifie les permissions côté serveur. | Le package et les tables sont présents, mais `User` n’utilise pas encore `HasRoles`. Pas d’activation avant implémentation de M1/M2. | Responsable technique et métier | Approuvé par le demandeur, 22 octobre 2026 |
| D10 | Pour le MVP, retenir une interface française et vérifier les formulaires au clavier, à 200 % de zoom et sur petit écran ; viser les critères WCAG 2.2 AA pertinents. | Le projet est francophone et le plan prévoit déjà les contrôles d’accessibilité ; la cible institutionnelle reste à confirmer. | Responsable projet / référent accessibilité | Approuvé par le demandeur, 22 octobre 2026 |
| D11 | Réviser D02 : conserver l’inscription publique ; chaque nouveau compte reçoit le rôle `etudiant`. Seul un administrateur peut remplacer ce rôle par un autre rôle approuvé. Le premier administrateur est créé par une commande de configuration ponctuelle, sans identifiant par défaut codé en dur. | Décision explicite du demandeur pendant le cadrage M2 ; remplace la règle « aucune inscription publique » de D02. | Demandeur | Approuvé, 24 octobre 2026 |
| D12 | Utiliser MySQL pour les tests PHPUnit avec la base dédiée `academia_test`, distincte de la base de développement. | Le demandeur confirme MySQL comme moteur du projet et choisit le nom de la base de test. | Demandeur / équipe technique | Approuvé, 24 octobre 2026 |

Validation enregistrée : le demandeur a approuvé D01 à D10 et la matrice de la section 5 le 22 octobre 2026. Ajouter son nom et sa fonction si la procédure de l’établissement exige une identification nominative.

## 9. Étapes d’implémentation

### Étape 0 — Vérifier et valider le cadrage

**Statut :** validée par approbation du demandeur le 22 octobre 2026.

#### Objectif et prérequis

Rendre le périmètre M1 applicable au projet ESGC VAK. Prérequis : accès au cahier des charges validé, aux consignes du dépôt et aux personnes pouvant décider des rôles et règles de compte.

#### Travaux à réaliser et fichiers concernés

- Relire le cahier des charges et consigner l’approbation ou les corrections de la matrice d’accès et des décisions D01 à D10.
- Vérifier les versions installées, les guards, le driver de session/cache, le broker e-mail et les conventions des tests.
- Comparer ce plan à `routes/web.php`, aux migrations et aux parcours déjà implémentés.
- Ne pas créer de migration ou de route avant d’avoir confirmé qu’elle est nécessaire.

#### Règles et points de vigilance

R01 à R10. Les décisions de droits, d’état de compte et de récupération sont bloquantes pour leur implémentation. Distinguer les fonctionnalités présentes, testées, à compléter et validées.

#### Livrables

- Plan M1 complété avec décisions, auteur et date.
- Matrice des rôles/permissions approuvée.
- Liste de critères d’acceptation issue du cahier des charges réel.

#### Tests et cas limites

- Jouer sur papier un accès anonyme, une connexion valide, un refus, une action interdite et une récupération de compte.
- Vérifier le périmètre de M1 par rapport à M2 ; aucun rôle ne doit être attribué par supposition.

#### Critères de sortie

- [x] Les acteurs, droits et politiques nécessaires sont documentés sous forme de propositions, chacune avec un responsable de validation.
- [x] Le demandeur approuve la matrice d’accès et les décisions D01 à D10 le 22 octobre 2026.
- [x] Le périmètre et les critères d’acceptation de cadrage sont approuvés avant toute modification des règles métier.

#### Bilan d’exécution

Vérifications réalisées le 22 octobre 2026 :

- Dépendances PHP directes vérifiées avec `composer show --direct` : Laravel 13.36.0, Inertia Laravel 3.5.1 et Spatie Permission 8.3.0 ; PHP CLI 8.4.22.
- Configuration Laravel vérifiée : `database.default=mysql`, `cache.default=database`, `session.driver=database`, `mail.default=log`.
- Premier lancement sur SQLite en mémoire : les 12 tests n’ont pas atteint leurs assertions, car `pdo_sqlite` est absent. Ce blocage a été contourné en démarrant MySQL et en créant un schéma de test isolé.
- Validation finale : `DB_CONNECTION=mysql DB_DATABASE=academia_m1_test_20261022 DB_URL= php artisan test --compact tests/Feature/AuthorizationTest.php tests/Feature/AuthenticationTest.php` — 16 tests réussis, 148 assertions.
- Fichier modifié : `docs/modules/M1-authentification-et-acces.md`.
- Décisions D01 à D10 et matrice d’accès approuvées par le demandeur le 22 octobre 2026 ; son nom et sa fonction ne sont pas renseignés.
- L’étape 0 est validée pour le cadrage. Les étapes 1 et 2 ont ensuite été testées sur MySQL isolé ; l’absence de `pdo_sqlite` ne bloque plus cette validation.

À la date de cette étape, `phpunit.xml` utilisait SQLite en mémoire et exigeait `pdo_sqlite`. La décision D12 a ensuite remplacé ce réglage par MySQL et une base dédiée `academia_test` ; ne jamais remplacer son nom par celui de la base applicative ou de production. Les tests M1 de cette étape avaient utilisé le schéma isolé `academia_m1_test_20261022`.

#### Validation

Validateur : demandeur (nom et fonction à compléter si requis). Date : 22 octobre 2026. Décision : étape 0 validée ; passage à l’étape 1 autorisé selon les décisions D01 à D10.

### Étape 1 — Relier les comptes aux rôles et protéger les accès

**Statut :** validée le 22 octobre 2026 — tests réussis sur MySQL isolé.

#### Objectif et prérequis

Faire appliquer par le serveur les rôles et permissions validés. Prérequis : matrice approuvée, modèle de compte et guard web confirmés.

#### Travaux à réaliser et fichiers concernés

- Ajouter `HasRoles` au modèle `User` et conserver le guard `web`.
- Créer `AccessControlSeeder` de façon idempotente pour les quatre rôles et les permissions de la matrice approuvée ; l’appeler depuis `DatabaseSeeder`.
- Protéger `/dashboard` avec `auth` et `can:dashboard.access`, comme première route représentative du contrôle serveur.
- Retirer la création du compte `test@example.com` du seeder général afin qu’un `db:seed` ne crée pas de compte de démonstration en production.
- Confirmer la séparation : M1 contrôle l’identité et les permissions ; M2 gère le cycle de vie et l’affectation des rôles.
- Adapter `app/Models/User.php`, `app/Policies/` ou les middleware uniquement selon les décisions et conventions constatées.

#### Règles et points de vigilance

R01, R05 et R10. Refus par défaut ; ne jamais accepter un rôle ou un identifiant d’acteur fourni par l’interface comme preuve d’autorisation. Ne pas dupliquer les tables de permissions existantes.

#### Livrables

- Contrôle des accès relié à la matrice approuvée.
- Tests serveur positifs et négatifs pour chaque permission du périmètre.
- Documentation de l’initialisation des rôles, si elle est nécessaire.

#### Tests et cas limites

- Visiteur refusé sur une route privée.
- Utilisateur authentifié sans permission refusé sur l’action concernée.
- Utilisateur avec permission autorisé ; vérification qu’un rôle ne peut pas être forgé par requête.
- Modèle utilisateur sérialisé sans mot de passe ni jeton.

#### Critères de sortie

- [x] Les permissions des quatre rôles de la matrice approuvée sont définies dans le seeder et vérifiées par un test de matrice.
- [x] Les migrations existantes sont réutilisées ; aucun schéma de permission supplémentaire n’est ajouté.
- [x] La route `/dashboard` applique `auth` et `can:dashboard.access`, vérifié avec `php artisan route:list --path=dashboard -v`.
- [x] Les tests d’accès passent sur une base MySQL de test isolée : 3 tests d’autorisation inclus dans les 16 tests réussis.

#### Bilan d’exécution

Vérifications réalisées le 22 octobre 2026 :

- Pint sur les six fichiers PHP modifiés : réussi.
- `php -l` sur `User.php`, les seeders, `web.php` et les deux tests Feature : aucune erreur de syntaxe.
- `php artisan route:list --path=dashboard -v` : la route applique `web`, `auth` et `can:dashboard.access`.
- `DB_CONNECTION=mysql DB_DATABASE=academia_m1_test_20261022 DB_URL= php artisan test --compact tests/Feature/AuthorizationTest.php tests/Feature/AuthenticationTest.php` : 16 tests réussis, 148 assertions. Les tests ont utilisé le schéma isolé et non la base `academia`.
- Fichiers modifiés : `app/Models/User.php`, `database/seeders/DatabaseSeeder.php`, `database/seeders/AccessControlSeeder.php`, `routes/web.php`, `tests/Feature/AuthenticationTest.php` et `tests/Feature/AuthorizationTest.php`.
- `DatabaseSeeder` crée désormais les rôles/permissions de base, mais aucun compte utilisateur. La création et l’affectation des comptes restent au M2.

#### Validation

Validateur : demandeur. Date : 22 octobre 2026. Décision : étape 1 validée après 16 tests et 148 assertions réussis sur MySQL isolé.

### Étape 2 — Finaliser la connexion, les sessions et la déconnexion

**Statut :** validée le 22 octobre 2026 — tests réussis sur MySQL isolé.

#### Objectif et prérequis

Fournir un parcours de session fiable, sans fixation de session et avec une limitation d’essais conforme à la politique approuvée.

#### Travaux à réaliser et fichiers concernés

- Auditer `AuthenticateUser`, `LoginRequest`, `AuthenticatedSessionController` et les routes `login`, `logout` et `dashboard`.
- Conserver les services Laravel natifs et les Actions existantes lorsque leur comportement est validé.
- Ajuster la normalisation de l’identifiant, les limites et la durée selon la décision de l’étape 0.
- Vérifier la régénération de session au succès, la réponse générique à l’échec, l’invalidation au logout et le redirect après connexion.
- Contrôler les cookies, le driver de session et les événements journalisés par environnement.

#### Règles et points de vigilance

R01 à R06 et R10. La limite côté interface ne remplace pas celle du serveur. Le logout reste une mutation `POST` protégée par CSRF. Ne pas exposer les raisons détaillées d’échec d’authentification.

#### Livrables

- Parcours connexion/déconnexion finalisé.
- Tests Feature de session, limitation, redirections et refus d’accès.
- Configuration documentée pour les environnements de recette et de production.

#### Tests et cas limites

- Connexion valide, adresse normalisée, mot de passe invalide et adresse inconnue avec réponse équivalente.
- Rotation de session après connexion ; ancienne session refusée après déconnexion.
- Limite atteinte, limite expirée et accès depuis plusieurs identités/IP selon la politique approuvée.
- Requête `GET` de logout refusée ; route privée sans session refusée.
- Panne du cache ou du stockage de session sans autorisation accordée par défaut.

#### Critères de sortie

- [x] Les critères de limitation approuvés sont couverts par des tests reproductibles, y compris le seuil par identité et par IP.
- [x] Les tests serveur démontrent la rotation et l’invalidation de session.
- [x] Les réponses d’échec sont génériques ; l’audit confirme que les traces d’authentification n’enregistrent ni mot de passe ni jeton.

#### Bilan d’exécution

Vérifications réalisées le 22 octobre 2026 :

- Audit du contrôleur, de l’Action, de la Form Request, des routes et de la page de connexion : connexion par guard `web`, normalisation de l’e-mail, régénération de session au succès, message générique en cas d’échec, conservation de l’e-mail seulement, limitation des tentatives par identité/IP et invalidation de session au logout sont présents.
- Tests ajoutés dans `tests/Feature/AuthenticationTest.php` : le mot de passe n’est pas conservé dans `_old_input` après un échec ; après 30 échecs depuis une même IP avec des adresses distinctes, une connexion valide est temporairement refusée.
- `vendor/bin/pint tests/Feature/AuthenticationTest.php --format agent` : réussi. `php -l tests/Feature/AuthenticationTest.php` : aucune erreur de syntaxe.
- `DB_CONNECTION=mysql DB_DATABASE=academia_m1_test_20261022 DB_URL= php artisan test --compact tests/Feature/AuthorizationTest.php tests/Feature/AuthenticationTest.php` : les 13 tests d’authentification et les 3 tests d’autorisation réussissent, soit 16 tests et 148 assertions.
- Le schéma de test MySQL est distinct de `academia`. Les seuils actuels (5 par identité/IP sur 60 secondes et 30 par IP sur 60 secondes) sont ceux approuvés en D04.
- Les contrôles Pint, syntaxe PHP, route enregistrée et middleware restent réussis.

#### Validation

Validateur : demandeur. Date : 22 octobre 2026. Décision : étape 2 validée après réussite de la suite M1 sur MySQL isolé.

### Étape 3 — Finaliser la récupération du mot de passe

**Statut :** implémentation et tests backend validés le 22 octobre 2026 ; configuration d’un transport e-mail réel encore requise pour la recette opérationnelle.

#### Objectif et prérequis

Permettre la récupération d’un compte par un lien temporaire, à usage unique et envoyé par le canal approuvé. Prérequis : politique de mot de passe, durée du jeton et service e-mail de recette décidés.

#### Travaux à réaliser et fichiers concernés

- Auditer `RequestPasswordReset`, `ResetUserPassword`, les Form Requests, les contrôleurs et `PasswordChangedNotification`.
- Vérifier la configuration du broker et la table existante `password_reset_tokens` ; ne pas créer un second mécanisme.
- Faire appliquer les limites et messages génériques retenus, ainsi que la politique de mot de passe approuvée.
- Définir le comportement des sessions actives après le changement de mot de passe et documenter l’invalidation effective.
- Vérifier que le jeton n’apparaît ni dans les journaux ni dans les props Inertia au-delà du formulaire nécessaire.

#### Règles et points de vigilance

R07 à R10. Les réponses publiques ne distinguent pas compte existant et inexistant. Un jeton expiré, invalide ou utilisé ne change aucun compte. Ne jamais se connecter automatiquement après reset sans décision approuvée.

#### Livrables

- Parcours de demande, réception et saisie d’un nouveau mot de passe.
- Tests Feature du broker, du message, des limites, des sessions et de la notification.
- Configuration et consignes de test du transport e-mail.

#### Tests et cas limites

- Adresse existante et inconnue avec confirmation publique identique.
- Jeton valide, expiré, invalide et réutilisé ; seul le jeton valide modifie le hash.
- Mot de passe hors politique ou confirmation différente ; état du compte inchangé.
- Limitation atteinte et service e-mail indisponible ; erreur sûre et absence de fuite de données.
- Connexion antérieure et session encore ouverte après changement, selon la décision adoptée.

#### Critères de sortie

- [x] Les règles de durée (60 minutes), d’usage unique, de mot de passe et de session sont approuvées et couvertes par des tests.
- [x] Les tests couvrent les jetons invalides/expirés/réutilisés, les limites par identité et IP et la panne du transport sans divulgation.
- [x] La notification de changement de mot de passe est vérifiée avec un faux transport isolé, sans envoi réel.
- [ ] Un transport e-mail réel est configuré pour un environnement de recette et l’envoi/réception est vérifié ; le mailer actif `log` ne satisfait pas ce critère opérationnel.

#### Bilan d’exécution

Vérifications réalisées le 22 octobre 2026 :

- `RequestPasswordReset` conserve la réponse publique générique si la livraison du lien lève une exception ; les logs ne contiennent ni le message de l’exception ni le jeton.
- Tests ajoutés dans `tests/Feature/AuthenticationTest.php` : panne e-mail, limites des demandes et soumissions par identité et par IP, expiration réelle du jeton, et invalidation d’une session après reset via `ResetUserPassword`.
- Suite complète M1 sur `academia_m1_test_20261022` : 23 tests réussis, 314 assertions.
- Pint et `php -l` réussis pour `app/Actions/Auth/RequestPasswordReset.php` et `tests/Feature/AuthenticationTest.php`.
- Configuration active pendant cette étape : mailer `log`. La recette de l’étape 4 a ensuite configuré SMTP Gmail, sans parvenir à authentifier ni envoyer un vrai e-mail.
- Fichiers modifiés pour cette étape : `app/Actions/Auth/RequestPasswordReset.php` et `tests/Feature/AuthenticationTest.php`.

#### Validation

Validation backend : demandeur, 22 octobre 2026. Le flux et ses tests sont validés ; la livraison réelle des e-mails reste à vérifier après configuration d’un transport de recette.

### Étape 4 — Recetter l’interface et préparer la livraison

**Statut :** recette front-end et intégration navigateur validées le 22 octobre 2026 ; e-mail SMTP d’essai reçu et confirmé le 24 octobre.

#### Objectif et prérequis

Vérifier le parcours complet avec les règles métier approuvées et documenter les limites restantes. Prérequis : contrôles serveur terminés et environnement de recette isolé.

#### Travaux à réaliser et fichiers concernés

- Tester les pages Inertia `LoginPage.vue`, `ForgotPassword.vue` et `ResetPassword.vue` avec les erreurs et états réseau.
- Compléter les tests Vue/Vitest utiles et lancer le type-check, le lint et le build du projet.
- Vérifier au clavier, à fort zoom et sur petit écran : labels, focus après erreur, annonces accessibles, contraste et absence de débordement.
- Exécuter la recette de bout en bout avec des comptes et rôles de test, puis contrôler les journaux et données retournées.
- Mettre à jour ce plan avec les résultats réellement obtenus, limites, commandes et procédure de configuration ; ne pas annoncer de test non exécuté.

#### Règles et points de vigilance

R01 à R10. Les tests de présentation ne remplacent pas les tests serveur. Aucune donnée de démonstration ni secret ne doit être livré en production. Le déploiement est séparé et nécessite une autorisation explicite.

#### Livrables

- Rapport de recette, résultat des commandes de test et liste des limites connues.
- Pages d’authentification vérifiées avec les états d’erreur et les droits validés.
- Plan M1 mis à jour avec les validations et la date de décision.

#### Tests et cas limites

- Scénario complet : connexion, accès à une route privée, refus d’une action sans permission, déconnexion.
- Scénario complet : demande de reset, jeton valide, nouveau mot de passe, ancien mot de passe refusé.
- Erreur réseau, service e-mail indisponible, écran vide, retour de validation et navigation clavier.
- Vérifier les réponses serveur et journaux pour détecter toute divulgation de comptes, mots de passe, jetons ou sessions.

#### Critères de sortie

- [x] Tests Feature d’authentification et d’accès réussis sur la base MySQL de test isolée.
- [x] Vitest, type-check, ESLint, Prettier et build front-end réussis.
- [x] Parcours navigateur de connexion, refus, dashboard, déconnexion, demande de lien et reset exécutés ; viewport 320 px sans débordement horizontal ; navigation clavier contrôlée.
- [x] Défauts UI trouvés pendant la recette corrigés : champs secrets effacés après erreurs et titre du dashboard actualisé.
- [x] Le serveur SMTP a accepté un e-mail de test vers l’adresse configurée le 24 octobre 2026.
- [x] Le demandeur confirme la réception du message dans la boîte configurée.

#### Bilan d’exécution

Vérifications réalisées le 22 octobre 2026 :

- Parcours navigateur via `http://127.0.0.1:8000` sur le schéma isolé `academia_m1_test_20261022` : connexion refusée avec message générique, adresse conservée et mot de passe effacé ; connexion valide, dashboard autorisé avec nom du compte ; logout puis accès protégé redirigé vers login.
- Parcours de récupération : demande pour adresse inconnue avec confirmation générique ; affichage du formulaire avec token de test ; réinitialisation valide et retour au login ; token invalide avec erreur générique et champs de mot de passe effacés.
- Contrôles d’interface : pages auth rendues, titre du dashboard corrigé, navigation clavier du formulaire de demande vérifiée, écran à 320 px sans débordement horizontal.
- Tests front-end : Vitest 2/2 ; `npm run typecheck`, `npm run lint`, `npm run format:check` et `npm run build` réussis.
- Tests backend : 23 tests réussis, 314 assertions, sur MySQL isolé.
- Fichiers modifiés pour l’étape : `resources/js/pages/Auth/LoginPage.vue`, `resources/js/pages/Auth/ResetPassword.vue`, `resources/js/pages/Auth/AuthPages.test.ts`, `resources/js/pages/AcademiaWelcome.vue`.
- Configuration SMTP Laravel vérifiée après `php artisan config:clear` : mailer `smtp`, hôte `smtp.gmail.com`, port `587`, expéditeur `toudedy@gmail.com`.
- Connectivité réseau SMTP testée sans authentification ni envoi : le serveur Gmail a répondu `220 smtp.gmail.com ESMTP`.
- Test SMTP réel tenté vers `toudedy@gmail.com` : Gmail a rejeté l’authentification ; aucun message n’a été accepté.
- Après mise à jour et `php artisan config:clear`, un nouvel essai avec un secret chargé de 16 caractères a encore été rejeté par Gmail ; aucun message n’a été accepté.
- Le 24 octobre 2026, après la configuration locale du SMTP, le serveur a accepté un e-mail d’essai et le demandeur en a confirmé la réception.
- Aucun secret ni réponse SMTP brute n’a été affiché.

#### Validation

Validation technique : recette effectuée le 22 octobre 2026 ; étapes 0 à 4 validées pour les parcours testés. Le SMTP a accepté le courriel d’essai et le demandeur en a confirmé la réception le 24 octobre.

## 10. Recette globale et suivi

La commande de base pour les tests backend ciblés est :

```sh
cd Academia
php artisan test --compact tests/Feature/AuthenticationTest.php
```

Les commandes front-end disponibles dans `package.json` incluent `npm run test:unit`, `npm run typecheck`, `npm run lint` et `npm run build`. Les exécuter selon les fichiers effectivement modifiés et reporter leurs résultats réels dans le bilan. Depuis D12, les tests PHPUnit utilisent la base MySQL isolée `academia_test`, distincte de la base applicative. La recette SMTP réelle reste indépendante des tests avec notifications simulées.

### Journal des décisions

| Date | Décision | Responsable | Impact / étape |
| --- | --- | --- | --- |
| 22 octobre 2026 | Cadrage M1, matrice de moindre privilège et décisions D01 à D10 approuvés par le demandeur. | Demandeur, nom/fonction à compléter si requis | Étape 0 |

### Bilan commun à renseigner après chaque étape

- Statut : à faire / en cours / bloquée / à valider / validée.
- Fichiers modifiés : chemins réellement modifiés.
- Vérifications : commande, résultat et date.
- Preuves : test, rapport ou parcours réellement observé.
- Limites : test non exécuté, dépendance manquante ou anomalie.
- Décision : acceptée ou corrections demandées, validateur et date.

Une case cochée ou un code présent ne constitue pas à lui seul une preuve de validation.