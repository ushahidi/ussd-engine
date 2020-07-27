<?php

namespace Tests\Unit\Messages\Outgoing\Fields;

use App\Messages\Outgoing\Fields\Location;
use BotMan\BotMan\Messages\Incoming\Answer;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LocationTest extends TestCase
{
    protected $field;

    protected $locationQuestion;

    public function setUp()
    {
        parent::setUp();

        $this->field = [
            'id' => 1,
            'type' => 'point',
            'label' => 'Location',
            'required' => true,
        ];
        $this->locationQuestion = new Location($this->field);
    }

    public function test_it_validates_answer_is_required_if_field_indicates_so()
    {
        $this->field['required'] = true;
        $locationQuestion = new Location($this->field);

        try {
            $locationQuestion->setAnswer(Answer::create());
        } catch (\Throwable $ex) {
            $this->assertValidationError('is required', $ex);

            return;
        }
        $this->validationDidNotFailed();
    }

    public function test_it_does_not_require_an_answer_if_field_does_not_indicates_so()
    {
        $this->field['required'] = false;
        $locationQuestion = new Location($this->field);

        $locationQuestion->setAnswer(Answer::create());

        $this->assertNull($locationQuestion->getAnswerValue());
    }

    public function test_it_returns_coordinates_from_answer_as_value()
    {
        $locationQuestion = new Location($this->field);
        $latitude = -1.292066;
        $longitude = 36.821945;
        $answer = Answer::create($latitude.','.$longitude);

        $location = [
            'latitude' =>$latitude,
            'longitude' => $longitude,
        ];

        $this->assertEquals($location, $locationQuestion->getValueFromAnswer($answer));
    }
}
