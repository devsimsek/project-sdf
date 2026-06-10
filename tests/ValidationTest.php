<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Validation\Validator;

class ValidationTest extends TestCase
{
    public function test_required_passes(): void
    {
        $v = Validator::make(['name' => 'John'], ['name' => 'required']);
        $this->assertTrue($v->validate());
    }

    public function test_required_fails(): void
    {
        $v = Validator::make(['name' => ''], ['name' => 'required']);
        $this->assertFalse($v->validate());
        $this->assertArrayHasKey('name', $v->errors());
    }

    public function test_required_with_null_value(): void
    {
        $v = Validator::make(['name' => null], ['name' => 'required']);
        $this->assertFalse($v->validate());
    }

    public function test_required_with_missing_field(): void
    {
        $v = Validator::make([], ['name' => 'required']);
        $this->assertFalse($v->validate());
    }

    public function test_email_passes(): void
    {
        $v = Validator::make(['email' => 'user@example.com'], ['email' => 'email']);
        $this->assertTrue($v->validate());
    }

    public function test_email_fails(): void
    {
        $v = Validator::make(['email' => 'not-an-email'], ['email' => 'email']);
        $this->assertFalse($v->validate());
    }

    public function test_min_string_passes(): void
    {
        $v = Validator::make(['name' => 'Hello'], ['name' => 'min:3']);
        $this->assertTrue($v->validate());
    }

    public function test_min_string_fails(): void
    {
        $v = Validator::make(['name' => 'Hi'], ['name' => 'min:3']);
        $this->assertFalse($v->validate());
    }

    public function test_max_string_passes(): void
    {
        $v = Validator::make(['name' => 'Hi'], ['name' => 'max:5']);
        $this->assertTrue($v->validate());
    }

    public function test_max_string_fails(): void
    {
        $v = Validator::make(['name' => 'TooLongName'], ['name' => 'max:5']);
        $this->assertFalse($v->validate());
    }

    public function test_numeric_passes(): void
    {
        $v = Validator::make(['age' => '25'], ['age' => 'numeric']);
        $this->assertTrue($v->validate());
    }

    public function test_numeric_fails(): void
    {
        $v = Validator::make(['age' => 'abc'], ['age' => 'numeric']);
        $this->assertFalse($v->validate());
    }

    public function test_integer_passes(): void
    {
        $v = Validator::make(['count' => '42'], ['count' => 'integer']);
        $this->assertTrue($v->validate());
    }

    public function test_integer_fails(): void
    {
        $v = Validator::make(['count' => '12.5'], ['count' => 'integer']);
        $this->assertFalse($v->validate());
    }

    public function test_string_passes(): void
    {
        $v = Validator::make(['text' => 'hello'], ['text' => 'string']);
        $this->assertTrue($v->validate());
    }

    public function test_string_fails_for_array(): void
    {
        $v = Validator::make(['text' => [1, 2]], ['text' => 'string']);
        $this->assertFalse($v->validate());
    }

    public function test_boolean_passes(): void
    {
        $v = Validator::make(['active' => true], ['active' => 'boolean']);
        $this->assertTrue($v->validate());
    }

    public function test_boolean_accepts_string_one(): void
    {
        $v = Validator::make(['active' => '1'], ['active' => 'boolean']);
        $this->assertTrue($v->validate());
    }

    public function test_array_passes(): void
    {
        $v = Validator::make(['items' => [1, 2]], ['items' => 'array']);
        $this->assertTrue($v->validate());
    }

    public function test_array_fails(): void
    {
        $v = Validator::make(['items' => 'string'], ['items' => 'array']);
        $this->assertFalse($v->validate());
    }

    public function test_alpha_passes(): void
    {
        $v = Validator::make(['name' => 'John'], ['name' => 'alpha']);
        $this->assertTrue($v->validate());
    }

    public function test_alpha_fails_with_numbers(): void
    {
        $v = Validator::make(['name' => 'John123'], ['name' => 'alpha']);
        $this->assertFalse($v->validate());
    }

    public function test_alpha_num_passes(): void
    {
        $v = Validator::make(['name' => 'John123'], ['name' => 'alpha_num']);
        $this->assertTrue($v->validate());
    }

    public function test_url_passes(): void
    {
        $v = Validator::make(['site' => 'https://example.com'], ['site' => 'url']);
        $this->assertTrue($v->validate());
    }

    public function test_url_fails(): void
    {
        $v = Validator::make(['site' => 'not-a-url'], ['site' => 'url']);
        $this->assertFalse($v->validate());
    }

    public function test_in_passes(): void
    {
        $v = Validator::make(['role' => 'admin'], ['role' => 'in:admin,user,guest']);
        $this->assertTrue($v->validate());
    }

    public function test_in_fails(): void
    {
        $v = Validator::make(['role' => 'superadmin'], ['role' => 'in:admin,user,guest']);
        $this->assertFalse($v->validate());
    }

