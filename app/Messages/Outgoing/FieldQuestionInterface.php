<?php

namespace App\Messages\Outgoing;

use BotMan\BotMan\Messages\Incoming\Answer;

interface FieldQuestionInterface
{
    public function getTextContent(): string;

    public function getMoreInfoContent(): string;

    public function setAnswer(Answer $answer);

    public function validate(array $answer);

    public function getRules(): array;

    public function getAnswerBody(Answer $answer): array;

    public function getAnswerResponse(): array;

    public function hasHints(): bool;

    public function getHints(): string;
}
