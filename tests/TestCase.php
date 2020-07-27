<?php

namespace Tests;

use App\BotManTester;
use BotMan\BotMan\BotMan;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Validation\ValidationException;
use Throwable;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * @var BotMan
     */
    protected $botman;

    /**
     * @var BotManTester
     */
    protected $bot;

    protected function getFirstErrorMessage(ValidationException $exception)
    {
        $errors = $exception->validator->errors()->all();

        return count($errors) ? $errors[0] : null;
    }

    /**
     * Assert the exception is a validation exception and contains provided string
     * as part of the error.
     *
     * @param string $error
     * @param ValidationException $exception
     * @return void
     */
    protected function assertValidationError(string $error, Throwable $exception)
    {
        $this->assertInstanceOf(ValidationException::class, $exception);
        $this->assertStringContainsString($error, $this->getFirstErrorMessage($exception));
    }
}
