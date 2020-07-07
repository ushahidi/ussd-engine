<?php

namespace App\Conversations;

use App\Exceptions\EmptySurveysResultsException;
use App\Messages\Outgoing\EndingMessage;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer as BotManAnswer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PlatformSDK\Ushahidi;

class SurveyConversation extends Conversation
{
    protected $sdk;

    protected $surveys;

    protected $survey;

    protected $fields;

    public function __construct()
    {
        $this->sdk = resolve(Ushahidi::class);
    }

    public function getAvailableSurveys(): array
    {
        try {
            $response = $this->sdk->getAvailableSurveys();

            if (! $response['body'] || ! $response['body']['results']) {
                throw new EmptySurveysResultsException('Empty survey results returned');
    }

            return $response['body']['results'];
        } catch (\Throwable $ex) {
            Log::error("Couldn't fetch available surveys: ".$ex->getMessage());
            throw $ex;
        }
    }

    protected function askSurvey()
    {
        $question = Question::create('Which form do you want to complete?')
            ->addButtons(
                $this->surveys->map(function ($survey) {
                    return Button::create($survey['name'])->value($survey['id']);
                })->all()
            );

        $this->ask($question, function (BotManAnswer $answer) use ($surveys) {
            // Detect if button was clicked:
            if ($answer->isInteractiveMessageReply()) {
                $selectedSurvey = $answer->getValue();
            } else {
                $selectedSurvey = $answer->getText();
            }

            $this->survey = $this->sdk->getSurvey($selectedSurvey)['body']['result'];

            $this->say("Okay, loading {$this->survey['name']} fields...");

            $this->askSurveyFields();
        });
    }

    protected function askSurveyFields()
    {
        $fields = array_merge(...Arr::pluck($this->survey['tasks'], 'fields'));
        $this->fields = Collection::make($fields)->keyBy('id');

        $this->checkForNextFields();
    }

    private function checkForNextFields()
    {
        if ($this->fields->count()) {
            $this->askField($this->fields->first());

            return;
        }

        $this->sendEndingMessage('Thanks for submitting your response.');
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
                $userId = $this->bot->getUser()->getId();
                $key = "survey_{$this->survey['id']}-{$userId}";
                $userAnswers = Cache::get($key, []);
                $userAnswers[$field['key']] = $answerForField;
                Cache::forever($key, $userAnswers);
            }

            $this->fields->forget($field['id']);
            $this->checkForNextFields();
        });
    }

    private function createQuestionForField($field)
    {
        $question = Question::create($field['label'].':');

        return $question;
    }

    private function validateAnswer($field, BotManAnswer $answer)
    {
        $validationRules = [];

        if ($field['required']) {
            $validationRules[] = 'required';
        }

        $validationRules = implode('|', $validationRules);
        $validator = Validator::make([
            $field['key'] => $answer->getText(),
        ], [
           $field['key']  => $validationRules,
        ]);

        if ($validator->fails()) {
            $errors = $validator->errors()->all();

            return $errors;
        }
    }

    public function sendEndingMessage(string $message)
    {
        $this->say(EndingMessage::create($message));
    }

    /**
     * Start the conversation.
     *
     * @return mixed
     */
    public function run()
    {
        try {
            $surveys = $this->getAvailableSurveys();
            $this->surveys = Collection::make($surveys);
        $this->askSurvey();
        } catch (\Throwable $exception) {
            $this->sendEndingMessage('Oops, something went wrong on our side. Try again later.');
        }
    }

    public function __sleep()
    {
        $this->sdk = null;

        return parent::__sleep();
    }

    public function __wakeup()
    {
        $this->sdk = resolve(Ushahidi::class);

        return parent::__sleep();
    }
}
