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

    protected $includeCancelOption;

    protected $canceled = false;

    public function __construct(bool $includeCancelOption = true)
    {
        $this->includeCancelOption = $includeCancelOption;
        $this->currentPage = $this->buildInitialPage();
        parent::__construct($this->getText());
    }

    public function getText(): string
    {
        return $this->currentPage->getText();
    }

    abstract public function buildInitialPage(): Page;

    abstract public function setAnswer(Answer $answer): void;

    public function isDone(): bool
    {
        return $this->done;
    }

    public function wasCanceled(): bool
    {
        return $this->canceled;
    }

    private function cancel()
    {
        $this->canceled = true;
        $this->dontRepeatAgain();
    }

    public function dontRepeatAgain(): void
    {
        $this->done = true;
    }

    public function getDefaultScreenOptions(): array
    {
        $options = [];

        // users should be able to cancel anytime
        if ($this->includeCancelOption) {
            $options[] = new Option(__('conversation.screen.cancel.value'), __('conversation.screen.cancel.text'));
        }

        return $options;
    }

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

    public function transitionToNextPage(): void
    {
        if ($this->currentPage->hasNext()) {
            $this->setCurrentPage($this->currentPage->getNext());
        }
    }

    public function transitionToPreviousPage(): void
    {
        if ($this->currentPage->hasPrevious()) {
            $this->setCurrentPage($this->currentPage->getPrevious());
        }
    }

    public function transitionToErrorPage(string $errorMessage): void
    {
        $errorPage = new Page([$errorMessage], [], $this->currentPage);
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

    public static function isEqualToOption(string $selectedOption, string $option): bool
    {
        return strcasecmp($selectedOption, $option) === 0;
    }
}
