<?php

namespace Tests\Unit\Traits;

use App\Traits\Models\QueryOrganizable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;

class QueryOrganizableTest extends TestCase {
    use MockeryPHPUnitIntegration;

    /**
     * @var QueryOrganizableTestClass
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
    private $testSearch = 'search';
    private $testSort = 'id';

    public function setUp(): void {
        parent::setUp();

        $this->instance = new QueryOrganizableTestClass();
        $this->query = Mockery::mock(Builder::class);
        $this->request = Mockery::mock(Request::class);
    }

    public function testQueryIsFilteredSearchedAndSorted() {
        $this->instance->filterableProperties = ['first_name', 'last_name'];
        $this->instance->searchProperties = [
            'first_name',
            'last_name',
            'description',
        ];
        $this->instance->sortableProperties = ['id', 'first_name', 'last_name'];

        $this->request
            ->shouldReceive('query')
            ->with('filter')
            ->andReturn(['first_name' => $this->testFilter])
            ->once();
        $this->request
            ->shouldReceive('query')
            ->with('search')
            ->andReturn($this->testSearch)
            ->once();
        $this->request
            ->shouldReceive('query')
            ->with('sort')
            ->andReturn($this->testSort)
            ->once();

        $this->query
            ->shouldReceive('where')
            ->with('first_name', $this->testFilter)
            ->once();
        $this->query
            ->shouldReceive('where')
            ->withArgs(function ($arg) {
                if (!is_callable($arg)) {
                    return false;
                }

                /**
                 * @var Builder|Mock
                 */
                $subQuery = Mockery::mock(Builder::class);

                $searchProperties = $this->instance->searchProperties;

                foreach ($searchProperties as $property) {
                    $subQuery
                        ->shouldReceive('orWhere')
                        ->with($property, 'LIKE', "%{$this->testSearch}%")
                        ->once();
                }

                $arg($subQuery);

                return true;
            })
            ->once();
        $this->query
            ->shouldReceive('orderBy')
            ->with('id', 'asc')
            ->once();

        $this->instance->scopeOrganized($this->query, $this->request);
    }
}

/**
 * @property array $filterableProperties
 * @property array $searchProperties
 * @property array $sortableProperties
 */
class QueryOrganizableTestClass {
    use QueryOrganizable;

    public $filterableProperties;
    public $searchProperties;
    public $sortableProperties;
}
