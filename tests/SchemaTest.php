<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Schema\Blueprint;
use SDF\Schema\ForeignKeyDefinition;
use SDF\Schema\Schema;
use SDF\Spark;

class SchemaTest extends TestCase
{
    protected function setUp(): void
    {
        Spark::connect('sqlite::memory:');
    }

    public function test_blueprint_id_column(): void
    {
        $bp = new Blueprint('test');
        $bp->id();

        $columns = $bp->getColumns();
        $this->assertCount(1, $columns);
        $this->assertSame('id', $columns[0]['name']);
        $this->assertSame('id', $columns[0]['type']);
        $this->assertFalse($columns[0]['nullable']);
        $this->assertSame('auto_increment', $columns[0]['extra']);
        $this->assertSame(['id'], $bp->getPrimaryKeys());
    }

    public function test_blueprint_custom_id_name(): void
    {
        $bp = new Blueprint('test');
        $bp->id('uuid');
        $this->assertSame('uuid', $bp->getColumns()[0]['name']);
        $this->assertSame(['uuid'], $bp->getPrimaryKeys());
    }

    public function test_blueprint_string_column(): void
    {
        $bp = new Blueprint('test');
        $bp->string('name', 100);
        $this->assertSame('name', $bp->getColumns()[0]['name']);
        $this->assertSame('string', $bp->getColumns()[0]['type']);
        $this->assertSame(100, $bp->getColumns()[0]['length']);
    }

    public function test_blueprint_string_default_length(): void
    {
        $bp = new Blueprint('test');
        $bp->string('email');
        $this->assertSame(255, $bp->getColumns()[0]['length']);
    }

    public function test_blueprint_integer_column(): void
    {
        $bp = new Blueprint('test');
        $bp->integer('age');
        $this->assertSame('integer', $bp->getColumns()[0]['type']);
    }

    public function test_blueprint_big_integer_column(): void
    {
        $bp = new Blueprint('test');
        $bp->bigInteger('views');
        $this->assertSame('bigInteger', $bp->getColumns()[0]['type']);
    }

    public function test_blueprint_boolean_column(): void
    {
        $bp = new Blueprint('test');
        $bp->boolean('active');
        $this->assertSame('boolean', $bp->getColumns()[0]['type']);
    }

    public function test_blueprint_text_column(): void
    {
        $bp = new Blueprint('test');
        $bp->text('bio');
        $this->assertSame('text', $bp->getColumns()[0]['type']);
    }

    public function test_blueprint_float_column(): void
    {
        $bp = new Blueprint('test');
        $bp->float('price', 10, 4);
        $col = $bp->getColumns()[0];
        $this->assertSame('float', $col['type']);
        $this->assertSame(10, $col['length']);
        $this->assertSame(4, $col['scale']);
    }

    public function test_blueprint_decimal_column(): void
    {
        $bp = new Blueprint('test');
        $bp->decimal('total', 12, 4);
        $col = $bp->getColumns()[0];
        $this->assertSame('decimal', $col['type']);
        $this->assertSame(12, $col['length']);
        $this->assertSame(4, $col['scale']);
    }

    public function test_blueprint_date_column(): void
    {
        $bp = new Blueprint('test');
        $bp->date('birthday');
        $this->assertSame('date', $bp->getColumns()[0]['type']);
    }

    public function test_blueprint_date_time_column(): void
    {
        $bp = new Blueprint('test');
        $bp->dateTime('created');
        $this->assertSame('dateTime', $bp->getColumns()[0]['type']);
    }

    public function test_blueprint_nullable_modifier(): void
    {
        $bp = new Blueprint('test');
        $bp->string('email')->nullable();
        $this->assertTrue($bp->getColumns()[0]['nullable']);
    }

    public function test_blueprint_default_modifier(): void
    {
        $bp = new Blueprint('test');
        $bp->integer('status')->default(1);
        $this->assertSame(1, $bp->getColumns()[0]['default']);
    }

    public function test_blueprint_unique_modifier(): void
    {
        $bp = new Blueprint('test');
        $bp->string('email')->unique();
        $this->assertTrue($bp->getColumns()[0]['unique']);
    }

