# M5 — Validation des dépôts

> État : fonctionnalités principales implémentées et tests automatisés réussis. Validation à 100 % non confirmée : la recette navigateur complète des décisions de revue et l’acceptation métier restent ouvertes.

## Responsabilité

M5 permet aux utilisateurs disposant de `exams.review` d’examiner les dépôts étudiants en attente, d’ouvrir leur fichier privé, puis de publier ou de refuser le dépôt. Un refus doit inclure un motif visible par l’auteur. M4 reste propriétaire des épreuves, des fichiers et de leur statut en base.

## Fonctionnalités présentes

- File paginée ne présentant que les épreuves `pending`.
- Contrôle serveur de la permission `exams.review` pour la file, la publication et le refus.
- Accès au fichier en attente limité à son auteur ou à un utilisateur habilité à la revue.
- Publication avec date de publication et confirmation explicite en cas de doublon exact.
- Refus avec motif obligatoire, conservé et visible dans le suivi personnel de l’auteur.
- Retrait des dépôts décidés de la file, car celle-ci ne liste que l’état `pending`.
- Interface de revue avec retours de validation, erreurs et état vide.

## Critères de validation

| Critère | État observé | Preuve dans le dépôt |
|---|---|---|
| Un réviseur habilité voit les dépôts en attente | Implémenté et couvert par un test | `EpreuveController::reviewIndex`, `EpreuveTest::test_class_manager_can_review_all_student_submissions` |
| Un étudiant ne peut pas accéder à la file | Implémenté et couvert par un test | Route `epreuves.review`, `EpreuveTest::test_students_cannot_open_the_review_queue` |
| Un dépôt étudiant peut être publié | Implémenté et couvert par un test | `EpreuveController::publish`, `EpreuveTest::test_student_submission_waits_for_reviewer_publication` |
| Le refus exige un motif et conserve celui-ci | Implémenté et couvert par un test | `EpreuveController::reject`, `EpreuveTest::test_reviewer_must_provide_a_reason_to_reject_an_exam` |
| Le doublon demande une confirmation avant publication | Implémenté et couvert dans le flux de dépôt direct | `ReviewQueue.vue`, `EpreuveController::publish`, `EpreuveTest::test_duplicate_submission_requires_explicit_confirmation_before_direct_publication` |
| Le fichier en attente reste privé | Implémenté et couvert par un test | `EpreuveController::file`, `EpreuveTest::test_user_can_only_download_own_pending_exam` |
| L’auteur retrouve l’état de sa soumission | Implémenté et couvert par le test de suivi personnel | `EpreuveController::index`, `EpreuveTest::test_owner_can_see_their_submission_status` |
| Accès navigateur selon le rôle | Vérifié dans cette session | Les comptes Étudiant et Responsable de classe obtiennent respectivement 403 et 200 sur `/admin/revue-epreuves`; Administration et Administrateur obtiennent 200 |
| Recette navigateur de publication/refus | Non effectuée sur une épreuve existante | La soumission locale en attente n’a pas été modifiée ni publiée |
| Suite Feature M4/M5 | Réussie dans cette session | `php artisan test --compact tests/Feature/EpreuveTest.php` : 13 tests, 108 assertions |
| Suite PHP complète actuelle | Réussie dans cette session | `php artisan test --compact` : 103 tests, 1 056 assertions |
| Acceptation métier propre à M5 | À confirmer | Pas de validation M5 distincte consignée avant ce plan |

## Conclusion

Le périmètre fonctionnel central de M5 est implémenté et les tests Feature ciblés ainsi que la suite PHP complète passent sur MySQL. La matrice d’accès à la file de revue a également été vérifiée dans le navigateur pour les quatre rôles. Le module n’est pas déclaré validé à 100 % : la recette navigateur des actions publier/refuser et l’acceptation métier spécifique à M5 restent à effectuer. La soumission déjà en attente dans la base locale a été laissée intacte.
