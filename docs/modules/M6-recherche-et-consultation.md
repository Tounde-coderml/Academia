# Plan de conception et d’implémentation — M6 Recherche et consultation

> **Projet :** Academia — Bibliothèque des épreuves ESGC VAK  
> **Version :** 1.0 — 8 novembre 2026  
> **Statut :** implémentation M6 réalisée dans le dépôt; validation technique partielle. La recette MySQL, performance, accessibilité et métier reste ouverte.  
> **Base de conception :** cahier des charges ESGC VAK, architecture logicielle (section 5.2.6), code M1, M3, M4 et état du dépôt au 8 novembre 2026.

## Résumé exécutif

M6 est l’interface de consultation de la bibliothèque. Il aide les utilisateurs connectés à retrouver une épreuve publiée, à vérifier ses informations, à consulter un aperçu lorsque le navigateur sait lire le fichier et à télécharger le document. M6 ne crée ni ne modifie les épreuves : M4 demeure propriétaire des données et du stockage privé. M3 fournit les filières, niveaux, matières, associations et semestres. M7 fournit désormais les pages personnelles de favoris et d’historique à partir des événements enregistrés par le parcours M6.

Le parcours M6 décrit dans ce document est maintenant implémenté : recherche paginée, filtres cumulables, favoris, fiche, historique minimal, pages de filière et de matière, aperçu contrôlé des fichiers. Ce plan conserve les vérifications de recette qui restent nécessaires avant l’acceptation complète.

## 1. Identification et responsabilité

| Élément | Définition |
|---|---|
| Module | M6 — Recherche et consultation |
| Famille | Cycle de vie des épreuves |
| Responsabilité principale | Permettre de découvrir et consulter les seules épreuves publiées |
| Acteurs | Étudiant, Responsable de classe, Administration, Administrateur |
| Dépendances | M1 pour l’authentification; M3 pour les référentiels; M4 pour les épreuves et fichiers; M7 pour favoris et historique |
| Données persistées par M6 | Aucune. Les requêtes interrogent les données détenues par M3 et M4. |
| Interface applicative | Application web Laravel, Inertia, Vue 3 et TypeScript |
| Responsable métier | À renseigner par l’ESGC VAK lors de l’acceptation formelle |

## 2. Contexte et objectifs

La bibliothèque doit rendre les épreuves publiées faciles à retrouver sur téléphone et ordinateur. Les informations de classement sont réparties entre les métadonnées M4 et les référentiels M3. Une recherche doit donc interroger ces relations sans recopier les données et sans exposer les dépôts en attente ou refusés.

### Objectifs mesurables

- Présenter les épreuves publiées avec titre, matière, filière, niveau, année scolaire, semestre et type.
- Rechercher dans le titre, le nom de matière et le nom de filière.
- Cumuler les filtres filière, niveau, matière, semestre, année et type.
- Trier les résultats selon une liste fermée de choix et les paginer côté serveur.
- Fournir une fiche détaillée, un aperçu sûr lorsque le format est supporté et un téléchargement protégé.
- Permettre la navigation par filière puis par matière.
- Répondre en moins de 3 secondes sur un jeu de référence de 5 000 épreuves; télécharger 10 Mo en moins de 10 secondes dans l’environnement de recette.
- Atteindre la cible de 200 utilisateurs simultanés définie dans le cahier des charges, après mesure avec l’infrastructure de recette.
- Permettre de retrouver une épreuve en moins d’une minute et en trois clics ou moins dans les parcours retenus.

## 3. Périmètre fonctionnel

### Inclus dans M6

- Catalogue paginé accessible à chacun des quatre rôles autorisés.
- Recherche plein texte limitée aux champs du CDC : titre, matière et filière; pas de recherche dans le contenu binaire du fichier.
- Filtres cumulatifs : filière, niveau, matière, semestre, année scolaire et type.
- Tri parmi les options fermées et ordre par défaut du plus récemment publié au plus ancien.
- Réinitialisation de la recherche et retrait des filtres par modification de l’URL.
- États de liste vide, de recherche sans résultat, de chargement et d’erreur lisibles.
- Page d’une filière affichant ses matières actives.
- Page d’une matière présentant ses épreuves publiées.
- Fiche d’une épreuve avec métadonnées, aperçu des PDF/JPEG/PNG et action de téléchargement.
- Enregistrement des ouvertures de fiche et des téléchargements acceptés via événements et tables locales d’intégration.
- Affichage et modification du favori propre à l’utilisateur connecté.

