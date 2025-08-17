<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use App\Traits\Otp;
use App\Models\Book;

use App\Models\User;
use App\Models\Order;
use Illuminate\Support\Str;
use App\Models\SallaSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\Front\FrontController;

class SallaWebhookController extends Controller
{

    // use Otp;
    public function handle(Request $request)
    {

        // Log::debug('Webhook Request Body: ', $request->all());
        $securityStrategy = $request->header('X-Salla-Security-Strategy');

        // old code
        // if ($securityStrategy === 'Token') {
        //     $token = $request->header('Authorization');
        //     $expectedToken = env('SALLA_WEBHOOK_TOKEN', '9ed7775a39619dede37bee13ad1f9444');

        //     if ($token !== $expectedToken) {
        //         Log::warning('Unauthorized webhook request. Invalid token.', ['provided_token' => $token]);
        //         return response('Unauthorized', 401);
        //     }
        // }

        // new code
        if ($securityStrategy === 'Token') {
            $token = $request->header('Authorization');
            $setting = SallaSetting::first();
            $expectedToken = $setting ? $setting->webhook_token : null;

            if (!$expectedToken || $token !== $expectedToken) {
                Log::warning('Unauthorized webhook request. Invalid token.', ['provided_token' => $token]);
                return response('Unauthorized', 401);
            }
        }

        $payload = $request->all();
        $event = $payload['event'];

        Log::info("Received Salla webhook: $event", $payload);

        switch ($event) {
            case 'order.created':
                $this->handleOrderCreated($payload);
                break;

            case 'order.updated':
                $this->handleOrderUpdated($payload);
                break;
            // case 'order.status.updated':
            //     $this->handleOrderUpdated($payload);
            //     break;
            // case 'product.created':
            //     $this->handleProductCreated($payload);
            //     break;

            // case 'product.updated':
            //     $this->handleProductUpdated($payload);
            //     break;
            // case 'product.deleted':
            //     $this->handleProductDeleted($payload);
            //     break;
            case 'app.store.authorize':
                $this->handleAppStoreAuthorize($payload);
                break;
            default:
                Log::warning("Unhandled webhook event received: $event", $payload);
                break;
        }

        return response('Webhook processed successfully', 200);
    }


