# Complete YAML Scenarios Guide

## Table of Contents

1. [Introduction](#introduction)
2. [Basic structure](#basic-structure)
3. [Scenario imports](#scenario-imports)
4. [Variables](#variables)
5. [Placeholders](#placeholders)
6. [Pipes (Transformers)](#pipes-transformers)
7. [Dynamic lookup](#dynamic-lookup)
8. [Data types](#data-types)
9. [Custom pivot](#custom-pivot)
10. [Custom purge](#custom-purge)
11. [Complete examples](#complete-examples)
12. [Limitations](#limitations)

---

## Introduction

YAML scenarios allow creating test datasets declaratively, without writing PHP code. They are ideal for:

- ✅ Simple data insertion with relationships
- ✅ Fast functional tests
- ✅ Fixture prototyping
- ✅ Demo data

**Files**: YAML scenarios are placed in `prisms/yaml/*.yaml`

**Commands**:
```bash
# Load a scenario
php bin/console app:prism:load scenario_name --scope=my_scope

# Purge a scenario
php bin/console app:prism:purge scenario_name --scope=my_scope

# List available scenarios
php bin/console app:prism:list
```

---

## Basic structure

### Minimal file

```yaml
load:
  - table: users
    data:
      username: "john_doe"
      email: "john@example.com"
      password: "hashed_password"
```

### Complete structure

```yaml
import:
  # Import other scenarios (optional)
  - base_users
  - base_acl

vars:
  # Reusable variables (optional)
  # Declared without $ but used with $ in data
  admin: "admin_{{ scope }}"
  contract: "0xABCDEF1234567890"

load:
  # Insertion instructions
  - table: table_name
    db: hexagonal_secondary  # Database (optional)
    data:
      column1: "{{ $admin }}"  # Usage with $
      column2: 123
    types:
      column1: string
    pivot:
      id: 456
      column: custom_id

purge:
  # Custom purge instructions (optional)
  - table: table_name
    db: hexagonal_secondary  # Database (optional)
    where:
      column: "{{ $admin }}"  # Usage with $
```

**Sections**:
- `import` (optional): List of scenarios to import
- `vars` (optional): Reusable variables throughout scenario
- `load` (required): List of insertions to perform
  - `db` (optional): Target database name (default: default database)
- `purge` (optional): Custom purge, otherwise automatic purge via tracker
  - `db` (optional): Target database name (default: default database)

---

## Scenario imports

The import system allows reusing existing scenarios as "modules" to compose more complex scenarios.

### Basic syntax

```yaml
import:
  - base_users
  - base_acl
```

Imported files must exist in `prisms/yaml/` folder:
- `prisms/yaml/base_users.yaml`
- `prisms/yaml/base_acl.yaml`

**⚠️ Important: Absolute paths from `prisms/yaml/`**

All imports are **always relative to `prisms/yaml/` folder**, regardless of where the importing file is located.

```yaml
# prisms/yaml/main.yaml
import:
  - test/admin_user              # → prisms/yaml/test/admin_user.yaml

# prisms/yaml/test/admin_user.yaml (in subfolder)
import:
  - test/users/acl_users_admin   # → prisms/yaml/test/users/acl_users_admin.yaml
  
# ❌ NOT this:
import:
  - users/acl_users_admin        # → looks for prisms/yaml/users/ (not prisms/yaml/test/users/)
```

**Rule**: Always write the full path from `prisms/yaml/`, even in subfolders.

### How it works

**Section concatenation**:

1. **Load**: Import instructions execute **before** local instructions
2. **Purge**: Order is **automatically reversed** (local → imports) to respect FK
3. **Variables**: Variables are merged (local file overrides imports)

**Import example**:

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
      description: "Post management"

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
  post_title: "Test article"

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

**Load execution order**:
1. `base_users` → Creates admin user
2. `base_acl` → Creates ACLs
3. `my_test` (local) → Creates message (lookup possible because user exists)

**Purge execution order** (reversed):
1. `my_test` (local) → Deletes message
2. `base_acl` → Deletes ACLs  
3. `base_users` → Deletes user

**Visual diagram**:

```
[Import execution order]
main.yaml
├─► base_users (load)
├─► base_acl (load)
└─► main (load)

[Reversed purge]
main (purge)
├─► base_acl (purge)
└─► base_users (purge)
```

### Variable merging

Variables are merged with priority to local file:

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
  post_title: "Test"    # New variable

# Result:
# admin_pwd: "admin123"  (from base_users)
# domain: "test.local"   (overridden)
# post_title: "Test"     (new)
```

### Circular import protection

The system detects and prevents circular imports:

```yaml
# a.yaml
import:
  - b  # ❌ Error: b imports a

# b.yaml
import:
  - a  # ❌ Circular import detected
```

### Use cases

**✅ Good usage**:
- Factor common data (users, ACL, settings)
- Create reusable "presets"
- Compose complex scenarios from simple modules

```yaml
# Example: E-commerce scenario
import:
  - base_users      # Admin + customers
  - base_products   # Catalog
  - base_categories # Categories

load:
  - table: orders
    data:
      user_id: 1
      total: 99.99
```

**❌ Bad usage**:
- Deep recursive imports (limit: 1-2 levels max)
- Circular dependencies between scenarios
- Importing too many files (limit: 3-5 imports max)

### Limitations

- ❌ No conditional imports
- ❌ No import parameters
- ❌ Recommended max depth: 2 levels

---

## Variables

Variables allow defining reusable values throughout the scenario, avoiding repetition and facilitating maintenance.

### Declaration

Variables are declared **without `$`** in the `vars` section at file start:

```yaml
vars:
  admin: "admin_{{ scope }}"
  manager: "manager_{{ scope }}"
  contract: "0xABCDEF1234567890ABCDEF1234567890ABCDEF12"
  email_domain: "example.test"
```

**Rules**:
- Name must **not** start with `$` in declaration
- Values can contain placeholders (`{{ scope }}`, `{{ hash() }}`, etc.)
- Placeholders in variables are resolved when scenario loads

### Usage

Variables are referenced **with `{{ $variable_name }}`** in data:

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
      title: "Article by {{ $admin }}"
      author_id:
        table: users
        where:
          username: "{{ $admin }}"
        return: id
```

**Important**: The `$` clearly distinguishes custom variables from system placeholders:
- `{{ $admin }}` → Custom variable
- `{{ scope }}` → System placeholder
- `{{ now }}` → System placeholder

### Advanced examples

**Variables with combined placeholders**:
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

**Variables for lookups**:
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

### Advantages

| Advantage | Description |
|----------|-------------|
| **DRY** | Avoids repetition of common values |
| **Maintenance** | Centralized value changes |
| **Readability** | Clearer code with explicit names |
| **Flexibility** | Combines variables and placeholders |

### Limitations

❌ Global variables **cannot**:
- Reference other variables (`$var1: "{{ $var2 }}"` not supported)
- Contain conditional logic
- Be modified dynamically during execution

✅ For these advanced cases, use a **hybrid scenario** (YAML + PHP).

---

## Temporary variables (auto-generated)

In addition to global variables, **each `data` field automatically creates a temporary variable** reusable in following fields of the same block.

### How it works

```yaml
- table: users
  data:
    username: "alice_{{ scope }}"       # Creates {{ $username }}
    email: "{{ $username }}@test.com"   # Uses {{ $username }}
    full_name: "User {{ $username }}"   # Reuses {{ $username }}
```

**Each field automatically becomes a variable**:
- `username` → `{{ $username }}`
- `email` → `{{ $email }}`
- `full_name` → `{{ $full_name }}`

### Block-scoped

Temporary variables are **reset with each new `data` block**:

```yaml
vars:
  user1: "alice_{{ scope }}"
  user2: "bob_{{ scope }}"

load:
  # Block 1: Alice
  - table: users
    data:
      username: "{{ $user1 }}"              # Creates {{ $username }}
      email: "{{ $username }}@test.com"     # {{ $username }} = alice_test
      bio: "I am {{ $username }}"           # Reuses {{ $username }}
  
  # Block 2: Bob (block 1 variables don't exist anymore)
  - table: users
    data:
      username: "{{ $user2 }}"              # Creates a NEW {{ $username }}
      email: "{{ $username }}@test.com"     # {{ $username }} = bob_test now
      bio: "I am {{ $username }}"           # New {{ $username }}
```

### Variable priority

When a variable exists both globally and temporarily, **temporary takes priority**:

```yaml
vars:
  username: "global_user"  # Global variable

load:
  - table: users
    data:
      username: "local_user"              # Creates temporary {{ $username }}
      message: "Hello {{ $username }}"    # Uses temporary = "local_user"
  
  - table: posts
    data:
      author: "{{ $username }}"            # Temporary variable doesn't exist anymore
                                           # Uses global = "global_user"
```

**Search order**:
1. Temporary variables (current block fields)
2. Global variables (`vars:`)
3. Error if not found

### Advanced examples

**Calculations with temporary variables**:
```yaml
vars:
  base_price: "100"

load:
  - table: products
    data:
      name: "Product {{ scope }}"
      price: "{{ $base_price }}"                    # Creates {{ $price }}
      quantity: "5"                                  # Creates {{ $quantity }}
      total: "{{ math($price*$quantity) }}"         # = 500
      discount: "{{ math($total*0.1) }}"            # = 50
      final_price: "{{ math($total-$discount) }}"   # = 450
```

**Reuse in lookups**:
```yaml
- table: users
  data:
    username: "alice_{{ scope }}"     # Creates {{ $username }}
    email: "{{ $username }}@test.com"

- table: chat_messages
  data:
    id: "msg_{{ uuid }}"
    username: "alice_{{ scope }}"     # Creates {{ $username }} (new block)
    user_id:
      table: users
      where:
        username: "{{ $username }}"    # Uses temporary variable
      return: id
    message: "Hello from {{ $username }}"
```

### Advantages

| Advantage | Description |
|----------|-------------|
| **No repetition** | No need to rewrite same values |
| **Self-documenting** | Fields become named variables |
| **Isolated scope** | Each block is independent |
| **Combination** | Works with `{{ math() }}` and other placeholders |

### Limitations

❌ Temporary variables **cannot**:
- Be used in a different block
- Be manually redefined
- Persist after block end

✅ For variables shared between blocks: use **global variables** (`vars:`).

---

## Placeholders

Placeholders allow injecting dynamic values into your data and variables.

### `{{ scope }}`

Injects the scope value passed as command parameter.

**Example**:
```yaml
- table: users
  data:
    username: "admin_{{ scope }}"
    email: "admin_{{ scope }}@example.test"
```

**Command**:
```bash
php bin/console app:prism:load my_prism --scope=prod2024
# Creates: admin_prod2024 / admin_prod2024@example.test
```

### `{{ hash('password') }}`

Generates a bcrypt hash of a password.

**Example**:
```yaml
- table: users
  data:
    password: "{{ hash('my_secret_password') }}"
```

### `{{ env('VAR') }}`

Retrieves an environment variable. Searches in `$_ENV`, `$_SERVER` then `getenv()`.

**Example**:
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

**Notes**:
- Throws error if variable doesn't exist
- Useful for environment configurations
- Can be used in global variables (`vars:`)

### `{{ now }}`

Inserts current timestamp in `Y-m-d H:i:s` format.

**Example**:
```yaml
- table: posts
  data:
    title: "My article"
    created_at: "{{ now }}"
  types:
    created_at: datetime_immutable
```

### `{{ date('modifier') }}`

Generates a date with relative modifier. More flexible than `{{ now }}` for future or past dates.

**Examples**:
```yaml
- table: subscriptions
  data:
    user_id: 123
    starts_at: "{{ now }}"
    expires_at: "{{ date('+7 days') }}"      # In 7 days
    trial_ends: "{{ date('+14 days') }}"     # In 2 weeks
  types:
    starts_at: datetime_immutable
    expires_at: datetime_immutable
    trial_ends: datetime_immutable
```

**Supported modifiers**:

| Modifier | Description | Example |
|--------------|-------------|---------|
| `+1 day` | Tomorrow | `{{ date('+1 day') }}` |
| `-1 day` | Yesterday | `{{ date('-1 day') }}` |
| `+7 days` | In 7 days | `{{ date('+7 days') }}` |
| `+1 week` | In 1 week | `{{ date('+1 week') }}` |
| `-1 week` | 1 week ago | `{{ date('-1 week') }}` |
| `+1 month` | In 1 month | `{{ date('+1 month') }}` |
| `+1 year` | In 1 year | `{{ date('+1 year') }}` |
| `next monday` | Next Monday | `{{ date('next monday') }}` |
| `last friday` | Last Friday | `{{ date('last friday') }}` |

**Notes**:
- Uses PHP's `strtotime()` (very flexible)
- Output format: `Y-m-d H:i:s`
- Combine with `types:` to convert to `DateTimeImmutable`

### `{{ uuid }}`

Generates a unique UUID v4.

**Example**:
```yaml
- table: chat_messages
  data:
    id: "msg_{{ scope }}_{{ uuid }}"
    message: "Hello World"
```

**Result**: `msg_test_a3f2b8c1-4d5e-4f6a-8b9c-1d2e3f4a5b6c`

### `{{ math(expression) }}`

Evaluates a mathematical expression. Supports `+`, `-`, `*`, `/`, `%` operators and parentheses.

**Simple examples**:
```yaml
- table: products
  data:
    name: "Product {{ scope }}"
    price: "{{ math(100+50) }}"        # 150
    discount: "{{ math(10*2) }}"       # 20
    total: "{{ math((100+50)-20) }}"   # 130
```

**With variables**:
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

**Supported operators**:

| Operator | Description | Example | Result |
|-----------|-------------|---------|----------|
| `+` | Addition | `{{ math(10+5) }}` | 15 |
| `-` | Subtraction | `{{ math(10-5) }}` | 5 |
| `*` | Multiplication | `{{ math(10*5) }}` | 50 |
| `/` | Division | `{{ math(10/5) }}` | 2 |
| `%` | Modulo | `{{ math(10%3) }}` | 1 |
| `()` | Parentheses | `{{ math((10+5)*2) }}` | 30 |

**Important notes**:
- Expressions are evaluated securely
- Only numbers and math operators are allowed
- Variables must be numeric
- Division by zero throws error

### `{{ fake(type, param1, param2, ...) }}`

Generates **random data** for tests.

```yaml
data:
  username: "{{ fake(user) }}"
  email: "{{ fake(email, 'company.com') }}"
  iban: "{{ fake(iban, 'DE') }}"
```

**📚 44 available types**: See [Complete Faker Guide](PRISM_FAKER.md)

---

## Pipes (Transformers)

Pipes allow **transforming placeholder values** by chaining transformation functions. Used with the `|` (pipe) operator.

### Syntax

```yaml
{{ expression|pipe1|pipe2(arg1, arg2)|pipe3 }}
```

**Principle**:
1. Expression is evaluated (scope, uuid, hash, etc.)
2. Result passes through each pipe in order
3. Each pipe transforms value and passes to next

### Available pipes

#### `truncate(max)`

Limits string length to `max` characters.

**Examples**:
```yaml
- table: chat_messages
  data:
    id: "msg_{{ uuid|truncate(8) }}"              # msg_a3f2b8c1
    short_hash: "{{ hash(scope)|truncate(24) }}"   # $2y$10$0DUDts2PaCjoUZUwa
```

**Arguments**:
- `max` (int): Maximum number of characters

#### `trim`

Removes whitespace from string start and end.

**Examples**:
```yaml
vars:
  user_input: "  Hello World  "

load:
  - table: users
    data:
      username: "{{ $user_input|trim }}"     # "Hello World"
```

#### `uppercase` / `upper`

Converts string to uppercase.

**Examples**:
```yaml
- table: config
  data:
    key: "environment"
    value: "{{ scope|uppercase }}"            # PROD_2024
    code: "{{ scope|upper|replace('_', '-') }}"  # PROD-2024
```

**Aliases**: `uppercase` and `upper` are equivalent.

#### `lowercase` / `lower`

Converts string to lowercase.

**Examples**:
```yaml
- table: users
  data:
    username: "{{ scope|lowercase }}"         # prod_2024
    email: "{{ scope|lower }}@example.com"    # prod_2024@example.com
```

**Aliases**: `lowercase` and `lower` are equivalent.

#### `capitalize` / `ucfirst`

Capitalizes first letter.

**Examples**:
```yaml
vars:
  text: "  hello world  "

load:
  - table: posts
    data:
      title: "{{ $text|trim|lowercase|capitalize }}"  # "Hello world"
```

**Aliases**: `capitalize` and `ucfirst` are equivalent.

#### `replace(search, replace)`

Replaces all occurrences of a string with another.

**Examples**:
```yaml
- table: identifiers
  data:
    # UUID without dashes
    clean_uuid: "{{ uuid|replace('-', '') }}"
    # 9e5be96c19344e82bec946f36b9aa94c
    
    # UUID with underscores
    snake_uuid: "{{ uuid|replace('-', '_') }}"
    # 9e5be96c_1934_4e82_bec9_46f36b9aa94c
    
    # Formatted scope
    code: "{{ scope|uppercase|replace('_', '-') }}"
    # PROD-2024
```

**Arguments**:
- `search` (string): String to search for
- `replace` (string): Replacement string

**Notes**:
- Arguments must be in single or double quotes
- Escaping: use `\'` or `\"` if needed

#### `base64`

Encodes a string to Base64.

**Examples**:
```yaml
- table: api_tokens
  data:
    token: "{{ uuid|base64 }}"                    # OWU1YmU5NmMtMTkzNC00ZTgyLWJlYzktNDZmMzZiOWFhOTRj
    encoded_data: "{{ scope|base64 }}"             # cHJvZF8yMDI0
```

#### `md5`

Generates an MD5 hash of the string.

**Examples**:
```yaml
- table: cache
  data:
    cache_key: "{{ scope|md5 }}"                  # 5f4dcc3b5aa765d61d8327deb882cf99
    file_hash: "{{ uuid|md5 }}"                   # a3f2b8c1d4e5f6a7b8c9d0e1f2a3b4c5
```

**Note**: MD5 is not recommended for security, use for cache/checksum only.

#### `sha1`

Generates a SHA-1 hash of the string.

**Examples**:
```yaml
- table: files
  data:
    checksum: "{{ uuid|sha1 }}"                   # 356a192b7913b04c54574d18c28d46e6395428ab
    signature: "{{ scope|sha1|truncate(16) }}"    # 356a192b7913b04c
```

**Note**: SHA-1 is not recommended for security, use for cache/checksum only.

#### `htmlencode`

Encodes HTML special characters (XSS protection).

**Examples**:
```yaml
vars:
  user_input: "<script>alert('XSS')</script>"

load:
  - table: posts
    data:
      content: "{{ $user_input|htmlencode }}"     # &lt;script&gt;alert(&#039;XSS&#039;)&lt;/script&gt;
      title: "User's article|htmlencode"          # User&#039;s article
```

**Usage**: Protects against XSS injections when storing user data.

#### `urlencode`

Encodes a string for use in URLs.

**Examples**:
```yaml
- table: api_logs
  data:
    url: "https://api.example.com?query={{ scope|urlencode }}"  # ?query=prod%5F2024
    slug: "{{ scope|replace('_', ' ')|urlencode }}"              # prod+2024
```

### Pipe combination

Pipes can be chained for complex transformations:

**Examples**:
```yaml
vars:
  original_text: "  Hello World  "

load:
  - table: messages
    data:
      # Clean + format
      clean_text: "{{ $original_text|trim|lowercase|capitalize }}"
      # Result: "Hello world"
      
      # Short and clean UUID
      short_id: "{{ uuid|truncate(24)|replace('-', '') }}"
      # Result: "95437ca45edb4490a78a" (24 chars, no dashes)
      
      # Short hash
      token: "{{ hash(scope)|truncate(16)|uppercase }}"
      # Result: "$2Y$10$0DUDTS2PA" (16 chars uppercase)
      
      # Formatted scope
      environment: "{{ scope|uppercase|replace('_', '-')|trim }}"
      # Result: "PROD-2024"
```

### Pipes with all placeholders

Pipes work with **all placeholder types**:

```yaml
vars:
  user_name: "  Alice  "
  contract: "0x{{ uuid|truncate(24)|replace('-', '') }}"

load:
  - table: demo
    data:
      # With scope
      scope_upper: "{{ scope|uppercase }}"
      scope_formatted: "{{ scope|uppercase|replace('_', '-') }}"
      
      # With variables
      clean_name: "{{ $user_name|trim|capitalize }}"
      contract_short: "{{ $contract|truncate(32) }}"
      
      # With hash
      password_short: "{{ hash('password')|truncate(24) }}"
      password_upper: "{{ hash(scope)|uppercase }}"
      
      # With UUID
      uuid_short: "{{ uuid|truncate(8) }}"
      uuid_clean: "{{ uuid|replace('-', '') }}"
      uuid_compact: "{{ uuid|truncate(24)|replace('-', '') }}"
      
      # With date
      date_upper: "{{ now|uppercase }}"
      
      # With math
      calc_short: "{{ math(100*2)|truncate(3) }}"
      
      # With env
      env_lower: "{{ env('APP_ENV')|lowercase }}"
```

### Use cases

**Compact identifiers**:
```yaml
- table: chat_messages
  data:
    id: "msg_{{ uuid|truncate(16)|replace('-', '') }}"  # msg_a3f2b8c195437ca4
```

**Ethereum-like addresses**:
```yaml
vars:
  contract: "0x{{ uuid|truncate(32)|replace('-', '')|uppercase }}"
  # 0xA3F2B8C195437CA45EDB4490A78A1D2E
```

**Product codes**:
```yaml
- table: products
  data:
    sku: "PROD-{{ scope|uppercase }}-{{ uuid|truncate(8)|uppercase }}"
    # PROD-TEST-A3F2B8C1
```

**User data cleanup**:
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

**Short tokens**:
```yaml
- table: api_keys
  data:
    key: "{{ hash(scope)|truncate(32)|uppercase|replace('$', '')|replace('/', '') }}"
    # 2Y10DUDTS2PACJOUZUWA1234ABCD5678
```

### Limitations

❌ Pipes **cannot**:
- Create custom pipes in YAML (use hybrid PHP)
- Use arbitrary PHP functions
- Modify global variables

✅ **Available pipes**:
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

**Tip**: For more complex transformations, use a **hybrid scenario** (YAML + PHP).

---

## Dynamic lookup

Lookups allow retrieving IDs from database to manage foreign keys.

### Syntax

```yaml
column_name:
  table: table_name
  where:
    search_column: "value"
  return: return_column
```

### Simple example

```yaml
- table: posts
  data:
    title: "My article"
    author_id:
      table: users
      where:
        username: "john_doe"
      return: id
```

**Execution**:
1. Search in `users` where `username = 'john_doe'`
2. Retrieve `id` column
3. Insert into `posts.author_id`

### Lookup with placeholder

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

**With `--scope=test`**:
- Searches `users.username = 'admin_test'`
- Searches `acl.slug = 'root'`
- Creates link in `users_acl`

### Lookup for hierarchical relationships

```yaml
# Root ACL (no parent)
- table: acl
  data:
    slug: "root_{{ scope }}"
    description: "Root access"

# Admin ACL (parent: root)
- table: acl
  data:
    slug: "admin_{{ scope }}"
    description: "Administrator"
    parent_id:
      table: acl
      where:
        slug: "root_{{ scope }}"
      return: id

# Manager ACL (parent: admin)
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

**Result**: Hierarchy `root → admin → manager` with dynamically resolved IDs

---

## Data types

The `types` section allows converting values to specific PHP objects with automatic validation and error handling.

### Available types

#### Date and Time Types

| Type | Description | Accepted format | Example |
|------|-------------|----------------|---------|
| `datetime_immutable` | `DateTimeImmutable` | String, timestamp (int), DateTime | `created_at: datetime_immutable` |
| `datetime` | `DateTime` | String, timestamp (int), DateTimeImmutable | `updated_at: datetime` |
| `datetimetz` | `DateTime` with timezone | String, timestamp (int) | `sent_at: datetimetz` |
| `datetimetz_immutable` | `DateTimeImmutable` with timezone | String, timestamp (int) | `received_at: datetimetz_immutable` |
| `date` | `DateTime` (date only) | String, timestamp (int) | `birth_date: date` |
| `date_immutable` | `DateTimeImmutable` (date only) | String, timestamp (int) | `start_date: date_immutable` |
| `time` | `DateTime` (time only) | String | `opening_time: time` |
| `time_immutable` | `DateTimeImmutable` (time only) | String | `closing_time: time_immutable` |

**Automatic conversions**:
- **String**: Parse with constructor (`new DateTime($value)`)
- **Timestamp (int)**: Convert with `setTimestamp()`
- **DateTime ↔ DateTimeImmutable**: Automatic conversion if needed
- **Invalid value**: Returns `null` (no exception)

```yaml
- table: events
  data:
    name: "Conference {{ scope }}"
    starts_at: "2024-06-15 14:00:00"      # String → DateTimeImmutable
    ends_at: 1718460000                    # Timestamp → DateTimeImmutable
    created_at: "{{ now }}"                # Placeholder → DateTimeImmutable
  types:
    starts_at: datetime_immutable
    ends_at: datetime_immutable
    created_at: datetime_immutable
```

#### Numeric Types

| Type | Description | Range | Conversion |
|------|-------------|-------|------------|
| `int` / `integer` | Standard integer | -2,147,483,648 to 2,147,483,647 | Auto clamping |
| `smallint` | Small integer | -32,768 to 32,767 | Auto clamping |
| `bigint` | Large integer | PHP_INT_MIN to PHP_INT_MAX | Auto clamping |
| `float` | Floating point | ±1.7e308 | Direct cast |
| `decimal` | Precise decimal | No limit | Kept as string |

**Automatic validation**:
- **Integers**: Out-of-range values automatically clamped
- **Non-numeric**: Returns `0` for int, `0.0` for float, `'0'` for decimal

```yaml
- table: products
  data:
    name: "Product {{ scope }}"
    quantity: "50000"            # > 32767 → clamped to 32767
    stock_level: "100"
    price: "99.99"
    precision_price: "99.999999"
  types:
    quantity: smallint           # Clamped to 32767
    stock_level: int
    price: float                 # 99.99
    precision_price: decimal     # "99.999999" (preserves precision)
```

**Clamping examples**:
```yaml
# smallint (-32768 to 32767)
quantity: "50000"  → 32767
quantity: "-50000" → -32768

# int (-2147483648 to 2147483647)  
user_id: "3000000000"  → 2147483647
user_id: "-3000000000" → -2147483648

# bigint (PHP_INT_MIN to PHP_INT_MAX)
big_number: "9999999999999999999" → PHP_INT_MAX
```

#### Boolean Types

| Type | Description | Accepted values | Example |
|------|-------------|------------------|---------|
| `bool` / `boolean` | Boolean | `true`, `false`, `1`, `0`, `"1"`, `"0"` | `is_active: bool` |

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

#### String Types

| Type | Description | Example |
|------|-------------|---------|
| `string` | String | `name: string` |
| `text` | Long text | `description: text` |
| `guid` / `uuid` | Unique identifier | `id: uuid` |

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

#### Complex Types

| Type | Description | Accepted format | Example |
|------|-------------|----------------|---------|
| `json` | PHP array (decoded) | JSON string or Array | `metadata: json` |
| `array` | Serialized array | Serialized string or Array | `options: array` |
| `simple_array` | Simple array (CSV) | String `"a,b,c"` or Array | `tags: simple_array` |

**JSON validation**:
- **Valid JSON string**: Decoded to array
- **PHP Array**: Returned as is
- **Invalid JSON**: Returns `[]` (no exception)

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

#### Binary Types

| Type | Description | Example |
|------|-------------|---------|
| `binary` | Binary data | `file_data: binary` |
| `blob` | SQL BLOB | `image_data: blob` |

```yaml
- table: files
  data:
    name: "document.pdf"
    data: "{{ file_content }}"
  types:
    data: binary
```

### Complete example

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
    tax_amount: decimal           # "19.99" (precision preserved)
    quantity: smallint            # 5
    is_paid: bool                 # true
    ordered_at: datetime_immutable
    shipped_at: datetime_immutable
    metadata: json                # PHP Array
    tags: simple_array            # ["express", "priority", "fragile"]
```

### Automatic timestamps

If your columns have `DEFAULT CURRENT_TIMESTAMP`, **no need to specify them**:

```yaml
# ✅ Good - DB handles timestamps
- table: users
  data:
    username: "john"
    email: "john@example.com"
  # No need for created_at/updated_at if DEFAULT CURRENT_TIMESTAMP

# ❌ Bad - Redundant
- table: users
  data:
    username: "john"
    email: "john@example.com"
    created_at: "{{ now }}"  # Unnecessary if DEFAULT exists
```

---

## Custom pivot

By default, tracking uses the table's `id` column. Custom pivot allows tracking by another column.

### Use case

**Table with VARCHAR ID**: Chat messages have VARCHAR `id` but should be tracked by `user_id` (INT).

### Syntax

```yaml
- table: table_name
  data:
    # ... data ...
  pivot:
    id: value_or_lookup
    column: column_name
```

### Example with direct value

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

**Tracking**: System tracks `chat_messages` with `user_id = 42` instead of `id = 'msg_custom_uuid'`

### Example with lookup

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
    message: "Next meeting tomorrow at 2pm."
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

**How it works**:
1. Inserts message with UUID as `id`
2. Resolves `user_id` via lookup
3. Tracks with `user_id` instead of UUID
4. On purge, deletes all messages of this user

### Why use pivot?

| Without pivot | With pivot |
|------------|-----------|
| Track by VARCHAR `id` | Track by INT `user_id` |
| Purge message by message | Purge all user messages |
| Complex tracking with UUIDs | Simplified tracking by FK relationship |

---

## Custom purge

By default, purge uses **automatic tracker**. You can add custom purge as **overlay**.

### 🔍 Understanding volatile and orphan data

**Important context**: When creating a dataset during user testing, you may create data that is **not tracked** by Prism.

#### Terminology

- **Volatile data**: Data created during user tests that is **NOT tracked** by Prism
  - Example: A user created by scenario who manually adds posts via UI
  - Prism has **no direct control** over this data

- **Orphan data**: Volatile data that **remains after purge** of a scenario
  - If you purge only via automatic tracker, volatile data is not deleted
  - This "orphan" data pollutes the database and can cause bugs in following tests

#### Role of custom purge

⚠️ **Custom purge ensures volatile data doesn't become orphaned**

Without custom purge, only Prism-tracked data is deleted. Data created manually (via UI, scripts, or other processes) remains in database.

---

### Automatic purge (default)

```yaml
load:
  - table: users
    data:
      username: "john_{{ scope }}"
```

**Purge**: Automatically deletes all tracked records of the scope.

### Custom purge (overlay)

Custom purge executes **before** automatic purge. Useful for cleaning data created outside tracking.

```yaml
load:
  - table: users
    data:
      username: "john_{{ scope }}"

purge:
  # Custom purge first
  - table: posts
    where:
      author: "john_{{ scope }}"
  
  # Then automatic purge at end (implicit)
```

**Execution order**:
1. Custom purge: `DELETE FROM posts WHERE author = 'john_test'`
2. Automatic purge (pivot): Deletes all tracked records

### Use case: Manually created data

**Scenario**: You create a user via scenario, then **manually** (via UI) the user creates posts that are **not tracked**.

```yaml
load:
  - table: users
    data:
      username: "author_{{ scope }}"
      email: "author@{{ scope }}.com"
      password: "{{ hash('secret') }}"

purge:
  # Purge manually created posts (not tracked)
  - table: posts
    where:
      author_username: "author_{{ scope }}"
  
  # Purge associated comments
  - table: comments
    where:
      author_username: "author_{{ scope }}"
  
  # Automatic purge will delete user at end
```

### Fine control with `purge_pivot`

You can trigger automatic purge **at a specific moment** in sequence with `purge_pivot: true`.

**Syntax**:
```yaml
purge:
  - table: table1
    where:
      col: "val"
  
  - purge_pivot: true  # Triggers automatic purge HERE
  
  - table: table2
    where:
      col: "val"
```

**Complete example**:
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
  # 1. Purge manually created logs
  - table: activity_logs
    where:
      username: "user_{{ scope }}"
  
  # 2. Purge manually uploaded files
  - table: uploads
    where:
      username: "user_{{ scope }}"
  
  # 3. Trigger automatic purge (deletes users and projects)
  - purge_pivot: true
  
  # 4. Purge related caches
  - table: cache_entries
    where:
      key_pattern: "user_{{ scope }}_%"
```

**Execution order**:
1. `DELETE FROM activity_logs WHERE username = 'user_test'`
2. `DELETE FROM uploads WHERE username = 'user_test'`
3. **Automatic purge**: Deletes `projects` and `users` via tracker
4. `DELETE FROM cache_entries WHERE key_pattern = 'user_test_%'`

### Why control order?

**Important note**: Automatic purge (pivot) **automatically reverses insertion order** to respect FK constraints. If you insert `users` then `posts`, purge deletes `posts` then `users`.

| Case | Need | Solution |
|-----|--------|----------|
| **FK without CASCADE** | Delete children before parents | Pivot purge does it automatically |
| **ON DELETE CASCADE constraints** | DB deletes children | Pivot purge tries anyway (catches errors) |
| **Untracked data** | Delete before tracked data | Custom purge **before** pivot (default) |
| **Caches/Logs without FK** | Clean after main deletion | Custom purge **after** pivot (with `purge_pivot: true`) |

**Natural purge order**:
```yaml
purge:
  - table: manual_data_1     # 1. Custom purge
  - table: manual_data_2     # 2. Custom purge
  # 3. Automatic pivot purge (reversed order: last inserted → first deleted)
```

**Controlled order with `purge_pivot`**:
```yaml
purge:
  - table: logs              # 1. Custom purge before
  - purge_pivot: true        # 2. Pivot purge (reversed order)
  - table: cache             # 3. Custom purge after
```

✅ **Best practice**: In 99% of cases, default order (custom purge → pivot purge at end) is enough because pivot already reverses insertion order.

---

## Complete examples

### Example 1: Simple users

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

**Command**:
```bash
php bin/console app:prism:load simple_users --scope=qa2024
```

### Example 2: Relationships with lookup

```yaml
# prisms/blog_posts.yaml
load:
  # Create author
  - table: users
    data:
      username: "author_{{ scope }}"
      email: "author_{{ scope }}@blog.com"
      password: "{{ hash('secret') }}"

  # Create their articles
  - table: posts
    data:
      title: "Article 1 - {{ scope }}"
      content: "Article content"
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
      content: "Other content"
      author_id:
        table: users
        where:
          username: "author_{{ scope }}"
        return: id
      published_at: "{{ now }}"
    types:
      published_at: datetime_immutable
```

### Example 3: Complete ACL hierarchy

```yaml
# prisms/acl_hierarchy.yaml
load:
  # Users
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

  # ACL hierarchy
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

  # Assignments
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

### Example 4: Multiple databases

```yaml
# prisms/multi_database.yaml
load:
  # Users in main database
  - table: users
    data:
      username: "admin_{{ scope }}"
      email: "admin@{{ scope }}.com"
      password: "{{ hash('admin123') }}"

  # Logs in secondary database
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
  # Purge logs from secondary database
  - table: audit_logs
    db: hexagonal_secondary
    where:
      action: "user_created"
```

### Example 5: Messages with custom pivot

```yaml
# prisms/chat_with_pivot.yaml
load:
  # Users
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

  # Messages (tracked by user_id)
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
      message: "Hi Bob!"
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
      message: "Hi Alice!"
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

### ❌ What YAML cannot do

| Limitation | Reason | Solution |
|------------|--------|----------|
| **Conditional logic** | No `if/else` | Hybrid scenario (YAML + PHP) |
| **Loops** | No `for/foreach` | Hybrid scenario or pure PHP |
| **Complex calculations** | No expressions | Hybrid scenario |
| **Reusable variables** | No variable system | Repeat lookups |
| **Relations on just created ID** | Lookup requires existing data | Hybrid scenario with ID cache |

### Example limitation: Complex calculations

**✅ Now possible in YAML with `{{ math() }}`**:
```yaml
vars:
  price: "10.50"
  quantity: "3"

load:
  - table: order_items
    data:
      price: "{{ $price }}"
      quantity: "{{ $quantity }}"
      total: "{{ math($price*$quantity) }}"  # Calculates 10.50 * 3 = 31.5
```

**❌ Still impossible: Conditional logic**
```yaml
# Cannot do: if quantity > 10 then apply discount
- table: order_items
  data:
    price: 10.50
    quantity: 15
    discount: ??? # Impossible to do if/else
```

**✅ Solution: Hybrid scenario**
```php
class OrderPrism extends YamlPrism
{
    public function load(Scope $scope): void
    {
        parent::load($scope); // Load YAML
        
        // Then conditional logic in PHP
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

### When to use YAML vs PHP vs Hybrid?

| Type | Use case |
|------|----------|
| **Pure YAML** | Simple data, lookups, math calculations, fast prototyping |
| **Pure PHP** | Complex business logic, conditions, loops, algorithms |
| **Hybrid** | YAML for structure + PHP for advanced conditional logic |

---

## Best Practices

### ✅ Do

1. **Use explicit scopes**: `--scope=test_feature_x` rather than `--scope=test`
2. **Suffix data with `{{ scope }}`**: Avoids conflicts between scopes
3. **Lookups for all FKs**: Never hardcode IDs
4. **Pivot for VARCHAR ID tables**: Track by main FK
5. **Comment your sections**: Make YAML readable

### ❌ Don't

1. **Hardcoded IDs**: `user_id: 123` → Use lookup
2. **Data without scope**: `username: "admin"` → Risk of collision
3. **Manual timestamps if DEFAULT exists**: Let DB handle it
4. **Unnecessary manual purge**: Automatic tracker often enough
5. **YAML for complex logic**: Switch to hybrid scenario

---

## Quick Reference

### Commands

```bash
# Load
php bin/console app:prism:load name --scope=value

# Purge
php bin/console app:prism:purge name --scope=value

# List
php bin/console app:prism:list
```

### Minimal structure

```yaml
load:
  - table: my_table
    data:
      column: "value"
```

### With imports

```yaml
import:
  - base_users
  - base_acl
  
load:
  - table: my_table
    data:
      column: "value"
```

### Placeholders

- `{{ scope }}` - Scope value
- `{{ hash('pwd') }}` - Bcrypt hash
- `{{ env('VAR') }}` - Environment variable
- `{{ now }}` - Current timestamp
- `{{ date('modifier') }}` - Relative date (+7 days, -1 week, etc.)
- `{{ uuid }}` - UUID v4
- `{{ $varname }}` - Custom variable
- `{{ math(expression) }}` - Math calculation

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
  id: value_or_lookup
  column: column_name
```

### Types

- `datetime_immutable`, `datetime`, `int`, `float`, `bool`, `string`

---

## 🔗 Resources

- **[Main Documentation](PRISM.md)** - System overview
- **[PHP Guide](PRISM_PHP.md)** - Create PHP scenarios
- **[Hybrid Guide](PRISM_HYBRID.md)** - Combine YAML + PHP
- **[Faker Guide](PRISM_FAKER.md)** - Random data generation
