<?php

namespace shop\useCases;

use shop\entities\Cart\Cart;
use shop\entities\Order\OrderItem;
use shop\entities\Order\OrderMessage;
use shop\entities\Product\Product;
use shop\forms\backend\Order\EditForm;
use shop\forms\frontend\Order\AddForm;
use shop\repositories\OrderRepository;
use shop\entities\Order\Order;

class OrderService
{
    private OrderRepository $orderRepository;
    private UserService $userService;

    public function __construct(OrderRepository $orderRepository, UserService $userService)
    {
        $this->orderRepository = $orderRepository;
        $this->userService = $userService;
    }

    public function create(AddForm $form, Cart $cart): Order
    {
        $user = $this->userService->create($form->phone);

        $orderItems = array_map(function (Product $product) {
            return OrderItem::create($product->id, $product->name, $product->slug, $product->price);
        }, $cart->productList);

        $order = Order::create(
            $user->id,
            $form->phone,
            $form->name,
            $form->city,
            $form->district,
            $form->address,
            [$form->mapLat, $form->mapLong],
            $orderItems,
            $form->payMethod->id
        );
        $this->orderRepository->save($order);
        return $order;
    }

    public function update(Order $order, EditForm $form)
    {
        $order->changeStatus($form->status);
        if ($form->message) $order->setMessage(OrderMessage::create($order->id, $form->message));
        $this->orderRepository->save($order);
    }
}
Footer