    private function handleAppStoreAuthorize($payload)
    {
        try {
            $data = $payload['data'];
            $sallaSetting = SallaSetting::first();
            if ($sallaSetting) {
                $sallaSetting->update([
                    'token'         => $data['access_token'],
                    'refresh_token' => $data['refresh_token'],
                    'expires_in'    => Carbon::now()->addSeconds($data['expires']),
                ]);
            } else {
                SallaSetting::create([
                    'token'         => $data['access_token'],
                    'refresh_token' => $data['refresh_token'],
                    'expires_in'    => Carbon::now()->addSeconds($data['expires']),
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Error while handling app store authorization', [
                'error_message' => $e->getMessage(),
            ]);
        }
    }


    // private function handleOrderCreated($payload)
    // {
    //     try {
    //         $orderData = $payload['data'];
    //         $orderId = $orderData['reference_id'];

    //         $userData = $orderData['customer'];
    //         $user = $this->create_order_user($userData, $orderId);
    //         $products = array_map(function ($item) {
    //             return $item['product']['id'];
    //         }, $orderData['items']);
    //         $productIds = implode(",", $products);

    //         $order = new Order();
    //         $order->order_number = $orderId;
    //         $order->serial_number = Str::random(6);
    //         $order->data = json_encode($orderData);
    //         $order->status = $orderData['status']['name'] ?? 'pending';


    //         if (isset($user)) {
    //             $order->user_id = $user->id;
    //         }

    //         $order->salla_order_id = $orderData['id'];

    //         if ($orderData['status']['name'] == 'تم التنفيذ') {
    //             $pdf = new FrontController();
    //             $pdf->create_pdf_order($order->id);
    //         }
    //         // Log the created order info
    //         Log::info('New order created', [
    //             'order_id' => $orderId,
    //             // 'order_data' => $orderData
    //         ]);
    //     } catch (\Exception $e) {
    //         // Log any errors encountered during the process
    //         Log::error('Error while creating order', [
    //             'error_message' => $e->getMessage(),
    //             // 'order_data' => $payload
    //         ]);
    //     }
    // }

    // v2
    private function handleOrderCreated($payload)
    {
        try {
            $orderData = $payload['data'];
            $orderId = $orderData['reference_id'];

            $userData = $orderData['customer'];
            $user = $this->create_order_user($userData, $orderId);

            $products = array_map(function ($item) {
                return $item['product']['id'];
            }, $orderData['items']);
            $productIds = implode(",", $products);

            $order = new Order();
            $order->order_number = $orderId;
            $order->serial_number = Str::random(6);
            $order->data = json_encode($orderData);
            $order->status = $orderData['status']['name'] ?? 'pending';

            if (isset($user)) {
                $order->user_id = $user->id;
            }

            $order->salla_order_id = $orderData['id'];

            $book = Book::where('name', "%{$orderData['items'][0]['product']['name']}%")->first();

            if ($book) {
                $order->pdf_path = $book->path; 
            }

            $order->save();

            // If the order is marked as completed, generate PDF
            // if ($orderData['status']['name'] == 'تم التنفيذ') {
            $pdf = new FrontController();
            $pdf->create_pdf_order($order->id);
            // }

            Log::info('New order created', [
                'order_id' => $orderId,
            ]);
        } catch (\Exception $e) {
            Log::error('Error while creating order', [
                'error_message' => $e->getMessage(),
            ]);
        }
    }


    private function handleOrderUpdated($payload)
    {
        try {
            $orderData = $payload['data'];
            $orderId = $orderData['reference_id'];
            $order = Order::where("order_number", $orderId)->first();
            $products = array_map(function ($item) {
                return $item['product']['id'];
            }, $orderData['items']);
            $productIds = implode(",", $products);


            if ($order) {

                $userData = $orderData['customer'];
                $user = $this->create_order_user($userData, $orderId);
                Log::info("Status: --------------->" . $orderData['status']['name']);
                $order->update([
                    'data' => json_encode($orderData),
                    'status' => $orderData['status']['name'] ?? 'pending',
                    // "product_id" => $productIds
                ]);

                // if ($orderData['status']['name'] == 'تم التنفيذ') {
                if (!$order->pdf_path) {
                    $pdf = new FrontController();
                    $pdf->create_pdf_order($order->id);
                }
                // }
            } else {
                $userData = $orderData['customer'];
                $user = $this->create_order_user($userData, $orderId);
                $order = new Order();
                $order->order_number = $orderId;
                $order->serial_number = Str::random(6);

                $order->data = json_encode($orderData);
                $order->status = $orderData['status']['name'] ?? 'pending';

                $order->save();

                // if ($orderData['status']['name'] == 'تم التنفيذ') {
                $pdf = new FrontController();
                $pdf->create_pdf_order($order->id);
                // }
                $order->salla_order_id = $orderData['id'];

                if (isset($user)) {
                    $order->user_id = $user->id;
                }
            }


            // Log the created order info
            Log::info('New order updated', [
                'order_id' => $orderId,
                // 'order_data' => $orderData
            ]);
        } catch (\Exception $e) {
            // Log any errors encountered during the process
            Log::error('Error while updating order', [
                'error_message' => $e->getMessage(),
                'line' => $e->getLine(),
                // 'order_data' => $payload
            ]);
        }
    }

    private function create_order_user($userData, $orderNumber = null)
    {
        try {
            // Check if the user already exists based on email or mobile
            $user = User::where('email', $userData['email'])
                ->orWhere('phone', $userData['mobile'])
                ->first();

            if (!$user) {
                // Create a new user if not found
                $user = User::create([
                    'name' => $userData['full_name'] ?? null,
                    'first_name' => $userData['first_name'] ?? null,
                    'last_name' => $userData['last_name'] ?? null,
                    'email' => $userData['email'] ?? 'admin@shafra.com',
                    'phone' => $userData['mobile'] ?? null,
                    'mobile_code' => $userData['mobile_code'] ?? null,
                    'password' => Hash::make('12345678'),
                    'type' => 'user',
                ]);
            }
            if ($user) {
                return $user;
            }
        } catch (\Exception $e) {
            // Log any errors encountered during the process
            Log::error('Error while creating order user', [
                'error_message' => $e->getMessage(),
            ]);

            return null;
        }
    }
    // private function handleProductCreated($payload)
    // {
    //     try {
    //         $orderData = $payload['data'];
    //         $productId = $orderData['id'];
    //         $product = Product::where('salla_product_id', $productId)->first();



    //         if ($product) {
    //             $product->update([

    //                 "webhook_response" => json_encode($orderData),
    //                 'name' => $orderData['name'],
    //             ]);
    //         } else {
    //             $order = new Product();
    //             $order->salla_product_id = $productId;
    //             $order->webhook_response = json_encode($orderData);
    //             $order->name = $orderData['name'];

    //             $order->save();
    //         }
    //         // Log the created order info
    //         Log::info('New Product created', [
    //             'product_id' => $productId,
    //             // 'order_data' => $orderData
    //         ]);
    //     } catch (\Exception $e) {
    //         // Log any errors encountered during the process
    //         Log::error('Error while creating product', [
    //             'error_message' => $e->getMessage(),
    //             // 'order_data' => $payload
    //         ]);
    //     }
    // }

    // private function handleProductUpdated($payload)
    // {
    //     try {
    //         $orderData = $payload['data'];
    //         $productId = $orderData['id'];
    //         $product = Product::where('salla_product_id', $productId)->first();

    //         if ($product) {
    //             $product->update([

    //                 "webhook_response" => json_encode($orderData),
    //                 'name' => $orderData['name'],
    //             ]);
    //         } else {
    //             $order = new Product();
    //             $order->salla_product_id = $productId;
    //             $order->response_webhook = json_encode($orderData);
    //             $order->name = $orderData['name'];

    //             $order->save();
    //         }
    //         // Log the created order info
    //         Log::info('New Product created', [
    //             'product_id' => $productId,
    //             // 'order_data' => $orderData
    //         ]);
    //     } catch (\Exception $e) {
    //         // Log any errors encountered during the process
    //         Log::error('Error while creating product', [
    //             'error_message' => $e->getMessage(),
    //             // 'order_data' => $payload
    //         ]);
    //     }
    // }

    // private function handleProductDeleted($payload)
    // {
    //     try {
    //         $orderData = $payload['data'];
    //         $productId = $orderData['id'];
    //         $product = Product::where('salla_product_id', $productId)->first();

    //         if ($product) {
    //             $product->delete();
    //         }
    //         // Log the created order info
    //         Log::info('New Product deleted', [
    //             'product_id' => $productId,
    //             // 'order_data' => $orderData
    //         ]);
    //     } catch (\Exception $e) {
    //         // Log any errors encountered during the process
    //         Log::error('Error while deleting product', [
    //             'error_message' => $e->getMessage(),
    //             // 'order_data' => $payload
    //         ]);
    //     }
    // }


    // private function handle_order_products($orderData, $order)
    // {
    //     $order = Order::where('salla_order_id', $order->salla_order_id)->first();
    //     if ($order) {

    //         $order_products = OrderProduct::where('salla_order_id', $order->salla_order_id)->delete();
    //     }
    //     $products = $orderData['items'];
    //     foreach ($products as $product) {
    //         $offer_id = null;
    //         $offer = Offer::whereHas('products', function ($query) use ($product) {
    //             $query->where('salla_product_id', $product['product']['id']); // Match the product ID
    //         })->first();
    //         if ($offer) {
    //             $offer_id = $offer->id;
    //             // Log::info('Offer found', [
    //             //     'offer_id' => $offer->id,

    //             // ]);
    //         } else {
    //             // Log::info('No offer found for product', [
    //             //     'product_id' => $product['product']['id']
    //             // ]);
    //         }

    //         $order_products = OrderProduct::create([
    //             'order_id' => $order->id,
    //             'salla_order_id' => $order->salla_order_id,
    //             'salla_product_id' => $product['product']['id'],
    //             'currency' => $product['currency'],
    //             'name' => $product['name'],
    //             'product_type' => $product['product_type'],
    //             'quantity' => $product['quantity'],
    //             'thumbnail' => $product['product']['thumbnail'],
    //             'data' => json_encode($product),
    //             'offer_id' => $offer_id

    //         ]);
    //     }
    // }

}
