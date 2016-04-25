<?php

namespace spec\Money\Parser;

use Money\Currency;
use Money\Exception\ParserException;
use Money\Money;
use Money\MoneyParser;
use PhpSpec\ObjectBehavior;

class AggregateMoneyParserSpec extends ObjectBehavior
{
    function it_is_initializable(MoneyParser $moneyParser)
    {
        $this->beConstructedWith([$moneyParser]);
        $this->shouldHaveType('Money\Parser\AggregateMoneyParser');
    }

    function it_is_a_money_parser(MoneyParser $moneyParser)
    {
        $this->beConstructedWith([$moneyParser]);
        $this->shouldImplement(MoneyParser::class);
    }

    function it_parses_money(MoneyParser $moneyParser)
    {
        $this->beConstructedWith([$moneyParser]);

        $money = new Money(10000, new Currency('EUR'));

        $moneyParser->parse('€ 100', null)->willReturn($money);

        $this->parse('€ 100', null)->shouldReturn($money);
    }

    function it_throws_an_exception_when_money_cannot_be_parsed(MoneyParser $moneyParser)
    {
        $this->beConstructedWith([$moneyParser]);

        $money = new Money(10000, new Currency('EUR'));

        $moneyParser->parse('INVALID', null)->willThrow(ParserException::class);

        $this->shouldThrow(ParserException::class)->duringParse('INVALID', null);
    }
}
