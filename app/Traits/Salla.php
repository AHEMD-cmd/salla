<?php

namespace App\Traits;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Offer;
use App\Models\Product;
use App\Models\Order;
use Illuminate\Support\Str;
use App\Models\OrderProduct;
use App\Models\ProductStore;
use App\Models\SallaSetting;
use App\Models\General\Setting;
use Telegram\Bot\Methods\Update;
use App\Models\Dashboard\Customer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Telegram\Bot\Laravel\Facades\Telegram;
use Salla\OAuth2\Client\Provider\Salla as RemoteSalla;

trait Salla
{
    public function createToken()
    {
        $setting = SallaSetting::first();
        if (!$setting || !$setting->client_id || !$setting->client_secret) {
            throw new \Exception('Salla settings (client_id or client_secret) are missing.');
        }

        $data = [
            'client_id'     => $setting->client_id,
            'client_secret' => $setting->client_secret,
            'response_type' => 'code',
            'scope'         => 'offline_access',
            'redirect_uri'  => config('salla.callback_url'),
            'state'         => rand(111111111, 999999999),
        ];

        $query = http_build_query($data);
        $url = config('salla.auth_url') . '?' . $query;

        return redirect($url);
    }

    public function refreshToken()
    {
        try {
            $setting = SallaSetting::first();
            if (!$setting || !$setting->refresh_token || !$setting->client_id || !$setting->client_secret) {
                return response()->json(['error' => 'Settings or refresh token not found'], 404);
            }

            $provider = new RemoteSalla([
                'clientId'     => $setting->client_id,
                'clientSecret' => $setting->client_secret,
            ]);

            $token = $provider->getAccessToken('refresh_token', ['refresh_token' => $setting->refresh_token]);

            $setting->update([
                'token'         => $token->getToken(),
                'refresh_token' => $token->getRefreshToken(),
                'expires_in'    => Carbon::now()->addSeconds($token->getExpires()),
            ]);

            return true;
        } catch (\Throwable $th) {
            return $th->getMessage();
        }
    }

    public function fetchOrder($order, $retryCount = 0)
    {
        $setting = SallaSetting::first();
        if (!$setting || !$setting->token) {
            return $this->error('Salla settings or token not found');
        }

        $token = $setting->token;
        $url = config('salla.salla_api_url') . '/orders?reference_id=' . $order;

        $response = Http::withToken($token)->acceptJson()->get($url);
        $status = $response->status();

        Log::info('Response received', [
            'status_code' => $status,
            'url' => $url,
            'order_reference' => $order,
            'retry_count' => $retryCount,
        ]);

        if ($status == 200) {
            $data = json_decode($response->body(), true);

            foreach ($data['data'] as $value) {
                if ($value['reference_id'] == $order) {
                    $createdOrder = $this->createOrder($value);
                    if ($createdOrder) {
                        Log::info('Order successfully created', ['order_reference' => $order]);
                        return $createdOrder;
                    }
                }
            }

            Log::warning('Order not found in API response', [
                'reference_id' => $order,
            ]);

            return $this->error('رقم التحقق خطأ');
        }

        if ($status == 429) {
            $retryAfter = $response->header('Retry-After');
            Log::warning('Rate limit exceeded', [
                'retry_after_seconds' => $retryAfter,
            ]);

            return $this->error('حاول مره اخرى', ['retry_after' => $retryAfter]);
        }

        if ($status == 401) {
            if ($retryCount < 3) {
                Log::info('Invalid token detected, attempting to refresh', [
                    'order_reference' => $order,
                    'retry_count' => $retryCount + 1,
                ]);

                $this->refreshToken();
                return $this->fetchOrder($order, $retryCount + 1);
            } else {
                Log::error('Maximum token refresh attempts reached', [
                    'order_reference' => $order,
                ]);
                return $this->error('حدث خطـأ ما');
            }
        }

        Log::error('Unhandled error during fetch', [
            'status_code' => $status,
            'response_body' => $response->body(),
            'order_reference' => $order,
        ]);

        return $this->error('حدث خطـأ ما');
    }

    public function createOrder($data)
    {
        try {
            $user = $this->create_order_user($data['customer']);
            $order = Order::create([
                'salla_order_id' => $data['id'],
                'order_number' => $data['reference_id'],
                'user_id' => $user->id,
                'data' => json_encode($data),
            ]);

            if ($order) {
                $productIds = $this->saveOrderItems($order);
                if ($productIds) {
                    Log::info('Order items successfully fetched and linked', ['order_id' => $order->id]);
                    return $order;
                }
            }
        } catch (\Exception $e) {
            Log::error('Error while creating order', [
                'error' => $e->getMessage(),
                'order_data' => $data,
            ]);
        }

        return null;
    }

    public function saveOrderItems($order)
    {
        Log::info('Fetching order items', ['order_id' => $order->id]);

        $setting = SallaSetting::first();
        if (!$setting || !$setting->token) {
            Log::error('Salla settings or token not found', ['order_id' => $order->id]);
            return null;
        }

        $token = $setting->token;
        $url = config('salla.salla_api_url') . '/orders/items?order_id=' . $order->salla_order_id;

        $response = Http::withToken($token)->acceptJson()->get($url);
        $status = $response->status();

        if ($status == 200) {
            $data = json_decode($response->body(), true);

            if (isset($data['data'])) {
                $this->handleOrderProducts($data['data'], $order);
                Log::info('Order items processed', ['order_id' => $order->id, 'data' => $data]);
                return $order;
            } else {
                Log::warning('No items found in order', ['order_id' => $order->id, 'data' => $data]);
            }
        } else {
            Log::error('Error fetching order items', [
                'status_code' => $status,
                'response_body' => $response->body(),
                'order_id' => $order->id,
            ]);
        }

        return null;
    }

