# 🎲 Générateur de Données Faker

Le système Prism intègre un **générateur de données aléatoires** avec **46 types différents** (44 types de base + json + serialize), couvrant les besoins des tests fonctionnels sans dépendance externe.

## 📋 Vue d'ensemble

### Avantages

✅ **0 dépendance** : Aucune librairie externe nécessaire  
✅ **46 types disponibles** : Identité, finance, crypto, dates, réseau, JSON, Serialize...  
✅ **Données françaises** : IBAN FR, SIREN, SIRET, NIR, codes postaux  
✅ **Scope-aware** : Génère des données uniques par scope  
✅ **Anti-répétition** : Évite les doublons dans les textes et slugs  
✅ **Production-ready** : Checksums valides (IBAN, SIREN, ISBN, EAN13...)  

### Deux syntaxes disponibles

| Approche | Syntaxe | Quand utiliser |
|----------|---------|----------------|
| **YAML** | `{{ fake(type, param) }}` | Scénarios déclaratifs simples |
| **PHP** | `$this->fake('type', param)` | Logique complexe, boucles, conditions |

---

## 📖 Utilisation en YAML

### Syntaxe de base

```yaml
load:
  - table: users
    data:
      username: "{{ fake(user) }}"
      email: "{{ fake(email) }}"
      firstname: "{{ fake(firstname) }}"
      age: "{{ fake(age) }}"
```

### Avec paramètres

```yaml
load:
  - table: users
    data:
      email: "{{ fake(email, 'company.com') }}"        # Domaine custom
      iban: "{{ fake(iban, 'DE') }}"                   # IBAN allemand
      phone: "{{ fake(tel, '+33') }}"                  # Téléphone français
      date: "{{ fake(date, 'd/m/Y') }}"               # Format custom
```

### Combinaison avec variables

```yaml
vars:
  domain: "acme.com"

load:
  - table: users
    data:
      email: "{{ fake(email, $domain) }}"              # Utilise la variable
```

---

## 💻 Utilisation en PHP

### Syntaxe de base

```php
use Prism\Application\Prism\AbstractPrism;
use Prism\Domain\ValueObject\{Scope, PrismName};

final class MyPrism extends AbstractPrism
{
    public function getName(): PrismName
    {
        return PrismName::fromString('my_prism');
    }
    
    public function load(Scope $scope): void
    {
        $userId = $this->insertAndTrack('users', [
            'username' => $this->fake('user'),
            'email' => $this->fake('email'),
            'firstname' => $this->fake('firstname'),
            'age' => $this->fake('age'),
        ]);
    }
}
```

### Avec paramètres

```php
$this->insertAndTrack('users', [
    'email' => $this->fake('email', 'company.com'),    // Domaine custom
    'iban' => $this->fake('iban', 'DE'),               // IBAN allemand
    'phone' => $this->fake('tel', '+33'),              // Téléphone français
    'date' => $this->fake('date', 'd/m/Y'),            // Format custom
]);
```

### Dans des boucles

```php
// Créer 10 utilisateurs avec des données uniques
for ($i = 0; $i < 10; $i++) {
    $this->insertAndTrack('users', [
        'username' => $this->fake('user'),
        'email' => $this->fake('email'),
        'firstname' => $this->fake('firstname'),
        'lastname' => $this->fake('lastname'),
        'company' => $this->fake('company'),
    ]);
}
```

---

## 📚 Types Disponibles

### 👤 Identité et Utilisateurs

