# 🎲 Faker Data Generator

The Prism system integrates a **random data generator** with **46 different types** (44 base types + json + serialize), covering functional testing needs without external dependencies.

## 📋 Overview

### Advantages

✅ **0 dependencies**: No external library required  
✅ **46 available types**: Identity, finance, crypto, dates, network, JSON, Serialize...  
✅ **French data**: IBAN FR, SIREN, SIRET, NIR, postal codes  
✅ **Scope-aware**: Generates unique data per scope  
✅ **Anti-repetition**: Avoids duplicates in texts and slugs  
✅ **Production-ready**: Valid checksums (IBAN, SIREN, ISBN, EAN13...)  

### Two available syntaxes

| Approach | Syntax | When to use |
|----------|---------|-------------|
| **YAML** | `{{ fake(type, param) }}` | Simple declarative scenarios |
| **PHP** | `$this->fake('type', param)` | Complex logic, loops, conditions |

---

## 📖 YAML Usage

### Basic syntax

```yaml
load:
  - table: users
    data:
      username: "{{ fake(user) }}"
      email: "{{ fake(email) }}"
      firstname: "{{ fake(firstname) }}"
      age: "{{ fake(age) }}"
```

### With parameters

```yaml
load:
  - table: users
    data:
      email: "{{ fake(email, 'company.com') }}"        # Custom domain
      iban: "{{ fake(iban, 'DE') }}"                   # German IBAN
      phone: "{{ fake(tel, '+33') }}"                  # French phone
      date: "{{ fake(date, 'd/m/Y') }}"               # Custom format
```

### Combination with variables

```yaml
vars:
  domain: "acme.com"

load:
  - table: users
    data:
      email: "{{ fake(email, $domain) }}"              # Uses variable
```

---

## 💻 PHP Usage

### Basic syntax

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

### With parameters

```php
$this->insertAndTrack('users', [
    'email' => $this->fake('email', 'company.com'),    // Custom domain
    'iban' => $this->fake('iban', 'DE'),               // German IBAN
    'phone' => $this->fake('tel', '+33'),              // French phone
    'date' => $this->fake('date', 'd/m/Y'),            // Custom format
]);
```

### In loops

