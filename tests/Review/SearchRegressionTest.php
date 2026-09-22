<?php

namespace Tests\Review;

use App\Api\Search;
use App\Tag;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SearchRegressionTest extends ReviewTestCase
{
    public function test_empty_search_with_filters_does_not_crash()
    {
        $search = new Search(new Request(['filters' => ['["beginner"]']]));
        $this->assertNull($search->query()->filtered()->get());
    }

    public function test_empty_count_is_zero()
    {
        $search = new Search(new Request(['count' => '']));
        $this->assertSame(0, $search->query()->get()->getData()->count);
    }

    public function test_missing_local_match_returns_an_empty_collection()
    {
        $search = new Search(new Request(['search' => 'missing', 'model' => Tag::class]));
        $this->assertCount(0, $search->query()->get());
    }

    /** @dataProvider invalidInputs */
    public function test_invalid_search_input_is_rejected_before_querying($input)
    {
        $this->expectException(ValidationException::class);
        new Search(new Request($input));
    }

    public static function invalidInputs()
    {
        return [
            [['search' => ['unexpected']]],
            [['model' => \App\User::class]],
            [['filters' => ['not json']]],
            [['filters' => ['null']]],
            [['filters' => ['[{}]']]],
            [['page' => -1]],
        ];
    }
}
