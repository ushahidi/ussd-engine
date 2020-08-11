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

    public function __construct(?string $text, array $screenOptions = [], array $options = [], self $previous = null)
    {
        $omissionIndicator = __('conversation.omissionIndicator');
        $builder = new PageContentBuilder();

        foreach ($screenOptions as $option) {
            $builder->appendPageOption($option);
        }

        if ($previous != null) {
            $this->previous = $previous;
            $builder->appendPreviousPageOption();
        }

        if ($text) {
            if ($builder->canAppendText($text)) {
                $builder->appendText($text);
                $text = null;
            } else {
                $builder->appendNextPageOption();
                $textSize = $builder->getAvailableCharactersCount() - strlen($omissionIndicator);
                $builder->appendText(Str::limit($text, $textSize, $omissionIndicator));
                $text = Str::substr($text, $textSize);
            }
        }

        if (! $builder->hasNextPageOption() && count($options) > 0) {
            $availableCharacters = $builder->getAvailableCharactersCount();
            while (! $builder->hasNextPageOption() && $availableCharacters > 0 && count($options) > 0) {
                $option = array_shift($options);
                $optionText = $option->getText();
                if (! $builder->canAppendText($optionText, count($options) === 0)) {
                    $builder->appendNextPageOption();
                    $optionTextSize = $builder->getAvailableCharactersCount() - strlen($omissionIndicator);
                    $builder->appendText(Str::limit($optionText, $optionTextSize, $omissionIndicator));
                    $newOptionText = Str::substr($optionText, $optionTextSize);
                    $option->text = $newOptionText;
                    array_unshift($options, $option);
                } else {
                    $builder->appendText($optionText);
                }
                $availableCharacters = $builder->getAvailableCharactersCount();
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