### Hors périmètre de M6

- Dépôt, édition, publication, refus ou suppression d’épreuve : M4 et le parcours de revue M5.
- Administration des filières, niveaux, matières et associations : M3.
- Consultation et retrait des favoris personnels, ainsi que consultation de l’historique personnel : M7. Les événements nécessaires sont enregistrés par le parcours M6.
- Recherche dans les scans, photos, PDF ou documents Word; OCR; suggestions de saisie; commentaires; corrigés annotés.
- Conversion de fichiers Word pour les prévisualiser. Les formats non prévisualisables sont téléchargeables.
- Publication ou livraison directe d’un chemin de stockage privé.

## 4. Utilisateurs et scénarios

### Acteurs

| Acteur | Besoin dans M6 |
|---|---|
| Étudiant | Retrouver rapidement une épreuve de sa filière, la lire ou la télécharger. |
| Responsable de classe | Consulter les épreuves publiées; les dépôts en attente restent dans M5. |
| Administration | Consulter les ressources publiées sans droit d’édition induit par M6. |
| Administrateur | Utiliser le catalogue comme les autres rôles; ses droits M4 d’administration ne sont pas exposés par M6. |

### Parcours principaux

**Recherche directe.** L’utilisateur ouvre le catalogue, saisit un mot-clé, choisit éventuellement des filtres, soumet le formulaire GET, puis ouvre une fiche. L’adresse conserve les critères pour que le retour navigateur et le partage de recherche fonctionnent.

**Navigation pédagogique.** Depuis une fiche ou une liste, l’utilisateur ouvre une filière, choisit l’une de ses matières, parcourt les épreuves publiées de cette matière et ouvre une fiche.

**Consultation de document.** La fiche vérifie que l’épreuve est publiée. Le serveur M4 fournit un aperçu inline uniquement pour un MIME sûr autorisé; sinon, l’interface propose le téléchargement. Le chemin physique et le nom interne du stockage ne sont jamais envoyés au client.

**Aucune correspondance.** Le système conserve les filtres choisis, annonce zéro résultat et propose de les retirer ou de réinitialiser la recherche.

## 5. Règles métier et décisions de conception

| ID | Règle |
|---|---|
| M6-R01 | Toutes les pages de catalogue exigent une session valide et un compte vérifié, conformément aux routes privées M1. |
| M6-R02 | Le catalogue, les pages de filière/matière et les fiches n’exposent que les épreuves dont `status = published`. Une épreuve en attente ou refusée répond comme une ressource inexistante à une URL directe. |
| M6-R03 | Les filtres sont cumulés par ET; chaque résultat doit respecter chacun des filtres fournis. |
| M6-R04 | Les valeurs de tri proviennent d’une liste fermée; aucune colonne reçue du client n’est interpolée dans une requête SQL. |
| M6-R05 | Les paramètres de recherche et filtres sont validés serveur, de longueur et de valeur bornées. Les valeurs inconnues produisent une erreur de validation compréhensible. |
| M6-R06 | La recherche mot-clé porte sur le titre, le nom de matière et le nom de filière seulement. Les requêtes sont paramétrées par Eloquent. |
| M6-R07 | M6 ne renvoie jamais `file_path`, `file_sha256` ou un lien vers le stockage. Le navigateur transmet uniquement l’identifiant d’épreuve aux routes contrôlées de M4. |
| M6-R08 | Aperçu inline autorisé uniquement pour PDF, JPEG et PNG après contrôle du statut publié, de la permission et du MIME réel stocké. Les autres formats forcent le téléchargement. |
| M6-R09 | Les titres et valeurs de référentiel affichés sont échappés par Vue. Aucun HTML provenant d’un dépôt n’est rendu avec `v-html`. |
| M6-R10 | Le favori et les événements de lecture sont privés à l’utilisateur; ils sont stockés dans `epreuve_favorites` et `epreuve_activities`, puis présentés par M7. |

### Décisions proposées à faire confirmer à la recette métier

