<?php

namespace App\Messages\Outgoing\Screen;

use App\Messages\Outgoing\Screen\Page;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;

abstract class AbstractScreen extends Question
{
    /**
     * @var \App\Messages\Outgoing\Screen\Page
     */
    protected $currentPage;

    /**
     * Determines if the conversation should skip this screen or not.
     *
     * @var bool
     */
    protected $done = false;

    /**
     * Indicates if the screen should have the cancel option.
     *
     * @var bool
     */
    protected $includeCancelOption;

    /**
     * Indicates if the screen was canceled by the user.
     *
     * @var bool
     */
    protected $canceled = false;

    public function __construct(bool $includeCancelOption = true)
    {
        $this->includeCancelOption = $includeCancelOption;
        $this->currentPage = $this->buildInitialPage();
        parent::__construct($this->getText());
    }

    /**
     * {@inheritdoc}
     *
     * Returns current page's text as the text Botman should show
     *  when asking the question
     */
    public function getText(): string
    {
        return $this->currentPage->getText();
    }

    /**
     * Generates the initial page for the screen.
     * If the text content does not fit in a page, considering the limited
     * amount of characters, the page will automatically create and save the
     * reference to the next page, this happens recursively.
     *
     * @return Page
     */
    abstract public function buildInitialPage(): Page;

    /**
     * Implement how the screen will handle an answer from Botman.
     *
     * It could pass the answer to another question or ignore it,
     * it will depend on the actual implementation.
     *
     * @param Answer $answer
     * @return void
     */
    abstract public function setAnswer(Answer $answer): void;

    /**
     * Indicates if within a conversation context this screen is done,
     * meaning that if it is, the conversation can move forward on the flow.
     * If it is not, the screen is usually repeated.
     *
     * @return bool
     */
    public function isDone(): bool
    {
        return $this->done;
    }

    public function wasCanceled(): bool
    {
        return $this->canceled;
    }

    /**
     * Sets the screen as canceled and indicates to not reapat this screen again.
     *
     * @return void
     */
    private function cancel()
    {
        $this->canceled = true;
        $this->dontRepeatAgain();
    }

    /**
     * Marks the screen as done.
     *
     * @return void
     */
    public function dontRepeatAgain(): void
    {
        $this->done = true;
    }

    /**
     * Returns the array of options that should be available on all the pages
     * that may come out of this screen.
     *
     * @return array
     */
    public function getDefaultScreenOptions(): array
    {
        $options = [];

        // users should be able to cancel anytime
        if ($this->includeCancelOption) {
            $options[] = new Option(__('conversation.screen.cancel.value'), __('conversation.screen.cancel.text'));
        }

        return $options;
    }

    /**
     * Map each screen option to it's correspondent action.
     *
     * @param string $option
     * @return void
     */
    public function handleScreenOption(string $option): void
    {
        if (self::isEqualToOption($option, __('conversation.screen.next.value'))) {
            $this->transitionToNextPage();
        }
        if (self::isEqualToOption($option, __('conversation.screen.previous.value'))) {
            $this->transitionToPreviousPage();
        }
        if (self::isEqualToOption($option, __('conversation.screen.cancel.value'))) {
            $this->cancel();
        }
    }

    /**
     * Sets the current page to the next one referenced in the current page.
     *
     * @return void
     */
    public function transitionToNextPage(): void
    {
        if ($this->currentPage->hasNext()) {
            $this->setCurrentPage($this->currentPage->getNext());
        }
    }

    /**
     * Sets the current page to the previous page referenced in the current page.
     *
     * @return void
     */
    public function transitionToPreviousPage(): void
    {
        if ($this->currentPage->hasPrevious()) {
            $this->setCurrentPage($this->currentPage->getPrevious());
        }
    }

    /**
     * Creates a new page for the error message provided and set it as current page.
     *
     * @param string $errorMessage
     * @return void
     */
    public function transitionToErrorPage(string $errorMessage): void
    {
        $errorPage = new Page($errorMessage, [], [], $this->currentPage);
        $this->setCurrentPage($errorPage);
    }

    public function setCurrentPage(Page $page): void
    {
        $this->currentPage = $page;
        $this->text = $this->currentPage->getText();
    }

    public function getCurrentPage(): Page
    {
        return $this->currentPage;
    }

    /**
     * Compares the option chosen by the user to a previously registered option.
     *
     * This allows options to be case insensitive.
     *
     * @param string $selectedOption
     * @param string $option
     * @return bool
     */
    public static function isEqualToOption(string $selectedOption, string $option): bool
    {
        return strcasecmp($selectedOption, $option) === 0;
    }
}
