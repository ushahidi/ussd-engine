<?php

namespace App\Messages\Outgoing\Screen;

use Illuminate\Support\Str;

/**
 * Encapsulates part of the proccess of adding content to a Page dynamically.
 */
class PageContentBuilder
{
    const WHITE_SPACE = ' ';

    protected $textArray = [];

    protected $text = '';

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

    public function appendText(string $text, bool $isLastPiece = false): int
    {
        $textLength = mb_strlen($text);
        $omissionIndicator = __('conversation.omissionIndicator');
        $omissionIndicatorLength = mb_strlen($omissionIndicator);
        $reservedCharactersCount = mb_strlen(self::getNextOption()->toString()) + $omissionIndicatorLength;
        $availableCharactersCount = $this->availableCharactersCount;
        if ($textLength <= ($this->availableCharactersCount - $reservedCharactersCount)) {
            $this->text .= $text;
            $this->availableCharactersCount -= $textLength;

            return $textLength;
        }

        // $this->appendNextPageOption();

        $tok = strtok($text, self::WHITE_SPACE);
        $appendedText = '';
        /*
         * while tok is not false
         *
         *      if there is space for tok
         *          if wont miss space // is last piece and fit or is not last piece but remaining text will fit anyways
         *              append tok
         *          else if tok
         *
         *
         *      else
         *          append ommision indicator
         *          break
         *      if is last word
         *          if there's space for tok
         *              append tok
         *          else
         *              append omission indicator
         *      else
         */
        while ($tok !== false && mb_strlen($appendedText) < $this->availableCharactersCount) {
            $doesFit = ($this->availableCharactersCount - mb_strlen($tok)) > $omissionIndicatorLength;
            if ($doesFit) {
                // dump('Does fit:', $tok, $availableCharactersCount, mb_strlen($appendedText));
                $appendedText .= $tok.' ';
            } else {
                $appendedText = trim($appendedText).$omissionIndicator;
                break;
            }
            $tok = strtok(self::WHITE_SPACE);
        }
        $this->text .= $appendedText;
        $this->availableCharactersCount -= mb_strlen($appendedText);

        return $textLength - mb_strlen($appendedText);
    }

    /**
     * Append as many text characters as spaces are left after appending
     * the next option and the omission indicator.
     */
    public function appendExceedingText(string $text): string
    {
        $this->appendNextPageOption();
        $omissionIndicator = __('conversation.omissionIndicator');
        $textSize = $this->getAvailableCharactersCount() - strlen($omissionIndicator);
        // $this->appendText(Str::limit($text, $textSize, $omissionIndicator));
        $text = Str::substr($text, $textSize);

        return $text;
    }

    public function appendPageOption(Option $option): void
    {
        $text = $option->toString();
        // $this->availableCharactersCount -= strlen($text);

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

    public function hasEnoughSpaceForText(string $text, bool $isLastPiece = true): bool
    {
        $textLength = mb_strlen($text);

        if ($isLastPiece) {
            return $textLength <= $this->availableCharactersCount;
        }

        $reservedCharactersCount = mb_strlen(self::getNextOption()->toString());

        return $textLength <= $this->availableCharactersCount - $reservedCharactersCount;
    }

    public function getPageContent(): string
    {
        return $this->text.implode($this->pageOptions);
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
