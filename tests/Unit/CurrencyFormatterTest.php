<?php

declare(strict_types=1);

use App\Enums\Currency;
use App\Services\CurrencyFormatter;

beforeEach(function (): void {
    $this->formatter = new CurrencyFormatter;
});

describe('CurrencyFormatter', function (): void {
    describe('format', function (): void {
        it('formats GHS amounts with symbol', function (): void {
            expect($this->formatter->format(100, Currency::GHS))->toBe('₵100.00');
            expect($this->formatter->format(1234.56, Currency::GHS))->toBe('₵1,234.56');
            expect($this->formatter->format(0, Currency::GHS))->toBe('₵0.00');
        });

        it('formats USD amounts with symbol', function (): void {
            expect($this->formatter->format(100, Currency::USD))->toBe('$100.00');
            expect($this->formatter->format(1234.56, Currency::USD))->toBe('$1,234.56');
            expect($this->formatter->format(0, Currency::USD))->toBe('$0.00');
        });

        it('accepts string currency codes', function (): void {
            expect($this->formatter->format(100, 'GHS'))->toBe('₵100.00');
            expect($this->formatter->format(100, 'USD'))->toBe('$100.00');
            expect($this->formatter->format(100, 'ghs'))->toBe('₵100.00');
        });

        it('formats integer amounts', function (): void {
            expect($this->formatter->format(100, Currency::GHS))->toBe('₵100.00');
        });

        it('formats string amounts', function (): void {
            expect($this->formatter->format('100.50', Currency::GHS))->toBe('₵100.50');
            expect($this->formatter->format('1,234.56', Currency::GHS))->toBe('₵1,234.56');
        });

        it('handles large amounts', function (): void {
            expect($this->formatter->format(1000000, Currency::GHS))->toBe('₵1,000,000.00');
            expect($this->formatter->format(1234567.89, Currency::USD))->toBe('$1,234,567.89');
        });
    });

    describe('formatWithCode', function (): void {
        it('formats amounts with currency code', function (): void {
            expect($this->formatter->formatWithCode(100, Currency::GHS))->toBe('GHS 100.00');
            expect($this->formatter->formatWithCode(100, Currency::USD))->toBe('USD 100.00');
        });

        it('includes thousand separators', function (): void {
            expect($this->formatter->formatWithCode(1234.56, Currency::GHS))->toBe('GHS 1,234.56');
        });
    });

    describe('formatFull', function (): void {
        it('formats amounts with symbol and code', function (): void {
            expect($this->formatter->formatFull(100, Currency::GHS))->toBe('₵100.00 GHS');
            expect($this->formatter->formatFull(100, Currency::USD))->toBe('$100.00 USD');
        });
    });

    describe('toSubunits', function (): void {
        it('converts GHS to pesewas', function (): void {
            expect($this->formatter->toSubunits(1, Currency::GHS))->toBe(100);
            expect($this->formatter->toSubunits(10.50, Currency::GHS))->toBe(1050);
            expect($this->formatter->toSubunits(0.01, Currency::GHS))->toBe(1);
        });

        it('converts USD to cents', function (): void {
            expect($this->formatter->toSubunits(1, Currency::USD))->toBe(100);
            expect($this->formatter->toSubunits(10.50, Currency::USD))->toBe(1050);
            expect($this->formatter->toSubunits(0.01, Currency::USD))->toBe(1);
        });

        it('rounds correctly', function (): void {
            expect($this->formatter->toSubunits(10.555, Currency::GHS))->toBe(1056);
            expect($this->formatter->toSubunits(10.554, Currency::GHS))->toBe(1055);
        });

        it('handles integer input', function (): void {
            expect($this->formatter->toSubunits(100, Currency::GHS))->toBe(10000);
        });
    });

    describe('fromSubunits', function (): void {
        it('converts pesewas to GHS', function (): void {
            expect($this->formatter->fromSubunits(100, Currency::GHS))->toBe(1.0);
            expect($this->formatter->fromSubunits(1050, Currency::GHS))->toBe(10.5);
            expect($this->formatter->fromSubunits(1, Currency::GHS))->toBe(0.01);
        });

        it('converts cents to USD', function (): void {
            expect($this->formatter->fromSubunits(100, Currency::USD))->toBe(1.0);
            expect($this->formatter->fromSubunits(1050, Currency::USD))->toBe(10.5);
            expect($this->formatter->fromSubunits(1, Currency::USD))->toBe(0.01);
        });

        it('handles zero', function (): void {
            expect($this->formatter->fromSubunits(0, Currency::GHS))->toBe(0.0);
        });
    });

    describe('parse', function (): void {
        it('parses formatted GHS amounts', function (): void {
            expect($this->formatter->parse('₵100.00'))->toBe(100.0);
            expect($this->formatter->parse('₵1,234.56'))->toBe(1234.56);
            expect($this->formatter->parse('GHS 100.00'))->toBe(100.0);
        });

        it('parses formatted USD amounts', function (): void {
            expect($this->formatter->parse('$100.00'))->toBe(100.0);
            expect($this->formatter->parse('$1,234.56'))->toBe(1234.56);
            expect($this->formatter->parse('USD 100.00'))->toBe(100.0);
        });

        it('parses plain numbers', function (): void {
            expect($this->formatter->parse('100.50'))->toBe(100.5);
            expect($this->formatter->parse('1,234.56'))->toBe(1234.56);
        });
    });

    describe('symbol', function (): void {
        it('returns currency symbol', function (): void {
            expect($this->formatter->symbol(Currency::GHS))->toBe('₵');
            expect($this->formatter->symbol(Currency::USD))->toBe('$');
            expect($this->formatter->symbol('GHS'))->toBe('₵');
            expect($this->formatter->symbol('USD'))->toBe('$');
        });
    });
});
