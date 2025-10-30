<?php

declare(strict_types=1);

namespace Tests\Security;

use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class DataEncryptionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    public function testDataEncryption(): void
    {
        $data = 'test data';
        $encrypted = Crypt::encrypt($data);
        self::assertNotSame($data, $encrypted);
        self::assertSame($data, Crypt::decrypt($encrypted));
    }

    public function testEncryptionKeys(): void
    {
        $data = 'secret';
        $encrypted1 = Crypt::encrypt($data);
        $encrypted2 = Crypt::encrypt($data);
        self::assertNotSame($encrypted1, $encrypted2); // Different each time
        self::assertSame($data, Crypt::decrypt($encrypted1));
        self::assertSame($data, Crypt::decrypt($encrypted2));
    }

    public function testDecryptionWorks(): void
    {
        $data = 'another test';
        $encrypted = Crypt::encrypt($data);
        $decrypted = Crypt::decrypt($encrypted);
        self::assertSame($data, $decrypted);
    }
}