| Type | Description | YAML | PHP | Résultat |
|------|-------------|------|-----|----------|
| `user`, `username` | Nom d'utilisateur unique | `{{ fake(user) }}` | `$this->fake('user')` | `user_a3f2b8c1_test` |
| `email` | Email avec scope | `{{ fake(email) }}` | `$this->fake('email')` | `user_f4e3d2c1_test@example.test` |
| `email` + domaine | Email custom | `{{ fake(email, 'acme.com') }}` | `$this->fake('email', 'acme.com')` | `user_abc123_test@acme.com` |
| `firstname` | Prénom | `{{ fake(firstname) }}` | `$this->fake('firstname')` | `Rick`, `Morty`, `Linus` |
| `lastname` | Nom de famille | `{{ fake(lastname) }}` | `$this->fake('lastname')` | `Sanchez`, `Torvalds` |
| `fullname` | Nom complet | `{{ fake(fullname) }}` | `$this->fake('fullname')` | `Rick Sanchez` |
| `company` | Entreprise | `{{ fake(company) }}` | `$this->fake('company')` | `Aperture Science` |
| `gender` | Genre | `{{ fake(gender) }}` | `$this->fake('gender')` | `male`, `female`, `other` |
| `age` | Âge (18-99) | `{{ fake(age) }}` | `$this->fake('age')` | `42` |
| `country` | Pays (43 pays) | `{{ fake(country) }}` | `$this->fake('country')` | `France`, `Germany` |

### 📍 Adresses

| Type | Description | YAML | PHP | Résultat |
|------|-------------|------|-----|----------|
| `postcode` | Code postal FR | `{{ fake(postcode) }}` | `$this->fake('postcode')` | `75001` |
| `postcode` + pays | Code postal localisé | `{{ fake(postcode, 'US') }}` | `$this->fake('postcode', 'US')` | `90210` |
| `street` | Rue FR | `{{ fake(street) }}` | `$this->fake('street')` | `42 Rue Victor Hugo` |
| `street` + pays | Rue localisée | `{{ fake(street, 'US') }}` | `$this->fake('street', 'US')` | `123 Main Street` |
| `city` | Ville FR | `{{ fake(city) }}` | `$this->fake('city')` | `Paris`, `Lyon` |
| `city` + pays | Ville localisée | `{{ fake(city, 'DE') }}` | `$this->fake('city', 'DE')` | `Berlin` |
| `address` | Adresse sans pays | `{{ fake(address) }}` | `$this->fake('address')` | `42 Rue Victor Hugo, 75001 Paris` |
| `address` + pays | Adresse localisée | `{{ fake(address, 'IT') }}` | `$this->fake('address', 'IT')` | `7 Via Roma, 00100 Roma` |
| `fulladdress` | Adresse + pays | `{{ fake(fulladdress) }}` | `$this->fake('fulladdress')` | `42 Rue Victor Hugo, 75001 Paris, France` |

### 🆔 Identifiants

| Type | Description | YAML | PHP | Résultat |
|------|-------------|------|-----|----------|
| `id` | ID numérique | `{{ fake(id) }}` | `$this->fake('id')` | `42857` |
| `uuid` | UUID v4 | `{{ fake(uuid) }}` | `$this->fake('uuid')` | `a3f2b8c1-4d5e-...` |

### 📋 Codes Standards

| Type | Description | YAML | PHP | Résultat |
|------|-------------|------|-----|----------|
| `isbn` | ISBN-13 avec checksum | `{{ fake(isbn) }}` | `$this->fake('isbn')` | `978-2-123-45678-9` |
| `ean13` | EAN-13 avec checksum | `{{ fake(ean13) }}` | `$this->fake('ean13')` | `1234567890128` |
| `vin` | Vehicle ID Number | `{{ fake(vin) }}` | `$this->fake('vin')` | `1HGBH41JXMN109186` |
| `ssn` | SSN (US) | `{{ fake(ssn) }}` | `$this->fake('ssn')` | `123-45-6789` |
| `nir` | NIR (French SSN) | `{{ fake(nir) }}` | `$this->fake('nir')` | `1 89 12 75 123 456 89` |

### 💰 Finance

