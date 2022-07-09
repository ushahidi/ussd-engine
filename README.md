# Ushahidi USSD Engine

## About this project
The Ushahidi platform allows for data collection via SMS, Email, Twitter, Web and smartphone apps on Android and iOS. There’s increased demand for a low cost alternative to SMS as a data source on the platform. Currently, data from SMS comes in unstructured, and requires manual intervention to ensure it conforms to the structure of surveys. I.e it takes a massive amount of human effort to process data from SMS into meaningful information. Integrating USSD into Ushahidi would increase the ability of deployers to respond to issues in a timely and efficient manner without increasing the number of volunteers they require to clean and structure the data they receive, allowing them to focus on the needs of those they are serving at this critical time. 

## About BotMan Studio and Laravel

BotMan Studio is a [Laravel](https://laravel.com)  and [Botman](http://botman.io) bundled version that makes your chatbot development experience better. By providing testing tools, an out of the box web driver implementation and additional tools like an enhanced CLI with driver installation, class generation and configuration support, it speeds up the development significantly.
BotMan is licensed under the MIT licenses.

### Donating to BotMan
[Donate](https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=m%2epociot%40googlemail%2ecom&lc=CY&item_name=BotMan&no_note=0&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donateCC_LG%2egif%3aNonHostedGuest)

## Code of conduct
Participating in this project requires adhering to the [Ushahidi code of conduct](https://docs.ushahidi.com/platform-developer-documentation/code-of-conduct)

### Documentation

You can find the BotMan and BotMan Studio documentation at [http://botman.io](http://botman.io).

Also, you can find references about Laravel at the [official documentation](https://laravel.com/docs/5.7)

## Installation

Please check the official Laravel [installation guide](https://laravel.com/docs/5.7/installation#installation) for server requirements before you start. 

Clone the repository

    git clone git@github.com:ushahidi/ussd-engine.git

Switch to the repo folder

    cd ussd-engine

Install all the dependencies using composer
    
    composer update
    
    composer install
    

Copy the example env file and make the required configuration changes in the .env file

    cp .env.example .env

Generate a new application key

    php artisan key:generate

Start the local development server

    php artisan serve

You can now access the server at http://localhost:8000

**TL;DR command list**

    git clone git@github.com:ushahidi/ussd-engine.git
    cd ussd-engine
    composer update
    composer install
    cp .env.example .env
    php artisan key:generate
    php artisan serve
    
**Make sure you set the correct values for your environment variables.** [Environment variables](#environment-variables)

# Code overview

Across the project we use a wide set of concepts related to Botman. We strongly suggest to read the "Core Concepts" guide at [Botman documentation](https://botman.io/2.0/welcome).

## Conversations

All the interaction with users and responses storage is structured using [Botman Conversation classes](https://botman.io/2.0/conversations).

Conversations are grouped in the `app/Conversations` folder.

Here's the list of available conversations:

- `SurveyConversation`: Contains all the logic for promting users which survey they want to complete, guide them through the form fields and store responses.


## Drivers

Botman is designed to work with different messaging channels.
Each channel is powered by it's own driver, which is reponsible for handling incoming and outgoing messages from and to the messaging channel respectively.

BotMan ships with support for a number of different messaging channels. You can find the list of supported ones in the documentation.

We created a custom driver for [Africa's Talking USSD service](https://africastalking.com/ussd). You can find it in the `app/Drivers` folder. We use it to correctly extract the payload from each request recived from Africa's Talking gateway and to parse each message returned by Botman into text the Africa's Talking gateway can understand.

Supported drivers in this project:
- Africa's Talking Driver


## Environment variables
Some important environment variables are:
- `CACHE_DRIVER`: This variable defines the cache driver to use in the project. Conversations and responses are stored using the Laravel's cache services. You can read more about it [here](https://laravel.com/docs/5.7/cache).
- `USHAHIDI_PLATFORM_API_URL`: The URL where you are hosting your instance of the Ushahidi platform.
- `USHAHIDI_PLATFORM_API_VERSION`: The Ushahidi platform version you are using. Note: This project requires the Platform API to be version 5 or later.
- `USHAHIDI_PLATFORM_API_TIMEOUT`:  Depending on your setup, you may want to set a custom timeout for requests to the Ushahidi Platform. It defaults to 2 seconds.
- `USSD_MAX_CHARACTERS_PER_PAGE`: USSD messages are limited to a fixed amount of characters depending on the telecommunications service provider. This allows to paginate the content delivered to your users. It defaults to 160 characters.

## Settings file

The code checks for a settings.json file in the root of the project. Through this file it's possible to tweak some of the bot's handling of aspects such as default field values, or limiting which surveys are offered to users.

Further references on the settings that can be set may be found in [settings.php](./config/settings.php)

## Custom language strings
If you would like to change any of the strings used by the bot, you may do so by providing a `lang_strings.json` file in the root of the project. The json file should have a key for each locale, and inside each locale a dictionary of keys to text. The keys would match the laravel structure of group.entry . For example:

```
{
  "en": {
    "conversation.selectSurvey": "Choose your adventure",
    "conversation.thanksForSubmitting": "Good job!"
  },
  "es": {
    "conversation.selectSurvey": "Elija su aventura",
    "conversation.thanksForSubmitting": "¡Buen trabajo!"
  }    
}
```

This file would override the `selectSurvey` and `thanksforSubmitting` translations present in the [resources/lang/en/conversation.php](./resources/lang/en/conversation.php) and [resources/lang/es/conversation.php](./resources/lang/es/conversation.php) files.

The file is loaded by the [CustomLangStringsProvider](./app/Providers/CustomLangStringsProvider.php) service.

## TODO
 - API Specification
 - Testing
 
 ## License 
 This project is licensed under the AGPLv3. [Find the full license here](LICENSE.md)

