<?php

namespace App\Messages\Outgoing;

use App\Messages\Outgoing\FieldQuestion;
use App\Messages\Outgoing\Screen\AbstractScreen;
use App\Messages\Outgoing\Screen\Option;
use App\Messages\Outgoing\Screen\Page;
use BotMan\BotMan\Messages\Incoming\Answer;
use Illuminate\Validation\ValidationException;

class QuestionScreen extends AbstractScreen
{
    /**
     * The question wrapped by this screen.
     *
     * @var \App\Messages\Outgoing\FieldQuestion
     */
    protected $question;

    protected $validationFailed = false;

    public function __construct(FieldQuestion $question, bool $includeCancelOption = true)
    {
        $this->question = $question;
        parent::__construct($includeCancelOption);
    }

    public function buildInitialPage(): Page
    {
        $textPieces = [$this->getQuestionText()];

        foreach ($this->question->getButtons() as $button) {
            $textPieces[] = "[{$button['value']}] {$button['text']}";
        }

        return new Page($textPieces, $this->getDefaultScreenOptions());
    }

    public function setAnswer(Answer $answer): void
    {
        if ($this->isDone()) {
            return;
        }

        $text = trim($answer->getText());

        if ($this->currentPage->hasScreenOption($text)) {
            $this->handleScreenOption($text);

            return;
        }

        $this->setAnswerForQuestion($answer);
    }

    public function setAnswerForQuestion(Answer $answer): void
    {
        try {
            $this->question->setAnswer($answer);
            $this->done = true;
        } catch (ValidationException $exception) {
            $this->validationFailed = true;
            $errors = $exception->validator->errors()->all();
            $errorMessage = implode("\n", $errors);

            $this->transitionToErrorPage($errorMessage);
        }
    }

    public function validationFailed(): bool
    {
        return $this->validationFailed;
    }

    public function handleScreenOption(string $option): void
    {
        parent::handleScreenOption($option);

        if (self::isEqualToOption($option, __('conversation.screen.skip.value'))) {
            $this->dontRepeatAgain();
        }

        if (self::isEqualToOption($option, __('conversation.screen.info.value'))) {
            $this->transitionToQuestionInfoPage();
        }
    }

    public function transitionToQuestionInfoPage()
    {
        $info = $this->question->getMoreInfoContent();
        $infoPage = new Page([$info], [], $this->currentPage);
        $this->setCurrentPage($infoPage);
    }

    public function getDefaultScreenOptions(): array
    {
        $options = parent::getDefaultScreenOptions();
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
