<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'Потрібно прийняти :attribute.',
    'accepted_if' => 'Потрібно прийняти :attribute, коли :other має значення :value.',
    'active_url' => 'Поле :attribute має бути коректним посиланням.',
    'after' => 'Поле :attribute має містити дату після :date.',
    'after_or_equal' => 'Поле :attribute має містити дату не раніше :date.',
    'alpha' => 'Поле :attribute може містити лише літери.',
    'alpha_dash' => 'Поле :attribute може містити лише літери, цифри, дефіси та підкреслення.',
    'alpha_num' => 'Поле :attribute може містити лише літери та цифри.',
    'any_of' => 'Поле :attribute заповнено неправильно.',
    'array' => 'Поле :attribute має бути масивом.',
    'array_keys' => 'Поле :attribute може містити лише такі ключі: :values.',
    'ascii' => 'Поле :attribute може містити лише однобайтові символи та знаки.',
    'base64' => 'Поле :attribute має бути коректним рядком Base64.',
    'before' => 'Поле :attribute має містити дату до :date.',
    'before_or_equal' => 'Поле :attribute має містити дату не пізніше :date.',
    'between' => [
        'array' => 'Поле :attribute має містити від :min до :max елементів.',
        'file' => 'Файл у полі :attribute має важити від :min до :max кілобайтів.',
        'numeric' => 'Поле :attribute має бути в межах від :min до :max.',
        'string' => 'Поле :attribute має містити від :min до :max символів.',
    ],
    'boolean' => 'Поле :attribute має бути «так» або «ні».',
    'can' => 'Поле :attribute містить недозволене значення.',
    'confirmed' => 'Підтвердження в полі :attribute не збігається.',
    'contains' => 'У полі :attribute бракує обовʼязкового значення.',
    'current_password' => 'Пароль неправильний.',
    'date' => 'Поле :attribute має містити коректну дату.',
    'date_equals' => 'Поле :attribute має містити дату :date.',
    'date_format' => 'Поле :attribute має відповідати формату :format.',
    'decimal' => 'Поле :attribute має містити :decimal знаків після коми.',
    'declined' => 'Потрібно відхилити :attribute.',
    'declined_if' => 'Потрібно відхилити :attribute, коли :other має значення :value.',
    'different' => 'Поля :attribute та :other мають відрізнятися.',
    'digits' => 'Поле :attribute має містити :digits цифр.',
    'digits_between' => 'Поле :attribute має містити від :min до :max цифр.',
    'dimensions' => 'Зображення в полі :attribute має неприпустимі розміри.',
    'distinct' => 'Поле :attribute містить повторюване значення.',
    'doesnt_contain' => 'Поле :attribute не може містити жодного з таких значень: :values.',
    'doesnt_end_with' => 'Поле :attribute не може закінчуватися на: :values.',
    'doesnt_start_with' => 'Поле :attribute не може починатися з: :values.',
    'email' => 'Поле :attribute має містити коректну електронну адресу.',
    'encoding' => 'Поле :attribute має бути в кодуванні :encoding.',
    'ends_with' => 'Поле :attribute має закінчуватися одним із: :values.',
    'enum' => 'Вибране значення в полі :attribute неприпустиме.',
    'exists' => 'Вибране значення в полі :attribute неприпустиме.',
    'extensions' => 'Файл у полі :attribute має мати одне з розширень: :values.',
    'file' => 'Поле :attribute має містити файл.',
    'filled' => 'Поле :attribute не може бути порожнім.',
    'gt' => [
        'array' => 'Поле :attribute має містити більше ніж :value елементів.',
        'file' => 'Файл у полі :attribute має важити більше ніж :value кілобайтів.',
        'numeric' => 'Поле :attribute має бути більше за :value.',
        'string' => 'Поле :attribute має містити більше ніж :value символів.',
    ],
    'gte' => [
        'array' => 'Поле :attribute має містити щонайменше :value елементів.',
        'file' => 'Файл у полі :attribute має важити щонайменше :value кілобайтів.',
        'numeric' => 'Поле :attribute має бути не менше за :value.',
        'string' => 'Поле :attribute має містити щонайменше :value символів.',
    ],
    'hex_color' => 'Поле :attribute має містити коректний шістнадцятковий колір.',
    'image' => 'Поле :attribute має містити зображення.',
    'in' => 'Вибране значення в полі :attribute неприпустиме.',
    'in_array' => 'Значення поля :attribute має бути серед значень :other.',
    'in_array_keys' => 'Поле :attribute має містити щонайменше один із таких ключів: :values.',
    'integer' => 'Поле :attribute має бути цілим числом.',
    'ip' => 'Поле :attribute має містити коректну IP-адресу.',
    'ipv4' => 'Поле :attribute має містити коректну адресу IPv4.',
    'ipv6' => 'Поле :attribute має містити коректну адресу IPv6.',
    'json' => 'Поле :attribute має містити коректний рядок JSON.',
    'list' => 'Поле :attribute має бути списком.',
    'lowercase' => 'Поле :attribute має бути написане малими літерами.',
    'lt' => [
        'array' => 'Поле :attribute має містити менше ніж :value елементів.',
        'file' => 'Файл у полі :attribute має важити менше ніж :value кілобайтів.',
        'numeric' => 'Поле :attribute має бути менше за :value.',
        'string' => 'Поле :attribute має містити менше ніж :value символів.',
    ],
    'lte' => [
        'array' => 'Поле :attribute має містити не більше ніж :value елементів.',
        'file' => 'Файл у полі :attribute має важити не більше ніж :value кілобайтів.',
        'numeric' => 'Поле :attribute має бути не більше за :value.',
        'string' => 'Поле :attribute має містити не більше ніж :value символів.',
    ],
    'mac_address' => 'Поле :attribute має містити коректну MAC-адресу.',
    'max' => [
        'array' => 'Поле :attribute має містити не більше ніж :max елементів.',
        'file' => 'Файл у полі :attribute має важити не більше ніж :max кілобайтів.',
        'numeric' => 'Поле :attribute має бути не більше за :max.',
        'string' => 'Поле :attribute має містити не більше ніж :max символів.',
    ],
    'max_digits' => 'Поле :attribute має містити не більше ніж :max цифр.',
    'mimes' => 'Поле :attribute має містити файл типу: :values.',
    'mimetypes' => 'Поле :attribute має містити файл типу: :values.',
    'min' => [
        'array' => 'Поле :attribute має містити щонайменше :min елементів.',
        'file' => 'Файл у полі :attribute має важити щонайменше :min кілобайтів.',
        'numeric' => 'Поле :attribute має бути не менше за :min.',
        'string' => 'Поле :attribute має містити щонайменше :min символів.',
    ],
    'min_digits' => 'Поле :attribute має містити щонайменше :min цифр.',
    'missing' => 'Поле :attribute має бути відсутнє.',
    'missing_if' => 'Поле :attribute має бути відсутнє, коли :other має значення :value.',
    'missing_unless' => 'Поле :attribute має бути відсутнє, якщо :other не має значення :value.',
    'missing_with' => 'Поле :attribute має бути відсутнє, коли задано :values.',
    'missing_with_all' => 'Поле :attribute має бути відсутнє, коли задано всі :values.',
    'multiple_of' => 'Поле :attribute має бути кратним :value.',
    'not_in' => 'Вибране значення в полі :attribute неприпустиме.',
    'not_regex' => 'Поле :attribute має неправильний формат.',
    'numeric' => 'Поле :attribute має бути числом.',
    'password' => [
        'letters' => 'Поле :attribute має містити щонайменше одну літеру.',
        'mixed' => 'Поле :attribute має містити щонайменше одну велику та одну малу літеру.',
        'numbers' => 'Поле :attribute має містити щонайменше одну цифру.',
        'symbols' => 'Поле :attribute має містити щонайменше один спеціальний символ.',
        'uncompromised' => 'Це значення :attribute траплялося у витоках даних. Виберіть інше.',
    ],
    'present' => 'Поле :attribute має бути присутнє.',
    'present_if' => 'Поле :attribute має бути присутнє, коли :other має значення :value.',
    'present_unless' => 'Поле :attribute має бути присутнє, якщо :other не має значення :value.',
    'present_with' => 'Поле :attribute має бути присутнє, коли задано :values.',
    'present_with_all' => 'Поле :attribute має бути присутнє, коли задано всі :values.',
    'prohibited' => 'Поле :attribute заповнювати не можна.',
    'prohibited_if' => 'Поле :attribute заповнювати не можна, коли :other має значення :value.',
    'prohibited_if_accepted' => 'Поле :attribute заповнювати не можна, коли прийнято :other.',
    'prohibited_if_declined' => 'Поле :attribute заповнювати не можна, коли відхилено :other.',
    'prohibited_unless' => 'Поле :attribute заповнювати не можна, якщо :other не належить до :values.',
    'prohibits' => 'Поле :attribute забороняє заповнювати :other.',
    'regex' => 'Поле :attribute має неправильний формат.',
    'required' => 'Поле :attribute обовʼязкове.',
    'required_array_keys' => 'Поле :attribute має містити записи для: :values.',
    'required_if' => 'Поле :attribute обовʼязкове, коли :other має значення :value.',
    'required_if_accepted' => 'Поле :attribute обовʼязкове, коли прийнято :other.',
    'required_if_declined' => 'Поле :attribute обовʼязкове, коли відхилено :other.',
    'required_unless' => 'Поле :attribute обовʼязкове, якщо :other не належить до :values.',
    'required_with' => 'Поле :attribute обовʼязкове, коли задано :values.',
    'required_with_all' => 'Поле :attribute обовʼязкове, коли задано всі :values.',
    'required_without' => 'Поле :attribute обовʼязкове, коли не задано :values.',
    'required_without_all' => 'Поле :attribute обовʼязкове, коли не задано жодного з :values.',
    'same' => 'Поле :attribute має збігатися з :other.',
    'size' => [
        'array' => 'Поле :attribute має містити рівно :size елементів.',
        'file' => 'Файл у полі :attribute має важити :size кілобайтів.',
        'numeric' => 'Поле :attribute має дорівнювати :size.',
        'string' => 'Поле :attribute має містити :size символів.',
    ],
    'starts_with' => 'Поле :attribute має починатися з одного із: :values.',
    'string' => 'Поле :attribute має бути рядком.',
    'timezone' => 'Поле :attribute має містити коректний часовий пояс.',
    'unique' => 'Таке значення поля :attribute вже зайняте.',
    'uploaded' => 'Не вдалося завантажити файл у полі :attribute.',
    'uppercase' => 'Поле :attribute має бути написане великими літерами.',
    'url' => 'Поле :attribute має містити коректне посилання.',
    'ulid' => 'Поле :attribute має містити коректний ULID.',
    'uuid' => 'Поле :attribute має містити коректний UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'current_password' => [
            'required' => 'Введіть поточний пароль.',
            'current_password' => 'Поточний пароль неправильний.',
        ],
        'password' => [
            'confirmed' => 'Паролі не збігаються.',
        ],
        'code' => [
            'required' => 'Введіть код із застосунку-автентифікатора.',
        ],
        'recovery_code' => [
            'required' => 'Введіть код відновлення.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap the attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'code' => '«код підтвердження»',
        'current_password' => '«поточний пароль»',
        'email' => '«електронна пошта»',
        'images' => '«зображення»',
        'name' => '«імʼя»',
        'password' => '«пароль»',
        'password_confirmation' => '«підтвердження пароля»',
        'path' => '«шлях»',
        'paths' => '«шляхи»',
        'permissions' => '«права»',
        'recovery_code' => '«код відновлення»',
        'roles' => '«ролі»',
        'token' => '«токен»',
    ],

];
