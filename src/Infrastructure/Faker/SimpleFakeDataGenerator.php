<?php

declare(strict_types=1);

namespace Prism\Infrastructure\Faker;

use Prism\Domain\Contract\FakeDataGeneratorInterface;

/**
 * Générateur simple de données aléatoires
 *
 * Implémentation légère sans dépendance externe.
 * Génère des données aléatoires cohérentes pour les tests.
 */
final class SimpleFakeDataGenerator implements FakeDataGeneratorInterface
{
    private const FIRSTNAMES = [
        'Rick', 'Morty', 'Walter', 'Jesse', 'Neo', 'Trinity', 'Luke', 'Leia',
        'Mario', 'Luigi', 'Link', 'Zelda', 'Master', 'Cortana', 'Gordon', 'Alyx',
        'Lara', 'Nathan', 'Ellie', 'Joel', 'Geralt', 'Ciri', 'Solid', 'Raiden',
        'Cloud', 'Tifa', 'Shepard', 'Garrus', 'Kratos', 'Atreus', 'Arthur', 'John',
        'Johnny', 'Panam', 'Judy', 'Viktor', 'Jackie', 'Rogue', 'Takemura', 'River',
        'Preston', 'Piper', 'Nick', 'Hancock', 'Curie', 'Dogmeat', 'Courier', 'Vault',
        'Fox', 'Dana', 'Alex', 'Monica', 'Alvin', 'Richard', 'Melvin',
        'Linus', 'Dennis', 'Ken', 'Eric', 'Brian', 'Elliot', 'Dade',
        'Kate', 'Emmanuel', 'Kevin', 'Adrian', 'Eugene', 'Zero', 'Crash',
        'Isaac', 'Dave', 'HAL', 'Cooper', 'Murph', 'TARS', 'CASE', 'David',
        'Galileo', 'Albert', 'Stephen', 'Nikola', 'Marie', 'Charles', 'Ada'
    ];

    private const LASTNAMES = [
        'Sanchez', 'Smith', 'White', 'Pinkman', 'Anderson', 'Skywalker', 'Freeman',
        'Vance', 'Croft', 'Drake', 'Snake', 'Strife', 'Lockhart', 'Chief',
        'Morgan', 'Marston', 'Reeves', 'Connor', 'Shepard', 'Vakarian',
        'McLane', 'Ripley', 'Solo', 'Fett', 'Wick', 'Banner', 'Stark', 'Wayne',
        'Silverhand', 'Palmer', 'Alvarez', 'Vektor', 'Welles', 'Parker',
        'Garvey', 'Wright', 'Valentine', 'McDonough', 'Wanderer', 'Survivor',
        'Mulder', 'Scully', 'Skinner', 'Krycek', 'Doggett', 'Reyes', 'Langly', 'Frohike',
        'Torvalds', 'Stallman', 'Ritchie', 'Thompson', 'Raymond', 'Kernighan', 'Alderson',
        'Murphy', 'Libby', 'Goldstein', 'Mitnick', 'Lamo', 'Spafford', 'Cool', 'Override',
        'Asimov', 'Bowman', 'Poole', 'Cooper', 'Brand', 'Lhoumaud',
        'Newton', 'Galilei', 'Einstein', 'Hawking', 'Tesla', 'Curie', 'Darwin', 'Lovelace'
    ];

    private const COMPANIES = [
        'Umbrella Corp', 'Aperture Science', 'Wayne Enterprises', 'Stark Industries',
        'Cyberdyne Systems', 'Tyrell Corporation', 'Weyland-Yutani', 'Oscorp',
        'Massive Dynamic', 'Initech', 'Soylent Corp', 'Omni Consumer Products',
        'Black Mesa', 'Vault-Tec', 'Abstergo Industries', 'Shinra Electric',
        'Arasaka Corporation', 'Militech', 'Trauma Team', 'Night Corp',
        'Nuka-Cola Company', 'RobCo Industries', 'West-Tek', 'Poseidon Energy',
        'The Syndicate', 'Lone Gunmen', 'Roush Technologies', 'Morley Cigarettes',
        'Red Hat', 'Canonical', 'SUSE Linux', 'Free Software Foundation',
        'E Corp', 'Allsafe Cybersecurity', 'fsociety', 'Dark Army',
        'Foundation', 'U.S. Robots', 'Positronic', 'Discovery One', 'Endurance', 'Solara'
    ];

    private const TLD = [
        'com', 'net', 'org', 'io', 'tech', 'app', 'dev', 'fr',
        'info', 'biz', 'co', 'us', 'uk', 'ca', 'de', 'jp', 'au',
        'ru', 'cn', 'in','es', 'it', 'nl', 'se', 'no', 'fi', 'br',
        'za', 'mx', 'ar', 'ch', 'be', 'dk', 'pl', 'gr', 'tr', 'kr', 'sg'
    ];

    private const COLORS = [
        'aliceblue', 'antiquewhite', 'aqua', 'aquamarine', 'azure', 'beige', 'bisque', 'black',
        'blanchedalmond', 'blue', 'blueviolet', 'brown', 'burlywood', 'cadetblue', 'chartreuse',
        'chocolate', 'coral', 'cornflowerblue', 'cornsilk', 'crimson', 'cyan', 'darkblue',
        'darkcyan', 'darkgoldenrod', 'darkgray', 'darkgrey', 'darkgreen', 'darkkhaki',
        'darkmagenta', 'darkolivegreen', 'darkorange', 'darkorchid', 'darkred', 'darksalmon',
        'darkseagreen', 'darkslateblue', 'darkslategray', 'darkslategrey', 'darkturquoise',
        'darkviolet', 'deeppink', 'deepskyblue', 'dimgray', 'dimgrey', 'dodgerblue', 'firebrick',
        'floralwhite', 'forestgreen', 'fuchsia', 'gainsboro', 'ghostwhite', 'gold', 'goldenrod',
        'gray', 'grey', 'green', 'greenyellow', 'honeydew', 'hotpink', 'indianred', 'indigo',
        'ivory', 'khaki', 'lavender', 'lavenderblush', 'lawngreen', 'lemonchiffon', 'lightblue',
        'lightcoral', 'lightcyan', 'lightgoldenrodyellow', 'lightgray', 'lightgrey', 'lightgreen',
        'lightpink', 'lightsalmon', 'lightseagreen', 'lightskyblue', 'lightslategray',
        'lightslategrey', 'lightsteelblue', 'lightyellow', 'lime', 'limegreen', 'linen', 'magenta',
        'maroon', 'mediumaquamarine', 'mediumblue', 'mediumorchid', 'mediumpurple',
        'mediumseagreen', 'mediumslateblue', 'mediumspringgreen', 'mediumturquoise',
        'mediumvioletred', 'midnightblue', 'mintcream', 'mistyrose', 'moccasin', 'navajowhite',
        'navy', 'oldlace', 'olive', 'olivedrab', 'orange', 'orangered', 'orchid', 'palegoldenrod',
        'palegreen', 'paleturquoise', 'palevioletred', 'papayawhip', 'peachpuff', 'peru', 'pink',
        'plum', 'powderblue', 'purple', 'rebeccapurple', 'red', 'rosybrown', 'royalblue',
        'saddlebrown', 'salmon', 'sandybrown', 'seagreen', 'seashell', 'sienna', 'silver',
        'skyblue', 'slateblue', 'slategray', 'slategrey', 'snow', 'springgreen', 'steelblue',
        'tan', 'teal', 'thistle', 'tomato', 'turquoise', 'violet', 'wheat', 'white', 'whitesmoke',
        'yellow', 'yellowgreen'
    ];

    private const GENDERS = ['male', 'female', 'other', 'non-binary'];

