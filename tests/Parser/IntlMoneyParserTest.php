<?php

declare(strict_types=1);

namespace Tests\Money\Parser;

use Money\Currencies;
use Money\Currencies\ISOCurrencies;
use Money\Currency;
use Money\Exception\ParserException;
use Money\Money;
use Money\Parser\IntlMoneyParser;
use NumberFormatter;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;

final class IntlMoneyParserTest extends TestCase
{
    use ProphecyTrait;

    /**
     * @psalm-param non-empty-string $string
     *
     * @dataProvider formattedMoneyExamples
     * @test
     */
    public function itParsesMoney(string $string, int $units): void
    {
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $formatter->setPattern('¤#,##0.00;-¤#,##0.00');

        $currencies = $this->prophesize(Currencies::class);

        $currencies->subunitFor(Argument::allOf(
            Argument::type(Currency::class),
            Argument::which('getCode', 'USD')
        ))->willReturn(2);

        $currencyCode = 'USD';
        $currency     = new Currency($currencyCode);

        $parser = new IntlMoneyParser($formatter, $currencies->reveal());
        $this->assertEquals($units, $parser->parse($string, $currency)->getAmount());
    }

    /**
     * @test
     */
    public function itCannotConvertStringToUnits(): void
    {
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $formatter->setPattern('¤#,##0.00;-¤#,##0.00');

        $currencyCode = 'USD';
        $currency     = new Currency($currencyCode);
        $parser       = new IntlMoneyParser($formatter, new ISOCurrencies());

        $this->expectException(ParserException::class);
        $parser->parse('THIS_IS_NOT_CONVERTABLE_TO_UNIT', $currency);
    }

    /**
     * @test
     */
    public function itWorksWithAllKindsOfLocales(): void
    {
        $formatter = new NumberFormatter('en_CA', NumberFormatter::CURRENCY);
        $formatter->setPattern('¤#,##0.00;-¤#,##0.00');

        $parser = new IntlMoneyParser($formatter, new ISOCurrencies());
        $money  = $parser->parse('$1000.00');

        $this->assertTrue(Money::CAD(100000)->equals($money));
    }

    /**
     * @test
     */
    public function itAcceptsAForcedCurrency(): void
    {
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $formatter->setPattern('¤#,##0.00;-¤#,##0.00');

        $currencyCode = 'CAD';
        $currency     = new Currency($currencyCode);
        $parser       = new IntlMoneyParser($formatter, new ISOCurrencies());
        $money        = $parser->parse('$1000.00', $currency);

        $this->assertEquals('100000', $money->getAmount());
        $this->assertEquals('CAD', $money->getCurrency()->getCode());
    }

    /**
     * @test
     */
    public function itSupportsFractionDigits(): void
    {
        $formatter = new NumberFormatter('en_US', NumberFormatter::CURRENCY);
        $formatter->setPattern('¤#,##0.00;-¤#,##0.00');
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 3);

        $parser = new IntlMoneyParser($formatter, new ISOCurrencies());
        $money  = $parser->parse('$1000.005');

        $this->assertEquals('100001', $money->getAmount());
    }

    /**
     * TODO: investigate why this test fails with segmentation fault.
     *
     * @group segmentation
     * @test
     */
    public function itSupportsFractionDigitsWithDifferentStyleAndPattern(): void
    {
        $formatter = new NumberFormatter('en_US', NumberFormatter::DECIMAL);
        $formatter->setPattern('¤#,##0.00;-¤#,##0.00');
        $formatter->setAttribute(NumberFormatter::FRACTION_DIGITS, 3);

        $parser = new IntlMoneyParser($formatter, new ISOCurrencies());
        $money  = $parser->parse('$1000.005');

        $this->assertEquals('100001', $money->getAmount());
    }

    /**
     * @group legacy
     * @test
     */
    public function itAcceptsOnlyACurrencyObject(): void
    {
        self::markTestIncomplete('Deprecation to be removed before merging this patch');

        $formatter = new NumberFormatter('en_CA', NumberFormatter::CURRENCY);
        $formatter->setPattern('¤#,##0.00;-¤#,##0.00');

        $parser = new IntlMoneyParser($formatter, new ISOCurrencies());

        $this->expectDeprecationMessage('Passing a currency as string is deprecated since 3.1 and will be removed in 4.0. Please pass a Money\Currency instance instead.');

        $parser->parse('$1000.00', 'EUR');
    }

    /**
     * @psalm-return non-empty-list<array{
     *     non-empty-string,
     *     int
     * }>
     */
    public function formattedMoneyExamples(): array
    {
        return [
            ['$1000.50', 100050],
            ['$1000.00', 100000],
            ['$1000.0', 100000],
            ['$1000.00', 100000],
            ['$0.01', 1],
            ['$0.00', 0],
            ['$1', 100],
            ['-$1000', -100000],
            ['-$1000.0', -100000],
            ['-$1000.00', -100000],
            ['-$0.01', -1],
            ['-$1', -100],
            ['$1000', 100000],
            ['$1000.0', 100000],
            ['$1000.00', 100000],
            ['$0.01', 1],
            ['$1', 100],
            ['$.99', 99],
            ['-$.99', -99],
            ['$99.', 9900],
        ];
    }
}