    private function handleOrderProducts($products, $order)
    {
        Log::info('Processing order products', ['order_id' => $order->id]);

        try {
            OrderProduct::where('salla_order_id', $order->salla_order_id)->delete();

            foreach ($products as $product) {
                $offer_id = null;
                $offer = Offer::whereHas('products', function ($query) use ($product) {
                    $query->where('salla_product_id', $product['product_id']);
                })->first();
                if ($offer) {
                    $offer_id = $offer->id;
                    Log::info('Offer found', [
                        'offer_id' => $offer->id,
                    ]);
                } else {
                    Log::info('No offer found for product', [
                        'product_id' => $product['product_id']
                    ]);
                }
                OrderProduct::create([
                    'order_id' => $order->id,
                    'salla_order_id' => $order->salla_order_id,
                    'salla_product_id' => $product['product_id'],
                    'quantity' => $product['quantity'],
                    'currency' => $product['currency'],
                    'product_type' => $product['product_type'],
                    'thumbnail' => $product['thumbnail'],
                    'name' => $product['name'],
                    'data' => json_encode($product),
                    'offer_id' => $offer_id
                ]);
            }

            Log::info('Order products successfully saved', ['order_id' => $order->id]);
        } catch (\Exception $e) {
            Log::error('Error while processing order products', [
                'error' => $e->getMessage(),
                'order_id' => $order->id,
                'products_data' => $products,
            ]);
        }
    }

    public function syncProducts()
    {
        try {
            $setting = SallaSetting::first();
            if (!$setting || !$setting->token || !$setting->client_id) {
                return response()->json(['message' => 'Salla settings or token not found.'], 400);
            }

            $token = $setting->token;
            $url = config('salla.salla_api_url') . '/products';

            $response = Http::withToken($token)->acceptJson()->get($url);
            $status = $response->status();

            if ($status === 200) {
                $products = json_decode($response->body(), true);

                $createdCount = 0;

                foreach ($products['data'] as $product) {
                    $existingProduct = Product::where('salla_product_id', $product['id'])->first();

                    if (!$existingProduct) {
                        Product::create([
                            'store_id' => Auth::user()->store_id,
                            'name' => $product['name'],
                            'salla_product_id' => $product['id'],
                            'webhook_response' => json_encode($product),
                        ]);

                        $createdCount++;
                    }
                }

                return response()->json([
                    'message' => 'Products synced successfully!',
                    'created_products' => $createdCount,
                ]);
            } else {
                return response()->json([
                    'message' => 'Failed to fetch products from Salla.',
                    'status_code' => $status,
                    'response_body' => $response->body(),
                ], $status);
            }
        } catch (\Exception $e) {
            Log::error('Error syncing products: ' . $e->getMessage(), [
                'stack' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'An unexpected error occurred while syncing products.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getOrderItems($orderId = null)
    {
        $setting = SallaSetting::first();
        if (!$setting || !$setting->token) {
            return back()->with('error', 'Salla settings or token not found');
        }

        $token = $setting->token;
        $url = config('salla.salla_api_url') . '/orders/items?order_id=' . $orderId;

        $response = Http::withToken($token)->acceptJson()->get($url);
        $status = $response->status();

        if ($status == 200) {
            $response = $response->body();
            $data = json_decode($response, true);
            $finalData = $data['data'];
            $productIds = array_map(function ($item) {
                return $item['id'];
            }, $finalData);
            $products = json_encode($finalData);
            $order = Order::updateOrCreate(
                [
                    'salla_order_id' => $orderId
                ],
                [
                    'product_details' => $products,
                    'product_id' => count($productIds) > 0 ? implode(",", $productIds) : "",
                ]
            );

            if ($finalData && count($finalData) > 0) {
                return view('admin.order.orderItems', compact('finalData'));
            }
            return back()->with('success', 'no products found');
        }

        if ($status == 429) {
            $retryAfter = $response->header('Retry-After');
            Log::info('Rate limit exceeded. Retrying after ' . $retryAfter . ' seconds.');
            return back()->with('error', 'Rate limit exceeded. Please try again after ' . $retryAfter . ' seconds.');
        }

        if ($status == 401) {
            return back()->with('error', 'un arror occured');
        }

        if (!$orderId) {
            return back()->with('success', 'no products found');
        }
    }

    private function create_order_user($userData)
    {
        try {
            $user = User::where('email', $userData['email'])
                ->orWhere('phone', $userData['mobile'])
                ->first();

            if (!$user) {
                $user = User::create([
                    'name' => $userData['full_name'] ?? null,
                    'first_name' => $userData['first_name'] ?? null,
                    'last_name' => $userData['last_name'] ?? null,
                    'email' => $userData['email'] ?? null,
                    'phone' => $userData['mobile'] ?? null,
                    'mobile_code' => $userData['mobile_code'] ?? null,
                    'password' => Hash::make('12345678'),
                ]);
            }

            return $user;
        } catch (\Exception $e) {
            Log::error('Error while creating order user', [
                'error_message' => $e->getMessage(),
            ]);

            return null;
        }
    }
}