    private const COUNTRIES = [
        'France', 'United States', 'Germany', 'United Kingdom', 'Spain', 'Italy', 'Canada',
        'Japan', 'China', 'Australia', 'Brazil', 'Mexico', 'India', 'Russia', 'South Korea',
        'Netherlands', 'Belgium', 'Switzerland', 'Sweden', 'Norway', 'Denmark', 'Finland',
        'Poland', 'Austria', 'Portugal', 'Greece', 'Czech Republic', 'Ireland', 'New Zealand',
        'Argentina', 'Chile', 'Colombia', 'Peru', 'Singapore', 'Thailand', 'Vietnam',
        'Philippines', 'Indonesia', 'Malaysia', 'Turkey', 'Egypt', 'South Africa', 'Morocco'
    ];

    private const STREET_TYPES = [
        'FR' => ['Rue', 'Avenue', 'Boulevard', 'Place', 'Allée', 'Impasse', 'Chemin', 'Route', 'Cours', 'Quai'],
        'US' => ['Street', 'Avenue', 'Boulevard', 'Road', 'Drive', 'Lane', 'Way', 'Court', 'Place', 'Parkway'],
        'GB' => ['Street', 'Road', 'Avenue', 'Lane', 'Drive', 'Close', 'Way', 'Gardens', 'Terrace', 'Grove'],
        'DE' => ['Straße', 'Weg', 'Platz', 'Allee', 'Gasse', 'Ring', 'Damm', 'Ufer'],
        'ES' => ['Calle', 'Avenida', 'Plaza', 'Paseo', 'Camino', 'Ronda', 'Travesía'],
        'IT' => ['Via', 'Viale', 'Piazza', 'Corso', 'Vicolo', 'Largo', 'Strada'],
    ];

    private const STREET_NAMES = [
        'FR' => ['Victor Hugo', 'de la République', 'Jean Jaurès', 'Pasteur', 'Voltaire', 'Gambetta',
                 'de la Liberté', 'du Général de Gaulle', 'Foch', 'Clemenceau', 'des Lilas', 'du Moulin',
                 'de la Paix', 'Nationale', 'du Commerce', 'des Martyrs', 'Carnot', 'Thiers'],
        'US' => ['Main', 'Washington', 'Oak', 'Maple', 'Park', 'Pine', 'Elm', 'Lincoln', 'Madison',
                 'Jackson', 'Jefferson', 'Franklin', 'Cedar', 'Lake', 'Hill', 'Church', 'Spring'],
        'GB' => ['High', 'Station', 'Church', 'Victoria', 'Albert', 'King', 'Queen', 'Park', 'London',
                 'Mill', 'Green', 'York', 'Manor', 'Windsor', 'Oxford', 'Cambridge'],
        'DE' => ['Haupt', 'Bahnhof', 'Kirch', 'Schiller', 'Goethe', 'Bismarck', 'Kaiser', 'König',
                 'Berliner', 'Hamburger', 'Münchner', 'Park', 'Wald', 'Berg'],
        'ES' => ['Mayor', 'Real', 'del Sol', 'de la Paz', 'San Juan', 'Santa María', 'del Carmen',
                 'de Colón', 'Cervantes', 'Goya', 'Velázquez', 'del Prado'],
        'IT' => ['Roma', 'Garibaldi', 'Mazzini', 'Dante', 'Verdi', 'Cavour', 'Vittorio Emanuele',
                 'del Duomo', 'San Marco', 'della Libertà', 'Nazionale'],
    ];

    private const CITIES = [
        'FR' => ['Paris', 'Lyon', 'Marseille', 'Toulouse', 'Nice', 'Nantes', 'Strasbourg', 'Montpellier',
                 'Bordeaux', 'Lille', 'Rennes', 'Reims', 'Le Havre', 'Saint-Étienne', 'Toulon', 'Grenoble',
                 'Dijon', 'Angers', 'Nîmes', 'Villeurbanne', 'Clermont-Ferrand', 'Le Mans', 'Aix-en-Provence',
                 'Brest', 'Tours', 'Amiens', 'Limoges', 'Annecy', 'Perpignan', 'Boulogne-Billancourt'],
        'US' => ['New York', 'Los Angeles', 'Chicago', 'Houston', 'Phoenix', 'Philadelphia', 'San Antonio',
                 'San Diego', 'Dallas', 'San Jose', 'Austin', 'Jacksonville', 'Fort Worth', 'Columbus',
                 'Charlotte', 'San Francisco', 'Indianapolis', 'Seattle', 'Denver', 'Boston', 'Nashville',
                 'Detroit', 'Portland', 'Las Vegas', 'Memphis', 'Louisville', 'Baltimore', 'Milwaukee'],
        'GB' => ['London', 'Birmingham', 'Manchester', 'Glasgow', 'Liverpool', 'Leeds', 'Sheffield',
                 'Edinburgh', 'Bristol', 'Newcastle', 'Cardiff', 'Belfast', 'Leicester', 'Nottingham',
                 'Bradford', 'Southampton', 'Brighton', 'Oxford', 'Cambridge', 'York', 'Bath'],
        'DE' => ['Berlin', 'Hamburg', 'München', 'Köln', 'Frankfurt', 'Stuttgart', 'Düsseldorf', 'Dortmund',
                 'Essen', 'Leipzig', 'Bremen', 'Dresden', 'Hannover', 'Nürnberg', 'Duisburg', 'Bochum',
                 'Wuppertal', 'Bonn', 'Bielefeld', 'Mannheim', 'Karlsruhe', 'Wiesbaden', 'Münster'],
        'ES' => ['Madrid', 'Barcelona', 'Valencia', 'Sevilla', 'Zaragoza', 'Málaga', 'Murcia', 'Palma',
                 'Las Palmas', 'Bilbao', 'Alicante', 'Córdoba', 'Valladolid', 'Vigo', 'Gijón', 'Granada',
                 'Vitoria', 'Elche', 'Oviedo', 'Santa Cruz', 'Badalona', 'Cartagena', 'Terrassa'],
        'IT' => ['Roma', 'Milano', 'Napoli', 'Torino', 'Palermo', 'Genova', 'Bologna', 'Firenze',
                 'Bari', 'Catania', 'Venezia', 'Verona', 'Messina', 'Padova', 'Trieste', 'Brescia',
                 'Parma', 'Taranto', 'Prato', 'Modena', 'Reggio Calabria', 'Livorno', 'Cagliari'],
    ];

    private const ISO_CODES = [
        'FR' => 'FRA', 'US' => 'USA', 'DE' => 'DEU', 'GB' => 'GBR', 'ES' => 'ESP',
        'IT' => 'ITA', 'CA' => 'CAN', 'JP' => 'JPN', 'CN' => 'CHN', 'AU' => 'AUS',
        'BR' => 'BRA', 'MX' => 'MEX', 'IN' => 'IND', 'RU' => 'RUS', 'KR' => 'KOR',
        'NL' => 'NLD', 'BE' => 'BEL', 'CH' => 'CHE', 'SE' => 'SWE', 'NO' => 'NOR',
        'DK' => 'DNK', 'FI' => 'FIN', 'PL' => 'POL', 'AT' => 'AUT', 'PT' => 'PRT',
        'GR' => 'GRC', 'CZ' => 'CZE', 'IE' => 'IRL', 'NZ' => 'NZL', 'AR' => 'ARG',
        'CL' => 'CHL', 'CO' => 'COL', 'PE' => 'PER', 'SG' => 'SGP', 'TH' => 'THA',
        'VN' => 'VNM', 'PH' => 'PHL', 'ID' => 'IDN', 'MY' => 'MYS', 'TR' => 'TUR',
        'EG' => 'EGY', 'ZA' => 'ZAF', 'MA' => 'MAR'
    ];