```php
// Create 10 users with unique data
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

## 📚 Available Types

### 👤 Identity and Users

| Type | Description | YAML | PHP | Result |
|------|-------------|------|-----|----------|
| `user`, `username` | Unique username | `{{ fake(user) }}` | `$this->fake('user')` | `user_a3f2b8c1_test` |
| `email` | Email with scope | `{{ fake(email) }}` | `$this->fake('email')` | `user_f4e3d2c1_test@example.test` |
| `email` + domain | Custom email | `{{ fake(email, 'acme.com') }}` | `$this->fake('email', 'acme.com')` | `user_abc123_test@acme.com` |
| `firstname` | First name | `{{ fake(firstname) }}` | `$this->fake('firstname')` | `Rick`, `Morty`, `Linus` |
| `lastname` | Last name | `{{ fake(lastname) }}` | `$this->fake('lastname')` | `Sanchez`, `Torvalds` |
| `fullname` | Full name | `{{ fake(fullname) }}` | `$this->fake('fullname')` | `Rick Sanchez` |
| `company` | Company | `{{ fake(company) }}` | `$this->fake('company')` | `Aperture Science` |
| `gender` | Gender | `{{ fake(gender) }}` | `$this->fake('gender')` | `male`, `female`, `other` |
| `age` | Age (18-99) | `{{ fake(age) }}` | `$this->fake('age')` | `42` |
| `country` | Country (43 countries) | `{{ fake(country) }}` | `$this->fake('country')` | `France`, `Germany` |

### 📍 Addresses

| Type | Description | YAML | PHP | Result |
|------|-------------|------|-----|----------|
| `postcode` | French postal code | `{{ fake(postcode) }}` | `$this->fake('postcode')` | `75001` |
| `postcode` + country | Localized postal code | `{{ fake(postcode, 'US') }}` | `$this->fake('postcode', 'US')` | `90210` |
| `street` | French street | `{{ fake(street) }}` | `$this->fake('street')` | `42 Rue Victor Hugo` |
| `street` + country | Localized street | `{{ fake(street, 'US') }}` | `$this->fake('street', 'US')` | `123 Main Street` |
| `city` | French city | `{{ fake(city) }}` | `$this->fake('city')` | `Paris`, `Lyon` |
| `city` + country | Localized city | `{{ fake(city, 'DE') }}` | `$this->fake('city', 'DE')` | `Berlin` |
| `address` | Address without country | `{{ fake(address) }}` | `$this->fake('address')` | `42 Rue Victor Hugo, 75001 Paris` |
| `address` + country | Localized address | `{{ fake(address, 'IT') }}` | `$this->fake('address', 'IT')` | `7 Via Roma, 00100 Roma` |
| `fulladdress` | Address + country | `{{ fake(fulladdress) }}` | `$this->fake('fulladdress')` | `42 Rue Victor Hugo, 75001 Paris, France` |

### 🆔 Identifiers

| Type | Description | YAML | PHP | Result |
|------|-------------|------|-----|----------|
| `id` | Numeric ID | `{{ fake(id) }}` | `$this->fake('id')` | `42857` |
| `uuid` | UUID v4 | `{{ fake(uuid) }}` | `$this->fake('uuid')` | `a3f2b8c1-4d5e-...` |

### 📋 Standard Codes

| Type | Description | YAML | PHP | Result |
|------|-------------|------|-----|----------|
| `isbn` | ISBN-13 with checksum | `{{ fake(isbn) }}` | `$this->fake('isbn')` | `978-2-123-45678-9` |
| `ean13` | EAN-13 with checksum | `{{ fake(ean13) }}` | `$this->fake('ean13')` | `1234567890128` |
| `vin` | Vehicle ID Number | `{{ fake(vin) }}` | `$this->fake('vin')` | `1HGBH41JXMN109186` |
| `ssn` | SSN (US) | `{{ fake(ssn) }}` | `$this->fake('ssn')` | `123-45-6789` |
| `nir` | NIR (French SSN) | `{{ fake(nir) }}` | `$this->fake('nir')` | `1 89 12 75 123 456 89` |

### 💰 Finance

| Type | Description | YAML | PHP | Result |
|------|-------------|------|-----|----------|
| `iban` | French IBAN | `{{ fake(iban) }}` | `$this->fake('iban')` | `FR76 12345 67890 ...` |
| `iban` + country | Multi-country IBAN | `{{ fake(iban, 'DE') }}` | `$this->fake('iban', 'DE')` | `DE89 12345678 ...` |
| `siren` | SIREN with Luhn | `{{ fake(siren) }}` | `$this->fake('siren')` | `123456782` |
| `siret` | SIRET | `{{ fake(siret) }}` | `$this->fake('siret')` | `12345678212345` |
| `creditcard` | Masked card | `{{ fake(creditcard) }}` | `$this->fake('creditcard')` | `4*** **** **** 1234` |

**Supported IBAN**: FR, DE, GB, ES, IT

### ₿ Cryptocurrencies

| Type | Description | YAML | PHP | Result |
|------|-------------|------|-----|----------|
| `crypto` | Bitcoin (default) | `{{ fake(crypto) }}` | `$this->fake('crypto')` | `1A1zP1eP5QGefi2D...` |
| `crypto` + type | Specific crypto | `{{ fake(crypto, 'eth') }}` | `$this->fake('crypto', 'eth')` | `0x742d35Cc6634...` |

**Supported types**: btc, eth, xrp, sol, ltc, doge, ada, dot, usdt

### 📅 Dates and Times

| Type | Description | YAML | PHP | Result |
|------|-------------|------|-----|----------|
| `date` | Date Y-m-d | `{{ fake(date) }}` | `$this->fake('date')` | `2015-03-21` |
| `date` + format | Custom date | `{{ fake(date, 'd/m/Y') }}` | `$this->fake('date', 'd/m/Y')` | `21/03/2015` |
| `date` + range | Date in range | `{{ fake(date, 'Y-m-d', '2020-01-01', '2025-12-31') }}` | `$this->fake('date', 'Y-m-d', '2020-01-01', '2025-12-31')` | `2023-07-15` |
| `datetime` | DateTime | `{{ fake(datetime) }}` | `$this->fake('datetime')` | `2015-03-21 14:32:18` |
| `datetime` + format | Custom DateTime | `{{ fake(datetime, 'Y-m-d H:i') }}` | `$this->fake('datetime', 'Y-m-d H:i')` | `2015-03-21 14:32` |
| `timestamp` | Unix timestamp | `{{ fake(timestamp) }}` | `$this->fake('timestamp')` | `1710334800` |
| `microtime` | Microtime float | `{{ fake(microtime) }}` | `$this->fake('microtime')` | `1710334800.123456` |

### 📁 Files and Paths

| Type | Description | YAML | PHP | Result |
|------|-------------|------|-----|----------|
| `pathfile` | .txt file | `{{ fake(pathfile) }}` | `$this->fake('pathfile')` | `/tmp/file_a3f2b8c1.txt` |
| `pathfile` + ext | File with extension | `{{ fake(pathfile, 'pdf') }}` | `$this->fake('pathfile', 'pdf')` | `/tmp/file_a3f2b8c1.pdf` |
| `pathdir` | Directory | `{{ fake(pathdir) }}` | `$this->fake('pathdir')` | `/tmp/dir_a3f2b8c1` |
| `mime`, `mimetype` | MIME type | `{{ fake(mime) }}` | `$this->fake('mime')` | `application/pdf` |
| `charset`, `encoding` | Encoding | `{{ fake(charset) }}` | `$this->fake('charset')` | `UTF-8` |

**MIME types**: 35 available types (application/pdf, image/png, video/mp4...)  
**Encodings**: 41 available encodings (UTF-8, ISO-8859-1, Windows-1252...)

**Aliases**: `mimetype` = `mime`, `encoding` = `charset`

### 📱 Devices

| Type | Description | YAML | PHP | Result |
|------|-------------|------|-----|----------|
| `device` | Short device | `{{ fake(device) }}` | `$this->fake('device')` | `iPhone`, `Galaxy S23` |
| `device` + symbol | Emoji symbol | `{{ fake(device, 'symbol') }}` | `$this->fake('device', 'symbol')` | `📱`, `💻`, `⌚` |
| `fulldevice` | Full device | `{{ fake(fulldevice) }}` | `$this->fake('fulldevice')` | `Apple iPhone 15 Pro Max` |

### 💱 Currencies

| Type | Description | YAML | PHP | Result |
|------|-------------|------|-----|----------|
| `currency` | Random ISO code | `{{ fake(currency) }}` | `$this->fake('currency')` | `EUR`, `USD`, `GBP` |
| `currency` + symbol | Random symbol | `{{ fake(currency, 'symbol') }}` | `$this->fake('currency', 'symbol')` | `€`, `$`, `£` |
| `currency` + ISO | Specific currency | `{{ fake(currency, 'eur') }}` | `$this->fake('currency', 'eur')` | `EUR` |
| `currency` + country | Via country code | `{{ fake(currency, 'fr') }}` | `$this->fake('currency', 'fr')` | `EUR` |
| `currency` + ISO + symbol | Specific symbol | `{{ fake(currency, 'eur', 'symbol') }}` | `$this->fake('currency', 'eur', 'symbol')` | `€` |
| `fullcurrency` | Full name | `{{ fake(fullcurrency) }}` | `$this->fake('fullcurrency')` | `Euro`, `US Dollar` |

### 📝 Text and Numbers

| Type | Description | YAML | PHP | Result |
|------|-------------|------|-----|----------|
| `text` | Lorem ipsum | `{{ fake(text) }}` | `$this->fake('text')` | `lorem ipsum dolor...` |
| `text` + length | Text N chars | `{{ fake(text, 50) }}` | `$this->fake('text', 50)` | `lorem ipsum...` (50 chars) |
| `slug` | Auto slug | `{{ fake(slug) }}` | `$this->fake('slug')` | `rick-arasaka-742` |
| `slug` + text | Slug from text | `{{ fake(slug, 'My Title') }}` | `$this->fake('slug', 'My Title')` | `my-title` |
| `number` | Number 1-100 | `{{ fake(number) }}` | `$this->fake('number')` | `42` |
| `number` + min,max | Number range | `{{ fake(number, 10, 99) }}` | `$this->fake('number', 10, 99)` | `67` |
| `boolean` | Boolean 0/1 | `{{ fake(boolean) }}` | `$this->fake('boolean')` | `1` |
| `BOOLEAN` | Boolean false/true | `{{ fake(BOOLEAN) }}` | `$this->fake('BOOLEAN')` | `true` |

### 🌐 Network

| Type | Description | YAML | PHP | Result |
|------|-------------|------|-----|----------|
| `url` | Random URL | `{{ fake(url) }}` | `$this->fake('url')` | `https://a3f2b8c1.com` |
| `url` + protocol | URL with protocol | `{{ fake(url, 'http') }}` | `$this->fake('url', 'http')` | `http://f4e3d2c1.io` |
| `ip` | IP v4 | `{{ fake(ip) }}` | `$this->fake('ip')` | `192.168.1.42` |
| `ipv6` | IP v6 | `{{ fake(ipv6) }}` | `$this->fake('ipv6')` | `2001:0db8:85a3:...` |
| `mac` | MAC address | `{{ fake(mac) }}` | `$this->fake('mac')` | `A3:F2:B8:C1:4D:5E` |
| `tel`, `phone` | Phone | `{{ fake(tel) }}` | `$this->fake('tel')` | `612345678` |
| `tel` + prefix | Phone with prefix | `{{ fake(tel, '+33') }}` | `$this->fake('tel', '+33')` | `+33123456789` |
| `useragent` | User-Agent | `{{ fake(useragent) }}` | `$this->fake('useragent')` | `Mozilla/5.0 (Windows...` |