| Sujet | Proposition V1 | Effet |
|---|---|---|
| Ordre par défaut | Publication la plus récente d’abord | L’ordre est déterministe avec un second tri par identifiant. |
| Options de tri | Plus récent, année, titre, matière | Liste simple, testable et protégée côté serveur. |
| Nombre par page | 20 résultats | Ajustable par configuration si la recette le demande. |
| Recherche sans accents | Utiliser la collation MySQL insensible aux accents, si confirmée sur la base cible | Éviter un `LOWER()` ou une normalisation non portable sans besoin confirmé. |
| Formats d’aperçu | PDF, JPEG et PNG; DOC reste en téléchargement | Le MIME est vérifié par M4; aucune conversion de document. |
| Consultation | Émettre une consultation à l’ouverture réussie de la fiche publiée | Implémenté; chaque ouverture de fiche produit un événement utilisateur/épreuve. |
| Téléchargement | Émettre l’événement différé après que le serveur a accepté la réponse fichier | Implémenté via `defer`; cela ne certifie pas que le navigateur a terminé l’enregistrement. |
| Auteur du dépôt | Ne pas montrer aux étudiants dans la fiche | Le CDC ne le demande pas; M4 garde la donnée interne disponible pour les personnes habilitées. |

Ces décisions ne sont pas des validations métier implicites. Toute modification de périmètre ou d’acteurs doit être ajoutée au journal de décision avant acceptation finale.

## 6. Données et contrats

### Sources de données

| Donnée affichée | Propriétaire | Lecture par M6 |
|---|---|---|
| Statut, titre, année, semestre, type, date de publication et taille | M4 (`epreuves`) | Seulement pour `published` |
| Filière, niveau et matière d’une épreuve | M3 via les clés étrangères M4 | Chargement groupé des relations |
| Liste active des filières, niveaux, matières et associations | M3 (`/referentiels/actifs`) | Réutiliser l’endpoint ou des projections limitées aux champs d’affichage |
| MIME et réponse fichier | M4, stockage `local` privé | Accès par identifiant et permission; pas de chemin client |
| Favori / historique minimal | Tables d’intégration M6 | Favoris isolés par utilisateur et événements de consultation/téléchargement |

### Paramètres de recherche proposés

| Paramètre URL | Type | Validation | Valeur par défaut |
|---|---|---|---|
| `q` | chaîne | facultatif, 120 caractères max; espaces extérieurs retirés | vide |
| `filiere_id` | entier | filière existante; liste d’options actives | aucun |
| `niveau_id` | entier | niveau existant; liste active | aucun |
| `matiere_id` | entier | matière existante; liste active | aucun |
| `semester` | entier | 1 ou 2, issu de `App\Semestre` | aucun |
| `academic_year` | chaîne | année au format `YYYY-YYYY`, idéalement issue des années ayant au moins une publication | aucun |
| `type` | énumération | `examen`, `rattrapage`, `devoir`, `controle_continu` | aucun |
| `sort` | énumération | `recent`, `year`, `title`, `subject` | `recent` |
| `page` | entier | supérieur ou égal à 1 | 1 |

Les paramètres sont transportés par GET, gardés dans la pagination et supprimés par le lien de réinitialisation. La validation de forme de l’année ne remplace pas la validation de sa présence dans le catalogue.

### Projection de résultat

La page reçoit uniquement les champs nécessaires : `id`, `title`, `filiere`, `filiere_id`, `niveau`, `matiere`, `matiere_id`, `academic_year`, `semester`, `type`, `published_at`. Aucun champ de stockage privé ou d’identité d’auteur n’est projeté pour le catalogue étudiant. La fiche ajoute `file_size` et `can_preview` calculé côté serveur.

## 7. Routes et responsabilités

Les routes du dépôt suivent le style web/Inertia existant; elles sont toutes sous `auth` et `verified`.

| Méthode / URI | Nom | Responsable | But |
|---|---|---|---|
| `GET /epreuves` | `catalogue.index` | M6 | Liste, recherche, filtres, tri et pagination |
| `GET /epreuves/{id}` | `catalogue.show` | M6 | Fiche d’une épreuve publiée; identifiant numérique |
| `GET /filieres/{id}/epreuves` | `catalogue.filieres.show` | M6/M3 | Matières actives d’une filière |
| `GET /matieres/{id}/epreuves` | `catalogue.matieres.show` | M6/M3 | Liste publiée filtrée par matière |
| `GET /epreuves/{id}/fichier` | `epreuves.file` | M4 | Téléchargement protégé |
| `GET /epreuves/{id}/fichier?preview=1` | `epreuves.file` | M4 | Réponse inline limitée aux PDF/images sûrs |

M6 n’ajoute pas de route de mutation des épreuves. À terme, M4 peut exposer des routes nommées distinctes `/apercu` et `/telecharger` si cela clarifie le contrat; ce changement doit garder une vérification unique et centralisée des droits.

