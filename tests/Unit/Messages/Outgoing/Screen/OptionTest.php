<?php

namespace Tests\Unit\Messages\Outgoing\Screen;

use App\Messages\Outgoing\Screen\Option;
use Tests\TestCase;

class OptionTest extends TestCase
{
    public $option;

    public function setUp()
    {
        parent::setUp();

        $this->option = new Option('1', 'First Option');
    }

    public function test_it_returns_the_expected_string()
    {
        $this->assertEquals('[1] First Option', $this->option->toString());
    }

    public function test_it_formats_the_value_as_expected()
    {
        $this->assertEquals('[1]', $this->option->getValueAsString());
    }
}