### 📍 Location

| Type | Description | YAML | PHP | Result |
|------|-------------|------|-----|----------|
| `gps`, `coordinates` | Full GPS | `{{ fake(gps) }}` | `$this->fake('gps')` | `48.856614, 2.352222` |
| `latitude` | Latitude only | `{{ fake(latitude) }}` | `$this->fake('latitude')` | `48.856614` |
| `longitude` | Longitude only | `{{ fake(longitude) }}` | `$this->fake('longitude')` | `2.352222` |
| `iso` | ISO alpha-2 code | `{{ fake(iso) }}` | `$this->fake('iso')` | `FR`, `US`, `DE` |
| `iso` + alpha3 | ISO alpha-3 code | `{{ fake(iso, 'alpha3') }}` | `$this->fake('iso', 'alpha3')` | `FRA`, `USA` |

### 🎨 Colors

| Type | Description | YAML | PHP | Result |
|------|-------------|------|-----|----------|
| `color` | CSS name | `{{ fake(color) }}` | `$this->fake('color')` | `aliceblue`, `crimson` |
| `hexcolor` | Hex | `{{ fake(hexcolor) }}` | `$this->fake('hexcolor')` | `#A3F2B8` |
| `rgb` | RGB | `{{ fake(rgb) }}` | `$this->fake('rgb')` | `rgb(255, 128, 0)` |
| `rgba` | RGBA | `{{ fake(rgba) }}` | `$this->fake('rgba')` | `rgba(255, 128, 0, 0.75)` |