## 8. Architecture applicative

### Serveur

- `CatalogueSearchRequest` valide les critères et normalise la saisie.
- `CatalogueController` construit une requête Eloquent limitée à `published`, applique les filtres par ET, ordonne selon une liste blanche et pagine à 20 éléments.
- Les relations `filiere`, `niveau` et `matiere` sont chargées avec `with()` pour éviter une requête par résultat.
- Les options des filtres proviennent des référentiels M3 actifs; les semestres proviennent de `App\Semestre`.
- La fiche retrouve l’épreuve publiée seulement et envoie à Inertia une projection explicite.
- M4 reste le seul contrôleur qui lit les octets du fichier depuis le disque privé.

### Interface

- `CatalogueIndex.vue` contient la recherche GET, les filtres, le tri, les cartes de résultat, l’état vide et la pagination.
- `FiliereShow.vue` liste les matières actives et conduit vers leurs épreuves.
- `EpreuveShow.vue` affiche les métadonnées, l’aperçu contrôlé ou le message de téléchargement.
- Le tableau de bord donne un accès direct au catalogue.
- Les composants conservent libellés, focus visible, navigation clavier, mise en page mobile et annonces de résultats.

### Indexation proposée

Le schéma M4 possède déjà un index `(status, submitted_at)` et un index de détection des doublons. La migration M6 doit mesurer et, si les requêtes le justifient, ajouter un index adapté au catalogue, par exemple `(status, published_at, id)` et des index simples sur les clés effectivement filtrées (`filiere_id`, `niveau_id`, `matiere_id`, `academic_year`, `semester`, `type`). Éviter d’ajouter tous ces index sans mesure : certaines clés sont déjà couvertes par une contrainte ou l’index composite de M4. Les recherches `LIKE '%terme%'` ne sont pas accélérées par un index B-tree classique; si le test de charge dépasse 3 secondes, évaluer un index FULLTEXT MySQL sur titre et relations ou une stratégie adaptée, sans introduire de moteur externe dans V1.

## 9. Sécurité, confidentialité et robustesse

- Toutes les pages et tous les fichiers exigent l’authentification et le statut vérifié.
- Les dépôts non publiés doivent répondre `404` depuis la fiche et la route fichier accessible au catalogue; le test vérifie également l’accès direct.
- La réponse de fichier est construite par M4 à partir de l’identifiant du modèle, jamais d’un chemin libre.
- Les types prévisualisés sont une liste blanche serveur. `X-Content-Type-Options: nosniff` et `Cache-Control: private, no-store` restent présents.
- Pour DOC, la réponse impose `Content-Disposition: attachment`; aucun aperçu HTML n’est tenté.
- Les champs de requête sont validés, les valeurs de tri fermées et toutes les valeurs liées à la recherche passent par les paramètres Eloquent.
- Les erreurs de stockage ou fichier absent produisent une réponse sûre et n’exposent ni trace interne ni chemin local.
- Les données M6 n’exposent pas le hash SHA-256, le chemin interne, l’auteur aux étudiants ni des commentaires rejetés.
- Ajouter une limitation de fréquence au téléchargement seulement si la recette d’exploitation le juge nécessaire; ne pas bloquer une classe entière derrière un seuil trop bas.

## 10. Plan d’implémentation détaillé

Les fonctionnalités des étapes 0 à 7 sont implémentées côté dépôt et les scénarios de test de l’étape 9 sont écrits; l’exécution Feature, les recettes de navigateur, les mesures de l’étape 8 et l’acceptation de l’étape 10 restent nécessaires.

### Étape 0 — Valider le périmètre et les décisions

1. Confirmer avec le responsable métier les rôles pouvant consulter; proposition : les quatre rôles déjà prévus par la matrice M1.
2. Confirmer les champs de recherche, le cumul des filtres, les tris et la taille de page.
3. Confirmer que les accents sont traités sans distinction dans la base cible, ou documenter la recherche exacte.
4. Confirmer PDF/JPEG/PNG comme formats d’aperçu et DOC comme téléchargement seulement.
5. Fixer avec M7 la définition d’une consultation et le moment d’écriture d’un téléchargement réussi.
6. Consigner le responsable métier, la date et les décisions approuvées dans ce document.

**Livrables :** périmètre approuvé, décisions fermées, matrice de traçabilité des critères.

### Étape 1 — Auditer les contrats M1, M3 et M4

