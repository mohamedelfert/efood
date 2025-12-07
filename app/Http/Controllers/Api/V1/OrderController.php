<?php

namespace App\Http\Controllers\Api\V1;

use App\User;
use Exception;
use Carbon\Carbon;
use App\Model\AddOn;
use App\Model\Order;
use App\Model\Branch;
use App\Model\Product;
use App\Model\Currency;
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
use App\Services\NotificationService;
use App\Services\PaymentGatewayHelper;
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
    protected $notificationService;

    public function __construct(
        Order $order,
        OrderDetail $order_detail,
        User $user,
        Product $product,
        ProductByBranch $product_by_branch,
        OfflinePayment $offlinePayment,
        OrderArea $orderArea,
        NotificationService $notificationService
    ) {
        $this->order = $order;
        $this->order_detail = $order_detail;
        $this->user = $user;
        $this->product = $product;
        $this->product_by_branch = $product_by_branch;
        $this->offlinePayment = $offlinePayment;
        $this->orderArea = $orderArea;
        $this->notificationService = $notificationService;
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function placeOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => 'required|in:wallet_payment,paymob,qib,cash_on_delivery,offline_payment',
            'order_type' => 'required|in:delivery,take_away,in_car,in_restaurant',
            'car_color' => 'required_if:order_type,in_car|string|max:50',
            'car_registration_number' => 'required_if:order_type,in_car|string|max:50',
            'branch_id' => 'required|integer|exists:branches,id',
            'delivery_time' => 'required',
            'delivery_date' => 'required',
            'distance' => 'required|numeric',
            'guest_id' => auth('api')->user() ? 'nullable' : 'required|integer',
            'is_partial' => 'required|in:0,1',
            'customer_data' => 'required_if:payment_method,paymob,qib|array',
            'customer_data.email' => 'required_if:payment_method,paymob,qib|email',
            'customer_data.phone' => 'required_if:payment_method,paymob,qib|string',
            'customer_data.name' => 'required_if:payment_method,paymob,qib|string',
            'payment_CustomerNo' => 'required_if:payment_method,qib|string',
            'payment_DestNation' => 'required_if:payment_method,qib|integer',
            'payment_Code' => 'required_if:payment_method,qib|integer',
            'cart' => 'required|array|min:1',
            'cart.*.product_id' => 'required|integer|exists:products,id',
            'cart.*.quantity' => 'required|integer|min:1',
            'cart.*.variant' => 'nullable|array',
            'cart.*.variant.*' => 'string',
            'cart.*.variations' => 'nullable|array',
            'cart.*.add_on_ids' => 'nullable|array',
            'cart.*.add_on_qtys' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => Helpers::error_processor($validator)], 403);
        }

        if (count($request['cart']) < 1) {
            return response()->json([
                'errors' => [['code' => 'empty-cart', 'message' => translate('Cart is empty')]]
            ], 403);
        }

        // Validate cart stock before proceeding
        $stockValidation = $this->validateCartInternal($request->branch_id, $request->cart);
        if (!$stockValidation['is_valid']) {
            return response()->json([
                'errors' => [[
                    'code' => 'stock_validation_failed',
                    'message' => translate('Some items are out of stock or have insufficient quantity. Please adjust your order.'),
                    'details' => $stockValidation['invalid_items']
                ]]
            ], 403);
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
        $orderStatus = 'pending';
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
                $branch_product = $this->product_by_branch->where([
                    'product_id' => $c['product_id'],
                    'branch_id' => $request['branch_id']
                ])->first();

                if (!$product || !$branch_product) {
                    DB::rollBack();
                    return response()->json([
                        'errors' => [['code' => 'product_error', 'message' => translate('Product data inconsistency detected')]]
                    ], 500);
                }

                // Additional stock check
                if (in_array($branch_product->stock_type, ['daily', 'fixed'])) {
                    $available_stock = $branch_product->stock - ($branch_product->sold_quantity ?? 0);
                    if ($available_stock < $c['quantity']) {
                        DB::rollBack();
                        return response()->json([
                            'errors' => [[
                                'code' => 'insufficient_stock',
                                'message' => translate('Insufficient stock for product :name. Only :count items available.', [
                                    'name' => $product->name ?? 'Unknown Product',
                                    'count' => $available_stock
                                ])
                            ]]
                        ], 403);
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
                        return response()->json([
                            'errors' => [['code' => 'addon_not_found', 'message' => translate('Add-on not found for ID: ' . $id)]]
                        ], 404);
                    }
                    $add_on_prices[] = $addon->price;
                    $add_on_taxes[] = ($addon->price * $addon->tax) / 100;
                    $total_addon_price += $addon->price * ($add_on_quantities[$key] ?? 1);
                }

                $total_addon_tax = array_reduce(
                    array_map(fn($qty, $tax) => $qty * $tax, $add_on_quantities, $add_on_taxes),
                    fn($carry, $item) => $carry + $item,
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
                    'variant' => json_encode($c['variant'] ?? []),
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
                if (in_array($branch_product->stock_type, ['daily', 'fixed'])) {
                    $branch_product->increment('sold_quantity', $c['quantity']);
                }
            }

            // Apply delivery charge and coupon discount
            $calculated_order_amount += $deliveryCharge;
            $coupon_discount_amount = Helpers::set_price($request->coupon_discount_amount ?? 0);
            $calculated_order_amount -= $coupon_discount_amount;

            if ($calculated_order_amount < 0.01) {
                DB::rollBack();
                return response()->json([
                    'errors' => [['code' => 'invalid_amount', 'message' => translate('Order amount must be at least 0.01')]]
                ], 403);
            }

            // Validate wallet payment
            if ($request->payment_method == 'wallet_payment') {
                if (Helpers::get_business_settings('wallet_status') != 1) {
                    DB::rollBack();
                    return response()->json([
                        'errors' => [['code' => 'payment_method', 'message' => translate('Customer wallet status is disabled')]]
                    ], 403);
                }
                if ($customer && $customer->wallet_balance < $calculated_order_amount) {
                    DB::rollBack();
                    return response()->json([
                        'errors' => [['code' => 'payment_method', 'message' => translate('You do not have sufficient balance in wallet')]]
                    ], 403);
                }
            }

            // Validate partial payment
            if ($request['is_partial'] == 1) {
                if (Helpers::get_business_settings('wallet_status') != 1) {
                    DB::rollBack();
                    return response()->json([
                        'errors' => [['code' => 'payment_method', 'message' => translate('Customer wallet status is disabled')]]
                    ], 403);
                }
                if ($customer && $customer->wallet_balance >= $calculated_order_amount) {
                    DB::rollBack();
                    return response()->json([
                        'errors' => [['code' => 'payment_method', 'message' => translate('Since your wallet balance is more than order amount, you cannot place partial order')]]
                    ], 403);
                }
                if ($customer && $customer->wallet_balance < 1) {
                    DB::rollBack();
                    return response()->json([
                        'errors' => [['code' => 'payment_method', 'message' => translate('Since your wallet balance is less than 1, you cannot place partial order')]]
                    ], 403);
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
                'delivery_address' => $request->delivery_address_id && ($address = CustomerAddress::find($request->delivery_address_id)) ? json_encode($address) : ($request->delivery_address ? json_encode($request->delivery_address) : null),
                'delivery_charge' => $deliveryCharge,
                'preparation_time' => 0,
                'is_cutlery_required' => $request['is_cutlery_required'] ?? 0,
                'bring_change_amount' => $request->payment_method != 'cash_on_delivery' ? 0 : ($request->bring_change_amount ?? 0),
                'total_tax_amount' => $totalTaxAmount,
                'car_color' => $request->order_type == 'in_car' ? $request->car_color : null,
                'car_registration_number' => $request->order_type == 'in_car' ? $request->car_registration_number : null,
                'created_at' => now(),
                'updated_at' => now()
            ];

            $o_id = $this->order->insertGetId($or);

            // Handle wallet payment
            if ($request->payment_method == 'wallet_payment' && !$request->is_partial) {
                $amount = $or['order_amount'] + $or['delivery_charge'];
                $walletTransaction = CustomerLogic::create_wallet_transaction(
                    $or['user_id'], $amount, 'order_place', 'ORDER_' . $o_id, $o_id
                );
                if (!$walletTransaction) {
                    DB::rollBack();
                    Log::error('Wallet transaction creation failed for wallet payment', ['order_id' => $o_id]);
                    return response()->json([
                        'errors' => [['code' => 'wallet_error', 'message' => translate('Failed to create wallet transaction')]]
                    ], 400);
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
                    $walletTransaction = CustomerLogic::create_wallet_transaction(
                        $or['user_id'], $walletAmount, 'order_place', 'ORDER_' . $o_id, $o_id
                    );
                    if (!$walletTransaction) {
                        DB::rollBack();
                        Log::error('Wallet transaction creation failed for partial payment (wallet)', ['order_id' => $o_id]);
                        return response()->json([
                            'errors' => [['code' => 'wallet_error', 'message' => translate('Failed to create wallet transaction')]]
                        ], 400);
                    }
                    $partial = new OrderPartialPayment;
                    $partial->order_id = $o_id;
                    $partial->paid_with = 'wallet_payment';
                    $partial->paid_amount = $walletAmount;
                    $partial->due_amount = $dueAmount;
                    $partial->transaction_id = $walletTransaction->transaction_id;
                    $partial->save();
                }

                if ($request->payment_method != 'cash_on_delivery' && $request->payment_method != 'offline_payment') {
                    $customerData = [
                        'user_id' => $userId,
                        'name' => $request->customer_data['name'],
                        'email' => $request->customer_data['email'],
                        'phone' => $request->customer_data['phone'],
                    ];
                    $callbackUrl = env('APP_URL') . config('payment.callback_url') ?? null;
                    $transactionId = 'PAY_' . time() . '_' . $userId;
                    $amountCents = round($dueAmount * 100);
                    $paymentData = [
                        'gateway' => $request->payment_method,
                        'amount' => $amountCents,
                        'currency' => Currency::where('is_primary', true)->first()->code ?? 'SAR',
                        'purpose' => 'order_payment',
                        'order_id' => $o_id,
                        'order_type' => $request->order_type,
                        'customer_data' => $customerData,
                        'callback_url' => $callbackUrl,
                        'transaction_id' => $transactionId,
                        'items' => array_map(function ($item) use ($request) {
                            $product = Product::find($item['product_id']);
                            return [
                                'name' => $product->name ?? 'Product ' . $item['product_id'],
                                'amount_cents' => round(($product->price ?? 0) * 100),
                                'quantity' => $item['quantity'],
                            ];
                        }, $request->cart),
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
                        return response()->json([
                            'errors' => [['code' => 'payment_error', 'message' => translate('Invalid payment gateway')]]
                        ], 400);
                    }

                    try {
                        $paymentResponse = $gateway->requestPayment($paymentData);
                        if (!isset($paymentResponse['status']) || !$paymentResponse['status']) {
                            DB::rollBack();
                            Log::error('Payment initiation failed', [
                                'error' => $paymentResponse['error'] ?? 'Unknown error',
                                'payment_data' => $paymentResponse
                            ]);
                            return response()->json([
                                'errors' => [['code' => 'payment_error', 'message' => $paymentResponse['error'] ?? 'Payment initiation failed']]
                            ], 400);
                        }

                        $walletTransaction = CustomerLogic::create_wallet_transaction(
                            $userId, $dueAmount, 'add_fund', 'ORDER_PAYMENT_' . $o_id, $o_id
                        );
                        if (!$walletTransaction) {
                            DB::rollBack();
                            Log::error('Wallet transaction creation failed for Paymob/QIB partial payment', ['order_id' => $o_id]);
                            return response()->json([
                                'errors' => [['code' => 'wallet_error', 'message' => translate('Failed to create wallet transaction')]]
                            ], 400);
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
                            $customer = auth('api')->user() ?? GuestUser::find($request['guest_id']);
                            
                            // Define $currency before using it
                            $currency = Currency::where('is_primary', true)->first()->code ?? 'SAR';
                            
                            if ($customer) {
                                $this->notificationService->sendOrderPlacedNotification(
                                    $customer,
                                    $this->order->find($o_id),
                                    [
                                        'currency' => $currency,
                                        'order_type' => $request['order_type'],
                                    ]
                                );
                            }
                            
                            $this->orderEmailAndNotification(request: $request, or: $or, order_id: $order_id);
                        } catch (\Exception $e) {
                            Log::error('Email/Notification failed', ['error' => $e->getMessage()]);
                        }

                        Log::info('Order placed successfully (partial payment)', [
                            'order_id' => $o_id,
                            'transaction_id' => $walletTransaction->transaction_id
                        ]);

                        // Get gateway type information
                        $gatewayInfo = PaymentGatewayHelper::getGatewayInfo($request->payment_method);

                        return response()->json([
                            'message' => translate('order_pending_payment'),
                            'order_id' => $order_id,
                            'gateway' => $request->payment_method,
                            'requires_online_url' => $gatewayInfo['requires_online_url'], // ✅ NEW
                            'payment_type' => $gatewayInfo['payment_type'], // ✅ NEW
                            'payment_details' => $paymentResponse
                        ], 200);
                    } catch (\Exception $e) {
                        DB::rollBack();
                        Log::error('Payment initiation failed', [
                            'error' => $e->getMessage(),
                            'trace' => $e->getTraceAsString(),
                            'order_id' => $o_id
                        ]);
                        return response()->json([
                            'errors' => [['code' => 'payment_error', 'message' => 'Payment initiation failed: ' . $e->getMessage()]]
                        ], 400);
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
                    'name' => $request->customer_data['name'],
                    'email' => $request->customer_data['email'],
                    'phone' => $request->customer_data['phone'],
                ];
                $callbackUrl = env('APP_URL') . config('payment.callback_url') ?? null;
                $transactionId = 'PAY_' . time() . '_' . $userId;
                $paymentData = [
                    'gateway' => $request->payment_method,
                    'amount' => $calculated_order_amount + $deliveryCharge,
                    'currency' => 'EGP',
                    'purpose' => 'order_payment',
                    'order_id' => $o_id,
                    'customer_data' => $customerData,
                    'callback_url' => $callbackUrl,
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
                    return response()->json([
                        'errors' => [['code' => 'payment_error', 'message' => translate('Invalid payment gateway')]]
                    ], 400);
                }

                try {
                    $paymentResponse = $gateway->requestPayment($paymentData);
                    if (!isset($paymentResponse['status']) || !$paymentResponse['status']) {
                        DB::rollBack();
                        Log::error('Payment initiation failed', ['error' => $paymentResponse['error'] ?? 'Unknown error']);
                        return response()->json([
                            'errors' => [['code' => 'payment_error', 'message' => $paymentResponse['error'] ?? 'Payment initiation failed']]
                        ], 400);
                    }

                    $user = User::find($userId);
                    if (!$user) {
                        DB::rollBack();
                        Log::error('User not found', ['user_id' => $userId]);
                        return response()->json([
                            'errors' => [['code' => 'user_error', 'message' => translate('User not found')]]
                        ], 400);
                    }

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
                        $customer = auth('api')->user() ?? GuestUser::find($request['guest_id']);
                        
                        // Define $currency before using it
                        $currency = Currency::where('is_primary', true)->first()->code ?? 'SAR';
                        
                        if ($customer) {
                            $this->notificationService->sendOrderPlacedNotification(
                                $customer,
                                $this->order->find($o_id),
                                [
                                    'currency' => $currency,
                                    'order_type' => $request['order_type'],
                                ]
                            );
                        }
                        
                        $this->orderEmailAndNotification(request: $request, or: $or, order_id: $order_id);
                    } catch (\Exception $e) {
                        Log::error('Email/Notification failed', ['error' => $e->getMessage()]);
                    }

                    Log::info('Order placed successfully (non-partial payment)', [
                        'order_id' => $o_id,
                        'transaction_id' => $transactionId
                    ]);

                    // Get gateway type information
                    $gatewayInfo = PaymentGatewayHelper::getGatewayInfo($request->payment_method);

                    return response()->json([
                        'message' => translate('order_pending_payment'),
                        'order_id' => $order_id,
                        'gateway' => $request->payment_method,
                        'requires_online_url' => $gatewayInfo['requires_online_url'],
                        'payment_type' => $gatewayInfo['payment_type'],
                        'payment_details' => $paymentResponse
                    ], 200);
                } catch (\Exception $e) {
                    DB::rollBack();
                    Log::error('Payment initiation failed', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString(),
                        'order_id' => $o_id
                    ]);
                    return response()->json([
                        'errors' => [['code' => 'payment_error', 'message' => 'Payment initiation failed: ' . $e->getMessage()]]
                    ], 400);
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
                $customer = auth('api')->user() ?? GuestUser::find($request['guest_id']);
                
                // Define $currency before using it
                $currency = Currency::where('is_primary', true)->first()->code ?? 'SAR';
                
                if ($customer) {
                    $this->notificationService->sendOrderPlacedNotification(
                        $customer,
                        $this->order->find($o_id),
                        [
                            'currency' => $currency,
                            'order_type' => $request['order_type'],
                        ]
                    );
                }
                
                $this->orderEmailAndNotification(request: $request, or: $or, order_id: $order_id);
            } catch (\Exception $e) {
                Log::error('Email/Notification failed', ['error' => $e->getMessage()]);
            }

            Log::info('Order placed successfully', [
                'order_id' => $o_id,
                'calculated_order_amount' => $calculated_order_amount
            ]);

            // Return response with gateway info for all payment methods
            $response = [
                'message' => translate('order_success'),
                'order_id' => $order_id,
                'order_amount' => Helpers::set_price($calculated_order_amount),
                'payment_method' => $request->payment_method,
            ];

            // Add gateway info for electronic payments
            if (in_array($request->payment_method, ['wallet_payment', 'paymob', 'qib', 'offline_payment'])) {
                $gatewayInfo = PaymentGatewayHelper::getGatewayInfo($request->payment_method);
                $response['requires_online_url'] = $gatewayInfo['requires_online_url'];
                $response['payment_type'] = $gatewayInfo['payment_type'];
            }

            return response()->json($response, 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Order placement failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);
            return response()->json([
                'errors' => [['code' => 'server_error', 'message' => $e->getMessage()]]
            ], 500);
        }
    }

    public function validateCart(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'branch_id' => 'required|integer|exists:branches,id',
            'cart' => 'required|array|min:1',
            'cart.*.product_id' => 'required|integer|exists:products,id',
            'cart.*.quantity' => 'required|integer|min:1',
            'cart.*.variations' => 'nullable|array',
            'cart.*.add_on_ids' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => Helpers::error_processor($validator)
            ], 403);
        }

        if (count($request['cart']) < 1) {
            return response()->json([
                'success' => false,
                'errors' => [['code' => 'empty-cart', 'message' => translate('Cart is empty')]]
            ], 403);
        }

        // Update daily stock before validation
        Helpers::update_daily_product_stock();

        // Get all product IDs from cart
        $productIds = array_column($request['cart'], 'product_id');
        $products = $this->product->whereIn('id', $productIds)->get()->keyBy('id');
        $branchProducts = $this->product_by_branch
            ->whereIn('product_id', $productIds)
            ->where('branch_id', $request['branch_id'])
            ->get()
            ->keyBy('product_id');

        $validationErrors = [];
        $cartSummary = [
            'subtotal' => 0,
            'total_tax' => 0,
            'total_discount' => 0,
            'items_count' => 0,
            'valid_items' => 0,
            'invalid_items' => 0,
        ];

        foreach ($request['cart'] as $index => $cartItem) {
            $itemValidation = [
                'cart_index' => $index,
                'product_id' => $cartItem['product_id'],
                'is_valid' => true,
                'errors' => [],
                'warnings' => [],
            ];

            // Validate product exists
            $product = $products->get($cartItem['product_id']);
            if (!$product) {
                $itemValidation['is_valid'] = false;
                $itemValidation['errors'][] = [
                    'code' => 'product_not_found',
                    'message' => translate('Product not found'),
                ];
                $validationErrors[] = $itemValidation;
                $cartSummary['invalid_items']++;
                continue;
            }

            $itemValidation['product_name'] = $product->name ?? 'Unknown Product';
            $itemValidation['product_image'] = $product->image;

            // Check if product is available
            if ($product->status != 1) {
                $itemValidation['is_valid'] = false;
                $itemValidation['errors'][] = [
                    'code' => 'product_unavailable',
                    'message' => translate('Product is currently unavailable'),
                ];
            }

            // Validate product in branch
            $branch_product = $branchProducts->get($cartItem['product_id']);
            if (!$branch_product) {
                $itemValidation['is_valid'] = false;
                $itemValidation['errors'][] = [
                    'code' => 'branch_product_not_found',
                    'message' => translate('Product not available in selected branch'),
                ];
                $validationErrors[] = $itemValidation;
                $cartSummary['invalid_items']++;
                continue;
            }

            // Check branch product availability
            if ($branch_product->is_available != 1) {
                $itemValidation['is_valid'] = false;
                $itemValidation['errors'][] = [
                    'code' => 'branch_product_unavailable',
                    'message' => translate('Product is currently unavailable in this branch'),
                ];
            }

            // Stock validation
            if (in_array($branch_product->stock_type, ['daily', 'fixed'])) {
                $available_stock = $branch_product->stock - ($branch_product->sold_quantity ?? 0);
                $itemValidation['available_stock'] = $available_stock;
                $itemValidation['requested_quantity'] = $cartItem['quantity'];
                $itemValidation['stock_type'] = $branch_product->stock_type;

                if ($available_stock <= 0) {
                    $itemValidation['is_valid'] = false;
                    $itemValidation['errors'][] = [
                        'code' => 'no_stock',
                        'message' => translate('Product :name is out of stock', ['name' => $product->name ?? 'Unknown Product']),
                    ];
                } elseif ($available_stock < $cartItem['quantity']) {
                    $itemValidation['is_valid'] = false;
                    $itemValidation['errors'][] = [
                        'code' => 'insufficient_stock',
                        'message' => translate('Insufficient stock for product :name. Only :count items available. Please order less.', [
                            'name' => $product->name ?? 'Unknown Product',
                            'count' => $available_stock
                        ]),
                        'available_stock' => $available_stock,
                        'requested_quantity' => $cartItem['quantity'],
                    ];
                } elseif ($available_stock < ($cartItem['quantity'] * 1.5)) {
                    $itemValidation['warnings'][] = [
                        'code' => 'low_stock',
                        'message' => translate('Low stock warning for product :name. Only :count items left.', [
                            'name' => $product->name ?? 'Unknown Product',
                            'count' => $available_stock
                        ]),
                    ];
                }
            } else {
                $itemValidation['stock_type'] = 'unlimited';
                $itemValidation['available_stock'] = 'unlimited';
            }

            // Validate variations
            $price = $branch_product->price;
            $variations = [];
            if (!empty($cartItem['variations'])) {
                $branch_variations = is_string($branch_product->variations) ? json_decode($branch_product->variations, true) : $branch_product->variations;
                $branch_variations = is_array($branch_variations) ? $branch_variations : [];
                if (!empty($branch_variations)) {
                    try {
                        $variation_data = Helpers::get_varient($branch_variations, $cartItem['variations']);
                        $price += $variation_data['price'] ?? 0;
                        $variations = $variation_data['variations'] ?? [];
                    } catch (\Exception $e) {
                        $itemValidation['warnings'][] = [
                            'code' => 'invalid_variation',
                            'message' => translate('Selected variation may not be available'),
                        ];
                    }
                }
            }

            // Validate add-ons
            $total_addon_price = 0;
            $valid_addons = [];
            foreach ($cartItem['add_on_ids'] ?? [] as $key => $addon_id) {
                $addon = AddOn::find($addon_id);
                if (!$addon) {
                    $itemValidation['is_valid'] = false;
                    $itemValidation['errors'][] = [
                        'code' => 'addon_not_found',
                        'message' => translate('Add-on not found for ID: :id', ['id' => $addon_id]),
                        'addon_id' => $addon_id,
                    ];
                } else {
                    $addon_qty = $cartItem['add_on_qtys'][$key] ?? 1;
                    $total_addon_price += $addon->price * $addon_qty;
                    $valid_addons[] = [
                        'id' => $addon->id,
                        'name' => $addon->name,
                        'price' => $addon->price,
                        'quantity' => $addon_qty,
                        'total' => $addon->price * $addon_qty,
                    ];
                }
            }

            // Calculate item pricing
            if ($itemValidation['is_valid']) {
                $discount_data = [
                    'discount_type' => $branch_product->discount_type ?? $product->discount_type,
                    'discount' => $branch_product->discount ?? $product->discount,
                ];
                $discount_on_product = Helpers::discount_calculate($discount_data, $price);
                $tax_amount = Helpers::new_tax_calculate($product, $price, $discount_data);
                $item_subtotal = (($price * $cartItem['quantity']) - $discount_on_product) + $total_addon_price;
                $item_total_tax = $tax_amount * $cartItem['quantity'];

                $itemValidation['pricing'] = [
                    'base_price' => $branch_product->price,
                    'price_with_variations' => $price,
                    'discount' => $discount_on_product,
                    'addon_total' => $total_addon_price,
                    'subtotal' => $item_subtotal,
                    'tax_amount' => $item_total_tax,
                    'total' => $item_subtotal + $item_total_tax,
                ];
                $itemValidation['addons'] = $valid_addons;

                $cartSummary['subtotal'] += $item_subtotal;
                $cartSummary['total_tax'] += $item_total_tax;
                $cartSummary['total_discount'] += $discount_on_product;
                $cartSummary['valid_items']++;
            } else {
                $cartSummary['invalid_items']++;
            }

            $validationErrors[] = $itemValidation;
            $cartSummary['items_count']++;
        }

        // Determine overall cart validity
        $isCartValid = $cartSummary['invalid_items'] === 0;
        $cartSummary['grand_total'] = $cartSummary['subtotal'] + $cartSummary['total_tax'];

        return response()->json([
            'success' => true,
            'is_valid' => $isCartValid,
            'message' => $isCartValid ? translate('All cart items are valid and available') : translate('Some cart items have issues. Please adjust your order.'),
            'cart_summary' => $cartSummary,
            'items' => $validationErrors,
            'timestamp' => now()->toIso8601String(),
        ], 200);
    }

    public function checkProductStock(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'branch_id' => 'required|integer|exists:branches,id',
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => Helpers::error_processor($validator)
            ], 403);
        }

        // Update daily stock
        Helpers::update_daily_product_stock();

        $product = $this->product->find($request->product_id);
        if (!$product) {
            return response()->json([
                'success' => false,
                'available' => false,
                'message' => translate('Product not found'),
            ], 404);
        }

        $branch_product = $this->product_by_branch->where([
            'product_id' => $request->product_id,
            'branch_id' => $request->branch_id
        ])->first();

        if (!$branch_product) {
            return response()->json([
                'success' => false,
                'available' => false,
                'message' => translate('Product not available in selected branch'),
            ], 404);
        }

        $response = [
            'success' => true,
            'product_id' => $product->id,
            'product_name' => $product->name ?? 'Unknown Product',
            'branch_id' => $request->branch_id,
            'is_available' => ($product->status == 1 && $branch_product->is_available == 1),
            'stock_type' => $branch_product->stock_type,
        ];

        if (in_array($branch_product->stock_type, ['daily', 'fixed'])) {
            $available_stock = $branch_product->stock - ($branch_product->sold_quantity ?? 0);
            $response['available_stock'] = $available_stock;
            $response['requested_quantity'] = $request->quantity;
            $response['sufficient_stock'] = $available_stock >= $request->quantity;
            $response['can_fulfill'] = $response['is_available'] && $response['sufficient_stock'];

            if ($available_stock <= 0) {
                $response['message'] = translate('Product :name is out of stock', ['name' => $product->name ?? 'Unknown Product']);
            } elseif ($available_stock < $request->quantity) {
                $response['message'] = translate('Insufficient stock for product :name. Only :count items available. Please order less.', [
                    'name' => $product->name ?? 'Unknown Product',
                    'count' => $available_stock
                ]);
            } else {
                $response['message'] = translate('Product :name is available.', ['name' => $product->name ?? 'Unknown Product']);
                if ($available_stock < ($request->quantity * 1.5)) {
                    $response['warning'] = translate('Low stock warning for product :name. Only :count items left.', [
                        'name' => $product->name ?? 'Unknown Product',
                        'count' => $available_stock
                    ]);
                }
            }
        } else {
            $response['available_stock'] = 'unlimited';
            $response['sufficient_stock'] = true;
            $response['can_fulfill'] = $response['is_available'];
            $response['message'] = translate('Product :name is available.', ['name' => $product->name ?? 'Unknown Product']);
        }

        return response()->json($response, $response['can_fulfill'] ? 200 : 403);
    }

    private function validateCartInternal(int $branchId, array $cart): array
    {
        $invalidItems = [];
        foreach ($cart as $item) {
            $product = $this->product->find($item['product_id']);
            if (!$product || $product->status != 1) {
                $invalidItems[] = [
                    'product_id' => $item['product_id'],
                    'error' => translate('Product not available'),
                    'product_name' => $product ? ($product->name ?? 'Unknown Product') : 'Unknown Product'
                ];
                continue;
            }

            $branch_product = $this->product_by_branch
                ->where('product_id', $item['product_id'])
                ->where('branch_id', $branchId)
                ->first();

            if (!$branch_product || $branch_product->is_available != 1) {
                $invalidItems[] = [
                    'product_id' => $item['product_id'],
                    'error' => translate('Product not available in branch'),
                    'product_name' => $product->name ?? 'Unknown Product'
                ];
                continue;
            }

            if (in_array($branch_product->stock_type, ['daily', 'fixed'])) {
                $available_stock = $branch_product->stock - ($branch_product->sold_quantity ?? 0);
                if ($available_stock <= 0) {
                    $invalidItems[] = [
                        'product_id' => $item['product_id'],
                        'error' => translate('Product :name is out of stock', [
                            'name' => $product->name ?? 'Unknown Product'
                        ]),
                        'available' => 0,
                        'requested' => $item['quantity'],
                        'product_name' => $product->name ?? 'Unknown Product'
                    ];
                } elseif ($available_stock < $item['quantity']) {
                    $invalidItems[] = [
                        'product_id' => $item['product_id'],
                        'error' => translate('Insufficient stock for product :name. Only :count items available. Please order less.', [
                            'name' => $product->name ?? 'Unknown Product',
                            'count' => $available_stock
                        ]),
                        'available' => max(0, $available_stock),
                        'requested' => $item['quantity'],
                        'product_name' => $product->name ?? 'Unknown Product'
                    ];
                }
            }
        }

        return [
            'is_valid' => empty($invalidItems),
            'invalid_items' => $invalidItems
        ];
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
            $customerName = auth('api')->user()?->name;
        } else {
            $guest = GuestUser::find($request['guest_id']);
            $fcmToken = $guest ? $guest->fcm_token : '';
            $local = 'en';
            $customerName = 'Guest User';
        }

        $message = Helpers::order_status_update_message($or['order_status']);

        if ($local != 'en') {
            $statusKey = Helpers::order_status_message_key($or['order_status']);
            $translatedMessage = \App\Model\BusinessSetting::with('translations')->where(['key' => $statusKey])->first();
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
                
                Log::info('Push notification sent to customer', [
                    'order_id' => $order_id,
                    'customer_name' => $customerName,
                    'fcm_token_exists' => !empty($fcmToken)
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send push notification to customer', [
                'order_id' => $order_id,
                'error' => $e->getMessage()
            ]);
        }

        try {
            $emailServices = Helpers::get_business_settings('mail_config');
            $orderMailStatus = Helpers::get_business_settings('place_order_mail_status_user');
            if (isset($emailServices['status']) && $emailServices['status'] == 1 && $orderMailStatus == 1 && (bool)auth('api')->user()) {
                Mail::to(auth('api')->user()->email)->send(new \App\Mail\OrderPlaced($order_id));
                
                Log::info('Order email sent', [
                    'order_id' => $order_id,
                    'email' => auth('api')->user()->email
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Failed to send order email', [
                'order_id' => $order_id,
                'error' => $e->getMessage()
            ]);
        }

        // Notify kitchen if order is confirmed
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
                
                Log::info('Kitchen notification sent', [
                    'order_id' => $order_id,
                    'branch_id' => $or['branch_id']
                ]);
            } catch (\Exception $e) {
                Log::error('Failed to send kitchen notification', [
                    'order_id' => $order_id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        // Notify admin and branch
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
            
            Log::info('Admin and branch notifications sent', [
                'order_id' => $order_id,
                'branch_id' => $or['branch_id']
            ]);
        } catch (\Exception $exception) {
            Log::error('Failed to send admin/branch notifications', [
                'order_id' => $order_id,
                'error' => $exception->getMessage()
            ]);
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
        $userId   = auth('api')->user() ? auth('api')->user()->id : $request['guest_id'];
        $userType = auth('api')->user() ? 0 : 1;

        $orderFilter = $request->input('order_filter');
        $orderFilter = $orderFilter ? trim(strtolower($orderFilter)) : null;

        $limit  = $request->filled('limit')  ? (int)$request->limit  : 10;
        $offset = $request->filled('offset') ? (int)$request->offset : 0;

        $ordersQuery = $this->order
            ->with(['customer', 'delivery_man.rating'])
            ->withCount('details')
            ->withCount([
                'details as total_quantity' => fn($q) => $q->select(DB::raw('sum(quantity)'))
            ])
            ->where(['user_id' => $userId, 'is_guest' => $userType])
            ->when($orderFilter === 'in_prepare', fn($q) => 
                $q->whereIn('order_status', [
                    'pending', 'confirmed', 'preparing', 'picked_up', 'on_the_way'
                ])
            )
            ->when(
                $orderFilter && $orderFilter !== 'in_prepare',
                fn($q) => $q->where('order_status', $orderFilter)
            )
            ->when($request->filled('search'), fn($q) => 
                $q->where(function ($sq) use ($request, $userType) {
                    $key = "%{$request->search}%";
                    $sq->where('id', 'like', $key)
                    ->orWhere('order_status', 'like', $key);
                    if ($userType == 0) {
                        $sq->orWhereHas('customer', fn($c) => 
                            $c->where('name', 'like', $key)
                            ->orWhere('phone', 'like', $key)
                        );
                    }
                })
            )
            ->orderBy('id', 'DESC');

        $total = $ordersQuery->count();
        $ordersList = $ordersQuery->offset($offset)->limit($limit)->get();

        $ordersList->transform(function ($data) {
            $order_id = $data->id;

            $data['deliveryman_review_count'] = DMReview::where([
                'delivery_man_id' => $data->delivery_man_id,
                'order_id'        => $data->id
            ])->count();

            $firstDetail      = $this->order_detail->where('order_id', $order_id)->first();
            $product_id       = $firstDetail?->product_id ?? null;
            $data['is_product_available'] = $product_id ? ($this->product->find($product_id) ? 1 : 0) : 0;

            $data['details_count'] = (int)$data->details_count;

            $productImages = $this->order_detail->where('order_id', $order_id)
                ->pluck('product_id')
                ->filter()
                ->map(fn($pid) => ($p = $this->product->find($pid)) ? $p->image : null)
                ->filter()
                ->values();

            $data['product_images'] = $productImages->toArray();

            $data['variants'] = $this->order_detail->where('order_id', $order_id)
                ->get(['variant', 'variation'])
                ->map(function ($detail) {
                    return [
                        'variant'   => $detail->variant ? json_decode($detail->variant, true) : [],
                        'variation' => $detail->variation ? json_decode($detail->variation, true) : [],
                    ];
                })->toArray();

            return $data;
        });

        return response()->json([
            'total_size' => $total,
            'limit'      => $limit,
            'offset'     => $offset,
            'orders'     => $ordersList->values()->toArray(),
        ], 200);
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
                $query->select(
                    'id',
                    'name',
                    'phone',
                    'email',
                    'image',
                    'branch_id',
                    'is_active'
                );
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
                    'name' => $request->user()->name,
                ]
            ]));

            return response()->json($response->getData(), 200);

        } catch (Exception $e) {
            return response()->json(['errors' => [['code' => 'payment_error', 'message' => 'Failed to process payment']]], 500);
        }
    }
}