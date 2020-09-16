# Introducing the screens concept

A screen is an special type of question, extending the Botman question, designed to wrap a field question and control the interaction of the user with the question.



# Explaining the three levels of interaction

Here's how the different levels look:

[https://whimsical.com/7VzPWjnvBo7q8XjJ2tgZ9L](https://whimsical.com/7VzPWjnvBo7q8XjJ2tgZ9L)

And here is how each level work:

## **Survey Conversation level:**

Has the survey and fields data. Creates question objects and pass them to the screens. It communicates with screens only. 

- It gets the text content from the screen and passes it to botman.
- When the user sends something, it takes the answer from botman and passes it to the screen.
- If the screen indicates that it should be sent back again to the user, the question screen is repeated with it's new content (maybe is the next/previous page, or hints, or errors, we'll never know at this level). This step will repeat until the question screen indicates otherwise, meaning that there will not be more interaction with the user for that question.
- After that, the flow will continue as it is already designed.

## **Question Screen level:**

Everything the user see, read, send or interact with have to go through this level. Things like getting the payload from the field question can be done by calling the methods on the the field question itself.

- It gets the field question label, instructions, options, hints, errors, etc from the field question and paginates accordingly by taking into consideration things like the max characters limit stablished and the amount of characters reserved to include navigation options.
- It intercepts all the answers passed from the survey conversation to verify if the user is trying to navigate or interact with the question before answering. If the input sent is not recognized as a known trigger and there is not any other condition to prevent it, the answer will be passed to the field question for evaluation.
- It uses the the field question to determine which options to include, for example: Skip question, Show more info, etc

## **Field Question Level:**

This is where translations, validations, and everything else happens. In other words, anything field-specific or related to the field data happens at this level.


# The QuestionScreen class

In order to separate concerns between asnwering or choosing a option and interacting and navigating through the content of a question, we created the Question Screen. Which basically is a Botman Question wrapping our FieldQuestions. This allow us to pass the question screens to the botman bot, as questions, and handle all user interactions for the question in context.


The question screen takes all the text pieces from the FieldQuestion and paginate them, adding navigation options also.

**Important**:
This does not apply for transitions between questions or steps.