1. Vérifier les middleware `auth` et `verified` et les permissions `exams.view_published` des quatre rôles.
2. Vérifier le schéma `epreuves`, les clés étrangères vers le triplet filière/niveau/matière et les noms exacts des colonnes.
3. Vérifier les référentiels et l’endpoint M3 actifs, dont S1/S2.
4. Vérifier la route M4 fichier : statut, rôle, propriété du dépôt, stockage privé et en-têtes.
5. Vérifier que le MIME provient de la détection serveur et que DOCX reste refusé par M4.

**Livrables :** contrat d’intégration vérifié; liste des écarts M4/M6; aucune duplication de stockage.

### Étape 2 — Stabiliser validation et modèle de recherche

1. Finaliser `CatalogueSearchRequest` avec règles, normalisation et messages français.
2. Rejeter `sort` inconnu; valider les identifiants; accepter uniquement les semestres et types définis.
3. Vérifier l’année au format et limiter le choix aux années disponibles afin d’éviter des filtres impossibles.
4. Décider si les filtres incohérents filière/niveau/matière retournent simplement zéro résultat ou s’ajustent de manière dynamique; V1 recommande zéro résultat et conservation des choix.
5. Garder les critères sous forme d’URL GET et vérifier réinitialisation et retour navigateur.

**Livrables :** Form Request stable, exemples d’URL valides/invalides, contrat de critères documenté.

### Étape 3 — Construire la requête catalogue sûre et efficace

1. Partir de `Epreuve::query()->where('status', 'published')` pour tout écran M6.
2. Grouper la recherche mot-clé sur titre OU matière OU filière.
3. Ajouter chacun des filtres par ET, sans concaténation SQL ni noms de colonne contrôlés par le client.
4. Charger les relations par eager loading et ne projeter que les champs consommés.
5. Appliquer le tri via `match`/liste blanche et ajouter un ordre secondaire stable.
6. Paginer côté serveur et conserver les paramètres dans les liens.
7. Calculer le nombre de résultats sur le même filtre que la page.
8. Contrôler les requêtes avec `EXPLAIN` sur la base de recette avant de créer une migration d’index.

**Livrables :** résultat exact pour filtres simples et combinés; absence de fuite d’états privés; mesure initiale de performance.

### Étape 4 — Finaliser le catalogue et les filtres Vue

1. Afficher les filtres avec labels explicites et options fournies par M3.
2. Ajouter le mot-clé, les six filtres, le tri et les boutons rechercher/réinitialiser.
3. Afficher titre, matière, filière, niveau, année, semestre et type dans chaque résultat.
4. Ajouter un message de catalogue vide et un message de recherche sans résultat distincts si nécessaire.
5. Ajouter le nombre de résultats avec `aria-live` sans annonces répétitives intrusives.
6. Présenter la pagination avec libellés compréhensibles, état de page et focus visible.
7. Vérifier les cas de filtre actif masqué par pagination, retour arrière et rafraîchissement.

**Livrables :** catalogue utilisable au clavier, URL reproductible, rendu à 320 px et 200 % de zoom.

### Étape 5 — Construire les parcours pédagogiques

1. La page filière affiche son code, son nom et les matières actives liées par une association active.
2. Chaque matière mène à sa page de résultats publiée.
3. La page matière affiche le nom et filtre le catalogue sans perdre la possibilité de trier/paginer.
4. Une filière ou matière inactive/inexistante répond sans exposer de données incohérentes.
5. Ajouter des liens de retour au catalogue et un fil de navigation si la recette le demande.

**Livrables :** parcours accueil → filière → matière → fiche dans la limite de trois clics retenue.

### Étape 6 — Finaliser la fiche et l’aperçu contrôlé

1. Charger uniquement une épreuve publiée; tester l’URL d’une épreuve pending/rejected.
2. Projeter uniquement les métadonnées prévues; ne pas transmettre `file_path`, hash ou auteur au public.
3. Fournir l’aperçu PDF/JPEG/PNG via le contrôleur M4, après contrôle statut/permission/MIME.
4. Afficher un cadre d’aperçu titré et accessible; afficher un lien de téléchargement distinct.
5. Pour DOC, masquer l’aperçu et expliquer qu’un téléchargement est requis.
6. Prévoir un message compréhensible si le fichier manque ou que le stockage échoue.
7. Tester le nom téléchargé neutralisé, le MIME, `nosniff` et `private, no-store`.

