<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Currency;
use App\Models\Language;
use App\Models\User;
use App\Models\UserLocaleSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class UserLocaleSettingTest extends TestCase
{
    use RefreshDatabase;

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    protected function setUp(): void
    {
        parent::setUp();
        // Set explicit config values needed by factories and hashing without mocking the repository
        \Config::set('app.timezone', 'UTC');
        \Config::set('app.faker_locale', 'en_US');
        \Config::set('hashing.driver', 'bcrypt');
        \Config::set('hashing.bcrypt', []);
    }

    #[Test]
    public function testItCanCreateAUserLocaleSetting(): void
    {
        $user = User::factory()->create();
        $language = Language::factory()->create();
        $currency = Currency::factory()->create();

        $userLocaleSetting = UserLocaleSetting::factory()->create([
            'user_id' => $user->id,
            'session_id' => 'session123',
            'language_id' => $language->id,
            'currency_id' => $currency->id,
            'ip_address' => '127.0.0.1',
            'country_code' => 'US',
        ]);

        self::assertInstanceOf(UserLocaleSetting::class, $userLocaleSetting);
        self::assertSame($user->id, $userLocaleSetting->user_id);
        self::assertSame('session123', $userLocaleSetting->session_id);
        self::assertSame($language->id, $userLocaleSetting->language_id);
        self::assertSame($currency->id, $userLocaleSetting->currency_id);
        self::assertSame('127.0.0.1', $userLocaleSetting->ip_address);
        self::assertSame('US', $userLocaleSetting->country_code);
    }

    #[Test]
    public function testItCastsAttributesCorrectly(): void
    {
        $user = User::factory()->create();
        $language = Language::factory()->create();
        $currency = Currency::factory()->create();

        $userLocaleSetting = UserLocaleSetting::factory()->create([
            'user_id' => $user->id,
            'language_id' => $language->id,
            'currency_id' => $currency->id,
        ]);

        self::assertIsInt($userLocaleSetting->user_id);
        self::assertIsInt($userLocaleSetting->language_id);
        self::assertIsInt($userLocaleSetting->currency_id);
    }

    #[Test]
    public function testItBelongsToUser(): void
    {
        $user = User::factory()->create();
        $userLocaleSetting = UserLocaleSetting::factory()->create(['user_id' => $user->id]);

        self::assertInstanceOf(User::class, $userLocaleSetting->user);
        self::assertSame($user->id, $userLocaleSetting->user->id);
    }

    #[Test]
    public function testItBelongsToLanguage(): void
    {
        $language = Language::factory()->create();
        $userLocaleSetting = UserLocaleSetting::factory()->create(['language_id' => $language->id]);

        self::assertInstanceOf(Language::class, $userLocaleSetting->language);
        self::assertSame($language->id, $userLocaleSetting->language->id);
    }

    #[Test]
    public function testItBelongsToCurrency(): void
    {
        $currency = Currency::factory()->create();
        $userLocaleSetting = UserLocaleSetting::factory()->create(['currency_id' => $currency->id]);

        self::assertInstanceOf(Currency::class, $userLocaleSetting->currency);
        self::assertSame($currency->id, $userLocaleSetting->currency->id);
    }

    #[Test]
    public function testFindForUserWithUserId(): void
    {
        $user = User::factory()->create();
        $language = Language::factory()->create();
        $currency = Currency::factory()->create();

        $userLocaleSetting = UserLocaleSetting::factory()->create([
            'user_id' => $user->id,
            'language_id' => $language->id,
            'currency_id' => $currency->id,
        ]);

        $found = UserLocaleSetting::findForUser($user->id, null);

        self::assertInstanceOf(UserLocaleSetting::class, $found);
        self::assertSame($userLocaleSetting->id, $found->id);
        self::assertSame($user->id, $found->user_id);
    }

    #[Test]
    public function testFindForUserWithSessionId(): void
    {
        $sessionId = 'session123';
        $language = Language::factory()->create();
        $currency = Currency::factory()->create();

        $userLocaleSetting = UserLocaleSetting::factory()->create([
            'user_id' => null,
            'session_id' => $sessionId,
            'language_id' => $language->id,
            'currency_id' => $currency->id,
        ]);

        $found = UserLocaleSetting::findForUser(null, $sessionId);

        self::assertInstanceOf(UserLocaleSetting::class, $found);
        self::assertSame($userLocaleSetting->id, $found->id);
        self::assertSame($sessionId, $found->session_id);
    }

    #[Test]
    public function testFindForUserReturnsLatestWhenMultipleExist(): void
    {
        $user = User::factory()->create();
        $language1 = Language::factory()->create();
        $currency1 = Currency::factory()->create();
        $language2 = Language::factory()->create();
        $currency2 = Currency::factory()->create();

        // Create older setting
        $olderSetting = UserLocaleSetting::factory()->create([
            'user_id' => $user->id,
            'language_id' => $language1->id,
            'currency_id' => $currency1->id,
            'created_at' => now()->subHour(),
        ]);

        // Create newer setting
        $newerSetting = UserLocaleSetting::factory()->create([
            'user_id' => $user->id,
            'language_id' => $language2->id,
            'currency_id' => $currency2->id,
            'created_at' => now(),
        ]);

        $found = UserLocaleSetting::findForUser($user->id, null);

        self::assertSame($newerSetting->id, $found->id);
        self::assertSame($language2->id, $found->language_id);
        self::assertSame($currency2->id, $found->currency_id);
    }

    #[Test]
    public function testFindForUserReturnsNullWhenNoUserOrSession(): void
    {
        $found = UserLocaleSetting::findForUser(null, null);

        self::assertNull($found);
    }

    #[Test]
    public function testFindForUserReturnsNullWhenNoMatch(): void
    {
        $found = UserLocaleSetting::findForUser(999, 'nonexistent_session');

        self::assertNull($found);
    }

    #[Test]
    public function testUpdateOrCreateForUserWithUserId(): void
    {
        $user = User::factory()->create();
        $language = Language::factory()->create();
        $currency = Currency::factory()->create();

        $userLocaleSetting = UserLocaleSetting::updateOrCreateForUser(
            $user->id,
            null,
            $language->id,
            $currency->id,
            '127.0.0.1',
            'US'
        );

        self::assertInstanceOf(UserLocaleSetting::class, $userLocaleSetting);
        self::assertSame($user->id, $userLocaleSetting->user_id);
        self::assertNull($userLocaleSetting->session_id);
        self::assertSame($language->id, $userLocaleSetting->language_id);
        self::assertSame($currency->id, $userLocaleSetting->currency_id);
        self::assertSame('127.0.0.1', $userLocaleSetting->ip_address);
        self::assertSame('US', $userLocaleSetting->country_code);
    }

    #[Test]
    public function testUpdateOrCreateForUserWithSessionId(): void
    {
        $sessionId = 'session123';
        $language = Language::factory()->create();
        $currency = Currency::factory()->create();

        $userLocaleSetting = UserLocaleSetting::updateOrCreateForUser(
            null,
            $sessionId,
            $language->id,
            $currency->id,
            '192.168.1.1',
            'CA'
        );

        self::assertInstanceOf(UserLocaleSetting::class, $userLocaleSetting);
        self::assertNull($userLocaleSetting->user_id);
        self::assertSame($sessionId, $userLocaleSetting->session_id);
        self::assertSame($language->id, $userLocaleSetting->language_id);
        self::assertSame($currency->id, $userLocaleSetting->currency_id);
        self::assertSame('192.168.1.1', $userLocaleSetting->ip_address);
        self::assertSame('CA', $userLocaleSetting->country_code);
    }

    #[Test]
    public function testUpdateOrCreateForUserUpdatesExisting(): void
    {
        $user = User::factory()->create();
        $language1 = Language::factory()->create();
        $currency1 = Currency::factory()->create();
        $language2 = Language::factory()->create();
        $currency2 = Currency::factory()->create();

        // Create initial setting
        $initialSetting = UserLocaleSetting::updateOrCreateForUser(
            $user->id,
            null,
            $language1->id,
            $currency1->id,
            '127.0.0.1',
            'US'
        );

        // Update the setting
        $updatedSetting = UserLocaleSetting::updateOrCreateForUser(
            $user->id,
            null,
            $language2->id,
            $currency2->id,
            '192.168.1.1',
            'CA'
        );

        self::assertSame($initialSetting->id, $updatedSetting->id);
        self::assertSame($language2->id, $updatedSetting->language_id);
        self::assertSame($currency2->id, $updatedSetting->currency_id);
        self::assertSame('192.168.1.1', $updatedSetting->ip_address);
        self::assertSame('CA', $updatedSetting->country_code);
    }

    #[Test]
    public function testUpdateOrCreateForUserThrowsExceptionWithoutUserOrSession(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Either userId or sessionId must be provided');

        UserLocaleSetting::updateOrCreateForUser(
            null,
            null,
            1,
            1,
            '127.0.0.1',
            'US'
        );
    }

    #[Test]
    public function testUpdateOrCreateForUserWithMinimalParameters(): void
    {
        $user = User::factory()->create();
        $language = Language::factory()->create();
        $currency = Currency::factory()->create();

        $userLocaleSetting = UserLocaleSetting::updateOrCreateForUser(
            $user->id,
            null,
            $language->id,
            $currency->id
        );

        self::assertInstanceOf(UserLocaleSetting::class, $userLocaleSetting);
        self::assertSame($user->id, $userLocaleSetting->user_id);
        self::assertSame($language->id, $userLocaleSetting->language_id);
        self::assertSame($currency->id, $userLocaleSetting->currency_id);
        self::assertNull($userLocaleSetting->ip_address);
        self::assertNull($userLocaleSetting->country_code);
    }

    #[Test]
    public function testFactoryCreatesValidUserLocaleSetting(): void
    {
        $userLocaleSetting = UserLocaleSetting::factory()->make();

        self::assertInstanceOf(UserLocaleSetting::class, $userLocaleSetting);
        self::assertNotNull($userLocaleSetting->language_id);
        self::assertNotNull($userLocaleSetting->currency_id);
    }

    #[Test]
    public function testFillableAttributes(): void
    {
        $fillable = [
            'user_id',
            'session_id',
            'language_id',
            'currency_id',
            'ip_address',
            'country_code',
        ];

        self::assertSame($fillable, (new UserLocaleSetting())->getFillable());
    }

    #[Test]
    public function testRelationshipsWorkCorrectly(): void
    {
        $user = User::factory()->create();
        $language = Language::factory()->create();
        $currency = Currency::factory()->create();

        $userLocaleSetting = UserLocaleSetting::factory()->create([
            'user_id' => $user->id,
            'language_id' => $language->id,
            'currency_id' => $currency->id,
        ]);

        // Test user relationship
        self::assertInstanceOf(User::class, $userLocaleSetting->user);
        self::assertSame($user->id, $userLocaleSetting->user->id);

        // Test language relationship
        self::assertInstanceOf(Language::class, $userLocaleSetting->language);
        self::assertSame($language->id, $userLocaleSetting->language->id);

        // Test currency relationship
        self::assertInstanceOf(Currency::class, $userLocaleSetting->currency);
        self::assertSame($currency->id, $userLocaleSetting->currency->id);
    }

    #[Test]
    public function testCanHaveNullUserId(): void
    {
        $userLocaleSetting = UserLocaleSetting::factory()->create([
            'user_id' => null,
            'session_id' => 'session123',
        ]);

        self::assertNull($userLocaleSetting->user_id);
        self::assertSame('session123', $userLocaleSetting->session_id);
    }

    #[Test]
    public function testCanHaveNullSessionId(): void
    {
        $user = User::factory()->create();
        $userLocaleSetting = UserLocaleSetting::factory()->create([
            'user_id' => $user->id,
            'session_id' => null,
        ]);

        self::assertSame($user->id, $userLocaleSetting->user_id);
        self::assertNull($userLocaleSetting->session_id);
    }

    #[Test]
    public function testCanHaveNullIpAddress(): void
    {
        $userLocaleSetting = UserLocaleSetting::factory()->create([
            'ip_address' => null,
        ]);

        self::assertNull($userLocaleSetting->ip_address);
    }

    #[Test]
    public function testCanHaveNullCountryCode(): void
    {
        $userLocaleSetting = UserLocaleSetting::factory()->create([
            'country_code' => null,
        ]);

        self::assertNull($userLocaleSetting->country_code);
    }
}
