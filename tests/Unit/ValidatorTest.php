<?php declare(strict_types=1);

namespace Tests\Unit;

use Concept\Extensions\ValidationRakit\Validator;
use PHPUnit\Framework\TestCase;
use Tests\Support\StartsWithRule;

final class ValidatorTest extends TestCase
{
    private Validator $validator;

    protected function setUp(): void
    {
        $this->validator = new Validator();
    }

    public function testMakePassesForValidData(): void
    {
        $validation = $this->validator->make(
            ['email' => 'user@example.com', 'name' => 'Alice'],
            [
                'email' => 'required|email',
                'name' => 'required',
            ],
        );
        $validation->validate();

        $this->assertTrue($validation->isValid());
        $this->assertSame(
            ['email' => 'user@example.com', 'name' => 'Alice'],
            $validation->getValidData(),
        );
        $this->assertSame([], $validation->getErrors());
    }

    public function testMakeFailsForInvalidData(): void
    {
        $validation = $this->validator->make(
            ['email' => 'not-an-email'],
            ['email' => 'required|email'],
        );
        $validation->validate();

        $this->assertFalse($validation->isValid());
        $this->assertArrayHasKey('email', $validation->getErrors());
        $this->assertSame([], $validation->getValidData());
    }

    public function testValidateRunsValidationWithoutChangingOutcome(): void
    {
        $validation = $this->validator->make(
            ['name' => ''],
            ['name' => 'required'],
        );

        $validation->validate();

        $this->assertFalse($validation->isValid());
        $this->assertArrayHasKey('name', $validation->getErrors());
    }

    public function testAddRulesRegistersCustomRule(): void
    {
        $this->validator->addRules(['starts_with' => new StartsWithRule()]);

        $valid = $this->validator->make(
            ['code' => 'ABC-123'],
            ['code' => 'required|starts_with:ABC'],
        );
        $invalid = $this->validator->make(
            ['code' => 'XYZ-123'],
            ['code' => 'required|starts_with:ABC'],
        );
        $valid->validate();
        $invalid->validate();

        $this->assertTrue($valid->isValid());
        $this->assertFalse($invalid->isValid());
        $this->assertArrayHasKey('code', $invalid->getErrors());
    }

    public function testSetAliasesMessagesAndTranslationsAreForwarded(): void
    {
        $validation = $this->validator->make(
            ['email' => 'bad'],
            ['email' => 'required|email'],
        );

        $validation->setAliases(['email' => 'E-mail']);
        $validation->setMessages(['email:email' => 'Invalid e-mail address.']);
        $validation->setTranslations([]);
        $validation->validate();

        $this->assertFalse($validation->isValid());
        $this->assertArrayHasKey('email', $validation->getErrors());
    }
}