    public function test_confirmed_passes(): void
    {
        $v = Validator::make([
            'password' => 'secret',
            'password_confirmation' => 'secret',
        ], ['password' => 'confirmed']);
        $this->assertTrue($v->validate());
    }

    public function test_confirmed_fails(): void
    {
        $v = Validator::make([
            'password' => 'secret',
            'password_confirmation' => 'different',
        ], ['password' => 'confirmed']);
        $this->assertFalse($v->validate());
    }

    public function test_same_passes(): void
    {
        $v = Validator::make([
            'a' => 'value',
            'b' => 'value',
        ], ['a' => 'same:b']);
        $this->assertTrue($v->validate());
    }

    public function test_different_passes(): void
    {
        $v = Validator::make([
            'a' => 'value1',
            'b' => 'value2',
        ], ['a' => 'different:b']);
        $this->assertTrue($v->validate());
    }

    public function test_regex_passes(): void
    {
        $v = Validator::make(['phone' => '555-1234'], ['phone' => 'regex:/^\d{3}-\d{4}$/']);
        $this->assertTrue($v->validate());
    }

    public function test_regex_fails(): void
    {
        $v = Validator::make(['phone' => 'abc'], ['phone' => 'regex:/^\d{3}-\d{4}$/']);
        $this->assertFalse($v->validate());
    }

    public function test_between_string_passes(): void
    {
        $v = Validator::make(['name' => 'Hello'], ['name' => 'between:3,10']);
        $this->assertTrue($v->validate());
    }

    public function test_between_string_fails_below(): void
    {
        $v = Validator::make(['name' => 'Hi'], ['name' => 'between:3,10']);
        $this->assertFalse($v->validate());
    }

    public function test_between_string_fails_above(): void
    {
        $v = Validator::make(['name' => 'ThisIsWayTooLong'], ['name' => 'between:3,10']);
        $this->assertFalse($v->validate());
    }

    public function test_nullable_allows_missing(): void
    {
        $v = Validator::make([], ['email' => 'nullable|email']);
        $this->assertTrue($v->validate());
    }

    public function test_nullable_allows_null(): void
    {
        $v = Validator::make(['email' => null], ['email' => 'nullable|email']);
        $this->assertTrue($v->validate());
    }

    public function test_nullable_still_validates_non_null(): void
    {
        $v = Validator::make(['email' => 'not-email'], ['email' => 'nullable|email']);
        $this->assertFalse($v->validate());
    }

    public function test_multiple_rules_on_one_field(): void
    {
        $v = Validator::make(['name' => ''], ['name' => 'required|string|min:3']);
        $this->assertFalse($v->validate());
    }

    public function test_passes_on_valid_data(): void
    {
        $v = Validator::make(['name' => 'John'], ['name' => 'required|string|min:2|max:50']);
        $this->assertTrue($v->validate());
        $this->assertTrue($v->passes());
        $this->assertFalse($v->fails());
    }

    public function test_errors_returns_array(): void
    {
        $v = Validator::make(['name' => ''], ['name' => 'required']);
        $v->validate();
        $errors = $v->errors();
        $this->assertIsArray($errors);
        $this->assertCount(1, $errors['name']);
    }

    public function test_custom_message(): void
    {
        $v = Validator::make(['name' => ''], ['name' => 'required']);
        $v->setMessages(['name.required' => 'Name is missing!']);
        $v->validate();
        $this->assertSame('Name is missing!', $v->errors()['name'][0]);
    }

    public function test_field_alias_in_message(): void
    {
        $v = Validator::make(['user_email' => ''], ['user_email' => 'required|email']);
        $v->setAliases(['user_email' => 'email address']);
        $v->validate();
        $msg = $v->errors()['user_email'][0];
        $this->assertStringContainsString('email address', $msg);
    }

    public function test_custom_rule_added(): void
    {
        $v = Validator::make(['value' => 'hello'], ['value' => 'custom_rule']);
        $v->addRule('custom_rule', fn ($value) => $value === 'hello');
        $this->assertTrue($v->validate());
    }

    public function test_custom_rule_fails(): void
    {
        $v = Validator::make(['value' => 'world'], ['value' => 'custom_rule']);
        $v->addRule('custom_rule', fn ($value) => $value === 'hello');
        $this->assertFalse($v->validate());
    }

    public function test_make_static_helper(): void
    {
        $v = Validator::make(['x' => 'hello'], ['x' => 'required|string']);
        $this->assertInstanceOf(Validator::class, $v);
        $this->assertTrue($v->validate());
    }

    public function test_min_on_numeric(): void
    {
        $v = Validator::make(['age' => 18], ['age' => 'numeric|min:18']);
        $this->assertTrue($v->validate());
    }

    public function test_max_on_numeric(): void
    {
        $v = Validator::make(['age' => 100], ['age' => 'numeric|max:99']);
        $this->assertFalse($v->validate());
    }

    public function test_errors_empty_when_valid(): void
    {
        $v = Validator::make(['name' => 'John'], ['name' => 'required']);
        $v->validate();
        $this->assertEmpty($v->errors());
    }
}
