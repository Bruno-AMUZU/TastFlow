# TaskFlow

Mini-Trello en **PHP natif + MySQL** : des tableaux, des colonnes, des tâches qu'on glisse d'une colonne à l'autre.
Projet d'entraînement, style visuel « Ludique » (pastels, bords noirs, ombres décalées).

## Lancer le projet

Prérequis : Docker Desktop.

```bash
cp .env.example .env        # puis choisis tes mots de passe dans .env
docker compose up -d --build
```

| Service      | Adresse                 |
|--------------|-------------------------|
| Application  | http://localhost:9000   |
| phpMyAdmin   | http://localhost:8086   |
| MySQL        | localhost:3307          |

Compte de démo : `demo@taskflow.local` / `demo1234`

> **La base est créée par les fichiers de `sql/` (dans l'ordre : `001_…`, `002_…`) uniquement au premier démarrage**,
> quand le volume `db-data` est vide. Pour repartir de zéro : `docker compose down -v` puis `docker compose up -d`.
> ⚠️ `-v` efface toutes les données de la base.

## Mettre à jour une base existante (migrations)

Quand un nouveau fichier `sql/00X_….sql` arrive et que ta base contient déjà des données,
exécute-le **une seule fois** (PowerShell, depuis le dossier du projet) :

```powershell
Get-Content sql/003_remove_default_labels.sql | docker compose exec -T db sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
```

Ou dans phpMyAdmin : sélectionne la base → onglet **Importer** → choisis le fichier.

| Migration          | Contenu                                                            |
|--------------------|--------------------------------------------------------------------|
| `001_init.sql`     | Tables `users`, `boards`, `board_columns`, `tasks` + données démo  |
| `002_labels.sql`   | Étiquettes multiples : tables `labels` et `task_labels`            |
| `003_remove_default_labels.sql` | Retire les étiquettes prédéfinies inutilisées       |

## Fonctionnalités

- Inscription / connexion (mots de passe hachés avec `password_hash`)
- Tableaux : créer (avec 3 colonnes par défaut), supprimer, voir l'avancement
- Tâches : ajouter, modifier (titre, description, échéance, colonne), supprimer
- Changer le statut d'une tâche : glisser-déposer, ou menu **⋯ › Marquer comme**
- Étiquettes : aucune imposée, chaque utilisateur crée les siennes ; plusieurs par tâche, à poser / retirer depuis le menu **⋯** ;
  créer ou supprimer les étiquettes d'un tableau (menu **⋯** ou page « Modifier la tâche »)
- Badge « En retard » quand l'échéance est passée

## Organisation du code

```
sql/                      Schéma de la base, une migration par fichier (001, 002…)
src/
├── index.php             Point d'entrée unique : lit ?page=… et appelle le bon contrôleur
├── app/
│   ├── bootstrap.php     Démarre la session, charge les fichiers
│   ├── db.php            Connexion PDO (variables d'environnement)
│   ├── helpers.php       e(), url(), render(), CSRF, messages flash…
│   ├── models/           Requêtes SQL (une fonction = une requête préparée)
│   └── controllers/      Logique des pages : valide les données, appelle les modèles, affiche une vue
├── views/                HTML des pages (layout.php = gabarit commun)
└── assets/               CSS et JavaScript
```

Le chemin d'une requête : `index.php?page=board&id=1` → `boards_show()` (contrôleur)
→ `board_find()`, `board_columns()`, `tasks_by_column()` (modèles) → `views/boards/show.php` (vue).

## Sécurité, en bref

- **Injections SQL** : toutes les requêtes passent par `prepare()` + `execute()`.
- **XSS** : tout texte affiché passe par `e()` (`htmlspecialchars`).
- **CSRF** : chaque formulaire POST contient un jeton vérifié par `csrf_check()`.
- **Accès** : chaque requête filtre sur l'utilisateur connecté, on ne peut pas ouvrir le tableau d'un autre.

## Idées pour la suite

- Inviter des membres sur un tableau (table `board_members`) et assigner les tâches
- Renommer / ajouter / réordonner les colonnes
- Sous-tâches (checklist) avec barre de progression
- Recherche et filtre par étiquette
- Passer à des URL propres (`/board/1`) avec la réécriture d'URL d'Apache