    private const COUNTRY_NAMES = [
        'FR' => 'France', 'US' => 'United States', 'DE' => 'Germany', 'GB' => 'United Kingdom',
        'ES' => 'Spain', 'IT' => 'Italy', 'CA' => 'Canada', 'JP' => 'Japan',
        'CN' => 'China', 'AU' => 'Australia', 'BR' => 'Brazil', 'MX' => 'Mexico',
        'IN' => 'India', 'RU' => 'Russia', 'KR' => 'South Korea', 'NL' => 'Netherlands',
        'BE' => 'Belgium', 'CH' => 'Switzerland', 'SE' => 'Sweden', 'NO' => 'Norway',
        'DK' => 'Denmark', 'FI' => 'Finland', 'PL' => 'Poland', 'AT' => 'Austria',
        'PT' => 'Portugal', 'GR' => 'Greece', 'CZ' => 'Czech Republic', 'IE' => 'Ireland',
        'NZ' => 'New Zealand', 'AR' => 'Argentina', 'CL' => 'Chile', 'CO' => 'Colombia',
        'PE' => 'Peru', 'SG' => 'Singapore', 'TH' => 'Thailand', 'VN' => 'Vietnam',
        'PH' => 'Philippines', 'ID' => 'Indonesia', 'MY' => 'Malaysia', 'TR' => 'Turkey',
        'EG' => 'Egypt', 'ZA' => 'South Africa', 'MA' => 'Morocco'
    ];

    private const CHARSETS = [
        'UTF-8', 'UTF-16', 'UTF-16BE', 'UTF-16LE', 'UTF-32', 'UTF-32BE', 'UTF-32LE',
        'ISO-8859-1', 'ISO-8859-2', 'ISO-8859-3', 'ISO-8859-4', 'ISO-8859-5',
        'ISO-8859-6', 'ISO-8859-7', 'ISO-8859-8', 'ISO-8859-9', 'ISO-8859-10',
        'ISO-8859-13', 'ISO-8859-14', 'ISO-8859-15', 'ISO-8859-16',
        'Windows-1250', 'Windows-1251', 'Windows-1252', 'Windows-1253', 'Windows-1254',
        'Windows-1255', 'Windows-1256', 'Windows-1257', 'Windows-1258',
        'ASCII', 'US-ASCII', 'ANSI', 'CP850', 'CP1252',
        'KOI8-R', 'KOI8-U', 'Big5', 'GB2312', 'GBK', 'Shift_JIS', 'EUC-JP', 'EUC-KR'
    ];

