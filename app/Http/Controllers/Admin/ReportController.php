<?php

namespace App\Http\Controllers\Admin;

use App\CentralLogics\Helpers;
use App\Http\Controllers\Controller;
use App\Model\Order;
use App\Model\OrderDetail;
use App\Model\Branch;
use Brian2694\Toastr\Facades\Toastr;
use Carbon\Carbon;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Contracts\Support\Renderable;
use Barryvdh\DomPDF\Facade\Pdf as PDF;

class ReportController extends Controller
{
    public function __construct(
        private Order       $order,
        private OrderDetail $orderDetail,
        private Branch      $branch
    )
    {
    }

    /**
     * @return Renderable
     */
    public function orderIndex(): Renderable
    {
        if (session()->has('from_date') == false) {
            session()->put('from_date', date('Y-m-01'));
            session()->put('to_date', date('Y-m-30'));
        }

        $branches = $this->branch->all();
        return view('admin-views.report.order-index', compact('branches'));
    }

    /**
     * @param Request $request
     * @return Renderable
     */
    public function earningIndex(Request $request): Renderable
    {
        $from = Carbon::parse($request->from)->startOfDay();
        $to = Carbon::parse($request->to)->endOfDay();

        if ($request->from > $request->to) {
            Toastr::warning(translate('Invalid date range!'));
        }

        $startDate = $request->from;
        $endDate = $request->to;

        $orders = $this->order->where(['order_status' => 'delivered'])
            ->when($request->from && $request->to, function ($q) use ($from, $to) {
                session()->put('from_date', $from);
                session()->put('to_date', $to);
                $q->whereBetween('created_at', [$from, $to]);
            })->get();

        $addonTaxAmount = 0;

        foreach ($orders as $order) {
            foreach ($order->details as $detail) {
                $addonTaxAmount += $detail->add_on_tax_amount;
            }
        }

        $productTax = $orders->sum('total_tax_amount');
        $total_tax = $productTax + $addonTaxAmount;
        $total_sold = $orders->sum('order_amount');

        if ($startDate == null) {
            session()->put('from_date', date('Y-m-01'));
            session()->put('to_date', date('Y-m-30'));
        }

        return view('admin-views.report.earning-index', compact('total_tax', 'total_sold', 'from', 'to', 'startDate', 'endDate'));
    }

    /**
     * Branch-wise Reports Index
     * @return Renderable
     */
    public function branchReportsIndex(): Renderable
    {
        $branches = $this->branch->all();
        
        if (session()->has('branch_from_date') == false) {
            session()->put('branch_from_date', date('Y-m-01'));
            session()->put('branch_to_date', date('Y-m-30'));
        }

        return view('admin-views.report.branch-reports-index', compact('branches'));
    }

