<?php

namespace App\Http\Controllers\Api\V1;

use App\User;
use Exception;
use Carbon\Carbon;
use App\Model\AddOn;
use App\Model\Order;
use App\Model\Branch;
use App\Model\Product;
use App\Model\DMReview;
use App\Models\GuestUser;
use App\Models\OrderArea;
use App\Model\OrderDetail;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\CentralLogics\Helpers;
use App\Model\CustomerAddress;
use App\Model\ProductByBranch;
use App\Models\OfflinePayment;
use App\Model\OrderTransaction;
use App\Model\WalletTransaction;
use App\CentralLogics\OrderLogic;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Models\OrderPartialPayment;
use Illuminate\Support\Facades\Log;
use App\CentralLogics\CustomerLogic;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Mail;
use function App\CentralLogics\translate;
use Illuminate\Support\Facades\Validator;
use App\Http\Controllers\PaymentsController;
use App\Services\Payment\PaymentGatewayFactory;

class OrderController extends Controller
{
    protected $order;
    protected $order_detail;
    protected $user;
    protected $product;
    protected $product_by_branch;
    protected $offlinePayment;
    protected $orderArea;

    public function __construct(
        Order $order,
        OrderDetail $order_detail,
        User $user,
        Product $product,
        ProductByBranch $product_by_branch,
        OfflinePayment $offlinePayment,
        OrderArea $orderArea
    ) {
        $this->order = $order;
        $this->order_detail = $order_detail;
        $this->user = $user;
        $this->product = $product;
        $this->product_by_branch = $product_by_branch;
        $this->offlinePayment = $offlinePayment;
        $this->orderArea = $orderArea;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function placeOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:wallet_payment,paymob,qib,cash_on_delivery,offline_payment',
            'order_type' => 'required|in:delivery,take_away',
            'branch_id' => 'required|integer|exists:branches,id',
            'delivery_time' => 'required',
            'delivery_date' => 'required',
            'distance' => 'required|numeric',
            'guest_id' => auth('api')->user() ? 'nullable' : 'required|integer',
            'is_partial' => 'required|in:0,1',
            'callback_url' => 'required_if:payment_method,paymob,qib|url',
            'customer_data' => 'required_if:payment_method,paymob,qib|array',
            'customer_data.email' => 'required_if:payment_method,paymob,qib|email',
            'customer_data.phone' => 'required_if:payment_method,paymob,qib|string',
            'customer_data.f_name' => 'required_if:payment_method,paymob,qib|string',
            'customer_data.l_name' => 'required_if:payment_method,paymob,qib|string',
            'payment_CustomerNo' => 'required_if:payment_method,qib|string',
            'payment_DestNation' => 'required_if:payment_method,qib|integer',
            'payment_Code' => 'required_if:payment_method,qib|integer',
            'cart' => 'required|array|min:1',
            'cart.*.product_id' => 'required|integer|exists:products,id',
            'cart.*.quantity' => 'required|integer|min:1',
            'cart.*.variant' => 'nullable|string',
            'cart.*.variations' => 'nullable|array',
            'cart.*.add_on_ids' => 'nullable|array',
            'cart.*.add_on_qtys' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if (count($request['cart']) < 1) {
            return response()->json(['errors' => [['code' => 'empty-cart', 'message' => translate('cart is empty')]]], 403);
        }

        // Update daily stock
        Helpers::update_daily_product_stock();

        $customer = auth('api')->user() ? $this->user->find(auth('api')->user()->id) : null;

        $preparation_time = Branch::where(['id' => $request['branch_id']])->first()->preparation_time ?? 0;

        if ($request['delivery_time'] == 'now') {
            $deliveryDate = Carbon::now()->format('Y-m-d');
            $deliveryTime = Carbon::now()->add($preparation_time, 'minute')->format('H:i:s');
        } else {
            $deliveryDate = $request['delivery_date'];
            $deliveryTime = Carbon::parse($request['delivery_time'])->add($preparation_time, 'minute')->format('H:i:s');
        }

        $userId = auth('api')->user() ? auth('api')->user()->id : $request['guest_id'];
        $userType = auth('api')->user() ? 0 : 1;

        // Determine payment status
        $paymentStatus = ($request->payment_method == 'cash_on_delivery' || $request->payment_method == 'offline_payment') ? 'unpaid' : 'pending';
        if ($request->is_partial == 1) {
            $paymentStatus = 'partial_paid';
        } elseif ($request->payment_method == 'wallet_payment' && !$request->is_partial) {
            $paymentStatus = 'paid';
        }

        $orderStatus = ($request->payment_method == 'cash_on_delivery' || $request->payment_method == 'offline_payment') ? 'pending' : 'pending';

        $deliveryCharge = $request['order_type'] == 'take_away' ? 0 : Helpers::get_delivery_charge(
            branchId: $request['branch_id'],
            distance: $request['distance'],
            selectedDeliveryArea: $request['selected_delivery_area'] ?? null
        );

        try {
            DB::beginTransaction();

            $order_id = 100000 + $this->order->all()->count() + 1;
            $calculated_order_amount = 0;
            $totalTaxAmount = 0;

            foreach ($request['cart'] as $c) {
                $product = $this->product->find($c['product_id']);
                if (!$product) {
                    DB::rollBack();
                    return response()->json(['errors' => [['code' => 'product_not_found', 'message' => translate('Product not found for ID: ' . $c['product_id'])]]], 404);
                }

                $branch_product = $this->product_by_branch->where(['product_id' => $c['product_id'], 'branch_id' => $request['branch_id']])->first();
                if (!$branch_product) {
                    DB::rollBack();
                    return response()->json(['errors' => [['code' => 'branch_product_not_found', 'message' => translate('Product not available in branch: ' . $request['branch_id'])]]], 404);
                }

                // Stock validation
                if ($branch_product->stock_type == 'daily' || $branch_product->stock_type == 'fixed') {
                    $available_stock = $branch_product->stock - $branch_product->sold_quantity;
                    if ($available_stock < $c['quantity']) {
                        DB::rollBack();
                        return response()->json(['errors' => [['code' => 'stock', 'message' => translate('stock limit exceeded for product: ' . $c['product_id'])]]], 403);
                    }
                }

                $discount_data = [
                    'discount_type' => $branch_product->discount_type ?? $product->discount_type,
                    'discount' => $branch_product->discount ?? $product->discount,
                ];

                $variations = [];
                $price = $branch_product->price;

                // Handle variations
                $branch_variations = is_string($branch_product->variations) ? json_decode($branch_product->variations, true) : $branch_product->variations;
                $branch_variations = is_array($branch_variations) ? $branch_variations : [];

                if (!empty($branch_variations)) {
                    $variation_data = Helpers::get_varient($branch_variations, $c['variations']);
                    $price += $variation_data['price'] ?? 0;
                    $variations = $variation_data['variations'] ?? [];
                } else {
                    $product_variations = is_string($product->variations) ? json_decode($product->variations, true) : $product->variations;
                    $product_variations = is_array($product_variations) ? $product_variations : [];
                    if (!empty($product_variations)) {
                        $variation_data = Helpers::get_varient($product_variations, $c['variations']);
                        $price = $product->price + ($variation_data['price'] ?? 0);
                        $variations = $variation_data['variations'] ?? [];
                    } else {
                        $price = $product->price;
                    }
                }

                $discount_on_product = Helpers::discount_calculate($discount_data, $price);

                // Add-on calculations
                $add_on_quantities = $c['add_on_qtys'] ?? [];
                $add_on_prices = [];
                $add_on_taxes = [];
                $total_addon_price = 0;

                foreach ($c['add_on_ids'] ?? [] as $key => $id) {
                    $addon = AddOn::find($id);
                    if (!$addon) {
                        DB::rollBack();
                        return response()->json(['errors' => [['code' => 'addon_not_found', 'message' => translate('Add-on not found for ID: ' . $id)]]], 404);
                    }
                    $add_on_prices[] = $addon->price;
                    $add_on_taxes[] = ($addon->price * $addon->tax) / 100;
                    $total_addon_price += $addon->price * ($add_on_quantities[$key] ?? 1);
                }

                $total_addon_tax = array_reduce(
                    array_map(function ($qty, $tax) {
                        return $qty * $tax;
                    }, $add_on_quantities, $add_on_taxes),
                    function ($carry, $item) {
                        return $carry + $item;
                    },
                    0
                );

                // Calculate item subtotal
                $item_subtotal = ($price * $c['quantity']) - $discount_on_product + $total_addon_price + $total_addon_tax;
                $calculated_order_amount += $item_subtotal;

                $tax_amount = Helpers::new_tax_calculate($product, $price, $discount_data);
                $totalTaxAmount += $tax_amount * $c['quantity'];

                $or_d = [
                    'order_id' => $order_id,
                    'product_id' => $c['product_id'],
                    'product_details' => $product,
                    'quantity' => $c['quantity'],
                    'price' => $price,
                    'tax_amount' => $tax_amount,
                    'discount_on_product' => $discount_on_product,
                    'discount_type' => 'discount_on_product',
                    'variant' => json_encode($c['variant'] ?? ''),
                    'variation' => json_encode($variations),
                    'add_on_ids' => json_encode($c['add_on_ids'] ?? []),
                    'add_on_qtys' => json_encode($add_on_quantities),
                    'add_on_prices' => json_encode($add_on_prices),
                    'add_on_taxes' => json_encode($add_on_taxes),
                    'add_on_tax_amount' => $total_addon_tax,
                    'created_at' => now(),
                    'updated_at' => now()
                ];

                $this->order_detail->insert($or_d);

                $this->product->find($c['product_id'])->increment('popularity_count');

                // Update stock
                if ($branch_product->stock_type == 'daily' || $branch_product->stock_type == 'fixed') {
                    $branch_product->sold_quantity += $c['quantity'];
                    $branch_product->save();
                }
            }

            // Apply delivery charge and coupon discount
            $calculated_order_amount += $deliveryCharge;
            $coupon_discount_amount = Helpers::set_price($request->coupon_discount_amount ?? 0);
            $calculated_order_amount -= $coupon_discount_amount;

            if ($calculated_order_amount < 0.01) {
                DB::rollBack();
                return response()->json(['errors' => [['code' => 'invalid_amount', 'message' => translate('Order amount must be at least 0.01')]]], 403);
            }

            // Validate wallet payment
            if ($request->payment_method == 'wallet_payment') {
                if (Helpers::get_business_settings('wallet_status') != 1) {
                    return response()->json(['errors' => [['code' => 'payment_method', 'message' => translate('customer_wallet_status_is_disable')]]], 403);
                }
                if ($customer && $customer->wallet_balance < $calculated_order_amount) {
                    return response()->json(['errors' => [['code' => 'payment_method', 'message' => translate('you_do_not_have_sufficient_balance_in_wallet')]]], 403);
                }
            }

            // Validate partial payment
            if ($request['is_partial'] == 1) {
                if (Helpers::get_business_settings('wallet_status') != 1) {
                    return response()->json(['errors' => [['code' => 'payment_method', 'message' => translate('customer_wallet_status_is_disable')]]], 403);
                }
                if ($customer && $customer->wallet_balance >= $calculated_order_amount) {
                    return response()->json(['errors' => [['code' => 'payment_method', 'message' => translate('since your wallet balance is more than order amount, you can not place partial order')]]], 403);
                }
                if ($customer && $customer->wallet_balance < 1) {
                    return response()->json(['errors' => [['code' => 'payment_method', 'message' => translate('since your wallet balance is less than 1, you can not place partial order')]]], 403);
                }
            }

            $or = [
                'id' => $order_id,
                'user_id' => $userId,
                'is_guest' => $userType,
                'order_amount' => Helpers::set_price($calculated_order_amount),
                'coupon_discount_amount' => $coupon_discount_amount,
                'coupon_discount_title' => $request->coupon_discount_title ?? null,
                'payment_status' => $paymentStatus,
                'order_status' => $orderStatus,
                'coupon_code' => $request['coupon_code'],
                'payment_method' => $request->payment_method,
                'transaction_reference' => $request->transaction_reference ?? null,
                'order_note' => $request['order_note'],
                'order_type' => $request['order_type'],
                'branch_id' => $request['branch_id'],
                'delivery_address_id' => $request->delivery_address_id,
                'delivery_date' => $deliveryDate,
                'delivery_time' => $deliveryTime,
                'delivery_address' => $request->delivery_address_id && ($address = CustomerAddress::find($request->delivery_address_id))
                    ? json_encode($address)
                    : ($request->delivery_address ? json_encode($request->delivery_address) : null),
                'delivery_charge' => $deliveryCharge,
                'preparation_time' => 0,
                'is_cutlery_required' => $request['is_cutlery_required'] ?? 0,
                'bring_change_amount' => $request->payment_method != 'cash_on_delivery' ? 0 : ($request->bring_change_amount ?? 0),
                'total_tax_amount' => $totalTaxAmount,
                'created_at' => now(),
                'updated_at' => now()
            ];

            $o_id = $this->order->insertGetId($or);

            // Handle wallet payment
            if ($request->payment_method == 'wallet_payment' && !$request->is_partial) {
                $amount = $or['order_amount'] + $or['delivery_charge'];
                $walletTransaction = CustomerLogic::create_wallet_transaction(
                    $or['user_id'],
                    $amount,
                    'order_place',
                    'ORDER_' . $o_id,
                    $o_id
                );
                if (!$walletTransaction) {
                    DB::rollBack();
                    Log::error('Wallet transaction creation failed for wallet payment', ['order_id' => $o_id]);
                    return response()->json(['errors' => [['code' => 'wallet_error', 'message' => translate('Failed to create wallet transaction')]]], 400);
                }
                $this->order->where('id', $o_id)->update([
                    'payment_status' => 'paid',
                    'order_status' => 'confirmed',
                    'transaction_reference' => 'WALLET_' . $o_id
                ]);
            }

            // Handle partial payment
            if ($request->is_partial == 1) {
                $totalOrderAmount = $or['order_amount'] + $or['delivery_charge'];
                $walletAmount = $customer ? $customer->wallet_balance : 0;
                $dueAmount = $totalOrderAmount - $walletAmount;

                if ($walletAmount > 0) {
                    $walletTransaction = CustomerLogic::create_wallet_transaction($or['user_id'], $walletAmount, 'order_place', $or['id']);
                    if (!$walletTransaction) {
                        DB::rollBack();
                        Log::error('Wallet transaction creation failed for partial payment (wallet)', ['order_id' => $o_id]);
                        return response()->json(['errors' => [['code' => 'wallet_error', 'message' => translate('Failed to create wallet transaction')]]], 400);
                    }
                    $partial = new OrderPartialPayment;
                    $partial->order_id = $or['id'];
                    $partial->paid_with = 'wallet_payment';
                    $partial->paid_amount = $walletAmount;
                    $partial->due_amount = $dueAmount;
                    $partial->transaction_id = $walletTransaction->transaction_id;
                    $partial->save();
                }

                if ($request->payment_method != 'cash_on_delivery' && $request->payment_method != 'offline_payment') {
                    $customerData = [
                        'user_id' => $userId,
                        'name' => $request->customer_data['f_name'] . ' ' . $request->customer_data['l_name'],
                        'email' => $request->customer_data['email'],
                        'phone' => $request->customer_data['phone'],
                        'f_name' => $request->customer_data['f_name'],
                        'l_name' => $request->customer_data['l_name']
                    ];

                    $transactionId = 'PAY_' . time() . '_' . $userId;
                    $paymentData = [
                        'gateway' => $request->payment_method,
                        'amount' => $dueAmount,
                        'currency' => 'EGP',
                        'purpose' => 'order_payment',
                        'order_id' => $o_id,
                        'customer_data' => $customerData,
                        'callback_url' => rtrim($request->callback_url, '?') . '?transaction_id=' . $transactionId,
                        'transaction_id' => $transactionId,
                    ];

                    if ($request->payment_method === 'qib') {
                        $paymentData['payment_CustomerNo'] = $request->payment_CustomerNo;
                        $paymentData['payment_DestNation'] = $request->payment_DestNation;
                        $paymentData['payment_Code'] = $request->payment_Code;
                    }

                    $gateway = PaymentGatewayFactory::create($request->payment_method);
                    if (!$gateway) {
                        DB::rollBack();
                        Log::error('Invalid payment gateway', ['gateway' => $request->payment_method]);
                        return response()->json(['errors' => [['code' => 'payment_error', 'message' => 'Invalid payment gateway']]], 400);
                    }

                    try {
                        $paymentResponse = $gateway->requestPayment($paymentData);
                        if (!isset($paymentResponse['status']) || !$paymentResponse['status']) {
                            DB::rollBack();
                            Log::error('Payment initiation failed', ['error' => $paymentResponse['error'] ?? 'Unknown error', 'payment_data' => $paymentResponse]);
                            return response()->json(['errors' => [['code' => 'payment_error', 'message' => $paymentResponse['error'] ?? 'Payment initiation failed']]], 400);
                        }

                        $metadata = [
                            'order_id' => $o_id,
                            'paymob_order_id' => $paymentResponse['order_id'] ?? null,
                            'paymob_transaction_id' => $paymentResponse['id'] ?? null,
                            'payment_key' => $paymentResponse['payment_key'] ?? null,
                            'gateway' => $request->payment_method,
                        ];

                        $walletTransaction = CustomerLogic::create_wallet_transaction(
                            $userId,
                            $dueAmount,
                            'add_fund',
                            'order_payment'
                        );

                        if (!$walletTransaction) {
                            DB::rollBack();
                            Log::error('Wallet transaction creation failed for Paymob/QIB partial payment', ['order_id' => $o_id]);
                            return response()->json(['errors' => [['code' => 'wallet_error', 'message' => translate('Failed to create wallet transaction')]]], 400);
                        }

                        $partial = new OrderPartialPayment;
                        $partial->order_id = $o_id;
                        $partial->paid_with = $request->payment_method;
                        $partial->paid_amount = 0;
                        $partial->due_amount = $dueAmount;
                        $partial->transaction_id = $walletTransaction->transaction_id;
                        $partial->save();

                        $this->order->where('id', $o_id)->update([
                            'transaction_reference' => $walletTransaction->transaction_id
                        ]);

                        DB::commit();

                        try {
                            $this->orderEmailAndNotification(request: $request, or: $or, order_id: $order_id);
                        } catch (\Exception $e) {
                            Log::error('Email/Notification failed', ['error' => $e->getMessage()]);
                        }

                        Log::info('Order placed successfully (partial payment)', ['order_id' => $o_id, 'transaction_id' => $walletTransaction->transaction_id]);

                        return response()->json([
                            'message' => translate('order_pending_payment'),
                            'order_id' => $order_id,
                            'payment_details' => $paymentResponse
                        ], 200);
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Payment initiation failed', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'order_id' => $o_id
                        ]);
                        return response()->json(['errors' => [['code' => 'payment_error', 'message' => 'Payment initiation failed: ' . $e->getMessage()]]], 400);
                    }
                }
            }

