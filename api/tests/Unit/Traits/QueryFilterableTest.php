<?php

namespace Tests\Unit\Traits;

use App\Traits\Models\QueryFilterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;

class QueryFilterableTest extends TestCase {
    use MockeryPHPUnitIntegration;

    /**
     * @var QueryFilterableTestClass
     */
    private $instance;
    /**
     * @var Builder|Mock
     */
    private $query;
    /**
     * @var Request|Mock
     */
    private $request;

    private $testFilter = 'filter';

    public function setUp(): void {
        parent::setUp();

        $this->instance = new QueryFilterableTestClass();
        $this->query = Mockery::mock(Builder::class);
        $this->request = Mockery::mock(Request::class);
    }

    public function testQueryIsFilteredBasicByOnePropertyWhenIsPresentInRequestAndAllowed() {
        $this->instance->filterableProperties = ['name'];

        $this->request
            ->shouldReceive('query')
            ->with('filter')
            ->andReturn(['name' => $this->testFilter]);

        $this->query
            ->shouldReceive('where')
            ->with('name', $this->testFilter)
            ->once();

        $this->instance->scopeFilter($this->query, $this->request);
    }

    public function testQueryIsFilteredByNullValue() {
        $this->instance->filterableProperties = ['name'];

        $this->request
            ->shouldReceive('query')
            ->with('filter')
            ->andReturn(['name' => "\x00"]);

        $this->query
            ->shouldReceive('whereNull')
            ->with('name')
            ->once();

        $this->instance->scopeFilter($this->query, $this->request);
    }

    public function testQueryIsFilteredWithNotByOneProperty() {
        $this->instance->filterableProperties = ['name'];

        $this->request
            ->shouldReceive('query')
            ->with('filter')
            ->andReturn(['name' => ['not' => $this->testFilter]]);

        $this->query
            ->shouldReceive('whereNot')
            ->with('name', $this->testFilter)
            ->once();

        $this->instance->scopeFilter($this->query, $this->request);
    }

    /**
     * @dataProvider filterOperatorProvider
     */
    public function testQueryIsFilteredWithFilterOperatorByOneProperty(
        $filterType,
        $expectedOperator
    ) {
        $this->instance->filterableProperties = ['name'];

        $this->request
            ->shouldReceive('query')
            ->with('filter')
            ->andReturn(['name' => [$filterType => $this->testFilter]]);

        $this->query
            ->shouldReceive('where')
            ->with('name', $expectedOperator, $this->testFilter)
            ->once();

        $this->instance->scopeFilter($this->query, $this->request);
    }

    public function testQueryIsFilteredByNullValueWithNotOperator() {
        $this->instance->filterableProperties = ['name'];

        $this->request
            ->shouldReceive('query')
            ->with('filter')
            ->andReturn(['name' => ['not' => "\x00"]]);

        $this->query
            ->shouldReceive('whereNotNull')
            ->with('name')
            ->once();

        $this->instance->scopeFilter($this->query, $this->request);
    }

    public function testQueryIsFilteredByInOperator() {
        $this->instance->filterableProperties = ['name'];

        $filter = ['a', 'b', 'c'];

        $this->request
            ->shouldReceive('query')
            ->with('filter')
            ->andReturn(['name' => ['in' => implode(',', $filter)]]);

        $this->query
            ->shouldReceive('whereIn')
            ->with('name', $filter)
            ->once();

        $this->instance->scopeFilter($this->query, $this->request);
    }

    public function testQueryIsFilteredByNotInOperator() {
        $this->instance->filterableProperties = ['name'];

        $filter = ['a', 'b', 'c'];

        $this->request
            ->shouldReceive('query')
            ->with('filter')
            ->andReturn(['name' => ['notin' => implode(',', $filter)]]);

        $this->query
            ->shouldReceive('whereNotIn')
            ->with('name', $filter)
            ->once();

        $this->instance->scopeFilter($this->query, $this->request);
    }

    public function testQueryIsNotFilteredWithInvalidFilterOperator() {
        $this->instance->filterableProperties = ['name'];

        $this->request
            ->shouldReceive('query')
            ->with('filter')
            ->andReturn(['name' => ['not_a_filter' => $this->testFilter]]);

        $this->query->shouldNotReceive('where');

        $this->instance->scopeFilter($this->query, $this->request);
    }

    public function testQueryIsFilteredByMultiplePropertiesWhenArePresentInRequestAndAllowed() {
        $this->instance->filterableProperties = ['first_name', 'last_name'];

        $this->request
            ->shouldReceive('query')
            ->with('filter')
            ->andReturn([
                'first_name' => $this->testFilter . 1,
                'last_name' => $this->testFilter . 2,
            ]);

        $this->query
            ->shouldReceive('where')
            ->with('first_name', $this->testFilter . 1)
            ->once();
        $this->query
            ->shouldReceive('where')
            ->with('last_name', $this->testFilter . 2)
            ->once();

        $this->instance->scopeFilter($this->query, $this->request);
    }

    public function testQueryIsOnlyFilteredByAllowedProperties() {
        $this->instance->filterableProperties = ['first_name'];

        $this->request
            ->shouldReceive('query')
            ->with('filter')
            ->andReturn([
                'first_name' => $this->testFilter . 1,
                'last_name' => $this->testFilter . 2,
            ]);

        $this->query
            ->shouldReceive('where')
            ->with('first_name', $this->testFilter . 1)
            ->once();
        $this->query
            ->shouldNotReceive('where')
            ->with('last_name', $this->testFilter . 2);

        $this->instance->scopeFilter($this->query, $this->request);
    }

    public function testQueryIsNotFilteredWhenFilterIsNotPresentInRequest() {
        $this->request
            ->shouldReceive('query')
            ->with('filter')
            ->andReturn(null);

        $this->query->shouldNotReceive('where')->withAnyArgs();

        $this->instance->scopeFilter($this->query, $this->request);
    }

    public function testQueryIsFilteredByExtendedProperties() {
        $this->instance->filterableProperties = [];

        $this->request
            ->shouldReceive('query')
            ->with('filter')
            ->andReturn(['name' => $this->testFilter]);

        $this->query
            ->shouldReceive('where')
            ->with('name', $this->testFilter)
            ->once();

        $this->instance->scopeFilter($this->query, $this->request, ['name']);
    }

    public function testQueryIsDefaultFilterableByNoProperties() {
        $this->request
            ->shouldReceive('query')
            ->with('filter')
            ->andReturn(['name' => $this->testFilter]);

        $this->query->shouldNotReceive('where')->withAnyArgs();

        $this->instance->scopeFilter($this->query, $this->request);
    }

    public static function filterOperatorProvider() {
        return [
            ['lt', '<'],
            ['le', '<='],
            ['ge', '>='],
            ['gt', '>'],
            ['like', 'LIKE'],
        ];
    }
}

/**
 * @property array $filterableProperties
 */
class QueryFilterableTestClass {
    use QueryFilterable;
}