**CSS colors**: 147 standard colors available

### 📦 Data Structures

| Type | Description | YAML | PHP | Result |
|------|-------------|------|-----|----------|
| `json` | Simple JSON | `{{ fake(json, 'id:int, name:string') }}` | `$this->fake('json', 'id:int, name:string')` | `{"id": 42, "name": "lorem"}` |
| `json` | JSON array | `{{ fake(json, 'int, int, int') }}` | `$this->fake('json', 'int, int, int')` | `[42, 57, 89]` |
| `json` | Nested object | `{{ fake(json, 'user:{id:int, email:email}') }}` | `$this->fake('json', 'user:{id:int, email:email}')` | `{"user": {"id": 42, ...}}` |
| `json` | With parameters | `{{ fake(json, 'phone:tel:+33, age:number:18:99') }}` | `$this->fake('json', 'phone:tel:+33, age:number:18:99')` | `{"phone": "+33...", "age": 42}` |
| `serialize` | PHP serialize | `{{ fake(serialize, 'id:int, name:string') }}` | `$this->fake('serialize', 'id:int, name:string')` | `a:2:{s:2:"id";i:42;...}` |
| `serialize` | With parameters | `{{ fake(serialize, 'user:user, email:email:acme.com') }}` | `$this->fake('serialize', 'user:user, email:email:acme.com')` | `a:2:{s:4:"user";...}` |

