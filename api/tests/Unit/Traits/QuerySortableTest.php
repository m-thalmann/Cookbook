<?php

namespace Tests\Unit\Traits;

use App\Traits\Models\QuerySortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;

class QuerySortableTest extends TestCase {
    use MockeryPHPUnitIntegration;

    /**
     * @var QuerySortableTestClass
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

    public function setUp(): void {
        parent::setUp();

        $this->instance = new QuerySortableTestClass();
        $this->query = Mockery::mock(Builder::class);
        $this->request = Mockery::mock(Request::class);
    }

    public function testQueryIsSortedByOnePropertyWhenSortIsPresentInRequestAndPropertyIsAllowed() {
        $this->instance->sortableProperties = ['id'];

        $this->request
            ->shouldReceive('query')
            ->with('sort')
            ->andReturn('id');

        $this->query
            ->shouldReceive('orderBy')
            ->with('id', 'asc')
            ->once();

        $this->instance->scopeSort($this->query, $this->request);
    }

    public function testQueryIsSortedByMultiplePropertiesWhenSortIsPresentInRequestAndPropertiesAreAllowed() {
        $this->instance->sortableProperties = ['id', 'name', 'date_of_birth'];

        $this->request
            ->shouldReceive('query')
            ->with('sort')
            ->andReturn('id,name');

        $this->query
            ->shouldReceive('orderBy')
            ->with('id', 'asc')
            ->once();
        $this->query
            ->shouldReceive('orderBy')
            ->with('name', 'asc')
            ->once();

        $this->instance->scopeSort($this->query, $this->request);
    }

    public function testQueryIsOnlySortedByAllowedProperties() {
        $this->instance->sortableProperties = ['id', 'name', 'date_of_birth'];

        $this->request
            ->shouldReceive('query')
            ->with('sort')
            ->andReturn('id,is_admin');

        $this->query
            ->shouldReceive('orderBy')
            ->with('id', 'asc')
            ->once();

        $this->instance->scopeSort($this->query, $this->request);
    }

    public function testQueryIsSortedDescIfPrefixedWithDash() {
        $this->instance->sortableProperties = ['id', 'name', 'date_of_birth'];

        $this->request
            ->shouldReceive('query')
            ->with('sort')
            ->andReturn('id,-name');

        $this->query
            ->shouldReceive('orderBy')

            ->with('id', 'asc')
            ->once();
        $this->query
            ->shouldReceive('orderBy')
            ->with('name', 'desc')
            ->once();

        $this->instance->scopeSort($this->query, $this->request);
    }

    public function testQueryIsNotSortedWhenSortIsNotPresentInRequest() {
        $this->instance->sortableProperties = ['id'];

        $this->request
            ->shouldReceive('query')
            ->with('sort')
            ->andReturn(null);

        $this->query->shouldNotReceive('orderBy')->withAnyArgs();

        $this->instance->scopeSort($this->query, $this->request);
    }

    public function testQueryIsNotSortedWhenNoPropertyIsInAllowedList() {
        $this->instance->sortableProperties = ['id'];

        $this->request
            ->shouldReceive('query')
            ->with('sort')
            ->andReturn('name,date_of_birth');

        $this->query->shouldNotReceive('orderBy')->withAnyArgs();

        $this->instance->scopeSort($this->query, $this->request);
    }

    public function testQueryIsSortedWhenPropertyIsPresentInExtendedProperties() {
        $this->request
            ->shouldReceive('query')
            ->with('sort')
            ->andReturn('name');

        $this->query
            ->shouldReceive('orderBy')
            ->with('name', 'asc')
            ->once();

        $this->instance->scopeSort($this->query, $this->request, ['name']);
    }

    public function testQueryIsSortableByDefaultOnId() {
        $this->request
            ->shouldReceive('query')
            ->with('sort')
            ->andReturn('id');

        $this->query
            ->shouldReceive('orderBy')
            ->with('id', 'asc')
            ->once();

        $this->instance->scopeSort($this->query, $this->request);
    }
}

class QuerySortableTestClass {
    use QuerySortable;
}
