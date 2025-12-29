<?php

declare(strict_types=1);

namespace Tests\Prism\Infrastructure\Faker;

use PHPUnit\Framework\TestCase;
use Prism\Infrastructure\Faker\SimpleFakeDataGenerator;

/**
 * Tests unitaires pour SimpleFakeDataGenerator
 */
final class SimpleFakeDataGeneratorTest extends TestCase
{
    private SimpleFakeDataGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new SimpleFakeDataGenerator();
    }

    // ==================== Tests Username ====================

    public function testGenerateUserWithoutScope(): void
    {
        $result = $this->generator->generate('user');

        $this->assertIsString($result);
        $this->assertStringStartsWith('user_', $result);
        // Vérifie que le résultat contient un hash hexadécimal (8 caractères)
        $this->assertMatchesRegularExpression('/^user_[a-f0-9]{8}$/', $result);
    }

    public function testGenerateUserWithScope(): void
    {
        $result = $this->generator->generate('user', 'test');

        $this->assertIsString($result);
        $this->assertStringStartsWith('user_', $result);
        $this->assertStringEndsWith('_test', $result);
        $this->assertMatchesRegularExpression('/^user_[a-f0-9]{8}_test$/', $result);
    }

    public function testGenerateUsernameAlias(): void
    {
        $result1 = $this->generator->generate('user', 'scope1');
        $result2 = $this->generator->generate('username', 'scope1');

        // Les deux devraient avoir le même format
        $this->assertIsString($result1);
        $this->assertMatchesRegularExpression('/^user_[a-f0-9]{8}_scope1$/', $result1);
        $this->assertIsString($result2);
        $this->assertMatchesRegularExpression('/^user_[a-f0-9]{8}_scope1$/', $result2);
    }

    // ==================== Tests Email ====================

    public function testGenerateEmailWithoutDomain(): void
    {
        $result = $this->generator->generate('email');

        $this->assertIsString($result);
        $this->assertStringContainsString('@example.test', $result);
        $this->assertMatchesRegularExpression('/^user_[a-f0-9]{8}@example\.test$/', $result);
    }

    public function testGenerateEmailWithCustomDomain(): void
    {
        $result = $this->generator->generate('email', null, 'acme.com');

        $this->assertIsString($result);
        $this->assertStringContainsString('@acme.com', $result);
        $this->assertMatchesRegularExpression('/^user_[a-f0-9]{8}@acme\.com$/', $result);
    }

    public function testGenerateEmailWithScope(): void
    {
        $result = $this->generator->generate('email', 'prod');

        $this->assertIsString($result);
        $this->assertStringContainsString('_prod@', $result);
        $this->assertMatchesRegularExpression('/^user_[a-f0-9]{8}_prod@example\.test$/', $result);
    }

    public function testGenerateEmailWithScopeAndCustomDomain(): void
    {
        $result = $this->generator->generate('email', 'dev', 'corp.io');

        $this->assertIsString($result);
        $this->assertStringContainsString('_dev@corp.io', $result);
        $this->assertMatchesRegularExpression('/^user_[a-f0-9]{8}_dev@corp\.io$/', $result);
    }

    // ==================== Tests Names ====================

    public function testGenerateFirstname(): void
    {
        $result = $this->generator->generate('firstname');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        // Vérifie que c'est un prénom valide (peut contenir acronymes comme TARS, HAL)
        $this->assertMatchesRegularExpression('/^[A-Z][A-Za-z]+$/', $result);
    }

    public function testGenerateLastnameWithoutScope(): void
    {
        $result = $this->generator->generate('lastname');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        // Sans scope, juste le nom
        $this->assertMatchesRegularExpression('/^[A-Z][a-z]+$/', $result);
    }

    public function testGenerateLastnameWithScope(): void
    {
        $result = $this->generator->generate('lastname', 'qa');

        $this->assertIsString($result);
        $this->assertStringContainsString('(qa)', $result);
        $this->assertMatchesRegularExpression('/^[A-Z][a-z]+ \(qa\)$/', $result);
    }

    public function testGenerateCompany(): void
    {
        $result = $this->generator->generate('company');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        // Vérifie que c'est une entreprise valide
        $this->assertGreaterThan(3, strlen($result));
    }

    // ==================== Tests IDs ====================

    public function testGenerateId(): void
    {
        $result = $this->generator->generate('id');

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(1, $result);
        $this->assertLessThanOrEqual(999999, $result);
    }

    public function testGenerateUuid(): void
    {
        $result = $this->generator->generate('uuid');

        $this->assertIsString($result);
        // Vérifie le format UUID v4 : xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{8}-[a-f0-9]{4}-4[a-f0-9]{3}-[89ab][a-f0-9]{3}-[a-f0-9]{12}$/i',
            $result
        );
    }

    public function testGenerateUuidUniqueness(): void
    {
        $uuid1 = $this->generator->generate('uuid');
        $uuid2 = $this->generator->generate('uuid');

        $this->assertNotEquals($uuid1, $uuid2, 'Les UUIDs devraient être uniques');
    }

    // ==================== Tests Dates ====================

    public function testGenerateDateDefaultFormat(): void
    {
        $result = $this->generator->generate('date');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}$/', $result);

        // Vérifie que c'est une date valide
        $timestamp = strtotime($result);
        $this->assertNotFalse($timestamp);
    }

    public function testGenerateDateCustomFormat(): void
    {
        $result = $this->generator->generate('date', null, 'd/m/Y');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{2}\/\d{2}\/\d{4}$/', $result);
    }

    public function testGenerateDateTimeDefaultFormat(): void
    {
        $result = $this->generator->generate('datetime');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $result);

        // Vérifie que c'est une date valide
        $timestamp = strtotime($result);
        $this->assertNotFalse($timestamp);
    }

    public function testGenerateDateTimeCustomFormat(): void
    {
        $result = $this->generator->generate('datetime', null, 'Y-m-d H:i');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $result);
    }

    // ==================== Tests Files ====================

    public function testGeneratePathFileDefaultExtension(): void
    {
        $result = $this->generator->generate('pathfile');

        $this->assertIsString($result);
        $this->assertStringStartsWith('/tmp/file_', $result);
        $this->assertStringEndsWith('.txt', $result);
        $this->assertMatchesRegularExpression('/^\/tmp\/file_[a-f0-9]{8}\.txt$/', $result);
    }

    public function testGeneratePathFileCustomExtension(): void
    {
        $result = $this->generator->generate('pathfile', null, 'pdf');

        $this->assertIsString($result);
        $this->assertStringEndsWith('.pdf', $result);
        $this->assertMatchesRegularExpression('/^\/tmp\/file_[a-f0-9]{8}\.pdf$/', $result);
    }

    public function testGeneratePathDir(): void
    {
        $result = $this->generator->generate('pathdir');

        $this->assertIsString($result);
        $this->assertStringStartsWith('/tmp/dir_', $result);
        $this->assertMatchesRegularExpression('/^\/tmp\/dir_[a-f0-9]{8}$/', $result);
    }

    // ==================== Tests Text & Numbers ====================

    public function testGenerateTextDefaultLength(): void
    {
        $result = $this->generator->generate('text');

        $this->assertIsString($result);
        $this->assertLessThanOrEqual(100, strlen($result));
        $this->assertGreaterThan(0, strlen($result));
    }

    public function testGenerateTextCustomLength(): void
    {
        $length = 50;
        $result = $this->generator->generate('text', null, $length);

        $this->assertIsString($result);
        // Le texte peut avoir ±1 caractère à cause du trim des espaces
        $this->assertLessThanOrEqual($length, strlen($result));
        $this->assertGreaterThanOrEqual($length - 1, strlen($result));
    }

    public function testGenerateNumberDefaultRange(): void
    {
        $result = $this->generator->generate('number');

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(1, $result);
        $this->assertLessThanOrEqual(100, $result);
    }

    public function testGenerateNumberCustomRange(): void
    {
        $min = 10;
        $max = 20;
        $result = $this->generator->generate('number', null, $min, $max);

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual($min, $result);
        $this->assertLessThanOrEqual($max, $result);
    }

    // ==================== Tests Network ====================

    public function testGenerateUrl(): void
    {
        $result = $this->generator->generate('url');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^https?:\/\/[a-f0-9]{8}\.[a-z]{2,}$/', $result);
    }

    public function testGenerateUrlWithProtocol(): void
    {
        $result = $this->generator->generate('url', null, 'http');

        $this->assertIsString($result);
        $this->assertStringStartsWith('http://', $result);
    }

    public function testGenerateIp(): void
    {
        $result = $this->generator->generate('ip');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression(
            '/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}$/',
            $result
        );

        // Vérifie que chaque octet est valide (0-255)
        $parts = explode('.', $result);
        $this->assertCount(4, $parts);
        foreach ($parts as $part) {
            $value = (int) $part;
            $this->assertGreaterThanOrEqual(0, $value);
            $this->assertLessThanOrEqual(255, $value);
        }
    }

    public function testGenerateIpv6(): void
    {
        $result = $this->generator->generate('ipv6');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression(
            '/^[a-f0-9]{4}:[a-f0-9]{4}:[a-f0-9]{4}:[a-f0-9]{4}:[a-f0-9]{4}:[a-f0-9]{4}:[a-f0-9]{4}:[a-f0-9]{4}$/i',
            $result
        );
    }

    public function testGenerateMac(): void
    {
        $result = $this->generator->generate('mac');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression(
            '/^[A-F0-9]{2}:[A-F0-9]{2}:[A-F0-9]{2}:[A-F0-9]{2}:[A-F0-9]{2}:[A-F0-9]{2}$/',
            $result
        );
    }

    public function testGenerateTelDefaultPrefix(): void
    {
        $result = $this->generator->generate('tel');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{9}$/', $result);
    }

    public function testGeneratePhoneAlias(): void
    {
        $result = $this->generator->generate('phone', null, '+1');

        $this->assertIsString($result);
        $this->assertStringStartsWith('+1', $result);
        $this->assertMatchesRegularExpression('/^\+1\d{9}$/', $result);
    }

    public function testGenerateTelCustomPrefix(): void
    {
        $result = $this->generator->generate('tel', null, '+44');

        $this->assertIsString($result);
        $this->assertStringStartsWith('+44', $result);
        $this->assertMatchesRegularExpression('/^\+44\d{9}$/', $result);
    }

    // ==================== Tests Misc ====================

    public function testGenerateColor(): void
    {
        $result = $this->generator->generate('color');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        // Color retourne un nom de couleur, pas un code hex
    }

    public function testGenerateBoolean(): void
    {
        $result = $this->generator->generate('boolean');

        $this->assertIsBool($result);
    }

    // ==================== Tests Errors ====================

    public function testGenerateThrowsExceptionForUnknownType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown fake type: invalid_type');

        $this->generator->generate('invalid_type');
    }

    // ==================== Tests Randomness ====================

    public function testGenerateProducesRandomValues(): void
    {
        // Teste que les valeurs générées sont différentes
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = $this->generator->generate('user', 'test');
        }

        // Au moins 9 valeurs sur 10 devraient être différentes (probabilité très élevée)
        $unique = array_unique($results);
        $this->assertGreaterThanOrEqual(9, count($unique), 'Les valeurs générées devraient être aléatoires');
    }

    public function testGenerateUuidProducesUniqueValues(): void
    {
        $uuids = [];
        for ($i = 0; $i < 100; $i++) {
            $uuids[] = $this->generator->generate('uuid');
        }

        // Tous les UUIDs devraient être uniques
        $unique = array_unique($uuids);
        $this->assertCount(100, $unique, 'Tous les UUIDs devraient être uniques');
    }

    public function testGenerateEmailProducesUniqueValues(): void
    {
        $emails = [];
        for ($i = 0; $i < 50; $i++) {
            $emails[] = $this->generator->generate('email', 'test');
        }

        // Tous les emails devraient être uniques
        $unique = array_unique($emails);
        $this->assertCount(50, $unique, 'Tous les emails devraient être uniques');
    }

    // ==================== Tests Integration ====================

    public function testScopeIntegrationAcrossMultipleTypes(): void
    {
        $scope = 'integration_test';

        $user = $this->generator->generate('user', $scope);
        $email = $this->generator->generate('email', $scope);
        $lastname = $this->generator->generate('lastname', $scope);

        // Vérifie que le scope est intégré dans chaque type
        $this->assertIsString($user);
        $this->assertStringContainsString($scope, $user);
        $this->assertIsString($email);
        $this->assertStringContainsString($scope, $email);
        $this->assertIsString($lastname);
        $this->assertStringContainsString($scope, $lastname);
    }

    public function testAllTypesGenerate(): void
    {
        // Test que tous les types supportés génèrent quelque chose sans erreur
        $types = [
            'user', 'username', 'email', 'firstname', 'lastname', 'company',
            'id', 'uuid',
            'date', 'datetime',
            'pathfile', 'pathdir',
            'text', 'number',
            'url', 'ip', 'ipv6', 'mac', 'tel', 'phone',
            'color', 'boolean'
        ];

        foreach ($types as $type) {
            $result = $this->generator->generate($type);
            $this->assertNotNull($result, "Type '{$type}' devrait générer une valeur");
        }
    }

    // ==================== Tests Fullname ====================

    public function testGenerateFullnameWithoutScope(): void
    {
        $result = $this->generator->generate('fullname');

        $this->assertIsString($result);
        $this->assertStringContainsString(' ', $result);
        // Accepte aussi des noms comme McDonough (avec majuscule interne)
        $this->assertMatchesRegularExpression('/^[A-Z][a-zA-Z]+ [A-Z][a-zA-Z]+$/', $result);
    }

    public function testGenerateFullnameWithScope(): void
    {
        $result = $this->generator->generate('fullname', 'test');

        $this->assertIsString($result);
        $this->assertStringContainsString(' ', $result);
        $this->assertStringContainsString('(test)', $result);
    }

    // ==================== Tests Addresses ====================

    public function testGeneratePostcodeDefaultFR(): void
    {
        $result = $this->generator->generate('postcode');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{5}$/', $result);
    }

    public function testGeneratePostcodeFR(): void
    {
        $result = $this->generator->generate('postcode', null, 'FR');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{5}$/', $result);
    }

    public function testGeneratePostcodeGB(): void
    {
        $result = $this->generator->generate('postcode', null, 'GB');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[A-Z]{1,2}\d{1,2}[A-Z]? \d[A-Z]{2}$/', $result);
    }

    public function testGeneratePostcodeCA(): void
    {
        $result = $this->generator->generate('postcode', null, 'CA');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[A-Z]\d[A-Z] \d[A-Z]\d$/', $result);
    }

    public function testGeneratePostcodeUS(): void
    {
        $result = $this->generator->generate('postcode', null, 'US');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{5}$/', $result);
    }

    public function testGenerateStreetDefaultFR(): void
    {
        $result = $this->generator->generate('street');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertGreaterThan(5, strlen($result));
    }

    public function testGenerateStreetUS(): void
    {
        $result = $this->generator->generate('street', null, 'US');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertMatchesRegularExpression('/\d+\s+\w+/', $result);
    }

    public function testGenerateCityDefaultFR(): void
    {
        $result = $this->generator->generate('city');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGenerateCityUS(): void
    {
        $result = $this->generator->generate('city', null, 'US');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGenerateAddressDefaultFR(): void
    {
        $result = $this->generator->generate('address');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertStringContainsString(',', $result);
    }

    public function testGenerateFulladdressDefaultFR(): void
    {
        $result = $this->generator->generate('fulladdress');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        $this->assertGreaterThan(20, strlen($result));
    }

    // ==================== Tests Time ====================

    public function testGenerateTimestampDefault(): void
    {
        $result = $this->generator->generate('timestamp');

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(946684800, $result); // 2000-01-01
        $this->assertLessThanOrEqual(2147483647, $result);   // 2038-01-19
    }

    public function testGenerateTimestampWithRange(): void
    {
        $min = '2020-01-01';
        $max = '2025-12-31';
        $result = $this->generator->generate('timestamp', null, $min, $max);

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(strtotime($min), $result);
        $this->assertLessThanOrEqual(strtotime($max), $result);
    }

    public function testGenerateMicrotimeDefault(): void
    {
        $result = $this->generator->generate('microtime');

        $this->assertIsFloat($result);
        $this->assertGreaterThan(0, $result);
    }

    // ==================== Tests Colors Extended ====================

    public function testGenerateHexColor(): void
    {
        $result = $this->generator->generate('hexcolor');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^#[A-F0-9]{6}$/', $result);
    }

    public function testGenerateRgb(): void
    {
        $result = $this->generator->generate('rgb');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^rgb\(\d{1,3}, \d{1,3}, \d{1,3}\)$/', $result);
    }

    public function testGenerateRgba(): void
    {
        $result = $this->generator->generate('rgba');

        $this->assertIsString($result);
        // Alpha peut être 0.00 à 1.00 (random_int(0, 100) / 100)
        $this->assertMatchesRegularExpression('/^rgba\(\d{1,3}, \d{1,3}, \d{1,3}, [01]\.\d{2}\)$/', $result);
    }

    // ==================== Tests Data Types ====================

    public function testGenerateSlugDefault(): void
    {
        $result = $this->generator->generate('slug');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[a-z0-9]+(-[a-z0-9]+)*$/', $result);
    }

    public function testGenerateSlugWithText(): void
    {
        $result = $this->generator->generate('slug', null, 'Hello World');

        $this->assertIsString($result);
        $this->assertEquals('hello-world', $result);
    }

    public function testGenerateGender(): void
    {
        $result = $this->generator->generate('gender');

        $this->assertIsString($result);
        $this->assertContains($result, ['male', 'female', 'other', 'non-binary']);
    }

    public function testGenerateBOOLEANUppercase(): void
    {
        $result = $this->generator->generate('BOOLEAN');

        $this->assertIsString($result);
        $this->assertContains($result, ['true', 'false']);
    }

    public function testGenerateAge(): void
    {
        $result = $this->generator->generate('age');

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(18, $result);
        $this->assertLessThanOrEqual(99, $result);
    }

    public function testGenerateCountry(): void
    {
        $result = $this->generator->generate('country');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    // ==================== Tests Identifiers ====================

    public function testGenerateIsbn(): void
    {
        $result = $this->generator->generate('isbn');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{3}-\d-\d{3}-\d{5}-\d$/', $result);
    }

    public function testGenerateEan13(): void
    {
        $result = $this->generator->generate('ean13');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{13}$/', $result);
    }

    public function testGenerateGps(): void
    {
        $result = $this->generator->generate('gps');

        $this->assertIsString($result);
        $this->assertStringContainsString(',', $result);
    }

    public function testGenerateLatitude(): void
    {
        $result = $this->generator->generate('latitude');

        $this->assertIsString($result);
        $lat = (float) $result;
        $this->assertGreaterThanOrEqual(-90, $lat);
        $this->assertLessThanOrEqual(90, $lat);
    }

    public function testGenerateLongitude(): void
    {
        $result = $this->generator->generate('longitude');

        $this->assertIsString($result);
        $lon = (float) $result;
        $this->assertGreaterThanOrEqual(-180, $lon);
        $this->assertLessThanOrEqual(180, $lon);
    }

    public function testGenerateUserAgent(): void
    {
        $result = $this->generator->generate('useragent');

        $this->assertIsString($result);
        $this->assertStringContainsString('Mozilla', $result);
    }

    public function testGenerateCreditCard(): void
    {
        $result = $this->generator->generate('creditcard');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[3-5]\*{3} \*{4} \*{4} \d{4}$/', $result);
    }

    // ==================== Tests Crypto Addresses ====================

    public function testGenerateBitcoinAddress(): void
    {
        $result = $this->generator->generate('crypto', null, 'bitcoin');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[13bc][a-km-zA-HJ-NP-Z1-9]{25,59}/', $result);
    }

    public function testGenerateEthereumAddress(): void
    {
        $result = $this->generator->generate('crypto', null, 'ethereum');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^0x[a-fA-F0-9]{40}$/', $result);
    }

    public function testGenerateRippleAddress(): void
    {
        $result = $this->generator->generate('crypto', null, 'ripple');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^r[a-km-zA-HJ-NP-Z1-9]{25,}/', $result);
    }

    public function testGenerateSolanaAddress(): void
    {
        $result = $this->generator->generate('crypto', null, 'solana');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[1-9A-HJ-NP-Za-km-z]{32,}/', $result);
    }

    public function testGenerateLitecoinAddress(): void
    {
        $result = $this->generator->generate('crypto', null, 'litecoin');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[LM][a-km-zA-HJ-NP-Z1-9]{25,33}$/', $result);
    }

    public function testGenerateDogecoinAddress(): void
    {
        $result = $this->generator->generate('crypto', null, 'dogecoin');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^D[a-km-zA-HJ-NP-Z1-9]{25,}/', $result);
    }

    public function testGenerateCardanoAddress(): void
    {
        $result = $this->generator->generate('crypto', null, 'cardano');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^addr1[a-z0-9]{50,}/', $result);
    }

    public function testGeneratePolkadotAddress(): void
    {
        $result = $this->generator->generate('crypto', null, 'polkadot');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^1[a-km-zA-HJ-NP-Z1-9]{40,}/', $result);
    }

    // ==================== Tests Banking ====================

    public function testGenerateIbanDefaultFR(): void
    {
        $result = $this->generator->generate('iban');

        $this->assertIsString($result);
        $this->assertStringStartsWith('FR', $result);
        $this->assertMatchesRegularExpression('/^FR\d{2}\s\d{5}\s\d{5}\s/', $result);
    }

    public function testGenerateIbanFR(): void
    {
        $result = $this->generator->generate('iban', null, 'FR');

        $this->assertIsString($result);
        $this->assertStringStartsWith('FR', $result);
    }

    public function testGenerateIbanDE(): void
    {
        $result = $this->generator->generate('iban', null, 'DE');

        $this->assertIsString($result);
        $this->assertStringStartsWith('DE', $result);
    }

    public function testGenerateIbanGB(): void
    {
        $result = $this->generator->generate('iban', null, 'GB');

        $this->assertIsString($result);
        $this->assertStringStartsWith('GB', $result);
    }

    public function testGenerateIbanES(): void
    {
        $result = $this->generator->generate('iban', null, 'ES');

        $this->assertIsString($result);
        $this->assertStringStartsWith('ES', $result);
    }

    public function testGenerateIbanIT(): void
    {
        $result = $this->generator->generate('iban', null, 'IT');

        $this->assertIsString($result);
        $this->assertStringStartsWith('IT', $result);
    }

    // ==================== Tests Official Numbers ====================

    public function testGenerateVin(): void
    {
        $result = $this->generator->generate('vin');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[A-HJ-NPR-Z0-9]{17}$/', $result);
        $this->assertEquals(17, strlen($result));
    }

    public function testGenerateSsnDefaultUS(): void
    {
        $result = $this->generator->generate('ssn');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{3}-\d{2}-\d{4}$/', $result);
    }

    public function testGenerateSsnUS(): void
    {
        $result = $this->generator->generate('ssn', null, 'US');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{3}-\d{2}-\d{4}$/', $result);
    }

    public function testGenerateSsnFR(): void
    {
        // SSN FR doit retourner un NIR
        $result = $this->generator->generate('ssn', null, 'FR');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[12]\s\d{2}\s\d{2}\s\d{2}\s\d{3}\s\d{3}\s\d{2}$/', $result);
    }

    public function testGenerateSsnUnknownCountry(): void
    {
        // Pays inconnu doit fallback sur US
        $result = $this->generator->generate('ssn', null, 'XX');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{3}-\d{2}-\d{4}$/', $result);
    }

    public function testGenerateNir(): void
    {
        $result = $this->generator->generate('nir');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[12]\s\d{2}\s\d{2}\s\d{2}\s\d{3}\s\d{3}\s\d{2}$/', $result);
    }

    public function testGenerateSiren(): void
    {
        $result = $this->generator->generate('siren');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{9}$/', $result);
    }

    public function testGenerateSiret(): void
    {
        $result = $this->generator->generate('siret');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{14}$/', $result);
    }

    // ==================== Tests Other Types ====================

    public function testGenerateMimeType(): void
    {
        $result = $this->generator->generate('mimetype');

        $this->assertIsString($result);
        $this->assertStringContainsString('/', $result);
    }

    public function testGenerateIsoCodeDefaultAlpha2(): void
    {
        $result = $this->generator->generate('iso');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[A-Z]{2}$/', $result);
        $this->assertEquals(2, strlen($result));
    }

    public function testGenerateIsoCodeAlpha3(): void
    {
        $result = $this->generator->generate('iso', null, 'alpha3');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[A-Z]{3}$/', $result);
        $this->assertEquals(3, strlen($result));
    }

    public function testGenerateIsoCodeNumeric(): void
    {
        $result = $this->generator->generate('iso', null, 'numeric');

        // Format numeric non supporté, retourne alpha2 par défaut
        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[A-Z]{2}$/', $result);
    }

    public function testGenerateCharset(): void
    {
        $result = $this->generator->generate('charset');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGenerateDeviceDefault(): void
    {
        $result = $this->generator->generate('device');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGenerateFullDevice(): void
    {
        $result = $this->generator->generate('fulldevice');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGenerateCurrencyDefaultCode(): void
    {
        $result = $this->generator->generate('currency');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[A-Z]{3}$/', $result);
        $this->assertEquals(3, strlen($result));
    }

    public function testGenerateCurrencySymbol(): void
    {
        $result = $this->generator->generate('currency', null, 'USD', 'symbol');

        $this->assertIsString($result);
        $this->assertEquals('$', $result);
    }

    public function testGenerateFullCurrency(): void
    {
        $result = $this->generator->generate('fullcurrency');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    // ==================== Tests JSON & Serialization ====================

    public function testGenerateJsonSimpleObject(): void
    {
        $result = $this->generator->generate('json', null, 'name: string, age: int');

        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('age', $data);
        $this->assertIsString($data['name']);
        $this->assertIsInt($data['age']);
    }

    public function testGenerateJsonSimpleArray(): void
    {
        $result = $this->generator->generate('json', null, '[int, int, int]');

        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertCount(3, $data);
        // Vérifie que c'est du JSON valide avec un array de 3 éléments
        foreach ($data as $value) {
            $this->assertTrue(is_int($value) || is_string($value), 'Each element should be int or string');
        }
    }

    public function testGenerateSerialize(): void
    {
        $result = $this->generator->generate('serialize', null, 'name: string, id: int');

        $this->assertIsString($result);
        $data = unserialize($result);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('id', $data);
    }

    // ==================== Tests Addresses Variants (pays spécifiques) ====================

    public function testGeneratePostcodeDE(): void
    {
        $result = $this->generator->generate('postcode', null, 'DE');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{5}$/', $result);
    }

    public function testGeneratePostcodeES(): void
    {
        $result = $this->generator->generate('postcode', null, 'ES');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{5}$/', $result);
    }

    public function testGeneratePostcodeIT(): void
    {
        $result = $this->generator->generate('postcode', null, 'IT');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{5}$/', $result);
    }

    public function testGeneratePostcodeUnknownCountry(): void
    {
        // Pays inconnu doit fallback sur format 5 chiffres
        $result = $this->generator->generate('postcode', null, 'XX');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^\d{5}$/', $result);
    }

    public function testGenerateStreetGB(): void
    {
        $result = $this->generator->generate('street', null, 'GB');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGenerateStreetDE(): void
    {
        $result = $this->generator->generate('street', null, 'DE');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGenerateCityGB(): void
    {
        $result = $this->generator->generate('city', null, 'GB');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGenerateCityDE(): void
    {
        $result = $this->generator->generate('city', null, 'DE');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGenerateAddressUS(): void
    {
        $result = $this->generator->generate('address', null, 'US');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGenerateFulladdressUS(): void
    {
        $result = $this->generator->generate('fulladdress', null, 'US');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    // ==================== Tests Crypto variants ====================

    public function testGenerateCryptoDefaultBTC(): void
    {
        $result = $this->generator->generate('crypto');

        $this->assertIsString($result);
        // Par défaut retourne bitcoin
        $this->assertMatchesRegularExpression('/^[13bc]/', $result);
    }

    public function testGenerateCryptoShortBTC(): void
    {
        $result = $this->generator->generate('crypto', null, 'btc');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[13bc]/', $result);
    }

    public function testGenerateCryptoShortETH(): void
    {
        $result = $this->generator->generate('crypto', null, 'eth');

        $this->assertIsString($result);
        $this->assertStringStartsWith('0x', $result);
    }

    public function testGenerateCryptoTether(): void
    {
        $result = $this->generator->generate('crypto', null, 'usdt');

        $this->assertIsString($result);
        // USDT utilise Ethereum ERC-20
        $this->assertStringStartsWith('0x', $result);
    }

    // ==================== Tests Date avec ranges ====================

    public function testGenerateDateWithMinDate(): void
    {
        $result = $this->generator->generate('date', null, 'Y-m-d', '2024-01-01');

        $this->assertIsString($result);
        $this->assertGreaterThanOrEqual('2024-01-01', $result);
    }

    public function testGenerateDateWithMaxDate(): void
    {
        // ATTENTION: quand seulement maxDate est fourni (pas minDate), le générateur utilise 2000-2038
        // Pour tester réellement maxDate, il faut fournir aussi minDate
        $result = $this->generator->generate('date', null, 'Y-m-d', '2024-01-01', '2024-12-31');

        $this->assertIsString($result);
        $this->assertGreaterThanOrEqual('2024-01-01', $result);
        $this->assertLessThanOrEqual('2024-12-31', $result);
    }

    public function testGenerateDateTimeWithRange(): void
    {
        $result = $this->generator->generate('datetime', null, 'Y-m-d H:i:s', '2024-01-01', '2024-12-31');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^2024-/', $result);
    }

    public function testGenerateDateWithInvalidDates(): void
    {
        // Dates invalides doivent fallback sur 2000-2038
        $result = $this->generator->generate('date', null, 'Y-m-d', 'INVALID_DATE', 'ALSO_INVALID');

        $this->assertIsString($result);
        // Doit être entre 2000 et 2038
        $this->assertGreaterThanOrEqual('2000-01-01', $result);
        $this->assertLessThanOrEqual('2038-01-19', $result);
    }

    public function testGenerateDateWithOnlyMinDateInvalid(): void
    {
        // MinDate invalide doit fallback sur 2000
        $result = $this->generator->generate('date', null, 'Y-m-d', 'INVALID_MIN_DATE');

        $this->assertIsString($result);
        $this->assertGreaterThanOrEqual('2000-01-01', $result);
        $this->assertLessThanOrEqual('2038-01-19', $result);
    }

    public function testGenerateMicrotimeWithRange(): void
    {
        $result = $this->generator->generate('microtime', null, '2024-01-01', '2024-12-31');

        $this->assertIsFloat($result);
        $this->assertGreaterThan(0, $result);
    }

    // ==================== Tests URL variants ====================

    public function testGenerateUrlHTTPS(): void
    {
        $result = $this->generator->generate('url', null, 'https');

        $this->assertIsString($result);
        $this->assertStringStartsWith('https://', $result);
    }

    public function testGenerateUrlFTP(): void
    {
        $result = $this->generator->generate('url', null, 'ftp');

        $this->assertIsString($result);
        $this->assertStringStartsWith('ftp://', $result);
    }

    // ==================== Tests JSON avancés ====================

    public function testGenerateJsonNestedObject(): void
    {
        $result = $this->generator->generate('json', null, 'user: {name: string, age: int}, active: boolean');

        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        // JSON nested peut avoir différentes structures
        $this->assertNotEmpty($data);
    }

    public function testGenerateJsonWithEmailType(): void
    {
        $result = $this->generator->generate('json', null, 'email: email, name: string');

        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayHasKey('name', $data);
    }

    public function testGenerateJsonWithFloatType(): void
    {
        $result = $this->generator->generate('json', null, 'price: float, quantity: int');

        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('price', $data);
        $this->assertArrayHasKey('quantity', $data);
    }

    // ==================== Tests Device options ====================

    public function testGenerateDeviceWithMobileOption(): void
    {
        $result = $this->generator->generate('device', null, 'mobile');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGenerateDeviceWithDesktopOption(): void
    {
        $result = $this->generator->generate('device', null, 'desktop');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGenerateDeviceWithSymbolOption(): void
    {
        $result = $this->generator->generate('device', null, 'symbol');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
        // Doit être un emoji device (contient au moins un caractère emoji)
        $this->assertGreaterThan(0, mb_strlen($result));
        $this->assertLessThanOrEqual(2, mb_strlen($result)); // Emoji + possiblement un variant selector
    }

    // ==================== Tests Currency options ====================

    public function testGenerateCurrencySymbolEUR(): void
    {
        $result = $this->generator->generate('currency', null, 'EUR', 'symbol');

        $this->assertIsString($result);
        $this->assertEquals('€', $result);
    }

    public function testGenerateCurrencyEUR(): void
    {
        $result = $this->generator->generate('currency', null, 'EUR');

        $this->assertIsString($result);
        $this->assertEquals('EUR', $result);
    }

    public function testGenerateFullCurrencyWithISO(): void
    {
        $result = $this->generator->generate('fullcurrency', null, 'USD');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testGenerateCurrencyWithInvalidCodeFallbackEUR(): void
    {
        // Code invalide (ni devise ni pays) doit retourner EUR par défaut
        $result = $this->generator->generate('currency', null, 'INVALID');

        $this->assertIsString($result);
        $this->assertEquals('EUR', $result);
    }

    public function testGenerateFullCurrencyWithInvalidCodeFallbackEUR(): void
    {
        // Code invalide (ni devise ni pays) doit retourner Euro par défaut
        $result = $this->generator->generate('fullcurrency', null, 'INVALID');

        $this->assertIsString($result);
        $this->assertEquals('Euro', $result);
    }

    public function testGenerateCurrencyWithCountryCodeConverted(): void
    {
        // Code pays FR doit être converti en EUR
        $result = $this->generator->generate('currency', null, 'FR');

        $this->assertIsString($result);
        $this->assertEquals('EUR', $result);
    }

    public function testGenerateFullCurrencyWithCountryCodeConverted(): void
    {
        // Code pays US doit être converti en USD puis en "US Dollar"
        $result = $this->generator->generate('fullcurrency', null, 'US');

        $this->assertIsString($result);
        $this->assertEquals('US Dollar', $result);
    }

    // ==================== Tests Slug avec texte spécial ====================

    public function testGenerateSlugWithAccents(): void
    {
        $result = $this->generator->generate('slug', null, 'Héllo Wörld');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $result);
    }

    public function testGenerateSlugWithSpecialChars(): void
    {
        $result = $this->generator->generate('slug', null, 'Test@#$%123');

        $this->assertIsString($result);
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $result);
    }

    // ==================== Tests Text avec longueurs variées ====================

    public function testGenerateTextLength10(): void
    {
        $result = $this->generator->generate('text', null, 10);

        $this->assertIsString($result);
        // Le texte peut avoir ±1 caractère à cause du trim des espaces
        $this->assertLessThanOrEqual(10, strlen($result));
        $this->assertGreaterThanOrEqual(9, strlen($result));
    }

    public function testGenerateTextLength200(): void
    {
        $result = $this->generator->generate('text', null, 200);

        $this->assertIsString($result);
        // Le texte peut avoir ±1 caractère à cause du trim des espaces
        $this->assertLessThanOrEqual(200, strlen($result));
        $this->assertGreaterThanOrEqual(199, strlen($result));
    }

    // ==================== Tests Number avec ranges variés ====================

    public function testGenerateNumberRange1000To2000(): void
    {
        $result = $this->generator->generate('number', null, 1000, 2000);

        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(1000, $result);
        $this->assertLessThanOrEqual(2000, $result);
    }

    // ==================== Tests Coordinates GPS alias ====================

    public function testGenerateCoordinatesAlias(): void
    {
        $result = $this->generator->generate('coordinates');

        $this->assertIsString($result);
        $this->assertStringContainsString(',', $result);
    }

    // ==================== Tests MIME alias ====================

    public function testGenerateMimeAlias(): void
    {
        $result = $this->generator->generate('mime');

        $this->assertIsString($result);
        $this->assertStringContainsString('/', $result);
    }

    // ==================== Tests Encoding alias ====================

    public function testGenerateEncodingAlias(): void
    {
        $result = $this->generator->generate('encoding');

        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    // ==================== Tests JSON parsing complexe pour couvrir helpers ====================

    public function testGenerateJsonComplexObject(): void
    {
        // Test pour forcer parseJsonObject et splitFields
        $result = $this->generator->generate('json', null, 'id: int, name: string, email: email, active: boolean');

        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertCount(4, $data);
    }

    public function testGenerateJsonArrayWithMultipleTypes(): void
    {
        // Test pour forcer parseJsonArray
        $result = $this->generator->generate('json', null, '[string, int, boolean, float]');

        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertCount(4, $data);
    }

    public function testGenerateJsonWithColonInValue(): void
    {
        // Test pour forcer findMainColon avec des colons dans les valeurs
        $result = $this->generator->generate('json', null, 'url: url, time: datetime');

        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
    }

    public function testGenerateJsonObjectWithBooleanType(): void
    {
        // Test pour generateJsonValue avec boolean
        $result = $this->generator->generate('json', null, 'flag1: bool, flag2: boolean');

        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertIsBool($data['flag1']);
        $this->assertIsBool($data['flag2']);
    }

    public function testGenerateJsonObjectWithFloatType(): void
    {
        // Test pour generateJsonValue avec float/double/decimal
        $result = $this->generator->generate('json', null, 'price: float, weight: double, tax: decimal');

        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertTrue(is_float($data['price']) || is_int($data['price']));
        $this->assertTrue(is_float($data['weight']) || is_int($data['weight']));
        $this->assertTrue(is_float($data['tax']) || is_int($data['tax']));
    }

    public function testGenerateJsonObjectWithIntegerVariants(): void
    {
        // Test pour generateJsonValue avec int/integer
        $result = $this->generator->generate('json', null, 'count: int, total: integer');

        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertIsInt($data['count']);
        $this->assertIsInt($data['total']);
    }

    public function testGenerateJsonWithTextType(): void
    {
        // Test pour generateJsonValue avec text
        $result = $this->generator->generate('json', null, 'description: text, comment: string');

        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertIsString($data['description']);
        $this->assertIsString($data['comment']);
    }

    public function testGenerateJsonRecursionPrevention(): void
    {
        // Test pour vérifier que json dans json retourne un word
        $result = $this->generator->generate('json', null, 'data: json, value: string');

        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        // data devrait être une string (word) pas un objet JSON
        $this->assertIsString($data['data']);
    }

    public function testGenerateJsonWithUnknownType(): void
    {
        // Test pour generateJsonValue avec type inconnu (retourne word)
        $result = $this->generator->generate('json', null, 'weird: unknown_type_xyz, normal: string');

        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertIsString($data['weird']);
        $this->assertIsString($data['normal']);
    }

    public function testGenerateSerializeComplexStructure(): void
    {
        // Test pour serialize avec structure complexe
        $result = $this->generator->generate('serialize', null, 'user: string, age: int, active: bool, score: float');

        $this->assertIsString($result);
        $data = unserialize($result);
        $this->assertIsArray($data);
        $this->assertCount(4, $data);
    }

    // ==================== Tests pour 100% couverture ====================

    public function testGenerateTimestampWithBothDates(): void
    {
        $result = $this->generator->generate('timestamp', null, '2024-01-01', '2024-12-31');
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(strtotime('2024-01-01'), $result);
        $this->assertLessThanOrEqual(strtotime('2024-12-31'), $result);
    }

    public function testGenerateTimestampWithInvalidBothDates(): void
    {
        $result = $this->generator->generate('timestamp', null, 'INVALID', 'ALSO_INVALID');
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(946684800, $result);
        $this->assertLessThanOrEqual(2147483647, $result);
    }

    public function testGenerateTimestampWithOnlyMinDate(): void
    {
        $result = $this->generator->generate('timestamp', null, '2024-01-01');
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(strtotime('2024-01-01'), $result);
    }

    public function testGenerateTimestampWithInvalidMinDate(): void
    {
        $result = $this->generator->generate('timestamp', null, 'INVALID_MIN');
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(946684800, $result);
    }

    public function testGenerateTimestampNoParams(): void
    {
        $result = $this->generator->generate('timestamp');
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(946684800, $result);
    }

    public function testGenerateCurrencyRandomWithSymbol(): void
    {
        $result = $this->generator->generate('currency', null, null, 'symbol');
        $this->assertIsString($result);
        $this->assertNotEmpty($result);
    }

    public function testIsJsonObjectWithNestedBraces(): void
    {
        // Tester avec des accolades imbriquées (doit retourner false si pas de : au niveau 0)
        $result = $this->generator->generate('json', null, '{inner:string}, other:int');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
    }

    public function testIsJsonObjectWithDepthIncrease(): void
    {
        // Tester isJsonObject avec augmentation de profondeur
        $result = $this->generator->generate('json', null, 'data:{nested:{value:string}}');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('data', $data);
    }

    public function testParseJsonObjectEmptyName(): void
    {
        // Tester parseJsonObject avec nom vide après trim
        $result = $this->generator->generate('json', null, '  :string, valid:int');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
    }

    public function testParseJsonArrayEmptyField(): void
    {
        // Tester parseJsonArray avec champ vide
        $result = $this->generator->generate('json', null, 'string, , int');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
    }

    public function testGenerateJsonValueWithNestedObject(): void
    {
        // Tester generateJsonValue avec objet imbriqué
        $result = $this->generator->generate('json', null, 'user:{name:string,age:int}');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('user', $data);
    }

    public function testGenerateJsonValueWithTypeParams(): void
    {
        // Tester generateJsonValue avec paramètres (text:100)
        $result = $this->generator->generate('json', null, 'description:text:50');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('description', $data);
    }

    public function testGenerateJsonValueWithJsonRecursion(): void
    {
        // Tester prévention récursion avec type json
        $result = $this->generator->generate('json', null, 'field:json');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
    }

    public function testGenerateJsonValueWithUnknownType(): void
    {
        // Tester generateJsonValue avec type inconnu (exception)
        $result = $this->generator->generate('json', null, 'field:unknowntype123');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
    }

    public function testFindMainColonWithNestedBraces(): void
    {
        // Tester findMainColon avec accolades imbriquées
        $result = $this->generator->generate('json', null, 'field:{inner:{deep:value}}');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
    }

    public function testFindMainColonNoColonFound(): void
    {
        // Tester findMainColon sans colon (retourne false)
        $result = $this->generator->generate('json', null, 'validfield:string, invalidfield');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('validfield', $data);
    }

    public function testGenerateJsonValueWithNumberType(): void
    {
        // Tester la conversion en int pour 'number'
        $result = $this->generator->generate('json', null, 'count:number');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertIsInt($data['count']);
    }

    public function testGenerateJsonValueWithAgeType(): void
    {
        // Tester la conversion en int pour 'age'
        $result = $this->generator->generate('json', null, 'years:age');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertIsInt($data['years']);
    }

    public function testGenerateJsonValueWithFloatConversion(): void
    {
        // Tester la conversion explicite en float (via match)
        $result = $this->generator->generate('json', null, 'ratio:float');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertTrue(is_float($data['ratio']) || is_int($data['ratio']));
    }

    public function testGenerateJsonValueWithBooleanLowercaseOriginal(): void
    {
        // Tester la conversion bool pour 'boolean' (minuscule)
        $result = $this->generator->generate('json', null, 'flag:boolean');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertIsBool($data['flag']);
    }

    public function testGenerateJsonValueWithBOOLEANUppercaseOriginal(): void
    {
        // Tester BOOLEAN retourne string 'true' ou 'false'
        $result = $this->generator->generate('json', null, 'status:BOOLEAN');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertIsString($data['status']);
        $this->assertContains($data['status'], ['true', 'false']);
    }

    public function testGenerateJsonValueDefaultStringConversion(): void
    {
        // Tester le default => (string)$result avec un type faker valide (email)
        $result = $this->generator->generate('json', null, 'contact:email');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertIsString($data['contact']);
        $this->assertStringContainsString('@', $data['contact']);
    }

    public function testGenerateJsonValueWithNameType(): void
    {
        // Tester avec un type qui retourne une string via default
        $result = $this->generator->generate('json', null, 'person:firstname');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertIsString($data['person']);
    }

    public function testParseJsonObjectWithValidNameAndType(): void
    {
        // Tester parseJsonObject avec nom valide pour couvrir la ligne $name = trim(...)
        $result = $this->generator->generate('json', null, 'username:string, email:email');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('username', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertIsString($data['username']);
        $this->assertIsString($data['email']);
    }

    public function testParseJsonObjectWithEmptyFieldContinue(): void
    {
        // Test pour couvrir la ligne 1245: continue quand field est vide
        // Format avec champs vides: "name:string, , age:number" ou espaces multiples
        $result = $this->generator->generate('json', null, 'name:string,  , age:number');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('age', $data);
        $this->assertCount(2, $data); // Seulement 2 champs, le vide est ignoré
    }

    public function testParseJsonArrayWithEmptyFields(): void
    {
        // Test pour parseJsonArray avec des champs vides (ligne 1270-1272: continue)
        // Format JSON array: ["type1", "", "type2"]
        $result = $this->generator->generate('json', null, '["string", , "number"]');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        // Les champs vides doivent être ignorés
        $this->assertCount(2, $data);
    }

    public function testGenerateJsonValueWithFloatTypeViaGenerate(): void
    {
        // Test pour ligne 1325: 'float' => (float)$result
        // Ce code est mort car 'float' est géré en amont aux lignes 1307-1309
        // Testons latitude qui retourne un float
        $result = $this->generator->generate('json', null, 'coord:latitude');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertTrue(is_numeric($data['coord']));
    }

    public function testGenerateJsonValueWithDefaultStringCase(): void
    {
        // Tester le default => (string)$result avec un type faker qui n'est ni number, ni age, ni float
        // Par exemple 'uuid' qui retourne une string
        $result = $this->generator->generate('json', null, 'id:uuid');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertIsString($data['id']);
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $data['id']);
    }

    public function testGenerateJsonValueWithBooleanLowercase(): void
    {
        // Test pour ligne 1327: 'boolean' => (bool)$result
        // Ce code est mort car 'boolean' est géré en amont
        // On ne peut pas l'atteindre, mais testons un cas similaire
        $result = $this->generator->generate('json', null, 'active:bool');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertIsBool($data['active']);
    }

    public function testGenerateJsonValueWithBooleanUppercase(): void
    {
        // Test pour BOOLEAN qui retourne string 'true'/'false'
        $result = $this->generator->generate('json', null, 'flag:BOOLEAN');
        $this->assertIsString($result);
        $data = json_decode($result, true);
        $this->assertIsArray($data);
        $this->assertIsString($data['flag']);
        $this->assertContains($data['flag'], ['true', 'false']);
    }
}
