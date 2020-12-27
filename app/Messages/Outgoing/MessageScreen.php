<?php

namespace App\Messages\Outgoing;

use App\Messages\Outgoing\Screen\AbstractScreen;
use App\Messages\Outgoing\Screen\Option;
use App\Messages\Outgoing\Screen\Page;
use BotMan\BotMan\Messages\Incoming\Answer;

class MessageScreen extends AbstractScreen
{
    /**
     * The text wrapped by this screen.
     *
     * @var string
     */
    protected $text;

    public function __construct(string $text, bool $includeCancelOption = true)
    {
        $this->text = $text;
        parent::__construct($includeCancelOption);
    }

    public function buildInitialPage(): Page
    {
        return new Page([$this->text], $this->getDefaultScreenOptions());
    }

    public function setAnswer(Answer $answer): void
    {
        if ($this->isDone()) {
            return;
        }

        $text = trim($answer->getText());

        if (! $this->currentPage->hasScreenOption($text)) {
            return;
        }

        $this->handleScreenOption($text);
    }

    public function handleScreenOption(string $option): void
    {
        parent::handleScreenOption($option);

        if (self::isEqualToOption($option, __('conversation.screen.ok.value'))) {
            $this->dontRepeatAgain();
        }
    }

    public function getDefaultScreenOptions(): array
    {
        $options = parent::getDefaultScreenOptions();

        $options[] = new Option(__('conversation.screen.ok.value'), __('conversation.screen.ok.text'));

        return $options;
    }
}