| Type | Description | YAML | PHP | Résultat |
|------|-------------|------|-----|----------|
| `iban` | IBAN France | `{{ fake(iban) }}` | `$this->fake('iban')` | `FR76 12345 67890 ...` |
| `iban` + pays | IBAN multi-pays | `{{ fake(iban, 'DE') }}` | `$this->fake('iban', 'DE')` | `DE89 12345678 ...` |
| `siren` | SIREN avec Luhn | `{{ fake(siren) }}` | `$this->fake('siren')` | `123456782` |
| `siret` | SIRET | `{{ fake(siret) }}` | `$this->fake('siret')` | `12345678212345` |
| `creditcard` | Carte masquée | `{{ fake(creditcard) }}` | `$this->fake('creditcard')` | `4*** **** **** 1234` |

**IBAN supportés** : FR, DE, GB, ES, IT

### ₿ Crypto-monnaies

| Type | Description | YAML | PHP | Résultat |
|------|-------------|------|-----|----------|
| `crypto` | Bitcoin (défaut) | `{{ fake(crypto) }}` | `$this->fake('crypto')` | `1A1zP1eP5QGefi2D...` |
| `crypto` + type | Crypto spécifique | `{{ fake(crypto, 'eth') }}` | `$this->fake('crypto', 'eth')` | `0x742d35Cc6634...` |

**Types supportés** : btc, eth, xrp, sol, ltc, doge, ada, dot, usdt

### 📅 Dates et Heures

| Type | Description | YAML | PHP | Résultat |
|------|-------------|------|-----|----------|
| `date` | Date Y-m-d | `{{ fake(date) }}` | `$this->fake('date')` | `2015-03-21` |
| `date` + format | Date custom | `{{ fake(date, 'd/m/Y') }}` | `$this->fake('date', 'd/m/Y')` | `21/03/2015` |
| `date` + plage | Date dans plage | `{{ fake(date, 'Y-m-d', '2020-01-01', '2025-12-31') }}` | `$this->fake('date', 'Y-m-d', '2020-01-01', '2025-12-31')` | `2023-07-15` |
| `datetime` | DateTime | `{{ fake(datetime) }}` | `$this->fake('datetime')` | `2015-03-21 14:32:18` |
| `datetime` + format | DateTime custom | `{{ fake(datetime, 'Y-m-d H:i') }}` | `$this->fake('datetime', 'Y-m-d H:i')` | `2015-03-21 14:32` |
| `timestamp` | Unix timestamp | `{{ fake(timestamp) }}` | `$this->fake('timestamp')` | `1710334800` |
| `microtime` | Microtime float | `{{ fake(microtime) }}` | `$this->fake('microtime')` | `1710334800.123456` |

### 📁 Fichiers et Chemins

| Type | Description | YAML | PHP | Résultat |
|------|-------------|------|-----|----------|
| `pathfile` | Fichier .txt | `{{ fake(pathfile) }}` | `$this->fake('pathfile')` | `/tmp/file_a3f2b8c1.txt` |
| `pathfile` + ext | Fichier avec ext | `{{ fake(pathfile, 'pdf') }}` | `$this->fake('pathfile', 'pdf')` | `/tmp/file_a3f2b8c1.pdf` |
| `pathdir` | Répertoire | `{{ fake(pathdir) }}` | `$this->fake('pathdir')` | `/tmp/dir_a3f2b8c1` |
| `mime`, `mimetype` | Type MIME | `{{ fake(mime) }}` | `$this->fake('mime')` | `application/pdf` |
| `charset`, `encoding` | Encodage | `{{ fake(charset) }}` | `$this->fake('charset')` | `UTF-8` |

**Types MIME** : 35 types disponibles (application/pdf, image/png, video/mp4...)  
**Encodages** : 41 encodages disponibles (UTF-8, ISO-8859-1, Windows-1252...)

**Alias** : `mimetype` = `mime`, `encoding` = `charset`

### 📱 Appareils

| Type | Description | YAML | PHP | Résultat |
|------|-------------|------|-----|----------|
| `device` | Appareil court | `{{ fake(device) }}` | `$this->fake('device')` | `iPhone`, `Galaxy S23` |
| `device` + symbol | Symbole emoji | `{{ fake(device, 'symbol') }}` | `$this->fake('device', 'symbol')` | `📱`, `💻`, `⌚` |
| `fulldevice` | Appareil complet | `{{ fake(fulldevice) }}` | `$this->fake('fulldevice')` | `Apple iPhone 15 Pro Max` |