    public function test_blueprint_timestamps(): void
    {
        $bp = new Blueprint('test');
        $bp->timestamps();
        $cols = $bp->getColumns();
        $this->assertCount(2, $cols);
        $this->assertSame('created_at', $cols[0]['name']);
        $this->assertSame('updated_at', $cols[1]['name']);
    }

    public function test_blueprint_soft_deletes(): void
    {
        $bp = new Blueprint('test');
        $bp->softDeletes();
        $col = $bp->getColumns()[0];
        $this->assertSame('deleted_at', $col['name']);
        $this->assertTrue($col['nullable']);
    }

    public function test_blueprint_primary_constraint(): void
    {
        $bp = new Blueprint('test');
        $bp->primary(['id', 'uuid']);
        $this->assertSame(['id', 'uuid'], $bp->getPrimaryKeys());
    }

    public function test_blueprint_foreign_key(): void
    {
        $bp = new Blueprint('test');
        $fk = $bp->foreign('user_id');
        $this->assertInstanceOf(ForeignKeyDefinition::class, $fk);
        $fk->references('id')->on('users')->onDelete('cascade');
        $def = $fk->getDefinition();
        $this->assertSame('user_id', $def['column']);
        $this->assertSame('id', $def['references']);
        $this->assertSame('users', $def['on']);
        $this->assertSame('CASCADE', $def['onDelete']);
    }

    public function test_blueprint_get_table(): void
    {
        $bp = new Blueprint('users');
        $this->assertSame('users', $bp->getTable());
    }

    public function test_schema_create_and_has_table(): void
    {
        Schema::create('test_users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->integer('age')->nullable()->default(0);
        });

        $this->assertTrue(Schema::hasTable('test_users'));
    }

    public function test_schema_has_column(): void
    {
        Schema::create('test_columns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
        });

        $this->assertTrue(Schema::hasColumn('test_columns', 'title'));
        $this->assertFalse(Schema::hasColumn('test_columns', 'nonexistent'));
    }

    public function test_schema_drop_table(): void
    {
        Schema::create('test_drop', function (Blueprint $table) {
            $table->id();
        });
        $this->assertTrue(Schema::hasTable('test_drop'));

        Schema::drop('test_drop');
        $this->assertFalse(Schema::hasTable('test_drop'));
    }

    public function test_schema_drop_if_exists(): void
    {
        Schema::create('test_drop_if', function (Blueprint $table) {
            $table->id();
        });
        Schema::dropIfExists('test_drop_if');
        $this->assertFalse(Schema::hasTable('test_drop_if'));

        Schema::dropIfExists('nonexistent_table');
        $this->assertTrue(true);
    }

    public function test_schema_table_add_column(): void
    {
        Schema::create('test_alter', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        Schema::table('test_alter', function (Blueprint $table) {
            $table->string('email')->nullable();
        });

        $this->assertTrue(Schema::hasColumn('test_alter', 'email'));
    }

    public function test_schema_create_with_timestamps(): void
    {
        Schema::create('test_ts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        $this->assertTrue(Schema::hasColumn('test_ts', 'created_at'));
        $this->assertTrue(Schema::hasColumn('test_ts', 'updated_at'));
    }

    public function test_schema_create_with_foreign_key(): void
    {
        Schema::create('test_parent', function (Blueprint $table) {
            $table->id();
        });

        Schema::create('test_child', function (Blueprint $table) {
            $table->id();
            $table->integer('parent_id');
            $table->foreign('parent_id')
                ->references('id')
                ->on('test_parent')
                ->onDelete('cascade');
        });

        $this->assertTrue(Schema::hasTable('test_parent'));
        $this->assertTrue(Schema::hasTable('test_child'));
    }

    protected function tearDown(): void
    {
        foreach (['test_users', 'test_columns', 'test_drop', 'test_drop_if', 'test_alter', 'test_ts', 'test_parent', 'test_child'] as $table) {
            if (Schema::hasTable($table)) {
                Schema::drop($table);
            }
        }
        Spark::disconnect();
    }
}
