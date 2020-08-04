<?php

namespace App\Messages\Outgoing;

use App\Messages\Outgoing\Screen\Option;
use App\Messages\Outgoing\Screen\Page;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Validation\ValidationException;

class QuestionScreen extends Question
{
    public const MAX_CHARACTERS_PER_PAGE = 160;

    /**
     * Reserved character to identify the user wants to retrieve more info.
     */
    public const MORE_INFO_TRIGGER = '?';

    /**
     * The question wrapped by this screen.
     *
     * @var \App\Messages\Outgoing\FieldQuestion
     */
    protected $question;

    protected $currentPage;

    protected $pages = [];

    protected $optionsForCurrentPage = [];

    protected $shouldRepeat = true;

    public function __construct(FieldQuestion $question)
    {
        $this->question = $question;
        $this->currentPage = $this->buildInitialPage();
        parent::__construct($this->getText());
    }

    public function getText()
    {
        return $this->currentPage->getText();
    }

    public function buildInitialPage()
    {
        $options = $this->mapQuestionButtonsToScreenOptions($this->question->getButtons());

        return new Page($this->getQuestionText(), $this->getDefaultScreenOptions(), $options);
    }

    public function mapQuestionButtonsToScreenOptions(array $buttons): array
    {
        return array_map(function (array $button) {
            return new Option($button['value'], $button['text']);
        }, $buttons);
    }

    public function getCurrentPageContent(): string
    {
        $pageContent = $this->pages[$this->currentPage];
        $this->currentPage = $this->currentPage + 1;

        return $pageContent;
    }

    public function shouldRepeat(): bool
    {
        return $this->shouldRepeat;
    }

    public function setAnswer(Answer $answer)
    {
        $text = trim($answer->getText());

        if ($this->currentPage->hasScreenOption($text)) {
            $this->handleScreenOption($text);

            return;
        }

        try {
            $this->question->setAnswer($answer);
            $this->shouldRepeat = false;
        } catch (ValidationException $exception) {
            $errors = $exception->validator->errors()->all();
            $errorMessage = implode("\n", $errors);

            $this->transitionToErrorPage($errorMessage);
        }
    }

    public function getValidatedAnswerValue()
    {
        return $this->question->getValidatedAnswerValue();
    }

    public function handleScreenOption(string $option)
    {
        if ($option == __('conversation.screen.next.value')) {
            $this->transitionToNextPage();
        }
        if ($option == __('conversation.screen.previous.value')) {
            $this->transitionToPreviousPage();
        }

        //TODO: handle more info option:
        // Transition to a new page with question instructions
    }

    public function transitionToNextPage(): void
    {
        if ($this->currentPage->hasNext()) {
            $this->currentPage = $this->currentPage->getNext();
        }
    }

    public function transitionToPreviousPage(): void
    {
        if ($this->currentPage->hasPrevious()) {
            $this->currentPage = $this->currentPage->getPrevious();
        }
    }

    public function transitionToErrorPage(string $errorMessage)
    {
        //TODO: review the type hinting for page constructor
        $errorPage = new Page($errorMessage, [], [], $this->currentPage);
        $this->currentPage = $errorPage;
    }

    public function getDefaultScreenOptions(): array
    {
        $options = [];
        if (! $this->question->isRequired()) {
            $options[] = new Option(__('conversation.screen.skip.value'), __('conversation.screen.skip.text'));
        }

        // TODO: Include this option only if user have not asked for more info before
        if ($this->question->hasMoreInfo()) {
            $options[] = new Option(__('conversation.screen.info.value'), __('conversation.screen.info.text'));
        }

        return $options;
    }

    public function getQuestion(): FieldQuestion
    {
        return $this->question;
    }

    public function getQuestionText(): string
    {
        $text = $this->question->getTextContent();

        if ($this->question->hasHints() && $this->question->shouldShowHintsByDefault()) {
            $text .= "\n".$this->question->getHints();
        }

        return $text;
    }
}
