<?php

namespace App\Conversations;

use App\Surveys;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer as BotManAnswer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;

class SurveyConversation extends Conversation
{
    protected $survey;

    protected $fields;

    protected function askSurvey()
    {
        $surveys = Collection::make(Surveys::load());
        $question = Question::create('Which form do you want to complete?')
            ->addButtons(
                $surveys->map(function ($survey) {
                    return Button::create($survey->name)->value($survey->id);
                })->all()
            );

        $this->ask($question, function (BotManAnswer $answer) use ($surveys) {
            // Detect if button was clicked:
            if ($answer->isInteractiveMessageReply()) {
                $selectedSurvey = $answer->getValue();
            } else {
                $selectedSurvey = $answer->getText();
            }

            $this->survey = $surveys->firstWhere('id', $selectedSurvey);

            $this->say("Okay, loading {$this->survey->name} fields...");

            $this->askSurveyFields();
        });
    }

    protected function askSurveyFields()
    {
        $this->fields = Collection::make($this->survey->tasks)
                            ->pluck('fields')
                            ->flatten()
                            ->keyBy('id');

        $this->checkForNextFields();
    }

    private function checkForNextFields()
    {
        if ($this->fields->count()) {
            return $this->askField($this->fields->first());
        }
    }

    private function askField($field)
    {
        $this->ask($this->createQuestionForField($field), function (BotManAnswer $answer) use ($field) {
            $errors = $this->validateAnswer($field, $answer);

            if ($errors) {
                foreach ($errors as $error) {
                    $this->say($error);
                }

                return $this->repeat();
            }

            $answerForField = $answer->getText();

            if ($answerForField) {
                $user = $this->bot->getUser();
                $response = [
                    'user_input' => $answerForField,
                    'user_id' => $user->getId(),
                ];
            }

            $this->fields->forget($field->id);
            $this->checkForNextFields();
        });
    }

    private function createQuestionForField($field)
    {
        $question = Question::create($field->label.':');

        return $question;
    }

    private function validateAnswer($field, BotManAnswer $answer)
    {
        $validationRules = Surveys::assembleFieldValidationRules($field);
        $validator = Validator::make([
            $field->key => $answer->getText(),
        ], [
           $field->key => $validationRules,
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();

            return $errors;
        }
    }

    /**
     * Start the conversation.
     *
     * @return mixed
     */
    public function run()
    {
        $this->askSurvey();
    }
}
