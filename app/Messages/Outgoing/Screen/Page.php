<?php

namespace App\Messages\Outgoing\Screen;

use Exception;
use Illuminate\Support\Str;

class Page
{
    public const TEXT_SEPARATOR = "\n";
    const WHITE_SPACE = ' ';

    /**
     * @var string
     */
    public $text;

    /**
     * Available characters count to use when checking it text will fit.
     * @var int
     */
    protected $availableCharactersCount;

    /**
     * Registered options for this page's scope.
     *
     * @var array
     */
    protected $textPieces = [];

    /**
     * Registered options for this page's scope.
     *
     * @var array
     */
    protected $screenOptions = [];

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
     * @param string|null $text The page text
     * @param array $screenOptions Options that should be available in this and every mew child page created
     * @param array $options Options to show on this page only. Almost always they are question options.
     * @param self $previous The previous page that created this instance.
     */
    public function __construct(?string $text, array $screenOptions = [], array $options = [], self $previous = null)
    {
        $this->availableCharactersCount = (int) config('ussd.max_characters_per_page');

        // The default screen options have priorities over everything else
        foreach ($screenOptions as $option) {
            $this->appendPageOption($option);
        }

        // If there's a previous page save the reference and attach previous option to the page content
        if ($previous != null) {
            $this->setPreviousPage($previous);
        }

        if ($text) {
            if ($this->doesFit($text)) {
                $this->appendText($text);
                $text = null;
            } else {
                $this->appendNextPageOption();
                $charactersAppended = $this->appendExceedingText($text);
                $text = Str::substr($text, $charactersAppended);
                $next = new self($text, $screenOptions, $options, $this);
                $this->setNextPage($next);

                return;
            }
        }

        while (count($options) > 0) {
            $option = array_shift($options);
            $optionText = $option->toString();

            if ($this->doesFit($optionText)) {
                $this->appendText($optionText);
            } else {
                $this->appendNextPageOption();
                $optionAndFirstWord = Str::words($optionText, 2, '');
                if ($this->doesFit($optionAndFirstWord)) {
                    $charactersAppended = $this->appendExceedingText($optionText);
                    $charactersAppended -= Str::length($option->getValueAsString());
                    $newOptionText = Str::substr($option->text, $charactersAppended);
                    $option->text = $newOptionText;
                }

                array_unshift($options, $option);
                $next = new self($text, $screenOptions, $options, $this);
                $this->setNextPage($next);

                return;
            }
        }
    }

    public function appendPageOption(Option $option): void
    {
        $text = $option->toString();
        $this->availableCharactersCount -= Str::length($text);

        if ($this->availableCharactersCount <= 0) {
            throw new Exception('Characters limit reached with page options only.');
        }

        $this->screenOptions[] = $text;
        $this->screenOptionsValues[] = $option->value;
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
        $this->availableCharactersCount -= Str::length($text);
        $this->textPieces[] = $text;
    }

    public function appendExceedingText(string $text): int
    {
        $omissionIndicator = __('conversation.omissionIndicator');
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

    public function addNextPage(?string $text, array $screenOptions = [], array $options = [])
    {
        $this->next = new self($text, $screenOptions, $options, $this);
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
        $this->appendPageOption(Option::previous());
    }

    public function setNextPage(self $next): void
    {
        $this->next = $next;
    }

    public function appendNextPageOption(): void
    {
        $this->appendPageOption(Option::next());
    }
}
