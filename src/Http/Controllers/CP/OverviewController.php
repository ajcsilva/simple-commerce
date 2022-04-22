<?php

namespace DoubleThreeDigital\SimpleCommerce\Http\Controllers\CP;

use Carbon\CarbonPeriod;
use DoubleThreeDigital\SimpleCommerce\Currency;
use DoubleThreeDigital\SimpleCommerce\Facades\Customer;
use DoubleThreeDigital\SimpleCommerce\Facades\Order;
use DoubleThreeDigital\SimpleCommerce\Facades\Product;
use DoubleThreeDigital\SimpleCommerce\Overview;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Statamic\Facades\Collection;
use Statamic\Facades\Site;

class OverviewController
{
    public function index(Request $request)
    {
        if ($request->wantsJson()) {
            $data = collect($request->get('widgets'))
                ->mapWithKeys(function ($widgetHandle) use ($request) {
                    $methodName = 'get' . Str::studly($widgetHandle); // TODO

                    return [
                        $widgetHandle => $this->$methodName($request),
                    ];
                })
                ->toArray();

            return ['data' => $data];
        }

        return view('simple-commerce::cp.overview', [
            'widgets' => Overview::widgets(),
        ]);
    }

    protected function getOrdersChart($request)
    {
        $timePeriod = CarbonPeriod::create(now()->subDays(30)->format('Y-m-d'), now()->format('Y-m-d'));

        return collect($timePeriod)->map(function ($date) {
            if (isset(SimpleCommerce::orderDriver()['collection'])) {
                $query = Collection::find(SimpleCommerce::orderDriver()['collection'])
                    ->queryEntries()
                    ->where('is_paid', true)
                    ->whereDate('paid_date', $date->format('d-m-Y'))
                    ->get();
            }

            // TODO: implement Eloquent query

            return [
                $query->count(),
                $date->format('d-m-Y'),
            ];

            // return [
            //     'date' => $date->format('d-m-Y'),
            //     'total' => $query->map(fn ($order) => $order->get('grand_total'))->sum(),
            //     'count' => $query->count(),
            // ];
        });
    }

    protected function getRecentOrders($request)
    {
        if (isset(SimpleCommerce::orderDriver()['collection'])) {
            $query = Collection::find(SimpleCommerce::orderDriver()['collection'])
                ->queryEntries()
                ->where('is_paid', true)
                ->orderBy('paid_date', 'desc')
                ->limit(5)
                ->get()
                ->map(function ($entry) {
                    return Order::find($entry->id());
                })
                ->values();

            return $query->map(function ($order) {
                return [
                    'id' => $order->id(),
                    'order_number' => $order->orderNumber(),
                    'edit_url' => $order->resource()->editUrl(),
                    'grand_total' => Currency::parse($order->grandTotal(), Site::selected()),
                    'paid_date' => $order->get('paid_date'),
                ];
            });
        }

        // TODO: implement Eloquent query

        return null;
    }

    protected function getTopCustomers($request)
    {
        if (isset(SimpleCommerce::customerDriver()['collection'])) {
            $query = Collection::find(SimpleCommerce::customerDriver()['collection'])
                ->queryEntries()
                ->get()
                ->sortByDesc(function ($customer) {
                    return count($customer->get('orders', []));
                })
                ->take(5)
                ->map(function ($entry) {
                    return Customer::find($entry->id());
                })
                ->values();

            return $query->map(function ($customer) {
                return [
                    'id' => $customer->id(),
                    'email' => $customer->email(),
                    'edit_url' => $customer->resource()->editUrl(),
                    'orders_count' => count($customer->get('orders', [])),
                ];
            });
        }

        // TODO: implement Eloquent query
        // TODO: implement User query

        return null;
    }

    protected function getLowStockProducts($request)
    {
        if (isset(SimpleCommerce::productDriver()['collection'])) {
            $query = Collection::find(SimpleCommerce::productDriver()['collection'])
                ->queryEntries()
                ->where('stock', '<', config('simple-commerce.low_stock_threshold'))
                ->orderBy('stock', 'asc')
                ->get()
                ->reject(function ($entry) {
                    return $entry->has('product_variants')
                        || ! $entry->has('stock');
                })
                ->take(5)
                ->map(function ($entry) {
                    return Product::find($entry->id());
                })
                ->values();

            return $query->map(function ($product) {
                return [
                    'id' => $product->id(),
                    'title' => $product->get('title'),
                    'stock' => $product->stock(),
                    'edit_url' => $product->resource()->editUrl(),
                ];
            });
        }

        return null;
    }
}
