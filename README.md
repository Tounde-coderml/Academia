# Academia — Bibliothèque des épreuves ESGC VAK

Academia est une application web destinée à l’ESGC VAK. Elle centralise les comptes, les référentiels pédagogiques et les épreuves afin de faciliter leur dépôt, leur validation et leur consultation.

## Fonctionnalités

- **M1 — Authentification et accès :** inscription des étudiants, connexion, vérification de l’adresse e-mail, réinitialisation du mot de passe et contrôle des permissions.
- **M2 — Comptes utilisateurs :** gestion administrative des comptes, rôles, activation, désactivation et restauration.
- **M3 — Catalogue pédagogique :** gestion des filières, niveaux, matières et associations, avec les semestres S1 et S2.
- **M4 — Épreuves et fichiers :** dépôt et suivi des épreuves, revue des dépôts étudiants, publication et téléchargement protégé. Les formats acceptés sont PDF, DOC, JPG et PNG, jusqu’à 20 Mo par fichier.

Les plans détaillés se trouvent dans [`docs/modules`](docs/modules/).

## Technologies

- PHP 8.3 ou supérieur et Laravel 13
- MySQL 8.4
- Inertia, Vue 3 et TypeScript
- Node.js 22 et npm
- PHPUnit, Vitest, ESLint, Vue TypeScript Checker, Laravel Pint et Vite

## Prérequis

Installez PHP avec les extensions `dom`, `fileinfo`, `mbstring`, `pdo_mysql` et `xml`, ainsi que Composer, MySQL 8.4+, Node.js 22 et npm.

Créez une base de données pour l’application et une base dédiée aux tests :

```sql
CREATE DATABASE academia CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE DATABASE academia_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

Configurez un utilisateur MySQL ayant accès aux deux bases.

## Installation

Depuis la racine du dépôt :

```bash
composer install
npm ci
cp .env.example .env
php artisan key:generate
```

Dans `.env`, configurez la base de données de développement :

```dotenv
APP_NAME=Academia
APP_URL=http://127.0.0.1:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=academia
DB_USERNAME=academia
DB_PASSWORD=votre_mot_de_passe
```

Le compte MySQL et son mot de passe doivent correspondre à ceux créés sur votre machine. Puis créez les tables et les données de référence :

```bash
php artisan migrate --seed
```

Le seeder ajoute les rôles et permissions, les filières GI/GT/GC, les niveaux L1/L2/L3 et des matières informatiques exemples pour GI. Il est idempotent : il peut être exécuté de nouveau sans créer de doublons.

### Configuration des e-mails

Le fichier `.env.example` utilise `MAIL_MAILER=log`. Avec cette configuration, les e-mails de vérification et de réinitialisation sont écrits dans les logs de l’application et ne sont pas envoyés à une boîte Gmail. Pour recevoir les messages, configurez un service SMTP dans `.env` et vérifiez ses identifiants et paramètres avant d’utiliser l’application.

Ne publiez jamais les secrets SMTP ou le fichier `.env` dans le dépôt.

## Déploiement gratuit

Le dépôt inclut un `Dockerfile` et un Blueprint Render (`render.yaml`) pour héberger l’application sur Render avec PostgreSQL et un stockage objet compatible S3 séparés. Le plan gratuit Render peut mettre le site en veille après une période d’inactivité : le premier accès suivant peut être lent. Les quotas et conditions des offres gratuites peuvent évoluer.

1. Poussez le dépôt sur GitHub, puis créez le service Render à partir du Blueprint `render.yaml`.
2. Créez une base PostgreSQL et un bucket privé compatible S3. Renseignez les variables d’environnement demandées par Render dans son tableau de bord, notamment `DB_URL`, `AWS_ACCESS_KEY_ID`, `AWS_SECRET_ACCESS_KEY`, `AWS_ENDPOINT_URL_S3`, `AWS_DEFAULT_REGION` et `AWS_BUCKET`.
3. Configurez `APP_KEY` avec une clé Laravel générée localement (`php artisan key:generate --show`), ainsi que les variables `MAIL_*` avec les identifiants d’un service SMTP. Ne partagez et ne commitez jamais ces secrets.
4. Pour le premier démarrage uniquement, renseignez `BOOTSTRAP_ADMIN_NAME`, `BOOTSTRAP_ADMIN_EMAIL` et un `BOOTSTRAP_ADMIN_PASSWORD` fort d’au moins 15 caractères. Le compte créé est vérifié et reçoit le rôle administrateur. Après confirmation de sa création, supprimez ces trois variables de Render.
5. Attendez la fin du déploiement et vérifiez que le contrôle de santé `/login` réussit. Les migrations sont exécutées au démarrage ; les fichiers déposés sont stockés sur le bucket privé afin de survivre aux redémarrages de Render.

Configurez les comptes et les secrets directement dans les tableaux de bord des fournisseurs, jamais dans le dépôt ni dans une conversation.

## Lancement en développement

Démarrez les processus nécessaires avec :

```bash
composer run dev
```

Cette commande lance le serveur Laravel configuré pour accepter les dépôts de fichiers de 20 Mo, Vite, la file d’attente et les logs. L’application est accessible à l’adresse configurée dans `APP_URL` (par défaut `http://127.0.0.1:8000`).

Pour créer le premier compte administrateur, utilisez la commande interactive :

```bash
php artisan users:create-administrator
```

## Tests et vérifications

Les tests PHP utilisent la base dédiée `academia_test`. Le fichier `phpunit.xml` sélectionne cette base ; les paramètres de connexion MySQL proviennent de l’environnement. Ne configurez jamais les tests pour utiliser une base contenant des données importantes.

```bash
php artisan test --compact
npm run test:unit
npm run typecheck
npm run lint
vendor/bin/pint --test
npm run build
```

Une vérification de formatage Prettier est également disponible :

```bash
npm run format:check
```

Le workflow [GitHub Actions](.github/workflows/ci.yml) s’exécute à chaque push et pull request. Il lance Pint, les tests PHP et frontend, le contrôle des types, ESLint et le build avec des services MySQL et PostgreSQL temporaires.

## Structure du dépôt

```text
app/                     Code Laravel (contrôleurs, modèles, règles métier)
database/migrations/     Évolution du schéma de base de données
database/seeders/        Rôles, permissions et référentiels initiaux
resources/js/            Interface Vue 3 / TypeScript
routes/web.php           Routes web et protections d’accès
tests/                   Tests PHPUnit et Vitest
docs/modules/            Plans fonctionnels et techniques M1 à M4
.github/workflows/       Automatisations GitHub Actions
docker/                  Configuration Apache et PHP pour l’image de déploiement
Dockerfile               Image de production
render.yaml              Blueprint Render
```

## Sécurité

Les clés, mots de passe, paramètres SMTP et autres secrets doivent rester dans l’environnement et ne doivent pas être commités. Les fichiers d’épreuves sont stockés sur le disque privé de Laravel et servis par une route protégée.
