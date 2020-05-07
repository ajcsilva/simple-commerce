<?php

namespace DoubleThreeDigital\SimpleCommerce\Gateways;

use Statamic\View\View;

class DummyGateway implements Gateway
{
    public function completePurchase($data)
    {
        $isPaid = true;

        if ($data['cardNumber'] === '1111 1111 1111 1111') {
            throw new \Exception('The card provided is invalid.');
        }

        if ($data['expiryYear'] < now()->format('Y')) {
            $isPaid = false;
        }

        return $isPaid;
    }

    public function rules(): array
    {
        return [
            'cardholder' => 'required|string',
            'cardNumber' => 'required|string',
            'expiryMonth' => 'required|in:01,02,03,04,05,06,07,08,09,10,11,12',
            'expiryYear' => 'required',
            'cvc' => 'required|min:3|max:4',
        ];
    }

    public function paymentForm()
    {
        return (new View())
            ->template('simple-commerce::gateways.dummy')
            ->with([
                'class' => get_class($this),
            ]);
    }

    public function refund(array $gatewayData)
    {
        return true;
    }

    public function name(): string
    {
        return 'Dummy';
    }
}
