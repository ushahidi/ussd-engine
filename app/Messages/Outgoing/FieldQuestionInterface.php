<?php

namespace App\Messages\Outgoing;

use BotMan\BotMan\Messages\Incoming\Answer;

interface FieldQuestionInterface
{
    public function getTextContent(): string;

    public function getMoreInfoContent(): string;

    public function setAnswer(Answer $answer);

    public function validate(Answer $answer);

    public function getRules(): array;

    public function getValueFromAnswer(Answer $answer);

    public function toPayload(): array;

    public function hasHints(): bool;

    public function getHints(): string;

    public function isRequired(): bool;
}
