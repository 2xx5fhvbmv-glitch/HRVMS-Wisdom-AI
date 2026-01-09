<?php 
// app/Services/RazorpayService.php

namespace App\Services;

use Razorpay\Api\Api;
use Razorpay\Api\Errors;
use Exception;
use Illuminate\Support\Facades\Log;
use App\Models\Order; 

class RazorpayService
{
    protected $api;

    public function __construct()
    {
        $this->api = new Api(env('RAZORPAY_KEY'), env('RAZORPAY_SECRET'));
    }

    // public function createCustomer($name, $email, $phone, $address)
    // {
    //     try {
    //         // Check if the customer already exists
    //         $customers = $this->api->customer->all(['email' => $email]);
    //         dd($customers);
    //         if (count($customers->items) > 0) {
    //             // Customer already exists
    //             $customer = $customers->items[0];
    //         } else {
    //             // Create a new customer
    //             $customer = $this->api->customer->create([
    //                 'name' => $name,
    //                 'email' => $email,
    //                 'contact' => $phone,
    //                 'notes' => [
    //                     'address' => $address
    //                 ]
    //             ]);
    //         }

    //         // Use $customer->id for further processing, like creating an order

    //         return $customer;

    //     } catch (BadRequestError $e) {
    //         // Handle specific Razorpay BadRequestError
    //         return response()->json(['error' => $e->getMessage()], 400);

    //     } catch (\Exception $e) {
    //         // Handle other errors
    //         return response()->json(['error' => 'An error occurred while creating the customer.'], 500);
    //     }
    // }

    public function createCustomer($name, $email, $phone, $address)
    {
        try {
            // Check if the customer already exists in the local database
            $existingCustomer = Order::where('email', $email)->first();

            if ($existingCustomer) {
                // Fetch the customer details from Razorpay using stored customer ID
                $customer = $this->api->customer->fetch($existingCustomer->customer_id);
            } else {
                // Create a new customer in Razorpay
                $customer = $this->api->customer->create([
                    'name' => $name,
                    'email' => $email,
                    'contact' => $phone,
                    'notes' => [
                        'address' => $address
                    ]
                ]);

            }

            return $customer;

        } catch (BadRequestError $e) {
            Log::error('Razorpay BadRequestError: ' . $e->getMessage(), [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address
            ]);
            return response()->json(['error' => $e->getMessage()], 400);

        } catch (\Exception $e) {
            Log::error('An error occurred while creating the customer: ' . $e->getMessage(), [
                'name' => $name,
                'email' => $email,
                'phone' => $phone,
                'address' => $address
            ]);
            return response()->json(['error' => 'An error occurred while creating the customer.'], 500);
        }
    }


    public function getCustomersAndMatchEmail($email)
    {
        try {
            $customers = $this->api->customer->all(['email' => $email]);
            if (count($customers->items) > 0) {
                return $customers->items[0]->id;
            }
            return null;
        } catch (\Exception $e) {
            Log::error('An error occurred while fetching customers: ' . $e->getMessage());
            return null;
        }
    }
    // public function getCustomersAndMatchEmail($email)
    // {
    //     try {
    //         // Fetch all customers from Razorpay
    //         $allCustomers = $this->api->customer->all();

    //         // Iterate through fetched customers and check for email match
    //         foreach ($allCustomers->items as $customer) {
    //             if ($customer->email == $email) {
    //                 // Email match found, return the customer ID
    //                 return $customer->id;
    //             }
    //         }

    //         // No matching customer found
    //         return null;
    //     } catch (\Exception $e) {
    //         // Handle exceptions (e.g., API errors)
    //         \Log::error('Exception while fetching customers from Razorpay API: ' . $e->getMessage());
    //         return null;
    //     }
    // }
    
    public function createOrder($amount, $customerId)
    {
        $order = $this->api->order->create([
            'amount' => $amount, // Convert amount to smallest currency unit (e.g., paise for INR)
            'currency' => 'INR',
            'customer_id' => $customerId,
            'receipt' => 'order_receipt_' . time(),
            'payment_capture' => 1
        ]);

        return $order;
    }

    public function processPayment($orderId, $paymentId, $signature)
    {
        try {
            // Verify payment signature
            $attributes = [
                'razorpay_order_id' => $orderId,
                'razorpay_payment_id' => $paymentId,
                'razorpay_signature' => $signature,
            ];

            $this->api->utility->verifyPaymentSignature($attributes);

            // Payment verification successful
            // Update order status or save payment details as needed

            return true;
        } catch (BadRequestError $e) {
            // Handle error
            return false;
        }
    }

    public function capturePayment($paymentId, $amount)
    {       
        $payment = $this->api->payment->fetch($paymentId);
        // dd($payment);
        if (!empty($payment)) {
            try {
                $response = $this->api->payment->fetch($paymentId)->capture([
                    'amount'=>$payment['amount'],
                    'currency' => 'INR'
                ]);
                // dd($response);
                return $response;
                
            } catch (Exception $e) {
                Log::info($e->getMessage());
                return back()->withError($e->getMessage());
            }
        }
    }
    
}
?>