    /**
     * Branch-wise Order Report
     * @param Request $request
     * @return JsonResponse
     */
    public function branchOrderReport(Request $request): JsonResponse
    {
        $fromDate = Carbon::parse($request->from)->startOfDay();
        $toDate = Carbon::parse($request->to)->endOfDay();
        $branchId = $request->branch_id;

        $query = $this->order->whereBetween('created_at', [$fromDate, $toDate]);
        
        if ($branchId != 'all') {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->get();

        // Calculate statistics
        $totalOrders = $orders->count();
        $delivered = $orders->where('order_status', 'delivered')->count();
        $canceled = $orders->where('order_status', 'canceled')->count();
        $returned = $orders->where('order_status', 'returned')->count();
        $failed = $orders->where('order_status', 'failed')->count();
        $pending = $orders->where('order_status', 'pending')->count();
        
        $totalAmount = $orders->sum('order_amount');
        $deliveredAmount = $orders->where('order_status', 'delivered')->sum('order_amount');

        $data = [
            'orders' => $orders,
            'stats' => [
                'total' => $totalOrders,
                'delivered' => $delivered,
                'canceled' => $canceled,
                'returned' => $returned,
                'failed' => $failed,
                'pending' => $pending,
                'total_amount' => $totalAmount,
                'delivered_amount' => $deliveredAmount,
            ]
        ];

        session()->put('branch_report_data', $data);

        return response()->json([
            'view' => view('admin-views.report.partials._branch-order-table', $data)->render(),
            'stats' => $data['stats']
        ]);
    }

    /**
     * Branch-wise Sales Report
     * @param Request $request
     * @return JsonResponse
     */
    public function branchSalesReport(Request $request): JsonResponse
    {
        $fromDate = Carbon::parse($request->from)->startOfDay();
        $toDate = Carbon::parse($request->to)->endOfDay();
        $branchId = $request->branch_id;

        $query = $this->order->whereBetween('created_at', [$fromDate, $toDate])
            ->where('order_status', 'delivered');
        
        if ($branchId != 'all') {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->pluck('id')->toArray();

        $data = [];
        $totalSold = 0;
        $totalQuantity = 0;

        foreach ($this->orderDetail->whereIn('order_id', $orders)->latest()->get() as $detail) {
            $price = $detail['price'] - $detail['discount_on_product'];
            $orderTotal = $price * $detail['quantity'];
            $data[] = [
                'order_id' => $detail['order_id'],
                'date' => $detail['created_at'],
                'price' => $orderTotal,
                'quantity' => $detail['quantity'],
                'product_details' => $detail->product_details,
            ];
            $totalSold += $orderTotal;
            $totalQuantity += $detail['quantity'];
        }

        session()->put('branch_sales_data', $data);

        return response()->json([
            'order_count' => count($data),
            'item_qty' => $totalQuantity,
            'order_sum' => Helpers::set_symbol($totalSold),
            'view' => view('admin-views.report.partials._branch-sales-table', compact('data'))->render(),
        ]);
    }

    /**
     * Branch-wise Product Report
     * @param Request $request
     * @return JsonResponse
     */
    public function branchProductReport(Request $request): JsonResponse
    {
        $fromDate = Carbon::parse($request->from)->startOfDay();
        $toDate = Carbon::parse($request->to)->endOfDay();
        $branchId = $request->branch_id;

        $query = $this->order->whereBetween('created_at', [$fromDate, $toDate]);
        
        if ($branchId != 'all') {
            $query->where('branch_id', $branchId);
        }

        $orders = $query->latest()->get();

        $data = [];
        $totalSold = 0;
        $totalQuantity = 0;

        foreach ($orders as $order) {
            foreach ($order->details as $detail) {
                if ($request->product_id != 'all' && $detail['product_id'] != $request->product_id) {
                    continue;
                }

                $price = Helpers::variation_price(json_decode($detail->product_details, true), $detail['variations']) - $detail['discount_on_product'];
                $orderTotal = $price * $detail['quantity'];
                $data[] = [
                    'order_id' => $order['id'],
                    'date' => $order['created_at'],
                    'customer' => $order->customer,
                    'branch' => $order->branch,
                    'price' => $orderTotal,
                    'quantity' => $detail['quantity'],
                    'product_name' => json_decode($detail->product_details, true)['name'] ?? 'N/A',
                ];
                $totalSold += $orderTotal;
                $totalQuantity += $detail['quantity'];
            }
        }

        session()->put('branch_product_data', $data);

        return response()->json([
            'order_count' => count($data),
            'item_qty' => $totalQuantity,
            'order_sum' => Helpers::set_symbol($totalSold),
            'view' => view('admin-views.report.partials._branch-product-table', compact('data'))->render(),
        ]);
    }

    /**
     * Print Branch Order Report
     * @param Request $request
     * @return mixed
     */
    public function printBranchOrderReport(Request $request): mixed
    {
        $data = session('branch_report_data', []);
        $branch = $request->branch_id != 'all' ? $this->branch->find($request->branch_id) : null;
        $reportType = 'Order Report';
        $dateRange = $request->from . ' to ' . $request->to;

        $pdf = PDF::loadView('admin-views.report.pdf.branch-order-report', compact('data', 'branch', 'reportType', 'dateRange'));
        
        // Set paper size and orientation
        $pdf->setPaper('A4', 'landscape');
        
        // Enable Arabic support
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ]);
        
        return $pdf->download('branch_order_report_' . time() . '.pdf');
    }