**Livrables :** fiche et document accessibles sans URL de stockage public; refus identique pour épreuve cachée et absente.

### Étape 7 — Ajouter l’intégration M7

Le parcours M6 enregistre les consultations de fiches et les téléchargements autorisés. M7 présente ces activités et les favoris de l’utilisateur connecté sans exposer les enregistrements d’un autre compte.

1. `EpreuveConsultee` est émis sur ouverture de la fiche publiée avec les seuls identifiants utilisateur/épreuve.
2. `EpreuveTelechargee` est émis après préparation de la réponse de téléchargement; l’aperçu n’est pas compté.
3. Les listeners ajoutent une ligne d’activité privée à l’utilisateur.
4. Les favoris ont une contrainte unique utilisateur/épreuve et des actions idempotentes PUT/DELETE.
5. Les pages catalogue et fiche exposent l’état du favori du compte courant seulement.
6. Des tests Feature couvrent favori, consultation et téléchargement; l’exécution reste à confirmer dès qu’une base MySQL de test fonctionnelle est disponible.

**Livrables :** contrat M6/M7 minimal et implémentation présents; la reprise et l’évolution par le module M7 restent à planifier.

### Étape 8 — Optimiser et valider la performance

1. Générer ou préparer un jeu représentatif de 5 000 épreuves publiées et non publiées.
2. Mesurer catalogue initial, recherche mot-clé et filtres cumulés avec outils de base de données et navigateur.
3. Vérifier le nombre de requêtes : eager loading des relations, pagination bornée, aucune boucle d’accès DB par carte.
4. Analyser les plans `EXPLAIN` et ajouter uniquement les index prouvés utiles dans une migration réversible.
5. Mesurer téléchargement de 10 Mo et comportement du serveur/proxy au-delà de la limite applicative.
6. Exécuter un test de charge jusqu’à 200 utilisateurs simultanés dans l’environnement de recette; publier les temps p50/p95, erreurs et configuration.
7. Si le seuil de 3 secondes n’est pas tenu, optimiser puis répéter les mêmes mesures; ne pas introduire un moteur externe sans décision approuvée.

**Livrables :** rapport reproductible de performance, migration d’index justifiée, cible mesurée ou écart explicitement accepté.

### Étape 9 — Tester les règles et les écrans

**Tests Feature Laravel à ajouter :**

1. Visiteur redirigé vers la connexion; compte non vérifié traité selon M1.
2. Chacun des quatre rôles peut accéder au catalogue si sa permission de lecture le permet.
3. Résultats publiés seulement; pending et rejected absents des listes, pages et fiche directe.
4. Recherche sur titre, matière et filière; valeur contenant `%`, `_`, guillemets et Unicode ne produit ni injection ni erreur.
5. Filtres individuels et combinaison filière+niveau+matière+semestre+année+type.
6. Valeurs invalides, paramètres inconnus et tri non autorisé refusés proprement.
7. Tri déterministe, pagination, conservation des filtres et résultat vide.
8. Pages filière/matière : associations actives uniquement, épreuves publiées uniquement.
9. Fiche publiée et fiche non publiée; projection Inertia sans chemins/hashes/auteur public.
10. Téléchargement et aperçu; formats PDF/images acceptés, DOC forcé en attachement, fichier absent et MIME non autorisé refusés.
11. Droits M4 : aucun chemin libre, aucune épreuve non publiée livrée directement.
12. Intégration M7 après livraison de son contrat.

**Tests frontend à ajouter :**

- Rendu et soumission des filtres; conservation des valeurs lors du retour; liste vide; pagination; liens fiche/filière/matière; alternatives accessibles; absence d’aperçu pour DOC.

**Recette navigateur :** Chrome/Firefox (et Safari si environnement disponible), largeur 320 px et bureau, clavier seul, zoom 200 %, lecteur d’écran ciblé, retour navigateur et téléchargement réel.

### Étape 10 — Préparer livraison et acceptation

1. Exécuter les migrations sur base vide et base M4 déjà peuplée; confirmer aucune donnée M4 perdue.
2. Lancer les tests PHP M6, la suite Feature complète, les tests unitaires Vue, TypeScript, ESLint, Pint, build et formatage applicable.
3. Faire vérifier les rôles, tris, accentuation, formats et historique par le responsable métier.
4. Vérifier les paramètres `APP_DEBUG`, HTTPS, cookies, accès stockage privé, taille des réponses et logs en recette.
5. Consigner versions, résultats, navigateur, jeux de données et limites mesurées.
6. Obtenir l’acceptation explicite du responsable métier; ne pas confondre build réussi et validation métier.

