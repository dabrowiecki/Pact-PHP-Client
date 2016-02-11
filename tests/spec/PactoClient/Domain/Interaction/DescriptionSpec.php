<?php

namespace spec\Madkom\PactoClient\Domain\Interaction;

use Madkom\PactoClient\Domain\Interaction\Description;
use Madkom\PactoClient\PactoException;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;

/**
 * Class DescriptionSpec
 * @package spec\Madkom\PactoClient\Domain\Interaction
 * @author  Dariusz Gafka <d.gafka@madkom.pl>
 * @mixin Description
 */
class DescriptionSpec extends ObjectBehavior
{

    /** @var  string */
    private $description;

    public function let()
    {
        $this->description = 'A request for foo';
        $this->beConstructedWith($this->description);
    }

    function it_is_initializable()
    {
        $this->shouldHaveType(\JsonSerializable::class);
    }

    public function it_should_return_description()
    {
        $this->jsonSerialize()->shouldReturn($this->description);
    }

    public function it_should_throw_exception_if_empty_value_passed()
    {
        $this->shouldThrow(PactoException::class)->during('__construct', ['']);
    }

}