**Supported types in JSON/Serialize**: All 44 faker types (int, float, boolean, string, email, user, iban, crypto, etc.)

**Parameter syntax**: `type:param1:param2` (e.g., `email:acme.com`, `tel:+33`, `number:10:99`)

**Advanced JSON examples**:
- Simple: `id:int, active:boolean`
- Nested: `user:{id:int, profile:{name:string, email:email}}`
- Array: `int, int, int` or `string, string`
- With parameters: `bio:text:200, phone:tel:+33, iban:iban:FR`

**Serialize examples**:
- Configuration: `theme:string, lang:string, notifications:boolean`
- Metadata: `last_login:datetime, ip:ip, browser:useragent`
- Products: `sku:ean13, color:hexcolor, price:float, stock:number:0:1000`

---

## 💡 Practical Examples

### YAML Example: Complete Users

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

### PHP Example: Loop with unique data

```php
public function load(Scope $scope): void
{
    // Create 50 users with unique random data
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

### PHP Example: Localized data

```php
public function load(Scope $scope): void
{
    // French customer
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

    // German customer
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

## 🔧 Technical Details

### Scope Uniqueness

All types that include the scope (user, email, etc.) generate **unique values per scope**:

```yaml
# Scope: dev_alice
{{ fake(user) }}   # user_a3f2b8c1_dev_alice
{{ fake(email) }}  # user_f4e3d2c1_dev_alice@example.test

# Scope: dev_bob
{{ fake(user) }}   # user_x9y8z7w6_dev_bob
{{ fake(email) }}  # user_m5n4o3p2_dev_bob@example.test
```

### Anti-Repetition

The system memorizes generated values to avoid repetitions in:
- **Text**: lorem ipsum with variations
- **Slug**: unique geek/sci-fi combinations
- **Names**: rotation in available lists

### Valid Checksums

The following codes use real validation algorithms:
- **IBAN**: Modulo 97 checksum
- **SIREN**: Luhn algorithm
- **ISBN-13**: EAN checksum
- **EAN-13**: EAN checksum
- **NIR**: French control key

### Performance

The generator is optimized for:
- ✅ **0 ms latency**: Instant generation
- ✅ **0 memory allocation**: No global cache
- ✅ **Thread-safe**: Secure random generation

---

## 📚 See Also

- [Complete YAML Guide](PRISM_YAML.md) - All placeholders and pipes
- [Complete PHP Guide](PRISM_PHP.md) - Full AbstractPrism API
- [Hybrid Guide](PRISM_HYBRID.md) - Combine YAML + PHP
- [Main Documentation](PRISM.md) - System overview