**Livrables :** rapport de recette, critères CA cochés avec preuves, approbation et plan de retour arrière.

## 11. Critères d’acceptation

| ID | Critère de sortie | Preuve attendue |
|---|---|---|
| CA-M6-01 | Un utilisateur autorisé accède au catalogue et aux pages de consultation. | Tests d’accès par rôle et recette navigateur |
| CA-M6-02 | Seules les épreuves publiées apparaissent dans listes, filtres et fiches. | Tests Feature, dont accès direct pending/rejected |
| CA-M6-03 | Chaque carte affiche au minimum titre, matière, année, semestre et type; filière/niveau sont également visibles. | Assertions Inertia et recette |
| CA-M6-04 | Recherche par mot-clé cible titre, matière et filière. | Tests Feature de chaque champ et combinaisons |
| CA-M6-05 | Tous les filtres se cumulent correctement et sont conservés dans l’URL/pagination. | Tests Feature et navigateur |
| CA-M6-06 | Les tris autorisés sont stables et le tri par défaut est le plus récent d’abord. | Tests de résultat ordonné |
| CA-M6-07 | Une recherche vide ou sans résultat donne un message utile et une action de réinitialisation. | Test Vue et recette |
| CA-M6-08 | La page filière mène aux matières actives; la page matière ne montre que les épreuves publiées. | Tests Feature et parcours navigateur |
| CA-M6-09 | La fiche d’une épreuve non publiée est indistinguable d’une fiche inexistante. | Tests statut 404 et absence d’informations |
| CA-M6-10 | PDF/JPEG/PNG sont prévisualisables avec contrôle M4; les autres formats sont téléchargeables seulement. | Tests headers, MIME, stockage privé et navigateur |
| CA-M6-11 | Les liens fichier n’exposent jamais de chemin physique; tentative directe non autorisée refusée. | Tests sécurité de M4 consommés par M6 |
| CA-M6-12 | Favori et consultation/téléchargement sont intégrés selon le contrat M7. | Tests intermodules M6/M7 |
| CA-M6-13 | Interfaces fonctionnelles au clavier, à 320 px et à 200 % de zoom. | Liste de recette accessibilité et preuves navigateur |
| CA-M6-14 | Recherche sous 3 s à 5 000 enregistrements; transfert de 10 Mo sous 10 s; charge 200 utilisateurs mesurée. | Rapport de performance horodaté, p50/p95 et configuration |
| CA-M6-15 | Suite de qualité complète réussie; acceptation métier datée. | CI verte, rapport de recette et approbation |

## 12. État actuel dans le dépôt au 8 novembre 2026

| Élément | État constaté | Preuve / reste à faire |
|---|---|---|
| Routes et droits | Catalogue, fiche, filière, matière et favoris sous `auth`, `verified` et `can:exams.view_published` | `route:list` et matrice navigateur des rôles vérifiés; suite PHP actuelle : 103 tests, 1 056 assertions |
| Recherche et filtres | Recherche titre/matière/filière, accents selon collation MySQL, filtres cumulés, tri allowlist, pagination 20 | Vérifiés par les tests Feature sur MySQL; `CatalogueTest` : 18 tests, 247 assertions |
| Projection/sécurité | Liste blanche des champs Inertia; pending/rejected masqués; filtres validés | Assertions Feature réussies pour les chemins/hash absents et 404 |
| Parcours filière/matière | Matières actives et catalogue publié associés | Tests Feature réussis; recette navigateur réelle encore à faire |
| Fiche et fichier | Aperçu PDF/JPEG/PNG, téléchargement protégé, réponse privée sans cache | Tests Feature réussis pour aperçu, DOC, en-têtes et téléchargement |
| Favoris et historique minimal | Migrations, modèles, événements/listeners, actions idempotentes et état privé | Événements enregistrés; tests Feature réussis |
| Tests frontend / qualité | 20 tests unitaires frontend réussis; typecheck, lint, formatage et build réussis | Commandes complètes exécutées le 8 novembre 2026 |
| Qualité PHP | Suite complète : 103 tests et 1 056 assertions réussis; Pint réussi | `php artisan test --compact`, Pint, routes et listeners vérifiés |
| Recette navigateur | Matrice des rôles et catalogue vérifiés; aucun débordement horizontal constaté sur le catalogue à 320 px et 1 280 px | Les quatre rôles ont des réponses d’accès conformes; exercices d’accessibilité complets non effectués |
| Performance | Non mesurée à 5 000 épreuves ou 200 connexions simultanées | Étape 8 à effectuer dans un environnement de recette représentatif |
| Acceptation métier | Non consignée | Nom/date/approbation du responsable ESGC VAK requis |

