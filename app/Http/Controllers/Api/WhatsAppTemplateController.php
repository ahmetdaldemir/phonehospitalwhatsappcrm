<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\WhatsAppTemplateService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WhatsAppTemplateController extends Controller
{
    protected WhatsAppTemplateService $whatsappService;

    public function __construct(WhatsAppTemplateService $whatsappService)
    {
        $this->whatsappService = $whatsappService;
    }

    /**
     * Send first reply template.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendFirstReply(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'customer_name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $result = $this->whatsappService->sendFirstReply(
            $request->phone_number,
            $request->customer_name ?? 'there'
        );

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 400);
    }

    /**
     * Send price display template.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendPriceDisplay(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'brand' => 'required|string|max:255',
            'model' => 'required|string|max:255',
            'problem_type' => 'required|string|max:255',
            'price_min' => 'required|numeric|min:0',
            'price_max' => 'required|numeric|min:0|gte:price_min',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $result = $this->whatsappService->sendPriceDisplay(
            $request->phone_number,
            $request->brand,
            $request->model,
            $request->problem_type,
            $request->price_min,
            $request->price_max
        );

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 400);
    }

    /**
     * Send location request template.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendLocationRequest(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $result = $this->whatsappService->sendLocationRequest($request->phone_number);

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 400);
    }

    /**
     * Send success and coupon confirmation template.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendSuccessCouponConfirmation(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone_number' => 'required|string',
            'ticket_id' => 'required|string',
            'coupon_code' => 'required|string|max:50',
            'discount_percent' => 'required|integer|min:0|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $result = $this->whatsappService->sendSuccessCouponConfirmation(
            $request->phone_number,
            $request->ticket_id,
            $request->coupon_code,
            $request->discount_percent
        );

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 400);
    }

    /**
     * Send template based on ticket.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string  $ticketId
     * @return \Illuminate\Http\JsonResponse
     */
    public function sendTicketConfirmation(Request $request, string $ticketId)
    {
        $ticket = Ticket::with('customer')->findOrFail($ticketId);

        if (!$ticket->customer) {
            return response()->json(['error' => 'Ticket has no customer'], 400);
        }

        // Generate coupon code (you can implement your own logic)
        $couponCode = 'WELCOME20';
        $discountPercent = 20;

        $result = $this->whatsappService->sendSuccessCouponConfirmation(
            $ticket->customer->phone_number,
            $ticket->id,
            $couponCode,
            $discountPercent
        );

        if ($result['success']) {
            return response()->json($result, 200);
        }

        return response()->json($result, 400);
    }
}

