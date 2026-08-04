<?php

namespace Tests\Unit;

use App\Models\User;
use PHPUnit\Framework\TestCase;

class SedeRoleMappingTest extends TestCase
{
    public function test_super_user_is_mapped_to_superowner_for_sede_management(): void
    {
        $user = new User();
        $user->role = new \stdClass();
        $user->role->name = 'super_user';

        $this->assertSame('superowner', User::canonicalRoleName('super_user'));
        $this->assertSame('superowner', User::canonicalRoleName('Super User'));
        $this->assertSame('superowner', User::canonicalRoleName('superowner'));
        $this->assertTrue($user->isSuperowner());
        $this->assertSame('Superowner', User::displayRoleName('super_user'));
    }
}