            // Handle offline payment
            if ($request->payment_method == 'offline_payment') {
                $offlinePayment = $this->offlinePayment;
                $offlinePayment->order_id = $o_id;
                $offlinePayment->payment_info = json_encode($request['payment_info'] ?? []);
                $offlinePayment->save();
            }

            // Handle payment gateway (non-partial)
            if (in_array($request->payment_method, ['paymob', 'qib']) && !$request->is_partial) {
                $customerData = [
                    'user_id' => $userId,
                    'name' => $request->customer_data['f_name'] . ' ' . $request->customer_data['l_name'],
                    'email' => $request->customer_data['email'],
                    'phone' => $request->customer_data['phone'],
                    'f_name' => $request->customer_data['f_name'],
                    'l_name' => $request->customer_data['l_name']
                ];

                $transactionId = 'PAY_' . time() . '_' . $userId;
                $paymentData = [
                    'gateway' => $request->payment_method,
                    'amount' => $calculated_order_amount + $deliveryCharge,
                    'currency' => 'EGP',
                    'purpose' => 'order_payment',
                    'order_id' => $o_id, // Internal order ID
                    'customer_data' => $customerData,
                    'callback_url' => rtrim($request->callback_url, '?') . '?transaction_id=' . $transactionId,
                    'transaction_id' => $transactionId,
                ];

                if ($request->payment_method === 'qib') {
                    $paymentData['payment_CustomerNo'] = $request->payment_CustomerNo;
                    $paymentData['payment_DestNation'] = $request->payment_DestNation;
                    $paymentData['payment_Code'] = $request->payment_Code;
                }

                $gateway = PaymentGatewayFactory::create($request->payment_method);
                if (!$gateway) {
                    DB::rollBack();
                    Log::error('Invalid payment gateway', ['gateway' => $request->payment_method]);
                    return response()->json(['errors' => [['code' => 'payment_error', 'message' => 'Invalid payment gateway']]], 400);
                }

                try {
                    $paymentResponse = $gateway->requestPayment($paymentData);
                    if (!isset($paymentResponse['status']) || !$paymentResponse['status']) {
                        DB::rollBack();
                        Log::error('Payment initiation failed', ['error' => $paymentResponse['error'] ?? 'Unknown error']);
                        return response()->json(['errors' => [['code' => 'payment_error', 'message' => $paymentResponse['error'] ?? 'Payment initiation failed']]], 400);
                    }

                    // Retrieve user to get current wallet balance
                    $user = User::find($userId);
                    if (!$user) {
                        DB::rollBack();
                        Log::error('User not found', ['user_id' => $userId]);
                        return response()->json(['errors' => [['code' => 'user_error', 'message' => 'User not found']]], 400);
                    }

                    // Create WalletTransaction for the payment
                    WalletTransaction::create([
                        'user_id' => $userId,
                        'transaction_id' => $transactionId,
                        'credit' => $calculated_order_amount + $deliveryCharge,
                        'debit' => 0,
                        'transaction_type' => 'order_payment',
                        'reference' => 'order_payment',
                        'status' => 'pending',
                        'gateway' => $request->payment_method,
                        'balance' => 0,
                        'admin_bonus' => json_encode([
                            'gateway' => $request->payment_method,
                            'purpose' => 'order_payment',
                            'callback_url' => $paymentData['callback_url'],
                        ]),
                        'metadata' => json_encode([
                            'order_id' => $o_id,
                            'paymob_order_id' => $paymentResponse['order_id'] ?? null,
                            'paymob_transaction_id' => $paymentResponse['id'] ?? null,
                            'payment_key' => $paymentResponse['payment_key'] ?? null,
                            'gateway' => $request->payment_method,
                            'purpose' => 'order_payment',
                        ]),
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    // Create OrderTransaction
                    OrderTransaction::create([
                        'user_id' => $userId,
                        'order_id' => $o_id,
                        'transaction_id' => Str::uuid(),
                        'reference' => 'order_payment',
                        'transaction_type' => 'order_payment',
                        'debit' => 0,
                        'balance' => $user->wallet_balance,
                        'order_amount' => $calculated_order_amount + $deliveryCharge,
                        'total_amount' => $calculated_order_amount + $deliveryCharge,
                        'status' => 'pending',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);

                    Log::info('WalletTransaction Created for Order Payment', [
                        'transaction_id' => $transactionId,
                        'paymob_order_id' => $paymentResponse['order_id'] ?? null,
                        'user_id' => $userId,
                        'amount' => $calculated_order_amount + $deliveryCharge,
                        'gateway' => $request->payment_method,
                        'order_id' => $o_id
                    ]);

                    $this->order->where('id', $o_id)->update([
                        'transaction_reference' => $transactionId
                    ]);

                    DB::commit();

                    try {
                        $this->orderEmailAndNotification(request: $request, or: $or, order_id: $order_id);
                    } catch (Exception $e) {
                        Log::error('Email/Notification failed', ['error' => $e->getMessage()]);
                    }

                    Log::info('Order placed successfully (non-partial payment)', ['order_id' => $o_id, 'transaction_id' => $transactionId]);

                    return response()->json([
                        'message' => translate('order_pending_payment'),
                        'order_id' => $order_id,
                        'payment_details' => $paymentResponse
                    ], 200);

                } catch (Exception $e) {
                    DB::rollBack();
                    Log::error('Payment initiation failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'order_id' => $o_id
                    ]);
                    return response()->json(['errors' => [['code' => 'payment_error', 'message' => 'Payment initiation failed: ' . $e->getMessage()]]], 400);
                }
            }

            if ($request['selected_delivery_area']) {
                $orderArea = $this->orderArea;
                $orderArea->order_id = $order_id;
                $orderArea->branch_id = $or['branch_id'];
                $orderArea->area_id = $request['selected_delivery_area'];
                $orderArea->save();
            }

            DB::commit();

            try {
                $this->orderEmailAndNotification(request: $request, or: $or, order_id: $order_id);
            } catch (Exception $e) {
                Log::error('Email/Notification failed', ['error' => $e->getMessage()]);
            }

            Log::info('Order placed successfully', ['order_id' => $o_id, 'calculated_order_amount' => $calculated_order_amount]);

            return response()->json([
                'message' => translate('order_success'),
                'order_id' => $order_id,
                'order_amount' => Helpers::set_price($calculated_order_amount)
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Order placement failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json(['errors' => [['code' => 'server_error', 'message' => $e->getMessage()]]], 500);
        }
    }

    public function handleCallback(Request $request): JsonResponse
    {
        try {
            $data = $request->all();
            $transactionId = $data['transaction_id'] ?? $request->query('transaction_id');
            $orderId = $data['order'] ?? $request->query('order');
            $paymobTransactionId = $data['id'] ?? $request->query('id');
            $hmac = $data['hmac'] ?? $request->query('hmac');

            // Log incoming callback data
            Log::info('Callback Data Received', [
                'transaction_id' => $transactionId,
                'order_id' => $orderId,
                'paymob_transaction_id' => $paymobTransactionId,
                'hmac' => $hmac,
                'query' => $request->query(),
                'data' => $data
            ]);

            // Find transaction
            $query = WalletTransaction::where('status', 'pending');
            if ($transactionId) {
                $query->where('transaction_id', $transactionId);
            } elseif ($orderId) {
                $query->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.order_id")) = ?', [$orderId]);
            } elseif ($paymobTransactionId) {
                $query->orWhereRaw('JSON_UNQUOTE(JSON_EXTRACT(metadata, "$.paymob_transaction_id")) = ?', [$paymobTransactionId]);
            }
            $transaction = $query->first();

            if (!$transaction) {
                // Debug: Log all pending transactions to check metadata
                $pendingTransactions = WalletTransaction::where('status', 'pending')->get(['transaction_id', 'metadata'])->toArray();
                Log::error('Callback: Transaction not found', [
                    'transaction_id' => $transactionId,
                    'order_id' => $orderId,
                    'paymob_transaction_id' => $paymobTransactionId,
                    'pending_transactions' => $pendingTransactions
                ]);
                return response()->json([
                    'success' => false,
                    'message' => translate('Invalid or completed transaction')
                ], 400);
            }

            // Use the gateway stored in the transaction
            $gateway = $transaction->gateway;
            if (!$gateway) {
                Log::error('Callback: Gateway not specified', [
                    'transaction_id' => $transaction->transaction_id,
                    'order_id' => $orderId
                ]);
                return response()->json([
                    'success' => false,
                    'message' => translate('Gateway not specified for this transaction')
                ], 400);
            }

            $this->gateway = PaymentGatewayFactory::create($gateway);
            if (!$this->gateway) {
                Log::error('Callback: Invalid gateway', ['gateway' => $gateway]);
                return response()->json([
                    'success' => false,
                    'message' => translate('Invalid payment gateway')
                ], 400);
            }

            // Update transaction with Paymob transaction ID if available
            if ($paymobTransactionId && empty($transaction->metadata['paymob_transaction_id'])) {
                $metadata = json_decode($transaction->metadata, true) ?? [];
                $metadata['paymob_transaction_id'] = $paymobTransactionId;
                $transaction->update(['metadata' => json_encode($metadata)]);
            }

            $response = $this->gateway->handleCallback($data);

            if (isset($response['status']) && $response['status'] === 'success') {
                $transaction->update([
                    'status' => 'completed',
                    'balance' => $transaction->user->wallet_balance + $transaction->credit,
                    'updated_at' => now(),
                ]);

                $transaction->user->increment('wallet_balance', $transaction->credit);

                // Update order status
                $orderId = json_decode($transaction->metadata, true)['order_id'] ?? null;
                if ($orderId) {
                    \App\Models\Order::where('id', $orderId)->update([
                        'payment_status' => 'paid',
                        'order_status' => 'confirmed',
                        'updated_at' => now(),
                    ]);
                }

                Log::info('Callback: Payment processed successfully', [
                    'transaction_id' => $transaction->transaction_id,
                    'user_id' => $transaction->user_id,
                    'order_id' => $orderId,
                    'new_balance' => $transaction->user->wallet_balance
                ]);

                return response()->json([
                    'success' => true,
                    'message' => translate('Payment processed successfully'),
                    'transaction_id' => $transaction->transaction_id
                ], 200);
            } else {
                Log::error('Callback: Payment failed', [
                    'transaction_id' => $transaction->transaction_id,
                    'response' => $response
                ]);
                return response()->json([
                    'success' => false,
                    'message' => $response['error'] ?? translate('Payment failed')
                ], 400);
            }
        } catch (Exception $e) {
            Log::error('Callback handling failed', [
                'error' => $e->getMessage(),
                'transaction_id' => $transactionId,
                'order_id' => $orderId,
                'paymob_transaction_id' => $paymobTransactionId,
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'success' => false,
                'errors' => [['code' => 'server_error', 'message' => translate('Callback processing failed')]]
            ], 500);
        }
    }

    private function orderEmailAndNotification($request, $or, $order_id)
    {
        if ((bool)auth('api')->user()) {
            $fcmToken = auth('api')->user()?->cm_firebase_token;
            $local = auth('api')->user()?->language_code;
            $customerName = auth('api')->user()?->f_name . ' '. auth('api')->user()?->l_name;
        } else {
            $guest = GuestUser::find($request['guest_id']);
            $fcmToken = $guest ? $guest->fcm_token : '';
            $local = 'en';
            $customerName = 'Guest User';
        }

        $message = Helpers::order_status_update_message($or['order_status']);

        if ($local != 'en') {
            $statusKey = Helpers::order_status_message_key($or['order_status']);
            $translatedMessage = $this->business_setting->with('translations')->where(['key' => $statusKey])->first();
            if (isset($translatedMessage->translations)) {
                foreach ($translatedMessage->translations as $translation) {
                    if ($local == $translation->locale) {
                        $message = $translation->value;
                    }
                }
            }
        }
        $restaurantName = Helpers::get_business_settings('restaurant_name');
        $value = Helpers::text_variable_data_format(value: $message, user_name: $customerName, restaurant_name: $restaurantName, order_id: $order_id);

        try {
            if ($value && isset($fcmToken)) {
                $data = [
                    'title' => translate('Order'),
                    'description' => $value,
                    'order_id' => (bool)auth('api')->user() ? $order_id : null,
                    'image' => '',
                    'type' => 'order_status',
                ];
                Helpers::send_push_notif_to_device($fcmToken, $data);
            }
        } catch (\Exception $e) {
            //
        }

        try {
            $emailServices = Helpers::get_business_settings('mail_config');
            $orderMailStatus = Helpers::get_business_settings('place_order_mail_status_user');
            if (isset($emailServices['status']) && $emailServices['status'] == 1 && $orderMailStatus == 1 && (bool)auth('api')->user()) {
                Mail::to(auth('api')->user()->email)->send(new \App\Mail\OrderPlaced($order_id));
            }
        } catch (\Exception $e) {
            //
        }

        if ($or['order_status'] == 'confirmed') {
            $data = [
                'title' => translate('You have a new order - (Order Confirmed).'),
                'description' => $order_id,
                'order_id' => $order_id,
                'image' => '',
                'order_status' => $or['order_status'],
            ];

            try {
                Helpers::send_push_notif_to_topic(data: $data, topic: "kitchen-{$or['branch_id']}", type: 'general', isNotificationPayloadRemove: true);
            } catch (\Exception $e) {
                //
            }
        }

        try {
            $data = [
                'title' => translate('New Order Notification'),
                'description' => translate('You have new order, Check Please'),
                'order_id' => $order_id,
                'image' => '',
                'type' => 'new_order_admin',
            ];

            Helpers::send_push_notif_to_topic(data: $data, topic: 'admin_message', type: 'order_request', web_push_link: route('admin.orders.list', ['status' => 'all']));
            Helpers::send_push_notif_to_topic(data: $data, topic: 'branch-order-'. $or['branch_id'] .'-message', type: 'order_request', web_push_link: route('branch.orders.list', ['status' => 'all']));
        } catch (\Exception $exception) {
            //
        }
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function trackOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'guest_id' => auth('api')->user() ? 'nullable' : 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $userId = (bool)auth('api')->user() ? auth('api')->user()->id : $request['guest_id'];
        $userType = (bool)auth('api')->user() ? 0 : 1;

        $order = $this->order->where(['id' => $request['order_id'], 'user_id' => $userId, 'is_guest' => $userType])->first();
        if (!isset($order)) {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => translate('Order not found!')]
                ]
            ], 404);
        }

        return response()->json(OrderLogic::track_order($request['order_id']), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderList(Request $request): JsonResponse
    {
        $userId = (bool)auth('api')->user() ? auth('api')->user()->id : $request['guest_id'];
        $userType = (bool)auth('api')->user() ? 0 : 1;
        $orderFilter = $request->order_filter;

        $orders = $this->order->with(['customer', 'delivery_man.rating'])
            ->withCount('details')
            ->withCount(['details as total_quantity' => function($query) {
                $query->select(DB::raw('sum(quantity)'));
            }])
            ->where(['user_id' => $userId, 'is_guest' => $userType])
            ->when($orderFilter == 'history', function ($query) use ($orderFilter) {
                $query->whereIn('order_status', ['delivered', 'canceled', 'failed', 'returned']);
            })
            ->when($orderFilter == 'ongoing', function ($query) use ($orderFilter) {
                $query->whereNotIn('order_status', ['delivered', 'canceled', 'failed', 'returned']);
            })
            ->orderBy('id', 'DESC')
            ->paginate($request['limit'], ['*'], 'page', $request['offset']);

        $orders->map(function ($data) {
            $data['deliveryman_review_count'] = DMReview::where(['delivery_man_id' => $data['delivery_man_id'], 'order_id' => $data['id']])->count();

            $order_id = $data->id;
            $order_details = $this->order_detail->where('order_id', $order_id)->first();
            $product_id = $order_details?->product_id;

            $data['is_product_available'] = $product_id ? $this->product->find($product_id) ? 1 : 0 : 0;
            $data['details_count'] = (int)$data->details_count;

            $productImages = $this->order_detail->where('order_id', $order_id)->pluck('product_id')
                ->filter()
                ->map(function ($product_id) {
                    $product = $this->product->find($product_id);
                    return $product ? $product->image : null;
                })->filter();

            $data['product_images'] = $productImages->toArray();

            return $data;
        });

        $ordersArray = [
            'total_size' => $orders->total(),
            'limit' => $request['limit'],
            'offset' => $request['offset'],
            'orders' => $orders->items(),
        ];

        return response()->json($ordersArray, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getOrderDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $userId = (bool)auth('api')->user() ? auth('api')->user()->id : $request['guest_id'];
        $userType = (bool)auth('api')->user() ? 0 : 1;

        $details = $this->order_detail->with(['order',
            'order.delivery_man' => function ($query) {
                $query->select('id', 'f_name', 'l_name', 'phone', 'email', 'image', 'branch_id', 'is_active');
            },
            'order.delivery_man.rating', 'order.delivery_address', 'order.order_partial_payments' , 'order.offline_payment', 'order.deliveryman_review'])
            ->withCount(['reviews'])
            ->where(['order_id' => $request['order_id']])
            ->whereHas('order', function ($q) use ($userId, $userType){
                $q->where([ 'user_id' => $userId, 'is_guest' => $userType ]);
            })
            ->get();

        if ($details->count() < 1) {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => translate('Order not found!')]
                ]
            ], 404);
        }

        $details = Helpers::order_details_formatter($details);
        return response()->json($details, 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function cancelOrder(Request $request): JsonResponse
    {
        $order = $this->order::find($request['order_id']);

        if (!isset($order)){
            return response()->json(['errors' => [['code' => 'order', 'message' => 'Order not found!']]], 404);
        }

        if ($order->order_status != 'pending'){
            return response()->json(['errors' => [['code' => 'order', 'message' => 'Order can only cancel when order status is pending!']]], 403);
        }

        $userId = (bool)auth('api')->user() ? auth('api')->user()->id : $request['guest_id'];
        $userType = (bool)auth('api')->user() ? 0 : 1;

        if ($this->order->where(['user_id' => $userId, 'is_guest' => $userType, 'id' => $request['order_id']])->first()) {
            $this->order->where(['user_id' => $userId, 'is_guest' => $userType, 'id' => $request['order_id']])->update([
                'order_status' => 'canceled'
            ]);
            return response()->json(['message' => translate('order_canceled')], 200);
        }
        return response()->json([
            'errors' => [
                ['code' => 'order', 'message' => translate('no_data_found')]
            ]
        ], 401);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function updatePaymentMethod(Request $request): JsonResponse
    {
        if ($this->order->where(['user_id' => $request->user()->id, 'id' => $request['order_id']])->first()) {
            $this->order->where(['user_id' => $request->user()->id, 'id' => $request['order_id']])->update([
                'payment_method' => $request['payment_method']
            ]);
            return response()->json(['message' => translate('payment_method_updated')], 200);
        }
        return response()->json([
            'errors' => [
                ['code' => 'order', 'message' => translate('no_data_found')]
            ]
        ], 401);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function guestTrackOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $orderId = $request->input('order_id');
        $phone = $request->input('phone');

        $order = $this->order->with(['customer'])
            ->where('id', $orderId)
            ->where(function ($query) use ($phone) {
                $query->where(function ($subQuery) use ($phone) {
                    $subQuery->where('is_guest', 0)
                        ->whereHas('customer', function ($customerSubQuery) use ($phone) {
                            $customerSubQuery->where('phone', $phone);
                        });
                })
                    ->orWhere(function ($subQuery) use ($phone) {
                        $subQuery->where('is_guest', 1)
                            ->whereHas('delivery_address', function ($addressSubQuery) use ($phone) {
                                $addressSubQuery->where('contact_person_number', $phone);
                            })
                            ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(orders.delivery_address, '$.contact_person_number')) = ?", [$phone]);
                    });
            })
            ->first();

        if (!isset($order)) {
            return response()->json(['errors' => [['code' => 'order', 'message' => translate('Order not found!')]]], 404);
        }

        return response()->json(OrderLogic::track_order($request['order_id']), 200);
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function getGuestOrderDetails(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'phone' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        $phone = $request->input('phone');

        $details = $this->order_detail->with([
            'order',
            'order.customer',
            'order.order_partial_payments'
        ])
            ->withCount(['reviews'])
            ->where(['order_id' => $request['order_id']])
            ->where(function ($query) use ($phone) {
                $query->where(function ($subQuery) use ($phone) {
                    $subQuery->whereHas('order', function ($orderSubQuery) use ($phone) {
                        $orderSubQuery->where('is_guest', 0)
                            ->whereHas('customer', function ($customerSubQuery) use ($phone) {
                                $customerSubQuery->where('phone', $phone);
                            });
                    });
                })
                    ->orWhere(function ($subQuery) use ($phone) {
                        $subQuery->whereHas('order', function ($orderSubQuery) use ($phone) {
                            $orderSubQuery->where('is_guest', 1)
                                ->whereHas('delivery_address', function ($addressSubQuery) use ($phone) {
                                    $addressSubQuery->where('contact_person_number', $phone);
                                })
                                ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(orders.delivery_address, '$.contact_person_number')) = ?", [$phone]);
                        });
                    });
            })
            ->get();

        if ($details->count() < 1) {
            return response()->json([
                'errors' => [
                    ['code' => 'order', 'message' => translate('Order not found!')]
                ]
            ], 404);
        }

        $details = Helpers::order_details_formatter($details);
        return response()->json($details, 200);
    }

    /**
     * Get available payment methods for order
     */
    public function getOrderPaymentMethods(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
            'amount' => 'required|numeric|min:0.01'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        try {
            $paymentController = app(PaymentsController::class);
            $response = $paymentController->getPaymentMethods(new Request([
                'order_id' => $request->order_id,
                'amount' => $request->amount,
                'currency' => 'EGP',
                'purpose' => 'order_payment'
            ]));

            return response()->json($response->getData(), 200);

        } catch (Exception $e) {
            return response()->json(['errors' => [['code' => 'payment_error', 'message' => 'Failed to get payment methods']]], 500);
        }
    }

    /**
     * Process order payment
     */
    public function processOrderPayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required|integer|exists:orders,id',
            'gateway' => 'required|string',
            'amount' => 'required|numeric|min:0.01'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        try {
            $paymentController = app(PaymentsController::class);
            $response = $paymentController->initiatePayment(new Request([
                'gateway' => $request->gateway,
                'amount' => $request->amount,
                'currency' => 'EGP',
                'purpose' => 'order_payment',
                'order_id' => $request->order_id,
                'customer_data' => [
                    'user_id' => $request->user()->id,
                    'email' => $request->user()->email,
                    'phone' => $request->user()->phone,
                    'f_name' => $request->user()->f_name,
                    'l_name' => $request->user()->l_name
                ]
            ]));

            return response()->json($response->getData(), 200);

        } catch (Exception $e) {
            return response()->json(['errors' => [['code' => 'payment_error', 'message' => 'Failed to process payment']]], 500);
        }
    }
}