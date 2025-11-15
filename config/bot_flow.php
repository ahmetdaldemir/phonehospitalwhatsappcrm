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
            'message' => 'Welcome to Phone Hospital! 👋\n\nHow can we help you today?\n\n1. Report a repair issue\n2. Check ticket status\n3. Telefonumu satmak istiyorum\n4. Contact support',
            'next_states' => [
                '1' => 'report_issue',
                '2' => 'check_status',
                '3' => 'tradein_start',
                'telefonumu satmak istiyorum' => 'tradein_start',
                '4' => 'contact_support',
            ],
            'validation' => null,
        ],

        'tradein_start' => [
            'message' => 'Harika! Telefonunuz hakkında bilgi alalım.\n\nTelefonunuzun markası nedir?\n(e.g., Apple, Samsung, Xiaomi)',
            'next_states' => [
                '*' => 'tradein_get_model',
            ],
            'validation' => 'required|string|max:100',
            'data_key' => 'brand',
        ],

        'tradein_get_model' => [
            'message' => 'Telefonunuzun modeli nedir?\n(e.g., iPhone 14, Galaxy S23)',
            'next_states' => [
                '*' => 'tradein_ask_storage',
            ],
            'validation' => 'required|string|max:100',
            'data_key' => 'model',
        ],

        'tradein_ask_storage' => [
            'message' => 'Telefonunuzun depolama kapasitesi nedir?\n\n1. 64GB\n2. 128GB\n3. 256GB\n4. 512GB\n5. 1TB\n6. Bilmiyorum',
            'next_states' => [
                '1' => 'tradein_ask_color',
                '2' => 'tradein_ask_color',
                '3' => 'tradein_ask_color',
                '4' => 'tradein_ask_color',
                '5' => 'tradein_ask_color',
                '6' => 'tradein_ask_color',
            ],
            'validation' => null,
            'data_key' => 'storage',
            'storage_map' => [
                '1' => '64GB',
                '2' => '128GB',
                '3' => '256GB',
                '4' => '512GB',
                '5' => '1TB',
                '6' => null,
            ],
        ],

        'tradein_ask_color' => [
            'message' => 'Telefonunuzun rengi nedir?\n(e.g., Siyah, Beyaz, Mavi)',
            'next_states' => [
                '*' => 'tradein_ask_condition',
            ],
            'validation' => 'required|string|max:50',
            'data_key' => 'color',
        ],

        'tradein_ask_condition' => [
            'message' => 'Telefonunuzun genel durumu nasıl?\n\n1. Mükemmel (A) - Çizik yok, yeni gibi\n2. İyi (B) - Hafif çizikler, normal kullanım\n3. Orta (C) - Belirgin çizikler, ekran hasarı',
            'next_states' => [
                '1' => 'tradein_ask_battery',
                '2' => 'tradein_ask_battery',
                '3' => 'tradein_ask_battery',
            ],
            'validation' => null,
            'data_key' => 'condition',
            'condition_map' => [
                '1' => 'A',
                '2' => 'B',
                '3' => 'C',
            ],
        ],

        'tradein_ask_battery' => [
            'message' => 'Pil sağlığı yüzdesi nedir?\n(0-100 arası bir sayı yazın veya "bilmiyorum" yazın)',
            'next_states' => [
                'bilmiyorum' => 'tradein_ask_photos',
                '*' => 'tradein_ask_photos',
            ],
            'validation' => null,
            'data_key' => 'battery_health',
        ],

        'tradein_ask_photos' => [
            'message' => 'Telefonunuzun fotoğraflarını gönderebilir misiniz?\n\n1. Evet, fotoğraf göndereceğim\n2. Hayır, devam et',
            'next_states' => [
                '1' => 'tradein_wait_photos',
                'evet' => 'tradein_wait_photos',
                '2' => 'tradein_calculate_price',
                'hayır' => 'tradein_calculate_price',
            ],
            'validation' => null,
        ],

        'tradein_wait_photos' => [
            'message' => 'Lütfen telefonunuzun fotoğraflarını gönderin. Birden fazla fotoğraf gönderebilirsiniz. Bitirdiğinizde "tamam" yazın.',
            'next_states' => [
                'tamam' => 'tradein_calculate_price',
                'done' => 'tradein_calculate_price',
                '*' => 'tradein_wait_photos',
            ],
            'validation' => null,
            'accepts_media' => true,
        ],

        'tradein_calculate_price' => [
            'message' => 'Fiyat teklifiniz hesaplanıyor...',
            'next_states' => [
                '*' => 'tradein_show_offer',
            ],
            'validation' => null,
            'action' => 'calculate_tradein_price',
        ],

        'tradein_show_offer' => [
            'message' => '💰 *Fiyat Teklifiniz*\n\nMarka: {brand}\nModel: {model}\nDurum: {condition_label}\n\nTeklif Aralığı: {offer_min} - {offer_max} TL\n\nBu teklifi kabul ediyor musunuz?\n\n1. Evet, kabul ediyorum\n2. Hayır, teşekkürler',
            'next_states' => [
                '1' => 'tradein_ask_payment',
                'evet' => 'tradein_ask_payment',
                '2' => 'ask_further_help',
                'hayır' => 'ask_further_help',
            ],
            'validation' => null,
        ],

        'tradein_ask_payment' => [
            'message' => 'Nasıl ödeme almak istersin?\n\n1️⃣ Nakit\n2️⃣ Aksesuar Hediye Çeki\n3️⃣ Yeni cihazda indirim',
            'next_states' => [
                '1' => 'tradein_create',
                '1️⃣' => 'tradein_create',
                'nakit' => 'tradein_create',
                '2' => 'tradein_create',
                '2️⃣' => 'tradein_create',
                'aksesuar' => 'tradein_create',
                '3' => 'tradein_create',
                '3️⃣' => 'tradein_create',
                'indirim' => 'tradein_create',
            ],
            'validation' => null,
            'data_key' => 'payment_option',
            'payment_map' => [
                '1' => 'cash',
                '1️⃣' => 'cash',
                'nakit' => 'cash',
                '2' => 'voucher',
                '2️⃣' => 'voucher',
                'aksesuar' => 'voucher',
                '3' => 'tradein',
                '3️⃣' => 'tradein',
                'indirim' => 'tradein',
            ],
        ],

        'tradein_create' => [
            'message' => 'Teklifiniz kaydediliyor...',
            'next_states' => [
                '*' => 'tradein_created',
            ],
            'validation' => null,
            'action' => 'create_tradein',
        ],

        'tradein_created' => [
            'message' => '✅ Teklifiniz kaydedildi!\n\nTeklif No: {tradein_id}\n\nEn kısa sürede sizinle iletişime geçeceğiz. Başka bir şey için yardımcı olabilir miyim?\n\n1. Yeni talep\n2. Teklif durumu\n3. Hayır, teşekkürler',
            'next_states' => [
                '1' => 'report_issue',
                '2' => 'check_tradein_status',
                '3' => 'end',
            ],
            'validation' => null,
        ],

        'check_tradein_status' => [
            'message' => 'Lütfen teklif numaranızı girin:',
            'next_states' => [
                '*' => 'show_tradein_status',
            ],
            'validation' => 'required|uuid',
            'data_key' => 'tradein_id_search',
        ],

        'show_tradein_status' => [
            'message' => 'Teklifiniz aranıyor...',
            'next_states' => [
                '*' => 'start',
            ],
            'validation' => null,
            'action' => 'show_tradein_status',
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
            'message' => '✅ Your ticket has been created successfully!\n\nTicket ID: {ticket_id}\n\nModeline uygun aksesuarları görmek ister misin?\n\n1️⃣ Evet\n2️⃣ Hayır',
            'next_states' => [
                '1' => 'show_products',
                '1️⃣' => 'show_products',
                'evet' => 'show_products',
                '2' => 'ask_further_help',
                '2️⃣' => 'ask_further_help',
                'hayır' => 'ask_further_help',
            ],
            'validation' => null,
        ],

        'show_products' => [
            'message' => 'Modelinize uygun aksesuarları getiriyorum...',
            'next_states' => [
                '*' => 'product_selection',
            ],
            'validation' => null,
            'action' => 'show_products', // Special action to fetch and display products
        ],

        'product_selection' => [
            'message' => '{products_list}\n\nSatın almak istediğiniz ürün numarasını yazın (örn: 1, 2, 3) veya "iptal" yazın.',
            'next_states' => [
                'iptal' => 'ask_further_help',
                'cancel' => 'ask_further_help',
                '*' => 'create_order_draft',
            ],
            'validation' => null,
            'data_key' => 'selected_product',
        ],

        'create_order_draft' => [
            'message' => 'Siparişiniz oluşturuluyor...',
            'next_states' => [
                '*' => 'order_created',
            ],
            'validation' => null,
            'action' => 'create_order_draft', // Special action to create order
        ],

        'order_created' => [
            'message' => '✅ Siparişiniz oluşturuldu!\n\nSipariş No: {order_id}\n\n{pickup_instructions}\n\nBaşka bir şey için yardımcı olabilir miyim?\n\n1. Yeni talep\n2. Sipariş durumu\n3. Hayır, teşekkürler',
            'next_states' => [
                '1' => 'report_issue',
                '2' => 'check_order_status',
                '3' => 'end',
            ],
            'validation' => null,
        ],

        'ask_further_help' => [
            'message' => 'Başka bir şey için yardımcı olabilir miyim?\n\n1. Yeni talep\n2. Talep durumu\n3. Hayır, teşekkürler',
            'next_states' => [
                '1' => 'report_issue',
                '2' => 'check_status',
                '3' => 'end',
            ],
            'validation' => null,
        ],

        'check_order_status' => [
            'message' => 'Lütfen sipariş numaranızı girin:',
            'next_states' => [
                '*' => 'show_order_status',
            ],
            'validation' => 'required|uuid',
            'data_key' => 'order_id_search',
        ],

        'show_order_status' => [
            'message' => 'Siparişiniz aranıyor...',
            'next_states' => [
                '*' => 'start',
            ],
            'validation' => null,
            'action' => 'show_order_status',
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

