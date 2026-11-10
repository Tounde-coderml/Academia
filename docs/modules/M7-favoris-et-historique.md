# M7 — Favoris et historique

**Statut :** parcours utilisateur implémenté; validation automatisée ciblée réussie.  
**Base de conception :** architecture logicielle, module 5.2.7, cahier des charges ESGC VAK et événements M6.

## Responsabilité

M7 permet à chaque utilisateur autorisé de retrouver ses épreuves favorites et son activité de consultation. Le module ne modifie pas les épreuves ni leurs droits de publication. M6 enregistre l’ouverture d’une fiche et le téléchargement d’un fichier; M7 présente ces données dans l’espace personnel.

## Fonctionnalités

- Afficher les favoris propres à l’utilisateur, du plus récent au plus ancien.
- Ajouter ou retirer un favori depuis les pages du catalogue et la liste personnelle.
- Afficher les consultations et téléchargements propres à l’utilisateur, du plus récent au plus ancien.
- Paginer les listes côté serveur.
- Ne présenter que les épreuves toujours publiées; les enregistrements d’un autre utilisateur ne sont jamais inclus.
- Fournir un état vide et des liens de retour au catalogue.

## Données et accès

Les favoris sont conservés dans `epreuve_favorites`; les actions dans `epreuve_activities`. Les pages `/mes-favoris` et `/historique` exigent une session authentifiée, une adresse vérifiée et la permission `exams.view_published`. Les requêtes filtrent explicitement par identifiant de l’utilisateur connecté.

Les événements pris en charge sont `consultation` et `telechargement`. Une prévisualisation de fichier n’est pas comptée comme un téléchargement.

## Validation

- `CatalogueTest` : 18 tests et 247 assertions réussis, y compris l’isolation des favoris/historiques et l’exclusion des épreuves non publiées.
- Frontend : tests unitaires, vérification TypeScript, lint et build réussis.
- Recette manuelle navigateur/accessibilité et acceptation métier : à effectuer.
