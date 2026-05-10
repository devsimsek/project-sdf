<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Scope;

/**
 * Unit tests for Scope constants.
 */
class ScopeTest extends TestCase
{
    public function test_scope_controller_constant(): void
    {
        $this->assertSame('controller', Scope::Controller);
    }

    public function test_scope_helper_constant(): void
    {
        $this->assertSame('helper', Scope::Helper);
    }

    public function test_scope_global_constant(): void
    {
        $this->assertSame('global', Scope::Global);
    }

    public function test_scope_system_constant(): void
    {
        $this->assertSame('system', Scope::System);
    }

    public function test_scope_view_constant(): void
    {
        $this->assertSame('view', Scope::View);
    }
}
