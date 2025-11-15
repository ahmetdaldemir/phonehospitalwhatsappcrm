<?php

namespace App\Http\Controllers;

class LandingPageController extends Controller
{
    /**
     * Display the landing page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // Get WhatsApp phone number from config
        $whatsappNumber = config('services.whatsapp.phone_number', '1234567890');
        
        // Remove any non-numeric characters except +
        $whatsappNumber = preg_replace('/[^0-9+]/', '', $whatsappNumber);
        
        // Remove + if present (wa.me doesn't need it)
        $whatsappNumber = str_replace('+', '', $whatsappNumber);
        
        // Default message in Turkish
        $message = urlencode('Merhaba, telefon tamiri için fiyat almak istiyorum.');
        
        // Create wa.me link
        $whatsappLink = "https://wa.me/{$whatsappNumber}?text={$message}";
        
        return view('landing', [
            'whatsappLink' => $whatsappLink,
            'whatsappNumber' => $whatsappNumber,
        ]);
    }
}

