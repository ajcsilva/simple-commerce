<?php

use DoubleThreeDigital\SimpleCommerce\Events\PaymentStatusUpdated;
use DoubleThreeDigital\SimpleCommerce\Facades\Order;
use DoubleThreeDigital\SimpleCommerce\Facades\Product;
use DoubleThreeDigital\SimpleCommerce\Orders\PaymentStatus;
use DoubleThreeDigital\SimpleCommerce\Tests\Gateways\Builtin\Helpers\FakeOffsiteGateway;
use DoubleThreeDigital\SimpleCommerce\Tests\Gateways\Builtin\Helpers\FakeOnsiteGateway;
use DoubleThreeDigital\SimpleCommerce\Tests\Helpers\SetupCollections;
use Illuminate\Support\Facades\Event;

uses(SetupCollections::class);

beforeEach(function () {
    $this->setupCollections();
});

test('can mark order as paid with offsite gateway', function () {
    Event::fake();

    $fakeGateway = new FakeOffsiteGateway();

    $product = Product::make()
        ->price(1500)
        ->stock(10)
        ->data([
            'title' => 'Smth',
        ]);

    $product->save();

    $order = Order::make()
        ->lineItems([
            [
                'product' => $product->id(),
                'quantity' => 1,
                'total' => 1500,
            ],
        ]);

    $order->save();

    $markOrderAsPaid = $fakeGateway->markOrderAsPaid($order);

    // Assert order has been marked as paid
    expect($markOrderAsPaid)->toBeTrue();
    expect(PaymentStatus::Paid)->toBe($order->fresh()->paymentStatus());

    Event::assertDispatched(PaymentStatusUpdated::class);

    // Assert stock count has been updated
    expect(9)->toBe($product->fresh()->stock());
});

test('can mark order as paid with offsite gateway and ensure gateway is set in order paid event', function () {
    Event::fake();

    $fakeGateway = new FakeOffsiteGateway();

    $product = Product::make()
        ->price(1500)
        ->stock(10)
        ->data([
            'title' => 'Smth',
        ]);

    $product->save();

    $order = Order::make()
        ->lineItems([
            [
                'product' => $product->id(),
                'quantity' => 1,
                'total' => 1500,
            ],
        ]);

    $order->save();

    $markOrderAsPaid = $fakeGateway->markOrderAsPaid($order);

    // Assert order has been marked as paid
    expect($markOrderAsPaid)->toBeTrue();
    expect(PaymentStatus::Paid)->toBe($order->fresh()->paymentStatus());

    Event::assertDispatched(function (PaymentStatusUpdated $event) {
        return $event->order->gateway['use'] === FakeOffsiteGateway::class;
    });

    // Assert stock count has been updated
    expect(9)->toBe($product->fresh()->stock());
});

test('can mark order as paid with onsite gateway', function () {
    Event::fake();

    $fakeGateway = new FakeOnsiteGateway();

    $product = Product::make()
        ->price(1500)
        ->data([
            'title' => 'Smth',
        ]);

    $product->save();

    $order = Order::make()
        ->lineItems([
            [
                'product' => $product->id(),
                'quantity' => 1,
                'total' => 1500,
            ],
        ]);

    $order->save();

    $markOrderAsPaid = $fakeGateway->markOrderAsPaid($order);

    // Assert order has been marked as paid
    expect($markOrderAsPaid)->toBeTrue();
    expect(PaymentStatus::Paid)->toBe($order->fresh()->paymentStatus());

    Event::assertDispatched(PaymentStatusUpdated::class);
});

test('can mark order as paid with onsite gateway and ensure gateway is set in order paid event', function () {
    Event::fake();

    $fakeGateway = new FakeOnsiteGateway();

    $product = Product::make()
        ->price(1500)
        ->data([
            'title' => 'Smth',
        ]);

    $product->save();

    $order = Order::make()
        ->lineItems([
            [
                'product' => $product->id(),
                'quantity' => 1,
                'total' => 1500,
            ],
        ]);

    $order->save();

    $markOrderAsPaid = $fakeGateway->markOrderAsPaid($order);

    // Assert order has been marked as paid
    expect($markOrderAsPaid)->toBeTrue();
    expect(PaymentStatus::Paid)->toBe($order->fresh()->paymentStatus());

    Event::assertDispatched(function (PaymentStatusUpdated $event) {
        return $event->order->gateway['use'] === FakeOnsiteGateway::class;
    });
});