### 💱 Devises

| Type | Description | YAML | PHP | Résultat |
|------|-------------|------|-----|----------|
| `currency` | Code ISO aléatoire | `{{ fake(currency) }}` | `$this->fake('currency')` | `EUR`, `USD`, `GBP` |
| `currency` + symbol | Symbole aléatoire | `{{ fake(currency, 'symbol') }}` | `$this->fake('currency', 'symbol')` | `€`, `$`, `£` |
| `currency` + ISO | Devise spécifique | `{{ fake(currency, 'eur') }}` | `$this->fake('currency', 'eur')` | `EUR` |
| `currency` + pays | Via code pays | `{{ fake(currency, 'fr') }}` | `$this->fake('currency', 'fr')` | `EUR` |
| `currency` + ISO + symbol | Symbole spécifique | `{{ fake(currency, 'eur', 'symbol') }}` | `$this->fake('currency', 'eur', 'symbol')` | `€` |
| `fullcurrency` | Nom complet | `{{ fake(fullcurrency) }}` | `$this->fake('fullcurrency')` | `Euro`, `US Dollar` |

### 📝 Texte et Nombres

| Type | Description | YAML | PHP | Résultat |
|------|-------------|------|-----|----------|
| `text` | Lorem ipsum | `{{ fake(text) }}` | `$this->fake('text')` | `lorem ipsum dolor...` |
| `text` + length | Texte N chars | `{{ fake(text, 50) }}` | `$this->fake('text', 50)` | `lorem ipsum...` (50 chars) |
| `slug` | Slug auto | `{{ fake(slug) }}` | `$this->fake('slug')` | `rick-arasaka-742` |
| `slug` + text | Slug depuis texte | `{{ fake(slug, 'My Title') }}` | `$this->fake('slug', 'My Title')` | `my-title` |
| `number` | Nombre 1-100 | `{{ fake(number) }}` | `$this->fake('number')` | `42` |
| `number` + min,max | Nombre plage | `{{ fake(number, 10, 99) }}` | `$this->fake('number', 10, 99)` | `67` |
| `boolean` | Boolean 0/1 | `{{ fake(boolean) }}` | `$this->fake('boolean')` | `1` |
| `BOOLEAN` | Boolean false/true | `{{ fake(BOOLEAN) }}` | `$this->fake('BOOLEAN')` | `true` |

### 🌐 Réseau

| Type | Description | YAML | PHP | Résultat |
|------|-------------|------|-----|----------|
| `url` | URL aléatoire | `{{ fake(url) }}` | `$this->fake('url')` | `https://a3f2b8c1.com` |
| `url` + protocole | URL avec proto | `{{ fake(url, 'http') }}` | `$this->fake('url', 'http')` | `http://f4e3d2c1.io` |
| `ip` | IP v4 | `{{ fake(ip) }}` | `$this->fake('ip')` | `192.168.1.42` |
| `ipv6` | IP v6 | `{{ fake(ipv6) }}` | `$this->fake('ipv6')` | `2001:0db8:85a3:...` |
| `mac` | Adresse MAC | `{{ fake(mac) }}` | `$this->fake('mac')` | `A3:F2:B8:C1:4D:5E` |
| `tel`, `phone` | Téléphone | `{{ fake(tel) }}` | `$this->fake('tel')` | `612345678` |
| `tel` + prefix | Téléphone avec prefix | `{{ fake(tel, '+33') }}` | `$this->fake('tel', '+33')` | `+33123456789` |
| `useragent` | User-Agent | `{{ fake(useragent) }}` | `$this->fake('useragent')` | `Mozilla/5.0 (Windows...` |

### 📍 Localisation

