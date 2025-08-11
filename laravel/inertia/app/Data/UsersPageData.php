<?php

namespace App\Data;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Spatie\LaravelData\Data;
use Spatie\TypeScriptTransformer\Attributes\TypeScript;

#[TypeScript]
class UsersPageData extends Data
{
    public function __construct(
        /**
         * @var array{
         *   users: array{
         *     id: int,
         *     name: string,
         *     email: string,
         *     email_verified_at: ?string,
         *     created_at: string,
         *     updated_at: string,
         *   }[],
         *   pagination: array{
         *     current_page: int,
         *     last_page: int,
         *     per_page: int,
         *     total: int,
         *     from: ?int,
         *     to: ?int,
         *     prev_page_url: ?string,
         *     next_page_url: ?string,
         *   },
         *   filters: array{
         *     search?: ?string
         *   }
         * }
         */
        public array $data,
    ) {
    }

    /**
     * @param LengthAwarePaginator<int, User> $users
     * @param Request $request
     * @return self
     */
    public static function fromPagination(LengthAwarePaginator $users, Request $request): self
    {
        return new self(
            data: [
                'users' => array_map(
                    fn (User $user) => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'email_verified_at' => $user->email_verified_at?->toISOString(),
                        'created_at' => $user->created_at->toISOString(),
                        'updated_at' => $user->updated_at->toISOString(),
                    ],
                    $users->items()
                ),
                'pagination' => [
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                    'total' => $users->total(),
                    'from' => $users->firstItem(),
                    'to' => $users->lastItem(),
                    'prev_page_url' => $users->previousPageUrl(),
                    'next_page_url' => $users->nextPageUrl(),
                ],
                'filters' => [
                    'search' => $request->search,
                ],
            ],
        );
    }
}
