<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Spark;
use SDF\Spark\Model;

class TimestampUser extends Model
{
    protected static string $table = 'ts_users';
    protected static bool $softDeletes = false;
}

class SoftDeleteUser extends Model
{
    protected static string $table = 'sd_users';
    protected static bool $softDeletes = true;
}

class ModelTimestampTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        Spark::connect('sqlite::memory:');
        Spark::pdo()->exec("
            CREATE TABLE ts_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                created_at TEXT,
                updated_at TEXT
            )
        ");
        Spark::pdo()->exec("
            CREATE TABLE sd_users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                created_at TEXT,
                updated_at TEXT,
                deleted_at TEXT
            )
        ");
    }

    protected function setUp(): void
    {
        Spark::connect('sqlite::memory:');
    }

    public static function tearDownAfterClass(): void
    {
        Spark::disconnect();
    }

    protected function tearDown(): void
    {
        Spark::pdo()->exec("DELETE FROM ts_users");
        Spark::pdo()->exec("DELETE FROM sd_users");
        Spark::pdo()->exec("DELETE FROM sqlite_sequence WHERE name IN ('ts_users','sd_users')");
    }

    public function test_timestamps_set_on_create(): void
    {
        $user = new TimestampUser(['name' => 'Alice']);
        $user->save();

        $this->assertNotNull($user->created_at);
        $this->assertNotNull($user->updated_at);
        $this->assertSame($user->created_at, $user->updated_at);
    }

    public function test_timestamps_update_on_save(): void
    {
        $user = new TimestampUser(['name' => 'Bob']);
        $user->save();
        $id = $user->id;
        $originalUpdated = $user->updated_at;

        $user->name = 'Robert';
        $user->save();

        $this->assertNotNull($user->updated_at);
        $reloaded = TimestampUser::find($id);
        $this->assertNotNull($reloaded);
        $this->assertSame('Robert', $reloaded->name);
    }

    public function test_find_without_soft_deletes(): void
    {
        $user = new SoftDeleteUser(['name' => 'Carol']);
        $user->save();
        $id = $user->id;

        $found = SoftDeleteUser::find($id);
        $this->assertNotNull($found);
    }

    public function test_soft_delete_sets_deleted_at(): void
    {
        $user = new SoftDeleteUser(['name' => 'Dave']);
        $user->save();
        $id = $user->id;

        $user->delete();
        $this->assertNotNull($user->deleted_at);
    }

    public function test_soft_deleted_record_hidden_from_normal_queries(): void
    {
        $user = new SoftDeleteUser(['name' => 'Eve']);
        $user->save();
        $id = $user->id;

        $user->delete();

        $found = SoftDeleteUser::find($id);
        $this->assertNull($found);
    }

    public function test_with_trashed_includes_soft_deleted(): void
    {
        $user = new SoftDeleteUser(['name' => 'Frank']);
        $user->save();
        $id = $user->id;

        $user->delete();

        $found = SoftDeleteUser::withTrashed()->where('id', $id)->first();
        $this->assertNotNull($found);
        $this->assertNotNull($found->deleted_at);
    }

    public function test_only_trashed_returns_only_deleted(): void
    {
        $alive = new SoftDeleteUser(['name' => 'Grace']);
        $alive->save();

        $deleted = new SoftDeleteUser(['name' => 'Heidi']);
        $deleted->save();
        $deletedId = $deleted->id;
        $deleted->delete();

        $trashed = SoftDeleteUser::onlyTrashed()->get();
        $this->assertCount(1, $trashed);
        $this->assertSame('Heidi', $trashed[0]->name);
    }

    public function test_trashed_returns_true_for_deleted(): void
    {
        $user = new SoftDeleteUser(['name' => 'Ivan']);
        $user->save();
        $user->delete();

        $this->assertTrue($user->trashed());
    }

    public function test_trashed_returns_false_for_active(): void
    {
        $user = new SoftDeleteUser(['name' => 'Judy']);
        $user->save();

        $this->assertFalse($user->trashed());
    }

    public function test_restore_removes_deleted_at(): void
    {
        $user = new SoftDeleteUser(['name' => 'Mallory']);
        $user->save();
        $id = $user->id;
        $user->delete();

        $user->restore();
        $this->assertNull($user->deleted_at);

        $found = SoftDeleteUser::find($id);
        $this->assertNotNull($found);
    }

    public function test_restore_on_non_deleted_returns_false(): void
    {
        $user = new SoftDeleteUser(['name' => 'Niaj']);
        $user->save();

        $this->assertFalse($user->restore());
    }

    public function test_force_delete_removes_permanently(): void
    {
        $user = new SoftDeleteUser(['name' => 'Oscar']);
        $user->save();
        $id = $user->id;

        $user->forceDelete();

        $found = SoftDeleteUser::withTrashed()->where('id', $id)->first();
        $this->assertNull($found);
    }

    public function test_soft_delete_not_applied_when_disabled(): void
    {
        $user = new TimestampUser(['name' => 'Peggy']);
        $user->save();
        $original = $user->toArray();

        $user->delete();

        $this->assertArrayNotHasKey('deleted_at', $user->toArray());
    }
}
