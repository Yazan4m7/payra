<?php

return [
    'required' => 'حقل :attribute مطلوب.',
    'required_with' => 'حقل :attribute مطلوب عند وجود :values.',
    'email' => 'يجب أن يكون :attribute بريداً إلكترونياً صحيحاً.',
    'unique' => 'قيمة :attribute مستخدمة مسبقاً.',
    'date' => 'يجب أن يكون :attribute تاريخاً صحيحاً.',
    'before_or_equal' => 'يجب أن يكون :attribute قبل أو مساوياً لـ :date.',
    'after_or_equal' => 'يجب أن يكون :attribute بعد أو مساوياً لـ :date.',
    'string' => 'يجب أن يكون :attribute نصاً.',
    'integer' => 'يجب أن يكون :attribute رقماً صحيحاً.',
    'decimal' => 'يجب أن يحتوي :attribute على عدد صحيح من المنازل العشرية.',
    'boolean' => 'قيمة :attribute يجب أن تكون صحيحة أو خاطئة.',
    'array' => 'يجب أن يكون :attribute قائمة.',
    'in' => 'القيمة المحددة في :attribute غير صالحة.',
    'distinct' => 'يحتوي :attribute على قيمة مكررة.',
    'regex' => 'صيغة :attribute غير صالحة.',
    'min' => [
        'numeric' => 'يجب ألا يقل :attribute عن :min.',
        'string' => 'يجب ألا يقل :attribute عن :min أحرف.',
        'array' => 'يجب أن يحتوي :attribute على :min عناصر على الأقل.',
    ],
    'max' => [
        'numeric' => 'يجب ألا يزيد :attribute عن :max.',
        'string' => 'يجب ألا يزيد :attribute عن :max أحرف.',
        'array' => 'يجب ألا يحتوي :attribute على أكثر من :max عناصر.',
    ],
    'gt' => [
        'numeric' => 'يجب أن يكون :attribute أكبر من :value.',
    ],
    'attributes' => [
        'email' => 'البريد الإلكتروني',
        'password' => 'كلمة المرور',
        'name' => 'الاسم',
        'date' => 'التاريخ',
        'year' => 'السنة',
        'month' => 'الشهر',
    ],
];
