<?php

namespace DoubleThreeDigital\SimpleCommerce\Customers;

use DoubleThreeDigital\SimpleCommerce\Contracts\Customer as Contract;
use DoubleThreeDigital\SimpleCommerce\Data\HasData;
use DoubleThreeDigital\SimpleCommerce\Exceptions\OrderNotFound;
use DoubleThreeDigital\SimpleCommerce\Facades\Customer as CustomerFacade;
use DoubleThreeDigital\SimpleCommerce\Facades\Order;
use DoubleThreeDigital\SimpleCommerce\Http\Resources\BaseResource;
use DoubleThreeDigital\SimpleCommerce\SimpleCommerce;
use DoubleThreeDigital\SimpleCommerce\Support\Runway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Collection;
use Statamic\Contracts\Entries\Entry;
use Statamic\Http\Resources\API\EntryResource;
use Statamic\Sites\Site;

class Customer implements Contract
{
    use HasData, Notifiable;

    public $id;

    public $email;

    public $data;

    public $resource;

    public function __construct()
    {
        $this->data = collect();
    }

    public function id($id = null)
    {
        return $this
            ->fluentlyGetOrSet('id')
            ->args(func_get_args());
    }

    public function resource($resource = null)
    {
        return $this
            ->fluentlyGetOrSet('resource')
            ->args(func_get_args());
    }

    public function site(): ?Site
    {
        if ($this->isOrExtendsClass(SimpleCommerce::orderDriver()['repository'], EntryOrderRepository::class)) {
            return $this->resource()->site();
        }

        return null;
    }

    public function name(): ?string
    {
        if ($this->has('first_name') && $this->has('last_name')) {
            return "{$this->get('first_name')} {$this->get('last_name')}";
        }

        return $this->get('name');
    }

    public function email($email = null)
    {
        return $this
            ->fluentlyGetOrSet('email')
            ->args(func_get_args());
    }

    public function orders(): Collection
    {
        if ($this->resource instanceof Model) {
            return $this->resource->orders->map(function ($order) {
                return Order::find($order->id);
            });
        }

        $orders = $this->get('orders', []);

        return collect($orders)->map(function ($orderId) {
            try {
                return Order::find($orderId);
            } catch (OrderNotFound $e) {
                return null;
            }
        })->filter()->values();
    }

    public function routeNotificationForMail($notification = null)
    {
        return $this->email();
    }

    public function beforeSaved()
    {
        return null;
    }

    public function afterSaved()
    {
        return null;
    }

    public function save(): self
    {
        if (method_exists($this, 'beforeSaved')) {
            $this->beforeSaved();
        }

        CustomerFacade::save($this);

        if (method_exists($this, 'afterSaved')) {
            $this->afterSaved();
        }

        return $this;
    }

    public function delete(): void
    {
        CustomerFacade::delete($this);
    }

    public function fresh(): self
    {
        $freshCustomer = CustomerFacade::find($this->id());

        $this->id = $freshCustomer->id;
        $this->email = $freshCustomer->email;
        $this->data = $freshCustomer->data;
        $this->resource = $freshCustomer->resource;

        return $this;
    }

    public function toArray(): array
    {
        $toArray = $this->data->toArray();

        $toArray['id'] = $this->id();

        return $toArray;
    }

    public function toResource()
    {
        if ($this->isOrExtendsClass(SimpleCommerce::customerDriver()['repository'], EntryCustomerRepository::class)) {
            return new EntryResource($this->resource());
        }

        return new BaseResource($this);
    }

    public function toAugmentedArray($keys = null): array
    {
        if ($this->resource() instanceof Entry) {
            $blueprintFields = $this->resource()->blueprint()->fields()->items()->reject(function ($field) {
                return isset($field['import']) || $field['handle'] === 'value';
            })->pluck('handle')->toArray();

            $augmentedData = $this->resource()->toAugmentedArray($blueprintFields);

            return array_merge(
                $this->toArray(),
                $augmentedData,
            );
        }

        if ($this->resource() instanceof Model) {
            return Runway::customerModel()->augment($this->resource());
        }

        return null;
    }

    protected function isOrExtendsClass(string $class, string $classToCheckAgainst): bool
    {
        return is_subclass_of($class, $classToCheckAgainst)
            || $class === $classToCheckAgainst;
    }
}
