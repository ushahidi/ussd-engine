<?php

namespace App\Conversations;

use App\Exceptions\EmptySurveysResultsException;
use App\Exceptions\NoSurveyTasksException;
use App\Messages\Outgoing\EndingMessage;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use PlatformSDK\Ushahidi;

class SurveyConversation extends Conversation
{
    protected $sdk;

    protected $surveys;

    protected $survey;

    protected $tasks;

    protected $postContent = [];

    protected $answers = [];

    public function __construct()
    {
        $this->sdk = resolve(Ushahidi::class);
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

    public function getSurvey($id): array
    {
        try {
            $response = $this->sdk->getSurvey($id);

            if (! $response['body'] || ! $response['body']['result']) {
                throw new EmptySurveysResultsException('Empty survey results returned');
            }

            return $response['body']['result'];
        } catch (\Throwable $ex) {
            Log::error("Couldn't fetch available surveys: ".$ex->getMessage());
            throw $ex;
        }
    }

    protected function askSurvey()
    {
        $question = Question::create(__('conversation.selectSurvey'))
            ->addButtons(
                $this->surveys->map(function ($survey) {
                    return Button::create($survey['name'])->value($survey['id']);
                })->all()
            );

        $this->ask($question, function (Answer $answer) {
            // Detect if button was clicked:
            if ($answer->isInteractiveMessageReply()) {
                $selectedSurvey = $answer->getValue();
            } else {
                $selectedSurvey = $answer->getText();
            }

            try {
                $this->survey = $this->getSurvey($selectedSurvey);
                $this->say(__('conversation.surveySelected', ['name' => $this->survey['name']]));
                $this->askTasks();
            } catch (\Throwable $exception) {
                $this->sendEndingMessage(__('oops'));
            }
        });
    }

    protected function askTasks()
    {
        if (isset($this->survey['tasks']) && is_array($this->survey['tasks']) && count($this->survey['tasks'])) {
            $this->tasks = Collection::make([$this->survey['tasks'][0]])->keyBy('id');
            $this->askNextTask();
        } else {
            Log::debug('Survey does not have tasks.', $this->survey);
            throw new NoSurveyTasksException('Survey does not have tasks.');
        }
    }

    private function askNextTask()
    {
        if ($this->tasks->count()) {
            $task = $this->tasks->first();

            if (isset($task['fields']) && is_array($task['fields']) && count($task['fields'])) {
                $this->fields = Collection::make($task['fields'])->keyBy('id');
                $this->askFields();

                return;
            }
        }

        try {
            $this->sendResponseToPlatform();
            $this->sendEndingMessage(__('thanksForSubmitting'));
        } catch (\Throwable $exception) {
            $this->sendEndingMessage(__('oops'));
        }
    }

    public function askFields()
    {
        if ($this->fields->count()) {
            $this->askNextField();
        }
    }

    private function askNextField()
    {
        if ($this->fields->count()) {
            $this->askField($this->fields->first());
        } else {
            $this->buildTaskResponse();
            $this->tasks->forget($this->tasks->first()['id']);
            $this->askNextTask();
        }
    }

    private function buildTaskResponse()
    {
        $currentTask = $this->tasks->first();
        $this->postContent[] = [
            'id' => $currentTask['id'],
            'type' => $currentTask['type'],
            'fields' => $this->answers,
        ];

        $this->answers = [];
    }

    private function askField(array $field)
    {
        $this->ask($this->createQuestionForField($field), function (Answer $answer) use ($field) {
            $errors = $this->validateAnswer($field, $answer);

            if ($errors) {
                foreach ($errors as $error) {
                    $this->say($error);
                }

                return $this->repeat();
            }

            $answerForField = $answer->getText();

            if ($answerForField) {
                $this->answers[] = [
                    'id' => $field['id'],
                    'type' => $field['type'],
                    'value' => $answerForField,
                ];
            }

            $this->fields->forget($field['id']);
            $this->askNextField();
        });
    }

    private function createQuestionForField(array $field)
    {
        $question = Question::create($field['label'].':');

        return $question;
    }

    private function validateAnswer($field, Answer $answer)
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

    public function sendResponseToPlatform()
    {
        $titleField = Collection::make($this->postContent[0]['fields'])->firstWhere('type', 'title');
        $descriptionField = Collection::make($this->postContent[0]['fields'])->firstWhere('type', 'description');
        $post = [
            'title' => $titleField ? $titleField['value'] : null,
            'locale' => 'en_US',
            'post_content' => $this->postContent,
            'form_id' => $this->survey['id'],
            'type' => 'report',
            'completed_stages' => [],
            'published_to' => [],
            'post_date' => now()->toISOString(),
            'enabled_languages' => [],
            'content' => $descriptionField ? $descriptionField['value'] : null,
        ];

        try {
            $this->sdk->createPost($post);
        } catch (\Throwable $ex) {
            Log::error("Couldn't save post: ".$ex->getMessage());
            throw $ex;
        }
    }
}
