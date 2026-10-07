<?php

namespace App\Http\Controllers\Webhooks;

use App\Domain\Reminders\Actions\HandleInteractiveButtonReply;
use App\Http\Controllers\Controller;
use App\Models\Reminder;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class WhatsAppWebhookController extends Controller
{
    public function __construct(
        protected HandleInteractiveButtonReply $buttonReplyHandler
    ) {}

    /**
     * Webhook challenge handshake for Meta WhatsApp Cloud API.
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $expectedToken = config('services.whatsapp.verify_token', 'slotsaver_webhook_secret');

        if ($mode === 'subscribe' && $token === $expectedToken) {
            return response($challenge, 200)->header('Content-Type', 'text/plain');
        }

        return response('Forbidden', 403);
    }

    /**
     * Inbound WhatsApp webhook with HMAC verification and strict idempotency deduplication.
     */
    public function handle(Request $request): JsonResponse
    {
        // 1. Verify HMAC signature if secret configured
        if (! $this->isValidSignature($request)) {
            Log::warning('Rejected WhatsApp webhook due to invalid signature.');

            return response()->json(['error' => 'Invalid signature'], 401);
        }

        $payload = $request->all();
        $entry = $payload['entry'][0]['changes'][0]['value'] ?? null;

        if (! $entry) {
            return response()->json(['status' => 'no_content'], 200);
        }

        // 2. Extract unique provider ID for idempotency key
        $message = $entry['messages'][0] ?? null;
        $status = $entry['statuses'][0] ?? null;

        $messageId = $message['id'] ?? $status['id'] ?? null;

        if (! $messageId) {
            return response()->json(['status' => 'ignored_no_id'], 200);
        }

        $idempotencyKey = "whatsapp:{$messageId}";

        // 3. Replay Protection: Atomic deduplication check
        if (WebhookEvent::isDuplicate($idempotencyKey)) {
            Log::info("Ignoring duplicate webhook delivery: {$idempotencyKey}");

            return response()->json(['status' => 'ignored_duplicate'], 200);
        }

        // Record into idempotency ledger
        WebhookEvent::create([
            'provider' => 'whatsapp',
            'idempotency_key' => $idempotencyKey,
            'event_type' => isset($message) ? 'message' : 'status',
            'payload' => $payload,
            'processed_at' => now(),
            'response_status' => 'processed',
        ]);

        // 4. Handle Status Updates (delivered, read)
        if ($status) {
            $statusType = $status['status'] ?? null; // delivered, read, failed
            if (in_array($statusType, ['delivered', 'read'], true)) {
                Reminder::where('provider_message_id', $messageId)
                    ->update(['delivery_status' => $statusType]);
            }

            return response()->json(['status' => 'status_updated'], 200);
        }

        // 5. Handle Inbound Interactive Button Click
        if ($message && isset($message['interactive']['button_reply']['id'])) {
            $buttonId = $message['interactive']['button_reply']['id'];
            $sender = $message['from'] ?? null;

            $result = $this->buttonReplyHandler->execute($buttonId, $sender);

            return response()->json([
                'status' => 'button_processed',
                'result' => $result,
            ], 200);
        }

        return response()->json(['status' => 'ok'], 200);
    }

    /**
     * Validate Meta X-Hub-Signature-256 header.
     */
    protected function isValidSignature(Request $request): bool
    {
        $appSecret = config('services.whatsapp.app_secret');

        // Allow test environments without signature requirement
        if (empty($appSecret) || app()->environment('testing')) {
            return true;
        }

        $signatureHeader = $request->header('X-Hub-Signature-256');

        if (! $signatureHeader || ! str_starts_with($signatureHeader, 'sha256=')) {
            return false;
        }

        $expectedHash = substr($signatureHeader, 7);
        $calculatedHash = hash_hmac('sha256', $request->getContent(), $appSecret);

        return hash_equals($expectedHash, $calculatedHash);
    }
}
