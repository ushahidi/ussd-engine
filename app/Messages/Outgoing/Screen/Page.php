<?php

namespace App\Messages\Outgoing\Screen;

use Illuminate\Support\Str;

class Page
{
    /**
     * @var string
     */
    public $text;

    /**
     * Registered options for this page's scope.
     *
     * Used to check is an option is supported for this page.
     *
     * @var array
     */
    protected $pageOptions = [];

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
        $omissionIndicator = __('conversation.omissionIndicator');
        $builder = new PageContentBuilder();

        // The default screen options have priorities over everything else
        foreach ($screenOptions as $option) {
            $builder->appendPageOption($option);
        }

        // If there's a previous page save the reference and attach previous option to the page content
        if ($previous != null) {
            $this->previous = $previous;
            $builder->appendPreviousPageOption();
        }

        if ($text) {
            if ($builder->hasEnoughSpaceForText($text)) {
                $builder->appendText($text);
                $text = null;
            } else {
                /**
                 * Append as much as possible and returns the rest to use it in the other page.
                 */
                $text = $builder->appendExceedingText($text);
            }
        }

        /*
         * Only if the page is not already full we'll try to append the options
         */
        if (! $builder->hasNextPageOption() && count($options) > 0) {
            $availableCharacters = $builder->getAvailableCharactersCount();
            while (! $builder->hasNextPageOption() && $availableCharacters > 0 && count($options) > 0) {
                $option = array_shift($options);
                $optionText = $option->getText();
                $isTheLastPiecePendingForAttachment = count($options) === 0;

                if ($builder->hasEnoughSpaceForText($optionText, $isTheLastPiecePendingForAttachment)) {
                    $builder->appendText($optionText);
                } else {
                    /**
                     * Append as much as possible and returns the rest to use it in the other page.
                     */
                    $newOptionText = $builder->appendExceedingText($optionText);
                    $option->text = $newOptionText;
                    array_unshift($options, $option);
                }
                $availableCharacters = $builder->getAvailableCharactersCount();
            }
        }

        if ($builder->hasNextPageOption()) {
            $this->next = new self($text, $screenOptions, $options, $this);
        }

        // all the appended text, including options
        $this->text = $builder->getPageContent();
        // page options for this page
        $this->pageOptions = $builder->getPageOptionsValues();
    }

    public function hasScreenOption(string $option): bool
    {
        foreach ($this->pageOptions as $registeredOption) {
            if (AbstractScreen::isEqualToOption($option, $registeredOption)) {
                return true;
            }
        }

        return false;
    }

    public function getText(): string
    {
        return $this->text;
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
}
