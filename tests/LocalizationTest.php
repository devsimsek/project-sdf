<?php

namespace Tests;

use PHPUnit\Framework\TestCase;
use SDF\Core;
use SDF\Localization\Translator;

class LocalizationTest extends TestCase
{
    private Translator $translator;

    protected function setUp(): void
    {
        Translator::reset();
        $this->translator = new Translator('en', 'en');
    }

    public function test_get_returns_key_when_not_found(): void
    {
        $this->assertSame('messages.nonexistent', $this->translator->get('messages.nonexistent'));
    }

    public function test_get_returns_loaded_translation(): void
    {
        $this->translator->set('messages.hello', 'Hello World');
        $this->assertSame('Hello World', $this->translator->get('messages.hello'));
    }

    public function test_get_with_parameter_replacement(): void
    {
        $this->translator->set('greeting.welcome', 'Welcome :name!');
        $this->assertSame('Welcome John!', $this->translator->get('greeting.welcome', ['name' => 'John']));
    }

    public function test_get_with_locale(): void
    {
        $this->translator->set('messages.hello', 'Hello', 'en');
        $this->translator->set('messages.hello', 'Merhaba', 'tr');
        $this->assertSame('Merhaba', $this->translator->get('messages.hello', [], 'tr'));
        $this->assertSame('Hello', $this->translator->get('messages.hello', [], 'en'));
    }

    public function test_set_locale(): void
    {
        $this->translator->setLocale('tr');
        $this->assertSame('tr', $this->translator->getLocale());
    }

    public function test_get_locale(): void
    {
        $this->assertSame('en', $this->translator->getLocale());
    }

    public function test_fallback_locale(): void
    {
        $translator = new Translator('de', 'en');
        $translator->set('messages.hello', 'Hello', 'en');
        $this->assertSame('Hello', $translator->get('messages.hello'));
    }

    public function test_has_returns_true_when_key_exists(): void
    {
        $this->translator->set('messages.hello', 'Hello');
        $this->assertTrue($this->translator->has('messages.hello'));
    }

    public function test_has_returns_false_when_key_missing(): void
    {
        $this->assertFalse($this->translator->has('messages.nonexistent'));
    }

    public function test_choice_singular(): void
    {
        $this->translator->set('app.apples', '{0} none|{1} one|{2,10} some|[11,*] many');
        $this->assertSame('one', $this->translator->choice('app.apples', 1));
    }

    public function test_choice_plural(): void
    {
        $this->translator->set('app.apples', '{0} none|{1} one|{2,10} some|[11,*] many');
        $this->assertSame('some', $this->translator->choice('app.apples', 5));
    }

    public function test_choice_zero(): void
    {
        $this->translator->set('app.apples', '{0} none|{1} one|{2,10} some');
        $this->assertSame('none', $this->translator->choice('app.apples', 0));
    }

    public function test_choice_many(): void
    {
        $this->translator->set('app.apples', '{0} none|{1,10} some|{11,*} many');
        $this->assertSame('many', $this->translator->choice('app.apples', 20));
    }

    public function test_choice_simple_pipe(): void
    {
        $this->translator->set('app.apples', 'one|other');
        $this->assertSame('one', $this->translator->choice('app.apples', 1));
        $this->assertSame('other', $this->translator->choice('app.apples', 2));
    }

    public function test_choice_with_replacement(): void
    {
        $this->translator->set('app.items', '{0} empty|{1,*} :count items');
        $this->assertSame('5 items', $this->translator->choice('app.items', 5, [':count' => '5']));
    }

    public function test_choice_fallback_to_key(): void
    {
        $this->assertSame('nonexistent.key', $this->translator->choice('nonexistent.key', 1));
    }

    public function test_singleton_instance(): void
    {
        $instance = Translator::getInstance('en', 'en');
        $this->assertInstanceOf(Translator::class, $instance);
        $this->assertSame($instance, Translator::getInstance());
    }

    public function test_reset(): void
    {
        Translator::getInstance('en', 'en');
        Translator::reset();
        $instance = Translator::getInstance();
        $this->assertNotSame($instance->getLocale(), '');
    }

    public function test_multiple_parameter_replacement(): void
    {
        $this->translator->set('mail.greeting', 'Hello :name, your :item is ready');
        $result = $this->translator->get('mail.greeting', ['name' => 'Alice', 'item' => 'report']);
        $this->assertSame('Hello Alice, your report is ready', $result);
    }

    public function test_get_with_dot_notation_nested(): void
    {
        $this->translator->set('nav.menu.profile', 'My Profile');
        $this->assertSame('My Profile', $this->translator->get('nav.menu.profile'));
    }
}
