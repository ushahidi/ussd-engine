<?php

namespace App\Messages\Outgoing;

use BotMan\BotMan\Messages\Incoming\Answer;

interface FieldQuestionInterface
{
    public function setAnswer(Answer $answer);

    public function validate(Answer $answer);

    public function getRules(): array;

    public function getAnswerResponse(): array;
}
