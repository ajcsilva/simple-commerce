<?php

namespace DoubleThreeDigital\SimpleCommerce\Contracts;

interface OrderRepository
{
    public function all();

    public function find($id): ?Order;

    public function make(): Order;

    public function save(Order $order): void;

    public function delete(Order $order): void;

    public static function bindings(): array;
}
