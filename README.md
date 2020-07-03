<h1 align="center">Ushahidi USSD Engine</h1>

## Getting started

## About this project
This project was created to enable USSD integrations in the Ushahidi Platform.


### About BotMan Studio and Laravel

BotMan Studio is a [Laravel](https://laravel.com)  and [Botman](http://botman.io) bundled version that makes your chatbot development experience better. By providing testing tools, an out of the box web driver implementation and additional tools like an enhanced CLI with driver installation, class generation and configuration support, it speeds up the development significantly.
BotMan is licensed under the MIT licenses.

#### Donating to BotMan
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


## TODO
 - API Specification
 - Testing
 
 ## License 
 This project is licensed under the AGPLv3. [Find the full license here](LICENSE.md)

