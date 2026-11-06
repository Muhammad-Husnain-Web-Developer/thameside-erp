<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Company Profile
    |--------------------------------------------------------------------------
    |
    | Single-tenant demo data for the fictional distributor. These values are
    | printed on every generated document (invoices, POs, payslips) and are
    | shared with the frontend through Inertia.
    |
    */

    'company' => [
        'name' => env('ERP_COMPANY_NAME', 'Thameside Kitchen Supply Ltd'),
        'short_name' => env('ERP_COMPANY_SHORT_NAME', 'Thameside Kitchen Supply'),
        'tagline' => env('ERP_COMPANY_TAGLINE', 'Kitchen appliance wholesale · London'),
        'address_line_1' => env('ERP_COMPANY_ADDRESS_LINE_1', 'Unit 14, Bankside Wharf'),
        'address_line_2' => env('ERP_COMPANY_ADDRESS_LINE_2', 'Mill Lane'),
        'city' => env('ERP_COMPANY_CITY', 'London'),
        'postcode' => env('ERP_COMPANY_POSTCODE', 'SE1 2QT'),
        'country' => env('ERP_COMPANY_COUNTRY', 'United Kingdom'),
        'email' => env('ERP_COMPANY_EMAIL', 'orders@thamesidekitchen.co.uk'),
        'phone' => env('ERP_COMPANY_PHONE', '+44 20 7946 0311'),
        'vat_number' => env('ERP_COMPANY_VAT_NUMBER', 'GB 452 8891 07'),
        'company_number' => env('ERP_COMPANY_NUMBER', '09214478'),
        'bank_name' => env('ERP_COMPANY_BANK_NAME', 'Lloyds Bank'),
        'bank_sort_code' => env('ERP_COMPANY_BANK_SORT_CODE', '30-96-14'),
        'bank_account' => env('ERP_COMPANY_BANK_ACCOUNT', '41882063'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Currency & Tax
    |--------------------------------------------------------------------------
    |
    | All monetary values are stored as integer pence across the application.
    | The VAT rate is held in basis points so the maths stays integer-based.
    |
    */

    'currency' => env('ERP_CURRENCY', 'GBP'),
    'currency_symbol' => env('ERP_CURRENCY_SYMBOL', '£'),
    'price_decimals' => 2,

    'vat_rate_bps' => (int) env('ERP_VAT_RATE_BPS', 2000),
    'vat_rate_label' => env('ERP_VAT_RATE_LABEL', '20%'),

    /*
    |--------------------------------------------------------------------------
    | Trading Defaults
    |--------------------------------------------------------------------------
    */

    'payment_terms_days' => (int) env('ERP_PAYMENT_TERMS_DAYS', 30),
    'low_stock_threshold' => (int) env('ERP_LOW_STOCK_THRESHOLD', 10),

    /*
    |--------------------------------------------------------------------------
    | Document Number Prefixes
    |--------------------------------------------------------------------------
    */

    'prefixes' => [
        'quote' => env('ERP_PREFIX_QUOTE', 'QUO'),
        'sales_order' => env('ERP_PREFIX_SALES_ORDER', 'SO'),
        'invoice' => env('ERP_PREFIX_INVOICE', 'INV'),
        'credit_note' => env('ERP_PREFIX_CREDIT_NOTE', 'CRN'),
        'purchase_order' => env('ERP_PREFIX_PURCHASE_ORDER', 'PO'),
        'goods_receipt' => env('ERP_PREFIX_GOODS_RECEIPT', 'GRN'),
        'bill' => env('ERP_PREFIX_BILL', 'BILL'),
        'journal' => env('ERP_PREFIX_JOURNAL', 'JE'),
        'payment' => env('ERP_PREFIX_PAYMENT', 'PAY'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Insights (OpenAI-compatible provider such as Groq)
    |--------------------------------------------------------------------------
    |
    | The AI module is read-only: it narrates forecasts and anomalies that are
    | calculated in PHP. Without a key the deterministic fallback is shown.
    |
    */

    'ai' => [
        'base_url' => env('AI_BASE_URL', 'https://api.groq.com/openai/v1'),
        'model' => env('AI_MODEL', 'llama-3.3-70b-versatile'),
        'key' => env('AI_API_KEY'),
    ],

];
