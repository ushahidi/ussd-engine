<?php

namespace App\Conversations;

use App\Exceptions\EmptySurveysResultsException;
use App\Exceptions\NoSurveyTasksException;
use App\Messages\Outgoing\EndingMessage;
use App\Messages\Outgoing\FieldQuestionFactory;
use App\Messages\Outgoing\SelectQuestion;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use BotMan\BotMan\Messages\Outgoing\Actions\Button;
use BotMan\BotMan\Messages\Outgoing\Question;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use PlatformSDK\Ushahidi;

/**
 * This Conversation defines all interaction with the user when filling a form.
 *
 * Instances of this class are stateful, they are serialized and cached before each
 * response is sent and restored on each incoming request.
 *
 * All inside this class should be able to serialize.
 */
class SurveyConversation extends Conversation
{
    /**
     * Ushahidi Platform SDK instance.
     *
     * @var \PlatformSDK\Ushahidi
     */
    protected $sdk;

    /**
     * All available surveys.
     *
     * @var Illuminate\Support\Collection
     */
    protected $surveys;

    /**
     * Selected survey.
     *
     * @var array
     */
    protected $survey;

    /**
     * Selected survey tasks.
     * @var Illuminate\Support\Collection
     */
    protected $tasks;

    /**
     * Per tasks responses.
     *
     * @var array
     */
    protected $postContent = [];

    /**
     * Array of each task fields.
     * @var Illuminate\Support\Collection
     */
    protected $fields;

    /**
     * Array of each task responses.
     * Gets empty after each task is completed.
     * @var array
     */
    protected $answers = [];

    public function __construct()
    {
        $this->sdk = resolve(Ushahidi::class);
    }

    /**
     * Start the conversation.
     *
     * This method is called by Botman.
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
            $this->sendEndingMessage(__('oops'));
        }
    }

    /**
     * Remove dependencies before serialization.
     *
     * @return array
     */
    public function __sleep()
    {
        $this->sdk = null;

        return parent::__sleep();
    }

    /**
     * Attach dependencies after unserialization.
     */
    public function __wakeup()
    {
        $this->sdk = resolve(Ushahidi::class);

        return parent::__sleep();
    }

    /**
     * Fetch all available surveys from the Ushahidi platform.
     *
     * @return array
     */
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

    /**
     * Fetchs information about a survey using the survey id.
     *
     * @param int $id
     * @return array
     */
    public function getSurvey(int $id): array
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

    /**
     * Ask the user to select a survey and handle the user input.
     *
     * @return void
     */
    protected function askSurvey()
    {
        $field = [
            'label' => 'Which form do you want to complete?',
            'key' => 'survey',
            'required' => true,
            'options' => $this->surveys,
        ];
        $question = new SelectQuestion($field, 'id', 'name');

        $this->ask($question, function (Answer $answer) use ($question) {
            try {
                $question->setAnswer($answer);
                $selectedSurvey = $question->getAnswerResponse()['value'];
            } catch (ValidationException $exception) {
                $errors = $exception->validator->errors()->all();
                foreach ($errors as $error) {
                    $this->say($error);
                }

                return $this->repeat();
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

    /**
     * Set and start asking each of the tasks in the selected survey.
     *
     * @return void
     */
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

    /**
     * Check is there is any tasks that have not been asked yet and ask it.
     * If all tasks has been asked, then send the responses to the Ushahidi Platform.
     *
     * @return void
     */
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

    /**
     * Ask each field on the current task.
     *
     * @return void
     */
    public function askFields()
    {
        if ($this->fields->count()) {
            $this->askNextField();
        }
    }

    /**
     * Ask each field on the current task.
     * If there are no fields to be asked, then it builds the responses for the current taks.
     *
     * @return void
     */
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

    /**
     * Take all the collected answers for the current task and push them to the post content.
     *
     * @return void
     */
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

    /**
     * Create a question from the provided field, ask it and handle the user answer.
     *
     * @param array $field
     * @return void
     */
    private function askField(array $field)
    {
        $question = FieldQuestionFactory::create($field);
        $this->ask($question, function (Answer $answer) use ($question, $field) {
            try {
                $question->setAnswer($answer);
                $this->answers[] = $question->getAnswerResponse();
            } catch (ValidationException $exception) {
                $errors = $exception->validator->errors()->all();
                foreach ($errors as $error) {
                    $this->say($error);
                }

                return $this->repeat();
            }

            $this->fields->forget($field['id']);
            $this->askNextField();
        });
    }

    /**
     * Send ending message using the provided text.
     *
     * @param string $message
     * @return void
     */
    public function sendEndingMessage(string $message)
    {
        $this->say(EndingMessage::create($message));
    }

    /**
     * Post the collected responses to the Ushahidi Platform.
     *
     * @return void
     */
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
