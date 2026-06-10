<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Spark;
use SDF\Spark\Model;
use SDF\Spark\Paginator;

class UserMdl extends Model
{
    protected static string $table = 'users_mdl';
}

class ProfileMdl extends Model
{
    protected static string $table = 'profiles_mdl';
}

class PostMdl extends Model
{
    protected static string $table = 'posts_mdl';
}

class RoleMdl extends Model
{
    protected static string $table = 'roles_mdl';
}

class ModelRelationshipTest extends TestCase
{
    private static bool $migrated = false;

    public static function setUpBeforeClass(): void
    {
        Spark::connect('sqlite::memory:');

        if (!self::$migrated) {
            Spark::pdo()->exec("
                CREATE TABLE users_mdl (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL
                )
            ");
            Spark::pdo()->exec("
                CREATE TABLE profiles_mdl (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_mdl_id INTEGER NOT NULL,
                    bio TEXT NOT NULL
                )
            ");
            Spark::pdo()->exec("
                CREATE TABLE posts_mdl (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_mdl_id INTEGER NOT NULL,
                    title TEXT NOT NULL
                )
            ");
            Spark::pdo()->exec("
                CREATE TABLE roles_mdl (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name TEXT NOT NULL
                )
            ");
            Spark::pdo()->exec("
                CREATE TABLE role_mdl_user_mdl (
                    role_mdl_id INTEGER NOT NULL,
                    user_mdl_id INTEGER NOT NULL
                )
            ");

            Spark::pdo()->exec("INSERT INTO users_mdl (name) VALUES ('Alice')");
            Spark::pdo()->exec("INSERT INTO users_mdl (name) VALUES ('Bob')");
            Spark::pdo()->exec("INSERT INTO profiles_mdl (user_mdl_id, bio) VALUES (1, 'Alice bio')");
            Spark::pdo()->exec("INSERT INTO posts_mdl (user_mdl_id, title) VALUES (1, 'Post 1')");
            Spark::pdo()->exec("INSERT INTO posts_mdl (user_mdl_id, title) VALUES (1, 'Post 2')");
            Spark::pdo()->exec("INSERT INTO posts_mdl (user_mdl_id, title) VALUES (2, 'Post 3')");
            Spark::pdo()->exec("INSERT INTO roles_mdl (name) VALUES ('Admin')");
            Spark::pdo()->exec("INSERT INTO roles_mdl (name) VALUES ('Editor')");
            Spark::pdo()->exec("INSERT INTO role_mdl_user_mdl (role_mdl_id, user_mdl_id) VALUES (1, 1)");
            Spark::pdo()->exec("INSERT INTO role_mdl_user_mdl (role_mdl_id, user_mdl_id) VALUES (2, 1)");

            self::$migrated = true;
        }
    }

    protected function setUp(): void
    {
        Spark::connect('sqlite::memory:');
    }

    public static function tearDownAfterClass(): void
    {
        Spark::disconnect();
    }

    public function test_has_one_returns_related(): void
    {
        $user = UserMdl::find(1);
        $this->assertNotNull($user);

        $profile = $user->hasOne(ProfileMdl::class);
        $this->assertNotNull($profile);
        $this->assertSame('Alice bio', $profile->bio);
    }

    public function test_has_one_returns_null_when_no_related(): void
    {
        $user = UserMdl::find(2);
        $this->assertNotNull($user);

        $profile = $user->hasOne(ProfileMdl::class);
        $this->assertNull($profile);
    }

    public function test_has_many_returns_collection(): void
    {
        $user = UserMdl::find(1);
        $this->assertNotNull($user);

        $posts = $user->hasMany(PostMdl::class);
        $this->assertCount(2, $posts);
    }

    public function test_has_many_returns_empty_when_none(): void
    {
        $user = new UserMdl(['id' => 999], false);
        $user->fill(['id' => 999]);

        $posts = $user->hasMany(PostMdl::class);
        $this->assertEmpty($posts);
    }

    public function test_belongs_to_returns_parent(): void
    {
        $post = PostMdl::find(1);
        $this->assertNotNull($post);

        $user = $post->belongsTo(UserMdl::class);
        $this->assertNotNull($user);
        $this->assertSame('Alice', $user->name);
    }

    public function test_belongs_to_returns_null_when_no_parent(): void
    {
        $post = new PostMdl(['user_id' => 999], false);
        $post->fill(['user_id' => 999]);

        try {
            $user = $post->belongsTo(UserMdl::class);
            $this->assertNull($user);
        } catch (\PDOException $e) {
            throw new \Exception(
                $e->getMessage() . "\nSQL: " . ($e->getPrevious()?->getMessage() ?? 'N/A'),
                $e->getCode(),
                $e
            );
        }
    }

    public function test_belongs_to_many_returns_related(): void
    {
        $user = UserMdl::find(1);
        $this->assertNotNull($user);

        $roles = $user->belongsToMany(RoleMdl::class);
        $this->assertCount(2, $roles);
        $names = array_map(fn ($r) => $r->name, $roles);
        $this->assertContains('Admin', $names);
        $this->assertContains('Editor', $names);
    }

    public function test_belongs_to_many_returns_empty_when_no_relation(): void
    {
        $user = UserMdl::find(2);
        $this->assertNotNull($user);

        $roles = $user->belongsToMany(RoleMdl::class);
        $this->assertEmpty($roles);
    }

    public function test_paginate_returns_paginator(): void
    {
        $result = UserMdl::query()->paginate(1, 1);
        $this->assertInstanceOf(Paginator::class, $result);
        $this->assertCount(1, $result->items());
        $this->assertSame(2, $result->total());
        $this->assertSame(1, $result->perPage());
        $this->assertSame(1, $result->currentPage());
        $this->assertTrue($result->hasMore());
    }

    public function test_paginate_second_page(): void
    {
        $result = UserMdl::query()->paginate(1, 2);
        $this->assertCount(1, $result->items());
        $this->assertSame(2, $result->currentPage());
        $this->assertFalse($result->hasMore());
    }

    public function test_paginate_with_wheres(): void
    {
        $result = UserMdl::query()->where('name', 'Alice')->paginate(15, 1);
        $this->assertCount(1, $result->items());
        $this->assertSame('Alice', $result->items()[0]->name);
        $this->assertSame(1, $result->total());
    }
}