    private const USER_AGENTS = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Safari/605.1.15',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (iPad; CPU OS 17_2 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.2 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (Linux; Android 14) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.6099.43 Mobile Safari/537.36',
        'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:121.0) Gecko/20100101 Firefox/121.0',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Edge/120.0.0.0 Safari/537.36'
    ];

    private const DEVICES = [
        'iPhone', 'iPad', 'MacBook', 'iMac', 'Apple Watch', 'AirPods',
        'Galaxy S23', 'Galaxy S24', 'Galaxy Tab', 'Galaxy Watch', 'Galaxy Buds',
        'Pixel 8', 'Pixel 7', 'Pixel Watch', 'Pixel Buds',
        'Surface Pro', 'Surface Laptop', 'Surface Go', 'Xbox',
        'ThinkPad', 'IdeaPad', 'Legion', 'Yoga',
        'Dell XPS', 'Inspiron', 'Alienware', 'Latitude',
        'HP Pavilion', 'HP Envy', 'HP EliteBook', 'HP Omen',
        'PlayStation 5', 'Nintendo Switch', 'Steam Deck',
        'Kindle', 'Fire Tablet', 'Echo', 'Fire TV',
        'Chromebook', 'Raspberry Pi', 'Arduino'
    ];

    private const FULL_DEVICES = [
        'Apple iPhone 15 Pro Max', 'Apple iPhone 14 Pro', 'Apple iPhone 13', 'Apple iPhone SE',
        'Apple iPad Pro 12.9"', 'Apple iPad Air', 'Apple iPad mini',
        'Apple MacBook Pro 16"', 'Apple MacBook Air M2', 'Apple iMac 24"',
        'Apple Watch Series 9', 'Apple Watch Ultra 2', 'Apple AirPods Pro 2',
        'Samsung Galaxy S24 Ultra', 'Samsung Galaxy S23 Plus', 'Samsung Galaxy Z Fold 5',
        'Samsung Galaxy Tab S9', 'Samsung Galaxy Watch 6', 'Samsung Galaxy Buds 2 Pro',
        'Google Pixel 8 Pro', 'Google Pixel 7a', 'Google Pixel Watch 2', 'Google Pixel Buds Pro',
        'Microsoft Surface Pro 9', 'Microsoft Surface Laptop 5', 'Microsoft Surface Go 3',
        'Lenovo ThinkPad X1 Carbon', 'Lenovo Yoga 9i', 'Lenovo Legion 5 Pro',
        'Dell XPS 13', 'Dell XPS 15', 'Dell Inspiron 16', 'Dell Alienware m16',
        'HP Pavilion 15', 'HP Envy x360', 'HP EliteBook 840', 'HP Omen 16',
        'Sony PlayStation 5', 'Sony PlayStation 5 Digital', 'Microsoft Xbox Series X',
        'Nintendo Switch OLED', 'Valve Steam Deck', 'Asus ROG Ally',
        'Amazon Kindle Paperwhite', 'Amazon Fire HD 10', 'Amazon Echo Dot 5',
        'Google Chromebook Pixelbook Go', 'Raspberry Pi 5', 'Arduino Uno Rev3'
    ];

    private const DEVICE_SYMBOLS = [
        '📱', // 📱 Smartphone
        '💻', // 💻 Laptop
        '🖥️', // 🖥️ Desktop
        '⌚', // ⌚ Watch
        '🎮', // 🎮 Game Console
        '📧', // 📧 Tablet
        '🎧', // 🎧 Headphones
        '📡', // 📡 IoT Device
    ];

    private const CURRENCIES = [
        'EUR', 'USD', 'GBP', 'JPY', 'CHF', 'CAD', 'AUD', 'CNY', 'INR', 'RUB',
        'BRL', 'MXN', 'ZAR', 'KRW', 'SGD', 'HKD', 'NOK', 'SEK', 'DKK', 'PLN',
        'THB', 'IDR', 'MYR', 'PHP', 'CZK', 'ILS', 'CLP', 'ARS', 'EGP', 'TRY',
        'NZD', 'HUF', 'ISK', 'HRK', 'BGN', 'RON', 'UAH', 'VND', 'NGN', 'KES'
    ];

    private const CURRENCY_SYMBOLS = [
        'EUR' => '€', 'USD' => '$', 'GBP' => '£', 'JPY' => '¥', 'CHF' => 'CHF', 'CAD' => '$', 'AUD' => '$',
        'CNY' => '¥', 'INR' => '₹', 'RUB' => '₽', 'BRL' => 'R$', 'MXN' => '$', 'ZAR' => 'R', 'KRW' => '₩',
        'SGD' => '$', 'HKD' => '$', 'NOK' => 'kr', 'SEK' => 'kr', 'DKK' => 'kr', 'PLN' => 'zł',
        'THB' => '฿', 'IDR' => 'Rp', 'MYR' => 'RM', 'PHP' => '₱', 'CZK' => 'Kč', 'ILS' => '₪',
        'CLP' => '$', 'ARS' => '$', 'EGP' => '£', 'TRY' => '₺', 'NZD' => '$', 'HUF' => 'Ft',
        'ISK' => 'kr', 'HRK' => 'kn', 'BGN' => 'лв', 'RON' => 'lei', 'UAH' => '₴', 'VND' => '₫',
        'NGN' => '₦', 'KES' => 'KSh'
    ];

    private const FULL_CURRENCIES = [
        'EUR' => 'Euro', 'USD' => 'US Dollar', 'GBP' => 'British Pound', 'JPY' => 'Japanese Yen',
        'CHF' => 'Swiss Franc', 'CAD' => 'Canadian Dollar', 'AUD' => 'Australian Dollar',
        'CNY' => 'Chinese Yuan', 'INR' => 'Indian Rupee', 'RUB' => 'Russian Ruble',
        'BRL' => 'Brazilian Real', 'MXN' => 'Mexican Peso', 'ZAR' => 'South African Rand',
        'KRW' => 'South Korean Won', 'SGD' => 'Singapore Dollar', 'HKD' => 'Hong Kong Dollar',
        'NOK' => 'Norwegian Krone', 'SEK' => 'Swedish Krona', 'DKK' => 'Danish Krone',
        'PLN' => 'Polish Zloty', 'THB' => 'Thai Baht', 'IDR' => 'Indonesian Rupiah',
        'MYR' => 'Malaysian Ringgit', 'PHP' => 'Philippine Peso', 'CZK' => 'Czech Koruna',
        'ILS' => 'Israeli Shekel', 'CLP' => 'Chilean Peso', 'ARS' => 'Argentine Peso',
        'EGP' => 'Egyptian Pound', 'TRY' => 'Turkish Lira', 'NZD' => 'New Zealand Dollar',
        'HUF' => 'Hungarian Forint', 'ISK' => 'Icelandic Krona', 'HRK' => 'Croatian Kuna',
        'BGN' => 'Bulgarian Lev', 'RON' => 'Romanian Leu', 'UAH' => 'Ukrainian Hryvnia',
        'VND' => 'Vietnamese Dong', 'NGN' => 'Nigerian Naira', 'KES' => 'Kenyan Shilling'
    ];

    private const COUNTRY_TO_CURRENCY = [
        'FR' => 'EUR', 'DE' => 'EUR', 'IT' => 'EUR', 'ES' => 'EUR', 'PT' => 'EUR', 'GR' => 'EUR',
        'NL' => 'EUR', 'BE' => 'EUR', 'AT' => 'EUR', 'IE' => 'EUR', 'FI' => 'EUR',
        'US' => 'USD', 'GB' => 'GBP', 'JP' => 'JPY', 'CH' => 'CHF', 'CA' => 'CAD', 'AU' => 'AUD',
        'CN' => 'CNY', 'IN' => 'INR', 'RU' => 'RUB', 'BR' => 'BRL', 'MX' => 'MXN', 'ZA' => 'ZAR',
        'KR' => 'KRW', 'SG' => 'SGD', 'HK' => 'HKD', 'NO' => 'NOK', 'SE' => 'SEK', 'DK' => 'DKK',
        'PL' => 'PLN', 'TH' => 'THB', 'ID' => 'IDR', 'MY' => 'MYR', 'PH' => 'PHP', 'CZ' => 'CZK',
        'IL' => 'ILS', 'CL' => 'CLP', 'AR' => 'ARS', 'EG' => 'EGP', 'TR' => 'TRY', 'NZ' => 'NZD',
        'HU' => 'HUF', 'IS' => 'ISK', 'HR' => 'HRK', 'BG' => 'BGN', 'RO' => 'RON', 'UA' => 'UAH',
        'VN' => 'VND', 'NG' => 'NGN', 'KE' => 'KES', 'MA' => 'MAD', 'CO' => 'COP', 'PE' => 'PEN'
    ];

    private const MIME_TYPES = [
        'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml', 'image/bmp',
        'application/pdf', 'application/json', 'application/xml', 'application/zip',
        'application/x-rar-compressed', 'application/x-7z-compressed', 'application/gzip',
        'text/plain', 'text/html', 'text/css', 'text/javascript', 'text/csv', 'text/markdown',
        'video/mp4', 'video/webm', 'video/ogg', 'video/quicktime', 'video/x-msvideo',
        'audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/webm', 'audio/aac',
        'application/msword', 'application/vnd.ms-excel', 'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation'
    ];

    private const LOREM_WORDS = [
            'lorem', 'ipsum', 'dolor', 'sit', 'amet', 'consectetur',
            'adipiscing', 'elit', 'sed', 'do', 'eiusmod', 'tempor',
            'incididunt', 'ut', 'labore', 'et', 'dolore', 'magna', 'aliqua',
            'enim', 'ad', 'minim', 'veniam', 'quis', 'nostrud',
            'exercitation', 'ullamco', 'laboris', 'nisi', 'aliquip',
            'ex', 'ea', 'commodo', 'consequat', 'duis', 'aute', 'irure',
            'in', 'reprehenderit', 'voluptate', 'velit',
            'esse', 'cillum', 'eu', 'fugiat', 'nulla', 'pariatur',
            'excepteur', 'sint', 'occaecat', 'cupidatat', 'non', 'proident',
            'sunt', 'culpa', 'qui', 'officia', 'deserunt', 'mollit',
            'anim', 'id', 'est', 'laborum', 'php', 'symfony', 'doctrine',
            'faker', 'bundle', 'prism', 'hexagonal', 'architecture', 'design',
            'pattern', 'software', 'development', 'testing', 'automation',
            'continuous', 'integration', 'deployment', 'docker', 'kubernetes',
            'cloud', 'aws', 'azure', 'gcp', 'database', 'api', 'rest', 'graphql',
            'json', 'xml', 'yaml', 'html', 'css', 'javascript', 'typescript', 'react',
            'vue', 'angular', 'node', 'express', 'laravel', 'zend', 'codeigniter'
        ];

    public function generate(string $type, ?string $scope = null, mixed ...$params): string|int|float|bool
    {
        return match ($type) {
            'int', 'integer' => random_int(1, 999),
            'float', 'double', 'decimal' => round(random_int(0, 99999) / 100, 2),
            'string' => $this->generateWord(),
            'user', 'username' => $this->generateUsername($scope),
            'email' => $this->generateEmail($scope, $params[0] ?? null),
            'tel', 'phone' => $this->generatePhone($params[0] ?? ''),
            'id' => random_int(1, 999999),
            'uuid' => $this->generateUuid(),
            'date' => $this->generateDate($params[0] ?? 'Y-m-d', $params[1] ?? null, $params[2] ?? null),
            'datetime' => $this->generateDateTime($params[0] ?? 'Y-m-d H:i:s', $params[1] ?? null, $params[2] ?? null),
            'timestamp' => $this->generateTimestamp($params[0] ?? null, $params[1] ?? null),
            'microtime' => $this->generateMicrotime($params[0] ?? null, $params[1] ?? null),
            'pathfile' => $this->generateFilePath($params[0] ?? 'txt'),
            'pathdir' => $this->generateDirPath(),
            'firstname' => $this->generateFirstname(),
            'lastname' => $this->generateLastname($scope),
            'fullname' => $this->generateFullname($scope),
            'company' => $this->generateCompany(),
            'gender' => $this->generateGender(),
            'postcode' => $this->generatePostcode($params[0] ?? 'FR'),
            'street' => $this->generateStreet($params[0] ?? 'FR'),
            'city' => $this->generateCity($params[0] ?? 'FR'),
            'address' => $this->generateAddress($params[0] ?? 'FR'),
            'fulladdress' => $this->generateFulladdress($params[0] ?? 'FR'),
            'text' => $this->generateText((int)($params[0] ?? 100)),
            'number' => random_int((int)($params[0] ?? 1), (int)($params[1] ?? 100)),
            'url' => $this->generateUrl($params[0] ?? null),
            'ip' => $this->generateIp(),
            'ipv6' => $this->generateIpv6(),
            'mac' => $this->generateMac(),
            'color' => $this->generateColor(),
            'hexcolor' => $this->generateHexColor(),
            'rgb' => $this->generateRgb(),
            'rgba' => $this->generateRgba(),
            'slug' => $this->generateSlug($params[0] ?? null),
            'bool', 'boolean' => (bool)random_int(0, 1),
            'BOOLEAN' => $this->generateBooleanString(),
            'isbn' => $this->generateIsbn(),
            'ean13' => $this->generateEan13(),
            'gps', 'coordinates' => $this->generateGps(),
            'latitude' => $this->generateLatitude(),
            'longitude' => $this->generateLongitude(),
            'useragent' => $this->generateUserAgent(),
            'creditcard' => $this->generateCreditCard(),
            'age' => $this->generateAge(),
            'country' => $this->generateCountry(),
            'crypto' => $this->generateCrypto($params[0] ?? 'btc'),
            'iban' => $this->generateIban($params[0] ?? 'FR'),
            'vin' => $this->generateVin(),
            'ssn' => $this->generateSsn($params[0] ?? 'US'),
            'nir' => $this->generateNir(),
            'mime', 'mimetype' => $this->generateMimeType(),
            'siren' => $this->generateSiren(),
            'siret' => $this->generateSiret(),
            'iso' => $this->generateIsoCode($params[0] ?? 'alpha2'),
            'charset', 'encoding' => $this->generateCharset(),
            'device' => $this->generateDevice($params[0] ?? null),
            'fulldevice' => $this->generateFulldevice(),
            'currency' => $this->generateCurrency($params[0] ?? null, $params[1] ?? null),
            'fullcurrency' => $this->generateFullcurrency($params[0] ?? null),
            'json' => $this->generateJson($params[0] ?? 'id:int, name:string'),
            'serialize' => $this->generateSerialize($params[0] ?? 'id:int, name:string'),
            default => throw new \InvalidArgumentException(sprintf('Unknown fake type: %s', $type))
        };
    }

    private function generateUsername(?string $scope): string
    {
        $suffix = $scope !== null ? sprintf('_%s', $scope) : '';
        return sprintf('user_%s%s', bin2hex(random_bytes(4)), $suffix);
    }

    private function generateEmail(?string $scope, ?string $domain = null): string
    {
        $domain = $domain ?? 'example.test';
        $scopePart = $scope !== null ? sprintf('_%s', $scope) : '';
        return sprintf('user_%s%s@%s', bin2hex(random_bytes(4)), $scopePart, $domain);
    }

    private function generatePhone(string $prefix): string
    {
        $number = '';
        for ($i = 0; $i < 9; $i++) {
            $number .= (string)random_int(0, 9);
        }
        return $prefix . $number;
    }

    private function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0x0fff) | 0x4000,
            random_int(0, 0x3fff) | 0x8000,
            random_int(0, 0xffff),
            random_int(0, 0xffff),
            random_int(0, 0xffff)
        );
    }

    private function generateFirstname(): string
    {
        return self::FIRSTNAMES[array_rand(self::FIRSTNAMES)];
    }

    private function generateLastname(?string $scope): string
    {
        $raw = self::LASTNAMES[array_rand(self::LASTNAMES)];
        $name = ucfirst(strtolower($raw));
        if ($scope !== null) {
            $name .= sprintf(' (%s)', $scope);
        }
        return $name;
    }

    private function generateCompany(): string
    {
        return self::COMPANIES[array_rand(self::COMPANIES)];
    }

    private function generateFullname(?string $scope): string
    {
        return $this->generateFirstname() . ' ' . $this->generateLastname($scope);
    }

    private function generatePostcode(string $iso = 'FR'): string
    {
        $iso = strtoupper($iso);

        return match ($iso) {
            'FR' => sprintf('%05d', random_int(1000, 99999)),
            'US' => sprintf('%05d', random_int(10000, 99999)),
            'GB' => $this->generatePostcodeGb(),
            'DE' => sprintf('%05d', random_int(10000, 99999)),
            'ES' => sprintf('%05d', random_int(1000, 99999)),
            'IT' => sprintf('%05d', random_int(10000, 99999)),
            'CA' => $this->generatePostcodeCa(),
            default => sprintf('%05d', random_int(1000, 99999))
        };
    }

    private function generatePostcodeGb(): string
    {
        // Format UK : AA9A 9AA ou A9A 9AA
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $format = random_int(0, 1) === 0 ? 'AA9A' : 'A9A';

        $part1 = '';
        for ($i = 0; $i < strlen($format); $i++) {
            $part1 .= $format[$i] === 'A' ? $letters[random_int(0, 25)] : (string)random_int(0, 9);
        }

        $part2 = sprintf('%d%s%s', random_int(0, 9), $letters[random_int(0, 25)], $letters[random_int(0, 25)]);

        return $part1 . ' ' . $part2;
    }

    private function generatePostcodeCa(): string
    {
        // Format Canada : A9A 9A9
        $letters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        return sprintf(
            '%s%d%s %d%s%d',
            $letters[random_int(0, 25)],
            random_int(0, 9),
            $letters[random_int(0, 25)],
            random_int(0, 9),
            $letters[random_int(0, 25)],
            random_int(0, 9)
        );
    }

    private function generateStreet(string $iso = 'FR'): string
    {
        $iso = strtoupper($iso);

        // Utilise les données du pays ou FR par défaut
        $streetTypes = self::STREET_TYPES[$iso] ?? self::STREET_TYPES['FR'];
        $streetNames = self::STREET_NAMES[$iso] ?? self::STREET_NAMES['FR'];

        $number = random_int(1, 999);
        $type = $streetTypes[array_rand($streetTypes)];
        $name = $streetNames[array_rand($streetNames)];

        return sprintf('%d %s %s', $number, $type, $name);
    }

    private function generateCity(string $iso = 'FR'): string
    {
        $iso = strtoupper($iso);

        // Utilise les villes du pays ou FR par défaut
        $cities = self::CITIES[$iso] ?? self::CITIES['FR'];

        return $cities[array_rand($cities)];
    }

    private function generateAddress(string $iso = 'FR'): string
    {
        $iso = strtoupper($iso);

        $street = $this->generateStreet($iso);
        $postcode = $this->generatePostcode($iso);
        $city = $this->generateCity($iso);

        return sprintf('%s, %s %s', $street, $postcode, $city);
    }

    private function generateFulladdress(string $iso = 'FR'): string
    {
        $iso = strtoupper($iso);

        $street = $this->generateStreet($iso);
        $postcode = $this->generatePostcode($iso);
        $city = $this->generateCity($iso);
        $country = self::COUNTRY_NAMES[$iso] ?? 'France';

        return sprintf('%s, %s %s, %s', $street, $postcode, $city, $country);
    }

    private function generateDate(string $format, ?string $minDate = null, ?string $maxDate = null): string
    {
        // Génère une date aléatoire dans une plage définie
        if ($minDate !== null && $maxDate !== null) {
            $minTimestamp = strtotime($minDate);
            $maxTimestamp = strtotime($maxDate);

            if ($minTimestamp === false || $maxTimestamp === false) {
                // Dates invalides, utiliser les valeurs par défaut
                $minTimestamp = 946684800; // 2000-01-01
                $maxTimestamp = 2147483647; // 2038-01-19
            }
        } elseif ($minDate !== null) {
            $minTimestamp = strtotime($minDate);
            $maxTimestamp = 2147483647; // 2038-01-19

            if ($minTimestamp === false) {
                $minTimestamp = 946684800; // 2000-01-01
            }
        } else {
            // Valeurs par défaut
            $minTimestamp = 946684800; // 2000-01-01
            $maxTimestamp = 2147483647; // 2038-01-19 (limite int 32 bits)
        }

        $timestamp = random_int($minTimestamp, $maxTimestamp);
        return date($format, $timestamp);
    }

    private function generateDateTime(string $format, ?string $minDate = null, ?string $maxDate = null): string
    {
        return $this->generateDate($format, $minDate, $maxDate);
    }

    private function generateTimestamp(?string $minDate = null, ?string $maxDate = null): int
    {
        if ($minDate !== null && $maxDate !== null) {
            $minTimestamp = strtotime($minDate);
            $maxTimestamp = strtotime($maxDate);

            if ($minTimestamp === false || $maxTimestamp === false) {
                $minTimestamp = 946684800; // 2000-01-01
                $maxTimestamp = 2147483647; // 2038-01-19
            }
        } elseif ($minDate !== null) {
            $minTimestamp = strtotime($minDate);
            $maxTimestamp = 2147483647;

            if ($minTimestamp === false) {
                $minTimestamp = 946684800;
            }
        } else {
            $minTimestamp = 946684800; // 2000-01-01
            $maxTimestamp = 2147483647; // 2038-01-19
        }

        return random_int($minTimestamp, $maxTimestamp);
    }

    private function generateMicrotime(?string $minDate = null, ?string $maxDate = null): float
    {
        $timestamp = $this->generateTimestamp($minDate, $maxDate);
        $microseconds = random_int(0, 999999) / 1000000;

        return (float)$timestamp + $microseconds;
    }

    private function generateFilePath(string $extension): string
    {
        return sprintf('/tmp/file_%s.%s', bin2hex(random_bytes(4)), $extension);
    }

    private function generateDirPath(): string
    {
        return sprintf('/tmp/dir_%s', bin2hex(random_bytes(4)));
    }

    private function generateText(int $length): string
    {
        $text = '';
        $lastWord = '';
        while (strlen($text) < $length) {
            do {
                $word = self::LOREM_WORDS[array_rand(self::LOREM_WORDS)];
            } while ($word === $lastWord);

            $text .= $word . ' ';
            $lastWord = $word;
        }

        return substr(trim($text), 0, $length);
    }

    private function generateUrl(?string $protocol = null): string
    {
        $protocol = $protocol ?? (random_int(0, 1) ? 'https' : 'http');
        $domain = strtolower(bin2hex(random_bytes(4)));
        $tld = self::TLD[array_rand(self::TLD)];

        return sprintf('%s://%s.%s', $protocol, $domain, $tld);
    }

    private function generateIp(): string
    {
        return sprintf(
            '%d.%d.%d.%d',
            random_int(1, 254),
            random_int(0, 255),
            random_int(0, 255),
            random_int(1, 254)
        );
    }

    private function generateIpv6(): string
    {
        $parts = [];
        for ($i = 0; $i < 8; $i++) {
            $parts[] = sprintf('%04x', random_int(0, 0xffff));
        }
        return implode(':', $parts);
    }

    private function generateMac(): string
    {
        $parts = [];
        for ($i = 0; $i < 6; $i++) {
            $parts[] = sprintf('%02x', random_int(0, 0xff));
        }
        return strtoupper(implode(':', $parts));
    }

    private function generateColor(): string
    {
        return self::COLORS[array_rand(self::COLORS)];
    }

    private function generateHexColor(): string
    {
        return sprintf('#%06X', random_int(0, 0xFFFFFF));
    }

    private function generateRgb(): string
    {
        $r = random_int(0, 255);
        $g = random_int(0, 255);
        $b = random_int(0, 255);

        return sprintf('rgb(%d, %d, %d)', $r, $g, $b);
    }

    private function generateRgba(): string
    {
        $r = random_int(0, 255);
        $g = random_int(0, 255);
        $b = random_int(0, 255);
        $a = round(random_int(0, 100) / 100, 2);

        return sprintf('rgba(%d, %d, %d, %.2f)', $r, $g, $b, $a);
    }

    private function generateSlug(?string $text = null): string
    {
        if ($text !== null) {
            // Slugify le texte fourni
            $slug = strtolower($text);
            $slug = (string) preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-');
            return $slug;
        }

        // Génère un slug aléatoire basé sur des mots geek, prénoms, noms et compagnies
        $words = [
            'cyber', 'quantum', 'neural', 'digital', 'crypto', 'matrix', 'nexus',
            'core', 'system', 'network', 'protocol', 'sentinel', 'phoenix', 'ghost',
            'shadow', 'blade', 'storm', 'pulse', 'nova', 'zero', 'prime', 'omega'
        ];

        // Combine tous les pools de mots
        $allWords = array_merge(
            array_map('strtolower', self::FIRSTNAMES),
            array_map('strtolower', self::LASTNAMES),
            array_map(fn($c) => strtolower((string) preg_replace('/[^a-z0-9]+/i', '-', $c)), self::COMPANIES),
            $words
        );

        $word1 = $allWords[array_rand($allWords)];
        $word2 = $allWords[array_rand($allWords)];
        $number = random_int(1, 999);

        return sprintf('%s-%s-%d', $word1, $word2, $number);
    }

    private function generateGender(): string
    {
        return self::GENDERS[array_rand(self::GENDERS)];
    }

    private function generateBooleanString(): string
    {
        return random_int(0, 1) === 1 ? 'true' : 'false';
    }

    private function generateIsbn(): string
    {
        // Génère un ISBN-13
        $prefix = ['978', '979'][array_rand(['978', '979'])];
        $group = sprintf('%d', random_int(0, 9));
        $publisher = sprintf('%03d', random_int(0, 999));
        $title = sprintf('%05d', random_int(0, 99999));

        $digits = str_split($prefix . $group . $publisher . $title);
        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += (int)$digits[$i] * (($i % 2 === 0) ? 1 : 3);
        }
        $checksum = (10 - ($sum % 10)) % 10;

        return sprintf('%s-%s-%s-%s-%d', $prefix, $group, $publisher, $title, $checksum);
    }

    private function generateEan13(): string
    {
        $digits = [];
        for ($i = 0; $i < 12; $i++) {
            $digits[] = random_int(0, 9);
        }

        $sum = 0;
        for ($i = 0; $i < 12; $i++) {
            $sum += $digits[$i] * (($i % 2 === 0) ? 1 : 3);
        }
        $checksum = (10 - ($sum % 10)) % 10;
        $digits[] = $checksum;

        return implode('', $digits);
    }

    private function generateGps(): string
    {
        $latitude = $this->generateLatitude();
        $longitude = $this->generateLongitude();
        return sprintf('%s, %s', $latitude, $longitude);
    }

    private function generateLatitude(): string
    {
        return sprintf('%.6f', (random_int(-90000000, 90000000) / 1000000));
    }

    private function generateLongitude(): string
    {
        return sprintf('%.6f', (random_int(-180000000, 180000000) / 1000000));
    }

    private function generateUserAgent(): string
    {
        return self::USER_AGENTS[array_rand(self::USER_AGENTS)];
    }

    private function generateCreditCard(): string
    {
        // Génère un numéro de carte de crédit masqué pour la sécurité
        $prefix = ['4', '5', '3'][array_rand(['4', '5', '3'])];
        $masked = sprintf('%s*** **** **** %04d', $prefix, random_int(0, 9999));
        return $masked;
    }

    private function generateAge(): int
    {
        return random_int(18, 99);
    }

    private function generateCountry(): string
    {
        return self::COUNTRIES[array_rand(self::COUNTRIES)];
    }

    private function generateCrypto(string $type): string
    {
        return match (strtolower($type)) {
            'btc', 'bitcoin' => $this->generateBitcoinAddress(),
            'eth', 'ethereum' => $this->generateEthereumAddress(),
            'xrp', 'ripple' => $this->generateRippleAddress(),
            'sol', 'solana' => $this->generateSolanaAddress(),
            'ltc', 'litecoin' => $this->generateLitecoinAddress(),
            'doge', 'dogecoin' => $this->generateDogecoinAddress(),
            'ada', 'cardano' => $this->generateCardanoAddress(),
            'dot', 'polkadot' => $this->generatePolkadotAddress(),
            'usdt', 'tether' => $this->generateEthereumAddress(), // USDT utilise souvent ERC-20
            default => $this->generateBitcoinAddress()
        };
    }

    private function generateBitcoinAddress(): string
    {
        // Format Bitcoin : commence par 1, 3 ou bc1 (legacy, script, bech32)
        $formats = ['1', '3', 'bc1'];
        $prefix = $formats[array_rand($formats)];
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz123456789';
        $length = $prefix === 'bc1' ? random_int(39, 59) : random_int(25, 33);
        $address = $prefix;

        for ($i = 0; $i < $length; $i++) {
            $address .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $address;
    }

    private function generateEthereumAddress(): string
    {
        // Format Ethereum : 0x suivi de 40 caractères hexadécimaux
        return '0x' . bin2hex(random_bytes(20));
    }

    private function generateRippleAddress(): string
    {
        // Format Ripple : commence par r suivi de 24-34 caractères
        $chars = 'rpshnaf39wBUDNEGHJKLM4PQRST7VWXYZ2bcdeCg65jkm8oFqi1tuvAxyz';
        $length = random_int(24, 34);
        $address = 'r';

        for ($i = 0; $i < $length; $i++) {
            $address .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $address;
    }

    private function generateSolanaAddress(): string
    {
        // Format Solana : 32-44 caractères base58
        $chars = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $length = random_int(32, 44);
        $address = '';

        for ($i = 0; $i < $length; $i++) {
            $address .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $address;
    }

    private function generateLitecoinAddress(): string
    {
        // Format Litecoin : commence par L ou M
        $prefix = ['L', 'M'][array_rand(['L', 'M'])];
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz123456789';
        $length = random_int(25, 33);
        $address = $prefix;

        for ($i = 0; $i < $length; $i++) {
            $address .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $address;
    }

    private function generateDogecoinAddress(): string
    {
        // Format Dogecoin : commence par D
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz123456789';
        $address = 'D';

        for ($i = 0; $i < 33; $i++) {
            $address .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $address;
    }

    private function generateCardanoAddress(): string
    {
        // Format Cardano : commence par addr1
        $chars = 'qpzry9x8gf2tvdw0s3jn54khce6mua7l';
        $length = random_int(54, 100);
        $address = 'addr1';

        for ($i = 0; $i < $length; $i++) {
            $address .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $address;
    }

    private function generatePolkadotAddress(): string
    {
        // Format Polkadot : commence par 1
        $chars = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $address = '1';

        for ($i = 0; $i < 46; $i++) {
            $address .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $address;
    }

    private function generateIban(string $country = 'FR'): string
    {
        // Génère un IBAN selon le pays (par défaut France)
        $country = strtoupper($country);

        return match ($country) {
            'FR' => $this->generateIbanFr(),
            'DE' => $this->generateIbanDe(),
            'GB' => $this->generateIbanGb(),
            'ES' => $this->generateIbanEs(),
            'IT' => $this->generateIbanIt(),
            default => $this->generateIbanFr()
        };
    }

    private function generateIbanFr(): string
    {
        // IBAN France : FR76 XXXX XXXX XXXX XXXX XXXX XXX (27 caractères)
        $bankCode = sprintf('%05d', random_int(10000, 99999));
        $branchCode = sprintf('%05d', random_int(10000, 99999));
        $accountNumber = sprintf('%011d', random_int(10000000000, 99999999999));
        $key = sprintf('%02d', random_int(10, 99));

        return sprintf('FR%s %s %s %s%s', $key, $bankCode, $branchCode, substr($accountNumber, 0, 6), substr($accountNumber, 6) . ' ' . sprintf('%03d', random_int(100, 999)));
    }

    private function generateIbanDe(): string
    {
        // IBAN Allemagne : DE89 XXXX XXXX XXXX XXXX XX (22 caractères)
        $key = sprintf('%02d', random_int(10, 99));
        $bankCode = sprintf('%08d', random_int(10000000, 99999999));
        $accountNumber = sprintf('%010d', random_int(1000000000, 9999999999));

        return sprintf('DE%s %s %s', $key, $bankCode, $accountNumber);
    }

    private function generateIbanGb(): string
    {
        // IBAN UK : GB29 XXXX XXXX XXXX XXXX XX (22 caractères)
        $key = sprintf('%02d', random_int(10, 99));
        $bankCode = sprintf('%04d', random_int(1000, 9999));
        $branchCode = sprintf('%06d', random_int(100000, 999999));
        $accountNumber = sprintf('%08d', random_int(10000000, 99999999));

        return sprintf('GB%s %s %s %s', $key, $bankCode, $branchCode, $accountNumber);
    }

    private function generateIbanEs(): string
    {
        // IBAN Espagne : ES91 XXXX XXXX XXXX XXXX XXXX (24 caractères)
        $key = sprintf('%02d', random_int(10, 99));
        $bankCode = sprintf('%04d', random_int(1000, 9999));
        $branchCode = sprintf('%04d', random_int(1000, 9999));
        $checkDigits = sprintf('%02d', random_int(10, 99));
        $accountNumber = sprintf('%010d', random_int(1000000000, 9999999999));

        return sprintf('ES%s %s %s %s %s', $key, $bankCode, $branchCode, $checkDigits, $accountNumber);
    }

    private function generateIbanIt(): string
    {
        // IBAN Italie : IT60 X XXXXX XXXXX XXXXXXXXXXXX (27 caractères)
        $key = sprintf('%02d', random_int(10, 99));
        $checkChar = chr(random_int(65, 90));
        $bankCode = sprintf('%05d', random_int(10000, 99999));
        $branchCode = sprintf('%05d', random_int(10000, 99999));
        $accountNumber = sprintf('%012d', random_int(100000000000, 999999999999));

        return sprintf('IT%s %s %s %s %s', $key, $checkChar, $bankCode, $branchCode, $accountNumber);
    }

    private function generateVin(): string
    {
        // VIN (Vehicle Identification Number) : 17 caractères alphanumériques (sans I, O, Q)
        $chars = 'ABCDEFGHJKLMNPRSTUVWXYZ0123456789';
        $vin = '';

        for ($i = 0; $i < 17; $i++) {
            $vin .= $chars[random_int(0, strlen($chars) - 1)];
        }

        return $vin;
    }

    private function generateSsn(string $country = 'US'): string
    {
        // Social Security Number selon le pays
        return match (strtoupper($country)) {
            'US' => $this->generateSsnUs(),
            'FR' => $this->generateNir(),
            default => $this->generateSsnUs()
        };
    }

    private function generateSsnUs(): string
    {
        // SSN US : XXX-XX-XXXX
        $area = sprintf('%03d', random_int(1, 899)); // 001-899 (évite 000, 666, 900-999)
        $group = sprintf('%02d', random_int(1, 99));
        $serial = sprintf('%04d', random_int(1, 9999));

        return sprintf('%s-%s-%s', $area, $group, $serial);
    }

    private function generateNir(): string
    {
        // NIR (Numéro d'Inscription au Répertoire) France : 1 YY MM DD CCC OOO KK
        $sexe = random_int(1, 2); // 1 = homme, 2 = femme
        $annee = sprintf('%02d', random_int(0, 99));
        $mois = sprintf('%02d', random_int(1, 12));
        $departement = sprintf('%02d', random_int(1, 95));
        $commune = sprintf('%03d', random_int(1, 999));
        $ordre = sprintf('%03d', random_int(1, 999));

        // Calcul de la clé
        $nir = $sexe . $annee . $mois . $departement . $commune . $ordre;
        $key = 97 - ((int)$nir % 97);

        return sprintf('%d %s %s %s %s %s %02d', $sexe, $annee, $mois, $departement, $commune, $ordre, $key);
    }

    private function generateMimeType(): string
    {
        return self::MIME_TYPES[array_rand(self::MIME_TYPES)];
    }

    private function generateSiren(): string
    {
        // SIREN : 9 chiffres avec algorithme de Luhn
        $digits = [];
        for ($i = 0; $i < 8; $i++) {
            $digits[] = random_int(0, 9);
        }

        // Calcul de la clé avec algorithme de Luhn
        $sum = 0;
        for ($i = 0; $i < 8; $i++) {
            $digit = $digits[$i];
            if ($i % 2 === 1) {
                $digit *= 2;
                if ($digit > 9) {
                    $digit -= 9;
                }
            }
            $sum += $digit;
        }

        $key = (10 - ($sum % 10)) % 10;
        $digits[] = $key;

        return implode('', $digits);
    }

    private function generateSiret(): string
    {
        // SIRET : SIREN (9 chiffres) + NIC (5 chiffres)
        $siren = $this->generateSiren();
        $nic = sprintf('%05d', random_int(1, 99999));

        return $siren . $nic;
    }

    private function generateIsoCode(string $format = 'alpha2'): string
    {
        $codes = array_keys(self::ISO_CODES);
        $alpha2 = $codes[array_rand($codes)];

        return match (strtolower($format)) {
            'alpha2', '2' => $alpha2,
            'alpha3', '3' => self::ISO_CODES[$alpha2],
            default => $alpha2
        };
    }

    private function generateCharset(): string
    {
        return self::CHARSETS[array_rand(self::CHARSETS)];
    }

    private function generateDevice(?string $option = null): string
    {
        if ($option === 'symbol') {
            return self::DEVICE_SYMBOLS[array_rand(self::DEVICE_SYMBOLS)];
        }

        return self::DEVICES[array_rand(self::DEVICES)];
    }

    private function generateFulldevice(): string
    {
        return self::FULL_DEVICES[array_rand(self::FULL_DEVICES)];
    }

    private function generateCurrency(?string $iso = null, ?string $option = null): string
    {
        // Si un ISO spécifique est demandé
        if ($iso !== null) {
            $isoUpper = strtoupper($iso);

            // Vérifier si c'est un code devise valide
            if (!isset(self::CURRENCY_SYMBOLS[$isoUpper])) {
                // Sinon, vérifier si c'est un code pays
                if (isset(self::COUNTRY_TO_CURRENCY[$isoUpper])) {
                    $isoUpper = self::COUNTRY_TO_CURRENCY[$isoUpper];
                } else {
                    // Si rien ne correspond, utiliser EUR par défaut
                    $isoUpper = 'EUR';
                }
            }

            if ($option === 'symbol') {
                return self::CURRENCY_SYMBOLS[$isoUpper];
            }

            return $isoUpper;
        }

        // Sinon, choisir une devise aléatoire
        $randomCurrency = self::CURRENCIES[array_rand(self::CURRENCIES)];

        if ($option === 'symbol') {
            return self::CURRENCY_SYMBOLS[$randomCurrency];
        }

        return $randomCurrency;
    }

    private function generateFullcurrency(?string $iso = null): string
    {
        // Si un ISO spécifique est demandé
        if ($iso !== null) {
            $isoUpper = strtoupper($iso);

            // Vérifier si c'est un code devise valide
            if (!isset(self::FULL_CURRENCIES[$isoUpper])) {
                // Sinon, vérifier si c'est un code pays
                if (isset(self::COUNTRY_TO_CURRENCY[$isoUpper])) {
                    $isoUpper = self::COUNTRY_TO_CURRENCY[$isoUpper];
                } else {
                    // Si rien ne correspond, utiliser EUR par défaut
                    $isoUpper = 'EUR';
                }
            }

            return self::FULL_CURRENCIES[$isoUpper];
        }

        // Sinon, choisir une devise aléatoire
        $randomCurrency = self::CURRENCIES[array_rand(self::CURRENCIES)];
        return self::FULL_CURRENCIES[$randomCurrency];
    }

    private function generateJson(string $definition): string
    {
        $result = $this->parseJsonDefinition($definition);
        $json = json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $json !== false ? $json : '{}';
    }

    private function generateSerialize(string $definition): string
    {
        // Réutilise la logique JSON puis convertit en serialize
        $json = $this->generateJson($definition);
        $array = json_decode($json, true);
        return serialize($array);
    }

    private function parseJsonDefinition(string $definition): array
    {
        $definition = trim($definition);

        // Détecte si c'est un objet (contient des `:` hors des accolades)
        if ($this->isJsonObject($definition)) {
            return $this->parseJsonObject($definition);
        }

        // Sinon c'est un array
        return $this->parseJsonArray($definition);
    }

    private function isJsonObject(string $definition): bool
    {
        // Supprime les accolades imbriquées pour détecter les `:` au niveau principal
        $depth = 0;
        for ($i = 0; $i < strlen($definition); $i++) {
            $char = $definition[$i];
            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
            } elseif ($char === ':' && $depth === 0) {
                return true;
            }
        }
        return false;
    }

    private function parseJsonObject(string $definition): array
    {
        $result = [];
        $fields = $this->splitFields($definition);

        foreach ($fields as $field) {
            $field = trim($field);
            if (empty($field)) {
                continue;
            }

            // Parse "name:type" ou "name:{...}"
            $colonPos = $this->findMainColon($field);
            if ($colonPos === false) {
                continue;
            }

            $name = trim(substr($field, 0, $colonPos));
            $type = trim(substr($field, $colonPos + 1));

            $result[$name] = $this->generateJsonValue($type);
        }

        return $result;
    }

    private function parseJsonArray(string $definition): array
    {
        $result = [];
        $fields = $this->splitFields($definition);

        foreach ($fields as $field) {
            $field = trim($field);
            if (empty($field)) {
                continue;
            }

            $result[] = $this->generateJsonValue($field);
        }

        return $result;
    }

    private function generateJsonValue(string $type): mixed
    {
        $type = trim($type);

        // Objet imbriqué
        if (str_starts_with($type, '{') && str_ends_with($type, '}')) {
            $innerDef = substr($type, 1, -1);
            return $this->parseJsonDefinition($innerDef);
        }

        // Parser le type et ses paramètres (ex: "text:100", "number:1:100", "email:acme.com")
        $parts = explode(':', $type);
        $typeName = trim($parts[0]);
        $params = array_slice($parts, 1);

        // Normaliser certains types en minuscules, mais préserver BOOLEAN
        $typeNameLower = strtolower($typeName);

        // Prévenir la récursion infinie avec json
        if ($typeNameLower === 'json') {
            return $this->generateWord();
        }

        // Utiliser generate() pour tous les types
        try {
            // Utiliser $typeName (avec casse préservée) pour generate()
            return $this->generate($typeName, null, ...$params);
        } catch (\InvalidArgumentException) {
            // Type inconnu, retourner un mot aléatoire
            return $this->generateWord();
        }
    }

    private function generateWord(): string
    {
        return self::LOREM_WORDS[array_rand(self::LOREM_WORDS)];
    }

    private function splitFields(string $definition): array
    {
        $fields = [];
        $current = '';
        $depth = 0;

        for ($i = 0; $i < strlen($definition); $i++) {
            $char = $definition[$i];

            if ($char === '{') {
                $depth++;
                $current .= $char;
            } elseif ($char === '}') {
                $depth--;
                $current .= $char;
            } elseif ($char === ',' && $depth === 0) {
                $fields[] = $current;
                $current = '';
            } else {
                $current .= $char;
            }
        }

        if (!empty(trim($current))) {
            $fields[] = $current;
        }

        return $fields;
    }

    private function findMainColon(string $field): int|false
    {
        $depth = 0;
        for ($i = 0; $i < strlen($field); $i++) {
            $char = $field[$i];
            if ($char === '{') {
                $depth++;
            } elseif ($char === '}') {
                $depth--;
            } elseif ($char === ':' && $depth === 0) {
                return $i;
            }
        }
        return false;
    }
}
