<?php

namespace App\Messages\Outgoing\Screen;

use Exception;
use Illuminate\Support\Str;

class Page
{
    public const TEXT_SEPARATOR = "\n";
    const WHITE_SPACE = ' ';

    /**
     * Available characters count to use when checking if text will fit.
     * @var int
     */
    protected $availableCharactersCount;

    /**
     * Text pieces to concatenate for this page.
     *
     * @var array
     */
    protected $textPieces = [];

    /**
     * Screen options to include in this page.
     *
     * @var array
     */
    protected $screenOptions = [];

    /**
     * Registered options for this page's scope.
     *
     * @var array
     */
    protected $screenOptionsValues = [];

    /**
     * Next page.
     *
     * @var self
     */
    protected $next;

    /**
     * Previous page.
     *
     * @var self
     */
    protected $previous;

    /**
     * Create a page with the text and options included.
     *
     * @param array $textPieces  Text pieces to concatenate for this or child pages
     * @param array $screenOptions Options that should always be available in this and every new child page created
     * @param self $previous The previous page that created this instance.
     */
    public function __construct(array $textPieces = [], array $screenOptions = [], self $previous = null)
    {
        $this->availableCharactersCount = self::getMaxCharactersPerPage();

        // The default screen options have priorities over everything else
        foreach ($screenOptions as $option) {
            $this->appendScreenOption($option);
        }

        // If there's a previous page save the reference and attach previous option to the page content
        if ($previous != null) {
            $this->setPreviousPage($previous);
        }

        while (count($textPieces) > 0) {
            $textPiece = array_shift($textPieces);
            /*
             * If a text piece fit in the current page, we just append that piece to the list.
             *
             * If not we should create a new page, include the next option in this current page and append
             * as much words as posible from the text piece we are trying to add.
             */
            if ($this->doesFit($textPiece)) {
                $this->appendText($textPiece);
            } else {
                /**
                 * Sometimes we may need to make room the "Next" option,
                 * if that's the case we should try to append as much words as possible from the last
                 * removed text piece and send the rest to the next page.
                 */
                $removedTextPieces = $this->appendNextPageOption();
                if (! empty($removedTextPieces)) {
                    array_unshift($textPieces, $textPiece);
                    $textPiece = array_shift($removedTextPieces);
                    if (! empty($removedTextPieces)) {
                        $textPieces = array_merge($removedTextPieces, $textPieces);
                    }
                }
                $firstTwoWords = Str::words($textPiece, 2, '');
                $omissionIndicator = self::getOmissionIndicator();
                $usefulTextPieceSegment = $firstTwoWords.$omissionIndicator;
                if ($this->doesFit($usefulTextPieceSegment)) {
                    $charactersAppended = $this->appendExceedingText($textPiece);
                    $textPiece = Str::substr($textPiece, $charactersAppended);
                }

                array_unshift($textPieces, $textPiece);
                $next = new self($textPieces, $screenOptions, $this);
                $this->setNextPage($next);

                return;
            }
        }
    }

    /**
     * Register a screen option.
     *
     * @param Option $option
     * @return void
     */
    public function appendScreenOption(Option $option): void
    {
        $text = $option->toString();
        $textLength = Str::length($text);
        $this->checkScreenOptionsLength($textLength);
        $this->reserveCharacters($textLength);
        $this->screenOptions[] = $text;
        $this->screenOptionsValues[] = $option->value;
    }

    /**
     * Check the amount of characters reserved for screen options specifically.
     *
     * Throws an exception if the amount surpass the max characters allowed per page
     *
     * @param int $lengthToAdd
     * @return void
     */
    public function checkScreenOptionsLength(int $lengthToAdd = 0): void
    {
        $totalCharactersTakenByScreenOptions = array_sum(array_map(function ($screenOption) {
            return Str::length($screenOption);
        }, $this->screenOptions));

        $separatorsLength = count($this->screenOptions) * Str::length(self::TEXT_SEPARATOR);
        $totalCharactersTakenByScreenOptions += $separatorsLength;

        $totalCharactersTakenByScreenOptions += $lengthToAdd;

        if ($totalCharactersTakenByScreenOptions >= self::getMaxCharactersPerPage()) {
            throw new Exception('Characters limit will be reached with page options only.');
        }
    }

