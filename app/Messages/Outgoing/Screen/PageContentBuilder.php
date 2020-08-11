<?php

namespace App\Messages\Outgoing\Screen;

/**
 * Encapsulates the part proccess of adding content to a Page dynamically.
 */
class PageContentBuilder
{
    protected $textArray = [];

    protected $pageOptions = [];

    protected $pageOptionsValues = [];

    protected $hasNextPageOption = false;

    /**
     * Available characters count to use when checking it text will fit.
     * @var int
     */
    protected $availableCharactersCount;

    public function __construct()
    {
        $this->availableCharactersCount = (int) config('ussd.max_characters_per_page');
    }

    public function appendText(string $text): void
    {
        $this->availableCharactersCount -= strlen($text);
        $this->textArray[] = $text;
    }

    public function appendPageOption(Option $option): void
    {
        $text = $option->getText();
        $this->availableCharactersCount -= strlen($text);

        $this->pageOptions[] = $text;
        $this->pageOptionsValues[] = $option->value;
    }

    public function appendNextPageOption(): void
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

    public function appendPreviousPageOption(): void
    {
        $this->appendPageOption(self::getPreviousOption());
    }

    public function getAvailableCharactersCount(): int
    {
        return $this->availableCharactersCount;
    }

    public function canAppendText(string $text, bool $isLastPiece = true): bool
    {
        $textLength = strlen($text);

        if ($isLastPiece) {
            return $textLength <= $this->availableCharactersCount;
        }

        $reservedCharactersCount = strlen(self::getNextOption()->getText());

        return $textLength <= $this->availableCharactersCount - $reservedCharactersCount;
    }

    public function getPageContent(): string
    {
        return implode($this->textArray).implode($this->pageOptions);
    }

    public function getPageOptionsValues(): array
    {
        return $this->pageOptionsValues;
    }

    public static function getNextOption(): Option
    {
        return new Option(__('conversation.screen.next.value'), __('conversation.screen.next.text'));
    }

    public static function getPreviousOption(): Option
    {
        return new Option(__('conversation.screen.previous.value'), __('conversation.screen.previous.text'));
    }
}
