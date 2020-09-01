<?php

namespace App\Conversations;

use App\Exceptions\EmptySurveysResultsException;
use App\Exceptions\NoSurveyTasksException;
use App\Messages\Outgoing\FieldQuestionFactory;
use App\Messages\Outgoing\LanguageQuestion;
use App\Messages\Outgoing\LastScreen;
use App\Messages\Outgoing\MessageScreen;
use App\Messages\Outgoing\QuestionScreen;
use App\Messages\Outgoing\SelectQuestion;
use App\Messages\Outgoing\SurveyQuestion;
use BotMan\BotMan\Messages\Conversations\Conversation;
use BotMan\BotMan\Messages\Incoming\Answer;
use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
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

    /**
     * The language selected by the user to interact
     * through the conversation.
     * This is used to set the App locale.
     *
     * @var string
     */
    protected $selectedLanguage;

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
            Log::error('Could not fetch available surveys:'.$exception->getMessage());
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
     * Sets the survey for the conversation.
     *
     * @param array $survey
     * @return void
     */
    public function setSurvey(array $survey): void
    {
        $this->survey = $survey;
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
                                            ->filter()
                                            ->values()
                                            ->all();

        if (empty($availableLanguagesList)) {
            return $this->askSurvey();
        }

        $questionScreen = new QuestionScreen(new LanguageQuestion($availableLanguagesList));

        $this->ask($questionScreen, $this->getInteractionLanguageHandler($questionScreen));
    }

    /**
     * Returns a callback able to handle the user interaction with the
     * interaction language question specifically.
     *
     * @param QuestionScreen $questionScreen
     * @return Closure
     */
    public function getInteractionLanguageHandler(QuestionScreen $questionScreen): Closure
    {
        return  function (Answer $answer) use ($questionScreen) {
            try {
                $questionScreen->setAnswer($answer);

                if (! $questionScreen->isDone()) {
                    if ($questionScreen->validationFailed()) {
                        $questionScreen->dontRepeatAgain();
                    }

                    return $this->ask($questionScreen, $this->getInteractionLanguageHandler($questionScreen));
                }

                if ($questionScreen->wasCanceled()) {
                    return $this->handleCancelBeforeSurveySelection();
                }

                if ($questionScreen->validationFailed()) {
                    return $this->askCancelOrGoToListOfSurveys();
                }

                $this->selectedLanguage = $questionScreen->getQuestion()->getValidatedAnswerValue();
                App::setLocale($this->selectedLanguage);
                $this->askSurvey();
            } catch (\Throwable $exception) {
                Log::error('Error while asking interaction language:'.$exception->getMessage());
                $this->sendEndingMessage(__('conversation.oops'));
            }
        };
    }

    /**
     * Ask the user to select a survey and handle the user input.
     *
     * @return void
     */
    protected function askSurvey()
    {
        $question = new SurveyQuestion($this->surveys->all());
        $questionScreen = new QuestionScreen($question);

        $this->ask($questionScreen, $this->getSurveyHandler($questionScreen));
    }

    /**
     * Returns a callback able to handle the user interaction with the
     * survey question specifically.
     *
     * @param QuestionScreen $questionScreen
     * @return Closure
     */
    protected function getSurveyHandler(QuestionScreen $questionScreen): Closure
    {
        return function (Answer $answer) use ($questionScreen) {
            try {
                $questionScreen->setAnswer($answer);

                if (! $questionScreen->isDone()) {
                    return $this->ask($questionScreen, $this->getSurveyHandler($questionScreen));
                }
                if ($questionScreen->wasCanceled()) {
                    return $this->handleCancelBeforeSurveySelection();
                }

                $selectedSurvey = $questionScreen->getQuestion()->getValidatedAnswerValue();
                $this->setSurvey($this->getSurvey($selectedSurvey));
                if ($this->shouldAskSurveyLanguage()) {
                    $this->askSurveyLanguage();
                } else {
                    $this->showSurveyInformation();
                }
            } catch (\Throwable $exception) {
                Log::error('Error while asking survey:'.$exception->getMessage());
                $this->sendEndingMessage(__('conversation.oops'));
            }
        };
    }

    /**
     * Ask the user to select one of the available languages for the selected survey.
     *
     * @return void
     */
    protected function askSurveyLanguage()
    {
        $surveyLanguages = array_filter(array_merge($this->survey['enabled_languages']['available'], [$this->survey['enabled_languages']['default']]));
        $questionScreen = new QuestionScreen(new LanguageQuestion($surveyLanguages));

        $this->ask($questionScreen, $this->getSurveyLanguageHandler($questionScreen));
    }

    /**
     * Returns a callback able to handle the user interaction with the
     * survey language question specifically.
     *
     * @param QuestionScreen $questionScreen
     * @return Closure
     */
    protected function getSurveyLanguageHandler(QuestionScreen $questionScreen): Closure
    {
        return function (Answer $answer) use ($questionScreen) {
            try {
                $questionScreen->setAnswer($answer);

                if (! $questionScreen->isDone()) {
                    if ($questionScreen->validationFailed()) {
                        $questionScreen->dontRepeatAgain();
                    }

                    return $this->ask($questionScreen, $this->getSurveyLanguageHandler($questionScreen));
                }

                if ($questionScreen->wasCanceled()) {
                    return $this->handleCancelAfterSurveySelection();
                }

                if ($questionScreen->validationFailed()) {
                    return $this->askCancelOrGoToListOfSurveys();
                }

                $this->selectedLanguage = $questionScreen->getQuestion()->getValidatedAnswerValue();
                App::setLocale($this->selectedLanguage);
                $this->showSurveyInformation();
            } catch (\Throwable $exception) {
                $this->sendEndingMessage(__('conversation.oops'));
            }
        };
    }

    /**
     * Sends a screen with the survey information to the user.
     *
     * @return void
     */
    public function showSurveyInformation()
    {
        $replace = [
            'name' => $this->survey['name'],
            'description' => $this->survey['description'],
        ];

        $messageScreen = new MessageScreen(__('conversation.surveySelected', $replace));

        $this->ask($messageScreen, $this->getShowSurveyInformationHandler($messageScreen));
    }

    /**
     * Returns a callback able to handle the user interaction while reading the survey information.
     *
     * @param MessageScreen $messageScreen
     * @return Closure
     */
    public function getShowSurveyInformationHandler(MessageScreen $messageScreen): Closure
    {
        return function (Answer $answer) use ($messageScreen) {
            try {
                $messageScreen->setAnswer($answer);

                if (! $messageScreen->isDone()) {
                    return $this->ask($messageScreen, $this->getShowSurveyInformationHandler($messageScreen));
                }

                if ($messageScreen->wasCanceled()) {
                    return $this->handleCancelAfterSurveySelection();
                }

                $this->askTasks();
            } catch (\Throwable $exception) {
                $this->sendEndingMessage(__('conversation.oops'));
            }
        };
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
        $field = [
            'label' => __('conversation.shouldSendResponses'),
            'required' => true,
            'options' => [
                [
                    'display' => __('conversation.yes'),
                    'value' => 'sendSurveyResponses',
                ],
                [
                    'display' => __('conversation.no'),
                    'value' => 'cancelConversation',
                ],
            ],
        ];

        $question = new SelectQuestion($field, 'value', 'display');

        $questionScreen = new QuestionScreen($question, false);

        $this->ask($questionScreen, $this->getCallbackHandler($questionScreen));
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
        $questionScreen = new QuestionScreen($question);

        $this->ask($questionScreen, $this->getFieldHandler($questionScreen, $field));
    }

    /**
     * Returns a callback able to handle the user interaction while answwering a field.
     *
     * @param QuestionScreen $questionScreen
     * @param array $field
     * @return Closure
     */
    public function getFieldHandler(QuestionScreen $questionScreen, array $field): Closure
    {
        return  function (Answer $answer) use ($questionScreen, $field) {
            try {
                $questionScreen->setAnswer($answer);

                if (! $questionScreen->isDone()) {
                    return $this->ask($questionScreen, $this->getFieldHandler($questionScreen, $field));
                }

                if ($questionScreen->wasCanceled()) {
                    return $this->handleCancelAfterSurveySelection();
                }

                $this->answers[] = $questionScreen->getQuestion()->toPayload();
            } catch (\Throwable $exception) {
                Log::error('Error while asking field:'.$exception->getMessage(), ['field' => $field]);
                $this->sendEndingMessage(__('conversation.oops'));
            }

            $this->fields->forget($field['id']);
            $this->askNextField();
        };
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
        $field = [
            'label' => __('conversation.whatDoYouWantToDo'),
            'required' => true,
            'options' => [
                [
                    'display' => __('conversation.cancel'),
                    'value' => 'cancelConversation',
                ],
                [
                    'display' => __('conversation.goToListOfSurveys'),
                    'value' => 'askSurvey',
                ],
            ],
        ];

        $question = new SelectQuestion($field, 'value', 'display');

        $questionScreen = new QuestionScreen($question, false);

        $this->ask($questionScreen, $this->getCallbackHandler($questionScreen));
    }

    /**
     * Returns a callback for the questions that has callbacks as option values.
     *
     * @param QuestionScreen $questionScreen
     * @return Closure
     */
    protected function getCallbackHandler(QuestionScreen $questionScreen): Closure
    {
        return function (Answer $answer) use ($questionScreen) {
            try {
                $questionScreen->setAnswer($answer);

                if (! $questionScreen->isDone()) {
                    return $this->ask($questionScreen, $this->getCallbackHandler($questionScreen));
                }

                $callback = $questionScreen->getQuestion()->getValidatedAnswerValue();

                return call_user_func([$this, $callback]);
            } catch (\Throwable $exception) {
                Log::error('Error while asking interaction language:'.$exception->getMessage());
                $this->sendEndingMessage(__('conversation.oops'));
            }
        };
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
        $lastScreen = new LastScreen($message);
        $this->ask($lastScreen, $this->getEndingMessageHandler($lastScreen));
    }

    /**
     * Returns a callback able to handle the user interaction wihle reading the ending message.
     *
     * @param LastScreen $screen
     * @return Closure
     */
    public function getEndingMessageHandler(LastScreen $screen): Closure
    {
        return function (Answer $answer) use ($screen) {
            try {
                $screen->setAnswer($answer);

                if (! $screen->isDone()) {
                    return $this->ask($screen, $this->getEndingMessageHandler($screen));
                }
            } catch (\Throwable $exception) {
                Log::error('Error while sending ending message:'.$exception->getMessage());
            }
        };
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
            Log::error("Couldn't save post: ".$ex->getMessage(), ['post' => $post]);
            throw $ex;
        }
    }

    /**
     * Returns wether the current survey is available in the current locale or not.
     *
     * @return bool
     */
    public function shouldAskSurveyLanguage(): bool
    {
        // don't ask survey lang if survey doesn't have enabled languages
        if (! isset($this->survey['enabled_languages'])) {
            return false;
        }

        $hasDefaultLanguage = isset($this->survey['enabled_languages']['default']) && ! empty($this->survey['enabled_languages']['default']);
        $hasAvailableLanguages = isset($this->survey['enabled_languages']['available']) && ! empty(array_filter($this->survey['enabled_languages']['available']));

        // if the survey doesn't have a default language or doesn't have available languages neither, don't ask for survey language
        if (! ($hasDefaultLanguage || $hasAvailableLanguages)) {
            return false;
        }

        $locale = App::getLocale();

        // if the survey default language is the same as the current locale, don't ask for survey language
        if ($hasDefaultLanguage && $this->survey['enabled_languages']['default'] == $locale) {
            return false;
        }

        // if the current locale is in the list of available languagues for the survey, don't ask for survey language
        if ($hasAvailableLanguages && in_array($locale, $this->survey['enabled_languages']['available'])) {
            return false;
        }

        return true;
    }

    public function handleCancelBeforeSurveySelection()
    {
        $this->cancelConversation();
    }

    public function handleCancelAfterSurveySelection()
    {
        $this->askSurvey();
    }
}