| Type | Description | YAML | PHP | Résultat |
|------|-------------|------|-----|----------|
| `gps`, `coordinates` | GPS complet | `{{ fake(gps) }}` | `$this->fake('gps')` | `48.856614, 2.352222` |
| `latitude` | Latitude seule | `{{ fake(latitude) }}` | `$this->fake('latitude')` | `48.856614` |
| `longitude` | Longitude seule | `{{ fake(longitude) }}` | `$this->fake('longitude')` | `2.352222` |
| `iso` | Code ISO alpha-2 | `{{ fake(iso) }}` | `$this->fake('iso')` | `FR`, `US`, `DE` |
| `iso` + alpha3 | Code ISO alpha-3 | `{{ fake(iso, 'alpha3') }}` | `$this->fake('iso', 'alpha3')` | `FRA`, `USA` |

### 🎨 Couleurs

| Type | Description | YAML | PHP | Résultat |
|------|-------------|------|-----|----------|
| `color` | Nom CSS | `{{ fake(color) }}` | `$this->fake('color')` | `aliceblue`, `crimson` |
| `hexcolor` | Hex | `{{ fake(hexcolor) }}` | `$this->fake('hexcolor')` | `#A3F2B8` |
| `rgb` | RGB | `{{ fake(rgb) }}` | `$this->fake('rgb')` | `rgb(255, 128, 0)` |
| `rgba` | RGBA | `{{ fake(rgba) }}` | `$this->fake('rgba')` | `rgba(255, 128, 0, 0.75)` |

**Couleurs CSS** : 147 couleurs standard disponibles

### 📦 Structures de Données

| Type | Description | YAML | PHP | Résultat |
|------|-------------|------|-----|----------|
| `json` | JSON simple | `{{ fake(json, 'id:int, name:string') }}` | `$this->fake('json', 'id:int, name:string')` | `{"id": 42, "name": "lorem"}` |
| `json` | Tableau JSON | `{{ fake(json, 'int, int, int') }}` | `$this->fake('json', 'int, int, int')` | `[42, 57, 89]` |
| `json` | Objet imbriqué | `{{ fake(json, 'user:{id:int, email:email}') }}` | `$this->fake('json', 'user:{id:int, email:email}')` | `{"user": {"id": 42, ...}}` |
| `json` | Avec paramètres | `{{ fake(json, 'phone:tel:+33, age:number:18:99') }}` | `$this->fake('json', 'phone:tel:+33, age:number:18:99')` | `{"phone": "+33...", "age": 42}` |
| `serialize` | PHP serialize | `{{ fake(serialize, 'id:int, name:string') }}` | `$this->fake('serialize', 'id:int, name:string')` | `a:2:{s:2:"id";i:42;...}` |
| `serialize` | Avec paramètres | `{{ fake(serialize, 'user:user, email:email:acme.com') }}` | `$this->fake('serialize', 'user:user, email:email:acme.com')` | `a:2:{s:4:"user";...}` |

**Types supportés dans JSON/Serialize** : Tous les 44 types faker (int, float, boolean, string, email, user, iban, crypto, etc.)

**Syntaxe des paramètres** : `type:param1:param2` (ex: `email:acme.com`, `tel:+33`, `number:10:99`)

**Exemples JSON avancés** :
- Simple : `id:int, active:boolean`
- Imbriqué : `user:{id:int, profile:{name:string, email:email}}`
- Tableau : `int, int, int` ou `string, string`
- Avec paramètres : `bio:text:200, phone:tel:+33, iban:iban:FR`

**Exemples Serialize** :
- Configuration : `theme:string, lang:string, notifications:boolean`
- Metadata : `last_login:datetime, ip:ip, browser:useragent`
- Produits : `sku:ean13, color:hexcolor, price:float, stock:number:0:1000`

---

## 💡 Exemples Pratiques

### Exemple YAML : Utilisateurs complets

