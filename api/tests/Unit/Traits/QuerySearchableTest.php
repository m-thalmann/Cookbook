<?php

namespace Tests\Unit\Traits;

use App\Traits\Models\QuerySearchable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Mockery\Mock;
use PHPUnit\Framework\TestCase;

class QuerySearchableTest extends TestCase {
    use MockeryPHPUnitIntegration;

    /**
     * @var QuerySearchableTestClass
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

    private $testSearch = 'test_search';

    public function setUp(): void {
        parent::setUp();

        $this->instance = new QuerySearchableTestClass();
        $this->query = Mockery::mock(Builder::class);
        $this->request = Mockery::mock(Request::class);
    }

    public function testQueryIsSearchedBySetPropertiesWhenSearchIsPresentInRequest() {
        $this->instance->searchProperties = ['first_name', 'last_name'];

        $this->prepareRequestMock();
        $this->prepareQueryMock();

        $this->instance->scopeSearch($this->query, $this->request);
    }

    public function testQueryIsSearchedBySetAndExtendedPropertiesWhenSearchIsPresentInRequest() {
        $this->instance->searchProperties = ['first_name', 'last_name'];
        $extendedProperties = ['bio'];

        $this->prepareRequestMock();
        $this->prepareQueryMock($extendedProperties);

        $this->instance->scopeSearch(
            $this->query,
            $this->request,
            $extendedProperties
        );
    }

    public function testQueryIsNotSearchedWhenSearchIsNotPresentInRequest() {
        $this->request
            ->shouldReceive('query')
            ->with('search')
            ->andReturn(null);

        $this->query->shouldNotReceive('where')->withAnyArgs();

        $this->instance->scopeSearch($this->query, $this->request);
    }

    public function testQueryIsNotSearchedWhenNoSearchPropertiesAreSet() {
        $this->instance->searchProperties = [];

        $this->prepareRequestMock();

        $this->query->shouldNotReceive('where')->withAnyArgs();

        $this->instance->scopeSearch($this->query, $this->request);
    }

    public function testQueryIsDefaultSearchedByNoProperties() {
        $this->prepareRequestMock();

        $this->query->shouldNotReceive('where')->withAnyArgs();

        $this->instance->scopeSearch($this->query, $this->request);
    }

    private function prepareRequestMock() {
        $this->request
            ->shouldReceive('query')
            ->with('search')
            ->andReturn($this->testSearch);
    }

    private function prepareQueryMock($extendedProperties = []) {
        $this->query
            ->shouldReceive('where')
            ->withArgs(function ($arg) use ($extendedProperties) {
                if (!is_callable($arg)) {
                    return false;
                }

                /**
                 * @var Builder|Mock
                 */
                $subQuery = Mockery::mock(Builder::class);

                $searchProperties = array_merge(
                    $this->instance->searchProperties,
                    $extendedProperties
                );

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
    }
}

/**
 * @property array $searchProperties
 */
class QuerySearchableTestClass {
    use QuerySearchable;
}
