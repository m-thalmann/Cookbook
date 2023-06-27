<?php

namespace App\Http\Controllers\Cookbook;

use App\Http\Controllers\Controller;
use App\Models\Cookbook;
use App\OpenApi\Parameters\BaseParameters;
use App\OpenApi\Parameters\Cookbooks\IndexCookbooksParameters;
use App\OpenApi\RequestBodies\Cookbooks\CreateCookbookRequestBody;
use App\OpenApi\RequestBodies\Cookbooks\UpdateCookbookRequestBody;
use App\OpenApi\Responses\Cookbooks\CookbookCreatedResponse;
use App\OpenApi\Responses\Cookbooks\CookbookIndexResponse;
use App\OpenApi\Responses\Cookbooks\CookbookShowResponse;
use App\OpenApi\Responses\Cookbooks\CookbookShowWithUserResponse;
use App\OpenApi\Responses\Cookbooks\EditableCookbookIndexResponse;
use App\OpenApi\Responses\ForbiddenResponse;
use App\OpenApi\Responses\NoContentResponse;
use App\OpenApi\Responses\NotFoundResponse;
use App\OpenApi\Responses\TooManyRequestsResponse;
use App\OpenApi\Responses\UnauthorizedResponse;
use App\OpenApi\Responses\ValidationErrorResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Vyuldashev\LaravelOpenApi\Attributes as OpenApi;

#[OpenApi\PathItem]
class CookbookController extends Controller {
    /**
     * Lists all cookbooks the user is a part of
     *
     * If the `all` parameter is set and the user is an admin, all cookbooks are returned
     */
    #[
        OpenApi\Operation(
            tags: ['Cookbooks'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: IndexCookbooksParameters::class)]
    #[OpenApi\Response(factory: CookbookIndexResponse::class, statusCode: 200)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function index(Request $request) {
        $all = $request->exists('all');

        $cookbooks = Cookbook::query()
            ->withCount(['recipes', 'users'])
            ->sort($request)
            ->search($request);

        if (!$all || !authUser()->is_admin) {
            $cookbooks->forUser(authUser());
        }

        return response()->pagination(
            fn($perPage) => $cookbooks->paginate($perPage)
        );
    }

    /**
     * Lists all cookbooks the authenticated user can administrate
     */
    #[OpenApi\Parameters(factory: BaseParameters::class)]
    #[
        OpenApi\Operation(
            tags: ['Cookbooks'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[
        OpenApi\Response(
            factory: EditableCookbookIndexResponse::class,
            statusCode: 200
        )
    ]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function indexEditable() {
        $cookbooks = Cookbook::query()
            ->forUser(authUser(), mustBeAdmin: true)
            ->orderBy('name', 'asc')
            ->select(['id', 'name'])
            ->get();

        return JsonResource::make($cookbooks);
    }

    /**
     * Creates a new cookbook and adds the authenticated user as admin
     */
    #[
        OpenApi\Operation(
            tags: ['Cookbooks'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: BaseParameters::class)]
    #[OpenApi\RequestBody(factory: CreateCookbookRequestBody::class)]
    #[
        OpenApi\Response(
            factory: CookbookCreatedResponse::class,
            statusCode: 201
        )
    ]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: ForbiddenResponse::class, statusCode: 403)]
    #[
        OpenApi\Response(
            factory: ValidationErrorResponse::class,
            statusCode: 422
        )
    ]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function store(Request $request) {
        $this->verifyNoDemo();

        $this->authorize('create', Cookbook::class);

        $data = $request->validate([
            'name' => ['required', 'filled', 'max:100'],
        ]);

        /**
         * @var Cookbook
         */
        $cookbook = Cookbook::create($data);
        $cookbook->users()->attach(authUser(), [
            'is_admin' => true,
        ]);

        return JsonResource::make($cookbook)
            ->response()
            ->setStatusCode(201);
    }

    /**
     * Returns information for the given cookbook including the information for the authenticated user
     */
    #[
        OpenApi\Operation(
            tags: ['Cookbooks'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: BaseParameters::class)]
    #[
        OpenApi\Response(
            factory: CookbookShowWithUserResponse::class,
            statusCode: 200
        )
    ]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function show(Cookbook $cookbook) {
        $this->authorizeAnonymously('view', $cookbook);

        if (authUser()) {
            $userCookbook = authUser()
                ->cookbooks()
                ->where('cookbook_id', $cookbook->id)
                ->first();

            if ($userCookbook) {
                $cookbook = $userCookbook;
            }
        }

        return JsonResource::make($cookbook);
    }

    /**
     * Updates an existing cookbook
     *
     * @param Cookbook $cookbook The cookbook's id
     */
    #[
        OpenApi\Operation(
            tags: ['Cookbooks'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: BaseParameters::class)]
    #[OpenApi\RequestBody(factory: UpdateCookbookRequestBody::class)]
    #[OpenApi\Response(factory: CookbookShowResponse::class, statusCode: 200)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: ValidationErrorResponse::class,
            statusCode: 422
        )
    ]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function update(Request $request, Cookbook $cookbook) {
        $this->verifyNoDemo();

        $this->authorizeAnonymously('update', $cookbook);

        $data = $request->validate([
            'name' => ['filled', 'max:100'],
        ]);

        $cookbook->update($data);

        return JsonResource::make($cookbook);
    }

    /**
     * Delete the given cookbook
     *
     * @param Cookbook $cookbook The cookbook's id
     */
    #[
        OpenApi\Operation(
            tags: ['Cookbooks'],
            security: 'AccessTokenSecurityScheme'
        )
    ]
    #[OpenApi\Parameters(factory: BaseParameters::class)]
    #[OpenApi\Response(factory: NoContentResponse::class, statusCode: 204)]
    #[OpenApi\Response(factory: UnauthorizedResponse::class, statusCode: 401)]
    #[OpenApi\Response(factory: NotFoundResponse::class, statusCode: 404)]
    #[
        OpenApi\Response(
            factory: TooManyRequestsResponse::class,
            statusCode: 429
        )
    ]
    public function destroy(Cookbook $cookbook) {
        $this->verifyNoDemo();

        $this->authorizeAnonymously('delete', $cookbook);

        $cookbook->delete();

        return response()->noContent();
    }
}