```yaml
load:
  - table: users
    data:
      username: "{{ fake(user) }}"
      email: "{{ fake(email, 'company.com') }}"
      firstname: "{{ fake(firstname) }}"
      lastname: "{{ fake(lastname) }}"
      company: "{{ fake(company) }}"
      gender: "{{ fake(gender) }}"
      age: "{{ fake(age) }}"
      phone: "{{ fake(tel, '+33') }}"
      address: "{{ fake(fulladdress) }}"
      iban: "{{ fake(iban) }}"
      created_at: "{{ fake(datetime) }}"
```

### Exemple PHP : Boucle avec données uniques

```php
public function load(Scope $scope): void
{
    // Créer 50 utilisateurs avec des données aléatoires uniques
    for ($i = 0; $i < 50; $i++) {
        $this->insertAndTrack('users', [
            'username' => $this->fake('user'),
            'email' => $this->fake('email'),
            'firstname' => $this->fake('firstname'),
            'lastname' => $this->fake('lastname'),
            'company' => $this->fake('company'),
            'gender' => $this->fake('gender'),
            'age' => $this->fake('age'),
            'phone' => $this->fake('tel', '+33'),
            'address' => $this->fake('fulladdress'),
            'iban' => $this->fake('iban'),
            'created_at' => new \DateTimeImmutable(),
        ]);
    }
}
```

### Exemple PHP : Données localisées

```php
public function load(Scope $scope): void
{
    // Client français
    $frenchUserId = $this->insertAndTrack('users', [
        'username' => $this->fake('user'),
        'email' => $this->fake('email', 'societe.fr'),
        'address' => $this->fake('fulladdress', 'FR'),
        'postcode' => $this->fake('postcode', 'FR'),
        'city' => $this->fake('city', 'FR'),
        'phone' => $this->fake('tel', '+33'),
        'iban' => $this->fake('iban', 'FR'),
        'currency' => $this->fake('currency', 'fr'),
    ]);

    // Client allemand
    $germanUserId = $this->insertAndTrack('users', [
        'username' => $this->fake('user'),
        'email' => $this->fake('email', 'unternehmen.de'),
        'address' => $this->fake('fulladdress', 'DE'),
        'postcode' => $this->fake('postcode', 'DE'),
        'city' => $this->fake('city', 'DE'),
        'phone' => $this->fake('tel', '+49'),
        'iban' => $this->fake('iban', 'DE'),
        'currency' => $this->fake('currency', 'de'),
    ]);
}
```

---

## 🔧 Détails Techniques

### Unicité par Scope

Tous les types qui incluent le scope (user, email, etc.) génèrent des valeurs **uniques par scope** :

```yaml
# Scope: dev_alice
{{ fake(user) }}   # user_a3f2b8c1_dev_alice
{{ fake(email) }}  # user_f4e3d2c1_dev_alice@example.test

# Scope: dev_bob
{{ fake(user) }}   # user_x9y8z7w6_dev_bob
{{ fake(email) }}  # user_m5n4o3p2_dev_bob@example.test
```

### Anti-Répétition

Le système mémorise les valeurs générées pour éviter les répétitions dans :
- **Texte** : lorem ipsum avec variations
- **Slug** : combinaisons geek/sci-fi uniques
- **Noms** : rotation dans les listes disponibles

### Checksums Valides

Les codes suivants utilisent des algorithmes de validation réels :
- **IBAN** : Checksum Modulo 97
- **SIREN** : Algorithme de Luhn
- **ISBN-13** : Checksum EAN
- **EAN-13** : Checksum EAN
- **NIR** : Clé de contrôle française

### Performance

Le générateur est optimisé pour :
- ✅ **0 ms latency** : Génération instantanée
- ✅ **0 allocation mémoire** : Pas de cache global
- ✅ **Thread-safe** : Génération aléatoire sécurisée

---

## 📚 Voir Aussi

- [Guide YAML complet](PRISM_YAML.md) - Tous les placeholders et pipes
- [Guide PHP complet](PRISM_PHP.md) - API AbstractPrism complète
- [Guide Hybride](PRISM_HYBRID.md) - Combiner YAML + PHP
- [Documentation principale](PRISM.md) - Vue d'ensemble du système
