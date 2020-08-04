<?php

namespace App\Messages\Outgoing\Screen;

use App\Messages\Outgoing\QuestionScreen;
use Exception;

class PageContentBuilder
{
    protected $textArray = [];
    protected $pageOptions = [];
    protected $pageOptionsValues = [];
    protected $hasNextPageOption = false;
    protected $availableCharactersCount = QuestionScreen::MAX_CHARACTERS_PER_PAGE;

    public function appendText(string $text)
    {
        $this->availableCharactersCount -= strlen($text);
        $this->textArray[] = $text;
    }

    public function appendPageOption(Option $option)
    {
        $text = $option->getText();
        $this->availableCharactersCount -= strlen($text);

        $this->pageOptions[] = $text;
        $this->pageOptionsValues[] = $option->value;
    }

    public function appendNextPageOption()
    {
        if (! $this->hasNextPageOption) {
            $this->appendPageOption(self::getNextOption());
            $this->hasNextPageOption = true;
        }
    }

    public function hasNextPageOption(): bool
    {
        return $this->hasNextPageOption;
    }

    public function appendPreviousPageOption()
    {
        $this->appendPageOption(self::getPreviousOption());
    }

    public function getAvailableCharactersCount(bool $isLastPiece = true): int
    {
        return $this->availableCharactersCount;
    }

    public function canAppendText(string $text, bool $isLastPiece = true)
    {
        $textLength = strlen($text);

        if ($isLastPiece) {
            return $textLength <= $this->availableCharactersCount;
        }

        $reservedCharactersCount = strlen(self::getNextOption()->getText());

        return $textLength <= $this->availableCharactersCount - $reservedCharactersCount;
    }

    public function getPageContent()
    {
        return implode($this->textArray).implode($this->pageOptions);
    }

    public function getPageOptionsValues()
    {
        return $this->pageOptionsValues;
    }

    public static function getNextOption()
    {
        return new Option(__('conversation.screen.next.value'), __('conversation.screen.next.text'));
    }

    public static function getPreviousOption()
    {
        return new Option(__('conversation.screen.previous.value'), __('conversation.screen.previous.text'));
    }
}
