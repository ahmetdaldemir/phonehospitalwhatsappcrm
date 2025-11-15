<?php

return [
    /*
    |--------------------------------------------------------------------------
    | WhatsApp Bot Flow Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration defines the conversation flow for the WhatsApp bot.
    | Each state represents a step in the conversation, and defines what
    | message to send and what the next state should be based on user input.
    |
    */

    'flow' => [
        'start' => [
            'message' => 'Welcome to Phone Hospital! 👋\n\nHow can we help you today?\n\n1. Report a repair issue\n2. Check ticket status\n3. Contact support',
            'next_states' => [
                '1' => 'report_issue',
                '2' => 'check_status',
                '3' => 'contact_support',
            ],
            'validation' => null,
        ],

        'report_issue' => [
            'message' => 'Great! Let\'s start by getting your phone details.\n\nWhat is your phone brand?\n(e.g., Apple, Samsung, Xiaomi)',
            'next_states' => [
                '*' => 'get_model', // Any input goes to next state
            ],
            'validation' => 'required|string|max:100',
            'data_key' => 'brand',
        ],

        'get_model' => [
            'message' => 'What is your phone model?\n(e.g., iPhone 14, Galaxy S23)',
            'next_states' => [
                '*' => 'get_problem',
            ],
            'validation' => 'required|string|max:100',
            'data_key' => 'model',
        ],

        'get_problem' => [
            'message' => 'What is the problem with your phone?\n\n1. Screen Repair\n2. Battery Replacement\n3. Charging Port\n4. Camera Repair\n5. Water Damage\n6. Software Issue\n7. Other',
            'next_states' => [
                '1' => 'get_problem_other',
                '2' => 'get_problem_other',
                '3' => 'get_problem_other',
                '4' => 'get_problem_other',
                '5' => 'get_problem_other',
                '6' => 'get_problem_other',
                '7' => 'get_problem_custom',
            ],
            'validation' => null,
            'data_key' => 'problem_type',
            'problem_map' => [
                '1' => 'Screen Repair',
                '2' => 'Battery Replacement',
                '3' => 'Charging Port',
                '4' => 'Camera Repair',
                '5' => 'Water Damage',
                '6' => 'Software Issue',
            ],
        ],

        'get_problem_custom' => [
            'message' => 'Please describe the problem:',
            'next_states' => [
                '*' => 'get_problem_other',
            ],
            'validation' => 'required|string|max:500',
            'data_key' => 'problem_type',
        ],

        'get_problem_other' => [
            'message' => 'Would you like to upload photos of the problem?\n\n1. Yes, I\'ll send photos\n2. No, continue without photos',
            'next_states' => [
                '1' => 'wait_for_photos',
                '2' => 'get_customer_name',
            ],
            'validation' => null,
        ],

        'wait_for_photos' => [
            'message' => 'Please send photos of the problem. Send multiple photos if needed. Type "done" when finished.',
            'next_states' => [
                'done' => 'get_customer_name',
                '*' => 'wait_for_photos', // Stay in same state for more photos
            ],
            'validation' => null,
            'accepts_media' => true,
        ],

        'get_customer_name' => [
            'message' => 'What is your name?',
            'next_states' => [
                '*' => 'create_ticket',
            ],
            'validation' => 'required|string|max:255',
            'data_key' => 'customer_name',
        ],

        'create_ticket' => [
            'message' => 'Thank you! We\'re creating your repair ticket...',
            'next_states' => [
                '*' => 'ticket_created',
            ],
            'validation' => null,
            'action' => 'create_ticket', // Special action to create ticket
        ],

        'ticket_created' => [
            'message' => '✅ Your ticket has been created successfully!\n\nTicket ID: {ticket_id}\n\nWe will contact you soon. Is there anything else we can help you with?\n\n1. Report another issue\n2. Check ticket status\n3. No, thank you',
            'next_states' => [
                '1' => 'report_issue',
                '2' => 'check_status',
                '3' => 'end',
            ],
            'validation' => null,
        ],

        'check_status' => [
            'message' => 'Please enter your ticket ID:',
            'next_states' => [
                '*' => 'show_ticket_status',
            ],
            'validation' => 'required|uuid',
            'data_key' => 'ticket_id_search',
        ],

        'show_ticket_status' => [
            'message' => 'Looking up your ticket...',
            'next_states' => [
                '*' => 'start',
            ],
            'validation' => null,
            'action' => 'show_ticket_status',
        ],

        'contact_support' => [
            'message' => 'For support, please contact us at:\n\n📞 Phone: +1-555-0100\n📧 Email: support@phonehospital.com\n\nOr visit our website: www.phonehospital.com\n\nType "back" to return to main menu.',
            'next_states' => [
                'back' => 'start',
                '*' => 'contact_support',
            ],
            'validation' => null,
        ],

        'end' => [
            'message' => 'Thank you for using Phone Hospital! Have a great day! 👋',
            'next_states' => [],
            'validation' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Messages
    |--------------------------------------------------------------------------
    */
    'default_messages' => [
        'invalid_input' => 'Sorry, I didn\'t understand that. Please try again.',
        'error' => 'An error occurred. Please try again later or contact support.',
        'no_ticket_found' => 'Ticket not found. Please check your ticket ID and try again.',
    ],
];

