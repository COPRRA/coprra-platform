<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\UserBanService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @internal
 *
 * @coversNothing
 */
final class UserBanServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserBanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserBanService();
    }

    protected function tearDown(): void
    {
        \Mockery::close();
        parent::tearDown();
    }

    public function testCanBeInstantiated()
    {
        // Act & Assert
        self::assertInstanceOf(UserBanService::class, $this->service);
    }

    public function testChecksUserIsNotBannedByDefault()
    {
        // Arrange
        $user = $this->createUser();

        // Act
        $result = $this->service->isUserBanned($user);

        // Assert
        self::assertFalse($result);
    }

    public function testHandlesBanUserWithValidReason()
    {
        // Arrange
        $user = $this->createUser();
        $reason = 'violation';
        $duration = 7; // days

        // Act
        $result = $this->service->banUser($user, $reason, null, now()->addDays($duration));

        // Assert
        self::assertTrue($result);
    }

    public function testHandlesBanUserWithInvalidReason()
    {
        // Arrange
        $user = $this->createUser();
        $reason = ''; // Empty reason
        $duration = 7;

        // Act
        $result = $this->service->banUser($user, $reason, null, now()->addDays($duration));

        // Assert
        self::assertFalse($result);
    }

    public function testHandlesPermanentBan()
    {
        // Arrange
        $user = $this->createUser();
        $reason = 'security';
        $duration = null; // Permanent ban

        // Act
        $result = $this->service->banUser($user, $reason, null, now()->addDays($duration));

        // Assert
        self::assertTrue($result);
    }

    public function testHandlesUnbanUser()
    {
        // Arrange
        $user = $this->createUser();

        // Act
        $result = $this->service->unbanUser($user);

        // Assert
        self::assertTrue($result);
    }

    public function testGetsBanInfoForUser()
    {
        // Arrange
        $user = $this->createUser();

        // Act
        $result = $this->service->getBanInfo($user);

        // Assert
        self::assertNull($result); // No ban info by default
    }

    public function testHandlesAutoUnbanExpiredUser()
    {
        // Arrange
        $user = $this->createUser();

        // Act
        $result = $this->service->cleanupExpiredBans();

        // Assert
        self::assertIsInt($result);
    }

    public function testGetsBannedUsers()
    {
        // Act
        $result = $this->service->getBannedUsers();

        // Assert
        self::assertInstanceOf(Collection::class, $result);
    }

    public function testGetsUsersWithExpiredBans()
    {
        // Act
        $result = $this->service->getUsersWithExpiredBans();

        // Assert
        self::assertInstanceOf(Collection::class, $result);
    }

    public function testCleansUpExpiredBans()
    {
        // Act
        $result = $this->service->cleanupExpiredBans();

        // Assert
        self::assertIsInt($result);
        self::assertGreaterThanOrEqual(0, $result);
    }

    public function testGetsBanStatistics()
    {
        // Act
        $result = $this->service->getBanStatistics();

        // Assert
        self::assertIsArray($result);
        self::assertArrayHasKey('total_banned', $result);
        self::assertArrayHasKey('permanent_bans', $result);
        self::assertArrayHasKey('temporary_bans', $result);
        self::assertArrayHasKey('expired_bans', $result);
    }

    public function testGetsBanReasons()
    {
        // Act
        $result = $this->service->getBanReasons();

        // Assert
        self::assertIsArray($result);
    }

    public function testChecksCanBanUser()
    {
        // Arrange
        $user = $this->createUser();

        // Act
        $result = $this->service->canBanUser($user);

        // Assert
        self::assertTrue($result);
    }

    public function testChecksCanUnbanUser()
    {
        // Arrange
        $user = $this->createUser();

        // Act
        $result = $this->service->canUnbanUser($user);

        // Assert
        self::assertFalse($result);
    }

    public function testGetsBanHistory()
    {
        // Arrange
        $user = $this->createUser();

        // Act
        $result = $this->service->getBanHistory($user);

        // Assert
        self::assertIsArray($result);
    }

    public function testHandlesExtendBan()
    {
        // Arrange
        $user = $this->createUser();
        $additionalDays = now()->addDays(7);

        // Act
        $result = $this->service->extendBan($user, $additionalDays);

        // Assert
        self::assertFalse($result);
    }

    public function testHandlesReduceBan()
    {
        // Arrange
        $user = $this->createUser();
        $reduceDays = now()->addDays(3);

        // Act
        $result = $this->service->reduceBan($user, $reduceDays);

        // Assert
        self::assertFalse($result);
    }

    private function createUser(int $id = 1, bool $isBlocked = false): User
    {
        $user = new User();
        $user->id = $id;
        $user->name = 'Test User';
        $user->email = 'test@example.com';
        $user->password = 'password';
        $user->is_blocked = $isBlocked;
        $user->ban_reason = null;
        $user->ban_description = null;
        $user->ban_expires_at = null;

        return $user;
    }
}
