<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrderCreateRequest;
use App\Http\Requests\OrderLockRequest;
use App\Http\Requests\OrderUpdateRequest;
use App\Src\Entities\Client;
use App\Src\Entities\Deposit;
use App\Src\Entities\Order;
use App\Src\Entities\Pay;
use App\Src\Entities\Product;
use App\Src\Exceptions\ValidateException;
use App\Src\UseCases\OrderService;
use App\View\Components\Flash;
use DomainException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\View\View;

class OrderController extends Controller
{
    private OrderService $service;

    public function __construct(OrderService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request): View
    {
        $orderQuery = Order::sortable()->orderBy('updated_at', 'DESC')->orderBy('ordered_at', 'DESC');

        if (isset($request->get('filter')['client'])) {
            $client = Client::where(['name' => $request->get('filter')['client']])->firstOrFail();
            $orderQuery->filterByClient($client);
        }

        if (isset($request->get('filter')['status'])) {
            $orderQuery->filterByStatus($request->get('filter')['status']);
        }

        $clone = clone $orderQuery;
        $orders = $clone->paginate(10);

        $clients = Client::all();

        $productPriceSum = intval(Product::filterByOrder($orderQuery->onlyOpened()->pluck('id'))->sum('price'));
        $productTotalPriceSum = intval(Product::filterByOrder($orderQuery->onlyOpened()->pluck('id'))->sum('total_price'));
        $payTotalSum = intval(Pay::filterByOrder($orderQuery->pluck('id'))->sum('amount'));

        $deposit = Deposit::first();

        return view('admin.orders.index', [
            'clients' => $clients,
            'orders' => $orders,
            'productPriceSum' => $productPriceSum,
            'productTotalPriceSum' => $productTotalPriceSum,
            'payTotalSum' => $payTotalSum,
            'deposit' => $deposit
        ]);
    }

    public function addForm(): View
    {
        $clients = Client::all();
        return view('admin.orders.add', ['clients' => $clients]);
    }

    public function add(OrderCreateRequest $request): RedirectResponse
    {
        try {
            $this->service->create($request);
        } catch (ValidateException $e) {
            return back()->withInput()->withErrors($e->getErrors());
        }
        Session::flash(Flash::SUCCESS, __('Заказ создан успешно'));
        return redirect()->route('admin.orders');
    }

    public function editForm(Order $order): View
    {
        $clients = Client::all();
        return view('admin.orders.edit', ['order' => $order, 'clients' => $clients]);
    }

    public function edit(OrderUpdateRequest $request): RedirectResponse
    {
        try {
            $this->service->update($request);
        } catch (ValidateException $e) {
            return back()->withInput()->withErrors($e->getErrors());
        }
        Session::flash(Flash::SUCCESS, __('Изменения сохранены успешно'));
        return redirect()->route('admin.orders');
    }

    public function delete(Request $request): RedirectResponse
    {
        try {
            $this->service->delete($request->get('id'));
        } catch (DomainException $e) {
            Session::flash(Flash::DANGER, __($e->getMessage()));
            return redirect()->route('admin.orders');
        }
        Session::flash(Flash::SUCCESS, __('Заказ удален успешно'));
        return redirect()->route('admin.orders');
    }

    public function lock(OrderLockRequest $request)
    {
        if ($order = Order::find($request->get('order_id'))) {
            if ($request->locked) {
                $order->lock();
            } else {
                $order->unlock();
            }
            $order->save();

            return Response::json(['status' => 'success']);
        }
        return Response::json(['status' => 'fail']);
    }
}