**Résultats de validation automatisée au 8 novembre 2026 :** `CatalogueTest` passe 18 tests et 247 assertions; la suite PHP complète passe 103 tests et 1 056 assertions. Les 20 tests frontend, TypeScript, ESLint, build, Prettier et Pint passent. La matrice navigateur a confirmé les accès par rôle et le rendu du catalogue aux largeurs testées de 320 px et 1 280 px. La réponse fichier utilise un cache privé sans stockage.

## 13. Risques et mesures

| Risque | Mesure |
|---|---|
| Un dépôt pending est exposé par une nouvelle route oubliant le filtre de statut | Centraliser la lecture publique et tester les URLs directes. |
| Une recherche complexe devient lente | Mesurer avec 5 000 lignes, eager loading et index évalués via EXPLAIN. |
| Les filtres conduisent à zéro résultat sans explication | Conserver les critères et proposer la réinitialisation. |
| Le MIME ou le chemin client est manipulé | M4 demeure l’unique lecteur; liste MIME de prévisualisation côté serveur. |
| L’historique enregistre un téléchargement échoué | Contrat M7 explicite et test d’échec de stockage. |
| M3 désactive une matière encore référencée | Distinguer les valeurs de filtre actives des libellés historiques; conserver l’intégrité référentielle M4. |
| Cible de 200 utilisateurs non vérifiée | Test de charge dans une infrastructure représentative avant annonce de conformité. |
| Aperçu iframe inutilisable avec certains navigateurs | Garder toujours l’action de téléchargement et une description accessible. |

## 14. Liste de fin de module

- [x] Recherche, filtres, tri, pagination et projection serveur implémentés.
- [x] Contrôle des rôles, vérification du compte et masquage pending/rejected implémentés.
- [x] Pages catalogue, filière, matière et fiche créées.
- [x] Aperçu/téléchargement protégé intégré à M4.
- [x] Favoris et historique minimal branchés au parcours M6.
- [x] Tests Feature et tests Vue écrits.
- [x] Tests Vue, TypeScript, ESLint, build, syntaxe PHP, Pint, routes et listeners réussis.
- [x] Suite Feature PHP réussie sur MySQL de test (`CatalogueTest` 18 tests; suite PHP complète 103 tests / 1 056 assertions).
- [x] Matrice navigateur des permissions testée pour les quatre rôles; catalogue contrôlé à 320 px et 1 280 px.
- [ ] Accessibilité clavier, mobile, zoom et navigation navigateur vérifiée.
- [ ] Cibles de performance mesurées sur 5 000 épreuves et 200 connexions.
- [ ] Validation métier et approbation consignées.

**État de sortie :** le code du parcours M6 est implémenté et les validations automatisées backend/frontend sont réussies. Le module n’est pas déclaré validé à 100 % au sens des critères CA-M6-01 à CA-M6-15 : les mesures de charge, les vérifications complètes d’accessibilité et l’acceptation métier restent à réaliser.

## 15. Journal des décisions

| Date | Décision / constat | Statut |
|---|---|---|
| 8 novembre 2026 | Le périmètre M6 a été transcrit depuis l’architecture 5.2.6 et relié aux contrats M1/M3/M4 du dépôt. | Documenté |
| 8 novembre 2026 | Catalogue, sécurité, fiches, aperçu, favoris/historique minimal et tests frontend/Feature ont été implémentés; un en-tête de cache public de la réponse fichier a été corrigé. | Suite PHP complète, tests Vue, typecheck, lint, build et Pint réussis; performance, accessibilité réelle et validation métier encore ouvertes |
| 8 novembre 2026 | La consultation est comptée à l’ouverture de fiche; l’activité de téléchargement est différée après réponse acceptée; l’ordre récent est stable par identifiant. | Décisions d’implémentation; validation métier toujours ouverte |

---

**Fin du plan M6.** L’implémentation et les tests automatisés du parcours sont terminés. La conformité à 100 % attend encore les mesures de performance, la recette d’accessibilité complète et l’acceptation métier; aucune de ces preuves n’est supposée acquise.
