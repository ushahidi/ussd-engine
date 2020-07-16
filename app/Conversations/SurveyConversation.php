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
use Illuminate\Support\Facades\App;
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

    protected $selectedLanguage;

    protected $userCanAskForInfo = true;

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
            $this->askInteractionLanguage();
        } catch (\Throwable $exception) {
            $this->sendEndingMessage(__('conversation.oops'));
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
        App::setLocale($this->selectedLanguage);

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
     * Ask the user to choose a language from the availables on the surveys list.
     * This happens before the survey selection question.
     *
     * @return void
     */
    protected function askInteractionLanguage()
    {
        $availableLanguagesList = $this->surveys
                                            ->pluck('enabled_languages')
                                            ->flatten()
                                            ->unique()
                                            ->values()
                                            ->all();

        $field = [
            'label' => __('conversation.chooseALanguage'),
            'key' => 'language',
            'required' => true,
            'options' => $availableLanguagesList,
        ];

        $question = new SelectQuestion($field);

        $this->ask($question, function (Answer $answer) use ($question) {
            try {
                $question->setAnswer($answer);
                $selectedLanguage = $question->getAnswerValue()['value'];
            } catch (ValidationException $exception) {
                $errors = $exception->validator->errors()->all();
                foreach ($errors as $error) {
                    $this->say($error);
                }

                return $this->askCancelOrGoToListOfSurveys();
            }

            try {
                $this->selectedLanguage = $selectedLanguage;
                App::setLocale($this->selectedLanguage);
                $this->askSurvey();
            } catch (\Throwable $exception) {
                Log::error('Error while asking interaction language:'.$exception->getMessage());
                $this->sendEndingMessage(__('conversation.oops'));
            }
        });
    }

    /**
     * Ask the user to select a survey and handle the user input.
     *
     * @return void
     */
    protected function askSurvey()
    {
        $field = [
            'label' => __('conversation.selectSurvey'),
            'key' => 'survey',
            'required' => true,
            'options' => $this->surveys->all(),
        ];
        $question = new SelectQuestion($field, 'id', 'name');

        $this->ask($question, function (Answer $answer) use ($question) {
            try {
                $question->setAnswer($answer);
                $selectedSurvey = $question->getAnswerValue()['value'];
            } catch (ValidationException $exception) {
                $errors = $exception->validator->errors()->all();
                foreach ($errors as $error) {
                    $this->say($error);
                }

                return $this->repeat();
            }

            try {
                $this->survey = $this->getSurvey($selectedSurvey);
                $this->askSurveyLanguage();
            } catch (\Throwable $exception) {
                Log::error('Error while asking survey:'.$exception->getMessage());
                $this->sendEndingMessage(__('conversation.oops'));
            }
        });
    }

    /**
     * Ask the user to select one of the available languages for the selected survey.
     *
     * @return void
     */
    protected function askSurveyLanguage()
    {
        $field = [
            'label' => __('conversation.chooseALanguage'),
            'key' => 'language',
            'required' => true,
            'options' => array_merge($this->survey['enabled_languages']['available'], [$this->survey['enabled_languages']['default']]),
        ];

        $question = new SelectQuestion($field);

        $this->ask($question, function (Answer $answer) use ($question) {
            try {
                $question->setAnswer($answer);
                $selectedLanguage = $question->getAnswerValue()['value'];
            } catch (ValidationException $exception) {
                $errors = $exception->validator->errors()->all();
                foreach ($errors as $error) {
                    $this->say($error);
                }

                return $this->askCancelOrGoToListOfSurveys();
            }

            try {
                $this->selectedLanguage = $selectedLanguage;
                App::setLocale($this->selectedLanguage);
                $this->askTasks();
            } catch (\Throwable $exception) {
                $this->sendEndingMessage(__('conversation.oops'));
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
        $replace = [
            'name' => $this->survey['name'],
            'description' => $this->survey['description'],
        ];
        $this->say(__('conversation.surveySelected', $replace));
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

        // If there are no more tasks to complete, confirm the user wants to send the responses
        $this->askForSendConfirmation();
    }

    /**
     * Ask the user for confirmation before sending the responses to the Ushahidi Platform.
     *
     * @return void
     */
    private function askForSendConfirmation()
    {
        // The callback should be a method available in this class
        $options = [
            [
                'display' => __('conversation.yes'),
                'value' =>  __('conversation.yes'),
                'callback' => 'sendSurveyResponses',
            ],
            [
                'display' => __('conversation.no'),
                'value' => __('conversation.no'),
                'callback' => 'cancelConversation',
            ],
        ];

        return $this->askDecision(__('conversation.shouldSendResponses'), $options);
    }

    /**
     * Try to create a post sending the responses to the platform.
     *
     * @return void
     */
    private function sendSurveyResponses()
    {
        try {
            $this->sendResponseToPlatform();
            $this->sendEndingMessage(__('conversation.thanksForSubmitting'));
        } catch (\Throwable $exception) {
            $this->sendEndingMessage(__('conversation.oops'));
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
            $this->userCanAskForInfo = true;
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
            if (trim($answer->getText()) === '?' && $this->userCanAskForInfo) {
                $this->userCanAskForInfo = false;
                $this->repeat();
                $this->say($question->getMoreInfoContent());
                $this->say(__('conversation.requestToFillIn'));

                return;
            }
            try {
                $question->setAnswer($answer);
                $this->answers[] = $question->getAnswerResponse();
            } catch (ValidationException $exception) {
                $this->userCanAskForInfo = true;

                $errors = $exception->validator->errors()->all();
                foreach ($errors as $error) {
                    $this->say($error);
                }

                if ($question->hasHints()) {
                    $this->say($question->getHints());
                }

                return $this->repeat();
            }

            $this->fields->forget($field['id']);
            $this->askNextField();
        });
        if ($question->hasHints() && $question->shouldShowHintsByDefault()) {
            $this->say($question->getHints());
        }
        $this->say(__('conversation.showMoreInfo'));
    }

    /**
     * Prompt the user to cancel or go to the list of surveys
     * using the decision question function.
     *
     * @return void
     */
    protected function askCancelOrGoToListOfSurveys()
    {
        // The callback should be a method available in this class
        $options = [
            [
                'display' => __('conversation.cancel'),
                'value' =>  __('conversation.cancelValue'),
                'callback' => 'cancelConversation',
            ],
            [
                'display' => __('conversation.goToListOfSurveys'),
                'value' => __('conversation.goToListOfSurveysValue'),
                'callback' => 'askSurvey',
            ],
        ];

        return $this->askDecision(__('conversation.whatDoYouWantToDo'), $options);
    }

    /**
     * Create and aks a question with desicion options, if one of the options is choosen
     * the callback for the option gets called.
     *
     * @param string $text
     * @param array $options
     * @return void
     */
    protected function askDecision(string $text = null, array $options)
    {
        $question = Question::create($text);
        $question->addButtons(array_map(function ($option) {
            return Button::create($option['display'])->value($option['value']);
        }, $options));

        $this->ask($question, function (Answer $answer) use ($options) {
            $selectedOption = Collection::make($options)->firstWhere('value', trim($answer->getText()));
            if (! $selectedOption) {
                $this->say(__('conversation.sorryIDidntCatchThat'));

                return $this->repeat();
            }

            return call_user_func([$this, $selectedOption['callback']]);
        });
    }

    /**
     * Cancels the conversation sending an thanks ending message.
     *
     * @return void
     */
    protected function cancelConversation()
    {
        $this->sendEndingMessage(__('conversation.thankYou'));
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
            'title' => $titleField ? $titleField['value']['value'] : null,
            'locale' => 'en_US',
            'post_content' => $this->postContent,
            'form_id' => $this->survey['id'],
            'type' => 'report',
            'completed_stages' => [],
            'published_to' => [],
            'post_date' => now()->toISOString(),
            'enabled_languages' => [],
            'content' => $descriptionField ? $descriptionField['value']['value'] : null,
        ];

        try {
            $this->sdk->createPost($post);
        } catch (\Throwable $ex) {
            Log::error("Couldn't save post: ".$ex->getMessage());
            throw $ex;
        }
    }
}
