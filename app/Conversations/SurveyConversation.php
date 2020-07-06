<?php

namespace App\Conversations;

use App\Messages\Outgoing\EndingMessage;
use App\Messages\Outgoing\FieldQuestionFactory;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer as BotManAnswer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use PlatformSDK\Ushahidi;

class SurveyConversation extends Conversation
{
    protected $sdk;

    protected $survey;

    protected $fields;

    public function __construct()
    {
        $this->sdk = resolve(Ushahidi::class);
    }

    public function getAvailableSurveys()
    {
        $result = $this->sdk->getAvailableSurveys();

        return $result['body']['results'];
    }

    protected function askSurvey()
    {
        $surveys = Collection::make($this->getAvailableSurveys());
        $question = Question::create('Which form do you want to complete?')
            ->addButtons(
                $surveys->map(function ($survey) {
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

        $this->sendEndingMessage();
    }

    private function askField($field)
    {
        $this->ask(FieldQuestionFactory::create($field), function (BotManAnswer $answer) use ($field) {
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

    public function sendEndingMessage()
    {
        $this->say(EndingMessage::create('Thanks for submitting your response.'));
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
