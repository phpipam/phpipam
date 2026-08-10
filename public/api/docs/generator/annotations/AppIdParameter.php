<?php
/**
 * The "app_id" path parameter shared by every route, kept in its own class
 * (and file) on purpose: mixing a scalar `schema: new OA\Schema(...)` with
 * the reusable Schema components in OpenApiSpec.php breaks their generation
 * — see the note there.
 */

use OpenApi\Attributes as OA;

#[OA\Parameter(
    parameter: 'app_id',
    name: 'app_id',
    description: 'API application identifier, created under Administration > API. Selects which application (and therefore which security model and permission set) handles the request.',
    in: 'path',
    required: true,
    schema: new OA\Schema(type: 'string', example: 'myapp')
)]
class AppIdParameter
{
}
