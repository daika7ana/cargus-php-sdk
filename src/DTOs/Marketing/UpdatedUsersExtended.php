<?php

declare(strict_types=1);

namespace Daika7ana\Cargus\DTOs\Marketing;

use Daika7ana\SimpleCollections\Collection;

final readonly class UpdatedUsersExtended
{
    /**
     * @param Collection<int, UpdatedUser> $updatedUsers
     */
    public function __construct(
        public Collection $updatedUsers,
        public int $totalRecords = 0,
    ) {}

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $users = [];

        foreach (is_array($data['UpdatedUsersModel'] ?? null) ? $data['UpdatedUsersModel'] : [] as $item) {
            if (is_array($item)) {
                $users[] = UpdatedUser::fromArray($item);
            }
        }

        return new self(
            updatedUsers: new Collection($users),
            totalRecords: (int) ($data['TotalRecords'] ?? $data['totalRecords'] ?? 0),
        );
    }
}
