# Guide Complet des Scénarios YAML

## Table des matières

1. [Introduction](#introduction)
2. [Structure de base](#structure-de-base)
3. [Imports de scénarios](#imports-de-scénarios)
4. [Variables](#variables)
5. [Placeholders](#placeholders)
6. [Pipes (Transformateurs)](#pipes-transformateurs)
7. [Lookup dynamique](#lookup-dynamique)
8. [Types de données](#types-de-données)
9. [Pivot custom](#pivot-custom)
10. [Purge personnalisé](#purge-personnalisé)
11. [Exemples complets](#exemples-complets)
12. [Limitations](#limitations)

---

## Introduction

Les scénarios YAML permettent de créer des jeux de données de test de manière déclarative, sans écrire de code PHP. Ils sont idéaux pour :

- ✅ Insertion de données simples avec relations
- ✅ Tests fonctionnels rapides
- ✅ Prototypage de fixtures
- ✅ Données de démonstration

**Fichiers** : Les scénarios YAML se placent dans `prisms/yaml/*.yaml`

**Commandes** :
```bash
# Charger un scénario
php bin/console app:prism:load nom_prism --scope=mon_scope

# Purger un scénario
php bin/console app:prism:purge nom_prism --scope=mon_scope

# Lister les scénarios disponibles
php bin/console app:prism:list
```

---

## Structure de base

### Fichier minimal

```yaml
load:
  - table: users
    data:
      username: "john_doe"
      email: "john@example.com"
      password: "hashed_password"
```

### Structure complète

```yaml
import:
  # Imports d'autres scénarios (optionnel)
  - base_users
  - base_acl

vars:
  # Variables réutilisables (optionnel)
  # Déclarées sans $ mais utilisées avec $ dans les données
  admin: "admin_{{ scope }}"
  contract: "0xABCDEF1234567890"

load:
  # Instructions d'insertion
  - table: nom_table
    db: hexagonal_secondary  # Base de données (optionnel)
    data:
      colonne1: "{{ $admin }}"  # Utilisation avec $
      colonne2: 123
    types:
      colonne1: string
    pivot:
      id: 456
      column: custom_id

purge:
  # Instructions de purge personnalisées (optionnel)
  - table: nom_table
    db: hexagonal_secondary  # Base de données (optionnel)
    where:
      colonne: "{{ $admin }}"  # Utilisation avec $
```

**Sections** :
- `import` (optionnel) : Liste des scénarios à importer
- `vars` (optionnel) : Variables réutilisables dans tout le scénario
- `load` (obligatoire) : Liste des insertions à effectuer
  - `db` (optionnel) : Nom de la base de données cible (par défaut: base par défaut)
- `purge` (optionnel) : Purge personnalisé, sinon purge automatique via le tracker
  - `db` (optionnel) : Nom de la base de données cible (par défaut: base par défaut)

---

## Imports de scénarios

Le système d'imports permet de réutiliser des scénarios existants comme des "modules" pour composer des scénarios plus complexes.

### Syntaxe de base

```yaml
import:
  - base_users
  - base_acl
```

Les fichiers importés doivent exister dans le dossier `prisms/yaml/` :
- `prisms/yaml/base_users.yaml`
- `prisms/yaml/base_acl.yaml`

**⚠️ Important : Chemins absolus depuis `prisms/yaml/`**

Tous les imports sont **toujours relatifs au dossier `prisms/yaml/`**, peu importe où se trouve le fichier qui importe.

```yaml
# prisms/yaml/main.yaml
import:
  - test/admin_user              # → prisms/yaml/test/admin_user.yaml

# prisms/yaml/test/admin_user.yaml (dans un sous-dossier)
import:
  - test/users/acl_users_admin   # → prisms/yaml/test/users/acl_users_admin.yaml
  
# ❌ PAS ça :
import:
  - users/acl_users_admin        # → cherche prisms/yaml/users/ (pas prisms/yaml/test/users/)
```

**Règle** : Écrivez toujours le chemin complet depuis `prisms/yaml/`, même dans les sous-dossiers.

### Fonctionnement

**Concaténation des sections** :

1. **Load** : Les instructions des imports sont exécutées **avant** les instructions locales
2. **Purge** : L'ordre est **inversé** automatiquement (local → imports) pour respecter les FK
3. **Variables** : Les variables sont fusionnées (le fichier local override les imports)

**Exemple d'import** :

```yaml
# prisms/base_users.yaml
vars:
  admin_pwd: "admin123"
  
load:
  - table: users
    data:
      username: "admin_{{ scope }}"
      email: "admin_{{ scope }}@test.com"
      password: "{{ hash($admin_pwd) }}"

purge:
  - table: users
    where:
      username: "admin_{{ scope }}"
```

```yaml
# prisms/base_acl.yaml
load:
  - table: acl
    data:
      slug: "posts_{{ scope }}"
      description: "Gestion des articles"

purge:
  - table: acl
    where:
      slug: "posts_{{ scope }}"
```

```yaml
# prisms/my_test.yaml
import:
  - base_users
  - base_acl

vars:
  post_title: "Article de test"

load:
  - table: chat_messages
    data:
      id: "msg_{{ uuid }}"
      user_id:
        table: users
        where:
          username: "admin_{{ scope }}"
        return: id
      message: "{{ $post_title }}"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable

purge:
  - table: chat_messages
    where:
      message: "{{ $post_title }}"
```

**Ordre d'exécution du load** :
1. `base_users` → Crée l'utilisateur admin
2. `base_acl` → Crée les ACL
3. `my_test` (local) → Crée le message (lookup possible car user existe)

**Ordre d'exécution du purge** (inversé) :
1. `my_test` (local) → Supprime le message
2. `base_acl` → Supprime les ACL  
3. `base_users` → Supprime l'utilisateur

**Schéma visuel** :

```
[Ordre d'exécution des imports]
main.yaml
├─► base_users (load)
├─► base_acl (load)
└─► main (load)

[Purge inversé]
main (purge)
├─► base_acl (purge)
└─► base_users (purge)
```

### Fusion des variables

Les variables sont fusionnées avec priorité au fichier local :

```yaml
# base_users.yaml
vars:
  admin_pwd: "admin123"
  domain: "example.com"

# my_test.yaml
import:
  - base_users
  
vars:
  domain: "test.local"  # Override
  post_title: "Test"    # Nouvelle variable

# Résultat :
# admin_pwd: "admin123"  (de base_users)
# domain: "test.local"   (overridé)
# post_title: "Test"     (nouveau)
```

### Protection contre les imports circulaires

Le système détecte et empêche les imports circulaires :

```yaml
# a.yaml
import:
  - b  # ❌ Erreur : b importe a

# b.yaml
import:
  - a  # ❌ Circular import detected
```

### Cas d'usage

**✅ Bon usage** :
- Factoriser des données communes (users, ACL, settings)
- Créer des "presets" réutilisables
- Composer des scénarios complexes à partir de modules simples

```yaml
# Exemple : Scénario e-commerce
import:
  - base_users      # Admin + clients
  - base_products   # Catalogue
  - base_categories # Catégories

load:
  - table: orders
    data:
      user_id: 1
      total: 99.99
```

**❌ Mauvais usage** :
- Imports récursifs profonds (limite : 1-2 niveaux max)
- Dépendances circulaires entre scénarios
- Importer trop de fichiers (limite : 3-5 imports max)

### Limitations

- ❌ Pas d'imports conditionnels
- ❌ Pas de paramètres d'import
- ❌ Profondeur maximale recommandée : 2 niveaux

---

## Variables

Les variables permettent de définir des valeurs réutilisables dans tout le scénario, évitant la répétition et facilitant la maintenance.

### Déclaration

Les variables se déclarent **sans `$`** dans la section `vars` en début de fichier :

```yaml
vars:
  admin: "admin_{{ scope }}"
  manager: "manager_{{ scope }}"
  contract: "0xABCDEF1234567890ABCDEF1234567890ABCDEF12"
  email_domain: "example.test"
```

**Règles** :
- Le nom ne doit **pas** commencer par `$` dans la déclaration
- Les valeurs peuvent contenir des placeholders (`{{ scope }}`, `{{ hash() }}`, etc.)
- Les placeholders dans les variables sont résolus au chargement du scénario

### Utilisation

Les variables se référencent **avec `{{ $nom_variable }}`** dans les données :

```yaml
vars:
  admin: "admin_{{ scope }}"
  email_domain: "example.test"

load:
  - table: users
    data:
      username: "{{ $admin }}"
      email: "{{ $admin }}@{{ $email_domain }}"
      password: "{{ hash('admin123') }}"
  
  - table: posts
    data:
      title: "Article de {{ $admin }}"
      author_id:
        table: users
        where:
          username: "{{ $admin }}"
        return: id
```

**Important** : Le `$` permet de distinguer clairement les variables personnalisées des placeholders système :
- `{{ $admin }}` → Variable personnalisée
- `{{ scope }}` → Placeholder système
- `{{ now }}` → Placeholder système

### Exemples avancés

**Variables avec placeholders combinés** :
```yaml
vars:
  admin_username: "admin_{{ scope }}"
  admin_email: "admin_{{ scope }}@example.test"
  admin_password: "{{ hash('secure_password') }}"
  contract_address: "0xABCD_{{ scope }}"
  timestamp: "{{ now }}"

load:
  - table: users
    data:
      username: "{{ $admin_username }}"
      email: "{{ $admin_email }}"
      password: "{{ $admin_password }}"
      created_at: "{{ $timestamp }}"
```

**Variables pour les lookups** :
```yaml
vars:
  user1: "alice_{{ scope }}"
  user2: "bob_{{ scope }}"
  contract: "0x1234567890ABCDEF"

load:
  - table: chat_messages
    data:
      id: "msg_{{ $user1 }}_{{ uuid }}"
      contract_address: "{{ $contract }}"
      user_id:
        table: users
        where:
          username: "{{ $user1 }}"
        return: id
      message: "Hello from {{ $user1 }}"
```

### Avantages

| Avantage | Description |
|----------|-------------|
| **DRY** | Évite la répétition de valeurs communes |
| **Maintenance** | Changement centralisé des valeurs |
| **Lisibilité** | Code plus clair avec des noms explicites |
| **Flexibilité** | Combine variables et placeholders |

### Limitations

❌ Les variables globales ne peuvent **pas** :
- Référencer d'autres variables (`$var1: "{{ $var2 }}"` non supporté)
- Contenir de la logique conditionnelle
- Être modifiées dynamiquement pendant l'exécution

✅ Pour ces cas avancés, utilisez un **scénario hybride** (YAML + PHP).

---

## Variables temporaires (auto-générées)

En plus des variables globales, **chaque champ `data` crée automatiquement une variable temporaire** réutilisable dans les champs suivants du même bloc.

### Fonctionnement

```yaml
- table: users
  data:
    username: "alice_{{ scope }}"       # Crée {{ $username }}
    email: "{{ $username }}@test.com"   # Utilise {{ $username }}
    full_name: "User {{ $username }}"   # Réutilise {{ $username }}
```

**Chaque champ devient automatiquement une variable** :
- `username` → `{{ $username }}`
- `email` → `{{ $email }}`
- `full_name` → `{{ $full_name }}`

### Portée limitée au bloc

Les variables temporaires sont **réinitialisées à chaque nouveau bloc** `data` :

```yaml
vars:
  user1: "alice_{{ scope }}"
  user2: "bob_{{ scope }}"

load:
  # Bloc 1 : Alice
  - table: users
    data:
      username: "{{ $user1 }}"              # Crée {{ $username }}
      email: "{{ $username }}@test.com"     # {{ $username }} = alice_test
      bio: "I am {{ $username }}"           # Réutilise {{ $username }}
  
  # Bloc 2 : Bob (variables du bloc 1 n'existent plus)
  - table: users
    data:
      username: "{{ $user2 }}"              # Crée un NOUVEAU {{ $username }}
      email: "{{ $username }}@test.com"     # {{ $username }} = bob_test maintenant
      bio: "I am {{ $username }}"           # Nouveau {{ $username }}
```

### Priorité des variables

Quand une variable existe à la fois en global et en temporaire, **la temporaire est prioritaire** :

```yaml
vars:
  username: "global_user"  # Variable globale

load:
  - table: users
    data:
      username: "local_user"              # Crée {{ $username }} temporaire
      message: "Hello {{ $username }}"    # Utilise la temporaire = "local_user"
  
  - table: posts
    data:
      author: "{{ $username }}"            # Variable temporaire n'existe plus
                                           # Utilise la globale = "global_user"
```

**Ordre de recherche** :
1. Variables temporaires (champs du bloc courant)
2. Variables globales (`vars:`)
3. Erreur si non trouvée

### Exemples avancés

**Calculs avec variables temporaires** :
```yaml
vars:
  base_price: "100"

load:
  - table: products
    data:
      name: "Product {{ scope }}"
      price: "{{ $base_price }}"                    # Crée {{ $price }}
      quantity: "5"                                  # Crée {{ $quantity }}
      total: "{{ math($price*$quantity) }}"         # = 500
      discount: "{{ math($total*0.1) }}"            # = 50
      final_price: "{{ math($total-$discount) }}"   # = 450
```

**Réutilisation dans lookups** :
```yaml
- table: users
  data:
    username: "alice_{{ scope }}"     # Crée {{ $username }}
    email: "{{ $username }}@test.com"

- table: chat_messages
  data:
    id: "msg_{{ uuid }}"
    username: "alice_{{ scope }}"     # Crée {{ $username }} (nouveau bloc)
    user_id:
      table: users
      where:
        username: "{{ $username }}"    # Utilise la variable temporaire
      return: id
    message: "Hello from {{ $username }}"
```

### Avantages

| Avantage | Description |
|----------|-------------|
| **Pas de répétition** | Pas besoin de réécrire les mêmes valeurs |
| **Auto-documentation** | Les champs deviennent des variables nommées |
| **Scope isolé** | Chaque bloc est indépendant |
| **Combinaison** | Fonctionne avec `{{ math() }}` et autres placeholders |

### Limitations

❌ Les variables temporaires ne peuvent **pas** :
- Être utilisées dans un bloc différent
- Être redéfinies manuellement
- Persister après la fin du bloc

✅ Pour des variables partagées entre blocs : utilisez les **variables globales** (`vars:`).

---

## Placeholders

Les placeholders permettent d'injecter des valeurs dynamiques dans vos données et vos variables.

### `{{ scope }}`

Injecte la valeur du scope passé en paramètre de commande.

**Exemple** :
```yaml
- table: users
  data:
    username: "admin_{{ scope }}"
    email: "admin_{{ scope }}@example.test"
```

**Commande** :
```bash
php bin/console app:prism:load my_prism --scope=prod2024
# Créera : admin_prod2024 / admin_prod2024@example.test
```

### `{{ hash('password') }}`

Génère un hash bcrypt d'un mot de passe.

**Exemple** :
```yaml
- table: users
  data:
    password: "{{ hash('my_secret_password') }}"
```

### `{{ env('VAR') }}`

Récupère une variable d'environnement. Cherche dans `$_ENV`, `$_SERVER` puis `getenv()`.

**Exemple** :
```yaml
vars:
  api_key: "{{ env('API_KEY') }}"
  db_host: "{{ env('DATABASE_HOST') }}"
  app_env: "{{ env('APP_ENV') }}"

load:
  - table: config
    data:
      key: "api_integration"
      value: "{{ $api_key }}"
      environment: "{{ $app_env }}"
```

**Notes** :
- Lève une erreur si la variable n'existe pas
- Utile pour les configurations d'environnement
- Peut être utilisé dans les variables globales (`vars:`)

### `{{ now }}`

Insère un timestamp actuel au format `Y-m-d H:i:s`.

**Exemple** :
```yaml
- table: posts
  data:
    title: "Mon article"
    created_at: "{{ now }}"
  types:
    created_at: datetime_immutable
```

### `{{ date('modifier') }}`

Génère une date avec un modificateur relatif. Plus flexible que `{{ now }}` pour des dates futures ou passées.

**Exemples** :
```yaml
- table: subscriptions
  data:
    user_id: 123
    starts_at: "{{ now }}"
    expires_at: "{{ date('+7 days') }}"      # Dans 7 jours
    trial_ends: "{{ date('+14 days') }}"     # Dans 2 semaines
  types:
    starts_at: datetime_immutable
    expires_at: datetime_immutable
    trial_ends: datetime_immutable
```

**Modificateurs supportés** :

| Modificateur | Description | Exemple |
|--------------|-------------|---------|
| `+1 day` | Demain | `{{ date('+1 day') }}` |
| `-1 day` | Hier | `{{ date('-1 day') }}` |
| `+7 days` | Dans 7 jours | `{{ date('+7 days') }}` |
| `+1 week` | Dans 1 semaine | `{{ date('+1 week') }}` |
| `-1 week` | Il y a 1 semaine | `{{ date('-1 week') }}` |
| `+1 month` | Dans 1 mois | `{{ date('+1 month') }}` |
| `+1 year` | Dans 1 an | `{{ date('+1 year') }}` |
| `next monday` | Prochain lundi | `{{ date('next monday') }}` |
| `last friday` | Dernier vendredi | `{{ date('last friday') }}` |

**Notes** :
- Utilise `strtotime()` de PHP (très flexible)
- Format de sortie : `Y-m-d H:i:s`
- Combine avec `types:` pour convertir en `DateTimeImmutable`

### `{{ uuid }}`

Génère un UUID v4 unique.

**Exemple** :
```yaml
- table: chat_messages
  data:
    id: "msg_{{ scope }}_{{ uuid }}"
    message: "Hello World"
```

**Résultat** : `msg_test_a3f2b8c1-4d5e-4f6a-8b9c-1d2e3f4a5b6c`

### `{{ math(expression) }}`

Évalue une expression mathématique. Supporte les opérateurs `+`, `-`, `*`, `/`, `%` et les parenthèses.

**Exemples simples** :
```yaml
- table: products
  data:
    name: "Product {{ scope }}"
    price: "{{ math(100+50) }}"        # 150
    discount: "{{ math(10*2) }}"       # 20
    total: "{{ math((100+50)-20) }}"   # 130
```

**Avec variables** :
```yaml
vars:
  base_price: "100"
  quantity: "5"
  tax_rate: "20"

load:
  - table: orders
    data:
      subtotal: "{{ math($base_price*$quantity) }}"           # 500
      tax: "{{ math($base_price*$quantity*$tax_rate/100) }}"  # 100
      total: "{{ math($base_price*$quantity+100) }}"          # 600
```

**Opérateurs supportés** :

| Opérateur | Description | Exemple | Résultat |
|-----------|-------------|---------|----------|
| `+` | Addition | `{{ math(10+5) }}` | 15 |
| `-` | Soustraction | `{{ math(10-5) }}` | 5 |
| `*` | Multiplication | `{{ math(10*5) }}` | 50 |
| `/` | Division | `{{ math(10/5) }}` | 2 |
| `%` | Modulo | `{{ math(10%3) }}` | 1 |
| `()` | Parenthèses | `{{ math((10+5)*2) }}` | 30 |

**Notes importantes** :
- Les expressions sont évaluées de manière sécurisée
- Seuls les nombres et opérateurs mathématiques sont autorisés
- Les variables doivent être numériques
- Les divisions par zéro lèvent une erreur

### `{{ fake(type, param1, param2, ...) }}`

Génère des **données aléatoires** pour les tests.

```yaml
data:
  username: "{{ fake(user) }}"
  email: "{{ fake(email, 'company.com') }}"
  iban: "{{ fake(iban, 'DE') }}"
```

**📚 44 types disponibles** : Voir [Guide Faker complet](PRISM_FAKER.md)

---

## Pipes (Transformateurs)

Les pipes permettent de **transformer les valeurs des placeholders** en chaînant des fonctions de transformation. Ils s'utilisent avec l'opérateur `|` (pipe).

### Syntaxe

```yaml
{{ expression|pipe1|pipe2(arg1, arg2)|pipe3 }}
```

**Principe** :
1. L'expression est évaluée (scope, uuid, hash, etc.)
2. Le résultat passe par chaque pipe dans l'ordre
3. Chaque pipe transforme la valeur et la passe au suivant

### Pipes disponibles

#### `truncate(max)`

Limite la longueur d'une chaîne à `max` caractères.

**Exemples** :
```yaml
- table: chat_messages
  data:
    id: "msg_{{ uuid|truncate(8) }}"              # msg_a3f2b8c1
    short_hash: "{{ hash(scope)|truncate(24) }}"   # $2y$10$0DUDts2PaCjoUZUwa
```

**Arguments** :
- `max` (int) : Nombre maximum de caractères

#### `trim`

Supprime les espaces en début et fin de chaîne.

**Exemples** :
```yaml
vars:
  user_input: "  Hello World  "

load:
  - table: users
    data:
      username: "{{ $user_input|trim }}"     # "Hello World"
```

#### `uppercase` / `upper`

Convertit une chaîne en majuscules.

**Exemples** :
```yaml
- table: config
  data:
    key: "environment"
    value: "{{ scope|uppercase }}"            # PROD_2024
    code: "{{ scope|upper|replace('_', '-') }}"  # PROD-2024
```

**Alias** : `uppercase` et `upper` sont équivalents.

#### `lowercase` / `lower`

Convertit une chaîne en minuscules.

**Exemples** :
```yaml
- table: users
  data:
    username: "{{ scope|lowercase }}"         # prod_2024
    email: "{{ scope|lower }}@example.com"    # prod_2024@example.com
```

**Alias** : `lowercase` et `lower` sont équivalents.

#### `capitalize` / `ucfirst`

Met la première lettre en majuscule.

**Exemples** :
```yaml
vars:
  text: "  hello world  "

load:
  - table: posts
    data:
      title: "{{ $text|trim|lowercase|capitalize }}"  # "Hello world"
```

**Alias** : `capitalize` et `ucfirst` sont équivalents.

#### `replace(search, replace)`

Remplace toutes les occurrences d'une chaîne par une autre.

**Exemples** :
```yaml
- table: identifiers
  data:
    # UUID sans tirets
    clean_uuid: "{{ uuid|replace('-', '') }}"
    # 9e5be96c19344e82bec946f36b9aa94c
    
    # UUID avec underscores
    snake_uuid: "{{ uuid|replace('-', '_') }}"
    # 9e5be96c_1934_4e82_bec9_46f36b9aa94c
    
    # Scope formatté
    code: "{{ scope|uppercase|replace('_', '-') }}"
    # PROD-2024
```

**Arguments** :
- `search` (string) : Chaîne à rechercher
- `replace` (string) : Chaîne de remplacement

**Notes** :
- Les arguments doivent être entre guillemets simples ou doubles
- Échappement : utilisez `\'` ou `\"` si nécessaire

#### `base64`

Encode une chaîne en Base64.

**Exemples** :
```yaml
- table: api_tokens
  data:
    token: "{{ uuid|base64 }}"                    # OWU1YmU5NmMtMTkzNC00ZTgyLWJlYzktNDZmMzZiOWFhOTRj
    encoded_data: "{{ scope|base64 }}"             # cHJvZF8yMDI0
```

#### `md5`

Génère un hash MD5 de la chaîne.

**Exemples** :
```yaml
- table: cache
  data:
    cache_key: "{{ scope|md5 }}"                  # 5f4dcc3b5aa765d61d8327deb882cf99
    file_hash: "{{ uuid|md5 }}"                   # a3f2b8c1d4e5f6a7b8c9d0e1f2a3b4c5
```

**Note** : MD5 n'est pas recommandé pour la sécurité, utilisez pour le cache/checksum uniquement.

#### `sha1`

Génère un hash SHA-1 de la chaîne.

**Exemples** :
```yaml
- table: files
  data:
    checksum: "{{ uuid|sha1 }}"                   # 356a192b7913b04c54574d18c28d46e6395428ab
    signature: "{{ scope|sha1|truncate(16) }}"    # 356a192b7913b04c
```

**Note** : SHA-1 n'est pas recommandé pour la sécurité, utilisez pour le cache/checksum uniquement.

#### `htmlencode`

Encode les caractères spéciaux HTML (protection XSS).

**Exemples** :
```yaml
vars:
  user_input: "<script>alert('XSS')</script>"

load:
  - table: posts
    data:
      content: "{{ $user_input|htmlencode }}"     # &lt;script&gt;alert(&#039;XSS&#039;)&lt;/script&gt;
      title: "Article de l'utilisateur|htmlencode" # Article de l&#039;utilisateur
```

**Utilité** : Protège contre les injections XSS lors du stockage de données utilisateur.

#### `urlencode`

Encode une chaîne pour utilisation dans une URL.

**Exemples** :
```yaml
- table: api_logs
  data:
    url: "https://api.example.com?query={{ scope|urlencode }}"  # ?query=prod%5F2024
    slug: "{{ scope|replace('_', ' ')|urlencode }}"              # prod+2024
```

### Combinaison de pipes

Les pipes peuvent être chaînés pour des transformations complexes :

**Exemples** :
```yaml
vars:
  original_text: "  Hello World  "

load:
  - table: messages
    data:
      # Nettoyer + formater
      clean_text: "{{ $original_text|trim|lowercase|capitalize }}"
      # Résultat: "Hello world"
      
      # UUID court et propre
      short_id: "{{ uuid|truncate(24)|replace('-', '') }}"
      # Résultat: "95437ca45edb4490a78a" (24 chars, sans tirets)
      
      # Hash court
      token: "{{ hash(scope)|truncate(16)|uppercase }}"
      # Résultat: "$2Y$10$0DUDTS2PA" (16 chars en majuscules)
      
      # Scope formatté
      environment: "{{ scope|uppercase|replace('_', '-')|trim }}"
      # Résultat: "PROD-2024"
```

### Pipes avec tous les placeholders

Les pipes fonctionnent avec **tous les types de placeholders** :

```yaml
vars:
  user_name: "  Alice  "
  contract: "0x{{ uuid|truncate(24)|replace('-', '') }}"

load:
  - table: demo
    data:
      # Avec scope
      scope_upper: "{{ scope|uppercase }}"
      scope_formatted: "{{ scope|uppercase|replace('_', '-') }}"
      
      # Avec variables
      clean_name: "{{ $user_name|trim|capitalize }}"
      contract_short: "{{ $contract|truncate(32) }}"
      
      # Avec hash
      password_short: "{{ hash('password')|truncate(24) }}"
      password_upper: "{{ hash(scope)|uppercase }}"
      
      # Avec UUID
      uuid_short: "{{ uuid|truncate(8) }}"
      uuid_clean: "{{ uuid|replace('-', '') }}"
      uuid_compact: "{{ uuid|truncate(24)|replace('-', '') }}"
      
      # Avec date
      date_upper: "{{ now|uppercase }}"
      
      # Avec math
      calc_short: "{{ math(100*2)|truncate(3) }}"
      
      # Avec env
      env_lower: "{{ env('APP_ENV')|lowercase }}"
```

### Cas d'usage

**Identifiants compacts** :
```yaml
- table: chat_messages
  data:
    id: "msg_{{ uuid|truncate(16)|replace('-', '') }}"  # msg_a3f2b8c195437ca4
```

**Adresses Ethereum-like** :
```yaml
vars:
  contract: "0x{{ uuid|truncate(32)|replace('-', '')|uppercase }}"
  # 0xA3F2B8C195437CA45EDB4490A78A1D2E
```

**Codes produits** :
```yaml
- table: products
  data:
    sku: "PROD-{{ scope|uppercase }}-{{ uuid|truncate(8)|uppercase }}"
    # PROD-TEST-A3F2B8C1
```

**Nettoyage de données utilisateur** :
```yaml
vars:
  user_input: "  John Doe  "

load:
  - table: users
    data:
      username: "{{ $user_input|trim|lowercase|replace(' ', '_') }}"
      # john_doe
      
      display_name: "{{ $user_input|trim|capitalize }}"
      # "John doe"
```

**Tokens courts** :
```yaml
- table: api_keys
  data:
    key: "{{ hash(scope)|truncate(32)|uppercase|replace('$', '')|replace('/', '') }}"
    # 2Y10DUDTS2PACJOUZUWA1234ABCD5678
```

### Limitations

❌ Les pipes ne peuvent **pas** :
- Créer de nouveaux pipes personnalisés en YAML (utiliser PHP hybride)
- Utiliser des fonctions PHP arbitraires
- Modifier des variables globales

✅ **Pipes disponibles** :
- `truncate(max)`
- `trim`
- `uppercase` / `upper`
- `lowercase` / `lower`
- `capitalize` / `ucfirst`
- `replace(search, replace)`
- `base64`
- `md5`
- `sha1`
- `htmlencode`
- `urlencode`

**Astuce** : Pour des transformations plus complexes, utilisez un **scénario hybride** (YAML + PHP).

---

## Lookup dynamique

Les lookups permettent de récupérer des IDs depuis la base de données pour gérer les foreign keys.

### Syntaxe

```yaml
column_name:
  table: nom_table
  where:
    colonne_recherche: "valeur"
  return: colonne_retour
```

### Exemple simple

```yaml
- table: posts
  data:
    title: "Mon article"
    author_id:
      table: users
      where:
        username: "john_doe"
      return: id
```

**Exécution** :
1. Recherche dans `users` où `username = 'john_doe'`
2. Récupère la colonne `id`
3. Insère dans `posts.author_id`

### Lookup avec placeholder

```yaml
- table: users_acl
  data:
    user_id:
      table: users
      where:
        username: "admin_{{ scope }}"
      return: id
    acl_id:
      table: acl
      where:
        slug: "root"
      return: id
```

**Avec `--scope=test`** :
- Recherche `users.username = 'admin_test'`
- Recherche `acl.slug = 'root'`
- Crée la liaison dans `users_acl`

### Lookup pour relations hiérarchiques

```yaml
# ACL Root (pas de parent)
- table: acl
  data:
    slug: "root_{{ scope }}"
    description: "Accès root"

# ACL Admin (parent: root)
- table: acl
  data:
    slug: "admin_{{ scope }}"
    description: "Administrateur"
    parent_id:
      table: acl
      where:
        slug: "root_{{ scope }}"
      return: id

# ACL Manager (parent: admin)
- table: acl
  data:
    slug: "manager_{{ scope }}"
    description: "Manager"
    parent_id:
      table: acl
      where:
        slug: "admin_{{ scope }}"
      return: id
```

**Résultat** : Hiérarchie `root → admin → manager` avec IDs résolus dynamiquement

---

## Types de données

La section `types` permet de convertir les valeurs en objets PHP spécifiques avec validation et gestion d'erreurs automatiques.

### Types disponibles

#### Types Date et Heure

| Type | Description | Format accepté | Exemple |
|------|-------------|----------------|---------|
| `datetime_immutable` | `DateTimeImmutable` | String, timestamp (int), DateTime | `created_at: datetime_immutable` |
| `datetime` | `DateTime` | String, timestamp (int), DateTimeImmutable | `updated_at: datetime` |
| `datetimetz` | `DateTime` avec timezone | String, timestamp (int) | `sent_at: datetimetz` |
| `datetimetz_immutable` | `DateTimeImmutable` avec timezone | String, timestamp (int) | `received_at: datetimetz_immutable` |
| `date` | `DateTime` (date seule) | String, timestamp (int) | `birth_date: date` |
| `date_immutable` | `DateTimeImmutable` (date seule) | String, timestamp (int) | `start_date: date_immutable` |
| `time` | `DateTime` (heure seule) | String | `opening_time: time` |
| `time_immutable` | `DateTimeImmutable` (heure seule) | String | `closing_time: time_immutable` |

**Conversions automatiques** :
- **String** : Parse avec constructeur (`new DateTime($value)`)
- **Timestamp (int)** : Convertit avec `setTimestamp()`
- **DateTime ↔ DateTimeImmutable** : Conversion automatique si nécessaire
- **Valeur invalide** : Retourne `null` (pas d'exception)

```yaml
- table: events
  data:
    name: "Conférence {{ scope }}"
    starts_at: "2024-06-15 14:00:00"      # String → DateTimeImmutable
    ends_at: 1718460000                    # Timestamp → DateTimeImmutable
    created_at: "{{ now }}"                # Placeholder → DateTimeImmutable
  types:
    starts_at: datetime_immutable
    ends_at: datetime_immutable
    created_at: datetime_immutable
```

#### Types Numériques

| Type | Description | Plage | Conversion |
|------|-------------|-------|------------|
| `int` / `integer` | Entier standard | -2,147,483,648 à 2,147,483,647 | Clamping automatique |
| `smallint` | Petit entier | -32,768 à 32,767 | Clamping automatique |
| `bigint` | Grand entier | PHP_INT_MIN à PHP_INT_MAX | Clamping automatique |
| `float` | Nombre flottant | ±1.7e308 | Cast direct |
| `decimal` | Décimal précis | Aucune limite | Gardé comme string |

**Validation automatique** :
- **Integers** : Valeurs hors limites automatiquement clampées
- **Non-numérique** : Retourne `0` pour int, `0.0` pour float, `'0'` pour decimal

```yaml
- table: products
  data:
    name: "Produit {{ scope }}"
    quantity: "50000"            # > 32767 → clampé à 32767
    stock_level: "100"
    price: "99.99"
    precision_price: "99.999999"
  types:
    quantity: smallint           # Clampé à 32767
    stock_level: int
    price: float                 # 99.99
    precision_price: decimal     # "99.999999" (préserve précision)
```

**Exemples de clamping** :
```yaml
# smallint (-32768 à 32767)
quantity: "50000"  → 32767
quantity: "-50000" → -32768

# int (-2147483648 à 2147483647)  
user_id: "3000000000"  → 2147483647
user_id: "-3000000000" → -2147483648

# bigint (PHP_INT_MIN à PHP_INT_MAX)
big_number: "9999999999999999999" → PHP_INT_MAX
```

#### Types Booléens

| Type | Description | Valeurs acceptées | Exemple |
|------|-------------|-------------------|---------|
| `bool` / `boolean` | Booléen | `true`, `false`, `1`, `0`, `"1"`, `"0"` | `is_active: bool` |

```yaml
- table: settings
  data:
    feature_enabled: "1"
    debug_mode: "0"
    is_public: true
  types:
    feature_enabled: bool    # true
    debug_mode: bool         # false
    is_public: bool          # true
```

#### Types Chaînes

| Type | Description | Exemple |
|------|-------------|---------|
| `string` | Chaîne de caractères | `name: string` |
| `text` | Texte long | `description: text` |
| `guid` / `uuid` | Identifiant unique | `id: uuid` |

```yaml
- table: articles
  data:
    id: "{{ uuid }}"
    title: "Article {{ scope }}"
    content: "Lorem ipsum dolor sit amet..."
  types:
    id: uuid
    title: string
    content: text
```

#### Types Complexes

| Type | Description | Format accepté | Exemple |
|------|-------------|----------------|---------|
| `json` | Tableau PHP (décodé) | String JSON ou Array | `metadata: json` |
| `array` | Tableau sérialisé | String sérialisé ou Array | `options: array` |
| `simple_array` | Tableau simple (CSV) | String `"a,b,c"` ou Array | `tags: simple_array` |

**Validation JSON** :
- **String JSON valide** : Décodé en array
- **Array PHP** : Retourné tel quel
- **JSON invalide** : Retourne `[]` (pas d'exception)

```yaml
- table: documents
  data:
    id: "{{ uuid }}"
    metadata: '{"author": "John", "version": 2}'
    tags: "php,symfony,prism"
    settings: 'a:2:{s:4:"mode";s:4:"prod";s:5:"debug";b:0;}'
  types:
    metadata: json          # {"author": "John", "version": 2}
    tags: simple_array      # ["php", "symfony", "prism"]
    settings: array         # Unserialized
```

#### Types Binaires

| Type | Description | Exemple |
|------|-------------|---------|
| `binary` | Données binaires | `file_data: binary` |
| `blob` | BLOB SQL | `image_data: blob` |

```yaml
- table: files
  data:
    name: "document.pdf"
    data: "{{ file_content }}"
  types:
    data: binary
```

### Exemple complet

```yaml
- table: orders
  data:
    reference: "ORD-{{ scope }}-001"
    total_amount: "99.99"
    tax_amount: "19.99"
    quantity: "5"
    is_paid: "1"
    ordered_at: "{{ now }}"
    shipped_at: 1718460000
    metadata: '{"carrier": "DHL", "tracking": "123456"}'
    tags: "express,priority,fragile"
  types:
    total_amount: float           # 99.99
    tax_amount: decimal           # "19.99" (précision préservée)
    quantity: smallint            # 5
    is_paid: bool                 # true
    ordered_at: datetime_immutable
    shipped_at: datetime_immutable
    metadata: json                # Array PHP
    tags: simple_array            # ["express", "priority", "fragile"]
```

### Exemple complet

```yaml
- table: orders
  data:
    reference: "ORD-{{ scope }}-001"
    total_amount: "99.99"
    quantity: "5"
    is_paid: "1"
    ordered_at: "{{ now }}"
  types:
    total_amount: float
    quantity: int
    is_paid: bool
    ordered_at: datetime_immutable
```

### Timestamps automatiques

Si vos colonnes ont `DEFAULT CURRENT_TIMESTAMP`, **inutile de les spécifier** :

```yaml
# ✅ Bon - La DB gère les timestamps
- table: users
  data:
    username: "john"
    email: "john@example.com"
  # Pas besoin de created_at/updated_at si DEFAULT CURRENT_TIMESTAMP

# ❌ Mauvais - Redondant
- table: users
  data:
    username: "john"
    email: "john@example.com"
    created_at: "{{ now }}"  # Inutile si DEFAULT existe
```

---

## Pivot custom

Par défaut, le tracking utilise la colonne `id` de la table. Le pivot custom permet de tracker par une autre colonne.

### Cas d'usage

**Table avec ID VARCHAR** : Les messages de chat ont un `id` VARCHAR mais doivent être trackés par `user_id` (INT).

### Syntaxe

```yaml
- table: nom_table
  data:
    # ... données ...
  pivot:
    id: valeur_ou_lookup
    column: nom_colonne
```

### Exemple avec valeur directe

```yaml
- table: chat_messages
  data:
    id: "msg_custom_uuid"
    user_id: 42
    message: "Hello"
  pivot:
    id: 42
    column: user_id
```

**Tracking** : Le système trackera `chat_messages` avec `user_id = 42` au lieu de `id = 'msg_custom_uuid'`

### Exemple avec lookup

```yaml
- table: chat_messages
  data:
    id: "msg_{{ scope }}_{{ uuid }}"
    contract_address: "0xABCDEF1234567890ABCDEF1234567890ABCDEF12"
    user_id:
      table: users
      where:
        username: "manager_{{ scope }}"
      return: id
    username: "manager_{{ scope }}"
    message: "Prochaine réunion demain à 14h."
    created_at: "{{ now }}"
  types:
    created_at: datetime_immutable
  pivot:
    id:
      table: users
      where:
        username: "manager_{{ scope }}"
      return: id
    column: user_id
```

**Fonctionnement** :
1. Insère le message avec son UUID comme `id`
2. Résout le `user_id` via lookup
3. Track avec `user_id` au lieu de l'UUID
4. Au purge, supprimera tous les messages de cet utilisateur

### Pourquoi utiliser pivot ?

| Sans pivot | Avec pivot |
|------------|-----------|
| Track par `id` VARCHAR | Track par `user_id` INT |
| Purge message par message | Purge tous les messages d'un utilisateur |
| Tracking complexe avec UUIDs | Tracking simplifié par relation FK |

---

## Purge personnalisé

Par défaut, le purge utilise le **tracker automatique**. Vous pouvez ajouter un purge custom en **surcouche**.

### 🔍 Comprendre les données volatiles et orphelines

**Contexte important** : Lors de la création d'un jeu de données pendant un test utilisateur, vous pouvez créer de la donnée qui ne sera **pas trackée** par Prism.

#### Terminologie

- **Données volatiles** : Données créées pendant les tests utilisateurs qui ne sont **PAS trackées** par Prism
  - Exemple : Un utilisateur créé par le scénario qui ajoute manuellement des posts via l'UI
  - Prism n'a **aucun contrôle direct** sur ces données

- **Données orphelines** : Données volatiles qui **restent après la purge** d'un scénario
  - Si vous purgez uniquement via le tracker automatique, les données volatiles ne sont pas supprimées
  - Ces données "orphelines" polluent la base et peuvent causer des bugs dans les tests suivants

#### Rôle de la purge custom

⚠️ **La purge custom est là pour s'assurer que les données volatiles ne deviennent pas orphelines**

Sans purge custom, seules les données trackées par Prism sont supprimées. Les données créées manuellement (via l'UI, des scripts, ou d'autres processus) restent en base.

---

### Purge automatique (par défaut)

```yaml
load:
  - table: users
    data:
      username: "john_{{ scope }}"
```

**Purge** : Supprime automatiquement tous les enregistrements trackés du scope.

### Purge personnalisé (surcouche)

Le purge custom s'exécute **avant** le purge automatique. Utile pour nettoyer des données créées hors tracking.

```yaml
load:
  - table: users
    data:
      username: "john_{{ scope }}"

purge:
  # Purge custom en premier
  - table: posts
    where:
      author: "john_{{ scope }}"
  
  # Puis purge automatique à la fin (implicite)
```

**Ordre d'exécution** :
1. Purge custom : `DELETE FROM posts WHERE author = 'john_test'`
2. Purge automatique (pivot) : Supprime tous les enregistrements trackés

### Cas d'usage : Données créées manuellement

**Scénario** : Vous créez un utilisateur via le scénario, puis **manuellement** (via l'UI) l'utilisateur crée des posts qui ne sont **pas trackés**.

```yaml
load:
  - table: users
    data:
      username: "author_{{ scope }}"
      email: "author@{{ scope }}.com"
      password: "{{ hash('secret') }}"

purge:
  # Purge les posts créés manuellement (non trackés)
  - table: posts
    where:
      author_username: "author_{{ scope }}"
  
  # Purge les commentaires associés
  - table: comments
    where:
      author_username: "author_{{ scope }}"
  
  # Le purge automatique supprimera l'utilisateur à la fin
```

### Contrôle fin avec `purge_pivot`

Vous pouvez déclencher le purge automatique **à un moment précis** dans la séquence avec `purge_pivot: true`.

**Syntaxe** :
```yaml
purge:
  - table: table1
    where:
      col: "val"
  
  - purge_pivot: true  # Déclenche le purge automatique ICI
  
  - table: table2
    where:
      col: "val"
```

**Exemple complet** :
```yaml
load:
  - table: users
    data:
      username: "user_{{ scope }}"

  - table: projects
    data:
      name: "project_{{ scope }}"
      owner_id:
        table: users
        where:
          username: "user_{{ scope }}"
        return: id

purge:
  # 1. Purge les logs créés manuellement
  - table: activity_logs
    where:
      username: "user_{{ scope }}"
  
  # 2. Purge les fichiers uploadés manuellement
  - table: uploads
    where:
      username: "user_{{ scope }}"
  
  # 3. Déclenche le purge automatique (supprime users et projects)
  - purge_pivot: true
  
  # 4. Purge les caches liés
  - table: cache_entries
    where:
      key_pattern: "user_{{ scope }}_%"
```

**Ordre d'exécution** :
1. `DELETE FROM activity_logs WHERE username = 'user_test'`
2. `DELETE FROM uploads WHERE username = 'user_test'`
3. **Purge automatique** : Supprime `projects` et `users` via tracker
4. `DELETE FROM cache_entries WHERE key_pattern = 'user_test_%'`

### Pourquoi contrôler l'ordre ?

**Note importante** : Le purge automatique (pivot) **inverse automatiquement l'ordre d'insertion** pour respecter les contraintes FK. Si vous insérez `users` puis `posts`, le purge supprimera `posts` puis `users`.

| Cas | Besoin | Solution |
|-----|--------|----------|
| **FK sans CASCADE** | Supprimer enfants avant parents | Le purge pivot le fait automatiquement |
| **Contraintes ON DELETE CASCADE** | La DB supprime les enfants | Le purge pivot essaie quand même (catch les erreurs) |
| **Données hors tracking** | Supprimer avant les données trackées | Purge custom **avant** pivot (défaut) |
| **Caches/Logs sans FK** | Nettoyer après suppression principale | Purge custom **après** pivot (avec `purge_pivot: true`) |

**Ordre naturel du purge** :
```yaml
purge:
  - table: manual_data_1     # 1. Purge custom
  - table: manual_data_2     # 2. Purge custom
  # 3. Purge pivot automatique (ordre inversé : dernier inséré → premier supprimé)
```

**Ordre contrôlé avec `purge_pivot`** :
```yaml
purge:
  - table: logs              # 1. Purge custom avant
  - purge_pivot: true        # 2. Purge pivot (ordre inversé)
  - table: cache             # 3. Purge custom après
```

✅ **Bonne pratique** : Dans 99% des cas, l'ordre par défaut (purge custom → purge pivot à la fin) suffit car le pivot inverse déjà l'ordre d'insertion.

---

## Exemples complets

### Exemple 1 : Utilisateurs simples

```yaml
# prisms/simple_users.yaml
load:
  - table: users
    data:
      username: "admin_{{ scope }}"
      email: "admin_{{ scope }}@example.test"
      password: "{{ hash('admin123') }}"

  - table: users
    data:
      username: "user_{{ scope }}"
      email: "user_{{ scope }}@example.test"
      password: "{{ hash('user123') }}"
```

**Commande** :
```bash
php bin/console app:prism:load simple_users --scope=qa2024
```

### Exemple 2 : Relations avec lookup

```yaml
# prisms/blog_posts.yaml
load:
  # Créer un auteur
  - table: users
    data:
      username: "author_{{ scope }}"
      email: "author_{{ scope }}@blog.com"
      password: "{{ hash('secret') }}"

  # Créer ses articles
  - table: posts
    data:
      title: "Article 1 - {{ scope }}"
      content: "Contenu de l'article"
      author_id:
        table: users
        where:
          username: "author_{{ scope }}"
        return: id
      published_at: "{{ now }}"
    types:
      published_at: datetime_immutable

  - table: posts
    data:
      title: "Article 2 - {{ scope }}"
      content: "Autre contenu"
      author_id:
        table: users
        where:
          username: "author_{{ scope }}"
        return: id
      published_at: "{{ now }}"
    types:
      published_at: datetime_immutable
```

### Exemple 3 : Hiérarchie ACL complète

```yaml
# prisms/acl_hierarchy.yaml
load:
  # Utilisateurs
  - table: users
    data:
      username: "admin_{{ scope }}"
      email: "admin@{{ scope }}.com"
      password: "{{ hash('admin') }}"

  - table: users
    data:
      username: "manager_{{ scope }}"
      email: "manager@{{ scope }}.com"
      password: "{{ hash('manager') }}"

  # Hiérarchie ACL
  - table: acl
    data:
      slug: "root_{{ scope }}"
      description: "Super admin"

  - table: acl
    data:
      slug: "admin_{{ scope }}"
      description: "Admin"
      parent_id:
        table: acl
        where:
          slug: "root_{{ scope }}"
        return: id

  - table: acl
    data:
      slug: "manager_{{ scope }}"
      description: "Manager"
      parent_id:
        table: acl
        where:
          slug: "admin_{{ scope }}"
        return: id

  # Assignations
  - table: users_acl
    data:
      user_id:
        table: users
        where:
          username: "admin_{{ scope }}"
        return: id
      acl_id:
        table: acl
        where:
          slug: "admin_{{ scope }}"
        return: id

  - table: users_acl
    data:
      user_id:
        table: users
        where:
          username: "manager_{{ scope }}"
        return: id
      acl_id:
        table: acl
        where:
          slug: "manager_{{ scope }}"
        return: id
```

### Exemple 4 : Utilisation de bases de données multiples

```yaml
# prisms/multi_database.yaml
load:
  # Utilisateurs dans la base principale
  - table: users
    data:
      username: "admin_{{ scope }}"
      email: "admin@{{ scope }}.com"
      password: "{{ hash('admin123') }}"

  # Logs dans une base secondaire
  - table: audit_logs
    db: hexagonal_secondary
    data:
      user_id:
        table: users
        where:
          username: "admin_{{ scope }}"
        return: id
      action: "user_created"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable

purge:
  # Purge les logs de la base secondaire
  - table: audit_logs
    db: hexagonal_secondary
    where:
      action: "user_created"
```

### Exemple 5 : Messages avec pivot custom

```yaml
# prisms/chat_with_pivot.yaml
load:
  # Utilisateurs
  - table: users
    data:
      username: "alice_{{ scope }}"
      email: "alice@{{ scope }}.com"
      password: "{{ hash('alice123') }}"

  - table: users
    data:
      username: "bob_{{ scope }}"
      email: "bob@{{ scope }}.com"
      password: "{{ hash('bob123') }}"

  # Messages (trackés par user_id)
  - table: chat_messages
    data:
      id: "msg_alice_{{ scope }}_{{ uuid }}"
      contract_address: "0x1234567890ABCDEF1234567890ABCDEF12345678"
      user_id:
        table: users
        where:
          username: "alice_{{ scope }}"
        return: id
      username: "alice_{{ scope }}"
      message: "Salut Bob !"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable
    pivot:
      id:
        table: users
        where:
          username: "alice_{{ scope }}"
        return: id
      column: user_id

  - table: chat_messages
    data:
      id: "msg_bob_{{ scope }}_{{ uuid }}"
      contract_address: "0x1234567890ABCDEF1234567890ABCDEF12345678"
      user_id:
        table: users
        where:
          username: "bob_{{ scope }}"
        return: id
      username: "bob_{{ scope }}"
      message: "Salut Alice !"
      created_at: "{{ now }}"
    types:
      created_at: datetime_immutable
    pivot:
      id:
        table: users
        where:
          username: "bob_{{ scope }}"
        return: id
      column: user_id
```

---

## Limitations

### ❌ Ce que YAML ne peut PAS faire

| Limitation | Raison | Solution |
|------------|--------|----------|
| **Logique conditionnelle** | Pas de `if/else` | Scénario hybride (YAML + PHP) |
| **Boucles** | Pas de `for/foreach` | Scénario hybride ou PHP pur |
| **Calculs complexes** | Pas d'expressions | Scénario hybride |
| **Variables réutilisables** | Pas de système de variables | Répéter les lookups |
| **Relations sur ID juste créé** | Lookup nécessite données existantes | Scénario hybride avec cache ID |

### Exemple de limitation : Calculs complexes

**✅ Maintenant possible en YAML avec `{{ math() }}`** :
```yaml
vars:
  price: "10.50"
  quantity: "3"

load:
  - table: order_items
    data:
      price: "{{ $price }}"
      quantity: "{{ $quantity }}"
      total: "{{ math($price*$quantity) }}"  # Calcule 10.50 * 3 = 31.5
```

**❌ Toujours impossible : Logique conditionnelle**
```yaml
# On ne peut pas faire : if quantity > 10 then apply discount
- table: order_items
  data:
    price: 10.50
    quantity: 15
    discount: ??? # Impossible de faire if/else
```

**✅ Solution : Scénario hybride**
```php
class OrderPrism extends YamlPrism
{
    public function load(Scope $scope): void
    {
        parent::load($scope); // Charge le YAML
        
        // Puis logique conditionnelle en PHP
        $price = 10.50;
        $quantity = 15;
        $discount = $quantity > 10 ? 0.10 : 0;
        
        $this->insertAndTrack('order_items', [
            'price' => $price,
            'quantity' => $quantity,
            'discount' => $discount,
        ], []);
    }
}
```

### Quand utiliser YAML vs PHP vs Hybride ?

| Type | Cas d'usage |
|------|-------------|
| **YAML pur** | Données simples, lookups, calculs mathématiques, prototypage rapide |
| **PHP pur** | Logique métier complexe, conditions, boucles, algorithmes |
| **Hybride** | YAML pour structure + PHP pour logique conditionnelle avancée |

---

## Bonnes pratiques

### ✅ À faire

1. **Utilisez des scopes explicites** : `--scope=test_feature_x` plutôt que `--scope=test`
2. **Suffixez les données avec `{{ scope }}`** : Évite les conflits entre scopes
3. **Lookups pour toutes les FK** : Ne hardcodez jamais les IDs
4. **Pivot pour tables avec ID VARCHAR** : Trackez par la FK principale
5. **Commentez vos sections** : Rendez le YAML lisible

### ❌ À éviter

1. **IDs hardcodés** : `user_id: 123` → Utilisez un lookup
2. **Données sans scope** : `username: "admin"` → Risque de collision
3. **Timestamps manuels si DEFAULT existe** : Laissez la DB gérer
4. **Purge manuel inutile** : Le tracker automatique suffit souvent
5. **YAML pour logique complexe** : Passez à un scénario hybride

---

## Référence rapide

### Commandes

```bash
# Charger
php bin/console app:prism:load nom --scope=valeur

# Purger
php bin/console app:prism:purge nom --scope=valeur

# Lister
php bin/console app:prism:list
```

### Structure minimale

```yaml
load:
  - table: ma_table
    data:
      colonne: "valeur"
```

### Avec imports

```yaml
import:
  - base_users
  - base_acl
  
load:
  - table: ma_table
    data:
      colonne: "valeur"
```

### Placeholders

- `{{ scope }}` - Valeur du scope
- `{{ hash('pwd') }}` - Hash bcrypt
- `{{ env('VAR') }}` - Variable d'environnement
- `{{ now }}` - Timestamp actuel
- `{{ date('modifier') }}` - Date relative (+7 days, -1 week, etc.)
- `{{ uuid }}` - UUID v4
- `{{ $varname }}` - Variable personnalisée
- `{{ math(expression) }}` - Calcul mathématique

### Variables

```yaml
vars:
  user: "admin_{{ scope }}"
  price: "100"

load:
  - table: users
    data:
      username: "{{ $user }}"
      balance: "{{ math($price*10) }}"
```

### Lookup

```yaml
column:
  table: table
  where:
    col: "val"
  return: id
```

### Pivot

```yaml
pivot:
  id: valeur_ou_lookup
  column: nom_colonne
```

### Types

- `datetime_immutable`, `datetime`, `int`, `float`, `bool`, `string`

---

## 🔗 Ressources

- **[Documentation principale](PRISM.md)** - Vue d'ensemble du système
- **[Guide PHP](SCENARIOS_PHP.md)** - Créer des scénarios PHP
- **[Guide Hybride](PRISM_HYBRID.md)** - Combiner YAML et PHP