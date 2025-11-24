<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class VerifyBitgetWebhook
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Cek IP whitelist jika dikonfigurasi
        $allowedIps = config('bitget.allowed_ips', []);
        
        if (!empty($allowedIps)) {
            $clientIp = $request->ip();
            
            if (!in_array($clientIp, $allowedIps)) {
                Log::warning('Unauthorized IP attempt', [
                    'ip' => $clientIp,
                    'allowed_ips' => $allowedIps
                ]);
                
                return response()->json([
                    'error' => 'Unauthorized IP address'
                ], 403);
            }
        }

        // Cek webhook token jika dikonfigurasi
        $webhookToken = config('bitget.webhook_token');
        
        if (!empty($webhookToken)) {
            $requestToken = $request->header('X-Webhook-Token') 
                         ?? $request->input('token');
            
            if ($requestToken !== $webhookToken) {
                Log::warning('Invalid webhook token', [
                    'ip' => $request->ip()
                ]);
                
                return response()->json([
                    'error' => 'Invalid webhook token'
                ], 401);
            }
        }

        return $next($request);
    }
}