    public function getAvailableCharactersCount(): int
    {
        $separatorsCount = count($this->screenOptions) + count($this->textPieces) - 1;
        $separatorsLength = $separatorsCount * Str::length(self::TEXT_SEPARATOR);

        return $this->availableCharactersCount - $separatorsLength;
    }

    public function doesFit(string $text): bool
    {
        return Str::length($text) <= $this->getAvailableCharactersCount();
    }

    public function hasScreenOption(string $option): bool
    {
        foreach ($this->screenOptionsValues as $registeredOption) {
            if (AbstractScreen::isEqualToOption($option, $registeredOption)) {
                return true;
            }
        }

        return false;
    }

    public function getText(): string
    {
        $pageContent = array_merge($this->textPieces, $this->screenOptions);

        return implode(self::TEXT_SEPARATOR, $pageContent);
    }

    public function appendText(string $text): void
    {
        $this->reserveCharacters(Str::length($text));
        $this->textPieces[] = $text;
    }

    /**
     * Append as many words as possible from the provided text.
     *
     * @param string $text
     * @return int The number of characters appended
     */
    public function appendExceedingText(string $text): int
    {
        $omissionIndicator = self::getOmissionIndicator();
        $omissionIndicatorLength = Str::length($omissionIndicator);
        $availableCharactersCount = $this->getAvailableCharactersCount() - $omissionIndicatorLength - Str::length(self::TEXT_SEPARATOR);
        $remainingCharactersCount = $availableCharactersCount;

        $textToAppend = '';
        $tok = strtok($text, self::WHITE_SPACE);

        while (Str::length($textToAppend) <= $availableCharactersCount) {
            $tokToAppend = $textToAppend ? self::WHITE_SPACE.$tok : $tok;
            $doesFit = Str::length($tokToAppend) <= $remainingCharactersCount;
            if ($doesFit) {
                $textToAppend .= $tokToAppend;
                $remainingCharactersCount -= Str::length($tokToAppend);
            } else {
                break;
            }
            $tok = strtok(self::WHITE_SPACE);
        }

        $this->appendText($textToAppend.$omissionIndicator);

        return Str::length($textToAppend);
    }

    public function reserveCharacters(int $amountOfCharacters):void
    {
        if (($this->availableCharactersCount - $amountOfCharacters) < 0) {
            throw new Exception('Invalid amount of characters to reserve.');
        }

        $this->availableCharactersCount -= $amountOfCharacters;
    }

    public function hasNext(): bool
    {
        return $this->next !== null;
    }

    public function getNext(): ?self
    {
        return $this->next;
    }

    public function hasPrevious(): bool
    {
        return $this->previous !== null;
    }

    public function getPrevious(): ?self
    {
        return $this->previous;
    }

    public function setPreviousPage(self $previous): void
    {
        $this->previous = $previous;
        $this->appendScreenOption(Option::previous());
    }

    public function setNextPage(self $next): void
    {
        $this->next = $next;
    }

    public function appendNextPageOption(): array
    {
        $nextOption = Option::next();

        $optionText = $nextOption->toString();
        $removedTextPieces = [];
        while (! $this->doesFit($optionText)) {
            array_unshift($removedTextPieces, $this->popTextPiece());
        }

        $this->appendScreenOption($nextOption);

        return $removedTextPieces;
    }

    public function popTextPiece(): ?string
    {
        $textPiece = array_pop($this->textPieces);
        if ($textPiece) {
            $this->availableCharactersCount += Str::length($textPiece);
        }

        return $textPiece;
    }

    public static function getMaxCharactersPerPage(): int
    {
        return (int) config('ussd.max_characters_per_page');
    }

    public static function getOmissionIndicator(): string
    {
        return __('conversation.omissionIndicator');
    }
}