    /**
     * Print Branch Sales Report
     * @return mixed
     */
    public function printBranchSalesReport(Request $request): mixed
    {
        $data = session('branch_sales_data', []);
        $branch = $request->branch_id != 'all' ? $this->branch->find($request->branch_id) : null;
        $reportType = 'Sales Report';
        $dateRange = $request->from . ' to ' . $request->to;

        $pdf = PDF::loadView('admin-views.report.pdf.branch-sales-report', compact('data', 'branch', 'reportType', 'dateRange'));
        
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ]);
        
        return $pdf->download('branch_sales_report_' . time() . '.pdf');
    }

    /**
     * Print Branch Product Report
     * @return mixed
     */
    public function printBranchProductReport(Request $request): mixed
    {
        $data = session('branch_product_data', []);
        $branch = $request->branch_id != 'all' ? $this->branch->find($request->branch_id) : null;
        $reportType = 'Product Report';
        $dateRange = $request->from . ' to ' . $request->to;

        $pdf = PDF::loadView('admin-views.report.pdf.branch-product-report', compact('data', 'branch', 'reportType', 'dateRange'));
        
        $pdf->setPaper('A4', 'landscape');
        $pdf->setOptions([
            'isHtml5ParserEnabled' => true,
            'isRemoteEnabled' => true,
            'defaultFont' => 'DejaVu Sans',
        ]);
        
        return $pdf->download('branch_product_report_' . time() . '.pdf');
}

    /**
     * @param Request $request
     * @return RedirectResponse
     */
    public function setDate(Request $request): RedirectResponse
    {
        $fromDate = Carbon::parse($request['from'])->startOfDay();
        $toDate = Carbon::parse($request['to'])->endOfDay();

        session()->put('from_date', $fromDate);
        session()->put('to_date', $toDate);

        return back();
    }

    /**
     * @return Renderable
     */
    public function deliverymanReport(): Renderable
    {
        $orders = $this->order->with(['customer', 'branch'])->paginate(25);
        return view('admin-views.report.driver-index', compact('orders'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function deliverymanFilter(Request $request): JsonResponse
    {
        $fromDate = Carbon::parse($request->formDate)->startOfDay();
        $toDate = Carbon::parse($request->toDate)->endOfDay();

        $orders = $this->order
            ->where(['delivery_man_id' => $request['delivery_man']])
            ->where(['order_status' => 'delivered'])
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->get();

        return response()->json([
            'view' => view('admin-views.order.partials._table', compact('orders'))->render(),
            'delivered_qty' => $orders->count()
        ]);
    }

    /**
     * @return Renderable
     */
    public function productReport(): Renderable
    {
        $branches = $this->branch->all();
        return view('admin-views.report.product-report', compact('branches'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function productReportFilter(Request $request): JsonResponse
    {
        $fromDate = Carbon::parse($request->from)->startOfDay();
        $toDate = Carbon::parse($request->to)->endOfDay();

        $orders = $this->order->when($request['branch_id'] != 'all', function ($query) use ($request) {
            $query->where('branch_id', $request['branch_id']);
        })
            ->whereBetween('created_at', [$fromDate, $toDate])
            ->latest()
            ->get();

        $data = [];
        $totalSold = 0;
        $totalQuantity = 0;
        foreach ($orders as $order) {
            foreach ($order->details as $detail) {
                if ($request['product_id'] != 'all') {
                    if ($detail['product_id'] == $request['product_id']) {
                        $price = Helpers::variation_price(json_decode($detail->product_details, true), $detail['variations']) - $detail['discount_on_product'];
                        $orderTotal = $price * $detail['quantity'];
                        $data[] = [
                            'order_id' => $order['id'],
                            'date' => $order['created_at'],
                            'customer' => $order->customer,
                            'price' => $orderTotal,
                            'quantity' => $detail['quantity'],
                        ];
                        $totalSold += $orderTotal;
                        $totalQuantity += $detail['quantity'];
                    }

                } else {
                    $price = Helpers::variation_price(json_decode($detail->product_details, true), $detail['variations']) - $detail['discount_on_product'];
                    $orderTotal = $price * $detail['quantity'];
                    $data[] = [
                        'order_id' => $order['id'],
                        'date' => $order['created_at'],
                        'customer' => $order->customer,
                        'price' => $orderTotal,
                        'quantity' => $detail['quantity'],
                    ];
                    $totalSold += $orderTotal;
                    $totalQuantity += $detail['quantity'];
                }
            }
        }

        session()->put('export_data', $data);

        return response()->json([
            'order_count' => count($data),
            'item_qty' => $totalQuantity,
            'order_sum' => Helpers::set_symbol($totalSold),
            'view' => view('admin-views.report.partials._table', compact('data'))->render(),
        ]);
    }

    /**
     * @return mixed
     */
    public function exportProductReport(): mixed
    {
        if (session()->has('export_data')) {
            $data = session('export_data');

        } else {
            $orders = $this->order->all();
            $data = [];
            $totalSold = 0;
            $totalQuantity = 0;
            foreach ($orders as $order) {
                foreach ($order->details as $detail) {
                    $price = Helpers::variation_price(json_decode($detail->product_details, true), $detail['variations']) - $detail['discount_on_product'];
                    $orderTotal = $price * $detail['quantity'];
                    $data[] = [
                        'order_id' => $order['id'],
                        'date' => $order['created_at'],
                        'customer' => $order->customer,
                        'price' => $orderTotal,
                        'quantity' => $detail['quantity'],
                    ];
                    $totalSold += $orderTotal;
                    $totalQuantity += $detail['quantity'];
                }
            }
        }

        $pdf = PDF::loadView('admin-views.report.partials._report', compact('data'));
        return $pdf->download('report_' . rand(00001, 99999) . '.pdf');
    }

    /**
     * @return Application|Factory|View
     */
    public function saleReport(): Factory|View|Application
    {
        $branches = $this->branch->all();
        return view('admin-views.report.sale-report', compact('branches'));
    }

    /**
     * @param Request $request
     * @return JsonResponse
     */
    public function saleFilter(Request $request): JsonResponse
    {
        $fromDate = Carbon::parse($request->from)->startOfDay();
        $toDate = Carbon::parse($request->to)->endOfDay();

        if ($request['branch_id'] == 'all') {
            $orders = $this->order->whereBetween('created_at', [$fromDate, $toDate])->pluck('id')->toArray();

        } else {
            $orders = $this->order
                ->where(['branch_id' => $request['branch_id']])
                ->whereBetween('created_at', [$fromDate, $toDate])
                ->pluck('id')
                ->toArray();
        }

        $data = [];
        $totalSold = 0;
        $totalQuantity = 0;

        foreach ($this->orderDetail->whereIn('order_id', $orders)->latest()->get() as $detail) {
            $price = $detail['price'] - $detail['discount_on_product'];
            $orderTotal = $price * $detail['quantity'];
            $data[] = [
                'order_id' => $detail['order_id'],
                'date' => $detail['created_at'],
                'price' => $orderTotal,
                'quantity' => $detail['quantity'],
            ];
            $totalSold += $orderTotal;
            $totalQuantity += $detail['quantity'];
        }

        return response()->json([
            'order_count' => count($data),
            'item_qty' => $totalQuantity,
            'order_sum' => Helpers::set_symbol($totalSold),
            'view' => view('admin-views.report.partials._table', compact('data'))->render(),
        ]);
    }

    /**
     * @return mixed
     */
    public function exportSaleReport(): mixed
    {
        $data = session('export_sale_data');
        $pdf = PDF::loadView('admin-views.report.partials._report', compact('data'));

        return $pdf->download('sale_report_' . rand(00001, 99999) . '.pdf');
    }
}