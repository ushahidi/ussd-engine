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
            $appendedCharacters = $builder->appendText($text, empty($options));
            $text = $appendedCharacters < mb_strlen($text) ? Str::substr($text, $appendedCharacters) : null;
        }

        /*
         * Only if the page is not already full we'll try to append the options
         */
        if (is_null($text) && count($options) > 0) {
            while (count($options) > 0) {
                $option = array_shift($options);
                $value = "\n[{$option->value}] ";
                $text = $option->text;
                $optionText = $value.$text;
                $charactersToAppend = mb_strlen($optionText);

                $appendedCharacters = $builder->appendText($optionText, empty($options));
                if ($appendedCharacters < $charactersToAppend) {
                    $appendedCharacters -= mb_strlen($value);
                    $newOptionText = Str::substr($text, $appendedCharacters);
                    $option->text = $newOptionText;
                    array_unshift($options, $option);
                    break;
                }
            }
        }

        if ($builder->hasNextPageOption()) {
            $this->next = new self($text, $screenOptions, $options, $this);
        }

        $this->text = $builder->getPageContent();
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
