# Surveys conversation

All the interaction needed to get user responses for a survey are defined in the Surveys conversation.

The SurveyConversation class is extending the Botman conversation class. We are closely following what Botman provides in this aspect, no custom behiors are implemented.

You can read more about Botman conversations here: https://botman.io/2.0/conversations

In other words, the SurveyConversation class, at a high level, is just creating and passing questions (actually QuestionScreens, which extends Botman question class) to Botman. 
Botman itself is responsible for the caching of the conversation and the serialization needed for that.

The survey conversation can be started anywhere, but as of right now it is being started as a fallback from the AfricasTalkingController.

## How the survey conversation works

First there are some important things to keep in mind:
- Conversations are serialized, all the properties and their value in this class should be serializable. In case you need something as part of the conversation that can't be serialized, you will have to unset and set those properties before and after serialization. You can use the `__sleep` and `__wakeup` methods for that. Example: Ushahidi Client instance.
- There is **no** any hook or magic methods naming system. We use the `ask` prefix as a convention to indicate we are going to use Botman to ask the user a question. 

### Asking questions

Botman start conversations through the `run` method. This is where we start asking our first question. Note that we don't have a predefined and fixed order for questions. We have designed the questions flow throught a set of consecutive function calls, sometimes branching by conditional expressions.

For all the questions we are asking through Botman, you will see the following pattern:
- The `askSomething` function, where we create the Botman question (actually `QuestionScreen` or `FieldQuestion`, both are custom question classes, more about that later), and pass it to the `ask` method of Botman.
- The `getSomethingHandler` function, where we define the callback Botman will use when it receives an answer from the user. That callback always receives an `Answer` object which we use to get and parse the user response. In this callback you will find always a call to the next question or action to perform. This last part is how we "iterate" over the conversation questions and fields.

Example:

```php
class HumanConversation extends Conversation
{
  
  public function run()  {
    $this->askName();
  }

  public function askName() {
    $nameQuestion = new Question('What is your name');

    $this->ask($nameQuestion, $this->getNameHandler($nameQuestion));
  }

  public function getNameHandler(Question $nameQuestion): Closure {
    return function (Answer $answer) use ($nameQuestion) {
      echo 'The question was:' . $nameQuestion->getText();
      echo 'The answer is:' . $answer->getText();

      $this->askAge();
    }
  }

  public function askAge...
}
```

## Communicating with the Ushahidi Platform
Due to the nature of this project, we are using the Ushahidi Platform Client multiple times:
1. When the conversation starts (`run` method), we fetch all the available surveys in the Ushahidi deployment. Each survey can have a lot of content, that's why in this first request we just fetch a minimal format of the surveys enough to show the user all the available survey options.
2. Once the user has chosen a survey, we fetch all the information (tasks, fields, etc) for the survey the user selected.
3. Finally, once we have collected all the user answers, we send them to the platform.

