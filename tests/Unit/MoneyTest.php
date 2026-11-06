<?php

use App\Modules\Core\Support\Money;
use App\Modules\Core\Support\Vat;

it('formats integer pence as pounds', function () {
    expect(Money::format(123456))->toBe('£1,234.56')
        ->and(Money::format(0))->toBe('£0.00')
        ->and(Money::format(5))->toBe('£0.05')
        ->and(Money::format(100000000))->toBe('£1,000,000.00');
});

it('formats negative amounts as credits', function () {
    expect(Money::format(-500))->toBe('-£5.00')
        ->and(Money::format(-123456))->toBe('-£1,234.56');
});

it('parses user supplied amounts into pence', function () {
    expect(Money::parse('£1,234.56'))->toBe(123456)
        ->and(Money::parse('1299.99'))->toBe(129999)
        ->and(Money::parse('12'))->toBe(1200)
        ->and(Money::parse(''))->toBe(0)
        ->and(Money::parse(null))->toBe(0);
});

it('rejects unparseable amounts', function () {
    Money::parse('not money');
})->throws(InvalidArgumentException::class);

it('renders plain decimals for exports', function () {
    expect(Money::toDecimal(123456))->toBe('1234.56')
        ->and(Money::toDecimal(-5))->toBe('-0.05');
});

it('calculates uk vat at twenty percent', function () {
    expect(Vat::rateBps())->toBe(2000)
        ->and(Vat::onNet(10000))->toBe(2000)
        ->and(Vat::grossFromNet(10000))->toBe(12000)
        ->and(Vat::breakdown(5000))->toBe(['net' => 5000, 'vat' => 1000, 'gross' => 6000]);
});

it('rounds vat to the nearest penny', function () {
    // £99.99 net → VAT £20.00 (1999.8p rounded), gross £119.99
    expect(Vat::onNet(9999))->toBe(2000)
        ->and(Vat::grossFromNet(9999))->toBe(11999);
});

it('extracts net and vat from a gross total', function () {
    expect(Vat::netFromGross(11999))->toBe(9999)
        ->and(Vat::fromGross(11999))->toBe(2000)
        ->and(Vat::fromGross(12000))->toBe(2000);
});

it('allocates an amount without losing a penny', function () {
    $parts = Money::allocate(100, [1, 1, 1]);

    expect(array_sum($parts))->toBe(100)
        ->and($parts)->toBe([34, 33, 33]);
});
