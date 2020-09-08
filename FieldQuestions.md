# FieldQuestion

Each survey field is being and should be handled as a Botman Question, so we can get text and pass answers when interacting with the Botman bot.

We created the FieldQuestion class, a class that **extends the Botman Question class** to create a new one able to take each field data and define the behavior of that question depending on the data provided. The role of that wrapper class is key to provide the UX we want to the end users.

Some of the responsibilities if the FieldQuestion class are:
- **Answer validations**: Depending on the field types, for example: If the field is of type number, the validation rules will include validating that the user provided a valid number. Another example are the field types that include multiple options, like categories, the validation rules for those type of fields will include validating that the user chose a valid option.
- **Text management**: Provide each text piece when requested by the user or as part of the default behaior. That includes the field label, description, instructions, options, etc...
- **Translations**: Translations for each of text piece is found on the field translations.

## General considerations:

- USSD messages are up to 182 alphanumeric characters long.
- Not all fields are required.

## Africa's Talking Specific considerations:

- Each reply should include wether "CON " or "END ", which left 4 characters less than the limit. This is handled in the Africa's Talking Driver.
- Africa's Talking concatenates all the user input within the session with a `*` until the session ends. Does this compromise the user response characters limit? TBD

# Field types

On v5 of the Platform API, each field contains an `input` and a `type` property.

The input represents the type of input to use for that field when rendering the form. The type is used to validate the value for the field using predefined rules.

The inputs supported by the Ushahidi platform are: 

- **text**: used with the varchar, geometry, title, markdown, text, and description data types.
- **location**: used with the point data type
- **textarea**: used with the description data type.
- **date**: used with datetime data type.
- **select**: used with a set of options of type varchar
- **upload:** used with the media data type
- **checkbox:** used with a set of options of type varchar
- **tags:** used with a set of options, each option is a Category object
- **video:** used with the video data type
- **datetime:** used with the datetime data type

# Ushahidi Field Types Classification

| Field         | Input    | Type        | Question Type | Class Implementation |
|---------------|----------|-------------|---------------|----------------------|
| Title         | text     | title       | Text          | Title                |
| Description   | text     | description | Text          | Description          |
| Short Text    | text     | varchar     | Text          | Short Text           |
| Long Text     | textarea | text        | Text          | Long Text            |
| Decimal       | number   | decimal     | Text          | Decimal              |
| Integer       | number   | int         | Text          | Integer              |
| Location      | location | point       | Text          | Location             |
| Date          | date     | datetime    | Text          | Date                 |
| Datetime      | datetime | datetime    | Text          | Datetime             |
| Radio Buttons | radio    | varchar     | Select        | Radio Buttons        |
| Checkboxes    | checkbox | varchar     | Select        | Checkboxes           |
| Markdown      | markdown | markdown    | Text          | Markdown             |
| Categories    | tags     | tags        | Select        | Categories           |


# The FieldQuestion class

The field question class has many methods that can be used to define the behavior of the conversation and get the field information.

Some important methods of this class are:

### `getAttributeName(): string`

Returns the attribute name to be use when performing translations and validations.

### `getValueFromAnswer(Answer $answer)`

Extract the value to be  validated from the Answer object.

### `setAnswer(Answer $answer)`

This is the method we use when we want the FieldQuestion to extract and validate the value of the user answer.

###  `getRules(): array`

Each FieldQuestion should have the set of rules needed for validating values based on the field types.

### `translate(string $accesor, array $context): string`

Find the translated value for the provided accesor in the provided context. The context can be the field information or category information.

# Text and select questions

`TextQuestion` and `SelectQuestion` are generic FieldQuestions types created to implement common functionalities among different text and select questions respectively.
